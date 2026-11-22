<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\SettingStore;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /** Columns that exist on the articles table, whichever naming convention is in use. */
    private function articleColumns(): array
    {
        return Schema::getColumnListing('articles');
    }

    /**
     * Build a URL-safe slug that keeps Persian letters (Str::slug strips them).
     */
    private function makeSlug(?string $title, ?string $ignoreId = null): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $title ?? '') ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'news';
        }
        $slug = mb_substr($slug, 0, 200);

        $base = $slug;
        $i = 1;
        while (true) {
            $exists = DB::table('articles')
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if (! $exists) break;
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /** CUID-like 25 character id, matching the ids imported from the Next.js version. */
    private function newId(array $columns): string
    {
        $id = 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22);
        while (DB::table('articles')->where('id', $id)->exists()) {
            $id = 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22);
        }
        return $id;
    }

    private function payload(Request $request, array $columns): array
    {
        $data = [
            'title'         => (string) $request->input('title'),
            'subtitle'      => $request->input('subtitle'),
            'excerpt'       => $request->input('excerpt', ''),
            'content'       => (string) $request->input('content'),
            'status'        => $request->input('status', 'PUBLISHED'),
            'featuredImage' => $request->input('featuredImage') ?: null,
            'source'        => $request->input('source'),
            'sourceUrl'     => $request->input('sourceUrl'),
            'metaTitle'     => $request->input('metaTitle'),
            'metaDesc'      => $request->input('metaDesc'),
            'metaKeywords'  => $request->input('metaKeywords'),
            'region'        => $request->input('region'),
            'placement'     => $request->input('placement', 'latest'),
            'isFeatured'    => $request->boolean('isFeatured') ? 1 : 0,
            'isBreaking'    => $request->boolean('isBreaking') ? 1 : 0,
            'isPinned'      => $request->boolean('isPinned') ? 1 : 0,
        ];

        if (in_array('viewCount', $columns, true))  $data['viewCount'] = 0;
        if (in_array('view_count', $columns, true)) $data['view_count'] = 0;

        if (in_array('categoryId', $columns, true))  $data['categoryId'] = $request->input('categoryId');
        if (in_array('category_id', $columns, true)) $data['category_id'] = $request->input('categoryId');

        if (in_array('publishedAt', $columns, true)) {
            $data['publishedAt'] = $request->input('publishedAt') ?: now();
        }
        if (in_array('published_at', $columns, true)) {
            $data['published_at'] = $request->input('publishedAt') ?: now();
        }

        return array_filter($data, fn ($v, $k) => in_array($k, $columns, true), ARRAY_FILTER_USE_BOTH);
    }

    // ------------------------------------------------------------------ داشبورد
    public function dashboard(): Response
    {
        $columns = $this->articleColumns();

        $articles = DB::table('articles')->orderByDesc('created_at')->get()->map(function ($item) {
            $catId = $item->categoryId ?? null;
            $category = $catId
                ? DB::table('categories')->where('id', $catId)->first()
                : null;

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
                'categoryId'   => $catId,
                'categoryName' => $category?->name ?? 'عمومی',
                'publishedAt'  => $item->publishedAt,
            ];
        })->values();

        $categories = DB::table('categories')->orderBy('order')->get();

        $stats = [
            'totalArticles'   => DB::table('articles')->count(),
            'totalViews'      => DB::table('articles')->sum('viewCount'),
            'totalCategories' => $categories->count(),
            'totalUsers'      => DB::table('users')->count(),
            'totalExternal'   => DB::table('external_news')->count(),
            'totalComments'   => DB::table('comments')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'articles'   => $articles,
            'categories' => $categories,
            'stats'      => $stats,
        ]);
    }

    // ------------------------------------------------------------------ ایجاد
    public function storeArticle(Request $request)
    {
        $request->validate([
            'title'      => 'nullable|string|max:255',
            'content'    => 'nullable|string',
            'categoryId' => 'nullable',
            'excerpt'    => 'nullable|string',
        ]);

        $columns = $this->articleColumns();

        $data = $this->payload($request, $columns);
        $data['id']   = $this->newId($columns);
        $data['slug'] = $this->makeSlug($request->input('title'));

        // authorId is NOT NULL in the schema and must reference an existing user
        $authorId = $request->user()?->id
            ?? DB::table('users')->where('role', 'ADMIN')->value('id');

        abort_if(! $authorId, 403, 'کاربر جاری معتبر نیست.');

        if (in_array('authorId', $columns, true))  $data['authorId'] = $authorId;
        if (in_array('author_id', $columns, true)) $data['author_id'] = $authorId;

        foreach (['created_at', 'updated_at', 'createdAt', 'updatedAt'] as $tc) {
            if (in_array($tc, $columns, true)) $data[$tc] = now();
        }

        DB::table('articles')->insert($data);

        return back()->with('success', 'خبر با موفقیت منتشر شد.');
    }

    // ------------------------------------------------------------------ ویرایش
    public function updateArticle(Request $request, $id)
    {
        $request->validate([
            'title'      => 'nullable|string|max:255',
            'content'    => 'nullable|string',
            'categoryId' => 'nullable',
        ]);

        $article = DB::table('articles')->where('id', $id)->first();
        abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

        $columns = $this->articleColumns();
        $data = $this->payload($request, $columns);
        $data['slug'] = $this->makeSlug($request->input('title'), $id);

        foreach (['updated_at', 'updatedAt'] as $tc) {
            if (in_array($tc, $columns, true)) $data[$tc] = now();
        }

        DB::table('articles')->where('id', $id)->update($data);

        return back()->with('success', 'تغییرات خبر ذخیره شد.');
    }

    // ------------------------------------------------------------------ کپی
    public function duplicateArticle($id)
    {
        $columns = $this->articleColumns();
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

        $data = [];
        foreach ($columns as $col) {
            if (property_exists($article, $col)) {
                $data[$col] = $article->{$col};
            }
        }

        $data['id'] = $this->newId($columns);

        $title = trim(((string) ($article->title ?? '')) . ' (کپی)');
        $data['title'] = mb_substr($title, 0, 191);
        $data['slug']  = $this->makeSlug($data['title']);

        if (in_array('status', $columns, true))     $data['status'] = 'DRAFT';
        if (in_array('viewCount', $columns, true))  $data['viewCount'] = 0;
        if (in_array('view_count', $columns, true)) $data['view_count'] = 0;
        if (in_array('publishedAt', $columns, true))  $data['publishedAt'] = null;
        if (in_array('published_at', $columns, true)) $data['published_at'] = null;
        if (in_array('placement', $columns, true))    $data['placement'] = 'latest';
        if (in_array('isFeatured', $columns, true))   $data['isFeatured'] = 0;
        if (in_array('isBreaking', $columns, true))   $data['isBreaking'] = 0;
        if (in_array('isPinned', $columns, true))     $data['isPinned'] = 0;

        foreach (['created_at', 'updated_at', 'createdAt', 'updatedAt'] as $tc) {
            if (in_array($tc, $columns, true)) $data[$tc] = now();
        }

        DB::table('articles')->insert($data);

        return redirect('/admin/articles?edit=' . $data['id'])
            ->with('success', 'خبر کپی شد؛ نسخهٔ جدید برای ویرایش باز شد.');
    }

    // ------------------------------------------------------------------ وضعیت انتشار
    public function setArticleStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
        ]);

        $article = DB::table('articles')->where('id', $id)->first();
        abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

        $columns = $this->articleColumns();
        $update = ['status' => $data['status']];

        foreach (['updated_at', 'updatedAt'] as $tc) {
            if (in_array($tc, $columns, true)) $update[$tc] = now();
        }

        DB::table('articles')->where('id', $id)->update($update);

        return back()->with('success', $data['status'] === 'DRAFT'
            ? 'خبر از انتشار خارج و به پیش‌نویس منتقل شد.'
            : 'وضعیت خبر به‌روزرسانی شد.');
    }

    // ------------------------------------------------------------------ لینک کوتاه
    public function articleShortlink($id)
    {
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

        $url = url('/news/' . $article->slug);

        foreach (DB::table('site_settings')->where('key', 'like', 'short.%')->get() as $row) {
            $v = json_decode((string) $row->value, true) ?: [];
            if (($v['url'] ?? null) === $url) {
                $code = substr($row->key, 6);
                return response()->json(['code' => $code, 'url' => url('/s/' . $code), 'created' => false]);
            }
        }

        do {
            $code = strtolower(Str::random(6));
        } while (DB::table('site_settings')->where('key', 'short.' . $code)->exists());

        SettingStore::set('short.' . $code, json_encode(['url' => $url, 'hits' => 0], JSON_UNESCAPED_UNICODE));

        return response()->json(['code' => $code, 'url' => url('/s/' . $code), 'created' => true]);
    }

    // ------------------------------------------------------------------ حذف
    public function destroyArticle($id)
    {
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if(! $article, 404, 'خبر مورد نظر یافت نشد.');

        DB::table('articles')->where('id', $id)->delete();

        return back()->with('success', 'خبر با موفقیت حذف گردید.');
    }
}
