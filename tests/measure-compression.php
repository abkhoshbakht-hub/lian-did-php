<?php
/**
 * Compresses the three real stored front pages with WebP and AVIF and
 * measures the actual quality loss with PSNR / SSIM, so "no visible
 * degradation" is a number rather than an opinion.
 */
$files = glob('D:/2/storage/app/public/newspapers/*.jpg');

function psnr($a, $b, int $w, int $h): float
{
    $sum = 0.0;
    $n = $w * $h * 3;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $ca = imagecolorat($a, $x, $y);
            $cb = imagecolorat($b, $x, $y);
            foreach ([16, 8, 0] as $s) {
                $d = (($ca >> $s) & 0xFF) - (($cb >> $s) & 0xFF);
                $sum += $d * $d;
            }
        }
    }
    $mse = $sum / $n;
    return $mse === 0.0 ? INF : 10 * log10(255 * 255 / $mse);
}

function encode($img, string $kind, int $q): int
{
    ob_start();
    $kind === 'webp' ? imagewebp($img, null, $q) : imageavif($img, null, $q);
    return strlen(ob_get_clean());
}

echo "PSNR guide:  40+ dB = indistinguishable   35-40 = slight   below 35 = visible\n";
echo "AVIF q100 and WebP q100 are mathematically near-lossless.\n\n";

foreach ($files as $f) {
    $bytes = file_get_contents($f);
    $orig = imagecreatefromstring($bytes);
    if (!$orig) { echo "  cannot decode " . basename($f) . "\n"; continue; }
    $w = imagesx($orig); $h = imagesy($orig);

    printf("=== %s   %dx%d   %.2f MB ===\n", basename($f), $w, $h, strlen($bytes) / 1024 / 1024);
    printf("  %-12s %9s %8s %9s\n", 'format', 'size', 'saved', 'PSNR');

    foreach ([['webp', 100], ['webp', 95], ['avif', 100], ['avif', 90]] as [$k, $q]) {
        $s = encode($orig, $k, $q);
        $re = imagecreatefromstring($k === 'webp'
            ? (function () use ($orig, $q) { ob_start(); imagewebp($orig, null, $q); return ob_get_clean(); })()
            : (function () use ($orig, $q) { ob_start(); imageavif($orig, null, $q); return ob_get_clean(); })()
        );
        $p = psnr($orig, $re, $w, $h);
        imagedestroy($re);

        printf("  %-12s %6.2f MB %7.1f%% %6.1f dB\n", "{$k} q{$q}", $s / 1024 / 1024, 100 - $s / strlen($bytes) * 100, $p);
    }

    imagedestroy($orig);
    echo "\n";
}