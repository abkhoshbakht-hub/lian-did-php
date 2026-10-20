<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$TOK = 'LianRunner#2026';
if (($_GET['tok'] ?? '') !== $TOK) { http_response_code(403); die('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(120);

try {
    $home = getenv('HOME') ?: '/home/liandidir';
    $app  = $home . '/lian_app';
    $ph_candidates = ['/usr/bin/php', '/usr/local/bin/php', '/opt/alt/php82/usr/bin/php', 'php82', 'php'];
    $ph = '';
    foreach ($ph_candidates as $c) {
        $test = trim(@shell_exec("$c -v 2>&1") ?? '');
        if ($test !== '' && strpos($test, 'PHP') !== false) { $ph = $c; break; }
    }
    if ($ph === '') { $found = 'NONE'; }
    foreach ($ph_candidates as $c) { echo "cand $c\n"; }

    echo "php_bin=$ph\n";
    $act = $_GET['act'] ?? 'info';

    function run($cmd, $cwd) {
        $desc = [1 => ['pipe','w'], 2 => ['pipe','w']];
        $p = @proc_open($cmd, $desc, $pipes, $cwd);
        if (!is_resource($p)) { return "proc_open failed\n"; }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($p);
        return trim($out . $err) . "\n[exit=$code]\n";
    }

    if ($act === 'migrate') {
        echo run("$ph artisan migrate --force 2>&1", $app);
    } elseif ($act === 'configclear') {
        echo run("$ph artisan config:clear 2>&1", $app);
        echo run("$ph artisan cache:clear 2>&1", $app);
    } elseif ($act === 'dbcheck') {
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=liandidir_liandid;charset=utf8mb4','liandidir_liadb','LianDid-db#8477');
            echo "DB OK\n";
        } catch (PDOException $e) {
            echo "DB ERR: " . $e->getMessage() . "\n";
        }
    } elseif ($act === 'optimize') {
        echo run("$ph artisan optimize 2>&1", $app);
    } else {
        $ver = shell_exec("$ph -v 2>&1");
        $ver = trim(str_replace("\n", ' | ', (string)$ver));
        echo "phpver=$ver\n";
        echo "app_dir=" . (is_dir($app) ? $app : 'MISSING') . "\n";
        echo "env_exists=" . (is_file($app . '/.env') ? 'YES' : 'NO') . "\n";
    }
} catch (Throwable $e) {
    echo "FATAL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
