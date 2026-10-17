<?php

/** Verifies the admin can actually change their password through the form. */

$base = 'D:/2';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\SettingStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

$jarDir = 'C:/Users/LianDesign/AppData/Local/Temp/opencode';
$jar = $jarDir . '/pw.txt';
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

function req($url, $jar, $fields = null)
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR  => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ];
    if ($fields !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $fields;
        $opts[CURLOPT_HTTPHEADER] = [
            'X-XSRF-TOKEN: ' . xsrf($jar),
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

$user = DB::table('users')->where('role', 'ADMIN')->first();
if (! $user) {
    $user = DB::table('users')->orderBy('created_at')->first();
}

$original = 'LianDid2026!';
$new = 'LianDidTest2026!';

echo "user      : {$user->email}\n";
echo "before    : " . (Hash::check($original, $user->password) ? 'original password works' : 'ORIGINAL PASSWORD DOES NOT MATCH') . "\n\n";

// make sure we know the working password
if (! Hash::check($original, $user->password)) {
    DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($original)]);
    echo "reset hash to the known password for the test\n\n";
}

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

echo "=== log in with the current password ===\n";
req('http://127.0.0.1:8000/login', $jar);
[$c] = req('http://127.0.0.1:8000/login', $jar, ['email' => $user->email, 'password' => $original]);
echo "  login HTTP $c\n";
[$dc] = req('http://127.0.0.1:8000/admin/dashboard', $jar);
echo "  dashboard HTTP $dc\n";
$check('session established', $dc === 200);

echo "\n=== submit the change-password form exactly like the UI does ===\n";
[$c, $body] = req('http://127.0.0.1:8000/admin/change-password', $jar, [
    '_method'        => 'PUT',
    'current'        => $original,
    'password'       => $new,
    'confirmation'   => $new,
]);
echo "  HTTP $c\n";
echo '  body: ' . mb_substr(preg_replace('/\s+/', ' ', $body), 0, 200) . "\n";

$hash = DB::table('users')->where('id', $user->id)->value('password');
$changed = Hash::check($new, $hash);
$check('password was actually changed', $changed);

echo "\n=== the new password logs in ===\n";
@unlink($jar);
touch($jar);
req('http://127.0.0.1:8000/login', $jar);
[$c] = req('http://127.0.0.1:8000/login', $jar, ['email' => $user->email, 'password' => $new]);
echo "  login with new password HTTP $c\n";
[$dc] = req('http://127.0.0.1:8000/admin/dashboard', $jar);
echo "  dashboard HTTP $dc\n";
$check('can log in with the new password', $dc === 200);

echo "\n=== wrong current password is rejected ===\n";
@unlink($jar);
touch($jar);
req('http://127.0.0.1:8000/login', $jar);
[$c] = req('http://127.0.0.1:8000/login', $jar, ['email' => $user->email, 'password' => $new]);
req('http://127.0.0.1:8000/admin/change-password', $jar);
[$c2, $b2] = req('http://127.0.0.1:8000/admin/change-password', $jar, [
    '_method'      => 'PUT',
    'current'      => 'totally-wrong',
    'password'     => 'AnotherPass123!',
    'confirmation' => 'AnotherPass123!',
]);
echo "  HTTP $c2\n";
$hash2 = DB::table('users')->where('id', $user->id)->value('password');
$check('bad current password rejected', ! Hash::check('AnotherPass123!', $hash2));

// restore the original password so the user keeps the credentials they know
DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($original)]);
$check('restored the original password', Hash::check($original, DB::table('users')->where('id', $user->id)->value('password')));

echo "\n" . ($fail === 0 ? 'CHANGE PASSWORD: ALL PASS' : "CHANGE PASSWORD: {$fail} FAILED") . "\n";
exit($fail === 0 ? 0 : 1);