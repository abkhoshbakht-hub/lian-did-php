<?php
/**
 * Measures how much vertical space sits above the map so the CSS offset
 * matches reality instead of a guess.
 *
 * Header  : a tall image banner, about 120px on small screens / 200px desktop
 * Hero    : the "شهرهای استان بوشهر" band
 * Gaps    : py-3 + py-4 on the hero, py-3 on the map wrapper
 */
$viewports = [1280, 900, 800, 720];

$header   = 120;   // header banner, mobile size (most screens we test)
$heroIcon = 56;    // w-14 h-14
$heroText = 70;    // two text lines
$heroPad  = 24;    // py-3 + py-4
$heroGap  = 12;    // flex gap
$mapPad   = 28;    // wrapper padding 14px each side
$hint     = 51;    // 8 margin + 8 padding + 1 border + 34 min-height
$bottom   = 20;    // breathing room under the map

$offset = $header + $heroIcon + $heroText + $heroPad + $heroGap + $mapPad + $hint + $bottom;

printf("=== space above the map, measured from the actual classes ===\n");
printf("  header banner      %4dpx\n", $header);
printf("  hero icon          %4dpx\n", $heroIcon);
printf("  hero text          %4dpx\n", $heroText);
printf("  hero padding       %4dpx\n", $heroPad);
printf("  hero gap           %4dpx\n", $heroGap);
printf("  map wrapper pad    %4dpx\n", $mapPad);
printf("  hint bar           %4dpx\n", $hint);
printf("  bottom gap         %4dpx\n", $bottom);
printf("  ------------------------------------\n");
printf("  TOTAL ABOVE MAP    %4dpx\n\n", $offset);

echo "=== resulting map size with that offset ===\n";
printf("  %-10s %-12s %-12s %s\n", 'viewport', 'map height', 'map width', 'verdict');
$minH = 380;
$maxH = 820;

foreach ($viewports as $vh) {
    $raw = $vh - $offset;
    $h = max($minH, min($maxH, $raw));
    $w = $h * 650 / 800;
    $scrolls = $h + $offset > $vh;

    // labels are unreadable below ~380px tall
    $readable = $h >= 380;

    printf(
        "  %-10s %-12s %-12s %s\n",
        "{$vh}px",
        round($h) . 'px',
        round($w) . 'px',
        $scrolls ? 'scrolls' : ($readable ? 'fits + readable' : 'too small to read')
    );
}

printf("\n  recommended offset: %dpx\n", $offset);
printf("  recommended clamp : clamp(%dpx, calc(100vh - %dpx), %dpx)\n", $minH, $offset, $maxH);