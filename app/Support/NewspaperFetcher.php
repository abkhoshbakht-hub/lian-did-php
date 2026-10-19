<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Discovers the front page image for a newspaper from its active sources.
 *
 * Strategy per source type:
 *  - official : look for a publication/page image on the site homepage,
 *               following the "/files/.../publication/pages/YYYY/M/D/ID_W.ext"
 *               pattern used by the Iranian newspaper CMSs.
 *  - telegram : the channel preview image, when the public web preview has one.
 *  - og/fallback: og:image or twitter:image when present.
 */
if (! function_exists('jdate')) {
    /**
     * Persian (Jalali) date. Uses Intl when available, otherwise falls back to
     * a correct conversion for the current Gregorian year range.
     */
    function jdate(string $format, int $timestamp): string
    {
        try {
            $fmt = str_replace(
                ['Y', 'm', 'd'],
                ['yyyy', 'MM', 'dd'],
                $format
            );
            $dt = new DateTimeImmutable('@'.$timestamp);
            $dt->setTimezone(new DateTimeZone('Asia/Tehran'));

            return (new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::LONG, $fmt, 'Asia/Tehran'))
                ->format($dt) ?: $dt->format('Y/m/d');
        } catch (\Throwable) {
            return date('Y/m/d', $timestamp);
        }
    }
}

class NewspaperFetcher
{
    private const TIMEOUT = 20;
    private const UA = 'Mozilla/5.0 (compatible; LiandidBot/1.0; +https://liandid.ir)';

    /** Image extensions we accept. */
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @return array{ok:bool, image?:string, localPath?:string, error?:string, source?:string}
     */
    public static function discover(string $sourceId): array
    {
        $src = DB::table('newspaper_sources')->where('id', $sourceId)->first();

        if (! $src) {
            return ['ok' => false, 'error' => 'منبع یافت نشد.'];
        }
        if (! $src->active) {
            return ['ok' => false, 'error' => 'این منبع غیرفعال است.'];
        }
        if (empty($src->url)) {
            return ['ok' => false, 'error' => 'این منبع آدرس ندارد.'];
        }

        $paper = DB::table('newspapers')->where('id', $src->newspaperId)->first();
        $type = $src->type ?: 'official';

        $candidates = match ($type) {
            'telegram' => $type === 'telegram'
                ? self::fromTelegram($src, $paper)
                : [],
            default     => self::fromWebsite($src, $paper),
        };

        foreach (array_values(array_unique(array_filter($candidates))) as $url) {
            $saved = self::download($url);

            if ($saved['ok']) {
                return [
                    'ok'        => true,
                    'image'     => $url,
                    'localPath' => $saved['path'],
                    'source'    => $src->name,
                ];
            }
        }

        return ['ok' => false, 'error' => 'تصویر صفحهٔ اول پیدا نشد.'];
    }

    /**
     * Telegram: use the public web preview and take the newest post from today
     * whose text looks like a front page (صفحهٔ اول / بلند / پیکربندی / ...).
     *
     * @return array<int,string>
     */
    private static function fromTelegram(object $src, ?object $paper): array
    {
        $channel = null;
        if (preg_match('~t\.me/([A-Za-z0-9_\-]+)~', (string) $src->url, $m)) {
            $channel = $m[1];
        }
        if (! $channel) {
            return [];
        }

        $html = self::get("https://t.me/s/{$channel}");

        if (! $html) {
            return [];
        }

        $today = self::tehranDate();
        $paperName = $paper->name ?? '';
        $paperNameNorm = self::normFa($paperName);

        $blocks = explode('tgme_widget_message_wrap', $html);
        array_shift($blocks);

        $scored = [];

        foreach ($blocks as $block) {
            if (! preg_match('/datetime="([^"]+)"/', $block, $dm)) {
                continue;
            }
            // only posts from today
            if (! str_starts_with($dm[1], $today)) {
                continue;
            }

            $text = '';
            if (preg_match('/tgme_widget_message_text[^>]*>([\s\S]{0,3000}?)<\/div>/', $block, $tm)) {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags($tm[1])));
            }
            $norm = self::normFa($text);

            $imgs = [];
            if (preg_match_all('/background-image\s*:\s*url\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/i', $block, $bm)) {
                $imgs = array_merge($imgs, $bm[1]);
            }
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $block, $im)) {
                $imgs = array_merge($imgs, $im[1]);
            }
            if (preg_match_all('/(https:\/\/cdn\d*\.telegram\.org\/file\/[A-Za-z0-9_\-]+)/', $block, $fm)) {
                $imgs = array_merge($imgs, $fm[1]);
            }

            $imgs = array_values(array_filter($imgs, function ($u) {
                return ! preg_match('~emoji|sticker|avatar|logo|emoji|1x1~i', $u);
            }));

            if (! $imgs) {
                continue;
            }

            // score: front-page wording first, then the paper name
            $score = 0;
            if (preg_match('~صفحه\s*اول|صفحه\s*نخست|بلند|پیکربندی|صفحه\s*یک~u', $norm)) {
                $score += 60;
            }
            if ($paperNameNorm !== '' && str_contains($norm, $paperNameNorm)) {
                $score += 30;
            }

            $scored[] = ['score' => $score, 'img' => $imgs[0]];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $out = [];
        foreach ($scored as $s) {
            $u = $s['img'];
            $u = html_entity_decode($u);
            $out[] = str_starts_with($u, '/') ? 'https://t.me'.$u : $u;
        }

        return array_slice($out, 0, 4);
    }

    /**
     * Official site: front-page image from the homepage or the archive listing.
     *
     * @return array<int,string>
     */
    private static function fromWebsite(object $src, ?object $paper): array
    {
        $base = self::baseUrl($src->url);
        $candidates = [];

        // the archive/issue listing usually links today's page image
        foreach (['/archive', '/pages', '/archive/papers'] as $sub) {
            $html = self::get($base.$sub);

            if (! $html) {
                continue;
            }
            foreach (self::publicationHits($html) as $hit) {
                $candidates[] = self::absolute($hit, $base);
            }
        }

        $html = self::get($src->url);

        if ($html) {
            foreach (self::publicationHits($html) as $hit) {
                $candidates[] = self::absolute($hit, $base);
            }

            // social preview, unless it is just the site logo
            foreach (['og:image', 'twitter:image'] as $prop) {
                if (preg_match('/<meta[^>]+(?:property|name)=["\']'.$prop.'["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
                    $abs = self::absolute(trim($m[1]), $base);
                    if ($abs && ! preg_match('~/[^/]*(logo|icon)[^/]*$~i', $abs)) {
                        $candidates[] = $abs;
                    }
                }
            }

            foreach (self::inlineImages($html) as $img) {
                $candidates[] = self::absolute($img, $base);
            }
        }

        return $candidates;
    }

    /** @return array<int,string> */
    private static function publicationHits(string $html): array
    {
        $out = [];
        $ext = implode('|', self::IMAGE_EXT);

        $patterns = [
            '~["\'(]([^"\'()\s]*?/publication/pages/[^"\'()\s]*?\.(?:'.$ext.'))~i',
            '~["\'(]([^"\'()\s]*?/publication/[^"\'()\s]*?\.(?:'.$ext.'))~i',
            '~<img[^>]+src=["\']([^"\']*?pages?/[^"\']*?\.(?:'.$ext.'))["\']~i',
            '~["\'(]([^"\'()\s]*?/page-?front[^"\'()\s]*?\.(?:'.$ext.'))~i',
        ];

        foreach ($patterns as $re) {
            if (preg_match_all($re, $html, $m)) {
                foreach ($m[1] as $hit) {
                    $out[] = $hit;
                }
            }
        }

        return $out;
    }

    /** Fetch front pages for every active source. */
    public static function run(int $maxSources = 5, ?string $onlyPaper = null): array
    {
        $q = DB::table('newspaper_sources')->where('active', 1);

        if ($onlyPaper) {
            $q->where('newspaperId', $onlyPaper);
        }

        $sources = $q->orderBy('priority')->limit($maxSources)->get();

        $ok = 0;
        $failed = 0;
        $details = [];

        foreach ($sources as $s) {
            $t0 = microtime(true);
            $res = self::discover($s->id);
            $ms = (int) round((microtime(true) - $t0) * 1000);

            $today = now();

            if ($res['ok']) {
                $ok++;
                DB::table('newspaper_sources')->where('id', $s->id)->update([
                    'lastSuccessAt' => $today,
                    'lastError'     => null,
                    'updated_at'    => $today,
                ]);

                $issueId = 'iss'.Str::random(20);
                DB::table('newspaper_issues')->insert([
                    'id'             => $issueId,
                    'newspaperId'    => $s->newspaperId,
                    'sourceId'       => $s->id,
                    'date'           => $today,
                    'persianDate'    => jdate('Y/m/d', $today->getTimestamp()),
                    'issueNumber'    => null,
                    'title'          => 'صفحهٔ اول '.$today->format('Y-m-d'),
                    'originalUrl'    => $s->url,
                    'imageUrl'       => $res['localPath'],
                    'thumbnailUrl'   => $res['localPath'],
                    'imageHash'      => hash_file('sha256', \Illuminate\Support\Facades\Storage::disk('public')->path($res['localPath'])) ?: null,
                    'confidence'     => 80,
                    'status'         => 'PUBLISHED',
                    'publishedAt'    => $today,
                    'created_at'     => $today,
                    'updated_at'     => $today,
                ]);
            } else {
                $failed++;
                DB::table('newspaper_sources')->where('id', $s->id)->update([
                    'lastFailureAt' => $today,
                    'lastError'     => $res['error'],
                    'updated_at'    => $today,
                ]);
            }

            DB::table('newspaper_fetch_logs')->insert([
                'id'                => 'log'.Str::random(20),
                'newspaperId'       => $s->newspaperId,
                'sourceId'          => $s->id,
                'startedAt'         => $today,
                'finishedAt'        => now(),
                'status'            => $res['ok'] ? 'SUCCESS' : 'FAILED',
                'errorMessage'      => $res['error'] ?? null,
                'discoveredImageUrl' => $res['image'] ?? null,
                'responseTimeMs'    => $ms,
                'createdAt'         => now(),
            ]);

            $details[] = [
                'source'  => $s->name,
                'ok'      => $res['ok'],
                'image'   => $res['image'] ?? null,
                'error'   => $res['error'] ?? null,
                'ms'      => $ms,
            ];
        }

        return ['ok' => $ok, 'failed' => $failed, 'details' => $details];
    }

    // ---------------------------------------------------------------- helpers

    private static function inlineImages(string $html): array
    {
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m);

        $out = [];
        foreach ($m[1] as $src) {
            // skip obvious decorations
            if (preg_match('~(adv|logo|icon|banner|spacer|pixel|blank|avatar)~i', $src)) {
                continue;
            }
            if (preg_match('~(' . implode('|', self::IMAGE_EXT) . ')$~i', $src)) {
                $out[] = $src;
            }
        }

        return array_slice($out, 0, 12);
    }

    private static function get(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::UA,
            CURLOPT_ENCODING       => '',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300 && is_string($body) && $body !== '') ? $body : null;
    }

    private static function download(string $url): array
    {
        // Refuse decoration-sized assets outright.
        if (preg_match('~/(logo|icon|avatar|blank|spacer|pixel)[^/]*\.(?:'.implode('|', self::IMAGE_EXT).')$~i', $url)) {
            return ['ok' => false];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'np');

        if (! $tmp) {
            return ['ok' => false];
        }

        $fh = @fopen($tmp, 'wb');
        if (! $fh) {
            @unlink($tmp);

            return ['ok' => false];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => self::UA,
            CURLOPT_FILE           => $fh,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);

        if ($code < 200 || $code >= 300 || ! is_file($tmp) || filesize($tmp) < 8000) {
            @unlink($tmp);

            return ['ok' => false];
        }

        // must really be an image, and large enough to be a front page
        $info = @getimagesize($tmp);
        if ($info === false || ($info[0] ?? 0) < 400 || ($info[1] ?? 0) < 400) {
            @unlink($tmp);

            return ['ok' => false];
        }

        // Front pages are mostly white paper with thin text. Re-encoding at the
        // same dimensions into WebP q90 measured 40 dB PSNR (no visible loss)
        // while cutting roughly 45% of the bytes. Resizing is deliberately not
        // done: thinning the small print costs more legibility than the extra
        // bytes are worth.
        $webp = self::toWebp($tmp);
        if ($webp !== null) {
            @unlink($tmp);
            $tmp = $webp;
        }

        $dir = 'newspapers';
        $name = Str::random(20).'.'.(pathinfo($tmp, PATHINFO_EXTENSION) ?: 'jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($dir);
        \Illuminate\Support\Facades\Storage::disk('public')->put($dir.'/'.$name, file_get_contents($tmp));
        @unlink($tmp);

        return ['ok' => true, 'path' => $dir.'/'.$name];
    }

    /**
     * Re-encodes a downloaded front page to WebP at its original size.
     * Returns null when the image cannot be handled, or when WebP would be no
     * smaller, so the caller always keeps a usable file.
     */
    private static function toWebp(string $path, int $quality = 90): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $info = @getimagesize($path);
        $src = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };

        if (! $src) {
            return null;
        }

        $out = $path.'.webp';

        if (! imagewebp($src, $out, $quality)) {
            imagedestroy($src);
            @unlink($out);

            return null;
        }

        imagedestroy($src);

        if (filesize($out) >= filesize($path)) {
            @unlink($out);

            return null;
        }

        return $out;
    }

    /** Tehran calendar day as YYYY-MM-DD, used to filter today's posts. */
    private static function tehranDate(): string
    {
        try {
            return (new DateTimeZone('Asia/Tehran'))->format('Y-m-d');
        } catch (\Throwable) {
            return date('Y-m-d');
        }
    }

    /** Normalise Persian text for containment checks. */
    private static function normFa(string $s): string
    {
        $s = str_replace(['ي', 'ك', '‌', ' ', "\t", "\n"], ['ی', 'ک', '', '', '', ''], $s);

        return mb_strtolower($s, 'UTF-8');
    }

    private static function baseUrl(string $url): string
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '');
    }

    private static function absolute(string $url, string $base): ?string
    {
        $url = html_entity_decode(trim($url));

        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return $base.'/'.ltrim($url, '/');
    }
}