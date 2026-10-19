<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * The same bleed also fills `description`. Rows whose description contains
 * raw feed markup or very long runs of concatenated text are corrupted.
 * This checks the LIVE approved content, not just the pending backlog.
 */
echo "=== description length distribution, ALL rows ===\n";
foreach (['APPROVED', 'PENDING'] as $status) {
    $rows = DB::table('external_news')->where('status', $status)->get(['description']);
    $lens = $rows->map(fn ($r) => strlen((string) $r->description))->sort();
    $n = $lens->count();
    if (! $n) continue;

    $over2k = $lens->filter(fn ($l) => $l > 2000)->count();
    $over5k = $lens->filter(fn ($l) => $l > 5000)->count();
    $median = $lens->values()[(int) floor(0.5 * ($n - 1))];
    $p90 = $lens->values()[(int) floor(0.9 * ($n - 1))];

    printf("  %-9s rows %5d   median %5d   p90 %5d   max %6d   >2KB %4d   >5KB %4d\n",
        $status, $n, $median, $p90, $lens->max(), $over2k, $over5k);
}

echo "\n=== rows that look corrupted (percent-encoded URL soup or raw tags) ===\n";
$bad = DB::table('external_news')->where(function ($w) {
    $w->where('description', 'like', '%<a href%')
      ->orWhere('description', 'like', '%<img%')
      ->orWhere('description', 'like', '%</p><p>%')
      ->orWhere('description', 'like', '%d8%a%d8%b%')
      ->orWhere('description', 'like', '%/feed/%');
})->get(['id', 'status', 'sourceName', 'title', 'description']);

printf("  rows with feed markup left in description: %d\n\n", $bad->count());

foreach ($bad->groupBy('status') as $status => $g) {
    printf("  %s: %d\n", $status, $g->count());
}
echo "\n";
foreach ($bad->take(4) as $r) {
    printf("  [%s] %-14s %s\n", $r->status, mb_substr((string) $r->sourceName, 0, 12), mb_substr((string) $r->title, 0, 44));
    printf("        desc starts: %s\n", mb_substr((string) $r->description, 0, 100));
}

echo "\n=== are those descriptions already shown to visitors? ===\n";
printf("  APPROVED rows shown on the live site: %d\n", $bad->where('status', 'APPROVED')->count());