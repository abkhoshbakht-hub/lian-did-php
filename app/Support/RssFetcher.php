<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RSS engine built for cheap shared hosting.
 *
 * Resource guards:
 *  - conditional GET (ETag / Last-Modified): an unchanged feed costs ~0 bytes
 *  - per-source cooldown: a source is only re-checked every N minutes
 *  - round-robin: each run touches only a slice of the sources
 *  - wall-clock budget: the run stops by itself instead of overrunning
 *  - streaming XML: constant memory regardless of feed size
 *  - advisory lock: two runs never overlap
 *
 * Every item lands as PENDING; nothing is published without an admin.
 */
class RssFetcher
{
    private const TIMEOUT = 12;
    private const ITEMS_PER_SOURCE = 12;
    private const MAX_LINK_LENGTH = 2000;
    private const LOCK_KEY = 'rss.lock';

    /**
     * Feed tag name => the field it fills on the item.
     *
     * Used on the way in and on the way out: while streaming, the reader keeps
     * the mapped field name, so the closing tag must be mapped back before the
     * two can be compared.
     */
    private const FIELD_MAP = [
        'title' => 'title',
        'description' => 'desc',
        'summary' => 'desc',
        'content' => 'desc',
        'pubDate' => 'date',
        'published' => 'date',
        'updated' => 'date',
        'dc:date' => 'date',
        'link' => 'link',
    ];

    private array $state = [];

    public function __construct(
        private int $budgetSeconds = 60,
        private int $maxSources = 6,
        private int $cooldownMinutes = 60,
        private bool $includeFallbacks = false,
    ) {}

    // ───────────────────────────────────────────── state (ETag / Last-Modified / last run)

    private function stateKey(string $name): string
    {
        return 'rss.' . md5($name);
    }

    private function loadState(string $name): array
    {
        $key = $this->stateKey($name);
        if (! array_key_exists($key, $this->state)) {
            $raw = SettingStore::get($key);
            $this->state[$key] = $raw ? (json_decode($raw, true) ?: []) : [];
        }
        return $this->state[$key];
    }

    private function saveState(string $name, array $data): void
    {
        $key = $this->stateKey($name);
        $this->state[$key] = $data;
        SettingStore::set($key, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function onCooldown(string $name): bool
    {
        $s = $this->loadState($name);
        if (empty($s['last'])) return false;
        return (time() - (int) $s['last']) < $this->cooldownMinutes * 60;
    }

    // ───────────────────────────────────────────── run

    public function run(?callable $onSource = null): array
    {
        if (! $this->acquireLock()) {
            return ['locked' => true, 'sources' => 0, 'ok' => 0, 'failed' => 0,
                    'newItems' => 0, 'skipped' => 0, 'skippedCooldown' => 0, 'unchanged' => 0, 'details' => []];
        }

        try {
            return $this->doRun($onSource);
        } finally {
            $this->releaseLock();
        }
    }

    private function doRun(?callable $onSource): array
    {
        $started = now();
        $t0 = microtime(true);
        $deadline = $t0 + $this->budgetSeconds;

        $all = RssSources::all($this->includeFallbacks);

        // round-robin: rotate the starting point so every source is reached
        $cursor = (int) SettingStore::get('rss.cursor', 0);
        $ordered = array_merge(array_slice($all, $cursor), array_slice($all, 0, $cursor));

        $logId = 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22);
        DB::table('rss_fetch_logs')->insert([
            'id' => $logId, 'startedAt' => $started, 'status' => 'RUNNING',
            'totalSources' => 0, 'successSources' => 0, 'failedSources' => 0,
            'newItems' => 0, 'updatedItems' => 0, 'triggerType' => 'manual', 'createdAt' => $started,
        ]);

        $details = [];
        $touched = 0;
        $ok = 0; $failed = 0; $newItems = 0; $skipped = 0;
        $cooldown = 0; $unchanged = 0;

        foreach ($ordered as $source) {
            if ($touched >= $this->maxSources) break;
            if (microtime(true) >= $deadline) break;

            if ($this->onCooldown($source['name'])) {
                $cooldown++;
                $details[] = ['source' => $source['name'], 'status' => 'COOLDOWN', 'fetched' => 0, 'new' => 0, 'ms' => 0];
                if ($onSource) $onSource(end($details));
                continue;
            }

            $touched++;
            $result = $this->fetchOne($source);

            $details[] = $result;
            if ($onSource) $onSource($result);

            match ($result['status']) {
                'SUCCESS' => $ok++,
                'UNCHANGED' => $unchanged++,
                default => $failed++,
            };
            $newItems += $result['new'];
            $skipped += $result['skipped'];
        }

        SettingStore::set('rss.cursor', (string) (($cursor + max($touched, 1)) % max(count($all), 1)));

        $status = $touched === 0 ? 'COOLDOWN' : ($failed === 0 ? 'SUCCESS' : ($ok + $unchanged === 0 ? 'FAILED' : 'PARTIAL'));

        DB::table('rss_fetch_logs')->where('id', $logId)->update([
            'finishedAt' => now(),
            'durationMs' => (int) ((microtime(true) - $t0) * 1000),
            'status' => $status,
            'totalSources' => $touched,
            'successSources' => $ok + $unchanged,
            'failedSources' => $failed,
            'newItems' => $newItems,
            'updatedItems' => $skipped,
            'errorSummary' => $failed > 0
                ? implode('، ', array_column(array_filter($details, fn ($d) => $d['status'] === 'FAILED'), 'source'))
                : null,
        ]);

        return [
            'locked' => false, 'logId' => $logId,
            'sources' => $touched, 'ok' => $ok, 'unchanged' => $unchanged,
            'failed' => $failed, 'newItems' => $newItems, 'skipped' => $skipped,
            'skippedCooldown' => $cooldown, 'details' => $details,
        ];
    }

    // ───────────────────────────────────────────── one source

    private function fetchOne(array $source): array
    {
        $t = microtime(true);
        $state = $this->loadState($source['name']);

        $headers = ['User-Agent: Mozilla/5.0 (compatible; LianDidBot/1.0)'];
        if (! empty($state['etag']))    $headers[] = 'If-None-Match: ' . $state['etag'];
        if (! empty($state['lastmod'])) $headers[] = 'If-Modified-Since: ' . $state['lastmod'];

        $res = $this->download($source['url'], $headers);
        $ms = (int) ((microtime(true) - $t) * 1000);

        $base = ['source' => $source['name'], 'ms' => $ms];

        if ($res['error']) {
            $this->saveState($source['name'], $state + ['last' => time()]);
            return $base + ['status' => 'FAILED', 'fetched' => 0, 'new' => 0, 'skipped' => 0, 'error' => $res['error']];
        }

        // server says nothing changed -> we downloaded almost nothing
        if ($res['code'] === 304) {
            $this->saveState($source['name'], $state + ['last' => time()]);
            return $base + ['status' => 'UNCHANGED', 'fetched' => 0, 'new' => 0, 'skipped' => 0, 'error' => null];
        }

        $items = $this->parse($res['body'], $source);

        $new = 0; $skipped = 0;
        foreach ($items as $item) {
            $r = $this->store($item);
            $new += $r['inserted'];
            $skipped += $r['skipped'];
        }

        $this->saveState($source['name'], [
            'etag' => $res['etag'] ?: ($state['etag'] ?? null),
            'lastmod' => $res['lastmod'] ?: ($state['lastmod'] ?? null),
            'last' => time(),
        ]);

        return $base + ['status' => 'SUCCESS', 'fetched' => count($items), 'new' => $new,
                        'skipped' => $skipped, 'error' => null];
    }

    // ───────────────────────────────────────────── http

    private function download(string $url, array $headers): array
    {
        $responseHeaders = [];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
            // collect response headers so we can read ETag / Last-Modified
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
                if (preg_match('/^etag:\s*(.+)$/i', $line, $m)) {
                    $responseHeaders['etag'] = trim($m[1]);
                } elseif (preg_match('/^last-modified:\s*(.+)$/i', $line, $m)) {
                    $responseHeaders['lastmod'] = trim($m[1]);
                }
                return strlen($line);
            },
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $etag = (string) ($responseHeaders['etag'] ?? '');
        $lastmod = (string) ($responseHeaders['lastmod'] ?? '');

        if ($body === false || $err) {
            return ['error' => $err ?: 'دریافت ناموفق', 'code' => 0, 'body' => '', 'etag' => $etag, 'lastmod' => $lastmod];
        }
        if ($code >= 400) {
            return ['error' => "HTTP $code", 'code' => $code, 'body' => '', 'etag' => $etag, 'lastmod' => $lastmod];
        }

        return ['error' => null, 'code' => $code, 'body' => (string) $body, 'etag' => $etag, 'lastmod' => $lastmod];
    }

    // ───────────────────────────────────────────── parse (streaming)

    private function parse(string $xml, array $source): array
    {
        if (! class_exists(\XMLReader::class)) {
            return $this->parseWithSimpleXml($xml, $source);
        }

        $prev = libxml_use_internal_errors(true);
        $reader = new \XMLReader();
        $ok = @$reader->XML($xml);

        if (! $ok) {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            return $this->parseWithSimpleXml($xml, $source);
        }

        $items = [];
        $current = null;
        $inField = null;

        while (@$reader->read() && count($items) < self::ITEMS_PER_SOURCE) {
            if ($reader->nodeType === \XMLReader::ELEMENT) {
                $name = $reader->name;

                if (in_array($name, ['item', 'entry'], true)) {
                    $current = ['title' => '', 'link' => '', 'desc' => '', 'img' => '', 'date' => '', 'linkHref' => ''];
                    $inField = null;
                    continue;
                }

                if ($current === null) continue;

                if (array_key_exists($name, self::FIELD_MAP)) {
                    $inField = self::FIELD_MAP[$name];
                    continue;
                }

                if ($name === 'link') {
                    $href = (string) ($reader->getAttribute('href') ?? '');
                    $rel = (string) ($reader->getAttribute('rel') ?? '');
                    if ($rel === 'enclosure' && $href !== '') {
                        $current['img'] = $href;
                    } else {
                        $inField = 'link';
                    }
                } elseif ($name === 'enclosure') {
                    $type = (string) ($reader->getAttribute('type') ?? '');
                    $url = (string) ($reader->getAttribute('url') ?? '');
                    if ($url !== '' && str_starts_with($type, 'image')) $current['img'] = $url;
                } elseif ($name === 'guid' && $current['link'] === '') { $inField = 'link'; }

            } elseif ($reader->nodeType === \XMLReader::TEXT || $reader->nodeType === \XMLReader::CDATA) {
                if ($current !== null && $inField !== null) {
                    $current[$inField] .= $reader->value;
                }

            } elseif ($reader->nodeType === \XMLReader::END_ELEMENT) {
                // The open field holds a mapped name (date, desc, ...), so the
                // closing tag has to be mapped back before comparing. Without
                // this the field never closes and swallows every later node.
                if ($inField !== null && (self::FIELD_MAP[$reader->name] ?? null) === $inField) {
                    $inField = null;
                }

                // Closing the item must not depend on a field still being open.
                if ($reader->name === 'item' || $reader->name === 'entry') {
                    if ($item = $this->buildItem($current, $source)) $items[] = $item;
                    $current = null;
                    $inField = null;
                }
            }
        }

        $reader->close();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $items;
    }

    private function parseWithSimpleXml(string $xml, array $source): array
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($doc === false) return [];

        $nodes = isset($doc->item) ? $doc->item : ($doc->entry ?? []);
        $out = [];

        foreach ($nodes as $node) {
            $raw = [
                'title' => (string) ($node->title ?? ''),
                'link' => (string) ($node->link ?? ''),
                'desc' => (string) ($node->description ?? $node->summary ?? $node->content ?? ''),
                'img' => '',
                'date' => (string) ($node->pubDate ?? $node->published ?? $node->updated ?? ''),
            ];

            if (isset($node->enclosure)) {
                foreach ($node->enclosure as $enc) {
                    $t = (string) ($enc['type'] ?? '');
                    $u = (string) ($enc['url'] ?? '');
                    if ($u !== '' && str_starts_with($t, 'image')) { $raw['img'] = $u; break; }
                }
            }
            if ($raw['img'] === '' && preg_match('/<img[^>]+src="([^"]+)"/i', $raw['desc'], $m)) {
                $raw['img'] = html_entity_decode($m[1]);
            }

            if ($item = $this->buildItem($raw, $source)) $out[] = $item;
            if (count($out) >= self::ITEMS_PER_SOURCE) break;
        }

        return $out;
    }

    private function buildItem(array $raw, array $source): ?array
    {
        $link = trim($raw['link']);
        if ($link === '' || str_starts_with($link, '<')) $link = trim(strip_tags($link));
        if (! str_starts_with($link, 'http')) return null;
        $link = mb_substr($link, 0, self::MAX_LINK_LENGTH);

        $title = trim($raw['title']);
        if ($title === '' || $link === '') return null;

        $description = TextCleaner::description($raw['desc']);

        $image = trim($raw['img']);
        if ($image !== '' && (str_starts_with($image, 'data:') || strlen($image) > 2000)) $image = '';

        $ts = $raw['date'] !== '' ? strtotime($raw['date']) : false;

        return [
            'title' => TextCleaner::title($title),
            'link' => $link,
            'description' => $description,
            'image' => $image !== '' ? $image : null,
            'source' => $source['name'],
            'sourceName' => $source['name'],
            'category' => $source['category'],
            'publishedAt' => $ts ? date('Y-m-d H:i:s', $ts) : null,
        ];
    }

    // ───────────────────────────────────────────── persist

    private function store(array $item): array
    {
        $hash = hash('sha256', $item['link']);

        if (DB::table('external_news')->where('link_hash', $hash)->exists()) {
            return ['inserted' => 0, 'skipped' => 1];
        }

        try {
            // auto-approve by default; SettingStore key 'rss_auto_approve' = '0' keeps moderation
            $auto = SettingStore::get('rss_auto_approve', '1') !== '0';
            DB::table('external_news')->insert([
                'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
                'title' => $item['title'],
                'link' => $item['link'],
                'link_hash' => $hash,
                'description' => $item['description'],
                'image' => $item['image'],
                'source' => $item['source'],
                'sourceName' => $item['sourceName'],
                'category' => $item['category'],
                'status' => $auto ? 'APPROVED' : 'PENDING',
                'isBreaking' => 0,
                'region' => RegionDetector::detect($item['title'], $item['description']),
                'publishedAt' => $item['publishedAt'],
                'fetchedAt' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return ['inserted' => 1, 'skipped' => 0];
        } catch (\Throwable) {
            return ['inserted' => 0, 'skipped' => 1];
        }
    }

    // ───────────────────────────────────────────── lock

    private function acquireLock(): bool
    {
        $held = SettingStore::get(self::LOCK_KEY);
        if ($held && (time() - (int) json_decode($held, true)['t'] ?? 0) < ($this->budgetSeconds * 2 + 60)) {
            return false;
        }
        SettingStore::set(self::LOCK_KEY, json_encode(['t' => time()]));
        return true;
    }

    private function releaseLock(): void
    {
        DB::table('site_settings')->where('key', self::LOCK_KEY)->delete();
    }

    // ───────────────────────────────────────────── housekeeping

    /**
     * Drops PENDING items nobody acted on, so the table cannot grow forever.
     */
    public static function prunePending(int $days = 14): int
    {
        $cut = now()->subDays($days);

        // Imported rows can have a NULL created_at, so fall back to the arrival
        // timestamp, which is always populated.
        $ids = DB::table('external_news')
            ->where('status', 'PENDING')
            ->where(function ($w) use ($cut) {
                $w->where('created_at', '<', $cut)
                  ->orWhere(function ($w2) use ($cut) {
                      $w2->whereNull('created_at')->where('fetchedAt', '<', $cut);
                  });
            })
            ->limit(2000)
            ->pluck('id');

        if ($ids->isEmpty()) return 0;

        DB::table('external_news')->whereIn('id', $ids)->delete();

        return $ids->count();
    }
}
