<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CityNews;
use Illuminate\Support\Facades\DB;

$cols = ['id', 'title', 'link', 'description', 'image', 'sourceName', 'category', 'publishedAt', 'region'];

// --- size of one item as it is sent to the browser -------------------
$sample = DB::table('external_news')->where('status', 'APPROVED')
    ->orderByDesc('publishedAt')->limit(200)->get($cols);

$avg = 0;
$max = 0;
foreach ($sample as $r) {
    $b = strlen(json_encode($r, JSON_UNESCAPED_UNICODE));
    $avg += $b;
    $max = max($max, $b);
}
$avg = $avg / max(1, $sample->count());

echo "=== payload of ONE news item ===\n";
printf("  sample size   %d items\n", $sample->count());
printf("  average       %d bytes (%.2f KB)\n", $avg, $avg / 1024);
printf("  largest       %d bytes (%.2f KB)\n", $max, $max / 1024);
printf("  worst case page payload at the code's limit of 60: %.1f KB\n\n", 60 * $avg / 1024);

// --- how many items a 7d and a 30d window would hold ----------------
// The feed has been stale since 2026-09-15, so the live windows are empty.
// Measure the same two window widths against the last 30 days that had
// data, which gives the real article density for this site.
$newest = DB::table('external_news')->where('status', 'APPROVED')->max('publishedAt');
$anchor = strtotime($newest);

echo "=== window widths measured against real data ===\n";
echo "  anchor (newest article): {$newest}\n\n";

foreach ([7, 30] as $d) {
    $from = date('Y-m-d H:i:s', $anchor - $d * 86400);
    $to = $newest;

    $kw = DB::table('external_news')->where('status', 'APPROVED')
        ->whereBetween('publishedAt', [$from, $to])
        ->where(function ($w) {
            foreach (CityNews::KEYWORDS as $i => $k) {
                $i === 0 ? $w->where('title', 'like', "%{$k}%") : $w->orWhere('title', 'like', "%{$k}%");
            }
        })->count();

    $reg = DB::table('external_news')->where('status', 'APPROVED')
        ->whereNotNull('region')->where('region', '<>', '')
        ->whereBetween('publishedAt', [$from, $to])->count();

    $cat = DB::table('external_news')->where('status', 'APPROVED')
        ->where('category', 'LIKE', '%بوشهر%')
        ->whereBetween('publishedAt', [$from, $to])->count();

    printf(
        "  %2d day window -> title-match %3d, region-tagged %3d, bushehr-cat %3d  (union capped at 60)\n",
        $d, $kw, $reg, $cat
    );
}