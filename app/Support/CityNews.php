<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * News for the "شهرهای استان بوشهر" page.
 *
 * Mirrors the Next.js page: it merges three sources inside a 30 day
 * window and drops duplicates:
 *   1. items whose title mentions a Bushehr town or village
 *   2. items that already carry a region tag
 *   3. items filed under the بوشهر category
 */
class CityNews
{
    /** Town and village names, exactly as the Next.js page lists them. */
    public const KEYWORDS = [
        'بوشهر', 'بندر دیلم', 'خارگ', 'جزیره خارگ',
        'دیر', 'بندر دیر', 'بردخون', 'آبدان',
        'دیلم', 'بندر دیلم', 'حسن می', 'امام حسن',
        'گناوه', 'بندر گناوه', 'ریگ',
        'برازجان', 'آب‌پخش', 'بوشکان', 'تنگ ارم', 'سعدآباد', 'شبانکاره', 'دلوار', 'کلمه',
        'خورموج', 'کاکی', 'شنبه', 'بوالخیر', 'طسوج',
        'اهرم', 'کنگان', 'سیراف', 'بنک',
        'عسلویه', 'نخل تقی',
        'جم', 'شهر جم', 'انارستان', 'ریز',
    ];

    public static function all(int $days = 30): array
    {
        $since = now()->subDays($days);
        $cols = ['id', 'title', 'link', 'description', 'image', 'sourceName', 'category', 'publishedAt', 'region'];

        // 1. title matches a town or village name
        $keywordQuery = DB::table('external_news')
            ->where('status', 'APPROVED')
            ->where('publishedAt', '>=', $since)
            ->where(function ($w) {
                foreach (self::KEYWORDS as $i => $k) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->{$method}('title', 'like', "%{$k}%");
                }
            })
            ->orderByDesc('publishedAt')
            ->limit(60)
            ->get($cols);

        // 2. anything already tagged with a region
        $regionQuery = DB::table('external_news')
            ->where('status', 'APPROVED')
            ->whereNotNull('region')
            ->where('region', '<>', '')
            ->where('publishedAt', '>=', $since)
            ->orderByDesc('publishedAt')
            ->limit(60)
            ->get($cols);

        // 3. the بوشهر category
        $categoryQuery = DB::table('external_news')
            ->where('status', 'APPROVED')
            ->where('category', 'بوشهر')
            ->where('publishedAt', '>=', $since)
            ->orderByDesc('publishedAt')
            ->limit(60)
            ->get($cols);

        $seen = [];
        $out  = [];

        foreach ($keywordQuery->concat($regionQuery)->concat($categoryQuery) as $n) {
            $id = (string) $n->id;

            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $out[] = [
                'id'          => $id,
                'title'       => $n->title,
                'link'        => $n->link ?: '#',
                'description' => $n->description,
                'image'       => $n->image,
                'sourceName'  => $n->sourceName,
                'category'    => $n->category,
                'publishedAt' => $n->publishedAt,
                'region'      => $n->region,
            ];
        }

        return $out;
    }
}