<?php
/**
 * Newspaper front pages are large. This applies the SAME pipeline the admin
 * media uploader uses and compares size and PSNR against the original,
 * across the dimensions and formats the settings offer.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

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

$ts = new ReflectionMethod(App\Support\ImageProcessor::class, 'targetSize');
$ts->setAccessible(true);
function sizeFor($ts, $w, $h, $max, $aspect) { return $ts->invoke(null, $w, $h, $max, $aspect); }

echo "current admin image settings:\n";
foreach (App\Support\SettingStore::imageSettings() as $k => $v) {
    printf("  %-20s %s\n", $k, var_export($v, true));
}

$files = Storage::disk('public')->files('newspapers');
if (! $files) { exit("no stored front pages\n"); }

echo "\n" . str_repeat('=', 74) . "\n";
echo "Pipeline: same as admin media (max_dim, aspect, format, quality)\n";
echo str_repeat('=', 74) . "\n";

foreach ($files as $rel) {
    $path = Storage::disk('public')->path($rel);
    if (! is_file($path)) continue;
    $origSize = filesize($path);
    $orig = @imagecreatefromstring(file_get_contents($path));
    if (! $orig) continue;
    $w = imagesx($orig); $h = imagesy($orig);

    printf("\n%s  %dx%d  %.2f MB\n", basename($rel), $w, $h, $origSize / 1024 / 1024);

    // Newspaper front pages are text-heavy: cap the long edge and keep quality high.
    foreach ([[1600, 'webp', 82], [1600, 'webp', 90], [1600, 'jpeg', 82], [1200, 'webp', 85], [1000, 'webp', 85]] as [$maxDim, $fmt, $q]) {
        [$tw, $th] = sizeFor($ts, $w, $h, $maxDim, 'free');
        $res = imagecreatetruecolor($tw, $th);
        imagecopyresampled($res, $orig, 0, 0, 0, 0, $tw, $th, $w, $h);

        ob_start();
        $fmt === 'webp' ? imagewebp($res, null, $q) : imagejpeg($res, null, $q);
        $blob = ob_get_clean();

        $re = imagecreatefromstring($blob);
        $p = psnr($orig, $re, $tw, $th);
        imagedestroy($re); imagedestroy($res);

        $tag = "{$maxDim}px {$fmt} q{$q}";
        printf("  %-24s %6.2f MB  %6.1f%% saved  %5.1f dB%s\n",
            $tag, strlen($blob) / 1024 / 1024,
            100 - strlen($blob) / $origSize * 100, $p,
            $p >= 40 ? '   visually lossless' : ($p >= 35 ? '   slight' : '   VISIBLE LOSS')
        );
    }
    imagedestroy($orig);
}

echo "\n" . str_repeat('=', 74) . "\n";
echo "PSNR: 40+ = indistinguishable, 35-40 = slight, <35 = visible\n";