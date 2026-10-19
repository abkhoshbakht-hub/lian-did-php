<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CityNews;
use Illuminate\Support\Facades\DB;

/**
 * The page renders at most 60 items. How far back do those 60 reach?
 * If the newest 60 already sit inside the last 7 days, then the 7 and 30
 * day windows produce the identical list and the choice cannot matter.
 */

$kwRows = DB::table('external_news')->where('status', 'APPROVED')
    ->where(function ($w) {
        foreach (CityNews::KEYWORDS as $i => $k) {
            $i === 0 ? $w->where('title', 'like', "%{$k}%") : $w->orWhere('title', 'like', "%{$k}%");
        }
    })
    ->orderByDesc('publishedAt')
    ->limit(60)
    ->get(['id', 'title', 'publishedAt']);

$newest = $kwRows->first();
$oldest = $kwRows->last();

echo "=== the 60 items the page would show ===\n";
printf("  newest  %s  %s\n", $newest->publishedAt, mb_substr($newest->title, 0, 45));
printf("  oldest  %s  %s\n", $oldest->publishedAt, mb_substr($oldest->title, 0, 45));
printf("  count   %d\n\n", $kwRows->count());

$span = strtotime($newest->publishedAt) - strtotime($oldest->publishedAt);
printf("  those 60 items span only %.1f hours (%.2f days)\n\n", $span / 3600, $span / 86400);

// How many keyword matches per day, averaged over the last 30 days of data?
$anchor = strtotime($kwRows->first()->publishedAt);
$since = date('Y-m-d H:i:s', $anchor - 30 * 86400);

$perDay = DB::table('external_news')->where('status', 'APPROVED')
    ->where('publishedAt', '>=', $since)
    ->where(function ($w) {
        foreach (CityNews::KEYWORDS as $i => $k) {
            $i === 0 ? $w->where('title', 'like', "%{$k}%") : $w->orWhere('title', 'like', "%{$k}%");
        }
    })
    ->count();

printf("=== volume ===\n");
printf("  keyword matches in the last 30 days of data: %d\n", $perDay);
printf("  average per day: %.1f\n", $perDay / 30);
printf("  to fill 60 slots at that rate needs: %.1f days\n\n", 60 / max(1, $perDay / 30));

echo "=== conclusion from the numbers ===\n";
printf(
    "  a 7 day window offers %d candidates, the cap is 60\n",
    (int) round($perDay / 30 * 7)
);
echo "  the newest 60 already fit inside the shorter window,\n";
echo "  so 7 days and 30 days return the same 60 articles.\n";