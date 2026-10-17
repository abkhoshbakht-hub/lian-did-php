<?php

/** Tests the newspaper front-page fetcher on real sources. */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\NewspaperFetcher;
use Illuminate\Support\Facades\DB;

$before = DB::table('newspaper_issues')->count();

echo "=== telegram sources (web preview) ===\n";
$tg = DB::table('newspaper_sources')
    ->join('newspapers', 'newspaper_sources.newspaperId', '=', 'newspapers.id')
    ->where('newspaper_sources.active', 1)
    ->where('newspaper_sources.type', 'telegram')
    ->select('newspaper_sources.id', 'newspaper_sources.name as sname', 'newspaper_sources.url', 'newspapers.name as pname')
    ->limit(5)->get();

foreach ($tg as $s) {
    $t0 = microtime(true);
    $res = NewspaperFetcher::discover($s->id);
    $ms = (int) round((microtime(true) - $t0) * 1000);
    $out = $res['ok']
        ? 'FOUND  ' . mb_substr($res['image'], 0, 70) . '  -> ' . $res['localPath']
        : 'fail: ' . $res['error'];
    printf("  %-22s %s (%dms)\n", mb_substr($s->pname, 0, 20), $out, $ms);
}

echo "\n=== official sites ===\n";
$off = DB::table('newspaper_sources')
    ->join('newspapers', 'newspaper_sources.newspaperId', '=', 'newspapers.id')
    ->where('newspaper_sources.active', 1)
    ->where('newspaper_sources.type', 'official')
    ->select('newspaper_sources.id', 'newspapers.name as pname')
    ->limit(4)->get();

foreach ($off as $s) {
    $t0 = microtime(true);
    $res = NewspaperFetcher::discover($s->id);
    $ms = (int) round((microtime(true) - $t0) * 1000);
    printf("  %-22s %s (%dms)\n", mb_substr($s->pname, 0, 20), $res['ok'] ? 'FOUND ' . mb_substr($res['image'], 0, 60) : 'fail: ' . $res['error'], $ms);
}

echo "\n=== run() writes an issue ===\n";
$result = NewspaperFetcher::run(6);
printf("  ok=%d failed=%d\n", $result['ok'], $result['failed']);
foreach ($result['details'] as $d) {
    printf("    %-24s %s %s\n", mb_substr($d['source'], 0, 22), $d['ok'] ? 'OK  ' : 'FAIL', $d['ok'] ? $d['image'] : $d['error']);
}

$after = DB::table('newspaper_issues')->count();
printf("\n  issues: %d -> %d (+%d)\n", $before, $after, $after - $before);

echo "\n=== files actually stored ===\n";
$files = Storage::disk('public')->files('newspapers');
printf("  count: %d\n", count($files));
foreach (array_slice($files, 0, 5) as $f) {
    $i = getimagesize(Storage::disk('public')->path($f));
    printf("  %-42s %dx%d %s\n", $f, $i[0] ?? 0, $i[1] ?? 0, number_format(Storage::disk('public')->size($f)));
}

// cleanup
DB::table('newspaper_issues')->where('title', 'like', 'صفحهٔ اول %')->delete();
foreach ($files as $f) {
    Storage::disk('public')->delete($f);
}
echo "\n(cleaned)\n";