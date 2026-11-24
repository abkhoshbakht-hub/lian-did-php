<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Meta Tags -->
    <meta name="theme-color" content="#1B365D" />
    <meta name="description" content="پایگاه خبری تحلیلی لیان دید - اخبار لحظه‌ای استان بوشهر و ایران با پروانه انتشار ۹۶۲۲">
    <meta name="keywords" content="خبر, تحلیل, بوشهر, ایران, اخبار سیاسی, اخبار اقتصادی">
    
    {{-- پیش‌نمایش لینک در شبکه‌های اجتماعی (تلگرام و ...) --}}
    @php
        $ogArticle = null;
        $ogImage = null;
        if (preg_match('#^news/(.+)$#u', request()->path(), $ogMatch)) {
            $ogArticle = \Illuminate\Support\Facades\DB::table('articles')
                ->where('slug', urldecode($ogMatch[1]))
                ->where('status', 'PUBLISHED')
                ->first();
        }
        if ($ogArticle) {
            $ogDesc = $ogArticle->excerpt ?: mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($ogArticle->content))), 0, 180);
            $ogImage = preg_replace('#^https:#', 'http:', url('/og/' . $ogArticle->id . '.jpg'));
        }
    @endphp
    @if($ogArticle)
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="لیان دید" />
        <meta property="og:locale" content="fa_IR" />
        <meta property="og:title" content="{{ $ogArticle->title }}" />
        <meta property="og:description" content="{{ $ogDesc }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        @if($ogImage)
            <meta property="og:image" content="{{ $ogImage }}" />
            <meta property="og:image:secure_url" content="{{ $ogImage }}" />
            <meta property="og:image:type" content="image/jpeg" />
        @endif
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="{{ $ogArticle->title }}" />
        <meta name="twitter:description" content="{{ $ogDesc }}" />
        @if($ogImage)
            <meta name="twitter:image" content="{{ $ogImage }}" />
            <meta name="twitter:image:src" content="{{ $ogImage }}" />
            <meta name="og:image" content="{{ $ogImage }}" />
            <meta name="og:title" content="{{ $ogArticle->title }}" />
            <meta name="og:description" content="{{ $ogDesc }}" />
        @endif
    @endif
    
    <!-- Google Fonts: Vazirmatn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    
    <!-- RSS Links -->
    <link rel="alternate" type="application/rss+xml" title="لیان دید | اخبار" href="/api/rss/feed" />
    <link rel="alternate" type="application/rss+xml" title="لیان دید | اخبار سیاسی" href="/api/rss/category/siasi" />
    <link rel="alternate" type="application/rss+xml" title="لیان دید | اخبار اقتصادی" href="/api/rss/category/eghtesadi" />

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="antialiased font-vazir text-gray-900 bg-white">
    @inertia
</body>
</html>