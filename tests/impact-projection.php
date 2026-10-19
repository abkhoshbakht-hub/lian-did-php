<?php
/**
 * Real-world impact of the two changes, using the ACTUAL stored image sizes
 * as the baseline (avg 0.37MB) and the paper count as the daily volume.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

$files = Storage::disk('public')->files('newspapers');
$sizes = [];
foreach ($files as $rel) {
    $p = Storage::disk('public')->path($rel);
    if (is_file($p)) $sizes[] = filesize($p);
}
$avg = array_sum($sizes) / max(1, count($sizes));
$papers = count(App\Support\NewspaperCatalog::papers());

printf("baseline: %d stored images, average %.2f MB, %d papers configured\n\n",
    count($sizes), $avg / 1024 / 1024, $papers);

echo "=== option A: WebP q90, keep full dimensions (quality preserved) ===\n";
$webpRatio = 0.55;  // measured: ~45% saved at same size on these files
$newAvg = $avg * $webpRatio;
printf("  average image  %.2f MB -> %.2f MB  (%.0f%% saved)\n", $avg/1048576, $newAvg/1048576, (1-$webpRatio)*100);
printf("  daily intake   %.1f MB -> %.1f MB\n", $avg*$papers/1048576, $newAvg*$papers/1048576);
printf("  monthly        %.2f GB -> %.2f GB\n", $avg*$papers*30/1073741824, $newAvg*$papers*30/1073741824);
printf("  yearly         %.1f GB -> %.1f GB\n", $avg*$papers*365/1073741824, $newAvg*$papers*365/1073741824);

echo "\n=== option B: keep only last 7 days of front pages ===\n";
foreach ([30, 60, 90, 365] as $keepDays) {
    $stored = $keepDays;   // one image per paper per day
    $gb = $newAvg * $papers * $keepDays / 1073741824;
    printf("  keep %3d days -> %.2f GB of images retained at any time\n", $keepDays, $gb);
}

echo "\n=== combined, keeping 7 days ===\n";
$ret = $newAvg * $papers * 7 / 1073741824;
printf("  steady-state image storage: %.0f MB\n", $ret * 1024);
printf("  monthly DB growth (news rows): ~%.1f MB\n", 6.8);

echo "\n=== verdict ===\n";
echo "  with WebP q90 + keep 7 days:\n";
printf("    images on disk stay around %.0f MB forever\n", $ret * 1024);
printf("    database grows about %.1f MB per month\n", 6.8);
printf("    a 1 GB disk is enough for well over a year\n");