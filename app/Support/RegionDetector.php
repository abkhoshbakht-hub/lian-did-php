<?php

namespace App\Support;

/**
 * Detects which Bushehr county a piece of news belongs to by matching
 * Persian city/village names, including the Ezafe suffix variations.
 */
class RegionDetector
{
    public const COUNTIES = [
        ['slug' => 'booshehr', 'name' => 'بوشهر', 'city' => ['بوشهر', 'بندر بوشهر', 'جزیره خارگ', 'خارگ'], 'village' => ['عالی‌شاه', 'سی‌.contracted', 'سازمان‌چشمه', 'ظهران']],
        ['slug' => 'dayer', 'name' => 'دیر', 'city' => ['دیر', 'بندر دیر', 'بردخون', 'آبدان'], 'village' => ['نبر', 'دیرچک', 'کبرا', 'حدتیه دیر']],
        ['slug' => 'deylam', 'name' => 'دیلم', 'city' => ['دیلم', 'بندر امام حسن'], 'village' => ['حدتیه', 'بستانه امام حسن']],
        ['slug' => 'genaveh', 'name' => 'گناوه', 'city' => ['گناوه', 'بندر گناوه', 'ریگ'], 'village' => ['بندر بی‌گانه', 'بستانه ساحلی']],
        ['slug' => 'dashtestan', 'name' => 'دشتستان', 'city' => ['برازجان', 'آب‌پخش', 'بوشکان', 'تنگ ارم', 'سعدآباد', 'شبانکاره', 'دلوار', 'کلمه', 'حدتیه'], 'village' => ['بستانه', 'دشتستان']],
        ['slug' => 'dashti', 'name' => 'دشتی', 'city' => ['خورموج', 'کاکی', 'شنبه', 'بوالخیر', 'طسوج'], 'village' => ['بستانه', 'دشتستان']],
        ['slug' => 'tangestan', 'name' => 'تنگستان', 'city' => ['اهرم', 'دلوار', 'آبنو'], 'village' => ['بستانه', 'دشتستان']],
        ['slug' => 'kangan', 'name' => 'کنگان', 'city' => ['کنگان', 'بندر کنگان', 'سیراف', 'بنک'], 'village' => ['بستانه', 'دشتستان']],
        ['slug' => 'asaluyeh', 'name' => 'عسلویه', 'city' => ['عسلویه', 'نخل تقی', 'بندر عسلویه'], 'village' => ['بستانه', 'دشتستان', 'نخل تقی']],
        ['slug' => 'jam', 'name' => 'جم', 'city' => ['جم', 'شهر جم', 'انارستان', 'ریز'], 'village' => ['بستانه', 'دشتستان']],
        ['slug' => 'kharg', 'name' => 'جزیره خارگ', 'city' => ['خارگ', 'جزیره خارگ'], 'village' => []],
    ];

    private const PERSIAN = '\x{0600}-\x{06FF}';
    private const SUFFIX = '(?:[\x{062A}\x{0628}\x{06A9}\x{06A9}\x{06CC}\x{0627}\x{0646}\x{06CC}\x{0627}\x{0631}\x{06CC}])?';

    /**
     * Returns "county:city" / "county:village", or null when nothing matches.
     * Bushehr is checked last so it never swallows a specific county.
     */
    public static function detect(?string $title, ?string $description = null): ?string
    {
        $text = trim(($title ?? '') . ' ' . ($description ?? ''));
        if ($text === '') return null;

        $ordered = array_values(array_filter(self::COUNTIES, fn ($c) => $c['slug'] !== 'booshehr'));
        $ordered[] = current(array_filter(self::COUNTIES, fn ($c) => $c['slug'] === 'booshehr'));

        foreach ($ordered as $county) {
            if (self::matches($text, $county['city']))  return $county['slug'] . ':city';
            if (self::matches($text, $county['village'])) return $county['slug'] . ':village';
        }

        return null;
    }

    public static function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $word) {
            if ($word === '') continue;
            if (preg_match(self::wordRegex($word), $text)) return true;
        }
        return false;
    }

    private static function wordRegex(string $word): string
    {
        $quoted = preg_quote($word, '/');

        return '/(?:^|[^' . self::PERSIAN . '])' . $quoted . self::SUFFIX . '(?=$|[^' . self::PERSIAN . '])/u';
    }

    public static function label(?string $region): string
    {
        if (! $region) return '';
        [$slug, $type] = array_pad(explode(':', $region, 2), 2, 'city');
        foreach (self::COUNTIES as $c) {
            if ($c['slug'] === $slug) {
                return ($type === 'village' ? 'روستای ' : 'شهر ') . $c['name'];
            }
        }
        return $region;
    }

    public static function countyName(string $slug): ?string
    {
        foreach (self::COUNTIES as $c) {
            if ($c['slug'] === $slug) return $c['name'];
        }
        return null;
    }
}
