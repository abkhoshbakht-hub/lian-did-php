<?php
/** Confirms the shipped HTML/JS actually contains the map markup and assets. */

$curl = function (string $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $r = curl_exec($ch);
    curl_close($ch);

    return (string) $r;
};

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

$url = 'http://127.0.0.1:8000/category/bushahr/cities';
$html = $curl($url);

echo "=== {$url} ===\n\n";
printf("  html bytes: %s\n\n", number_format(strlen($html)));

// props
preg_match('#data-page="app"[^>]*>(\{.*?\})</script>#s', $html, $m);
$props = json_decode($m[1] ?? '{}', true);
$news = $props['props']['news'] ?? [];
printf("  news items in props: %d\n", count($news));

// the page chunk the browser will fetch
preg_match('#/build/assets/(CityMap-[A-Za-z0-9_-]+\.js)#', $html, $c);
$pageChunk = $c[1] ?? '';
printf("  page chunk: %s\n", $pageChunk ?: 'NOT FOUND');

// inside that chunk, find the map chunk import
$chunkJs = $pageChunk ? $curl('http://127.0.0.1:8000/build/assets/' . $pageChunk) : '';
preg_match('#["\']\./(BushehrMap-[A-Za-z0-9_-]+\.js)["\']#', $chunkJs, $mc);
$mapChunk = $mc[1] ?? '';
printf("  map chunk  : %s\n", $mapChunk ?: 'not imported');

// and inside the map chunk, the real markup
$mapJs = $mapChunk ? $curl('http://127.0.0.1:8000/build/assets/' . $mapChunk) : '';
printf("  map chunk bytes: %s\n\n", number_format(strlen($mapJs)));

echo "--- what the browser receives ---\n";
foreach ([
    'map wrapper class'  => 'bushehr-map-wrapper',
    'svg container'      => 'bushehr-svg-container',
    'hint bar'           => 'bushehr-map-hint',
    'svg fetch path'     => 'maps/bushehr-fa.svg',
    'polygon tagging'    => 'data-county',
    'county list'        => 'عسلویه',
    'hit-test fallback'  => 'elementsFromPoint',
    'opacity dimming'    => 'opacity',
] as $label => $needle) {
    $check("map chunk contains $label", str_contains($mapJs, $needle));
}

echo "\n--- page chunk ---\n";
$check('page chunk holds the correct title', str_contains($chunkJs, 'شهرهای استان'));
$check('page chunk links home and bushehr', str_contains($chunkJs, '/category/bushahr'));
$check('page chunk shows the news count badge', str_contains($chunkJs, 'خبر'));

echo "\n--- stylesheet ---\n";
preg_match('#/build/assets/(app-[A-Za-z0-9_-]+\.css)#', $html, $cc);
$css = $cc[1] ? $curl('http://127.0.0.1:8000/build/assets/' . $cc[1]) : '';
$check('css is linked by the page', (bool) $css);
$check('css keeps aspect-ratio 650/800', (bool) preg_match('/aspect-ratio:\s*650\s*\/\s*800/', $css));
$check('css has the map styles', str_contains($css, 'bushehr-map-wrapper'));

echo "\n--- svg ---\n";
$svg = $curl('http://127.0.0.1:8000/maps/bushehr-fa.svg');
$check('svg served', strlen($svg) > 100000);
$check('svg has the 650x800 viewBox', str_contains($svg, 'viewBox="0 0 650 800"'));
$check('svg has hit-test polygons', substr_count($svg, '<polygon') >= 11);

echo "\n" . ($fail === 0 ? 'MAP SHIPPING: ALL PASS' : "MAP SHIPPING: {$fail} FAILED") . "\n";
exit($fail === 0 ? 0 : 1);