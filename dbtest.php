<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
$app = '/home/liandidir/lian_app';
$lines = file($app . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$v = [];
foreach ($lines as $l) { if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $l, $m)) { $v[$m[1]] = $m[2]; } }
echo 'DB=' . $v['DB_DATABASE'] . ' USER=' . $v['DB_USERNAME'] . ' HOST=' . $v['DB_HOST'] . "\n";
try {
    $pdo = new PDO('mysql:host=' . $v['DB_HOST'] . ';dbname=' . $v['DB_DATABASE'] . ';charset=utf8mb4', $v['DB_USERNAME'], $v['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "POD_MYSQL_OK\n";
} catch (Throwable $e) {
    echo "DBERR: " . $e->getMessage() . "\n";
}
