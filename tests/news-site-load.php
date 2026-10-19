<?php
/**
 * Measures the workload a news site actually produces, not just page views:
 * the RSS cron, the newspaper front-page fetcher, and how the database grows.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1) RSS fetch: cost of ONE cron run ===\n";
$t0 = microtime(true);
$r = (new App\Support\RssFetcher(60, 6, 0, false))->run();
$el = microtime(true) - $t0;
printf("  wall time      %.2fs\n", $el);
printf("  sources tried  %d\n", $r['ok'] + $r['failed']);
printf("  new items      %d\n", $r['newItems']);
printf("  peak memory    %.1f MB\n", memory_get_peak_usage(true) / 1024 / 1024);

echo "\n  run every 30 min => ".round(24 * 2)." runs/day\n";
printf("  worst case 6 new items per run => %d items/day\n", 24 * 2 * 6);
printf("  monthly inflow (same rate)     => %s items\n", number_format(24 * 2 * 6 * 30));

echo "\n=== 2) how fast does external_news actually grow? ===\n";
$log = DB::table('rss_fetch_logs')->orderBy('id')->get(['createdAt', 'newItems']);
printf("  fetch runs recorded: %d\n", $log->count());
$recent = $log->slice(-10);
foreach ($recent as $l) {
    printf("    %s  new=%s\n", mb_substr((string) $l->createdAt, 0, 16), $l->newItems);
}
$withNew = $log->filter(fn ($l) => (int) $l->newItems > 0);
printf("  runs that produced something: %d of %d\n", $withNew->count(), $log->count());

echo "\n=== 3) database growth projection ===\n";
$rowBytes = DB::table('external_news')->orderBy('id')->first() ? null : null;
$avg = DB::table('external_news')->select(DB::raw('AVG(LENGTH(title)+LENGTH(description)+LENGTH(link)+IFNULL(LENGTH(image),0)+40) a'))->value('a');
printf("  average row payload      %.0f bytes\n", $avg);
$perDay = 48 * 6;                    // conservative: 6 new items every run
printf("  at %d items/day -> %.1f MB/month\n", $perDay, $perDay * $avg * 30 / 1024 / 1024);
printf("  at %d items/day -> %.1f MB/month\n", 48 * 12, 48 * 12 * $avg * 30 / 1024 / 1024);
printf("  index overhead roughly doubles the data size\n");

echo "\n=== 4) newspaper fetcher: images are the real cost ===\n";
$ch = curl_init('https://www.irna.ir/');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_NOBODY => true]);
curl_exec($ch);
printf("  front-page HTML fetched per paper, every day\n");
$img = DB::table('newspaper_issues')->whereNotNull('imageUrl')->first();
if ($img) {
    $c = curl_init($img->imageUrl);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
    $body = curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    printf("  one stored front-page image: HTTP %d, %.2f MB\n", $code, strlen((string) $body) / 1024 / 1024);
    printf("  %d papers/day -> %.1f MB/day of images\n", 18, 18 * strlen((string) $body) / 1024 / 1024);
    printf("  monthly image growth: %.1f GB\n", 18 * strlen((string) $body) * 30 / 1024 / 1024 / 1024);
}

echo "\n=== 5) visitor page weight ===\n";
$html = file_get_contents('http://127.0.0.1:8000/');
printf("  homepage HTML      %6.1f KB\n", strlen($html) / 1024);
$css = glob('D:/2/public/build/assets/app-*.css')[0] ?? null;
$js  = array_map('filesize', glob('D:/2/public/build/assets/*.js'));
printf("  css                %6.1f KB\n", $css ? filesize($css) / 1024 : 0);
printf("  all js chunks      %6.1f KB\n", array_sum($js) / 1024);
printf("  visitor downloads only the chunks the page needs\n");

echo "\n=== 6) RSS subscribers polling the feed ===\n";
$feed = file_get_contents('http://127.0.0.1:8000/api/rss/feed');
printf("  main feed response  %6.1f KB\n", strlen($feed) / 1024);
printf("  feed category routes: %d\n", DB::select("SELECT COUNT(*) c FROM routes WHERE uri LIKE 'api/rss%'")[0]->c);
echo "  aggregators re-poll these every 15-60 min, forever\n";