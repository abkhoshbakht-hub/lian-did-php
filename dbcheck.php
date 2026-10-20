<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (($_GET['tok'] ?? '') !== 'LianRunner#2026') { http_response_code(403); die('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
try {
    $app = '/home/liandidir/lian_app';
    $lines = file($app . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $v = [];
    foreach ($lines as $l) {
        if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $l, $m)) { $v[$m[1]] = $m[2]; }
    }
    echo "db=" . ($v['DB_DATABASE'] ?? 'NO') . " user=" . ($v['DB_USERNAME'] ?? 'NO') . "\n";
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=' . $v['DB_DATABASE'] . ';charset=utf8mb4', $v['DB_USERNAME'], $v['DB_PASSWORD']);
    $tables = $pdo->query('show tables')->fetchAll(PDO::FETCH_COLUMN);
    echo "TABLES=" . count($tables) . "\n";
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
