<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * Removes the rows the old parser stored without a date so the corrected
 * fetcher can pull them again. Only PENDING rows are touched: nothing that a
 * visitor has ever seen is removed.
 */
$dryRun = ! in_array('--apply', $_SERVER['argv'], true);

$q = DB::table('external_news')->whereNull('publishedAt')->where('status', 'PENDING');

printf("  candidates (PENDING, no date): %d\n", $q->count());
printf("  APPROVED with no date (kept) : %d\n", DB::table('external_news')->whereNull('publishedAt')->where('status', 'APPROVED')->count());

if (! $dryRun) {
    $deleted = $q->delete();
    printf("  deleted: %d\n", $deleted);
} else {
    echo "  (dry run - pass --apply to delete)\n";
}

printf("\n  remaining NULL publishedAt: %d\n", DB::table('external_news')->whereNull('publishedAt')->count());