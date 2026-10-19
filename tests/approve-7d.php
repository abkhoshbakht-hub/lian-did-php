<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/**
 * Approves the PENDING rows published within the last 7 days.
 * Nothing is deleted; only the status column changes.
 */
$dryRun = ! in_array('--apply', $_SERVER['argv'], true);
$since = now()->subDays(7);

$before = DB::table('external_news')->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status');

echo "  status before:\n";
foreach ($before as $s => $c) printf("    %-9s %5d\n", $s, $c);

$q = DB::table('external_news')->where('status', 'PENDING')->where('publishedAt', '>=', $since);
printf("\n  matches: %d\n", $q->count());

if ($dryRun) {
    echo "  (dry run - pass --apply)\n";
    exit;
}

$updated = $q->update(['status' => 'APPROVED', 'updated_at' => now()]);
printf("  approved: %d\n", $updated);

$after = DB::table('external_news')->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status');
echo "\n  status after:\n";
foreach ($after as $s => $c) printf("    %-9s %5d\n", $s, $c);

printf("\n  newest APPROVED now: %s\n", DB::table('external_news')->where('status', 'APPROVED')->max('publishedAt'));
printf("  still PENDING        : %d\n", DB::table('external_news')->where('status', 'PENDING')->count());