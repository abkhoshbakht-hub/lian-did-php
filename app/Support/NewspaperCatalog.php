<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Data helpers for the public "newspapers" section. */
class NewspaperCatalog
{
    public static function papers(): array
    {
        return DB::table('newspapers')
            ->where('active', 1)
            ->orderBy('displayOrder')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category', 'logo', 'website', 'telegram', 'description'])
            ->map(fn ($p) => [
                'id'          => (string) $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'category'    => $p->category,
                'logo'        => $p->logo,
                'website'     => $p->website,
                'telegram'    => $p->telegram,
                'description' => $p->description,
                'link'        => '/newspapers/'.$p->slug,
            ])
            ->all();
    }

    public static function paper(string $slug): ?array
    {
        $p = DB::table('newspapers')->where('slug', $slug)->where('active', 1)->first();

        if (! $p) {
            return null;
        }

        $issues = self::issues($p->id);

        return [
            'id'          => (string) $p->id,
            'name'        => $p->name,
            'slug'        => $p->slug,
            'category'    => $p->category,
            'logo'        => $p->logo,
            'website'     => $p->website,
            'telegram'    => $p->telegram,
            'description' => $p->description,
            'issues'      => $issues,
            'issueCount'  => count($issues),
        ];
    }

    /** Published issues for a paper, newest first. */
    public static function issues(string $paperId, int $limit = 60): array
    {
        return DB::table('newspaper_issues')
            ->where('newspaperId', $paperId)
            ->where('status', 'PUBLISHED')
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['id', 'date', 'persianDate', 'issueNumber', 'title', 'imageUrl', 'thumbnailUrl', 'originalUrl'])
            ->map(fn ($i) => [
                'id'          => (string) $i->id,
                'persianDate' => $i->persianDate,
                'issueNumber' => $i->issueNumber,
                'title'       => $i->title,
                'image'       => self::publicUrl($i->thumbnailUrl ?: $i->imageUrl),
                'originalUrl' => $i->originalUrl,
            ])
            ->all();
    }

    /**
     * Stored paths are relative to the public disk; legacy rows may hold an
     * absolute URL. Only local paths get turned into a /storage URL.
 */
private static function publicUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (preg_match('~^(https?:)?//~i', $path)) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    public static function latestIssues(int $limit = 24): array
    {
        return DB::table('newspaper_issues')
            ->leftJoin('newspapers', 'newspaper_issues.newspaperId', '=', 'newspapers.id')
            ->where('newspaper_issues.status', 'PUBLISHED')
            ->orderByDesc('newspaper_issues.date')
            ->limit($limit)
            ->get([
                'newspaper_issues.id', 'newspaper_issues.date', 'newspaper_issues.persianDate',
                'newspaper_issues.issueNumber', 'newspaper_issues.title',
                'newspaper_issues.thumbnailUrl', 'newspaper_issues.imageUrl',
                'newspapers.name as paperName', 'newspapers.slug as paperSlug',
            ])
            ->map(fn ($i) => [
                'id'          => (string) $i->id,
                'paperName'   => $i->paperName,
                'paperSlug'   => $i->paperSlug,
                'persianDate' => $i->persianDate,
                'issueNumber' => $i->issueNumber,
                'title'       => $i->title,
                'image'       => self::publicUrl($i->thumbnailUrl ?: $i->imageUrl),
                'link'        => '/newspapers/'.$i->paperSlug,
            ])
            ->all();
    }
}