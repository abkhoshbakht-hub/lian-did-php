<?php

/** Adds the newly built public pages to the footer link list. */

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$current = json_decode((string) DB::table('site_settings')->where('key', 'footerLinks')->value('value'), true) ?: [];

$wanted = [
    ['name' => 'آخرین اخبار', 'href' => '/latest'],
    ['name' => 'شهرهای استان بوشهر', 'href' => '/category/bushahr/cities'],
    ['name' => 'روزنامه‌ها', 'href' => '/newspapers'],
    ['name' => 'گالری تصاویر', 'href' => '/gallery'],
];

$byHref = [];
foreach ($current as $l) {
    $byHref[$l['href']] = $l;
}

$added = 0;
foreach ($wanted as $w) {
    if (isset($byHref[$w['href']])) {
        continue;
    }
    $current[] = $w;
    $added++;
}

DB::table('site_settings')->where('key', 'footerLinks')->update([
    'value'      => json_encode($current, JSON_UNESCAPED_UNICODE),
    'updated_at' => now(),
]);

printf("  added %d footer link(s)\n", $added);
printf("  total  %d\n\n", count($current));

foreach ($current as $l) {
    printf("  %-18s %s%s\n", $l['name'], $l['href'], $l['external'] ?? false ? '  (external)' : '');
}