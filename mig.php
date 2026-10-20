<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
@set_time_limit(180);
if (($_GET['tok'] ?? '') !== 'LianRunner#2026') { http_response_code(403); die('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$app = '/home/liandidir/lian_app';
chdir($app);
require $app . '/vendor/autoload.php';

try {
    $application = require $app . '/bootstrap/app.php';
    $application->useEnvironmentPath($app);
    $kernel = $application->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "booted\n";

    foreach (['config:clear', 'cache:clear'] as $cmd) {
        $buf = '';
        $code = Illuminate\Support\Facades\Artisan::call($cmd, [], $buf);
        echo "$cmd => $code " . trim(strip_ansi($buf)) . "\n";
    }

    $buf = '';
    $code = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true], $buf);
    echo "migrate => $code\n" . trim(strip_ansi($buf)) . "\n";

    $tables = DB::select('show tables');
    echo 'TABLES=' . count($tables) . "\n";
} catch (Throwable $e) {
    echo 'FATAL: ' . $e->getMessage() . "\n" . basename($e->getFile()) . ':' . $e->getLine() . "\n";
}

function strip_ansi($s) { return preg_replace('/\x1b\[[0-9;]*m/', '', (string)$s); }
