<?php
namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RssFetcher
{
    /** Mirror of nextjs/src/lib/rss-sources.ts */
    public const SOURCES = [
        ['name' => 'بوشهر خبر', 'url' => 'https://bushehrkhabar.com/feed/', 'category' => 'بوشهر'],
        ['name' => 'بوشهر نیوز', 'url' => 'https://bushehrnews.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'پی جی نیوز', 'url' => 'https://www.pgnews.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'کارانه بوشهر', 'url' => 'https://karanehbushehr.ir/?feed=rss2', 'category' => 'بوشهر'],
        ['name' => 'ندای استان', 'url' => 'https://nedayostan.ir/?feed=rss2', 'category' => 'بوشهر'],
        ['name' => 'سوک نیوز', 'url' => 'https://sooknews.ir/?feed=rss2', 'category' => 'بوشهر'],
        ['name' => 'چغادک نیوز', 'url' => 'https://choghadaknews.ir/rss', 'category' => 'بوشهر'],
        ['name' => 'نصیر بوشهر', 'url' => 'https://nasirbushehr.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'مهر بوشهر', 'url' => 'https://www.mehrnews.com/rss/service/Provinces/Booshehr', 'category' => 'بوشهر'],
        ['name' => 'ایسنا بوشهر', 'url' => 'https://www.isna.ir/rss/tp/225', 'category' => 'بوشهر'],
        ['name' => 'ایرنا', 'url' => 'https://www.irna.ir/rss', 'category' => 'ملی'],
        ['name' => 'ایسنا', 'url' => 'https://www.isna.ir/rss', 'category' => 'ملی'],
        ['name' => 'مهر', 'url' => 'https://www.mehrnews.com/rss', 'category' => 'ملی'],
        ['name' => 'فارس', 'url' => 'https://www.farsnews.ir/rss', 'category' => 'ملی'],
        ['name' => 'تسنیم', 'url' => 'https://www.tasnimnews.com/rss', 'category' => 'ملی'],
        ['name' => 'تسنیم بوشهر', 'url' => 'https://www.tasnimnews.ir/fa/rss/feeds/53/0/13/1', 'category' => 'بوشهر'],
        ['name' => 'انتخاب', 'url' => 'https://www.entekhab.ir/fa/rss/allnews', 'category' => 'ملی'],
        ['name' => 'عصر ایران', 'url' => 'https://www.asriran.com/fa/rss/allnews', 'category' => 'ملی'],
        ['name' => 'همشهری', 'url' => 'https://www.hamshahrionline.ir/rss', 'category' => 'روزنامه'],
        ['name' => 'دنیای اقتصاد', 'url' => 'https://donya-e-eqtesad.com/rss', 'category' => 'روزنامه'],
        ['name' => 'اطلاعات', 'url' => 'https://www.ettelaat.com/rss', 'category' => 'روزنامه'],
        ['name' => 'رسالت', 'url' => 'https://resalatnews.com/rss', 'category' => 'روزنامه'],
        ['name' => 'اعتماد', 'url' => 'https://etemadnewspaper.ir/fa/rss', 'category' => 'روزنامه'],
        ['name' => 'خبر ورزشی', 'url' => 'https://khabarvarzeshi.com/rss', 'category' => 'روزنامه'],
        ['name' => 'طرفداری', 'url' => 'https://www.tarafdari.com/rss', 'category' => 'روزنامه'],
    ];

    const TIMEOUT_MS = 8000;
    const BUDGET_MS = 40000;
    const LOCK_MIN = 12;

    /**
     * Endpoint for external cron (cron-job.org): never 404 — always JSON 200.
     * force=1 bypasses the rate-lock (manual test).
     */
    public static function refreshEndpoint(bool $force = false): array
    {
        $force = $force || env('RSS_FORCE') === '1';
        try {
            return ['success' => true, 'data' => self::fetchAllFeeds('cron', $force)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'خطای دریافت اخبار' . ' ' . mb_substr($e->getMessage(), 0, 200), 'data' => ['fatal' => true]];
        }
    }

    public static function lockRead(): ?array
    {
        $rows = DB::table('site_settings')->whereIn('key', ['rss:cronLock', 'rss:lastCron'])->get(['key', 'value']);
        $out = [];
        foreach ($rows as $r) { $out[$r->key] = $r->value; }
        return $out;
    }

    public static function lockWrite(string $key, string $value): void
    {
        DB::table('site_settings')->where('key', $key)->delete();
        DB::table('site_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
    }

    public static function fetchAllFeeds(string $triggerType = 'manual', bool $force = false): array
    {
        $t0 = microtime(true);
        $list = self::SOURCES;

        // Rate lock — skip if last run is fresh (unless forced)
        $lock = self::lockRead();
        $lastMs = isset($lock['rss:cronLock']) ? (Carbon::parse($lock['rss:cronLock'])->getTimestampMs() ?: 0) : 0;
        $diffMin = (microtime(true) * 1000 - $lastMs) / 60000;
        if (!$force && $diffMin < self::LOCK_MIN) {
            return ['skipped' => true, 'reason' => 'locked', 'lastRunMsAgo' => round($diffMin, 1)];
        }
        self::lockWrite('rss:cronLock', now()->toISOString());

        set_time_limit(115);

        // 1) Parallel text fetch of all feeds
        $xmls = self::multiGet(array_map(fn ($s) => $s['url'], $list), self::TIMEOUT_MS / 1000);

        // 2) Parse & prepare items
        $prepared = [];
        $results = [];
        $newBySource = [];
        foreach ($list as $src) {
            $t1 = microtime(true);
            $res = ['source' => $src['name'], 'status' => 'FAILED', 'items' => 0, 'error' => null, 'attempts' => 1, 'retryCount' => 0, 'durationMs' => 0, 'raw' => 0];
            $raw = $xmls[$src['url']] ?? null;
            $err = $xmls['@error:' . $src['url']] ?? null;
            if ($err) {
                $res['error'] = mb_substr((string) $err, 0, 200);
                if (stripos($res['error'], 'tim') !== false) $res['status'] = 'TIMEOUT';
            } else {
                try {
                    $items = self::parseFeed($raw);
                    $res['status'] = count($items) > 0 ? 'SUCCESS' : 'NO_NEW_ITEMS';
                    $res['raw'] = count($items);
                } catch (\Throwable $e) {
                    $res['error'] = 'parse-error ' . mb_substr($e->getMessage(), 0, 120);
                    $items = [];
                }
                foreach ($items as $it) {
                    $sourceDef = $src;
                    $isBushehr = $sourceDef['category'] === 'بوشهر'
                        || self::isBushehrNews($it['title'], $it['description'], $src['name']);
                    $category = $isBushehr ? 'بوشهر' : ($sourceDef['category'] ?: 'ملی');
                    $prepared[] = [
                        'title' => $it['title'],
                        'link' => $it['link'],
                        'description' => $it['description'],
                        'image' => $it['image'],
                        'source' => $src['name'],
                        'category' => $category,
                        'topic' => self::classifyNews($it['title'], $it['description']),
                        'autoApprove' => $category === 'روزنامه',
                        'publishedAt' => $it['publishedAt'],
                    ];
                }
            }
            $res['items'] = count($items ?? []);
            $res['durationMs'] = (int) round((microtime(true) - $t1) * 1000);
            $results[] = $res;
        }

        // 3) Dedupe against DB links
        $uniqueLinks = array_values(array_unique(array_map(fn ($p) => $p['link'], $prepared)));
        $existingByLink = [];
        foreach (array_chunk($uniqueLinks, 100) as $chunk) {
            $rows = DB::table('external_news')->whereIn('link', $chunk)->get(['id', 'link', 'status']);
            foreach ($rows as $r) { $existingByLink[$r->link] = ['id' => $r->id, 'status' => $r->status]; }
        }

        $seen = [];
        $toInsert = [];
        foreach ($prepared as $p) {
            if (!$p['title'] || !$p['link']) continue;
            if (isset($existingByLink[$p['link']]) || isset($seen[$p['link']])) continue;
            $seen[$p['link']] = true;
            $toInsert[] = $p;
        }

        $newItems = 0;
        $updatedItems = 0;
        $nowTs = now();
        if ($toInsert) {
            foreach (array_chunk($toInsert, 80) as $chunk) {
                $rows = [];
                foreach ($chunk as $p) {
                    $rows[] = [
                        'id' => RssFetcherIds::newId(),
                        'title' => $p['title'],
                        'link' => $p['link'],
                        'link_hash' => hash('sha256', $p['link']),
                        'description' => $p['description'],
                        'image' => $p['image'],
                        'source' => $p['source'],
                        'sourceName' => $p['source'],
                        'category' => $p['category'],
                        'status' => $p['autoApprove'] ? 'APPROVED' : 'PENDING',
                        'topic' => $p['topic'],
                        'publishedAt' => $p['publishedAt'],
                        'fetchedAt' => $nowTs,
                        'created_at' => $nowTs,
                        'updated_at' => $nowTs,
                    ];
                }
                try {
                    DB::table('external_news')->insertOrIgnore($rows);
                    $newItems += count($rows);
                    foreach ($chunk as $p) {
                        if ($p['source']) { $newBySource[$p['source']] = ($newBySource[$p['source']] ?? 0) + 1; }
                    }
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // 4) Update existing rows (light / full)
        $supdFull = "UPDATE external_news SET title = ?, description = ?, image = ?, publishedAt = ?, category = ?, topic = ?, fetchedAt = ?, status = IF(status = 'PENDING' AND ?, 'APPROVED', status), updated_at = ? WHERE id = ?";
        $supdLight = "UPDATE external_news SET category = ?, topic = ?, fetchedAt = ?, updated_at = ? WHERE id = ?";
        $pdo = DB::connection()->getPdo();
        $stmtFull = $pdo->prepare($supdFull);
        $stmtLight = $pdo->prepare($supdLight);
        $ts = now()->format('Y-m-d H:i:s');
        foreach ($prepared as $p) {
            $ex = $existingByLink[$p['link']] ?? null;
            if (!$ex) continue;
            try {
                if ($ex['status'] === 'APPROVED' || $ex['status'] === 'REJECTED') {
                    $stmtLight->execute([$p['category'], $p['topic'], $ts, $ts, $ex['id']]);
                } else {
                    $stmtFull->execute([$p['title'], $p['description'], $p['image'], $p['publishedAt'], $p['category'], $p['topic'], $ts, (int) $p['autoApprove'], $ts, $ex['id']]);
                }
                $updatedItems++;
            } catch (\Throwable $e) { /* ignore */ }
        }

        // 5) Logs
        $successSources = count(array_filter($results, fn ($r) => in_array($r['status'], ['SUCCESS', 'NO_NEW_ITEMS'])));
        $failedSources = count($results) - $successSources;
        $failed = array_values(array_filter($results, fn ($r) => in_array($r['status'], ['FAILED', 'TIMEOUT'])));
        try {
            $logId = RssFetcherIds::newId();
            DB::table('rss_fetch_logs')->insert([
                'id' => $logId,
                'startedAt' => gmdate('Y-m-d H:i:s', (int) ($t0)),
                'finishedAt' => now()->format('Y-m-d H:i:s'),
                'durationMs' => (int) round((microtime(true) - $t0) * 1000),
                'status' => $failedSources === 0 ? 'SUCCESS' : ($successSources === 0 ? 'FAILED' : 'PARTIAL'),
                'totalSources' => count($results),
                'successSources' => $successSources,
                'failedSources' => $failedSources,
                'newItems' => $newItems,
                'updatedItems' => $updatedItems,
                'errorSummary' => $failed ? mb_substr(implode(' | ', array_map(fn ($f) => $f['source'] . ': ' . ($f['error'] ?? $f['status']), array_slice($failed, 0, 10))), 0, 1000) : null,
                'triggerType' => $triggerType,
                'createdAt' => now()->format('Y-m-d H:i:s'),
            ]);
            $srcRows = [];
            foreach ($results as $r) {
                $srcRows[] = [
                    'id' => RssFetcherIds::newId(),
                    'fetchLogId' => $logId,
                    'source' => $r['source'],
                    'status' => $r['status'],
                    'startedAt' => now()->format('Y-m-d H:i:s'),
                    'finishedAt' => now()->format('Y-m-d H:i:s'),
                    'durationMs' => $r['durationMs'],
                    'fetchedCount' => $r['items'],
                    'newCount' => $newBySource[$r['source']] ?? 0,
                    'error' => mb_substr((string) $r['error'], 0, 500) ?: null,
                    'retryCount' => $r['retryCount'],
                ];
            }
            DB::table('rss_fetch_source_logs')->insert($srcRows);
        } catch (\Throwable $e) { /* ignore */ }

        self::lockWrite('rss:lastCron', now()->toISOString());
        $out = [
            'saved' => $newItems + $updatedItems,
            'newItems' => $newItems,
            'updatedItems' => $updatedItems,
            'totalSources' => count($results),
            'successSources' => $successSources,
            'failedSources' => $failedSources,
            'durationMs' => (int) round((microtime(true) - $t0) * 1000),
            'results' => array_map(fn ($r) => collect($r)->except('raw')->all(), $results),
        ];
        return $out;
    }

    /** Parallel GET (curl multi) — returns map url => body plus '@error:url' => err */
    public static function multiGet(array $urls, float $timeoutSec): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $u) {
            $ch = curl_init($u);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => (int) ceil($timeoutSec),
                CURLOPT_CONNECTTIMEOUT => (int) ceil($timeoutSec),
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_HTTPHEADER => ['Accept: application/rss+xml, application/xml, text/xml, */*'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$u] = $ch;
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running) { curl_multi_select($mh, 0.2); }
        } while ($running > 0);
        $out = [];
        foreach ($handles as $u => $ch) {
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $body = curl_multi_getcontent($ch);
            if ($err) { $out['@error:' . $u] = $err; }
            elseif ($code >= 400) { $out['@error:' . $u] = 'HTTP ' . $code; }
            elseif (!is_string($body) || $body === '') { $out['@error:' . $u] = 'empty-body'; }
            else { $out[$u] = $body; }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $out;
    }

    /** Parse an RSS 2.0 / Atom feed into normalized items (max 15) */
    public static function parseFeed(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            throw new \Exception('empty');
        }
        $items = [];
        libxml_use_internal_errors(true);
        $x = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        if ($x !== false) {
            $isRss = strtolower($x->getName()) === 'rss' || strtolower($x->getName()) === 'rdf' || strtolower($x->getName()) === 'channel';
            $list = $isRss ? $x->channel->item : (isset($x->entry) ? $x->entry : []);
            $max = 15;
            foreach ($list as $item) {
                if ($max-- <= 0) break;
                if ($isRss) {
                    $title = (string) $item->title;
                    $link = (string) $item->link;
                    if ($link === '' && count($item->link) > 1) { $link = (string) $item->link[1]; }
                    $desc = (string) ($item->description ?: '');
                    if ($desc === '') { $desc = (string) ($item->children('http://purl.org/rss/1.0/modules/content/')->encoded ?: ''); }
                    $pub = (string) $item->pubDate;
                    $pub = $pub ?: (string) $item->children('http://purl.org/dc/elements/1.1/')->date;
                    $rawBlock = $item->asXML() ?: '';
                } else {
                    $title = (string) $item->title;
                    $link = '';
                    foreach ($item->link as $l) {
                        $href = (string) $l['href'];
                        if ($href && ((string) $l['href'] !== '')) {
                            $rel = (string) $l['rel'];
                            if ($rel === '' || $rel === 'alternate') { $link = $href; break; }
                            if ($link === '') { $link = $href; }
                        }
                    }
                    $desc = (string) ($item->summary ?: '') ?: (string) $item->content;
                    $pub = (string) $item->updated;
                    $rawBlock = $item->asXML() ?: '';
                }
                if (!$title || !$link) continue;
                $image = self::extractImageFromXml($rawBlock);
                $items[] = [
                    'title' => self::cleanTitle($title),
                    'link' => $link,
                    'description' => self::cleanDesc($desc),
                    'image' => $image,
                    'publishedAt' => $pub ? now()->setTimestamp(strtotime($pub) ?: time())->format('Y-m-d H:i:s') : null,
                ];
            }
            if (count($items) > 0) return $items;
        }
        // Regex fallback (feeds with broken XML)
        $items = self::parseFeedRegex($xml);
        if (count($items) === 0) throw new \Exception('no-items');
        return $items;
    }

    private static function parseFeedRegex(string $xml): array
    {
        $items = [];
        if (!preg_match_all('#<item[\s>].*?</item>#is', $xml, $ms) && !preg_match_all('#<entry[\s>].*?</entry>#is', $xml, $ms)) {
            return $items;
        }
        $max = 15;
        foreach ($ms[0] as $block) {
            if ($max-- <= 0) break;
            $gt = fn ($t) => (trim(strip_tags($t)));
            preg_match('#<title>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?</title>#is', $block, $tm);
            preg_match('#<link(?:[^>]*href="([^"]+)")?>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?</link>#is', $block, $lm);
            preg_match('#<(description|summary|content)[^>]*>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?</\1>#is', $block, $dm);
            preg_match('#<(pubDate|published|updated|dc:date)[^>]*>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?</\1>#is', $block, $pm);
            $title = $tm[1] ?? '';
            $link = $lm[1] ?? ($lm[2] ?? '');
            $link = trim(strip_tags($link));
            if (!$title || !$link) continue;
            if (str_starts_with($link, '/')) {
                $pu = parse_url($link, PHP_URL_HOST);
                $link = ($pu['scheme'] ?? 'https') . '://' . ($pu['host'] ?? '') . $link;
            }
            $items[] = [
                'title' => self::cleanTitle(html_entity_decode($title)),
                'link' => $link,
                'description' => self::cleanDesc($dm[2] ?? ''),
                'image' => self::extractImageFromHtml($dm[2] ?? ''),
                'publishedAt' => isset($pm[2]) && strtotime($pm[2]) ? now()->setTimestamp(strtotime($pm[2]))->format('Y-m-d H:i:s') : null,
            ];
        }
        return $items;
    }

    public static function extractImageFromXml(string $block): string
    {
        if (!$block) return '';
        if (preg_match('#<enclosure[^>]*url="([^"\s]+)"[^>]*type="image#is', $block, $m)
            || preg_match('#<enclosure[^>]*type="image[^>]*url="([^"\s]+)"#is', $block, $m)
            || preg_match('#<enclosure[^>]*url="([^"\s]+)"#is', $block, $m)) {
            return html_entity_decode($m[1]);
        }
        // media:content / media:thumbnail attributes (namespace-agnostic)
        if (preg_match('#<(?:[a-zA-Z0-9-]+:)?(?:content|thumbnail)[^>]*\burl="([^"\s]+)"#is', $block, $m)) {
            return html_entity_decode($m[1]);
        }
        // <img> src inside CDATA description/content
        if (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $block, $m)) {
            return self::imgFilter($m[1]);
        }
        return '';
    }

    private static function extractImageFromHtml(string $html): string
    {
        if (!$html) return '';
        if (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $m)) {
            return self::imgFilter($m[1]);
        }
        return '';
    }

    private static function imgFilter(string $src): string
    {
        $src = html_entity_decode($src);
        if (str_starts_with($src, 'data:') || str_contains($src, 'gravatar') || str_contains($src, 'icon') || str_contains($src, 'logo')) return '';
        return $src;
    }

    public static function cleanDesc(string $s): string
    {
        $s = strip_tags($s);
        $s = html_entity_decode($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? '';
        return mb_substr(trim($s), 0, 300);
    }

    public static function cleanTitle(string $title): string
    {
        $cleaned = preg_replace('/[A-Za-z0-9\x{0660}-\x{0669}\x{06F0}-\x{06F9}]*[A-Za-z][A-Za-z0-9\x{0660}-\x{0669}\x{06F0}-\x{06F9}]*/u', '', $title) ?? $title;
        $cleaned = preg_replace('/\(\s*\)/u', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\[\s*\]/u', '', $cleaned) ?? $cleaned;
        $cleaned = str_replace(['""', '«»'], '', $cleaned);
        $cleaned = preg_replace('/\s{2,}/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/^[\s\-–—:؛،,|\/\\\\]+/u', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/[\s\-–—:؛،,|\/\\\\]+$/u', '', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);
        return preg_match('/[\p{L}\p{N}]/u', $cleaned) ? $cleaned : trim($title);
    }

    public static function isBushehrNews(?string $title, ?string $desc, ?string $sourceName): bool
    {
        if ($sourceName && str_contains($sourceName, 'بوشهر')) return true;
        $kw = '/بوشهر(ی|ستان)?|عسلویه|کنگان(ی)?|گناوه(ای)?|(?<![مد])دیر(?!کل|یت|عامل|ان|انه)|تنگستان(ی)?|دیلم(ی)?|خارگ(ی)?|برازجان(ی)?|دشتی|دشتستان(ی)?|جم‌پیلن|کیمیای پارس|پارس جنوبی|نخل تقی|سیراف(ی)?|اهرم(ی)?|چاه‌مبارک|پارسیان(ی)?/iu';
        if ($title && preg_match($kw, $title)) return true;
        if ($desc && preg_match($kw, $desc)) return true;
        return false;
    }

    /** Mirror of topics.ts classifyNews — keyword score then by title+desc */
    public static function classifyNews(string $title, string $description = ''): ?string
    {
        $text = $title . ' ' . $description;
        $best = null;
        $bestScore = 0;
        foreach (self::TOPICS as $cat) {
            foreach ($cat['subcategories'] as $sub) {
                $score = 0;
                foreach ($sub['keywords'] as $kw) {
                    if (str_contains($text, $kw)) { $score++; }
                }
                foreach ($sub['keywords'] as $kw) {
                    if (str_contains($title, $kw)) { $score++; }
                }
                if ($score > $bestScore) { $bestScore = $score; $best = $sub['slug']; }
            }
        }
        return $best;
    }

    /** Port of topics.ts menuCategories keywords */
    public const TOPICS = [
        ['slug' => 'siyasi', 'subcategories' => [
            ['name' => 'رهبری', 'slug' => 'rahbari', 'keywords' => ['مقام معظم رهبری', 'رهبر انقلاب', 'آیت‌الله خامنه‌ای', 'امام خامنه‌ای', 'بیت رهبری', 'نماینده ولی فقیه']],
            ['name' => 'دولت', 'slug' => 'dolat', 'keywords' => ['دولت', 'کابینه', 'وزیر', 'وزارت', 'هیئت وزیران', 'رئیس جمهور', 'معاون اول', 'نشست هیئت دولت', 'سخنگوی دولت', 'دولت چهاردهم', 'پاستور']],
            ['name' => 'مجلس', 'slug' => 'majles', 'keywords' => ['مجلس', 'نماینده', 'نمایندگان', 'نطق', 'طرح دوفوریتی', 'کمیسیون مجلس', 'بهارستان', 'رییس مجلس', 'رئیس مجلس']],
            ['name' => 'سیاست خارجی', 'slug' => 'siyasat-khareji', 'keywords' => ['سیاست خارجی', 'وزارت خارجه', 'سفیر', 'سفارت', 'دیپلماسی', 'مذاکرات', 'هیئت مذاکره', 'برجام', 'تحریم', 'سازمان ملل', 'آژانس']],
            ['name' => 'احزاب و تشکل‌ها', 'slug' => 'ahzab', 'keywords' => ['حزب', 'احزاب', 'جبهه', 'اصولگرایان', 'اصلاح‌طلبان', 'تشکل سیاسی', 'انتخابات']],
            ['name' => 'امنیت و دفاع', 'slug' => 'amniyat-defa', 'keywords' => ['سپاه', 'ارتش', 'وزارت دفاع', 'دفاع مقدس', 'امنیت', 'فرمانده کل', 'موشک', 'پدافند', 'نیروی دریایی', 'نیروی هوایی', 'نیروی زمینی']],
        ]],
        ['slug' => 'eghtesadi', 'subcategories' => [
            ['name' => 'اقتصاد ایران', 'slug' => 'eghtesad-iran', 'keywords' => ['اقتصاد ایران', 'تورم', 'بودجه', 'یارانه', 'قیمت', 'بازار', 'تجارت', 'بانک مرکزی', 'سود', 'تسهیلات', 'کالابرگ', 'قیمت طلا', 'قیمت دلار', 'ارز']],
            ['name' => 'اقتصاد جهان', 'slug' => 'eghtesad-jahan', 'keywords' => ['اقتصاد جهانی', 'صندوق بین‌المللی پول', 'بانک جهانی', 'بازار جهانی', 'وال‌استریت', 'اقتصاد آمریکا', 'اقتصاد چین', 'اوپک', 'نرخ بهره آمریکا']],
            ['name' => 'بورس و بازار سرمایه', 'slug' => 'bours', 'keywords' => ['بورس', 'شاخص کل', 'فرابورس', 'سهام', 'سرمایه‌گذاری', 'سرمایه‌گذار', 'عرضه اولیه', 'نقدشوندگی', 'حقیقی', 'حقوقی']],
            ['name' => 'مسکن و ساختمان', 'slug' => 'maskan', 'keywords' => ['مسکن', 'ملک', 'اجاره', 'رهن', 'نهضت ملی مسکن', 'ساخت و ساز', 'املاک', 'سقف اجاره', 'قیمت مسکن']],
            ['name' => 'انرژی', 'slug' => 'energy', 'keywords' => ['انرژی', 'نفت', 'گاز', 'بنزین', 'برق', 'نیروگاه', 'پتروشیمی', 'پالایشگاه', 'گازوئیل', 'پارس جنوبی', 'صادرات نفت']],
            ['name' => 'کشاورزی و صنعت', 'slug' => 'keshavarzi-sanat', 'keywords' => ['کشاورزی', 'زراعت', 'گندم', 'محصولات کشاورزی', 'صنعت', 'کارخانه', 'معدن', 'فولاد', 'خودروسازی', 'تولید داخلی', 'واحد تولیدی']],
        ]],
        ['slug' => 'ejtemaei', 'subcategories' => [
            ['name' => 'حوادث', 'slug' => 'havades', 'keywords' => ['حادثه', 'تصادف', 'آتش سوزی', 'زلزله', 'قتل', 'مصدوم', 'کشته', 'مجروح', 'آسیب‌دیده', 'سقوط', 'غرق', 'نزاع', 'درگیری', 'کشف جسد', 'تیراندازی', 'سرقت']],
            ['name' => 'بهداشت و درمان', 'slug' => 'behdasht', 'keywords' => ['بهداشت', 'درمان', 'بیمارستان', 'بیمار', 'پزشک', 'دارو', 'واکسن', 'سلامت', 'اورژانس', 'وزارت بهداشت', 'علوم پزشکی', 'پرستار']],
            ['name' => 'آموزش و پرورش', 'slug' => 'amoozesh', 'keywords' => ['آموزش و پرورش', 'مدرسه', 'دانش آموز', 'معلم', 'کنکور', 'کلاس درس', 'سازمان سنجش', 'امتحانات', 'مربی', 'طرح تربیت']],
            ['name' => 'گردشگری', 'slug' => 'gardeshgari', 'keywords' => ['گردشگری', 'مسافر', 'هتل', 'تور', 'سفر', 'موزه', 'بازدید', 'جاذبه', 'صنعت گردشگری', 'مسافرت']],
            ['name' => 'محیط زیست', 'slug' => 'mahiat-zist', 'keywords' => ['محیط زیست', 'آلودگی', 'هوا', 'طبیعت', 'تالاب', 'درختکاری', 'پسماند', 'بازیافت', 'گونه', 'حیات وحش', 'ریزگرد']],
            ['name' => 'قضایی و حقوقی', 'slug' => 'ghazayi', 'keywords' => ['قوه قضائیه', 'دادگاه', 'دادگستری', 'قاضی', 'دیوان', 'پرونده قضایی', 'بازداشت', 'حبس', 'رای', 'مجازات', 'قانون', 'لایحه']],
        ]],
        ['slug' => 'beynolmelal', 'subcategories' => [
            ['name' => 'خاورمیانه', 'slug' => 'khavarmianeh', 'keywords' => ['خاورمیانه', 'غزه', 'فلسطین', 'اسرائیل', 'لبنان', 'سوریه', 'عراق', 'یمن', 'حماس', 'حزب‌الله', 'قدس', 'صهیونیست']],
            ['name' => 'آسیا', 'slug' => 'asia', 'keywords' => ['چین', 'روسیه', 'هند', 'پاکستان', 'افغانستان', 'تاجیکستان', 'قرقیزستان', 'قزاقستان', 'ترکیه', 'ژاپن', 'کره', 'آسیای مرکزی']],
            ['name' => 'اروپا', 'slug' => 'oropa', 'keywords' => ['اروپا', 'فرانسه', 'آلمان', 'انگلیس', 'بریتانیا', 'ایتالیا', 'اسپانیا', 'اتحادیه اروپا', 'ناتو', 'اوکراین']],
            ['name' => 'آمریکا', 'slug' => 'amrika', 'keywords' => ['آمریکا', 'امریکا', 'کاخ سفید', 'کنگره', 'ترامپ', 'بایدن', 'پنتاگون', 'آمریکای لاتین', 'کانادا']],
            ['name' => 'بین‌الملل (عمومی)', 'slug' => 'beynolmelal-omoomi', 'keywords' => ['بین‌الملل', 'سازمان ملل', 'شورای امنیت', 'حقوق بشر', 'جهان', 'دنیا', 'کشورهای جهان', 'دیپلمات']],
        ]],
        ['slug' => 'fanavari', 'subcategories' => [
            ['name' => 'هوش مصنوعی', 'slug' => 'ai', 'keywords' => ['هوش مصنوعی', 'چت‌جی‌پی‌تی', 'چت بات', 'یادگیری ماشین', 'ربات', 'جی‌پی‌تی', 'اوپن‌ای‌آی', 'مهمات هوشمند', 'الگوریتم']],
            ['name' => 'موبایل و تبلت', 'slug' => 'mobile', 'keywords' => ['موبایل', 'گوشی', 'تبلت', 'آیفون', 'سامسونگ', 'شیائومی', 'اپل', 'اندروید', 'آی او اس']],
            ['name' => 'شبکه‌های اجتماعی', 'slug' => 'social', 'keywords' => ['اینستاگرام', 'تلگرام', 'واتساپ', 'فیسبوک', 'توئیتر', 'ایکس', 'شبکه اجتماعی', 'فیلترینگ', 'اینترنت', 'فضای مجازی']],
            ['name' => 'گجت و تکنولوژی', 'slug' => 'gadget', 'keywords' => ['گجت', 'تکنولوژی', 'فناوری', 'سخت‌افزار', 'کامپیوتر', 'لپ تاپ', 'امنیت سایبری', 'سایبری', 'هک']],
            ['name' => 'نرم‌افزار و اپلیکیشن', 'slug' => 'software', 'keywords' => ['نرم‌افزار', 'اپلیکیشن', 'اپ', 'برنامه', 'وب‌سایت', 'سایت', 'پلتفرم', 'استارتاپ', 'خدمات دیجیتال', 'فین‌تک']],
        ]],
        ['slug' => 'varzeshi', 'subcategories' => [
            ['name' => 'فوتبال ایران', 'slug' => 'footbal-iran', 'keywords' => ['لیگ برتر', 'پرسپولیس', 'استقلال', 'تراکتور', 'سپاهان', 'فوتبال ایران', 'تیم ملی', 'مربی ایرانی', 'جام حذفی', 'دیدار']],
            ['name' => 'فوتبال جهان', 'slug' => 'footbal-jahan', 'keywords' => ['لیگ قهرمانان', 'بارسلونا', 'رئال مادرید', 'منچستریونایتد', 'لیگ برتر انگلیس', 'لژیونر', 'فوتبال جهان', 'جام جهانی']],
            ['name' => 'کشتی و وزنه‌برداری', 'slug' => 'koshti', 'keywords' => ['کشتی', 'کشتی آزاد', 'کشتی فرنگی', 'وزنه‌برداری', 'ملی پوش', 'باشگاه کشتی']],
            ['name' => 'ورزش‌های رزمی', 'slug' => 'varzesh-ha-razmi', 'keywords' => ['بوکس', 'کاراته', 'تکواندو', 'جودو', 'ووشو', 'رزمی']],
            ['name' => 'نتایج زنده', 'slug' => 'natayej-zende', 'keywords' => ['نتایج زنده', 'برنامه بازی', 'جدول', 'گل', 'پیروزی', 'شکست', 'قهرمان', 'مدال']],
        ]],
        ['slug' => 'farhangi', 'subcategories' => [
            ['name' => 'سینما و تلویزیون', 'slug' => 'sinama', 'keywords' => ['سینما', 'فیلم', 'کارگردان', 'بازیگر', 'جشنواره فیلم', 'اکران', 'سریال', 'تلویزیون', 'پخش', 'نمایش خانگی']],
            ['name' => 'موسیقی', 'slug' => 'moosighi', 'keywords' => ['موسیقی', 'کنسرت', 'آهنگ', 'خواننده', 'آلبوم', 'ارکستر', 'موسیقی‌دان']],
            ['name' => 'ادبیات و کتاب', 'slug' => 'adabiat', 'keywords' => ['کتاب', 'ادبیات', 'نویسنده', 'شاعر', 'رمان', 'انتشارات', 'نمایشگاه کتاب', 'داستان']],
            ['name' => 'هنرهای تجسمی', 'slug' => 'honar', 'keywords' => ['نقاشی', 'مجسمه', 'گالری', 'هنر', 'عکاسی', 'طراحی', 'خوشنویسی', 'تجسمی']],
            ['name' => 'تئاتر', 'slug' => 'teatr', 'keywords' => ['تئاتر', 'نمایش', 'صحنه', 'بازیگری', 'تماشاخانه']],
        ]],
        ['slug' => 'elmi', 'subcategories' => [
            ['name' => 'پزشکی و سلامت', 'slug' => 'pezeshki', 'keywords' => ['پزشکی', 'سلامت', 'درمان', 'بیماری', 'پژوهش پزشکی', 'دانشگاه علوم پزشکی', 'طب', 'تغذیه', 'روانشناسی']],
            ['name' => 'نجوم و فضا', 'slug' => 'nojoom', 'keywords' => ['نجوم', 'فضا', 'سیاره', 'ستاره', 'ماهواره', 'ایستگاه فضایی', 'کیهان', 'رصد', 'تلسکوپ', 'پرواز فضایی']],
            ['name' => 'محیط زیست', 'slug' => 'mahiat', 'keywords' => ['محیط زیست', 'اقلیم', 'زیست', 'تنوع زیستی', 'گونه', 'اکوسیستم', 'آب', 'تغییر اقلیم']],
            ['name' => 'اختراعات', 'slug' => 'ekhtera', 'keywords' => ['اختراع', 'نوآوری', 'دانشمند', 'پژوهشگر', 'کشف', 'فناوری نوین']],
            ['name' => 'دانشگاه و پژوهش', 'slug' => 'daneshgah', 'keywords' => ['دانشگاه', 'پژوهش', 'تحقیق', 'استاد', 'دانشجو', 'پایان نامه', 'نشریه علمی', 'همایش', 'مقالات']],
        ]],
    ];
}

class RssFetcherIds
{
    public static function newId(): string
    {
        $ts = base_convert((string) (int) (microtime(true) * 1000), 10, 36);
        $rnd = bin2hex(random_bytes(6));
        return substr($ts . $rnd, 0, 25);
    }
}
