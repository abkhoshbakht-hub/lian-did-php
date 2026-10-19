<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/**
 * Replays the exact loop from RssFetcher::parse() and prints what ends up in
 * $current['date'] and $current['desc'] for one item.
 */
function replay(string $xml): array
{
    $prev = libxml_use_internal_errors(true);
    $reader = new XMLReader();
    $reader->XML($xml);

    $current = null;
    $inField = null;
    $out = [];

    while (@$reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $name = $reader->name;

            if (in_array($name, ['item', 'entry'], true)) {
                $current = ['title' => '', 'link' => '', 'desc' => '', 'img' => '', 'date' => '', 'linkHref' => ''];
                $inField = null;
                continue;
            }
            if ($current === null) continue;

            if ($name === 'title') { $inField = 'title'; }
            elseif ($name === 'description' || $name === 'summary' || $name === 'content') { $inField = 'desc'; }
            elseif ($name === 'pubDate' || $name === 'published' || $name === 'updated' || $name === 'dc:date') { $inField = 'date'; }
            elseif ($name === 'link') { $inField = 'link'; }
            elseif ($name === 'guid' && $current['link'] === '') { $inField = 'link'; }
        } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            if ($current !== null && $inField !== null) {
                $current[$inField] .= $reader->value;
            }
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $inField !== null) {
            if ($reader->name === $inField) $inField = null;   // <-- the suspect line
            if ($reader->name === 'item' || $reader->name === 'entry') {
                $out[] = $current;
                $current = null;
                $inField = null;
            }
        }
    }
    $reader->close();
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    return $out;
}

$feed = 'https://bushehrnews.ir/feed/';
$xml = file_get_contents($feed);

$items = replay($xml);
$first = $items[0] ?? null;

echo "feed: {$feed}\n\n";
echo "=== what the parser captured ===\n";
printf("  raw date string : %s\n", var_export($first['date'], true));
printf("  length          : %d chars\n", strlen($first['date']));
printf("  strtotime()     : %s\n\n", $first['date'] !== '' ? (($t = strtotime($first['date'])) === false ? 'FAILED -> publishedAt = NULL' : date('Y-m-d H:i:s', $t)) : 'empty');

echo "=== field bleeding: is desc also contaminated? ===\n";
printf("  desc length     : %d chars\n", strlen($first['desc']));
printf("  desc tail       : ...%s\n", mb_substr($first['desc'], -70));

echo "\n=== same feed, but with the reset line fixed ===\n";
function replayFixed(string $xml): array
{
    $map = ['title' => 'title', 'description' => 'desc', 'summary' => 'desc', 'content' => 'desc',
        'pubDate' => 'date', 'published' => 'date', 'updated' => 'date', 'dc:date' => 'date',
        'link' => 'link', 'guid' => 'link'];

    $prev = libxml_use_internal_errors(true);
    $reader = new XMLReader();
    $reader->XML($xml);
    $current = null; $inField = null; $out = [];

    while (@$reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $name = $reader->name;
            if (in_array($name, ['item', 'entry'], true)) {
                $current = ['title' => '', 'link' => '', 'desc' => '', 'img' => '', 'date' => '', 'linkHref' => ''];
                $inField = null;
                continue;
            }
            if ($current === null) continue;
            if ($name === 'title') { $inField = 'title'; }
            elseif (in_array($name, ['description', 'summary', 'content'], true)) { $inField = 'desc'; }
            elseif (in_array($name, ['pubDate', 'published', 'updated', 'dc:date'], true)) { $inField = 'date'; }
            elseif ($name === 'link') { $inField = 'link'; }
            elseif ($name === 'guid' && $current['link'] === '') { $inField = 'link'; }
        } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            if ($current !== null && $inField !== null) $current[$inField] .= $reader->value;
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            // the reset must be compared through the name map, and the
            // </item> close must NOT depend on $inField being set
            if ($inField !== null && isset($map[$reader->name]) && $map[$reader->name] === $inField) {
                $inField = null;
            }
            if ($reader->name === 'item' || $reader->name === 'entry') {
                $out[] = $current;
                $current = null;
                $inField = null;
            }
        }
    }
    $reader->close();

    return $out;
}

$fixedAll = replayFixed($xml);
echo "\n=== same feed, but with the reset line fixed ===\n";
printf("  items parsed: %d\n", count($fixedAll));
$fixed = $fixedAll[0] ?? ['date' => '(no item)', 'desc' => ''];
printf("  raw date string : %s\n", var_export($fixed['date'], true));
printf("  strtotime()     : %s\n", $fixed['date'] !== '' ? (($t = strtotime($fixed['date'])) === false ? 'FAILED' : date('Y-m-d H:i:s', $t)) : 'empty');
printf("  desc length     : %d chars\n", strlen($fixed['desc']));
printf("  desc head       : %s\n", mb_substr($fixed['desc'], 0, 90));