<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CityNews;
use Illuminate\Support\Facades\DB;

$now = now();
echo 'now                     '.$now->toDateTimeString()."\n";
echo 'newest approved article '.DB::table('external_news')->where('status', 'APPROVED')->max('publishedAt')."\n";
echo 'total approved rows     '.DB::table('external_news')->where('status', 'APPROVED')->count()."\n\n";

foreach ([7, 30] as $d) {
    $since = $now->copy()->subDays($d);

    $total = DB::table('external_news')->where('status', 'APPROVED')
        ->where('publishedAt', '>=', $since)->count();

    $kw = DB::table('external_news')->where('status', 'APPROVED')
        ->where('publishedAt', '>=', $since)
        ->where(function ($w) {
            foreach (CityNews::KEYWORDS as $i => $k) {
                $i === 0 ? $w->where('title', 'like', "%{$k}%") : $w->orWhere('title', 'like', "%{$k}%");
            }
        })->count();

    $reg = DB::table('external_news')->where('status', 'APPROVED')
        ->whereNotNull('region')->where('region', '<>', '')
        ->where('publishedAt', '>=', $since)->count();

    printf(
        "%-8s window | all approved %4d | title-match %4d | region-tagged %4d\n",
        "{$d}d", $total, $kw, $reg
    );
}

echo "\nwhat the page actually renders:\n";
foreach ([7, 30] as $d) {
    $rows = CityNews::all($d);
    $bytes = strlen(json_encode($rows, JSON_UNESCAPED_UNICODE));
    $titles = array_map(fn ($r) => mb_substr($r['title'] ?? '', 0, 50), $rows);
    printf("  %2dd -> %2d items, JSON payload %6d bytes (%.1f KB)\n", $d, count($rows), $bytes, $bytes / 1024);
    foreach ($titles as $t) {
        echo "        - {$t}\n";
    }
}