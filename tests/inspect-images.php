<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== GD capabilities ===\n";
foreach (gd_info() as $k => $v) {
    printf("  %-24s %s\n", $k, is_array($v) ? implode(',', $v) : (string) $v);
}

echo "\n=== stored newspaper front pages ===\n";
foreach (App\Support\NewspaperCatalog::latestIssues(8) as $i) {
    printf("  %-18s %-12s %s\n",
        mb_substr((string) ($i['title'] ?? '-'), 0, 16),
        (string) ($i['date'] ?? '-'),
        basename((string) ($i['imageUrl'] ?? ''))
    );
}

echo "\n=== images on disk ===\n";
$files = glob('D:/2/storage/app/public/newspapers/*');
printf("  files in storage/app/public/newspapers: %d\n", count($files));
$byExt = [];
foreach ($files as $f) {
    $e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $byExt[$e]['n'] = ($byExt[$e]['n'] ?? 0) + 1;
    $byExt[$e]['b'] = ($byExt[$e]['b'] ?? 0) + filesize($f);
}
foreach ($byExt as $e => $d) {
    printf("  %-6s %4d files  %8.2f MB  (avg %.2f MB)\n", $e, $d['n'], $d['b'] / 1024 / 1024, $d['b'] / $d['n'] / 1024 / 1024);
}

echo "\n=== total storage used by newspapers ===\n";
$all = [];
foreach (['newspapers', 'public/newspapers'] as $p) {
    foreach (glob("D:/2/storage/app/public/$p/*") ?: [] as $f) $all[] = $f;
}
$sum = array_sum(array_map('filesize', $all));
printf("  %d files, %.2f MB total\n", count($all), $sum / 1024 / 1024);