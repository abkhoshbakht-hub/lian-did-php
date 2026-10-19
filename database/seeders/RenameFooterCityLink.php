<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('site_settings')->where('key', 'footerLinks')->first();
if (! $row) {
    exit("footerLinks setting not found\n");
}

$links = json_decode($row->value, true) ?: [];

$oldName = 'مناطق بوشهر';
$oldHref = '/regions';
$newName = 'اخبار استان بوشهر';
$newHref = '/category/bushahr/cities';

$hit = null;
foreach ($links as $i => $l) {
    if (($l['href'] ?? '') === $oldHref || ($l['name'] ?? '') === $oldName) {
        $hit = $i;
        break;
    }
}

if ($hit === null) {
    echo "  nothing matched {$oldHref} / {$oldName} - no change\n";
} else {
    echo "  matched at index {$hit}\n";
    echo "  before: {$links[$hit]['name']} -> {$links[$hit]['href']}\n";
    $links[$hit]['name'] = $newName;
    $links[$hit]['href'] = $newHref;
    echo "  after : {$links[$hit]['name']} -> {$links[$hit]['href']}\n";

    DB::table('site_settings')->where('key', 'footerLinks')->update([
        'value' => json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'updated_at' => now(),
    ]);
    echo "  saved\n";
}

echo "\n  footer now:\n";
foreach ($links as $l) {
    printf("    %-20s %s\n", $l['name'], $l['href']);
}