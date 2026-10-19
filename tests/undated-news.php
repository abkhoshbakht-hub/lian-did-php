<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$since = now()->subDays(7);

echo "=== publishedAt presence, all pending ===\n";
$rows = DB::table('external_news')->where('status', 'PENDING')->get();
$dated = $rows->filter(fn ($r) => $r->publishedAt !== null && $r->publishedAt !== '');
$undated = $rows->filter(fn ($r) => $r->publishedAt === null || $r->publishedAt === '');
printf("  dated    %d\n  undated  %d\n\n", $dated->count(), $undated->count());

echo "=== per source: dated vs undated ===\n";
foreach ($rows->groupBy('sourceName')->sortByDesc(fn ($g) => $g->count()) as $src => $g) {
    $d = $g->filter(fn ($r) => $r->publishedAt !== null && $r->publishedAt !== '')->count();
    printf("  %-24s total %4d   dated %4d   UNDATED %4d%s\n",
        mb_substr((string) $src, 0, 22), $g->count(), $d, $g->count() - $d,
        $g->count() === $d ? '' : '   <-- never dated'
    );
}

echo "\n=== undated examples (what the page can never show) ===\n";
foreach ($undated->take(6) as $r) {
    printf("  %-16s %s\n", mb_substr((string) $r->sourceName, 0, 14), mb_substr((string) $r->title, 0, 60));
}

echo "\n=== does any of them mention a Bushehr town? ===\n";
$kw = ['بوشهر', 'برازجان', 'گناوه', 'کنگان', 'سیراف', 'جم', 'عسلویه', 'دیر', 'خارگ', 'اهرم'];
$hits = $undated->filter(function ($r) use ($kw) {
    foreach ($kw as $k) {
        if (str_contains((string) $r->title, $k)) return true;
    }
    return false;
});
printf("  undated rows whose title mentions a Bushehr town: %d\n", $hits->count());
foreach ($hits->take(8) as $r) {
    printf("    %-16s %s\n", mb_substr((string) $r->sourceName, 0, 14), mb_substr((string) $r->title, 0, 58));
}