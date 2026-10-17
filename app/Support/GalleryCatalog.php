<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Reads the gallery albums and their items. */
class GalleryCatalog
{
    /** @return array<int,array> */
    public static function albums(): array
    {
        $counts = DB::table('gallery_items')
            ->where('isActive', 1)
            ->select('albumId', DB::raw('COUNT(*) AS n'))
            ->groupBy('albumId')
            ->pluck('n', 'albumId');

        return DB::table('gallery_albums')
            ->where('isActive', 1)
            ->orderBy('displayOrder')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'slug', 'category', 'description', 'coverImage', 'color', 'eventDate'])
            ->map(fn ($a) => [
                'id'          => (string) $a->id,
                'title'       => $a->title,
                'slug'        => $a->slug,
                'category'    => $a->category,
                'description' => $a->description,
                'cover'       => self::url($a->coverImage),
                'color'       => $a->color ?: 'from-blue-500 to-blue-700',
                'date'        => $a->eventDate,
                'itemCount'   => (int) ($counts[$a->id] ?? 0),
                'link'        => '/gallery/'.$a->slug,
            ])
            ->all();
    }

    /** @return array|null */
    public static function album(string $slug): ?array
    {
        $a = DB::table('gallery_albums')->where('slug', $slug)->where('isActive', 1)->first();

        if (! $a) {
            return null;
        }

        return [
            'id'          => (string) $a->id,
            'title'       => $a->title,
            'slug'        => $a->slug,
            'category'    => $a->category,
            'description' => $a->description,
            'cover'       => self::url($a->coverImage),
            'color'       => $a->color ?: 'from-blue-500 to-blue-700',
            'date'        => $a->eventDate,
            'itemCount'   => DB::table('gallery_items')->where('albumId', $a->id)->where('isActive', 1)->count(),
        ];
    }

    /** @return array<int,array> */
    public static function items(string $albumId): array
    {
        return DB::table('gallery_items')
            ->where('albumId', $albumId)
            ->where('isActive', 1)
            ->orderBy('displayOrder')
            ->orderBy('created_at')
            ->get(['id', 'kind', 'url', 'thumbnail', 'title', 'videoUrl'])
            ->map(fn ($i) => [
                'id'        => (string) $i->id,
                'kind'      => $i->kind ?: 'image',
                'url'       => self::url($i->url),
                'thumbnail' => self::url($i->thumbnail),
                'title'     => $i->title,
                'videoUrl'  => $i->videoUrl ?: self::url($i->url),
            ])
            ->all();
    }

    /** @return array<int,array> */
    public static function videos(int $limit = 60): array
    {
        return DB::table('gallery_items')
            ->leftJoin('gallery_albums', 'gallery_items.albumId', '=', 'gallery_albums.id')
            ->where('gallery_items.kind', 'video')
            ->where('gallery_items.isActive', 1)
            ->orderByDesc('gallery_items.created_at')
            ->limit($limit)
            ->get([
                'gallery_items.id', 'gallery_items.url', 'gallery_items.thumbnail',
                'gallery_items.title', 'gallery_items.videoUrl',
                'gallery_albums.title as albumTitle',
            ])
            ->map(fn ($v) => [
                'id'        => (string) $v->id,
                'url'       => self::url($v->url),
                'thumbnail' => self::url($v->thumbnail),
                'title'     => $v->title ?: $v->albumTitle,
                'albumTitle' => $v->albumTitle,
                'videoUrl'  => $v->videoUrl ?: self::url($v->url),
            ])
            ->all();
    }

    private static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (preg_match('~^(https?:)?//~i', $path)) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }
}