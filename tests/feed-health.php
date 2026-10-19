<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== newest article per status ===\n";
foreach (DB::table('external_news')->select('status', DB::raw('COUNT(*) c'), DB::raw('MAX(publishedAt) newest'), DB::raw('MIN(publishedAt) oldest'))
    ->groupBy('status')->get() as $r) {
    printf("  %-10s count %6d   newest %s   oldest %s\n", $r->status, $r->c, $r->newest, $r->oldest);
}

echo "\n=== when was each row actually fetched (fetchedAt / created_at)? ===\n";
foreach ([7, 30, 90] as $d) {
    $n = DB::table('external_news')->where('created_at', '>=', now()->subDays($d))->count();
    printf("  rows created in the last %2d days: %d\n", $d, $n);
}

printf("  last created_at : %s\n", DB::table('external_news')->max('created_at'));

echo "\n=== rows per day, most recent 10 days that have any ===\n";
$rows = DB::table('external_news')
    ->select(DB::raw('DATE(created_at) d'), DB::raw('COUNT(*) c'))
    ->groupBy('d')->orderByDesc('d')->limit(10)->get();
foreach ($rows as $r) {
    printf("  %s  %5d\n", $r->d, $r->c);
}

echo "\n=== fetch log, last 15 runs ===\n";
$cols = Schema::hasColumn('external_news', 'fetchedAt') ? ['sourceName', 'status', 'fetchedAt', 'publishedAt'] : ['sourceName', 'status', 'created_at', 'publishedAt'];
foreach (DB::table('external_news')->orderByDesc('created_at')->limit(15)->get(['sourceName', 'status', 'publishedAt', 'created_at']) as $r) {
    printf("  %-22s %-9s published %s   stored %s\n", mb_substr((string) $r->sourceName, 0, 20), $r->status, $r->publishedAt, $r->created_at);
}

echo "\n=== rss_sources state ===\n";
if (Schema::hasTable('rss_sources')) {
    foreach (DB::table('rss_sources')->get() as $s) {
        printf("  id=%-3s %-9s %-28s last=%s\n",
            $s->id ?? '?', $s->status ?? '?', mb_substr((string) ($s->url ?? ''), 0, 28), $s->lastFetchedAt ?? $s->last_fetched_at ?? 'never'
        );
    }
}