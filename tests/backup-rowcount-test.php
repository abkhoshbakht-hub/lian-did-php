<?php

/** Compares every table's row count in the live database against the dump. */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\DatabaseBackup;
use Illuminate\Support\Facades\DB;

echo "=== live row counts ===\n";
$expected = [];
$ref = new ReflectionClass(DatabaseBackup::class);
$inst = $ref->newInstance();
$m = $ref->getMethod('tableNames');
$m->setAccessible(true);
$skip = (new ReflectionClass(DatabaseBackup::class))->getConstant('SKIP');

foreach ($m->invoke($inst) as $t) {
    if (in_array($t, $skip, true)) continue;
    $n = DB::table($t)->count();
    $expected[$t] = $n;
    printf("  %-26s %s\n", $t, number_format($n));
}
file_put_contents('C:/Users/LianDesign/AppData/Local/Temp/opencode/expected.json', json_encode($expected));

echo "\n=== generate dump into a buffer ===\n";
$w = $ref->getMethod('writeTo');
$w->setAccessible(true);
$buf = fopen('php://temp', 'w+b');
$w->invoke($inst, $buf);
$size = fstat($buf)['size'];
rewind($buf);
echo '  bytes: ' . number_format($size) . "\n";

// count the "-- N row(s)" markers the dump writes per table
$sql = stream_get_contents($buf);
fclose($buf);

echo "\n=== rows recorded in the dump ===\n";
$bad = 0;
preg_match_all('/INSERT INTO `(\w+)` \(.*?\) VALUES\n(.*?);\n/s', $sql, $blocks, PREG_SET_ORDER);
$actual = [];
foreach ($blocks as $b) {
    $table = $b[1];
    $n = substr_count($b[2], "),\n") + substr_count($b[2], "),");
    if (substr_count(trim($b[2]), '(') && substr(trim($b[2]), -1) === ')') {
        // count opening parens at value starts
        $n = preg_match_all('/^\(/m', $b[2]);
    }
    $actual[$table] = ($actual[$table] ?? 0) + $n;
}

printf("  %-26s %-10s %-10s %s\n", 'table', 'live', 'dump', 'ok');
foreach ($expected as $t => $n) {
    $d = $actual[$t] ?? 0;
    $ok = ($d === $n);
    if (! $ok) $bad++;
    printf("  %-26s %-10s %-10s %s\n", $t, number_format($n), number_format($d), $ok ? 'OK' : 'MISMATCH');
}

echo "\n";
echo $bad === 0 ? "BACKUP ROW COUNT: EVERY TABLE COMPLETE\n" : "BACKUP ROW COUNT: {$bad} TABLE(S) INCOMPLETE\n";
exit($bad === 0 ? 0 : 1);