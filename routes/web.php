<?php

use App\Http\Controllers\AdminController;
use App\Support\ArticleFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// ---------------------------------------------------------------- صفحه اصلی
Route::get('/', function () {
    $home = \App\Support\HomepageResolver::resolve();

    $articles = ArticleFormatter::collection(
        DB::table('articles')
            ->where('status', 'PUBLISHED')
            ->orderByDesc('publishedAt')
            ->get()
    );

    $categories = DB::table('categories')->orderBy('order')->get();

    return Inertia\Inertia::render('Home', [
        'articles'   => $articles,
        'categories' => $categories,
        'home'       => $home,
    ]);
})->name('home');

// ---------------------------------------------------------------- صفحه تک‌خبر
Route::get('/news/{slug}', function ($slug) {
    $article = DB::table('articles')
        ->where('slug', $slug)
        ->where('status', 'PUBLISHED')
        ->first();

    abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

    DB::table('articles')->where('id', $article->id)->increment('viewCount');

    $fresh = DB::table('articles')->where('id', $article->id)->first();

    $comments = DB::table('comments')
        ->where('articleId', $article->id)
        ->where('approved', 1)
        ->orderBy('created_at')
        ->get()
        ->map(fn ($c) => [
            'id'        => (string) $c->id,
            'content'   => $c->content,
            'createdAt' => $c->created_at,
        ])
        ->values();

    return Inertia\Inertia::render('Article', [
        'article'  => ArticleFormatter::make($fresh, withContent: true),
        'comments' => $comments,
    ]);
})->name('news.show');

// ---------------------------------------------------------------- دسته‌بندی‌ها
Route::get('/category/bushahr/cities', function () {
    return Inertia\Inertia::render('CityMap', [
        'news' => \App\Support\CityNews::all(),
    ]);
})->name('cities.map');

Route::get('/category/{slug}/{subSlug?}', function ($slug, $subSlug = null) {
    $category = DB::table('categories')->where('slug', $slug)->first();

    abort_if(! $category, 404, 'دسته‌بندی مورد نظر یافت نشد.');

    $articles = ArticleFormatter::collection(
        DB::table('articles')
            ->where('status', 'PUBLISHED')
            ->where('categoryId', $category->id)
            ->orderByDesc('publishedAt')
            ->get()
    );

    return Inertia\Inertia::render('Category', [
        'articles'     => $articles,
        'categories'   => DB::table('categories')->orderBy('order')->get(),
        'categoryName' => $category->name,
        'categorySlug' => $category->slug,
        'subSlug'      => $subSlug,
    ]);
})->name('category.show');

// ---------------------------------------------------------------- پنل مدیریت
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    $s = \App\Http\Controllers\Admin\SectionController::class;
    $m = \App\Http\Controllers\Admin\ManageController::class;

    // داشبورد و اخبار
    Route::get('/dashboard', [$s, 'dashboard'])->name('dashboard');
    Route::get('/articles', [$s, 'articles'])->name('admin.articles');
    Route::post('/articles', [\App\Http\Controllers\AdminController::class, 'storeArticle'])->name('admin.articles.store');
    Route::put('/articles/{id}', [\App\Http\Controllers\AdminController::class, 'updateArticle'])->name('admin.articles.update');
    Route::delete('/articles/{id}', [\App\Http\Controllers\AdminController::class, 'destroyArticle'])->name('admin.articles.destroy');

    // مدیریت محتوا
    Route::get('/categories', [$s, 'categories'])->name('admin.categories');
    Route::get('/regions', [$s, 'regions'])->name('admin.regions');
    Route::get('/comments', [$s, 'comments'])->name('admin.comments');
    Route::get('/subscribers', [$s, 'subscribers'])->name('admin.subscribers');
    Route::get('/users', [$s, 'users'])->name('admin.users');
    Route::get('/media', [$s, 'media'])->name('admin.media');

    // تنظیم عکس
    Route::get('/image-settings', [$s, 'imageSettings'])->name('admin.image-settings');
    Route::post('/image-settings', [$s, 'saveImageSettings'])->name('admin.image-settings.save');
    Route::post('/upload', [\App\Http\Controllers\Admin\MediaController::class, 'upload'])->name('admin.upload');
    Route::post('/gallery/albums', [\App\Http\Controllers\Admin\GalleryController::class, 'storeAlbum'])->name('admin.gallery.albums.store');
    Route::put('/gallery/albums/{id}', [\App\Http\Controllers\Admin\GalleryController::class, 'updateAlbum'])->name('admin.gallery.albums.update');
    Route::delete('/gallery/albums/{id}', [\App\Http\Controllers\Admin\GalleryController::class, 'destroyAlbum'])->name('admin.gallery.albums.destroy');
    Route::post('/gallery/albums/{id}/items', [\App\Http\Controllers\Admin\GalleryController::class, 'storeItem'])->name('admin.gallery.items.store');
    Route::delete('/gallery/items/{id}', [\App\Http\Controllers\Admin\GalleryController::class, 'destroyItem'])->name('admin.gallery.items.destroy');

    // تنظیمات متنی (هر گروه یک جفت مسیر)
    Route::get('/settings/logo', [$s, 'settingsPage'])->defaults('group', 'logo')->name('admin.settings.logo');
    Route::post('/settings/logo', [$s, 'saveSettings'])->defaults('group', 'logo')->name('admin.settings.logo.save');
    Route::get('/settings/license', [$s, 'settingsPage'])->defaults('group', 'license')->name('admin.settings.license');
    Route::post('/settings/license', [$s, 'saveSettings'])->defaults('group', 'license')->name('admin.settings.license.save');
    Route::get('/settings/about', [$s, 'settingsPage'])->defaults('group', 'about')->name('admin.settings.about');
    Route::post('/settings/about', [$s, 'saveSettings'])->defaults('group', 'about')->name('admin.settings.about.save');
    Route::get('/settings/contact', [$s, 'settingsPage'])->defaults('group', 'contact')->name('admin.settings.contact');
    Route::post('/settings/contact', [$s, 'saveSettings'])->defaults('group', 'contact')->name('admin.settings.contact.save');
    Route::get('/settings/search', [$s, 'settingsPage'])->defaults('group', 'search')->name('admin.settings.search');
    Route::post('/settings/search', [$s, 'saveSettings'])->defaults('group', 'search')->name('admin.settings.search.save');
    Route::get('/settings/nav', [$s, 'settingsPage'])->defaults('group', 'nav')->name('admin.settings.nav');
    Route::post('/settings/nav', [$s, 'saveSettings'])->defaults('group', 'nav')->name('admin.settings.nav.save');
    Route::get('/settings/footer', [$s, 'settingsPage'])->defaults('group', 'footer')->name('admin.settings.footer');
    Route::post('/settings/footer', [$s, 'saveSettings'])->defaults('group', 'footer')->name('admin.settings.footer.save');
    Route::get('/settings/social', [$s, 'settingsPage'])->defaults('group', 'social')->name('admin.settings.social');
    Route::post('/settings/social', [$s, 'saveSettings'])->defaults('group', 'social')->name('admin.settings.social.save');

    // ─────────────────────────── میان‌برهای منو (هم‌ارز مسیرهای تنظیمات)
    Route::get('/articles/new', [$s, 'articles'])->defaults('new', '1')->name('admin.articles.new');
    Route::get('/header/{group}', [$s, 'settingsPage'])
        ->where('group', 'logo|license|about|contact|search|nav')
        ->name('admin.header');
    Route::get('/social', [$s, 'settingsPage'])->defaults('group', 'social')->name('admin.social');
    Route::get('/footer', [$s, 'settingsPage'])->defaults('group', 'footer')->name('admin.footer');
    Route::get('/static-pages/launch', [$s, 'settingsPage'])
        ->defaults('group', 'launch')->name('admin.launch');
    Route::post('/static-pages/launch', [$s, 'saveSettings'])->defaults('group', 'launch')->name('admin.launch.save');

    // ─────────────────────────── چیدمان صفحه اصلی
    Route::get('/homepage/{section}', [$m, 'homepage'])->where('section', 'hero|latest|analysis')
        ->defaults('section', 'hero')->name('admin.homepage');    Route::post('/articles/{id}/placement', [$m, 'setPlacement'])->name('admin.articles.placement');
    Route::post('/slots/{key}', [$m, 'updateSlot'])->name('admin.slots.update');

    // ─────────────────────────── اخبار لحظه‌ای
    Route::get('/external-news', [$m, 'externalNews'])->name('admin.external-news');
    Route::post('/external-news/bulk', [$m, 'bulkNewsStatus'])->name('admin.external-news.bulk');
    Route::post('/external-news/{id}/status', [$m, 'setNewsStatus'])->name('admin.external-news.status');
    Route::delete('/external-news/{id}', [$m, 'destroyNews'])->name('admin.external-news.destroy');

    // ─────────────────────────── مناسبت‌های تقویمی
    Route::get('/events', [$m, 'events'])->name('admin.events');
    Route::post('/events', [$m, 'storeEvent'])->name('admin.events.store');
    Route::delete('/events/{key}', [$m, 'destroyEvent'])->name('admin.events.destroy');

    // ─────────────────────────── صفحات ایستا
    Route::get('/static-pages', [$m, 'staticPages'])->name('admin.static-pages');
    Route::post('/static-pages/{slug}', [$m, 'saveStaticPage'])->name('admin.static-pages.save');

    // ─────────────────────────── ارسالی مخاطبین
    Route::get('/submissions', [$m, 'submissions'])->name('admin.submissions');
    Route::post('/submissions/{id}/status', [$m, 'setSubmission'])->name('admin.submissions.status');
    Route::delete('/submissions/{id}', [$m, 'destroySubmission'])->name('admin.submissions.destroy');

    // ─────────────────────────── گالری
    Route::get('/gallery', [\App\Http\Controllers\Admin\GalleryController::class, 'index'])->name('admin.gallery');
    Route::post('/gallery/delete', [$m, 'destroyGalleryImage'])->name('admin.gallery.delete');

    // ─────────────────────────── لینک کوتاه
    Route::get('/shortlinks', [$m, 'shortlinks'])->name('admin.shortlinks');
    Route::post('/shortlinks', [$m, 'storeShortlink'])->name('admin.shortlinks.store');
    Route::delete('/shortlinks/{code}', [$m, 'destroyShortlink'])->name('admin.shortlinks.destroy');

    // ─────────────────────────── خاموشی سایت
    Route::get('/shutdown', [$m, 'shutdown'])->name('admin.shutdown');
    Route::post('/shutdown', [$m, 'setMode'])->name('admin.shutdown.mode');

    // ─────────────────────────── روزنامه‌ها
    Route::get('/newspapers', [$m, 'newspapers'])->name('admin.newspapers');
    Route::post('/newspapers/fetch', [$m, 'fetchNewspaperFronts'])->name('admin.newspapers.fetch');
    Route::post('/newspapers', [$m, 'storeNewspaper'])->name('admin.newspapers.store');
    Route::put('/newspapers/{id}', [$m, 'updateNewspaper'])->name('admin.newspapers.update');
    Route::delete('/newspapers/{id}', [$m, 'destroyNewspaper'])->name('admin.newspapers.destroy');
    Route::post('/newspapers/sources', [$m, 'storeNewspaperSource'])->name('admin.newspapers.sources.store');
    Route::delete('/newspapers/sources/{id}', [$m, 'destroyNewspaperSource'])->name('admin.newspapers.sources.destroy');
    Route::delete('/newspapers/issues/{id}', [$m, 'destroyNewspaperIssue'])->name('admin.newspapers.issues.destroy');

    // ─────────────────────────── کرون (هاست اشتراکی)


    // ─────────────────────────── RSS
    Route::get('/rss-sources', [$m, 'rssSources'])->name('admin.rss-sources');
    Route::post('/rss/fetch', [$m, 'fetchRss'])->name('admin.rss.fetch');
    Route::post('/rss/prune', [$m, 'pruneRss'])->name('admin.rss.prune');
    Route::post('/rss/count', [$m, 'countRssBefore'])->name('admin.rss.count');
    Route::post('/rss/delete-before', [$m, 'deleteRssBefore'])->name('admin.rss.delete-before');

    // ─────────────────────────── پشتیبان‌گیری (خارج از هاست)
    $b = \App\Http\Controllers\Admin\BackupController::class;
    Route::get('/backup', [$b, 'index'])->name('admin.backup');
    Route::post('/backup/download', [$b, 'download'])->name('admin.backup.download');
    Route::post('/backup/telegram', [$b, 'sendTelegram'])->name('admin.backup.telegram');
    Route::post('/backup/telegram/settings', [$b, 'saveTelegram'])->name('admin.backup.telegram.settings');

    // ─────────────────────────── راهنما
    Route::get('/help', [$m, 'help'])->name('admin.help');
    Route::get('/cron', function () {
        return Inertia\Inertia::render('Admin/Cron', [
            'token'   => \App\Support\CronToken::get(),
            'health'  => \App\Support\ServerHealth::run(),
            'lastRun' => json_decode((string) \App\Support\SettingStore::get('cron.last_run'), true),
        ]);
    })->name('admin.cron');
    Route::get('/change-password', fn () => Inertia\Inertia::render('Admin/ChangePassword'))->name('admin.change-password');
    Route::put('/change-password', [\App\Http\Controllers\Admin\AccountController::class, 'updatePassword'])
        ->defaults('group', 'password')
        ->name('admin.change-password.update');

    // ─────────────────────────── عملیات فهرست‌ها
    Route::post('/categories', [$m, 'storeCategory'])->name('admin.categories.store');
    Route::put('/categories/{id}', [$m, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [$m, 'destroyCategory'])->name('admin.categories.destroy');

    Route::post('/users', [$m, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{id}', [$m, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [$m, 'destroyUser'])->name('admin.users.destroy');
    Route::post('/users/{id}/toggle', [$m, 'toggleUser'])->name('admin.users.toggle');

    Route::post('/comments/{id}/toggle', [$m, 'toggleComment'])->name('admin.comments.toggle');
    Route::delete('/comments/{id}', [$m, 'destroyComment'])->name('admin.comments.destroy');

    Route::post('/subscribers/{id}/toggle', [$m, 'toggleSubscriber'])->name('admin.subscribers.toggle');
    Route::delete('/subscribers/{id}', [$m, 'destroySubscriber'])->name('admin.subscribers.destroy');
});

// ---------------------------------------------------------------- لینک کوتاه (عمومی)
Route::get('/s/{code}', function ($code) {
    $row = DB::table('site_settings')->where('key', 'short.' . $code)->first();
    abort_if(! $row, 404);

    $data = json_decode($row->value, true);
    $url = $data['url'] ?? null;
    abort_if(! $url, 404);

    DB::table('site_settings')->where('key', 'short.' . $code)->update([
        'value' => json_encode(['url' => $url, 'hits' => (int) ($data['hits'] ?? 0) + 1], JSON_UNESCAPED_UNICODE),
    ]);

    return redirect()->away($url);
})->name('shortlink');

// ---------------------------------------------------------------- پروفایل کاربر
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------------------------------------------------------------- صفحات عمومی
Route::get('/latest', function () {
    $articles = \App\Support\ArticleFormatter::collection(
        DB::table('articles')
            ->where('status', 'PUBLISHED')
            ->orderByDesc('publishedAt')
            ->limit(60)
            ->get()
    );

    return Inertia\Inertia::render('Latest', ['articles' => $articles]);
})->name('latest');

Route::get('/newspapers', function () {
    return Inertia\Inertia::render('Newspapers', [
        'papers' => \App\Support\NewspaperCatalog::papers(),
        'issues' => \App\Support\NewspaperCatalog::latestIssues(),
    ]);
})->name('newspapers.index');

Route::get('/newspapers/archive', function () {
    $papers = \App\Support\NewspaperCatalog::papers();
    $papers = collect($papers)
        ->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'category' => $p['category']])
        ->all();

    $dates = DB::table('newspaper_issues')
        ->where('status', 'PUBLISHED')
        ->orderByDesc('date')
        ->distinct()
        ->pluck('date');

    return Inertia\Inertia::render('NewspapersArchive', [
        'papers' => $papers,
        'dates'  => $dates,
    ]);
})->name('newspapers.archive');

Route::get('/api/newspapers/archive', function (Illuminate\Http\Request $request) {
    try {
        $q = DB::table('newspaper_issues')
            ->join('newspapers', 'newspaper_issues.newspaperId', '=', 'newspapers.id')
            ->where('newspaper_issues.status', 'PUBLISHED')
            ->where('newspapers.active', 1);

        if ($request->query('date')) {
            $q->where('newspaper_issues.date', $request->query('date'));
        }
        if ($paper = $request->query('paper')) {
            $p = DB::table('newspapers')->where('slug', $paper)->orWhere('id', $paper)->first();
            if ($p) {
                $q->where('newspaper_issues.newspaperId', $p->id);
            }
        }
        if ($cat = $request->query('category')) {
            if (in_array($cat, ['national', 'bushehr', 'sports'])) {
                $q->where('newspapers.category', $cat);
            }
        }

        $page = max(1, (int) $request->query('page', 1));
        $per = 24;

        $total = (clone $q)->count();
        $items = $q->orderByDesc('newspaper_issues.date')
            ->orderBy('newspapers.displayOrder')
            ->offset(($page - 1) * $per)
            ->limit($per)
            ->get([
                'newspaper_issues.id',
                'newspaper_issues.persianDate',
                'newspaper_issues.issueNumber',
                'newspaper_issues.imageUrl',
                'newspaper_issues.thumbnailUrl',
                'newspapers.name as newspaper_name',
                'newspapers.slug as newspaper_slug',
                'newspapers.category as newspaper_category',
            ])
            ->map(fn ($i) => [
                'id'           => (string) $i->id,
                'persianDate'  => $i->persianDate,
                'issueNumber'  => $i->issueNumber,
                'imageUrl'     => $i->imageUrl,
                'thumbnailUrl' => $i->thumbnailUrl,
                'slug'         => $i->newspaper_slug,
                'newspaper'    => [
                    'name'     => $i->newspaper_name,
                    'slug'     => $i->newspaper_slug,
                    'category' => $i->newspaper_category,
                ],
            ])
            ->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'items' => $items,
                'total' => $total,
                'page'  => $page,
                'pages' => (int) ceil($total / $per),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'message' => 'خطا'], 500);
    }
})->name('newspapers.archive.api');

Route::get('/api/newspapers', function () {
    try {
        // تاریخ امروز تهران (شمسی) — مطابق lib/newspapers/date در نکست
        $now = \Carbon\Carbon::now('Asia/Tehran');
        $gy = (int) $now->format('Y');
        $gm = (int) $now->format('n');
        $gd = (int) $now->format('j');
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + 365 * $gy + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        $q = DB::table('newspapers')->where('active', 1);
        if ($cat = request()->query('category')) {
            if (in_array($cat, ['national', 'bushehr', 'sports'])) {
                $q->where('category', $cat);
            }
        }
        $papers = $q->orderBy('displayOrder')->get(['id', 'name', 'slug', 'category', 'website']);

        $ids = $papers->pluck('id')->all();
        $issues = DB::table('newspaper_issues')
            ->whereIn('newspaperId', $ids)
            ->where('status', 'PUBLISHED')
            ->orderByDesc('created_at')
            ->get(['newspaperId', 'imageUrl', 'thumbnailUrl', 'persianDate', 'issueNumber', 'originalUrl', 'date']);

        $map = [];
        foreach ($issues as $t) {
            if (! isset($map[$t->newspaperId])) {
                $map[$t->newspaperId] = $t;
            }
        }
        $missing = collect($ids)->reject(fn ($id) => isset($map[$id]));        if ($missing->isNotEmpty()) {
            $review = DB::table('newspaper_issues')
                ->whereIn('newspaperId', $missing->all())
                ->where('status', 'NEEDS_REVIEW')
                ->whereNotNull('imageUrl')
                ->where('imageUrl', '!=', '')
                ->orderByDesc('created_at')
                ->get(['newspaperId', 'imageUrl', 'thumbnailUrl', 'persianDate', 'issueNumber', 'originalUrl', 'date']);
            foreach ($review as $t) {
                if (! isset($map[$t->newspaperId]) && $t->imageUrl) {
                    $map[$t->newspaperId] = $t;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'date'   => $jy . '/' . str_pad($jm, 2, '0', STR_PAD_LEFT) . '/' . str_pad($jd, 2, '0', STR_PAD_LEFT),
                'papers' => $papers->map(fn ($p) => [
                    'id'       => (string) $p->id,
                    'name'     => $p->name,
                    'slug'     => $p->slug,
                    'category' => $p->category,
                    'website'  => $p->website,
                    'today'    => isset($map[$p->id]) ? [
                        'newspaperId' => (string) $map[$p->id]->newspaperId,
                        'imageUrl'    => $map[$p->id]->imageUrl,
                        'thumbnailUrl'=> $map[$p->id]->thumbnailUrl,
                        'persianDate' => $map[$p->id]->persianDate,
                        'issueNumber' => $map[$p->id]->issueNumber,
                        'originalUrl' => $map[$p->id]->originalUrl,
                        'date'        => $map[$p->id]->date,
                    ] : null,
                ]),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'message' => 'خطا'], 500);
    }
})->name('newspapers.api');

Route::get('/api/rss', function (Illuminate\Http\Request $request) {
    try {
        $q = DB::table('external_news')->where('status', 'APPROVED');
        if ($cat = $request->query('category')) {
            $q->where('category', $cat);
        }
        $news = $q->orderByDesc('publishedAt')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data'    => $news->map(fn ($n) => [
                'id'          => (string) $n->id,
                'title'       => $n->title,
                'link'        => $n->link,
                'description' => $n->description,
                'image'       => $n->image,
                'source'      => $n->source,
                'sourceName'  => $n->sourceName,
                'category'    => $n->category,
                'publishedAt' => $n->publishedAt ? \Carbon\Carbon::parse($n->publishedAt)->toISOString() : null,
            ]),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'message' => 'خطا'], 500);
    }
})->name('rss.api');

// Endpoit cron-job.org / GitHub Action: هرگز ۴۰۴ نمی‌دهد — همیشه ۲۰۰ JSON
Route::get('/api/rss/refresh', function (Illuminate\Http\Request $request) {
    $budget = max(10, min(90, (int) $request->query('budget', 45)));
    $t0 = microtime(true);
    try {
        $result = (new \App\Support\RssFetcher($budget, 6, 0, false))->run();
        $pruned = \App\Support\RssFetcher::prunePending(14);
        return response()->json([
            'ok'        => true,
            'locked'    => $result['locked'] ?? false,
            'sources'   => $result['ok'] . '/' . ($result['ok'] + $result['failed']),
            'newItems'  => $result['newItems'],
            'pruned'    => $pruned,
            'elapsed'   => round(microtime(true) - $t0, 1),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'message' => mb_substr($e->getMessage(), 0, 200)]);
    }
})->name('rss.refresh');

Route::get('/kiosk', function () {
    $byDate = [];

    $rows = DB::table('site_settings')
        ->where('key', 'like', 'frontpage:%')
        ->orderByDesc('key')
        ->limit(200)
        ->get(['key', 'value']);

    foreach ($rows as $r) {
        $parts = explode(':', $r->key);
        if (count($parts) < 3) {
            continue;
        }
        $date = $parts[1];
        $data = json_decode($r->value, true);
        if (! is_array($data) || empty($data['image']) || empty($data['paper'])) {
            continue;
        }
        if (! isset($byDate[$date])) {
            $byDate[$date] = [];
        }
        $byDate[$date][] = ['paper' => $data['paper'], 'image' => $data['image']];
    }

    krsort($byDate);
    $latest = array_key_first($byDate);

    return Inertia\Inertia::render('Kiosk', [
        'latest' => $latest,
        'items'  => $latest ? $byDate[$latest] : [],
    ]);
})->name('kiosk');

Route::get('/newspapers/{slug}', function ($slug) {
    $paper = \App\Support\NewspaperCatalog::paper($slug);

    abort_if(! $paper, 404, 'روزنامه مورد نظر پیدا نشد.');

    return Inertia\Inertia::render('NewspaperDetail', [
        'paper'  => $paper,
        'issues' => $paper['issues'],
    ]);
})->name('newspapers.show');

Route::get('/about', fn () => Inertia\Inertia::render('About'))->name('about');

// The hero slot in the database links here, so it must resolve.
Route::get('/about/{slug}', function ($slug) {
    $page = DB::table('static_pages')->where('slug', $slug)->first();

    abort_if(! $page, 404, 'صفحه مورد نظر پیدا نشد.');

    return Inertia\Inertia::render('StaticPage', [
        'page' => [
            'title'   => $page->title,
            'content' => $page->content,
            'excerpt' => $page->excerpt,
            'image'   => $page->featuredImage,
        ],
    ]);
})->name('about.slug');

// Legal pages referenced from the footer.
foreach (['privacy' => 'حریم خصوصی', 'terms' => 'قوانین و مقررات'] as $slug => $fallbackTitle) {
    Route::get('/'.$slug, function () use ($slug, $fallbackTitle) {
        $page = DB::table('static_pages')->where('slug', $slug)->first();

        $content = $page->content ?? null;

        if (! $content) {
            abort(404, 'این صفحه هنوز تکمیل نشده است.');
        }

        return Inertia\Inertia::render('StaticPage', [
            'page' => [
                'title'   => $page->title ?: $fallbackTitle,
                'content' => $content,
                'excerpt' => $page->excerpt,
                'image'   => $page->featuredImage,
            ],
        ]);
    })->name($slug);
}

// RSS feed for readers - referenced from the footer.
Route::get('/api/rss/feed', function () {
    $siteUrl = rtrim((string) config('app.url'), '/');
    $siteName = 'پایگاه خبری تحلیلی لیان دید';
    $siteDesc = 'اخبار لحظه‌ای استان بوشهر و ایران';

    $esc = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    $articles = DB::table('articles')
        ->leftJoin('categories', 'articles.categoryId', '=', 'categories.id')
        ->where('articles.status', 'PUBLISHED')
        ->orderByDesc('articles.publishedAt')
        ->limit(50)
        ->get(['articles.title', 'articles.slug', 'articles.excerpt', 'articles.publishedAt', 'articles.created_at', 'categories.name as categoryName']);

    $items = '';
    foreach ($articles as $a) {
        $pub = $a->publishedAt ?: $a->created_at;
        $link = $siteUrl.'/news/'.$a->slug;
        $cats = $a->categoryName ? '<category>'.$esc($a->categoryName).'</category>' : '';
        $items .= "    <item>\n"
            .'      <title>'.$esc($a->title)."</title>\n"
            .'      <link>'.$esc($link)."</link>\n"
            .'      <guid isPermaLink="true">'.$esc($link)."</guid>\n"
            .($pub ? '      <pubDate>'.date('D, d M Y H:i:s O', strtotime($pub))."</pubDate>\n" : '')
            .($a->excerpt ? '      <description>'.$esc(strip_tags($a->excerpt))."</description>\n" : '')
            .($cats ? '      '.$cats."\n" : '')
            ."    </item>\n";
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<rss version="2.0">'."\n"
        .'  <channel>'."\n"
        .'    <title>'.$esc($siteName)."</title>\n"
        .'    <link>'.$esc($siteUrl)."</link>\n"
        .'    <description>'.$esc($siteDesc)."</description>\n"
        .'    <language>fa</language>'."\n"
        .$items
        ."  </channel>\n"
        .'</rss>';

    return response($xml, 200, [
        'Content-Type' => 'application/rss+xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=600',
    ]);
})->name('rss.feed');

Route::get('/contact', fn () => Inertia\Inertia::render('Contact'))->name('contact');

Route::post('/contact', function (Illuminate\Http\Request $request) {
    $v = $request->validate([
        'name'    => 'required|string|max:120',
        'email'   => 'required|email|max:180',
        'phone'   => 'nullable|string|max:40',
        'subject' => 'nullable|string|max:180',
        'message' => 'required|string|max:4000',
    ]);

    \App\Support\SettingStore::set('contact.last_message', json_encode([
        'at'      => now()->toIso8601String(),
        'data'    => $v,
    ], JSON_UNESCAPED_UNICODE));

    return back()->with('success', 'پیام شما دریافت شد.');
})->name('contact.submit');

Route::get('/search', function (Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));

    $articles = $q === '' ? [] : ArticleFormatter::collection(
        DB::table('articles')
            ->where('status', 'PUBLISHED')
            ->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('excerpt', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            })
            ->orderByDesc('publishedAt')
            ->limit(50)
            ->get()
    );

    return Inertia\Inertia::render('Search', [
        'articles'   => $articles,
        'categories' => DB::table('categories')->orderBy('order')->get(),
        'q'          => $q,
    ]);
})->name('search');

Route::get('/category/bushahr/cities', function () {
    return Inertia\Inertia::render('CityMap', [
        'news' => \App\Support\CityNews::all(),
    ]);
})->name('cities.map');

Route::get('/regions', fn () => redirect()->route('cities.map'));

Route::get('/gallery', function () {
    return Inertia\Inertia::render('Gallery', [
        'albums' => \App\Support\GalleryCatalog::albums(),
    ]);
})->name('gallery');

Route::get('/gallery/videos', function () {
    return Inertia\Inertia::render('GalleryVideos', [
        'videos' => \App\Support\GalleryCatalog::videos(),
    ]);
})->name('gallery.videos');

Route::get('/gallery/{slug}', function ($slug) {
    $album = \App\Support\GalleryCatalog::album($slug);

    abort_if(! $album, 404, 'آلبوم مورد نظر پیدا نشد.');

    return Inertia\Inertia::render('GalleryAlbum', [
        'album' => $album,
        'items' => \App\Support\GalleryCatalog::items($album['id']),
    ]);
})->name('gallery.album');

Route::get('/static/{slug}', function ($slug) {
    $page = DB::table('static_pages')->where('slug', $slug)->first();

    abort_if(! $page, 404, 'صفحه مورد نظر پیدا نشد.');

    return Inertia\Inertia::render('StaticPage', [
        'page' => [
            'title'   => $page->title,
            'content' => $page->content,
            'excerpt' => $page->excerpt,
            'image'   => $page->featuredImage,
        ],
    ]);
})->name('static.show');

// ── کرون (هاست اشتراکی) ──
$c = \App\Http\Controllers\CronController::class;
Route::get('/_cron/rss', [$c, 'rss'])->name('cron.rss');
Route::get('/_cron/backup', [$c, 'backup'])->name('cron.backup');
Route::get('/_cron/health', [$c, 'health'])->name('cron.health');
