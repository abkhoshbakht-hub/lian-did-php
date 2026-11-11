<?php

namespace App\Http\Controllers\Admin;

use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SectionController
{
    // ---------------------------------------------------------------- داشبورد
    public function dashboard(): Response
    {
        $articles = DB::table('articles')->orderByDesc('created_at')->get()->map(function ($item) {
            $catId = $item->categoryId ?? null;
            $category = $catId ? DB::table('categories')->where('id', $catId)->first() : null;

            return [
                'id'           => (string) $item->id,
                'title'        => $item->title,
                'slug'         => $item->slug,
                'subtitle'     => $item->subtitle,
                'excerpt'      => $item->excerpt,
                'content'      => $item->content,
                'featuredImage' => $item->featuredImage,
                'status'       => $item->status,
                'viewCount'    => (int) $item->viewCount,
                'isBreaking'   => (bool) $item->isBreaking,
                'isFeatured'   => (bool) $item->isFeatured,
                'isPinned'     => (bool) $item->isPinned,
                'placement'    => $item->placement,
                'source'       => $item->source,
                'sourceUrl'    => $item->sourceUrl,
                'metaTitle'    => $item->metaTitle,
                'metaDesc'     => $item->metaDesc,
                'metaKeywords' => $item->metaKeywords,
                'region'       => $item->region,
                'categoryId'   => $catId,
                'categoryName' => $category?->name ?? 'عمومی',
                'publishedAt'  => $item->publishedAt,
            ];
        })->values();

        return Inertia::render('Admin/Dashboard', [
            'articles'   => $articles,
            'categories' => DB::table('categories')->orderBy('order')->get(),
            'stats'      => [
                'totalArticles'   => DB::table('articles')->count(),
                'totalViews'      => (int) DB::table('articles')->sum('viewCount'),
                'totalCategories' => DB::table('categories')->count(),
                'totalUsers'      => DB::table('users')->count(),
                'totalExternal'   => DB::table('external_news')->count(),
                'totalComments'   => DB::table('comments')->count(),
                'totalSubscribers' => DB::table('subscribers')->count(),
                'totalNewspapers' => DB::table('newspapers')->count(),
                'pendingSubmissions' => DB::table('user_submissions')->where('status', 'PENDING')->count(),
            ],
        ]);
    }

    // ---------------------------------------------------------------- اخبار
    public function articles(Request $request): Response
    {
        $q = DB::table('articles');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('cat')) {
            $category = DB::table('categories')->where('slug', $request->string('cat'))->first();
            $q->where('categoryId', $category?->id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('slug', 'like', $term));
        }

        $articles = $q->orderByDesc('created_at')->get()->map(fn ($item) => $this->articleRow($item))->values();

        return Inertia::render('Admin/Articles', [
            'articles'   => $articles,
            'categories' => DB::table('categories')->orderBy('order')->get(),
            'new'        => $request->boolean('new') || $request->route('new') === '1',
            'filters'    => [
                'status' => $request->string('status')->toString(),
                'cat'    => $request->string('cat')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'editing'    => $request->string('edit')->toString()
                ? DB::table('articles')->where('id', $request->string('edit'))->first()
                : null,
        ]);
    }

    // ---------------------------------------------------------------- دسته‌بندی
    public function categories(): Response
    {
        $counts = DB::table('articles')
            ->select('categoryId', DB::raw('COUNT(*) AS n'))
            ->whereNotNull('categoryId')
            ->groupBy('categoryId')
            ->pluck('n', 'categoryId');

        $rows = DB::table('categories')
            ->orderBy('order')
            ->get()
            ->map(fn ($c) => (array) $c + ['articles_count' => (int) ($counts[$c->id] ?? 0)]);

        return Inertia::render('Admin/Categories', ['categories' => $rows]);
    }

    // ---------------------------------------------------------------- شهرها
    public function regions(): Response
    {
        $rows = DB::table('external_news')
            ->whereNotNull('region')
            ->where('region', '<>', '')
            ->select('region', DB::raw('COUNT(*) AS n'))
            ->groupBy('region')
            ->orderByDesc('n')
            ->get();

        $regions = $rows->map(function ($r) {
            [$county, $kind] = array_pad(explode(':', (string) $r->region, 2), 2, 'city');
            return [
                'region'  => $r->region,
                'county'  => $county,
                'kind'    => $kind,
                'count'   => (int) $r->n,
                'label'   => $this->countyName($county) . ' / ' . ($kind === 'village' ? 'روستا' : 'شهر'),
            ];
        })->values();

        return Inertia::render('Admin/Regions', [
            'regions'   => $regions,
            'counties'  => \App\Support\BushehrCounties::all(),
        ]);
    }

    private function countyName(string $key): string
    {
        return collect(\App\Support\BushehrCounties::all())
            ->firstWhere('key', $key)['name'] ?? $key;
    }

    // ---------------------------------------------------------------- نظرات
    public function comments(): Response
    {
        $titles = DB::table('articles')->pluck('title', 'id');

        $rows = DB::table('comments')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) use ($titles) {
                return (array) $c + [
                    'article_title' => $titles[$c->articleId] ?? '—',
                    'approved'      => (bool) $c->approved,
                ];
            });

        return Inertia::render('Admin/Comments', ['comments' => $rows]);
    }

    // ---------------------------------------------------------------- خبرنامه
    public function subscribers(): Response
    {
        return Inertia::render('Admin/Subscribers', [
            'subscribers' => DB::table('subscribers')->orderByDesc('created_at')->get(),
        ]);
    }

    // ---------------------------------------------------------------- کاربران
    public function users(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => DB::table('users')->orderByDesc('created_at')->get()
                ->map(fn ($u) => (array) $u + ['isActive' => (bool) $u->isActive]),
        ]);
    }

    // ---------------------------------------------------------------- تنظیم عکس
    public function imageSettings(): Response
    {
        return Inertia::render('Admin/ImageSettings', [
            'settings' => SettingStore::imageSettings(),
        ]);
    }

    public function saveImageSettings(Request $request)
    {
        $validated = $request->validate([
            'image_max_mb'    => 'nullable|numeric|min:0.5|max:20',
            'image_quality'   => 'nullable|integer|min:10|max:100',
            'image_max_dim'   => 'nullable|integer|min:320|max:4000',
            'image_aspect'    => 'nullable|in:16:9,4:3,1:1,free',
            'image_format'    => 'nullable|in:webp,jpeg,png,original',
        ]);

        SettingStore::setMany($validated);

        return back()->with('success', 'تنظیمات عکس ذخیره شد.');
    }

    // ---------------------------------------------------------------- رسانه
    public function media(): Response
    {
        $files = [];
        foreach (['public/images', 'storage/app/public'] as $dir) {
            $path = base_path($dir);
            if (! is_dir($path)) continue;
            foreach (scandir($path) ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $path . '/' . $f;
                if (! is_file($full)) continue;
                $files[] = [
                    'name' => $f,
                    'dir'  => $dir,
                    'url'  => str_starts_with($dir, 'public')
                        ? '/' . str_replace('public/', '', $dir) . '/' . $f
                        : '/storage/' . $f,
                    'size' => $this->humanSize(filesize($full)),
                    'time' => date('Y/m/d H:i', filemtime($full)),
                ];
            }
        }

        return Inertia::render('Admin/Media', [
            'files'  => $files,
            'maxMb'  => SettingStore::get('image_max_mb', '1'),
            'format' => SettingStore::get('image_format', 'webp'),
        ]);
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $u;
            $bytes /= 1024;
        }
        return $bytes . ' TB';
    }

    // ---------------------------------------------------------------- تنظیمات متنی
    public function settingsPage(string $group): Response
    {
        return Inertia::render('Admin/Settings', [
            'group'    => $group,
            'title'    => \App\Support\AdminSections::settingsTitle($group),
            'settings' => SettingStore::group($group),
        ]);
    }
    public function saveSettings(Request $request, string $group)
    {
        SettingStore::setGroup($group, $request->except(['_token']));

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }

    private function articleRow($item): array
    {
        $catId = $item->categoryId ?? null;
        $category = $catId ? DB::table('categories')->where('id', $catId)->first() : null;

        return [
            'id'           => (string) $item->id,
            'title'        => $item->title,
            'slug'         => $item->slug,
            'subtitle'     => $item->subtitle,
            'excerpt'      => $item->excerpt,
            'content'      => $item->content,
            'featuredImage' => $item->featuredImage,
            'status'       => $item->status,
            'viewCount'    => (int) $item->viewCount,
            'isBreaking'   => (bool) $item->isBreaking,
            'isFeatured'   => (bool) $item->isFeatured,
            'isPinned'     => (bool) $item->isPinned,
            'placement'    => $item->placement,
            'source'       => $item->source,
            'sourceUrl'    => $item->sourceUrl,
            'metaTitle'    => $item->metaTitle,
            'metaDesc'     => $item->metaDesc,
            'metaKeywords' => $item->metaKeywords,
            'region'       => $item->region,
            'categoryId'   => $catId,
            'categoryName' => $category?->name ?? 'عمومی',
            'publishedAt'  => $item->publishedAt,
        ];
    }
}
