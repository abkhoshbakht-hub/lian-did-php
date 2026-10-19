<?php
/**
 * Measures peak memory and wall time of the operations a shared host has to
 * survive. Run from CLI so it sees the real server limits.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function mem(): string
{
    return round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MB';
}

function measure(string $label, callable $fn): void
{
    $m0 = memory_get_usage(true);
    $t0 = microtime(true);
    $out = $fn();
    printf("  %-34s %7.2fs   peak %7s   %s\n",
        $label, microtime(true) - $t0, mem(), $out);
}

echo "=== PHP limits configured here ===\n";
printf("  memory_limit      %s\n", ini_get('memory_limit'));
printf("  max_execution_time %s\n", ini_get('max_execution_time'));
printf("  upload_max_filesize %s\n", ini_get('upload_max_filesize'));
printf("  post_max_size       %s\n", ini_get('post_max_size'));
printf("  peak so far         %s\n\n", mem());

echo "=== database footprint ===\n";
$rows = DB::select("SELECT table_name, table_rows, data_length, index_length
                    FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY data_length DESC");
$total = 0;
foreach ($rows as $r) {
    $bytes = (int) $r->data_length + (int) $r->index_length;
    $total += $bytes;
    if ($bytes > 0) {
        printf("  %-28s rows ~%-7s %8.2f MB\n", $r->table_name, number_format((int) $r->table_rows), $bytes / 1024 / 1024);
    }
}
printf("  %-28s %18.2f MB\n", 'TOTAL', $total / 1024 / 1024);
$dbSize = DB::select("SELECT SUM(data_length+index_length) s FROM information_schema.tables WHERE table_schema=DATABASE()")[0]->s ?? 0;
printf("  MySQL reported size: %.1f MB\n\n", $dbSize / 1024 / 1024);

echo "=== heaviest read paths (what a visitor hits) ===\n";
measure('homepage resolve', fn () => count(App\Support\HomepageResolver::resolve()['latest'] ?? []) . ' latest');
measure('city news (30d)', fn () => count(App\Support\CityNews::all(30)) . ' items');
measure('city news (7d)', fn () => count(App\Support\CityNews::all(7)) . ' items');
measure('region news counts', fn () => count(App\Support\RegionNews::counts()) . ' counties');
measure('gallery catalog', fn () => count(App\Support\GalleryCatalog::albums()) . ' albums');
measure('newspaper catalog', fn () => count(App\Support\NewspaperCatalog::latestIssues(24)) . ' issues');
measure('article list 24', fn () => count(DB::table('articles')->orderByDesc('publishedAt')->limit(24)->get()) . ' rows');

echo "\n=== heaviest write path ===\n";
measure('db backup (full stream)', function () {
    $m = new ReflectionMethod(App\Support\DatabaseBackup::class, 'writeTo');
    $m->setAccessible(true);
    $f = fopen('php://temp/maxmemory:8388608', 'w+b');
    $m->invoke(new App\Support\DatabaseBackup(), $f);
    $size = ftell($f);
    fclose($f);
    return round($size / 1024 / 1024, 1) . ' MB written';
});

echo "\n  PEAK MEMORY FOR THE WHOLE RUN: " . mem() . "\n";