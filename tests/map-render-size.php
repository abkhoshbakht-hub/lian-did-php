<?php
/**
 * Reports what the map wrapper / container / svg actually resolve to.
 * Uses the same clamp() maths the browser applies, reading the values
 * straight out of the shipped stylesheet instead of assuming them.
 */

$files = glob('D:/2/public/build/assets/app-*.css') ?: [];
$css = $files ? file_get_contents($files[0]) : '';

preg_match('/\.bushehr-svg-container\{([^}]*)\}/', $css, $c);
preg_match('/\.bushehr-svg-container svg\{([^}]*)\}/', $css, $s);
preg_match('/\.bushehr-map-wrapper\{([^}]*)\}/', $css, $w);

echo "=== shipped values ===\n";
echo "  container : " . trim($c[1] ?? '?') . "\n";
echo "  svg       : " . trim($s[1] ?? '?') . "\n";
echo "  wrapper   : " . trim($w[1] ?? '?') . "\n";

// pull the numbers out of the clamp()
preg_match('/height:clamp\((\d+)px,(\d+)vh\s*-\s*(\d+)px,(\d+)px\)/', $c[1] ?? '', $m);
if (! $m) {
    echo "\n  clamp() not parsed\n";
    exit(1);
}
[, $min, $vhPart, $off, $max] = array_map('floatval', $m);

echo "\n=== clamp components ===\n";
printf("  min  : %.0fpx\n", $min);
printf("  calc : %.0fvh - %.0fpx\n", $vhPart, $off);
printf("  max  : %.0fpx\n", $max);

preg_match('/max-width:(\d+)px/', $w[1] ?? '', $mw);
$wrapperMax = (float) ($mw[1] ?? 0);
preg_match('/padding:(\d+)px/', $w[1] ?? '', $pd);
$pad = (float) ($pd[1] ?? 0);
$inner = $wrapperMax - $pad * 2;

echo "\n=== wrapper box ===\n";
printf("  max-width : %.0fpx\n", $wrapperMax);
printf("  padding   : %.0fpx\n", $pad);
printf("  inner box : %.0fpx\n", $inner);

echo "\n=== map size per viewport ===\n";
printf("  %-9s %-10s %-10s %-11s %-9s %s\n", 'viewport', 'raw', 'clamped', 'map height', 'map width', 'overflows?');

foreach ([1440, 1280, 1080, 900, 800] as $vh) {
    $raw = $vhPart * $vh / 100 - $off;
    $h = max($min, min($max, $raw));
    $w = $h * 650 / 800;
    $over = $w > $inner;

    printf(
        "  %-9s %-10s %-10s %-11s %-9s %s\n",
        "{$vh}px",
        round($raw) . 'px',
        round($h) . 'px',
        round($h) . 'px',
        round($w) . 'px',
        $over ? 'YES (too wide)' : 'no'
    );
}

echo "\n  svg rule sets height:100%, width:auto -> height wins, ratio 650:800 kept\n";
echo "  so the rendered height IS the clamp() value above\n";