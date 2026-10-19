<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$since = now()->subDays(7);

$byCreated = DB::table('external_news')->where('status', 'PENDING')->where('created_at', '>=', $since);
$byPublished = DB::table('external_news')->where('status', 'PENDING')->where('publishedAt', '>=', $since);

printf("pending created_at  >= %s : %d\n", $since->toDateTimeString(), $byCreated->count());
printf("pending publishedAt >= %s : %d\n\n", $since->toDateTimeString(), $byPublished->count());

foreach (['publishedAt' => $byPublished, 'created_at' => $byCreated] as $label => $q) {
    $rows = (clone $q)->get();
    $titles = $rows->pluck('title');
    $dupes = $titles->duplicates()->count();

    printf("=== by %s : %d rows, %d unique titles, %d duplicated titles\n",
        $label, $rows->count(), $titles->unique()->count(), $dupes);

    $bySource = $rows->groupBy('sourceName')->map->count()->sortDesc();
    foreach ($bySource as $src => $n) {
        printf("     %-24s %4d\n", mb_substr((string) $src, 0, 22), $n);
    }

    $dupList = $titles->duplicates()->unique()->take(5);
    if ($dupList->isNotEmpty()) {
        echo "     duplicate examples:\n";
        foreach ($dupList as $t) {
            printf("       x%d  %s\n", $titles->filter(fn ($x) => $x === $t)->count(), mb_substr((string) $t, 0, 55));
        }
    }
    echo "\n";
}

echo "=== sample of what publishedAt>=7d would approve ===\n";
foreach ($byPublished->orderByDesc('publishedAt')->limit(12)->get(['sourceName', 'title', 'category', 'publishedAt']) as $r) {
    printf("  %s  [%-10s] %s\n", mb_substr((string) $r->publishedAt, 5, 11), mb_substr((string) $r->category, 0, 9), mb_substr((string) $r->title, 0, 52));
}