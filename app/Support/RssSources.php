<?php

namespace App\Support;

/**
 * The RSS feeds Lian Did pulls from. Mirrors the Next.js list minus the
 * sources that were removed (کارانه بوشهر، ندای استان، انتخاب، عصر ایران،
 * اعتماد، طرفداری).
 */
class RssSources
{
    public const LIST = [
        // ── خبرگزاری‌های محلی بوشهر ──
        ['name' => 'بوشهر خبر', 'url' => 'https://bushehrkhabar.com/feed/', 'category' => 'بوشهر'],
        ['name' => 'بوشهر نیوز', 'url' => 'https://bushehrnews.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'پی جی نیوز', 'url' => 'https://www.pgnews.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'سوک نیوز', 'url' => 'https://sooknews.ir/?feed=rss2', 'category' => 'بوشهر'],
        ['name' => 'چغادک نیوز', 'url' => 'https://choghadaknews.ir/rss', 'category' => 'بوشهر'],
        ['name' => 'نصیر بوشهر', 'url' => 'https://nasirbushehr.ir/feed/', 'category' => 'بوشهر'],
        ['name' => 'مهر بوشهر', 'url' => 'https://www.mehrnews.com/rss/service/Provinces/Booshehr', 'category' => 'بوشهر'],
        ['name' => 'ایسنا بوشهر', 'url' => 'https://www.isna.ir/rss/tp/225', 'category' => 'بوشهر'],
        ['name' => 'تسنیم بوشهر', 'url' => 'https://www.tasnimnews.ir/fa/rss/feeds/53/0/13/1', 'category' => 'بوشهر'],

        // ── خبرگزاری‌های ملی ──
        ['name' => 'ایرنا', 'url' => 'https://www.irna.ir/rss', 'category' => 'ملی'],
        ['name' => 'ایسنا', 'url' => 'https://www.isna.ir/rss', 'category' => 'ملی'],
        ['name' => 'مهر', 'url' => 'https://www.mehrnews.com/rss', 'category' => 'ملی'],
        ['name' => 'فارس', 'url' => 'https://www.farsnews.ir/rss', 'category' => 'ملی'],
        ['name' => 'تسنیم', 'url' => 'https://www.tasnimnews.com/rss', 'category' => 'ملی'],

        // ── روزنامه‌ها ──
        ['name' => 'همشهری', 'url' => 'https://www.hamshahrionline.ir/rss', 'category' => 'روزنامه'],
        ['name' => 'دنیای اقتصاد', 'url' => 'https://donya-e-eqtesad.com/rss', 'category' => 'روزنامه'],
        ['name' => 'اطلاعات', 'url' => 'https://www.ettelaat.com/rss', 'category' => 'روزنامه'],
        ['name' => 'رسالت', 'url' => 'https://resalat-news.com/rss', 'category' => 'روزنامه'],
        ['name' => 'خبر ورزشی', 'url' => 'https://khabarvarzeshi.com/rss', 'category' => 'روزنامه'],

        // ── آدرس‌های جایگزین (وقتی آدرس اصلی جواب نداد) ──
        ['name' => 'بوشهر خبر (۲)', 'url' => 'https://bushehrkhabar.com/rss', 'category' => 'بوشهر', 'fallback' => true],
        ['name' => 'ایرنا (۲)', 'url' => 'https://www.irna.ir/service/rss', 'category' => 'ملی', 'fallback' => true],
        ['name' => 'ایسنا (۲)', 'url' => 'https://www.isna.ir/service/rss', 'category' => 'ملی', 'fallback' => true],
        ['name' => 'مهر (۲)', 'url' => 'https://www.mehrnews.com/rss/allnews', 'category' => 'ملی', 'fallback' => true],
        ['name' => 'تسنیم (۲)', 'url' => 'https://www.tasnimnews.com/rss/all', 'category' => 'ملی', 'fallback' => true],
    ];

    public static function all(bool $includeFallbacks = false): array
    {
        return array_values(array_filter(
            self::LIST,
            fn ($s) => $includeFallbacks || empty($s['fallback'])
        ));
    }

    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }
}
