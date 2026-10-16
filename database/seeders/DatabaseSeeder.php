<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private function insertSafe(string $table, array $records): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);

        foreach ($records as $record) {
            $row = [];
            foreach ($record as $key => $value) {
                $snakeKey = Str::snake($key);
                $camelKey = Str::camel($key);

                if (in_array($key, $columns)) {
                    $row[$key] = $value;
                } elseif (in_array($camelKey, $columns)) {
                    $row[$camelKey] = $value;
                } elseif (in_array($snakeKey, $columns)) {
                    $row[$snakeKey] = $value;
                }
            }

            foreach (['createdAt', 'created_at', 'updatedAt', 'updated_at'] as $timeCol) {
                if (in_array($timeCol, $columns) && !isset($row[$timeCol])) {
                    $row[$timeCol] = now();
                }
            }

            DB::table($table)->insert($row);
        }
    }

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('comments')) DB::table('comments')->truncate();
        if (Schema::hasTable('articles')) DB::table('articles')->truncate();
        if (Schema::hasTable('categories')) DB::table('categories')->truncate();
        if (Schema::hasTable('users')) DB::table('users')->truncate();

        // ۱. ایجاد نویسنده پیش‌فرض
        $this->insertSafe('users', [
            [
                'id' => '1',
                'name' => 'تحریریه لیان دید',
                'email' => 'info@liandid.ir',
                'password' => Hash::make('12345678'),
                'role' => 'ADMIN',
                'permissions' => '[]',
                'isActive' => true,
            ]
        ]);

        // ۲. ایجاد دسته‌بندی‌ها
        $this->insertSafe('categories', [
            ['id' => '1', 'name' => 'اخبار ویژه', 'slug' => 'special', 'color' => '#1B365D', 'order' => 1],
            ['id' => '2', 'name' => 'سیاسی و استانی', 'slug' => 'political', 'color' => '#1e3a64', 'order' => 2],
            ['id' => '3', 'name' => 'ورزشی', 'slug' => 'sports', 'color' => '#C9A96E', 'order' => 3],
            ['id' => '4', 'name' => 'تحلیل و یادداشت', 'slug' => 'analysis', 'color' => '#2E5090', 'order' => 4],
        ]);

        // ۳. ایجاد اخبار با عکس‌های استاندارد، باکیفیت و شکیل خبری
        $this->insertSafe('articles', [
            [
                'id' => '1',
                'title' => 'توسعه زیرساخت‌های بندری و اقتصادی استان بوشهر در سال جدید',
                'slug' => 'bushehr-port-development',
                'subtitle' => 'گزارش اختصاصی از طرح‌های عمرانی جنوب کشور',
                'excerpt' => 'با بهره‌برداری از فاز جدید طرح‌های توسعه‌ای در بنادر استان بوشهر، ظرفیت تخلیه و بارگیری کالا افزایش چشمگیری خواهد یافت.',
                'content' => '<p>به گزارش تحریریه <strong>لیان دید</strong>، طرح‌های توسعه زیرساخت‌های بندری در استان بوشهر با سرعت در حال اجراست و پیش‌بینی می‌شود نقش مهمی در رونق اقتصادی منطقه ایفا کند.</p>',
                'featuredImage' => 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=1200&q=80',
                'status' => 'PUBLISHED',
                'viewCount' => 1540,
                'isFeatured' => true,
                'isBreaking' => true,
                'isPinned' => false,
                'placement' => 'hero',
                'authorId' => '1',
                'categoryId' => '1',
                'publishedAt' => now(),
            ],
            [
                'id' => '2',
                'title' => 'پیروزی ارزشمند نماینده بوشهر در رقابت‌های لیگ کشوری',
                'slug' => 'bushehr-sports-victory',
                'excerpt' => 'تیم استان بوشهر در دیداری حساس و پرهیجان موفق شد با نتیجه دو بر یک حریف خود را شکست دهد.',
                'content' => '<p>در چارچوب رقابت‌های هفتگی لیگ، تیم نماینده استان بوشهر با ارائه یک بازی هجومی توانست پیروز شود.</p>',
                'featuredImage' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=80',
                'status' => 'PUBLISHED',
                'viewCount' => 890,
                'isFeatured' => false,
                'isBreaking' => false,
                'isPinned' => false,
                'placement' => 'latest',
                'authorId' => '1',
                'categoryId' => '3',
                'publishedAt' => now()->subHours(2),
            ],
            [
                'id' => '3',
                'title' => 'تحلیل بازار مسکن و پروژه‌های شهرسازی در شهرستان جم و عسلویه',
                'slug' => 'jam-housing-market-analysis',
                'excerpt' => 'نگاهی تحلیلی به روند ساخت‌وساز و نیازهای زیرساختی شهرهای جنوبی استان بوشهر.',
                'content' => '<p>رشد جمعیت شاغل در صنایع جنوب استان نیازمند برنامه‌ریزی دقیق‌تر در حوزه مسکن است.</p>',
                'featuredImage' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80',
                'status' => 'PUBLISHED',
                'viewCount' => 620,
                'isFeatured' => true,
                'isBreaking' => false,
                'isPinned' => false,
                'placement' => 'analysis',
                'authorId' => '1',
                'categoryId' => '4',
                'publishedAt' => now()->subHours(5),
            ],
        ]);

        // ۴. ایجاد نظر نمونه
        $this->insertSafe('comments', [
            [
                'id' => '1',
                'content' => 'گزارش بسیار جامع و دقیقی بود. امیدواریم این پروژه‌ها هرچه سریع‌تر به بهره‌برداری برسند.',
                'approved' => true,
                'userId' => '1',
                'articleId' => '1',
            ]
        ]);

        Schema::enableForeignKeyConstraints();
    }
}