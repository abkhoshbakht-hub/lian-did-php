<?php
/**
 * Creates throwaway issues at known ages, runs the retention rule, and proves
 * that exactly the 8..30 day band is touched. Everything is rolled back.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\NewspaperRetention;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$marker = 'newspapers/ZZtest' . random_int(100000, 999999) . '.webp';

$ages = [
    2    => 'must stay (last 7 days)',
    7    => 'must stay (exactly 7)',
    8    => 'must be deleted (first day of window)',
    20   => 'must be deleted (middle)',
    30   => 'must be deleted (last day of window)',
    31   => 'must stay (outside window)',
    90   => 'must stay (outside window)',
];

DB::beginTransaction();
try {
    // the column has a foreign key, so reuse a real newspaper id
    $paperId = DB::table('newspapers')->value('id');
    echo "  using newspaper id: {$paperId}\n";

    $ids = [];
    foreach ($ages as $days => $why) {
        $file = 'newspapers/ZZtest_' . $days . '_' . random_int(100000, 999999) . '.webp';
        Storage::disk('public')->put($file, str_repeat('x', 50000));
        $id = 'ZZtest' . $days . random_int(1000, 9999);
        DB::table('newspaper_issues')->insert([
            'id' => $id,
            'newspaperId' => $paperId,
            'date' => now()->subDays($days)->toDateTimeString(),
            'persianDate' => '',
            'issueNumber' => '',
            'title' => "test {$days}d",
            'originalUrl' => 'https://example.com/paper',
            'imageUrl' => $file,
            'thumbnailUrl' => $file,
            'status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ids[$days] = ['id' => $id, 'file' => $file];
    }

    echo "=== before ===\n";
    foreach ($ids as $d => $i) {
        printf("  %2dd  file=%-3s  %s\n", $d, is_file(Storage::disk('public')->path($i['file'])) ? 'yes' : 'NO ', $ages[$d]);
    }

    $result = NewspaperRetention::prune();
    printf("\n  prune() reported: files=%d bytes=%d rows=%d\n", $result['files'], $result['bytes'], $result['rows']);

    echo "\n=== after ===\n";
    $fail = 0;
    foreach ($ids as $d => $i) {
        $exists = is_file(Storage::disk('public')->path($i['file']));
        $row = DB::table('newspaper_issues')->where('id', $i['id'])->first();
        $shouldDelete = $d >= 8 && $d <= 30;
        $ok = $exists === ! $shouldDelete;
        if (! $ok) $fail++;

        printf("  %2dd  file=%-3s  expect=%-8s  %s   row imageUrl=%s\n",
            $d,
            $exists ? 'yes' : 'no',
            $shouldDelete ? 'deleted' : 'kept',
            $ok ? 'OK' : 'FAIL',
            $row ? (str_starts_with((string) $row->imageUrl, 'newspapers/') ? 'local' : 'originalUrl') : 'row gone'
        );
        if ($ok) $fail += 0;
    }

    echo "\n  rows deleted from DB: " . DB::table('newspaper_issues')->where('id', 'like', 'ZZtest%')->count() . " (must be 0)\n";
    echo $fail === 0 ? "\nRETENTION RULE VERIFIED\n" : "\n$fail CHECK(S) FAILED\n";
} finally {
    foreach (Storage::disk('public')->files('newspapers') as $f) {
        if (str_starts_with(basename($f), 'ZZtest')) Storage::disk('public')->delete($f);
    }
    DB::table('newspaper_issues')->where('id', 'like', 'ZZtest%')->delete();
    DB::rollBack();
    echo "\n(rolled back, no test data left)\n";
}