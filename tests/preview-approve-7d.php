<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * Read-only preview of the exact set that would be approved:
 * PENDING rows published within the last 7 days.
 */
$since = now()->subDays(7);

$q = DB::table('external_news')
    ->where('status', 'PENDING')
    ->where('publishedAt', '>=', $since);

$rows = $q->orderByDesc('publishedAt')->get();

printf("window: publishedAt >= %s\n", $since->toDateTimeString());
printf("rows that would be approved: %d\n\n", $rows->count());

if ($rows->isEmpty()) {
    echo "nothing to approve\n";
    exit;
}

echo "=== newest 15 of them ===\n";
foreach ($rows->take(15) as $r) {
    printf("  %s  %-14s %s\n",
        mb_substr((string) $r->publishedAt, 5, 11),
        mb_substr((string) $r->sourceName, 0, 13),
        mb_substr((string) $r->title, 0, 48)
    );
}

echo "\n=== per source ===\n";
foreach ($rows->groupBy('sourceName')->sortByDesc(fn ($g) => $g->count()) as $src => $g) {
    printf("  %-16s %4d   (%s .. %s)\n",
        mb_substr((string) $src, 0, 15), $g->count(),
        mb_substr((string) $g->min('publishedAt'), 5, 11),
        mb_substr((string) $g->max('publishedAt'), 5, 11)
    );
}

echo "\n=== quality checks ===\n";
printf("  rows with no title        : %d\n", $rows->filter(fn ($r) => trim((string) $r->title) === '')->count());
printf("  rows with NULL date       : %d\n", $rows->filter(fn ($r) => ! $r->publishedAt)->count());
printf("  rows with no description  : %d\n", $rows->filter(fn ($r) => trim((string) $r->description) === '')->count());
printf("  duplicate titles in set   : %d\n", $rows->pluck('title')->duplicates()->count());

$kw = ['بوشهر', 'برازجان', 'گناوه', 'کنگان', 'سیراف', 'جم', 'عسلویه', 'دیر', 'خارگ', 'اهرم', 'خورموج', 'کاکی', 'بردخون'];
$local = $rows->filter(function ($r) use ($kw) {
    foreach ($kw as $k) {
        if (str_contains((string) $r->title, $k)) return true;
    }
    return false;
});
printf("  Bushehr-town news in set   : %d\n", $local->count());
foreach ($local->take(6) as $r) {
    printf("      %-14s %s\n", mb_substr((string) $r->sourceName, 0, 13), mb_substr((string) $r->title, 0, 52));
}

echo "\n=== what the cities page would show after approval ===\n";
$cityHits = $rows->filter(function ($r) use ($kw) {
    foreach ($kw as $k) {
        if (str_contains((string) $r->title, $k)) return true;
    }
    return false;
})->count();
printf("  matches for the city page: %d\n", $cityHits);