<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AdminController;
use Inertia\Inertia;

function formatArticleRecord($item) {
    if (!$item) return null;
    $arr = (array) $item;
    
    $categoryId = $arr['categoryId'] ?? $arr['category_id'] ?? null;
    $category = $categoryId ? DB::table('categories')->where('id', $categoryId)->first() : null;

    return [
        'id'            => (string) $arr['id'],
        'title'         => $arr['title'],
        'slug'          => $arr['slug'],
        'subtitle'      => $arr['subtitle'] ?? null,
        'excerpt'       => $arr['excerpt'] ?? '',
        'content'       => $arr['content'] ?? '',
        'featuredImage' => $arr['featuredImage'] ?? $arr['featured_image'] ?? '/og-image.png',
        'status'        => $arr['status'] ?? 'PUBLISHED',
        'viewCount'     => (int) ($arr['viewCount'] ?? $arr['view_count'] ?? 0),
        'isFeatured'    => (bool) ($arr['isFeatured'] ?? $arr['is_featured'] ?? false),
        'isBreaking'    => (bool) ($arr['isBreaking'] ?? $arr['is_breaking'] ?? false),
        'placement'     => $arr['placement'] ?? 'latest',
        'publishedAt'   => $arr['publishedAt'] ?? $arr['published_at'] ?? now()->toIso8601String(),
        'category'      => $category ? ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug] : ['name' => 'اخبار', 'slug' => 'general'],
        'author'        => ['name' => 'تحریریه لیان دید'],
    ];
}

// صفحه اصلی سایت
Route::get('/', function () {
    $rawArticles = DB::table('articles')->where('status', 'PUBLISHED')->orderBy('id', 'desc')->get();
    $articles = $rawArticles->map(fn($item) => formatArticleRecord($item))->values();
    $categories = DB::table('categories')->orderBy('order')->get();

    return Inertia::render('Home', [
        'articles'   => $articles,
        'categories' => $categories,
    ]);
});

// صفحه تک‌خبر
Route::get('/news/{slug}', function ($slug) {
    $rawArticle = DB::table('articles')->where('slug', $slug)->first() ?? DB::table('articles')->first();
    if ($rawArticle) {
        $viewCol = property_exists($rawArticle, 'viewCount') ? 'viewCount' : 'view_count';
        DB::table('articles')->where('id', $rawArticle->id)->increment($viewCol);
    }
    return Inertia::render('Article', [
        'article' => formatArticleRecord($rawArticle),
    ]);
});

// صفحات دسته‌بندی منوها
Route::get('/category/{slug}/{subSlug?}', function ($slug, $subSlug = null) {
    $categories = DB::table('categories')->orderBy('order')->get();
    $rawArticles = DB::table('articles')->where('status', 'PUBLISHED')->orderBy('id', 'desc')->get();
    $articles = $rawArticles->map(fn($item) => formatArticleRecord($item))->values();
    
    $matchedCategory = DB::table('categories')->where('slug', $slug)->first();
    $categoryName = $matchedCategory ? $matchedCategory->name : 'بخش اخبار';

    return Inertia::render('Category', [
        'articles'     => $articles,
        'categories'   => $categories,
        'categoryName' => $categoryName,
    ]);
});

// پنل مدیریت حرفه‌ای ادمین
Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
Route::post('/admin/articles', [AdminController::class, 'storeArticle']);
Route::delete('/admin/articles/{id}', [AdminController::class, 'destroyArticle']);

// صفحات عمومی
Route::get('/gallery', fn() => Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/about', fn() => Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/contact', fn() => Inertia::render('Home', ['articles' => [], 'categories' => []]));
Route::get('/search', fn() => Inertia::render('Home', ['articles' => [], 'categories' => []]));