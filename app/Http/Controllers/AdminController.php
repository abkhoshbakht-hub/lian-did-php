<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminController extends Controller
{
    // داشبورد اصلی ادمین با آمار کامل و مدیریت محتوا
    public function dashboard()
    {
        $articles = DB::table('articles')->orderBy('id', 'desc')->get()->map(function ($item) {
            $arr = (array) $item;
            $catId = $arr['categoryId'] ?? $arr['category_id'] ?? null;
            $category = $catId ? DB::table('categories')->where('id', $catId)->first() : null;
            
            return [
                'id'            => (string) $arr['id'],
                'title'         => $arr['title'],
                'slug'          => $arr['slug'],
                'status'        => $arr['status'] ?? 'PUBLISHED',
                'viewCount'     => (int) ($arr['viewCount'] ?? $arr['view_count'] ?? 0),
                'isBreaking'    => (bool) ($arr['isBreaking'] ?? $arr['is_breaking'] ?? false),
                'isFeatured'    => (bool) ($arr['isFeatured'] ?? $arr['is_featured'] ?? false),
                'categoryName'  => $category ? $category->name : 'عمومی',
                'publishedAt'   => $arr['publishedAt'] ?? $arr['published_at'] ?? now()->toIso8601String(),
            ];
        })->values();

        $categories = DB::table('categories')->orderBy('order')->get();
        
        $columns = Schema::getColumnListing('articles');
        $viewCol = in_array('viewCount', $columns) ? 'viewCount' : (in_array('view_count', $columns) ? 'view_count' : null);
        $totalViews = $viewCol ? DB::table('articles')->sum($viewCol) : 0;

        $stats = [
            'totalArticles' => DB::table('articles')->count(),
            'totalViews'    => $totalViews,
            'totalCategories' => $categories->count(),
            'totalUsers'    => Schema::hasTable('users') ? DB::table('users')->count() : 1,
        ];

        return Inertia::render('Admin/Dashboard', [
            'articles'   => $articles,
            'categories' => $categories,
            'stats'      => $stats,
        ]);
    }

    // ایجاد و انتشار خبر جدید با جزئیات کامل
    public function storeArticle(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string',
            'content'     => 'required|string',
            'categoryId'  => 'required',
        ]);

        $articleCols = Schema::getColumnListing('articles');
        $maxId = DB::table('articles')->max('id');
        $newId = $maxId ? (string) (intval($maxId) + 1) : '1';

        $data = [
            'id'            => $newId,
            'title'         => $request->input('title'),
            'slug'          => Str::slug($request->input('title')) . '-' . rand(1000, 9999),
            'subtitle'      => $request->input('subtitle'),
            'excerpt'       => $request->input('excerpt', ''),
            'content'       => $request->input('content'),
            'status'        => $request->input('status', 'PUBLISHED'),
            'featuredImage' => $request->input('featuredImage', 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=1200&q=80'),
            'isFeatured'    => $request->boolean('isFeatured'),
            'isBreaking'    => $request->boolean('isBreaking'),
            'placement'     => $request->input('placement', 'latest'),
        ];

        if (in_array('viewCount', $articleCols)) $data['viewCount'] = 0;
        if (in_array('view_count', $articleCols)) $data['view_count'] = 0;

        if (in_array('categoryId', $articleCols)) $data['categoryId'] = $request->input('categoryId');
        if (in_array('category_id', $articleCols)) $data['category_id'] = $request->input('categoryId');

        foreach (['createdAt', 'created_at', 'updatedAt', 'updated_at'] as $tc) {
            if (in_array($tc, $articleCols)) $data[$tc] = now();
        }

        DB::table('articles')->insert($data);

        return redirect()->back()->with('success', 'خبر با موفقیت منتشر شد.');
    }

    // حذف خبر
    public function destroyArticle($id)
    {
        DB::table('articles')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'خبر با موفقیت حذف گردید.');
    }
}