<?php

/**
 * Checks the rendered /category/bushahr/cities page against the Next.js
 * reference structure, class by class. Nothing here is invented: every
 * expected string is copied from src/app/category/bushahr/cities/page.tsx
 * and components/news/BushehrMap.tsx.
 */

$base = 'D:/2';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => (string) $r];
}

echo "=== DATA LAYER (mirrors the 3 prisma queries) ===\n";

$news = \App\Support\CityNews::all();
printf("  merged news items: %d\n", count($news));
$check('news returned', count($news) > 0);

$ids = array_column($news, 'id');
$check('no duplicate ids', count($ids) === count(array_unique($ids)));

$withImage = array_filter($news, fn ($n) => ! empty($n['image']));
$withRegion = array_filter($news, fn ($n) => ! empty($n['region']));
$withDesc = array_filter($news, fn ($n) => ! empty($n['description']));
printf("  with image : %d\n", count($withImage));
printf("  with region: %d\n", count($withRegion));
printf("  with desc  : %d\n", count($withDesc));
$check('items carry title+link+sourceName', count(array_filter($news, fn ($n) => empty($n['title']) || empty($n['link']) || empty($n['sourceName']))) === 0);

// the 30 day window
$old = array_filter($news, fn ($n) => $n['publishedAt'] && strtotime($n['publishedAt']) < strtotime('-30 days'));
$check('everything is inside the 30 day window', count($old) === 0);

// keyword matching really works: at least one item matched by title only
$kwOnly = array_filter($news, fn ($n) => empty($n['region']) && empty($n['category']));
printf("  matched by title keyword only: %d\n", count($kwOnly));
$check('keyword matching adds items', count($kwOnly) > 0);

echo "\n=== RENDERED PAGE ===\n";
$page = fetch('http://127.0.0.1:8000/category/bushahr/cities');
$check('page returns 200', $page['code'] === 200);

// the browser never sees server HTML for an Inertia page, so read the props
preg_match('#data-page="app"[^>]*>(\{.*?\})</script>#s', $page['body'], $m);
$props = json_decode($m[1] ?? '{}', true);
$check('component is CityMap', ($props['component'] ?? '') === 'CityMap');
$pn = $props['props']['news'] ?? [];
printf("  news in props: %d\n", count($pn));
$check('props carry the news', count($pn) === count($news));

// now verify the page chunk markup
preg_match('#/build/assets/(CityMap-[A-Za-z0-9_-]+\.js)#', $page['body'], $c);
$chunk = $c[1] ?? '';
printf("  page chunk: %s\n", $chunk ?: 'MISSING');
$check('page chunk is emitted', $chunk !== '');
$js = $chunk ? fetch('http://127.0.0.1:8000/build/assets/' . $chunk)['body'] : '';

echo "\n--- classes copied verbatim from the reference ---\n";
$expected = [
    'gradient'            => 'from-[#1B365D] to-[#2E5090]',
    'blurred gold blob'   => 'bg-[#C9A96E]/8 rounded-full blur-3xl',
    'blurred white blob'  => 'bg-white/5 rounded-full blur-2xl',
    'container padding'   => 'site-container relative z-10 py-6',
    'icon box'            => 'bg-[#C9A96E]/15 backdrop-blur-sm rounded-xl',
    'title size'          => 'text-xl md:text-2xl font-black text-white',
    'gold title accent'   => 'text-[#C9A96E]',
    'count badge'         => 'bg-emerald-400 rounded-full animate-pulse',
    'breadcrumb'          => 'text-xs text-white/40',
    'map container'       => 'site-container py-8',
    'back to home'        => 'fixed bottom-6 left-6 z-50',
    'back home label'     => 'صفحه اصلی',
];
foreach ($expected as $label => $needle) {
    $check("chunk has $label", str_contains($js, $needle));
}

echo "\n--- nothing extra was invented ---\n";
$forbidden = [
    'پراخبارترین شهرستان‌ها' => 'the invented busiest-county section',
    'آمار شهرستان'            => 'the invented stats text',
    'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6' => 'the invented county grid',
];
foreach ($forbidden as $needle => $why) {
    $check("chunk does NOT contain $why", ! str_contains($js, $needle));
}

echo "\n--- the map component is still the ported one ---\n";
preg_match('#["\']\./(BushehrMap-[A-Za-z0-9_-]+\.js)["\']#', $js, $mc);
$mapChunk = $mc[1] ?? '';
printf("  map chunk: %s\n", $mapChunk ?: 'MISSING');
$check('map chunk imported', $mapChunk !== '');
$mapJs = $mapChunk ? fetch('http://127.0.0.1:8000/build/assets/' . $mapChunk)['body'] : '';
foreach ([
    'bushehr-map-wrapper' => 'wrapper from the reference css',
    'bushehr-map-header'  => 'header block',
    'bushehr-svg-container' => 'svg container',
    'bushehr-map-hint'    => 'hint bar',
    'maps/bushehr-fa.svg' => 'svg fetch path',
    'data-county'         => 'polygon tagging',
    'elementsFromPoint'   => 'hit testing',
] as $needle => $label) {
    $check("map chunk has $label", str_contains($mapJs, $needle));
}

echo "\n" . ($fail === 0 ? 'CITIES PAGE: EXACT MATCH' : "CITIES PAGE: {$fail} MISMATCH") . "\n";
exit($fail === 0 ? 0 : 1);