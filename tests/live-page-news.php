<?php
/** Reads the Inertia props out of the rendered HTML and checks the news. */
$url = 'http://127.0.0.1:8000/category/bushahr/cities';
$html = file_get_contents($url);

preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $html, $m);
if (! $m) {
    exit("Inertia props block not found\n");
}

$page = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
if (! $page) {
    exit("props did not decode\n");
}

$news = $page['props']['news'] ?? null;

echo "component : {$page['component']}\n";
echo "url       : {$page['url']}\n";
echo "news count: ".count($news ?? [])."\n\n";

if (! $news) {
    exit("no news in props\n");
}

$dated = array_filter($news, fn ($n) => ! empty($n['publishedAt']));
printf("items with a date: %d of %d\n\n", count($dated), count($news));

echo "=== first 6 rendered on the page ===\n";
foreach (array_slice($news, 0, 6) as $n) {
    printf("  %s  %-14s %s\n",
        mb_substr((string) ($n['publishedAt'] ?? '??'), 0, 16),
        mb_substr((string) ($n['sourceName'] ?? ''), 0, 13),
        mb_substr((string) ($n['title'] ?? ''), 0, 50)
    );
}

$bushehr = array_filter($news, fn ($n) => str_contains((string) ($n['title'] ?? ''), 'بوشهر'));
printf("\nBushehr-specific items on the page: %d\n", count($bushehr));