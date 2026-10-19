<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$since = now()->subDays(7);

echo "=== publishedAt spread of the rows fetched in the last 7 days ===\n";
$rows = DB::table('external_news')->where('status', 'PENDING')->where('created_at', '>=', $since)->get();

foreach ($rows->groupBy(fn ($r) => substr((string) $r->publishedAt, 0, 7))->sortDesc() as $ym => $g) {
    printf("  %s  %3d rows\n", $ym, $g->count());
}

echo "\n=== per source: newest publishedAt among recently fetched ===\n";
foreach ($rows->groupBy('sourceName') as $src => $g) {
    printf("  %-22s newest %s   oldest %s   (%d rows)\n",
        mb_substr((string) $src, 0, 20),
        $g->max('publishedAt'), $g->min('publishedAt'), $g->count()
    );
}

echo "\n=== how many rows per publishedAt day, all pending ===\n";
foreach (DB::table('external_news')->where('status', 'PENDING')
    ->select(DB::raw('DATE(publishedAt) d'), DB::raw('COUNT(*) c'))
    ->groupBy('d')->orderByDesc('d')->limit(12)->get() as $r) {
    printf("  %s  %5d\n", $r->d, $r->c);
}

echo "\n=== check: are these feed items genuine or is publishedAt wrong? ===\n";
echo "  sample of oldest publishedAt among the 105:\n";
foreach ($rows->sortBy('publishedAt')->take(6)->get(['sourceName', 'title', 'publishedAt', 'created_at']) as $r) {
    printf("    %-16s pub %s  fetched %s  %s\n",
        mb_substr((string) $r->sourceName, 0, 14), $r->publishedAt, $r->created_at, mb_substr((string) $r->title, 0, 42));
}