<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\RssSources;
use Illuminate\Support\Facades\DB;

/**
 * Fetches the feeds that stored UNDATED rows and prints the raw pubDate
 * exactly as the feed sends it, plus what strtotime() makes of it.
 */
$broken = DB::table('external_news')
    ->where('publishedAt', null)
    ->where('status', 'PENDING')
    ->distinct()
    ->pluck('sourceName');

echo "sources that stored undated rows: ".implode('، ', $broken->all())."\n\n";

foreach (RssSources::LIST as $s) {
    if (! in_array($s['name'], $broken->all(), true)) {
        continue;
    }

    echo "--- {$s['name']}\n    {$s['url']}\n";

    $ch = curl_init($s['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_ENCODING       => '',
    ]);
    $xml = curl_exec($ch);
    curl_close($ch);

    if (! $xml) {
        echo "    FETCH FAILED\n\n";
        continue;
    }

    preg_match_all('/<(?:pubDate|published|updated|dc:date)>(.*?)<\/(?:pubDate|published|updated|dc:date)>/is', $xml, $m);
    $dates = array_slice(array_map('trim', $m[1] ?? []), 0, 3);

    if (! $dates) {
        echo "    feed carries NO date element at all\n";
        preg_match_all('/<item[^>]*>|<entry[^>]*>/i', $xml, $im);
        echo "    items in feed: ".count($im[0] ?? [])."\n\n";
        continue;
    }

    foreach ($dates as $d) {
        $ts = strtotime($d);
        printf("    raw  : %s\n", $d);
        printf("    parse: %s\n\n", $ts === false ? 'FAILED -> publishedAt stored as NULL' : date('Y-m-d H:i:s', $ts));
    }
}