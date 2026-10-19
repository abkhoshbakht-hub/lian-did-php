<?php
/**
 * The low PSNR on the 1378x1968 image is suspicious. This checks whether the
 * two images are even comparable (alpha channel, orientation) before any
 * quality claim is made.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

$rel = 'newspapers/icNRK22HkB8DUghQHjfa.jpg';
$path = Storage::disk('public')->path($rel);
$bytes = file_get_contents($path);
$orig = imagecreatefromstring($bytes);

$info = getimagesizefromstring($bytes);
printf("original: %dx%d  type=%s  alpha=%s  orientation=%s\n",
    $info[0], $info[1],
    match ($info[2]) { IMAGETYPE_JPEG => 'jpeg', default => 'other' },
    imageistruecolor($orig) ? 'false' : 'true',
    $info['orientation'] ?? 'n/a'
);

$w = imagesx($orig); $h = imagesy($orig);

$res = imagecreatetruecolor($w, $h);
imagecopyresampled($res, $orig, 0, 0, 0, 0, $w, $h, $w, $h);
ob_start(); imagewebp($res, null, 90); $blob = ob_get_clean();
$re = imagecreatefromstring($blob);

echo "\nsame-size round trip (no resize at all):\n";
$sum = 0.0; $n = $w * $h * 3; $diffPixels = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $ca = imagecolorat($orig, $x, $y);
        $cb = imagecolorat($re, $x, $y);
        $d = 0;
        foreach ([16, 8, 0] as $s) {
            $dd = (($ca >> $s) & 0xFF) - (($cb >> $s) & 0xFF);
            $d += $dd * $dd;
        }
        if ($d > 0) $diffPixels++;
        $sum += $d;
    }
}
$mse = $sum / $n;
printf("  size  %d -> %d bytes\n", strlen($bytes), strlen($blob));
printf("  PSNR  %.1f dB\n", 10 * log10(255 * 255 / $mse));
printf("  pixels that differ at all: %d of %d (%.1f%%)\n", $diffPixels, $w * $h, $diffPixels / ($w * $h) * 100);

// Where do the differences sit? Sample a few.
echo "\nfirst 6 differing pixels:\n";
$shown = 0;
for ($y = 0; $y < $h && $shown < 6; $y += 97) {
    for ($x = 0; $x < $w && $shown < 6; $x += 97) {
        $ca = imagecolorat($orig, $x, $y);
        $cb = imagecolorat($re, $x, $y);
        if ($ca !== $cb) {
            printf("  (%4d,%4d)  orig #%06X   webp #%06X\n", $x, $y, $ca, $cb);
            $shown++;
        }
    }
}
echo "\ncorner pixels (orientation clue):\n";
printf("  orig  TL #%06X   TR #%06X   BL #%06X   BR #%06X\n",
    imagecolorat($orig, 0, 0), imagecolorat($orig, $w - 1, 0),
    imagecolorat($orig, 0, $h - 1), imagecolorat($orig, $w - 1, $h - 1));
printf("  webp  TL #%06X   TR #%06X   BL #%06X   BR #%06X\n",
    imagecolorat($re, 0, 0), imagecolorat($re, $w - 1, 0),
    imagecolorat($re, 0, $h - 1), imagecolorat($re, $w - 1, $h - 1));