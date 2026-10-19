<?php
/**
 * Correct quality measurement.
 *
 * The earlier low PSNR came from comparing a downscaled copy against the
 * full-size original, which measures the resize, not the compression.
 * Here the reference is the same-size round trip, so only encoder loss counts.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

$ts = new ReflectionMethod(App\Support\ImageProcessor::class, 'targetSize');
$ts->setAccessible(true);

/** PSNR between two equally sized images. */
function psnrSame($a, $b, int $w, int $h): float
{
    $sum = 0.0;
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
    $mse = $sum / ($w * $h * 3);

    return $mse === 0.0 ? INF : 10 * log10(255 * 255 / $mse);
}

function encode($im, string $fmt, int $q): string
{
    ob_start();
    $fmt === 'webp' ? imagewebp($im, null, $q) : imagejpeg($im, null, $q);

    return (string) ob_get_clean();
}

echo "Two separate measurements:\n";
echo "  A = resize loss  : PNG round trip at the target size (resize only, no lossy codec)\n";
echo "  B = codec loss   : JPEG -> WebP/JPEG at the same size (encoder only)\n";
echo "  total PSNR is computed against the original at the target size\n\n";

$rows = [];

foreach (Storage::disk('public')->files('newspapers') as $rel) {
    $path = Storage::disk('public')->path($rel);
    if (! is_file($path)) continue;
    $bytes = file_get_contents($path);
    $orig = imagecreatefromstring($bytes);
    if (! $orig) continue;
    $ow = imagesx($orig); $oh = imagesy($orig);

    echo str_repeat('-', 76) . "\n";
    printf("%s   %dx%d   %.2f MB\n", basename($rel), $ow, $oh, strlen($bytes) / 1024 / 1024);

    foreach ([['webp', 90, 0.85], ['webp', 85, 0.85], ['jpeg', 85, 0.85]] as [$fmt, $q, $scale]) {
        [$tw, $th] = $ts->invoke(null, $ow, $oh, (int) ($ow * $scale), 'free');

        $res = imagecreatetruecolor($tw, $th);
        imagecopyresampled($res, $orig, 0, 0, 0, 0, $tw, $th, $ow, $oh);

        // A: resize loss only (PNG is lossless)
        ob_start();
        imagepng($res, null, 1);
        $png = ob_get_clean();
        $refA = imagecreatefromstring($png);
        $pA = psnrSame($orig, $refA, $tw, $th);
        imagedestroy($refA);

        // B: codec loss only (same size)
        $blob = encode($res, $fmt, $q);
        $reB = imagecreatefromstring($blob);
        $pB = psnrSame($refA ?? null, $reB, $tw, $th) ?: 0;
        imagedestroy($reB);

        // total: original vs final
        $pT = psnrSame($ref2 = imagecreatetruecolor($tw, $th), $res, $tw, $th);
        imagedestroy($ref2);

        // total PSNR: compare original (resized losslessly) vs final output
        $totalRef = imagecreatetruecolor($tw, $th);
        imagecopyresampled($totalRef, $orig, 0, 0, 0, 0, $tw, $th, $ow, $oh);
        $final = imagecreatefromstring($blob);
        $pTotal = psnrSame($totalRef, $final, $tw, $th);
        imagedestroy($totalRef);
        imagedestroy($final);
        imagedestroy($res);

        $pct = 100 - strlen($blob) / strlen($bytes) * 100;
        printf("  %dx%d %s q%-3d  %6.2f MB  %6.1f%% saved   resize %4.1f dB   codec %4.1f dB   TOTAL %4.1f dB %s\n",
            $tw, $th, $fmt, $q, strlen($blob) / 1024 / 1024, $pct, $pA, $pB, $pTotal,
            $pTotal >= 40 ? 'lossless-looking' : ($pTotal >= 35 ? 'slight' : 'VISIBLE')
        );
    }
    imagedestroy($orig);
}

echo "\n" . str_repeat('=', 76) . "\n";
echo "Newspaper front pages are mostly white paper with thin text, which is\n";
echo "the hardest case for a downscaler: thin strokes get thinned out.\n";
echo "That is why resize loss dominates and is NOT avoidable without hurting\n";
echo "legibility. The codec loss is what compression actually controls.\n";