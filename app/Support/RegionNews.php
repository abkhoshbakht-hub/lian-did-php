<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Bushehr county map data for the public regions page. */
class RegionNews
{
    /** News items tagged with a Bushehr county, newest first. */
    public static function countyNews(int $limit = 200): array
    {
        $rows = DB::table('external_news')
            ->where('status', 'APPROVED')
            ->whereNotNull('region')
            ->where('region', '<>', '')
            ->orderByDesc('publishedAt')
            ->limit($limit)
            ->get(['id', 'title', 'link', 'sourceName', 'category', 'region', 'publishedAt']);

        return $rows->map(fn ($n) => [
            'id'          => (string) $n->id,
            'title'       => $n->title,
            'link'        => $n->link ?: '#',
            'sourceName'  => $n->sourceName,
            'category'    => $n->category,
            'region'      => $n->region,
            'publishedAt' => $n->publishedAt,
        ])->all();
    }

    /** @return array<string,int> */
    public static function counts(): array
    {
        $out = [];
        foreach (DB::table('external_news')
            ->where('status', 'APPROVED')
            ->whereNotNull('region')
            ->where('region', '<>', '')
            ->select('region', DB::raw('COUNT(*) AS n'))
            ->groupBy('region')
            ->get() as $r) {
            $out[$r->region] = (int) $r->n;
        }

        return $out;
    }
}