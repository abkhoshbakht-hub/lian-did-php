<?php

/** Tests every cron endpoint with the real token, plus the admin page. */

$base = 'D:/2';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CronToken;
use Illuminate\Support\Facades\DB;

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

$token = CronToken::get();
printf("token: %s... (%d chars)\n\n", substr($token, 0, 8), strlen($token));

$call = function (string $path, string $token) {
    $url = 'http://127.0.0.1:8000' . $path . '?token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Accept: application/json'],
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, (string) $r];
};

echo "=== 1. wrong token is rejected ===\n";
[$c, $b] = $call('/_cron/rss', 'wrong-token');
printf("  http %d\n", $c);
$check('403 for a bad token', $c === 403);

echo "\n=== 2. health ===\n";
[$c, $b] = $call('/_cron/health', $token);
printf("  http %d\n", $c);
$j = json_decode($b, true);
$check('health returns ok flag', is_array($j) && array_key_exists('ok', $j));
$check('health lists the checks', count($j['checks'] ?? []) >= 10);
printf("  ok=%s  failed=%d\n", $j['ok'] ? 'yes' : 'no', count($j['failed'] ?? []));
foreach (($j['failed'] ?? []) as $f2) {
    printf("    FAILED: %s\n", $f2);
}

echo "\n=== 3. rss ===\n";
$before = DB::table('external_news')->where('status', 'PENDING')->count();
$t0 = microtime(true);
[$c, $b] = $call('/_cron/rss', $token);
$sec = round(microtime(true) - $t0, 1);
printf("  http %d in %ss\n", $c, $sec);
$j = json_decode($b, true);
$check('rss returns ok', is_array($j) && ($j['ok'] ?? false));
printf("  sources: %s   new: %s   pruned: %s\n", $j['sources'] ?? '?', $j['newItems'] ?? '?', $j['pruned'] ?? '?');
$after = DB::table('external_news')->where('status', 'PENDING')->count();
printf("  pending: %d -> %d\n", $before, $after);
$check('pruning removed old pending items', $after < $before);

echo "\n=== 4. backup ===\n";
$dir = 'D:/2/storage/app/backups';
@mkdir($dir, 0750, true);
$beforeFiles = glob($dir . '/liandid_php_*.sql') ?: [];
[$c, $b] = $call('/_cron/backup', $token);
printf("  http %d\n", $c);
$j = json_decode($b, true);
$check('backup returns ok', is_array($j) && ($j['ok'] ?? false));
printf("  target: %s   file: %s   bytes: %s\n", $j['target'] ?? '?', $j['file'] ?? '?', number_format($j['bytes'] ?? 0));
$afterFiles = glob($dir . '/liandid_php_*.sql') ?: [];
printf("  dumps kept: %d (was %d)\n", count($afterFiles), count($beforeFiles));
$check('a dump file exists', count($afterFiles) > count($beforeFiles) - 1);
$check('only the newest dumps are kept', count($afterFiles) <= 3);

echo "\n=== 5. admin cron page (guests must be sent to login) ===\n";
$ch = curl_init('http://127.0.0.1:8000/admin/cron');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
printf("  /admin/cron -> %d\n", $code);
$check('guest is redirected to login', $code === 302);

echo "\n" . ($fail === 0 ? 'CRON: ALL PASS' : "CRON: {$fail} FAILED") . "\n";
exit($fail === 0 ? 0 : 1);