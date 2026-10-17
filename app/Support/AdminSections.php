<?php

namespace App\Support;

/**
 * Metadata for the text-based settings screens (header / footer / social ...).
 */
class AdminSections
{
    public const GROUPS = [
        'logo'    => 'لوگو',
        'license' => 'پروانه انتشار',
        'about'   => 'درباره ما',
        'contact' => 'تماس با ما',
        'search'  => 'جستجو',
        'nav'     => 'منوی ناوبری',
        'footer'  => 'فوتر',
        'social'  => 'شبکه‌های اجتماعی',
    ];

    public const FIELDS = [
        'logo' => [
            ['key' => 'logo_image', 'label' => 'تصویر لوگو', 'type' => 'image'],
            ['key' => 'logo_width', 'label' => 'عرض لوگو (پیکسل)', 'type' => 'number'],
            ['key' => 'show_text', 'label' => 'نمایش نام «لیان دید» کنار لوگو', 'type' => 'checkbox'],
        ],
        'license' => [
            ['key' => 'license_text', 'label' => 'متن پروانه', 'type' => 'textarea'],
            ['key' => 'license_number', 'label' => 'شماره پروانه', 'type' => 'text'],
            ['key' => 'license_image', 'label' => 'تصویر پروانه', 'type' => 'image'],
        ],
        'about' => [
            ['key' => 'about_title', 'label' => 'عنوان', 'type' => 'text'],
            ['key' => 'about_body', 'label' => 'متن', 'type' => 'textarea'],
            ['key' => 'about_image', 'label' => 'تصویر', 'type' => 'image'],
        ],
        'contact' => [
            ['key' => 'contact_address', 'label' => 'نشانی', 'type' => 'textarea'],
            ['key' => 'contact_phone', 'label' => 'تلفن', 'type' => 'text'],
            ['key' => 'contact_mobile', 'label' => 'همراه', 'type' => 'text'],
            ['key' => 'contact_email', 'label' => 'ایمیل', 'type' => 'text'],
            ['key' => 'contact_map', 'label' => 'نشانی نقشه (لینک)', 'type' => 'text'],
        ],
        'search' => [
            ['key' => 'search_enabled', 'label' => 'نمایش جستجو در هدر', 'type' => 'checkbox'],
            ['key' => 'search_placeholder', 'label' => 'متن راهنمای جعبه جستجو', 'type' => 'text'],
        ],
        'nav' => [
            ['key' => 'nav_items', 'label' => 'آیتم‌های منو (هر خط یک آیتم: عنوان | لینک)', 'type' => 'textarea'],
            ['key' => 'show_breaking', 'label' => 'نمایش نوار خبر فوری', 'type' => 'checkbox'],
            ['key' => 'breaking_text', 'label' => 'متن نوار خبر فوری', 'type' => 'text'],
        ],
        'footer' => [
            ['key' => 'footer_about', 'label' => 'معرفی کوتاه', 'type' => 'textarea'],
            ['key' => 'footer_copyright', 'label' => 'متن کپی‌رایت', 'type' => 'text'],
            ['key' => 'footer_links', 'label' => 'لینک‌های فوتر (هر خط یکی)', 'type' => 'textarea'],
        ],
        'social' => [
            ['key' => 'social_instagram', 'label' => 'اینستاگرام', 'type' => 'text'],
            ['key' => 'social_telegram', 'label' => 'تلگرام', 'type' => 'text'],
            ['key' => 'social_twitter', 'label' => 'ایکس (توییتر)', 'type' => 'text'],
            ['key' => 'social_whatsapp', 'label' => 'واتس‌اپ', 'type' => 'text'],
            ['key' => 'social_email', 'label' => 'ایمیل', 'type' => 'text'],
        ],
    ];

    public static function settingsTitle(string $group): string
    {
        return self::GROUPS[$group] ?? $group;
    }

    public static function fields(string $group): array
    {
        return self::FIELDS[$group] ?? [];
    }
}
