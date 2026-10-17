<?php

/**
 * Seeds the gallery albums that the Next.js version ships with, so the public
 * gallery page is populated the moment the migration runs.
 */

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$albums = [
    ['title' => 'تسلیم پاسخ به قدس', 'category' => 'سیاسی', 'count' => 24, 'color' => 'from-blue-500 to-blue-700'],
    ['title' => 'جشنواره غذای محلی بوشهر', 'category' => 'فرهنگی', 'count' => 18, 'color' => 'from-amber-500 to-orange-600'],
    ['title' => 'مسابقات فوتبال ساحلی', 'category' => 'ورزشی', 'count' => 32, 'color' => 'from-green-500 to-emerald-600'],
    ['title' => 'بازدید مقامات از پالایشگاه', 'category' => 'اقتصادی', 'count' => 12, 'color' => 'from-purple-500 to-indigo-600'],
    ['title' => 'نمایشگاه نقاشی کودکان', 'category' => 'فرهنگی', 'count' => 20, 'color' => 'from-pink-500 to-rose-500'],
    ['title' => 'عملیات نجات دریایی', 'category' => 'اجتماعی', 'count' => 8, 'color' => 'from-cyan-500 to-teal-500'],
];

$now = now();
$created = 0;

foreach ($albums as $i => $a) {
    if (DB::table('gallery_albums')->where('title', $a['title'])->exists()) {
        continue;
    }

    DB::table('gallery_albums')->insert([
        'id'           => 'gal'.Str::random(20),
        'title'        => $a['title'],
        'slug'         => Str::slug($a['title']) ?: 'album-'.($i + 1),
        'category'     => $a['category'],
        'description'  => null,
        'color'        => $a['color'],
        'isActive'     => 1,
        'displayOrder' => $i + 1,
        'created_at'   => $now,
        'updated_at'   => $now,
    ]);

    $created++;
}

printf("  albums created: %d\n", $created);
printf("  albums total  : %d\n", DB::table('gallery_albums')->count());