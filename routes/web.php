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
    Route::get('/gallery', [$m, 'gallery'])->name('admin.gallery');
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
    Route::post('/newspapers', [$m, 'storeNewspaper'])->name('admin.newspapers.store');
    Route::put('/newspapers/{id}', [$m, 'updateNewspaper'])->name('admin.newspapers.update');
    Route::delete('/newspapers/{id}', [$m, 'destroyNewspaper'])->name('admin.newspapers.destroy');
    Route::post('/newspapers/sources', [$m, 'storeNewspaperSource'])->name('admin.newspapers.sources.store');
    Route::delete('/newspapers/sources/{id}', [$m, 'destroyNewspaperSource'])->name('admin.newspapers.sources.destroy');
    Route::delete('/newspapers/issues/{id}', [$m, 'destroyNewspaperIssue'])->name('admin.newspapers.issues.destroy');

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
    Route::get('/change-password', fn () => Inertia\Inertia::render('Admin/ChangePassword'))->name('admin.change-password');

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
Route::get('/gallery', fn () => Inertia\Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/about', fn () => Inertia\Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/contact', fn () => Inertia\Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/search', fn () => Inertia\Inertia::render('Home', ['articles' => [], 'categories' => []]));
