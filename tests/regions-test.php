<?php

/** Verifies the public /regions page serves the map, the SVG and the news. */

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

$curl = function (string $url, bool $body = false) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => $body,
        CURLOPT_NOBODY         => ! $body,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return ['code' => $code, 'body' => (string) $r, 'type' => $type];
};

echo "=== /regions END-TO-END ===\n\n";

echo "--- svg asset ---\n";
$svg = $curl('http://127.0.0.1:8000/maps/bushehr-fa.svg', true);
printf("  http %s  %s  %.0f KB\n", $svg['code'], $svg['type'], strlen($svg['body']) / 1024);
$check('svg returns 200', $svg['code'] === 200);
$check('svg is served as image/svg+xml', str_contains($svg['type'], 'svg'));
$check('svg is the real map (650x800 viewBox)', str_contains($svg['body'], 'viewBox="0 0 650 800"'));
$check('svg has hit-test polygons', substr_count($svg['body'], '<polygon') >= 11);

echo "\n--- page ---\n";
$page = $curl('http://127.0.0.1:8000/regions', true);
printf("  http %s  %.0f KB\n", $page['code'], strlen($page['body']) / 1024);
$check('page returns 200', $page['code'] === 200);

// Inertia emits: <script data-page="app" type="application/json">{...}</script>
if (! preg_match('#data-page="app"[^>]*>(\{.*?\})</script>#s', $page['body'], $m)) {
    $check('page carries Inertia props', false);
} else {
    $props = json_decode($m[1], true);
    $check('page carries Inertia props', is_array($props) && isset($props['props']));
    echo '  component    : ' . ($props['component'] ?? '?') . "\n";
    $news = $props['props']['news'] ?? [];
    printf("  props keys      : %s\n", implode(', ', array_keys($props['props'] ?? [])));
    printf("  news items passed: %d\n", count($news));
    $check('news items delivered', count($news) > 0);

    $withRegion = array_filter($news, fn ($n) => ! empty($n['region']));
    printf("  with a region tag: %d\n", count($withRegion));
    $check('items carry a county region', count($withRegion) > 0);

    $keys = [];
    foreach ($withRegion as $n) {
        $keys[$n['region']] = true;
    }
    printf("  distinct regions : %d (%s)\n", count($keys), implode(', ', array_slice(array_keys($keys), 0, 6)));

    $bad = array_filter($news, fn ($n) => empty($n['title']) || empty($n['link']));
    $check('every item has a title and link', count($bad) === 0);

    $allText = implode(' ', array_column($news, 'title'));
    $check('titles are real Persian', (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $allText));

    // the frontend groups by county key (region before the colon)
    $countyKeys = array_unique(array_map(fn ($n) => explode(':', $n['region'])[0], $withRegion));
    printf("  county keys      : %s\n", implode(', ', $countyKeys));
    $check('county keys match the map', array_diff($countyKeys, ['deylam', 'genaveh', 'kharg', 'booshehr', 'dashtestan', 'tangestan', 'dashti', 'dayer', 'jam', 'kangan', 'asaluyeh']) === []);
}

echo "\n--- built assets ---\n";
$manifest = @file_get_contents('D:/2/public/build/manifest.json');
$cssFile = null;
$jsFile = null;
if ($manifest) {
    $mf = json_decode($manifest, true);
    foreach ($mf as $k => $v) {
        if (str_contains($v['file'] ?? '', '.css')) $cssFile = '/build/' . $v['file'];
        if (str_contains($v['file'] ?? '', 'app') && str_contains($v['file'] ?? '', '.js')) $jsFile = '/build/' . $v['file'];
    }
}
printf("  css : %s\n", $cssFile ?: 'not found');
printf("  js  : %s\n", $jsFile ?: 'not found');
$check('build produced a css bundle', (bool) $cssFile);
$check('build produced a js bundle', (bool) $jsFile);

if ($cssFile) {
    $css = $curl('http://127.0.0.1:8000' . $cssFile, true);
    $check('css is served', $css['code'] === 200);
    $check('css carries the map styles', substr_count($css['body'], 'bushehr-') > 0);
    $check('css keeps the 650/800 aspect ratio', (bool) preg_match('/aspect-ratio:\s*650\s*\/\s*800/', $css['body']));
    $check('css disables pointer events on circles', (bool) preg_match('/bushehr-svg-container circle\{pointer-events:none\}/', $css['body']));
}

echo "\n--- admin is unaffected ---\n";
$admin = $curl('http://127.0.0.1:8000/admin/dashboard');
$check('admin redirects guests to login', in_array($admin['code'], [302, 200], true));

echo "\n" . ($fail === 0 ? '/regions: ALL PASS' : "/regions: {$fail} FAILED") . "\n";
exit($fail === 0 ? 0 : 1);