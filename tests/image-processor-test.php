<?php

/** Real end-to-end test of the image processor: upload -> resize -> convert. */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\ImageProcessor;
use App\Support\SettingStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$dir = 'C:/Users/LIANDE~1/AppData/Local/Temp/opencode/img';
@mkdir($dir, 0777, true);

// Build a source image 2400x1200 in JPEG
$src = "$dir/source.jpg";
$im = imagecreatetruecolor(2400, 1200);
for ($x = 0; $x < 2400; $x += 20) {
    $c = imagecolorallocate($im, ($x / 8) % 255, 120, 200 - ($x / 12) % 255);
    imagefilledrectangle($im, $x, 0, $x + 20, 1200, $c);
}
$white = imagecolorallocate($im, 255, 255, 255);
imagestring($im, 5, 100, 100, 'LIANDID IMAGE TEST', $white);
imagejpeg($im, $src, 92);
imagedestroy($im);
printf("source: %dx%d, %s bytes\n", 2400, 1200, number_format(filesize($src)));

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $name);
    if (! $ok) $fail++;
};

echo "\n=== driver ===\n";
printf("  driver: %s\n", ImageProcessor::driver() ?: 'NONE');
$check('a driver is available', ImageProcessor::driver() !== null);

Storage::disk('public')->deleteDirectory('test-upload');
Storage::disk('public')->makeDirectory('test-upload');

$run = function (array $settings, string $label, int $expW, int $expH, string $expExt) use ($src, $fail) {
    SettingStore::setMany($settings);
    $file = new UploadedFile($src, 'photo.jpg', 'image/jpeg', null, true);
    $res = ImageProcessor::process($file, 'test-upload');

    printf("\n=== %s ===\n", $label);
    if (! $res['ok']) {
        printf("  ERROR: %s\n", $res['error']);
        $fail++;

        return;
    }

    $path = Storage::disk('public')->path($res['path']);
    $info = getimagesize($path);
    printf("  saved  : %s\n", $res['path']);
    printf("  got    : %dx%d %s  %s bytes\n", $info[0], $info[1], $info['mime'], number_format(filesize($path)));
    printf("  want   : %dx%d .%s\n", $expW, $expH, $expExt);

    $okDims = ($info[0] === $expW && $info[1] === $expH);
    $okExt  = str_contains(strtolower($res['path']), '.'.$expExt);

    printf("  %s  dimensions\n", $okDims ? 'PASS' : 'FAIL');
    printf("  %s  format/extension\n", $okExt ? 'PASS' : 'FAIL');
    if (! $okDims) $fail++;
    if (! $okExt) $fail++;
};

// source is 2400x1200
$run(['image_aspect' => 'free', 'image_max_dim' => 800, 'image_format' => 'webp', 'image_quality' => 80], 'free / max 800 / webp', 800, 400, 'webp');
$run(['image_aspect' => '1:1', 'image_max_dim' => 600, 'image_format' => 'jpeg', 'image_quality' => 85], '1:1 / max 600 / jpeg', 600, 600, 'jpg');
$run(['image_aspect' => '4:3', 'image_max_dim' => 400, 'image_format' => 'png'], '4:3 / max 400 / png', 400, 300, 'png');
// smaller than the source: must not upscale
$run(['image_aspect' => 'free', 'image_max_dim' => 100, 'image_format' => 'jpeg'], 'free / max 100 / jpeg (no upscale)', 100, 50, 'jpg');

echo "\n=== files on disk ===\n";
foreach (Storage::disk('public')->files('test-upload') as $f) {
    printf("  %-44s %s\n", $f, number_format(Storage::disk('public')->size($f)));
}

Storage::disk('public')->deleteDirectory('test-upload');
SettingStore::setMany(['image_aspect' => 'free', 'image_max_dim' => 1600, 'image_format' => 'webp', 'image_quality' => 82]);

echo "\n".($fail === 0 ? 'IMAGE PROCESSOR: ALL PASS' : "IMAGE PROCESSOR: {$fail} FAILED")."\n";
exit($fail === 0 ? 0 : 1);