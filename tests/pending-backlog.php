<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CityNews;
use Illuminate\Support\Facades\DB;

echo "=== pending backlog ===\n";
$rows = DB::table('external_news')->where('status', 'PENDING')->get();
printf("  total pending      %d\n", $rows->count());
printf("  oldest publishedAt %s\n", $rows->min('publishedAt'));
printf("  newest publishedAt %s\n", $rows->max('publishedAt'));
printf("  created_at range   %s .. %s\n", $rows->min('created_at'), $rows->max('created_at'));

echo "\n=== duplicate titles inside the backlog ===\n";
$dupes = DB::table('external_news')->where('status', 'PENDING')
    ->select('title', DB::raw('COUNT(*) c'))
    ->groupBy('title')->having('c', '>', 1)->count();
printf("  distinct-titles    %d\n", DB::table('external_news')->where('status', 'PENDING')->distinct()->count('title'));
printf("  titles seen >1x    %d\n", $dupes);

echo "\n=== how many would the cities page get per window if approved? ===\n";
foreach ([7, 30] as $d) {
    $since = now()->subDays($d);
    $kw = DB::table('external_news')->where('status', 'PENDING')
        ->where('publishedAt', '>=', $since)
        ->where(function ($w) {
            foreach (CityNews::KEYWORDS as $i => $k) {
                $i === 0 ? $w->where('title', 'like', "%{$k}%") : $w->orWhere('title', 'like', "%{$k}%");
            }
        })->count();

    $cat = DB::table('external_news')->where('status', 'PENDING')
        ->where('publishedAt', '>=', $since)
        ->where('category', 'LIKE', '%بوشهر%')->count();

    printf("  %2d days -> keyword %4d, bushehr-category %4d  (page cap is 60)\n", $d, $kw, $cat);
}

echo "\n=== where are the feed sources stored? ===\n";
foreach (DB::select("SHOW TABLES LIKE '%rss%'") as $t) {
    printf("  table %s -> %d rows\n", $t->{'Tables_in_liandid_php (like \'%rss%\')'} ?? '?', 0);
}
foreach (['rss_sources', 'rss_feeds', 'external_news'] as $t) {
    if (Schema::hasTable($t)) {
        printf("  %-16s exists, %d rows\n", $t, DB::table($t)->count());
    }
}