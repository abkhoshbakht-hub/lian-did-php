<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\RssFetcher;
use App\Support\RssSources;
use Illuminate\Support\Facades\DB;

/**
 * Runs the REAL RssFetcher parser (via reflection, so nothing is stored) over
 * the feeds that previously produced undated rows and reports what comes out.
 */
$method = new ReflectionMethod(RssFetcher::class, 'parse');
$method->setAccessible(true);

$fetcher = new RssFetcher(120, 20, 0);
$broken = DB::table('external_news')->whereNull('publishedAt')->distinct()->pluck('sourceName')->all();

echo "feeds that produced undated rows: ".count($broken)."\n\n";

$allOk = true;
foreach (RssSources::all() as $src) {
    if (! in_array($src['name'], $broken, true)) continue;

    $ch = curl_init($src['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_ENCODING       => '',
    ]);
    $xml = curl_exec($ch);
    curl_close($ch);

    if (! $xml) { printf("  %-14s FETCH FAILED\n", $src['name']); continue; }

    $items = $method->invoke($fetcher, $xml, $src);
    $dated = count(array_filter($items, fn ($i) => ! empty($i['publishedAt'])));
    $maxDesc = 0;
    foreach ($items as $i) { $maxDesc = max($maxDesc, strlen((string) $i['description'])); }

    $ok = $dated === count($items) && count($items) > 0;
    $allOk = $allOk && $ok;

    printf("  %-14s items %3d   dated %3d   longest desc %5d   %s\n",
        $src['name'], count($items), $dated, $maxDesc, $ok ? 'OK' : 'STILL BROKEN');

    if ($items) {
        $f = $items[0];
        printf("       first: %s | %s\n", $f['publishedAt'] ?? 'NULL', mb_substr((string) $f['title'], 0, 40));
    }
}

echo "\n", $allOk ? "ALL FEEDS NOW PRODUCE DATES\n" : "SOME FEEDS STILL FAIL\n";