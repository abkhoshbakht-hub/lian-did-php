<?php

/** End-to-end test of gallery album + item CRUD over HTTP. */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$jarDir = 'C:/Users/LianDesign/AppData/Local/Temp/opencode';
$jar = $jarDir . '/gal.txt';
@unlink($jar);
touch($jar);

function xsrf($jar)
{
    foreach (@file($jar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if (str_contains($l, 'XSRF-TOKEN')) {
            $p = explode("\t", $l);

            return urldecode(end($p));
        }
    }

    return '';
}

function req($url, $jar, $fields = null, $file = null, $fieldName = 'file')
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR  => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ];
    if ($fields !== null) {
        $payload = $fields;
        if ($file) {
            $payload[$fieldName] = new CURLFile($file, 'image/png', 'g.png');
        }
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $payload;
        $opts[CURLOPT_HTTPHEADER] = [
            'X-XSRF-TOKEN: '.xsrf($jar),
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ];
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, (string) $res];
}

// build a test png
$src = "$jarDir/gal.png";
$im = imagecreatetruecolor(1400, 900);
$c1 = imagecolorallocate($im, 20, 80, 160);
$c2 = imagecolorallocate($im, 200, 120, 40);
imagefilledrectangle($im, 0, 0, 1400, 450, $c1);
imagefilledrectangle($im, 0, 450, 1400, 900, $c2);
imagepng($im, $src);
imagedestroy($im);
printf("test png: 1400x900, %s bytes\n\n", number_format(filesize($src)));

// login
req('http://127.0.0.1:8000/login', $jar);
[$lc] = req('http://127.0.0.1:8000/login', $jar, ['email' => 'admin@liandid.ir', 'password' => 'LianDid2026!']);
echo "login: $lc\n";

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

$title = 'آلبوم تست ' . substr(md5((string) microtime(true)), 0, 6);

echo "\n=== 1. create album ===\n";
[$c] = req('http://127.0.0.1:8000/admin/gallery/albums', $jar, [
    '_method' => 'POST', 'title' => $title, 'category' => 'آزمایشی',
    'description' => 'ساخته‌شده توسط تست خودکار', 'color' => 'from-cyan-500 to-teal-500',
]);
echo "  HTTP $c\n";
$album = DB::table('gallery_albums')->where('title', $title)->first();
$check('album row created', (bool) $album);
echo "  slug: ".($album->slug ?? '?')."\n";

echo "\n=== 2. upload an image into it ===\n";
[$c, $body] = req("http://127.0.0.1:8000/admin/gallery/albums/{$album->id}/items", $jar,
    ['kind' => 'image', 'title' => 'تصویر آزمایشی'], $src, 'file');
echo "  HTTP $c\n";
$item = DB::table('gallery_items')->where('albumId', $album->id)->first();
$check('gallery item created', (bool) $item);
if ($item) {
    $path = Storage::disk('public')->path($item->url);
    echo "  stored path: {$item->url}\n";
    $check('file written to public disk', is_file($path));
    if (is_file($path)) {
        $i = getimagesize($path);
        printf("  size: %dx%d %s, %s bytes\n", $i[0], $i[1], $i['mime'], number_format(filesize($path)));
        // image settings default is max 1280
        $check('resized to fit settings', $i[0] <= 1280 && $i[1] <= 1280);
        $check('converted to webp', $i['mime'] === 'image/webp');
    }
    $code = curl_init('http://127.0.0.1:8000/storage/'.$item->url);
    curl_setopt_array($code, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
    curl_exec($code);
    $http = curl_getinfo($code, CURLINFO_HTTP_CODE);
    curl_close($code);
    echo "  public URL -> HTTP $http\n";
    $check('reachable on the public site', $http === 200);
}

echo "\n=== 3. public pages list it ===\n";
foreach (['/gallery', '/gallery/'.$album->slug, '/gallery/videos'] as $u) {
    $code = curl_get($u);
    printf("  %-42s %d\n", $u, $code);
    $check("public page {$u} responds", $code === 200);
}

echo "\n=== 4. rename album ===\n";
$newTitle = $title.' (ویرایش‌شده)';
[$c] = req("http://127.0.0.1:8000/admin/gallery/albums/{$album->id}", $jar, [
    '_method' => 'PUT', 'title' => $newTitle,
]);
echo "  HTTP $c\n";
$check('title updated', DB::table('gallery_albums')->where('id', $album->id)->value('title') === $newTitle);

echo "\n=== 5. delete album (must remove file too) ===\n";
$itemUrl = $item->url ?? null;
[$c] = req("http://127.0.0.1:8000/admin/gallery/albums/{$album->id}", $jar, ['_method' => 'DELETE']);
echo "  HTTP $c\n";
$check('album row deleted', ! DB::table('gallery_albums')->where('id', $album->id)->exists());
$check('items deleted', ! DB::table('gallery_items')->where('albumId', $album->id)->exists());
if ($itemUrl) {
    $check('image file deleted from disk', ! Storage::disk('public')->exists($itemUrl));
}

echo "\n=== 6. nothing left behind ===\n";
$leftover = DB::table('gallery_albums')->where('title', 'like', 'آلبوم تست%')->count();
$check('no test albums left', $leftover === 0);

echo "\n".($fail === 0 ? 'GALLERY CRUD: ALL PASS' : "GALLERY CRUD: {$fail} FAILED")."\n";
exit($fail === 0 ? 0 : 1);

function curl_get($u)
{
    $ch = curl_init('http://127.0.0.1:8000'.$u);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
    curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $c;
}