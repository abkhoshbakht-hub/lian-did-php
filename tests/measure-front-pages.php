<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/**
 * Measures the REAL front-page image of each configured paper, then compares
 * WebP and AVIF against the original. AVIF is the interesting one: GD here
 * reports AVIF support, which compresses far better than WebP.
 */
$papers = App\Support\NewspaperCatalog::papers();
printf("configured papers: %d\n\n", count($papers));

$rows = [];

foreach ($papers as $p) {
    try {
        $found = App\Support\NewspaperFetcher::discover($p['id']);
        $img = null;
        foreach (($found['candidates'] ?? []) as $u) {
            if (preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $u)) { $img = $u; break; }
        }
        if (! $img && ! empty($found['image'])) $img = $found['image'];
        if (! $img) { $rows[] = [$p['name'] ?? $p['id'], null]; continue; }

        $ch = curl_init($img);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$body || $code !== 200) { $rows[] = [$p['name'] ?? $p['id'], null]; continue; }

        $rows[] = [$p['name'] ?? $p['id'], strlen($body), $body];
    } catch (Throwable $e) {
        $rows[] = [$p['name'] ?? $p['id'], null];
    }
}

echo "=== real front-page image size per paper ===\n";
$sizes = [];
foreach ($rows as $r) {
    if ($r[1] === null) { printf("  %-20s   not found\n", mb_substr($r[0], 0, 18)); continue; }
    $sizes[] = $r[1];
    printf("  %-20s %8.2f MB\n", mb_substr($r[0], 0, 18), $r[1] / 1024 / 1024);
}
if (!$sizes) { exit("no images retrieved\n"); }
$avg = array_sum($sizes) / count($sizes);
printf("\n  papers with an image: %d\n  average: %.2f MB\n", count($sizes), $avg / 1024 / 1024);
printf("  daily intake (%d papers): %.1f MB\n", count($sizes), $avg * count($sizes) / 1024 / 1024);
printf("  monthly intake: %.2f GB   yearly: %.1f GB\n",
    $avg * count($sizes) * 30 / 1024 / 1024 / 1024,
    $avg * count($sizes) * 365 / 1024 / 1024 / 1024);

echo "\n=== compression comparison on a real image ===\n";
$sample = null;
foreach ($rows as $r) {
    if (isset($r[2]) && strlen($r[2]) > 200000) { $sample = $r; break; }
}
if (!$sample) $sample = $rows[array_key_first(array_filter($rows, fn ($r) => isset($r[2])))];
if (!$sample) exit("no sample\n");

[$name, $origSize, $bytes] = $sample;
$img = @imagecreatefromstring($bytes);
if (!$img) exit("cannot decode sample\n");

printf("  sample: %s  %d x %d  %.2f MB  (source %s)\n",
    $name, imagesx($img), imagesy($img), $origSize / 1024 / 1024,
    $origSize > 300000 ? 'jpeg/png' : '?');

function out($img, $kind, $q): int
{
    ob_start();
    if ($kind === 'webp') imagewebp($img, null, $q);
    elseif ($kind === 'avif') imageavif($img, null, $q);
    else imagejpeg($img, null, $q);
    return strlen(ob_get_clean());
}

printf("\n  %-12s %10s %10s %10s\n", 'format', 'size', 'vs orig', 'per paper/day');
foreach ([['jpeg', 95], ['webp', 100], ['webp', 90], ['avif', 100], ['avif', 90], ['avif', 80]] as [$k, $q]) {
    $s = out($img, $k, $q);
    printf("  %-12s %8.2f MB %9.1f%% %8.2f MB\n",
        "{$k} q{$q}", $s / 1024 / 1024, $s / $origSize * 100,
        $s * count($sizes) / 1024 / 1024);
}
imagedestroy($img);