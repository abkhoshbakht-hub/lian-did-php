/**
 * Admin navigation, mirroring the Next.js dashboard sidebar exactly:
 * 9 collapsible sections, 39 links, gold (#C9A96E) on dark navy (#0a1220).
 */

const icon = (d, extra = '') => (
    <svg className={`w-4 h-4 ${extra}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {d.map((p, i) => (
            <path key={i} strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={p} />
        ))}
    </svg>
);

export const adminSections = [
    {
        title: 'اضافه کردن خبر جدید',
        icon: icon(['M12 4v16m8-8H4']),
        items: [{ href: '/admin/articles/new', label: 'خبر جدید' }],
    },
    {
        title: 'تنظیمات هدر',
        icon: icon(['M4 6h16M4 12h16M4 18h7']),
        items: [
            { href: '/admin/header/logo', label: 'لوگو' },
            { href: '/admin/header/license', label: 'پروانه انتشار' },
            { href: '/admin/header/about', label: 'درباره ما' },
            { href: '/admin/header/contact', label: 'تماس با ما' },
            { href: '/admin/header/search', label: 'جستجو' },
            { href: '/admin/header/nav', label: 'منوی ناوبری' },
            { href: '/admin/social', label: 'شبکه‌های اجتماعی' },
        ],
    },
    {
        title: 'صفحه اصلی',
        icon: icon(['M3 12l2-2m0 0l7-7l7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6']),
        items: [
            { href: '/admin/homepage/hero', label: 'هیرو' },
            { href: '/admin/homepage/latest', label: 'آخرین اخبار' },
            { href: '/admin/homepage/analysis', label: 'تحلیل خبر' },
            { href: '/admin/events', label: 'مناسبت‌های تقویمی' },
            { href: '/admin/external-news', label: 'اخبار لحظه‌ای' },
        ],
    },
    {
        title: 'تب‌های سایت',
        icon: icon(['M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z']),
        items: [
            { href: '/admin/articles?cat=siyasi', label: 'سیاسی' },
            { href: '/admin/articles?cat=eghtesadi', label: 'اقتصادی' },
            { href: '/admin/articles?cat=ejtemaei', label: 'اجتماعی' },
            { href: '/admin/articles?cat=beynolmelal', label: 'بین‌الملل' },
            { href: '/admin/articles?cat=fanavari', label: 'فناوری' },
            { href: '/admin/articles?cat=varzeshi', label: 'ورزشی' },
            { href: '/admin/articles?cat=farhangi', label: 'فرهنگی' },
            { href: '/admin/articles?cat=elmi', label: 'علمی' },
            { href: '/admin/gallery', label: 'گالری' },
        ],
    },
    {
        title: 'صفحات ایستا',
        icon: icon(['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']),
        items: [
            { href: '/admin/static-pages', label: 'ویرایش صفحات' },
            { href: '/admin/static-pages/launch', label: 'پیام مدیر مسئول' },
        ],
    },
    {
        title: 'اخبار',
        icon: icon(['M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z']),
        items: [
            { href: '/admin/articles?status=ARCHIVED', label: 'آرشیو اخبار' },
            { href: '/admin/submissions', label: 'ارسالی مخاطبین' },
        ],
    },
    {
        title: 'مدیریت',
        icon: icon(['M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z']),
        items: [
            { href: '/admin/categories', label: 'دسته‌بندی‌ها' },
            { href: '/admin/regions', label: 'شهرها' },
            { href: '/admin/comments', label: 'نظرات' },
            { href: '/admin/subscribers', label: 'خبرنامه' },
            { href: '/admin/users', label: 'کاربران' },
            { href: '/admin/media', label: 'رسانه' },
            { href: '/admin/image-settings', label: 'تنظیم عکس' },
            { href: '/admin/shortlinks', label: 'کوتاه‌کننده لینک' },
            { href: '/admin/newspapers', label: 'صفحه اول روزنامه‌ها' },
        { href: '/admin/rss-sources', label: 'منابع RSS' },
        { href: '/admin/backup', label: 'پشتیبان‌گیری' },
        ],
    },
    {
        title: 'فوتر',
        icon: icon(['M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10']),
        items: [{ href: '/admin/footer', label: 'تنظیمات فوتر' }],
    },
    {
        title: 'راهنما',
        icon: icon(['M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z']),
        items: [
            { href: '/admin/help', label: 'آموزش و راهنما' },
    { href: '/admin/cron', label: 'کرون و سلامت سرور' },
            { href: '/admin/change-password', label: 'تغییر رمز عبور' },
        ],
    },
];

/** Total links, used by the tests. */
export const adminLinkCount = adminSections.reduce((n, s) => n + s.items.length, 0);
