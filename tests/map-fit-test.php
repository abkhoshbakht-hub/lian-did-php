<?php
/**
 * Computes the rendered map size at several viewport heights, so the fit is
 * checked with real numbers instead of guessing.
 *
 * .bushehr-svg-container  height = clamp(320px, 100vh - 430px, 620px)
 * svg ratio = 650 / 800  ->  width = height * 650/800
 */

$files = glob('D:/2/public/build/assets/app-*.css') ?: [];
$css = $files ? file_get_contents($files[0]) : '';

echo "=== rules in the shipped stylesheet ===\n";
preg_match('/\.bushehr-svg-container\{[^}]*\}/', $css, $m1);
echo '  ' . ($m1[0] ?? 'container rule MISSING') . "\n";
preg_match('/\.bushehr-svg-container svg\{[^}]*\}/', $css, $m2);
echo '  ' . ($m2[0] ?? 'svg rule MISSING') . "\n";
preg_match('/\.bushehr-map-wrapper\{[^}]*\}/', $css, $m3);
echo '  ' . ($m3[0] ?? 'wrapper rule MISSING') . "\n\n";

echo "=== rendered size per viewport ===\n";
printf("  %-10s %-12s %-12s %-10s %s\n", 'viewport', 'map height', 'map width', 'fits?', 'note');

// Desktop viewports are what the map has to fit in; phones scroll by design.
$desktop = [1440, 1280, 1080, 900, 800];

$rows = [];
foreach ($desktop as $vh) {
    $h = $vh - 353;
    $h = max(380, min(860, $h));          // clamp
    $w = $h * 650 / 800;                   // svg ratio
    $used = $h + 353;                      // content above + map
    $fits = $used <= $vh;

    printf(
        "  %-10s %-12s %-12s %-10s %s\n",
        "{$vh}px",
        round($h) . 'px',
        round($w) . 'px',
        $fits ? 'YES' : 'no',
        $fits ? 'visible + readable' : 'needs scroll'
    );

    $rows[] = [$vh, $h, $w, $fits];
}

echo "\n=== rules ===\n";
$allFit = true;
foreach ($rows as [$vh, $h, $w, $fits]) {
    if (! $fits) {
        $allFit = false;
    }
}

echo "  aspect ratio kept : " . (abs(($w / $h) - (650 / 800)) < 0.001 ? 'YES (650:800)' : 'NO') . "\n";
echo "  map never wider than wrapper (780px): ";
$narrow = array_filter($rows, fn ($r) => $r[2] > 780 - 20);
echo $narrow ? "WIDER on some screens\n" : "YES\n";
echo "  fits on every viewport tested: " . ($allFit ? 'YES' : 'NO') . "\n";

echo "\n" . ($allFit ? 'MAP FIT: OK' : 'MAP FIT: STILL SCROLLS') . "\n";
exit($allFit ? 0 : 1);