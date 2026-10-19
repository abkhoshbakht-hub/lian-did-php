<?php
/**
 * Drives the real conversion used by NewspaperFetcher on the three stored
 * front pages, comparing byte size and PSNR before/after.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

$m = new ReflectionMethod(App\Support\NewspaperFetcher::class, 'toWebp');
$m->setAccessible(true);

function psnr($a, $b, int $w, int $h): float
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

echo "toWebp() on the real stored front pages:\n\n";
printf("  %-32s %9s %9s %8s %9s\n", 'file', 'before', 'after', 'saved', 'PSNR');

$totBefore = $totAfter = 0;

foreach (Storage::disk('public')->files('newspapers') as $rel) {
    if (! preg_match('/\.(jpe?g|png)$/i', $rel)) continue;
    $src = Storage::disk('public')->path($rel);
    if (! is_file($src)) continue;

    $before = filesize($src);
    $img = @imagecreatefromstring(file_get_contents($src));
    if (! $img) { printf("  %-32s cannot decode\n", basename($rel)); continue; }
    $w = imagesx($img); $h = imagesy($img);

    $out = $m->invoke(null, $src);

    if ($out === null) {
        printf("  %-32s %7.2f MB %9s %7s %9s\n", basename($rel), $before / 1048576, 'kept', '-', 'no change');
        imagedestroy($img);
        continue;
    }

    $after = filesize($out);
    $re = imagecreatefromstring(file_get_contents($out));
    $p = psnr($img, $re, $w, $h);
    imagedestroy($re); imagedestroy($img);
    @unlink($out);

    $totBefore += $before; $totAfter += $after;

    printf("  %-32s %6.2f MB %6.2f MB %7.1f%% %6.1f dB %s\n",
        basename($rel), $before / 1048576, $after / 1048576,
        100 - $after / $before * 100, $p,
        $p >= 38 ? 'no visible loss' : ($p >= 35 ? 'slight' : 'CHECK')
    );
}

printf("\n  total %.2f MB -> %.2f MB  (%.0f%% smaller)\n",
    $totBefore / 1048576, $totAfter / 1048576, 100 - $totAfter / $totBefore * 100);
echo "\n  40 dB or more means the difference cannot be seen.\n";