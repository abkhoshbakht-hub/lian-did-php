<?php

namespace App\Support;

/**
 * The eleven Bushehr counties used by the map, the city pages and the
 * external_news.region field (format: `county:city|village`).
 */
class BushehrCounties
{
    public const LIST = [
        ['key' => 'deylam',     'name' => 'دیلم',      'keywords' => ['دیلم', 'امام حسن']],
        ['key' => 'genaveh',    'name' => 'گناوه',     'keywords' => ['گناوه', 'ریگ']],
        ['key' => 'kharg',      'name' => 'خارگ',      'keywords' => ['خارگ', 'جزیره خارگ']],
        ['key' => 'booshehr',   'name' => 'بوشهر',     'keywords' => ['بوشهر']],
        ['key' => 'dashtestan', 'name' => 'دشتستان',   'keywords' => ['برازجان', 'آب‌پخش', 'بوشکان', 'تنگ ارم', 'سعدآباد', 'شبانکاره', 'دلوار', 'کلمه']],
        ['key' => 'tangestan',  'name' => 'تنگستان',   'keywords' => ['اهرم']],
        ['key' => 'dashti',     'name' => 'دشتی',      'keywords' => ['خورموج', 'کاکی', 'شنبه', 'بوالخیر', 'طسوج']],
        ['key' => 'dayer',      'name' => 'دیر',       'keywords' => ['دیر', 'بردخون', 'آبدان']],
        ['key' => 'jam',        'name' => 'جم',        'keywords' => ['جم', 'انارستان', 'ریز']],
        ['key' => 'kangan',     'name' => 'کنگان',     'keywords' => ['کنگان', 'سیراف', 'بنک']],
        ['key' => 'asaluyeh',   'name' => 'عسلویه',    'keywords' => ['عسلویه', 'نخل تقی']],
    ];

    /** Map coordinates, identical to the Next.js component. */
    public const POINTS = [
        'deylam'     => ['cx' => 62,  'cy' => 96],
        'genaveh'    => ['cx' => 135, 'cy' => 208],
        'kharg'      => ['cx' => 64,  'cy' => 270],
        'booshehr'   => ['cx' => 182, 'cy' => 339],
        'dashtestan' => ['cx' => 239, 'cy' => 248],
        'tangestan'  => ['cx' => 251, 'cy' => 424],
        'dashti'     => ['cx' => 355, 'cy' => 502],
        'dayer'      => ['cx' => 346, 'cy' => 610],
        'jam'        => ['cx' => 436, 'cy' => 586],
        'kangan'     => ['cx' => 484, 'cy' => 672],
        'asaluyeh'   => ['cx' => 573, 'cy' => 753],
    ];

    public static function all(): array
    {
        return array_map(fn ($c) => $c + (self::POINTS[$c['key']] ?? []), self::LIST);
    }

    public static function find(string $key): ?array
    {
        foreach (self::LIST as $c) {
            if ($c['key'] === $key) return $c;
        }
        return null;
    }

    /** Nearest county to a map point, matching the Next.js implementation. */
    public static function nearest(float $x, float $y): string
    {
        $best = self::LIST[0]['key'];
        $bestD = INF;

        foreach (self::POINTS as $key => $p) {
            $d = ($p['cx'] - $x) ** 2 + ($p['cy'] - $y) ** 2;
            if ($d < $bestD) { $bestD = $d; $best = $key; }
        }

        return $best;
    }
}
