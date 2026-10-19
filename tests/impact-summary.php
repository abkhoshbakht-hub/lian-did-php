<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== exact impact ===\n";
$undated = DB::table('external_news')->whereNull('publishedAt');
printf("  rows with NULL publishedAt      : %d\n", $undated->count());
printf("    of those, APPROVED (live)     : %d\n", (clone $undated)->where('status', 'APPROVED')->count());
printf("    of those, PENDING             : %d\n", (clone $undated)->where('status', 'PENDING')->count());

echo "\n=== do the descriptions of those rows read correctly? ===\n";
foreach ($undated->where('status', 'PENDING')->take(3)->get(['sourceName', 'title', 'description']) as $r) {
    printf("  %-14s %s\n", mb_substr((string) $r->sourceName, 0, 12), mb_substr((string) $r->title, 0, 46));
    printf("      desc: %s\n\n", mb_substr((string) $r->description, 0, 120));
}

echo "=== how sorting treats NULL dates on the live site ===\n";
$top = DB::table('external_news')->where('status', 'APPROVED')
    ->orderByDesc('publishedAt')->limit(3)->get(['title', 'publishedAt']);
foreach ($top as $t) {
    printf("  %-20s %s\n", $t->publishedAt, mb_substr((string) $t->title, 0, 42));
}

echo "\n=== can re-fetching repair the 93? ===\n";
$code = file_get_contents('D:/2/app/Support/RssFetcher.php');
printf("  store() skips an existing link_hash: %s\n",
    str_contains($code, "where('link_hash', \$hash)->exists()") ? 'YES - so re-fetch will NOT update them' : 'no');