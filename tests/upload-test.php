<?php

/** Uploads a real image over HTTP and checks the stored result. */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\SettingStore;
use Illuminate\Support\Facades\Storage;

$dir = 'C:/Users/LianDesign/AppData/Local/Temp/opencode/img';
@mkdir($dir, 0777, true);

// Build a 2000x1000 PNG
$src = "$dir/upload-me.png";
$im = imagecreatetruecolor(2000, 1000);
$blue = imagecolorallocate($im, 30, 90, 200);
$white = imagecolorallocate($im, 255, 255, 255);
imagefilledrectangle($im, 0, 0, 2000, 1000, $blue);
imagestring($im, 5, 100, 100, 'LIANDID UPLOAD TEST', $white);
imagepng($im, $src);
imagedestroy($im);
printf("source png: 2000x1000, %s bytes\n\n", number_format(filesize($src)));

SettingStore::setMany(['image_aspect' => 'free', 'image_max_dim' => 500, 'image_format' => 'webp', 'image_quality' => 78]);

// Cookie jar, kept for the whole test
$jar = 'C:/Users/LianDesign/AppData/Local/Temp/opencode/up.txt';
@unlink($jar);
touch($jar);

function readJar($jar): array
{
    $cookies = [];
    foreach (@file($jar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if ($l === '' || str_starts_with($l, '#')) continue;
        $p = explode("\t", $l);
        if (count($p) >= 7) $cookies[$p[5]] = $p[6];
    }

    return $cookies;
}

function xsrf($jar): string
{
    $c = readJar($jar);

    return isset($c['XSRF-TOKEN']) ? urldecode($c['XSRF-TOKEN']) : '';
}

function req(string $url, string $jar, ?array $fields = null, ?string $file = null)
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR  => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ];
    if ($fields !== null || $file !== null) {
        $payload = $fields ?? [];
        if ($file) $payload['file'] = new CURLFile($file, 'image/png', 'upload-me.png');
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
    clearstatcache();

    return [$code, (string) $res];
}

echo "=== login ===\n";
req('http://127.0.0.1:8000/login', $jar);            // GET primes the session
[$lc] = req('http://127.0.0.1:8000/login', $jar, [
    'email' => 'admin@liandid.ir',
    'password' => 'LianDid2026!',
]);
echo "  login HTTP $lc\n";

[$dc] = req('http://127.0.0.1:8000/admin/dashboard', $jar);  // confirm session
echo "  dashboard HTTP $dc (302 = not logged in)\n";

if ($dc === 302) {
    echo "\nFAILED: could not authenticate\n";
    exit(1);
}

Storage::disk('public')->deleteDirectory('media');

echo "\n=== upload ===\n";
[$code, $body] = req('http://127.0.0.1:8000/admin/upload', $jar, [], $src);
echo "  HTTP $code\n";
echo "  body: " . mb_substr($body, 0, 300) . "\n";

$json = json_decode($body, true);

echo "\n=== verify ===\n";
$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

if (is_array($json) && isset($json['url'])) {
    $public = 'D:/2/public'.$json['url'];
    printf("  url    : %s\n", $json['url']);
    printf("  driver : %s\n", $json['driver'] ?? '?');
    $check('file exists on disk', is_file($public));
    if (is_file($public)) {
        $i = getimagesize($public);
        printf("  stored : %dx%d %s, %s bytes\n", $i[0], $i[1], $i['mime'], number_format(filesize($public)));
        $check('resized to 500 wide', $i[0] === 500);
        $check('converted to webp', $i['mime'] === 'image/webp');
        $check('smaller than source', filesize($public) < filesize($src));
    }
    $h = @get_headers('http://127.0.0.1:8000'.$json['url']);
    printf("  http   : %s\n", $h[0] ?? '?');
    $check('served over http', str_contains($h[0] ?? '', '200'));
} else {
    $check('upload returned a url', false);
}

echo "\n".($fail === 0 ? 'UPLOAD END-TO-END: ALL PASS' : "UPLOAD END-TO-END: {$fail} FAILED")."\n";
exit($fail === 0 ? 0 : 1);