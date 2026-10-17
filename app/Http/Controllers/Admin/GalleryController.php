<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $albums = DB::table('gallery_albums')->orderBy('displayOrder')->get()
            ->map(function ($a) {
                $items = DB::table('gallery_items')->where('albumId', $a->id)->get(['id', 'kind', 'url', 'thumbnail', 'title', 'isActive']);

                return [
                    'id'          => $a->id,
                    'title'       => $a->title,
                    'slug'        => $a->slug,
                    'category'    => $a->category,
                    'description' => $a->description,
                    'coverImage'  => $a->coverImage,
                    'color'       => $a->color,
                    'active'      => (bool) $a->isActive,
                    'displayOrder' => $a->displayOrder,
                    'itemCount'   => $items->count(),
                    'items'       => $items,
                ];
            });

        return Inertia::render('Admin/Gallery', [
            'albums' => $albums,
            'stats'  => [
                'albums' => DB::table('gallery_albums')->count(),
                'items'  => DB::table('gallery_items')->count(),
                'images' => DB::table('gallery_items')->where('kind', 'image')->count(),
                'videos' => DB::table('gallery_items')->where('kind', 'video')->count(),
            ],
        ]);
    }

    /**
 * Persian titles produce an empty slug, so fall back to a timestamp-based
 * handle that is still URL-safe and unique.
 */
private static function slugFor(string $title, ?string $provided = null): string
    {
        if ($provided) {
            $s = Str::slug($provided);

            if ($s !== '') {
                return $s;
            }
        }

        $s = Str::slug($title);
        if ($s !== '') {
            return $s;
        }

        return 'album-'.substr(md5($title.microtime(true)), 0, 8);
    }

    public function storeAlbum(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:128',
            'description'  => 'nullable|string',
            'color'        => 'nullable|string|max:64',
            'displayOrder' => 'nullable|integer',
            'isActive'     => 'nullable|boolean',
        ]);

        $slug = self::slugFor($data['title'], $data['slug'] ?? null);

        if (DB::table('gallery_albums')->where('slug', $slug)->exists()) {
            return back()->with('error', 'این نشانی قبلاً استفاده شده است.');
        }

        DB::table('gallery_albums')->insert([
            'id'           => 'gal'.Str::random(20),
            'title'        => $data['title'],
            'slug'         => $slug,
            'category'     => $data['category'] ?? null,
            'description'  => $data['description'] ?? null,
            'color'        => $data['color'] ?? 'from-blue-500 to-blue-700',
            'isActive'     => $request->boolean('isActive', true) ? 1 : 0,
            'displayOrder' => $data['displayOrder'] ?? (DB::table('gallery_albums')->max('displayOrder') + 1),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'آلبوم ساخته شد.');
    }

    public function updateAlbum(Request $request, string $id)
    {
        $album = DB::table('gallery_albums')->where('id', $id)->first();

        if (! $album) {
            return back()->with('error', 'آلبوم پیدا نشد.');
        }

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'slug'         => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:128',
            'description'  => 'nullable|string',
            'color'        => 'nullable|string|max:64',
            'displayOrder' => 'nullable|integer',
            'isActive'     => 'nullable|boolean',
        ]);

        if (! empty($data['slug'])) {
            $clash = DB::table('gallery_albums')->where('slug', $data['slug'])->where('id', '!=', $id)->exists();

            if ($clash) {
                return back()->with('error', 'این نشانی قبلاً استفاده شده است.');
            }
        }

        DB::table('gallery_albums')->where('id', $id)->update([
            'title'        => $data['title'] ?? $album->title,
            'slug'         => $data['slug'] ?? $album->slug,
            'category'     => array_key_exists('category', $data) ? $data['category'] : $album->category,
            'description'  => array_key_exists('description', $data) ? $data['description'] : $album->description,
            'color'        => $data['color'] ?? $album->color,
            'isActive'     => array_key_exists('isActive', $data) ? ($data['isActive'] ? 1 : 0) : $album->isActive,
            'displayOrder' => $data['displayOrder'] ?? $album->displayOrder,
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'آلبوم ویرایش شد.');
    }

    public function destroyAlbum(string $id)
    {
        $items = DB::table('gallery_items')->where('albumId', $id)->get(['url', 'thumbnail']);

        foreach ($items as $it) {
            foreach (array_filter([$it->url, $it->thumbnail]) as $path) {
                if ($path && ! preg_match('~^https?://~i', $path) && str_starts_with($path, 'gallery/')) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        }

        DB::table('gallery_items')->where('albumId', $id)->delete();
        DB::table('gallery_albums')->where('id', $id)->delete();

        return back()->with('success', 'آلبوم و تصاویر آن حذف شد.');
    }

    public function storeItem(Request $request, string $id): JsonResponse
    {
        if (! DB::table('gallery_albums')->where('id', $id)->exists()) {
            return response()->json(['message' => 'آلبوم پیدا نشد.'], 404);
        }

        $data = $request->validate([
            'kind'  => 'nullable|in:image,video',
            'title' => 'nullable|string|max:255',
            'url'   => 'nullable|string|max:1000',
            'file'  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
            'videoFile' => 'nullable|file|mimes:mp4,webm,mov',
        ]);

        $kind = $data['kind'] ?? 'image';
        $path = null;
        $videoUrl = $data['url'] ?? null;

        if ($request->hasFile('file')) {
            $res = ImageProcessor::process($request->file('file'), 'gallery');

            if (! $res['ok']) {
                return response()->json(['message' => $res['error']], 422);
            }
            $path = $res['path'];
        } elseif ($request->hasFile('videoFile')) {
            $file = $request->file('videoFile');
            $name = Str::random(24).'.'.$file->getClientOriginalExtension();
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('gallery');
            $file->storeAs('gallery', $name, 'public');
            $path = 'gallery/'.$name;
            $kind = 'video';
        } elseif (! empty($data['url'])) {
            $path = $data['url'];
        }

        if (! $path) {
            return response()->json(['message' => 'فایلی انتخاب نشده است.'], 422);
        }

        $id2 = 'git'.Str::random(20);
        DB::table('gallery_items')->insert([
            'id'           => $id2,
            'albumId'      => $id,
            'kind'         => $kind,
            'url'          => $path,
            'thumbnail'    => null,
            'title'        => $data['title'] ?? null,
            'videoUrl'     => $videoUrl,
            'displayOrder' => DB::table('gallery_items')->where('albumId', $id)->max('displayOrder') + 1,
            'isActive'     => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json(['id' => $id2, 'message' => 'افزوده شد.']);
    }

    public function destroyItem(string $id)
    {
        $item = DB::table('gallery_items')->where('id', $id)->first();

        if ($item) {
            foreach (array_filter([$item->url, $item->thumbnail]) as $path) {
                if ($path && ! preg_match('~^https?://~i', $path) && str_starts_with($path, 'gallery/')) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
            DB::table('gallery_items')->where('id', $id)->delete();
        }

        return back()->with('success', 'آیتم حذف شد.');
    }
}