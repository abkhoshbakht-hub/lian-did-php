<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Resolves the homepage layout exactly like the Next.js implementation
 * (liandid-nextjs/src/app/page.tsx -> getHomepageData).
 *
 * Slot precedence: manager-pinned article placement > explicit homepage slot
 * (hero/breaking/latest/analysis 1..3) > automatic pool fallback.
 */
class HomepageResolver
{
    /** @return array{heroMain:?array,heroSide1:?array,heroSide2:?array,breaking:array,latest:array,analysis:array} */
    public static function resolve(): array
    {
        try {
            $slots = DB::table('homepage_slots')
                ->where('isActive', 1)
                ->orderBy('order')
                ->get();

            $approvedNews = DB::table('external_news')
                ->where('status', 'APPROVED')
                ->orderByDesc('publishedAt')
                ->limit(100)
                ->get();

            $articles = DB::table('articles')
                ->leftJoin('categories', 'articles.categoryId', '=', 'categories.id')
                ->leftJoin('users', 'articles.authorId', '=', 'users.id')
                ->where('articles.status', 'PUBLISHED')
                ->orderByDesc('articles.publishedAt')
                ->select([
                    'articles.id', 'articles.title', 'articles.slug', 'articles.excerpt',
                    'articles.featuredImage', 'articles.publishedAt', 'articles.isBreaking',
                    'articles.isFeatured', 'articles.placement',
                    'categories.name as categoryName', 'categories.slug as categorySlug',
                    'users.name as authorName',
                ])
                ->get();

            $toExternalItem = fn ($n) => [
                'id'            => (string) $n->id,
                'title'         => (string) $n->title,
                'link'          => (string) ($n->link ?: '#'),
                'description'   => $n->description,
                'image'         => $n->image,
                'source'        => (string) $n->source,
                'sourceName'    => (string) $n->sourceName,
                'category'      => $n->category,
                'publishedAt'   => $n->publishedAt,
                'isBreaking'    => (bool) $n->isBreaking,
                'isCustom'      => false,
            ];

            $toArticleItem = fn ($a) => [
                'id'            => (string) $a->id,
                'title'         => (string) $a->title,
                'link'          => '/news/'.$a->slug,
                'description'   => $a->excerpt,
                'image'         => $a->featuredImage,
                'source'        => $a->authorName ?: 'لیان دید',
                'sourceName'    => 'لیان دید',
                'category'      => $a->categoryName,
                'publishedAt'   => $a->publishedAt,
                'isBreaking'    => (bool) $a->isBreaking,
                'isCustom'      => false,
            ];

            $articleItems = $articles->map(fn ($a) => $toArticleItem($a))->values();
            $externalItems = $approvedNews->map(fn ($n) => $toExternalItem($n))->values();

            // Articles first, then RSS news - matches the Next.js ordering.
            $allNewsItems = $articleItems->concat($externalItems)->values();

            $placementById = [];
            foreach ($articles as $a) {
                $placementById[(string) $a->id] = $a->placement ?: 'latest';
            }
            $getPlacement = fn ($id) => $placementById[$id] ?? 'latest';

            $itemsById = [];
            foreach ($allNewsItems as $i) {
                $itemsById[$i['id']] ??= $i;
            }

            $pick = function (array $list, string $placement) use ($getPlacement) {
                foreach ($list as $i) {
                    if ($getPlacement($i['id']) === $placement) {
                        return $i;
                    }
                }

                return null;
            };

            $dedupe = function (array $items) {
                $seen = [];

                return array_values(array_filter($items, function ($i) use (&$seen) {
                    if (isset($seen[$i['id']])) {
                        return false;
                    }
                    $seen[$i['id']] = true;

                    return true;
                }));
            };

            $findSlot = function (string $key) use ($slots, $itemsById) {
                $slot = $slots->firstWhere('slotKey', $key);

                if (! $slot) {
                    return null;
                }

                $payload = [
                    'id'           => (string) $slot->id,
                    'type'         => (string) $slot->type,
                    'customTitle'  => $slot->customTitle,
                    'customLink'   => $slot->customLink,
                    'customContent' => $slot->customContent,
                    'customImage'  => $slot->customImage,
                    'category'     => $slot->category,
                ];

                if ($slot->externalNewsId && isset($itemsById[(string) $slot->externalNewsId])) {
                    $payload['externalNews'] = $itemsById[(string) $slot->externalNewsId];
                }

                return $payload;
            };

            $resolve = function (?array $slot, ?array $fallback = null) {
                if ($slot && $slot['type'] === 'CUSTOM' && $slot['customTitle']) {
                    return [
                        'id'          => $slot['id'],
                        'title'       => $slot['customTitle'],
                        'link'        => $slot['customLink'] ?: '#',
                        'description' => $slot['customContent'] ?: '',
                        'image'       => $slot['customImage'] ?: '',
                        'source'      => 'لیان دید',
                        'sourceName'  => 'لیان دید',
                        'category'    => $slot['category'] ?: 'اختصاصی',
                        'publishedAt' => null,
                        'isBreaking'  => false,
                        'isCustom'    => true,
                    ];
                }

                if ($slot && $slot['type'] === 'EXTERNAL' && isset($slot['externalNews'])) {
                    return $slot['externalNews'];
                }

                return $fallback ?: null;
            };

            $heroPlaced = $pick($allNewsItems->all(), 'hero');

            $heroShown = [];
            $takePlacedHero = function () use (&$heroShown, $heroPlaced) {
                if ($heroPlaced && ! isset($heroShown[$heroPlaced['id']])) {
                    $heroShown[$heroPlaced['id']] = true;

                    return $heroPlaced;
                }

                return null;
            };
            $takePoolHero = function () use (&$heroShown, $allNewsItems) {
                foreach ($allNewsItems as $i) {
                    if (! isset($heroShown[$i['id']])) {
                        $heroShown[$i['id']] = true;

                        return $i;
                    }
                }

                return null;
            };
            $pickHero = function (string $slotKey) use ($takePlacedHero, $takePoolHero, $findSlot, $resolve, &$heroShown) {
                $placed = $takePlacedHero();
                if ($placed) {
                    return $placed;
                }

                $r = $resolve($findSlot($slotKey));
                if ($r && ! isset($heroShown[$r['id']])) {
                    $heroShown[$r['id']] = true;

                    return $r;
                }

                return $takePoolHero();
            };

            $heroMain  = $pickHero('hero-main');
            $heroSide1 = $pickHero('hero-side-1');
            $heroSide2 = $pickHero('hero-side-2');

            $heroIds = array_values(array_filter(array_map(fn ($h) => $h['id'] ?? null, [$heroMain, $heroSide1, $heroSide2])));

            // Breaking: articles + external news flagged isBreaking, hero ids excluded.
            $breaking = [];
            foreach ($allNewsItems as $i) {
                if (! $i['isBreaking'] || in_array($i['id'], $heroIds, true)) {
                    continue;
                }
                $breaking[] = $i;
                if (count($breaking) >= 3) {
                    break;
                }
            }

            $usedIds = array_fill_keys(array_merge(
                $heroIds,
                array_column($breaking, 'id')
            ), true);

            $pinnedPlaced = $pick($allNewsItems->all(), 'pinned');
            $analysisPlaced = $pick($allNewsItems->all(), 'analysis');

            $restPool = array_values(array_filter(
                $allNewsItems->all(),
                fn ($i) => ! isset($usedIds[$i['id']])
            ));

            $isPinnedOrAnalysis = function ($i) use ($getPlacement, $pinnedPlaced, $analysisPlaced) {
                $p = $getPlacement($i['id']);
                if ($p === 'analysis') {
                    return true;
                }
                if ($p === 'pinned') {
                    return true;
                }

                return $pinnedPlaced && $i['id'] === $pinnedPlaced['id'];
            };

            $latestPool = array_values(array_filter(
                $restPool,
                fn ($i) => ! $isPinnedOrAnalysis($i)
            ));

            // Pinned items float to the front, everything else sorted newest first.
            $byTime = function ($x, $y) {
                $tx = $x['publishedAt'] ? strtotime($x['publishedAt']) : 0;
                $ty = $y['publishedAt'] ? strtotime($y['publishedAt']) : 0;

                return $ty <=> $tx;
            };
            usort($restPool, $byTime);
            usort($latestPool, $byTime);

            $pinnedFirst = [];
            if ($pinnedPlaced && ! isset($usedIds[$pinnedPlaced['id']])) {
                $pinnedFirst[] = $pinnedPlaced;
            }
            $latestPool = $dedupe(array_merge($pinnedFirst, $latestPool));

            $latest = array_slice($latestPool, 0, 8);
            foreach ($latest as $k => $item) {
                $usedIds[$item['id']] = true;
                if (empty($item['image'])) {
                    // Only fill a placeholder when the item has no real image.
                    $latest[$k]['image'] = '/latest-news/'.($k + 1).'.jpg';
                }
            }

            // --- The three analysis slots -------------------------------------
            $analysis = [];
            foreach (['analysis-1', 'analysis-2', 'analysis-3'] as $i => $key) {
                $slot = $findSlot($key);

                $pool = [];
                if ($analysisPlaced && ! isset($usedIds[$analysisPlaced['id']])) {
                    $pool[] = $analysisPlaced;
                }
                foreach ($allNewsItems as $i2) {
                    if (! isset($usedIds[$i2['id']])) {
                        $pool[] = $i2;
                    }
                }
                $pool = $dedupe($pool);
                usort($pool, $byTime);

                $fallback = $pool[$i] ?? null;
                $item = $resolve($slot, $fallback);

                if ($item) {
                    $usedIds[$item['id']] = true;
                    $analysis[] = $item;
                }
            }

            return [
                'heroMain'  => $heroMain,
                'heroSide1' => $heroSide1,
                'heroSide2' => $heroSide2,
                'breaking'  => $breaking,
                'latest'    => $latest,
                'analysis'  => $analysis,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'heroMain' => null, 'heroSide1' => null, 'heroSide2' => null,
                'breaking' => [], 'latest' => [], 'analysis' => [],
            ];
        }
    }
}