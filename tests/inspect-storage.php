<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== newspaper_issues columns ===\n";
foreach (DB::select('SHOW COLUMNS FROM newspaper_issues') as $c) {
    printf("  %-24s %s\n", $c->Field, $c->Type);
}

echo "\n=== rows ===\n";
foreach (DB::table('newspaper_issues')->orderByDesc('id')->limit(6)->get() as $r) {
    $r = (array) $r;
    echo '  ';
    foreach ($r as $k => $v) {
        if (str_contains(strtolower($k), 'image') || in_array($k, ['id', 'date', 'title'], true)) {
            printf("%s=%s  ", $k, mb_substr((string) $v, 0, 46));
        }
    }
    echo "\n";
}

echo "\n=== files under storage/app/public ===\n";
$base = 'D:/2/storage/app/public';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
$tot = 0; $n = 0; $byExt = [];
foreach ($it as $f) {
    if ($f->isFile()) {
        $n++; $sz = $f->getSize(); $tot += $sz;
        $e = strtolower($f->getExtension());
        $byExt[$e]['n'] = ($byExt[$e]['n'] ?? 0) + 1;
        $byExt[$e]['b'] = ($byExt[$e]['b'] ?? 0) + $sz;
        if ($n <= 8) printf("  %-56s %7.2f MB\n", str_replace($base . '/', '', $f->getPathname()), $sz / 1024 / 1024);
    }
}
printf("\n  %d files, %.2f MB total\n", $n, $tot / 1024 / 1024);
foreach ($byExt as $e => $d) {
    printf("  %-6s %3d files  %7.2f MB  avg %.2f MB\n", $e, $d['n'], $d['b'] / 1024 / 1024, $d['b'] / $d['n'] / 1024 / 1024);
}