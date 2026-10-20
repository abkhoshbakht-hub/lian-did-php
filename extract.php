<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
@ini_set('max_execution_time', 240);

$step = isset($_GET['step']) ? $_GET['step'] : '0';
$home = dirname(__DIR__);
$zipFile = $home . '/liandid_bundle.zip';
$target = $home . '/lian_app';
$dst = __DIR__;

header('Content-Type: text/plain; charset=utf-8');
echo "php=" . PHP_VERSION . "\n";
echo "home=$home\n";

function rcopy($s, $d) {
    if (!is_dir($d)) { @mkdir($d, 0755, true); }
    $items = scandir($s);
    foreach ($items as $f) {
        if ($f == '.' || $f == '..') { continue; }
        $sp = $s . '/' . $f;
        $dp = $d . '/' . $f;
        if (is_dir($sp)) { rcopy($sp, $dp); }
        else { @copy($sp, $dp); }
    }
}

if ($step >= '1') {
    echo "step1: extract\n";
    if (!is_file($zipFile)) { die("zip not found at $zipFile\n"); }
    $zip = new ZipArchive();
    $res = $zip->open($zipFile);
    if ($res === TRUE) {
        if (!$zip->extractTo($target)) { die("extractTo failed\n"); }
        $zip->close();
        echo "extract: OK\n";
        echo "check .env=" . (is_file($target . '/.env.example') ? 'Y' : 'N') . "\n";
        echo "check index=" . (is_file($target . '/public/index.php') ? 'Y' : 'N') . "\n";
    } else {
        die("zip open failed code=$res\n");
    }
}

if ($step >= '2') {
    echo "step2: copy public -> public_html\n";
    rcopy($target . '/public', $dst);
    echo "index.php in public_html: " . (is_file($dst . '/index.php') ? 'Y' : 'N') . "\n";
}

if ($step >= '3') {
    echo "step3: storage symlink\n";
    if (!is_link($dst . '/storage') && !is_dir($dst . '/storage')) {
        @symlink($target . '/storage/app/public', $dst . '/storage');
    }
    echo "storage link: " . (is_link($dst . '/storage') ? 'OK' : 'FAIL') . "\n";
}

echo "ext zip=" . (extension_loaded('zip') ? 'Y' : 'N')
   . " pdo_mysql=" . (extension_loaded('pdo_mysql') ? 'Y' : 'N')
   . " gd=" . (extension_loaded('gd') ? 'Y' : 'N')
   . " mbstring=" . (extension_loaded('mbstring') ? 'Y' : 'N')
   . " openssl=" . (extension_loaded('openssl') ? 'Y' : 'N')
   . " curl=" . (function_exists('curl_version') ? 'Y' : 'N') . "\n";
echo "DONE\n";
