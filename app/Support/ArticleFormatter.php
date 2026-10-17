<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Normalises an article row (either a DB row or Eloquent model) into the
 * shape the Inertia pages expect.
 */
class ArticleFormatter
{
    public static function make($item, bool $withContent = false): ?array
    {
        if (! $item) return null;

        $arr = $item instanceof stdClass ? (array) $item : (array) $item;

        $categoryId = $arr['categoryId'] ?? null;
        $category = $categoryId
            ? DB::table('categories')->where('id', $categoryId)->first()
            : null;

        $record = [
            'id'            => (string) ($arr['id'] ?? ''),
            'title'         => $arr['title'] ?? '',
            'slug'          => $arr['slug'] ?? '',
            'subtitle'      => $arr['subtitle'] ?? null,
            'excerpt'       => $arr['excerpt'] ?? '',
            'featuredImage' => $arr['featuredImage'] ?? null,
            'status'        => $arr['status'] ?? 'DRAFT',
            'viewCount'     => (int) ($arr['viewCount'] ?? 0),
            'isFeatured'    => (bool) ($arr['isFeatured'] ?? false),
            'isBreaking'    => (bool) ($arr['isBreaking'] ?? false),
            'isPinned'      => (bool) ($arr['isPinned'] ?? false),
            'placement'     => $arr['placement'] ?? 'latest',
            'region'        => $arr['region'] ?? null,
            'source'        => $arr['source'] ?? null,
            'sourceUrl'     => $arr['sourceUrl'] ?? null,
            'metaTitle'     => $arr['metaTitle'] ?? null,
            'metaDesc'      => $arr['metaDesc'] ?? null,
            'metaKeywords'  => $arr['metaKeywords'] ?? null,
            'publishedAt'   => $arr['publishedAt'] ?? null,
            'createdAt'     => $arr['created_at'] ?? $arr['createdAt'] ?? null,
            'category'      => $category
                ? [
                    'id'    => $category->id,
                    'name'  => $category->name,
                    'slug'  => $category->slug,
                    'icon'  => $category->icon,
                    'color' => $category->color,
                ]
                : [
                    'id' => null, 'name' => 'عمومی', 'slug' => 'general',
                    'icon' => '📰', 'color' => '#1B365D',
                ],
            'author'        => ['name' => 'تحریریه لیان دید'],
        ];

        if ($withContent) {
            $record['content'] = $arr['content'] ?? '';
        }

        return $record;
    }

    /**
     * @param  iterable  $items
     */
    public static function collection($items, bool $withContent = false)
    {
        return collect($items)
            ->map(fn ($i) => self::make($i, $withContent))
            ->values();
    }
}
