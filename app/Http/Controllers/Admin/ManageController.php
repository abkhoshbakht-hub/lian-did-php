<?php

namespace App\Http\Controllers\Admin;

use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ManageController
{
    // ================================================================ دسته‌بندی
    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'slug'  => 'nullable|string|max:255',
            'icon'  => 'nullable|string|max:32',
            'color' => 'nullable|string|max:32',
        ]);

        $slug = $this->uniqueSlug('categories', $data['slug'] ?: $data['name']);

        DB::table('categories')->insert([
            'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
            'name' => $data['name'],
            'slug' => $slug,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'order' => (int) DB::table('categories')->max('order') + 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته‌بندی ساخته شد.');
    }

    public function updateCategory(Request $request, $id)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'slug'  => 'nullable|string|max:255',
            'icon'  => 'nullable|string|max:32',
            'color' => 'nullable|string|max:32',
            'order' => 'nullable|integer',
        ]);

        $row = DB::table('categories')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('categories')->where('id', $id)->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('categories', $data['slug'] ?: $data['name'], $id),
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'order' => $data['order'] ?? $row->order,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroyCategory($id)
    {
        $used = DB::table('articles')->where('categoryId', $id)->count();
        if ($used > 0) {
            return back()->with('error', "این دسته {$used} خبر دارد؛ ابتدا اخبار را به دسته دیگری منتقل کنید.");
        }

        DB::table('categories')->where('id', $id)->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }

    // ================================================================ کاربران
    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'  => 'required|in:ADMIN,USER',
            'phone' => 'nullable|string|max:32',
            'bio'   => 'nullable|string',
        ]);

        DB::table('users')->insert([
            'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'isActive' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'کاربر ساخته شد.');
    }

    public function updateUser(Request $request, $id)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role'  => 'required|in:ADMIN,USER',
            'phone' => 'nullable|string|max:32',
            'bio'   => 'nullable|string',
            'isActive' => 'nullable|boolean',
        ]);

        $row = DB::table('users')->where('id', $id)->first();
        abort_if(! $row, 404);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'isActive' => ! empty($data['isActive']) ? 1 : 0,
            'updated_at' => now(),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        DB::table('users')->where('id', $id)->update($update);

        return back()->with('success', 'کاربر به‌روزرسانی شد.');
    }

    public function destroyUser($id)
    {
        $row = DB::table('users')->where('id', $id)->first();
        abort_if(! $row, 404);

        if ($row->email === 'admin@liandid.ir') {
            return back()->with('error', 'حساب اصلی مدیر قابل حذف نیست.');
        }

        DB::table('users')->where('id', $id)->delete();

        return back()->with('success', 'کاربر حذف شد.');
    }

    public function toggleUser($id)
    {
        $row = DB::table('users')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('users')->where('id', $id)->update([
            'isActive' => $row->isActive ? 0 : 1,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'وضعیت کاربر تغییر کرد.');
    }

    // ================================================================ نظرات
    public function toggleComment($id)
    {
        $row = DB::table('comments')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('comments')->where('id', $id)->update([
            'approved' => $row->approved ? 0 : 1,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'وضعیت نظر تغییر کرد.');
    }

    public function destroyComment($id)
    {
        DB::table('comments')->where('id', $id)->delete();

        return back()->with('success', 'نظر حذف شد.');
    }

    // ================================================================ خبرنامه
    public function toggleSubscriber($id)
    {
        $row = DB::table('subscribers')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('subscribers')->where('id', $id)->update([
            'isActive' => $row->isActive ? 0 : 1,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'وضعیت عضو تغییر کرد.');
    }

    public function destroySubscriber($id)
    {
        DB::table('subscribers')->where('id', $id)->delete();

        return back()->with('success', 'عضو حذف شد.');
    }

    // ================================================================ شهرها
    public function bulkRegion(Request $request, string $from, string $to)
    {
        $moved = DB::table('external_news')->where('region', $from)->update([
            'region' => $to,
            'updated_at' => now(),
        ]);

        return back()->with('success', "{$moved} خبر منتقل شد.");
    }

    // ================================================================ صفحه اصلی
    public function homepage(string $section): Response
    {
        $articles = DB::table('articles')
            ->where('status', 'PUBLISHED')
            ->orderByDesc('publishedAt')
            ->get()
            ->map(fn ($a) => (array) $a + ['region' => $a->region]);

        $picked = match ($section) {
            'hero' => $articles->where('placement', 'hero')->take(10)->values(),
            'analysis' => $articles->where('placement', 'analysis')->take(10)->values(),
            default => $articles->where('placement', 'latest')->take(10)->values(),
        };

        return Inertia::render('Admin/Homepage', [
            'section'  => $section,
            'title'    => match ($section) {
                'hero' => 'هیرو',
                'analysis' => 'تحلیل خبر',
                default => 'آخرین اخبار',
            },
            'articles' => $picked,
            'slots'    => DB::table('homepage_slots')->orderBy('order')->get(),
        ]);
    }

    public function setPlacement(Request $request, $id)
    {
        $data = $request->validate([
            'placement' => 'required|in:latest,hero,analysis,featured',
        ]);

        $row = DB::table('articles')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('articles')->where('id', $id)->update([
            'placement' => $data['placement'],
            'updated_at' => now(),
        ]);

        return back()->with('success', 'محل نمایش تغییر کرد.');
    }

    public function updateSlot(Request $request, $key)
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:255',
            'customTitle' => 'nullable|string|max:255',
            'customContent' => 'nullable|string',
            'customLink' => 'nullable|string|max:255',
            'isActive' => 'nullable|boolean',
        ]);

        $update = array_filter($data, fn ($v) => $v !== null, ARRAY_FILTER_USE_BOTH);
        if (array_key_exists('isActive', $data)) {
            $update['isActive'] = ! empty($data['isActive']) ? 1 : 0;
        }
        $update['updated_at'] = now();

        DB::table('homepage_slots')->where('slotKey', $key)->update($update);

        return back()->with('success', 'جایگاه به‌روزرسانی شد.');
    }

    // ================================================================ اخبار لحظه‌ای
    public function externalNews(Request $request): Response
    {
        $q = DB::table('external_news');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('source')) {
            $q->where('sourceName', $request->string('source'));
        }
        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('sourceName', 'like', $term));
        }

        $rows = $q->orderByDesc('publishedAt')->limit(300)->get();

        return Inertia::render('Admin/ExternalNews', [
            'news' => $rows->map(fn ($n) => (array) $n + ['isBreaking' => (bool) $n->isBreaking]),
            'sources' => DB::table('external_news')->distinct()->orderBy('sourceName')->pluck('sourceName'),
            'counts' => [
                'total' => DB::table('external_news')->count(),
                'APPROVED' => DB::table('external_news')->where('status', 'APPROVED')->count(),
                'PENDING' => DB::table('external_news')->where('status', 'PENDING')->count(),
                'REJECTED' => DB::table('external_news')->where('status', 'REJECTED')->count(),
            ],
            'filters' => [
                'status' => $request->string('status')->toString(),
                'source' => $request->string('source')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function setNewsStatus(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:APPROVED,PENDING,REJECTED']);

        $row = DB::table('external_news')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('external_news')->where('id', $id)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        return back()->with('success', 'وضعیت خبر تغییر کرد.');
    }

    public function toggleNewsBreaking($id)
    {
        $row = DB::table('external_news')->where('id', $id)->first();
        abort_if(! $row, 404);

        DB::table('external_news')->where('id', $id)->update([
            'isBreaking' => $row->isBreaking ? 0 : 1,
            'updated_at' => now(),
        ]);

        return back();
    }

    public function destroyNews($id)
    {
        DB::table('external_news')->where('id', $id)->delete();

        return back()->with('success', 'خبر حذف شد.');
    }

    public function bulkNewsStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string',
            'status' => 'required|in:APPROVED,PENDING,REJECTED',
        ]);

        $n = DB::table('external_news')->whereIn('id', $data['ids'])->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        return back()->with('success', "وضعیت {$n} خبر تغییر کرد.");
    }

    // ================================================================ مناسبت‌های تقویمی
    public function events(): Response
    {
        return Inertia::render('Admin/Events', [
            'events' => DB::table('site_settings')->where('key', 'like', 'event.%')->get()
                ->map(function ($r) {
                    $v = json_decode($r->value, true) ?: [];
                    return [
                        'key' => $r->key,
                        'date' => $v['date'] ?? '',
                        'title' => $v['title'] ?? '',
                        'text' => $v['text'] ?? '',
                    ];
                })->values(),
        ]);
    }

    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|string|max:32',
            'title' => 'required|string|max:255',
            'text' => 'nullable|string',
        ]);

        $key = 'event.' . $data['date'];
        SettingStore::set($key, json_encode($data, JSON_UNESCAPED_UNICODE));

        return back()->with('success', 'مناسبت ثبت شد.');
    }

    public function destroyEvent($key)
    {
        DB::table('site_settings')->where('key', $key)->delete();

        return back()->with('success', 'مناسبت حذف شد.');
    }

    // ================================================================ صفحات ایستا
    public function staticPages(): Response
    {
        return Inertia::render('Admin/StaticPages', [
            'pages' => DB::table('static_pages')->orderBy('slug')->get()
                ->map(fn ($p) => (array) $p),
        ]);
    }

    public function saveStaticPage(Request $request, $slug)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
        ]);

        $exists = DB::table('static_pages')->where('slug', $slug)->exists();

        if ($exists) {
            DB::table('static_pages')->where('slug', $slug)->update([
                'title' => $data['title'], 'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? null, 'updated_at' => now(),
            ]);
        } else {
            DB::table('static_pages')->insert([
                'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
                'slug' => $slug,
                'title' => $data['title'], 'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'صفحه ذخیره شد.');
    }

    // ================================================================ ارسالی مخاطبین
    public function submissions(): Response
    {
        return Inertia::render('Admin/Submissions', [
            'submissions' => DB::table('user_submissions')->orderByDesc('created_at')->get()
                ->map(fn ($s) => (array) $s),
        ]);
    }

    public function setSubmission(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:PENDING,APPROVED,REJECTED',
            'adminNote' => 'nullable|string',
        ]);

        DB::table('user_submissions')->where('id', $id)->update([
            'status' => $data['status'],
            'adminNote' => $data['adminNote'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'وضعیت ارسالی تغییر کرد.');
    }

    public function destroySubmission($id)
    {
        DB::table('user_submissions')->where('id', $id)->delete();

        return back()->with('success', 'ارسالی حذف شد.');
    }

    // ================================================================ گالری
    public function gallery(): Response
    {
        $items = [];
        foreach (['public/images', 'storage/app/public/gallery'] as $dir) {
            $path = base_path($dir);
            if (! is_dir($path)) continue;
            foreach (scandir($path) ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $path . '/' . $f;
                if (! is_file($full)) continue;
                if (! preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $f)) continue;
                $items[] = [
                    'name' => $f,
                    'url' => str_starts_with($dir, 'public') ? '/' . substr($dir, 7) . '/' . $f : '/storage/gallery/' . $f,
                    'size' => round(filesize($full) / 1024) . ' KB',
                    'time' => date('Y/m/d H:i', filemtime($full)),
                ];
            }
        }

        return Inertia::render('Admin/Gallery', [
            'images' => $items,
            'maxMb' => SettingStore::get('image_max_mb', '1'),
        ]);
    }

    public function destroyGalleryImage(Request $request)
    {
        $data = $request->validate(['name' => 'required|string', 'dir' => 'nullable|string']);

        $dir = $data['dir'] ?: 'public/images';
        $path = base_path($dir) . '/' . basename($data['name']);

        if (is_file($path)) @unlink($path);

        return back()->with('success', 'تصویر حذف شد.');
    }

    // ================================================================ کوتاه‌کننده لینک
    public function shortlinks(): Response
    {
        return Inertia::render('Admin/Shortlinks', [
            'links' => DB::table('site_settings')->where('key', 'like', 'short.%')->get()
                ->map(function ($r) {
                    $v = json_decode($r->value, true) ?: [];
                    return ['code' => substr($r->key, 6), 'url' => $v['url'] ?? '', 'hits' => (int) ($v['hits'] ?? 0)];
                })->values(),
        ]);
    }

    public function storeShortlink(Request $request)
    {
        $data = $request->validate(['url' => 'required|url|max:2048']);

        do {
            $code = strtolower(Str::random(6));
        } while (DB::table('site_settings')->where('key', 'short.' . $code)->exists());

        SettingStore::set('short.' . $code, json_encode(['url' => $data['url'], 'hits' => 0], JSON_UNESCAPED_UNICODE));

        return back()->with('success', 'لینک کوتاه ساخته شد: /s/' . $code);
    }

    public function destroyShortlink($code)
    {
        DB::table('site_settings')->where('key', 'short.' . $code)->delete();

        return back()->with('success', 'لینک کوتاه حذف شد.');
    }

    // ================================================================ خاموشی سایت
    public function shutdown(): Response
    {
        $mode = SettingStore::get('site.mode', 'online');

        return Inertia::render('Admin/Shutdown', [
            'mode' => $mode,
            'message' => SettingStore::get('site.down_message', 'سایت موقتاً به دلیل تعمیرات در دسترس نیست.'),
        ]);
    }

    public function setMode(Request $request)
    {
        $data = $request->validate([
            'mode' => 'required|in:online,maintenance,offline',
            'message' => 'nullable|string|max:500',
        ]);

        SettingStore::set('site.mode', $data['mode']);
        SettingStore::set('site.down_message', $data['message'] ?? '');

        return back()->with('success', 'وضعیت سایت تغییر کرد.');
    }

    // ================================================================ راهنما
    public function help(): Response
    {
        return Inertia::render('Admin/Help', [
            'sections' => \App\Support\HelpContent::sections(),
        ]);
    }

    private function uniqueSlug(string $table, string $value, ?string $ignore = null): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        $slug = trim(mb_substr(trim($slug, '-'), 0, 180), '-') ?: 'item';
        $base = $slug;
        $i = 1;

        while (DB::table($table)->where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    // ================================================================ پاک‌سازی RSS
    /** Count what a deletion would remove, so the admin can confirm first. */
    public function countRssBefore(Request $request)
    {
        $v = $request->validate([
            'date'  => 'required|date',
            'scope' => 'nullable|in:pending,all',
        ]);

        return response()->json([
            'count' => $this->rssQuery($v['date'], $v['scope'] ?? 'pending')->count(),
        ]);
    }

    public function deleteRssBefore(Request $request)
    {
        $v = $request->validate([
            'date'  => 'required|date',
            'scope' => 'nullable|in:pending,all',
        ]);

        $count = $this->rssQuery($v['date'], $v['scope'] ?? 'pending')->count();

        if ($count === 0) {
            return back()->with('error', 'خبری با این شرایط پیدا نشد.');
        }

        $this->rssQuery($v['date'], $v['scope'] ?? 'pending')->delete();

        return back()->with('success', "{$count} خبر دریافتی از RSS تا تاریخ {$v['date']} حذف شد.");
    }

    private function rssQuery(string $date, string $scope)
    {
        $q = DB::table('external_news');

        // match on the news date when we have it, otherwise on arrival time
        $q->where(function ($w) use ($date) {
            $w->whereNotNull('publishedAt')->whereDate('publishedAt', '<=', $date)
              ->orWhere(function ($w2) use ($date) {
                  $w2->whereNull('publishedAt')->whereDate('fetchedAt', '<=', $date);
              });
        });

        if ($scope === 'pending') {
            $q->where('status', 'PENDING');
        }

        return $q;
    }

    // ================================================================ پشتیبان‌گیری
    public function fetchRss(Request $request)
    {
        $budget = max(5, (int) $request->input('budget', 60));
        $maxSources = max(1, (int) $request->input('sources', 6));
        $cooldown = max(0, (int) $request->input('cooldown', 60));

        $fetcher = new \App\Support\RssFetcher($budget, $maxSources, $cooldown, false);

        $t0 = microtime(true);
        $result = $fetcher->run();

        if ($result['locked']) {
            return back()->with('error', 'یک دریافت دیگر در حال اجراست؛ کمی بعد دوباره تلاش کنید.');
        }

        $elapsed = round(microtime(true) - $t0, 1);

        $summary = "دریافت در {$elapsed} ثانیه — {$result['ok']} موفق، {$result['unchanged']} بدون تغییر، "
            . "{$result['failed']} ناموفق، {$result['newItems']} خبر جدید (منتظر تأیید)";

        \App\Support\SettingStore::set('rss.last_result', json_encode([
            'at' => now()->toDateTimeString(),
            'summary' => $summary,
            'details' => $result['details'],
        ], JSON_UNESCAPED_UNICODE));

        return back()->with('success', $summary);
    }

    public function rssSources()
    {
        return Inertia::render('Admin/RssSources', [
            'sources' => collect(\App\Support\RssSources::all(true))->map(function ($s) {
                $state = json_decode(
                    (string) \App\Support\SettingStore::get('rss.' . md5($s['name'])),
                    true
                ) ?: [];

                $lastLog = DB::table('rss_fetch_source_logs')
                    ->where('source', $s['name'])
                    ->orderByDesc('startedAt')
                    ->first();

                return [
                    'name'      => $s['name'],
                    'url'       => $s['url'],
                    'category'  => $s['category'],
                    'fallback'  => ! empty($s['fallback']),
                    'lastFetch' => ! empty($state['last']) ? date('Y/m/d H:i', (int) $state['last']) : null,
                    'hasEtag'   => ! empty($state['etag']),
                    'status'    => $lastLog->status ?? null,
                    'newCount'  => $lastLog->newCount ?? null,
                    'error'     => $lastLog->error ?? null,
                ];
            })->values(),
            'pending' => DB::table('external_news')->where('status', 'PENDING')->count(),
            'approved' => DB::table('external_news')->where('status', 'APPROVED')->count(),
            'lastResult' => json_decode((string) \App\Support\SettingStore::get('rss.last_result'), true),
            'lastFetch' => json_decode((string) \App\Support\SettingStore::get('newspapers.last_fetch'), true),
        ]);
    }

    public function pruneRss(Request $request)
    {
        $days = max(1, (int) $request->input('days', 14));
        $n = \App\Support\RssFetcher::prunePending($days);

        return back()->with('success', "{$n} خبر «در انتظار تأیید» قدیمی‌تر از {$days} روز حذف شد.");
    }

    // ================================================================ روزنامه‌ها
    /** Discovers and stores today's front-page images. */
    public function fetchNewspaperFronts(Request $request)
    {
        $max = max(1, min(30, (int) $request->input('sources', 5)));
        $paper = $request->input('paper') ?: null;

        $t0 = microtime(true);
        $result = \App\Support\NewspaperFetcher::run($max, $paper);
        $elapsed = round(microtime(true) - $t0, 1);

        $detail = collect($result['details'])->map(function ($d) {
            return ($d['ok'] ? '✓ ' : '✗ ').$d['source'].($d['ok'] ? '' : ' — '.$d['error']);
        })->implode("\n");

        \App\Support\SettingStore::set('newspapers.last_fetch', json_encode([
            'at'      => now()->toDateTimeString(),
            'ok'      => $result['ok'],
            'failed'  => $result['failed'],
            'elapsed' => $elapsed,
            'details' => $result['details'],
        ], JSON_UNESCAPED_UNICODE));

        if ($result['ok'] > 0) {
            return back()->with('success', "در {$elapsed} ثانیه — {$result['ok']} صفحهٔ اول ذخیره شد، {$result['failed']} ناموفق.\n".$detail);
        }

        return back()->with('error', "هیچ صفحهٔ اولی پیدا نشد ({$result['failed']} منبع بررسی شد).\n".$detail);
    }

    public function newspapers(): Response
    {
        $issueCounts = DB::table('newspaper_issues')
            ->select('newspaperId', DB::raw('COUNT(*) AS n'))
            ->groupBy('newspaperId')
            ->pluck('n', 'newspaperId');

        $papers = DB::table('newspapers')->orderBy('displayOrder')->get()->map(function ($p) use ($issueCounts) {
            return (array) $p + [
                'active'     => (bool) $p->active,
                'issuesCount' => (int) ($issueCounts[$p->id] ?? 0),
                'sourcesCount' => DB::table('newspaper_sources')->where('newspaperId', $p->id)->count(),
            ];
        });

        return Inertia::render('Admin/Newspapers', [
            'newspapers' => $papers,
            'sources'    => DB::table('newspaper_sources')->orderBy('priority')->get()
                ->map(fn ($s) => (array) $s + ['active' => (bool) $s->active]),
            'issues'     => DB::table('newspaper_issues')
                ->orderByDesc('date')->limit(60)->get()
                ->map(fn ($i) => (array) $i + ['hasImage' => ! empty($i->imageUrl)]),
            'logs'       => DB::table('newspaper_fetch_logs')->orderByDesc('startedAt')->limit(20)->get(),
            'stats'      => [
                'papers'  => DB::table('newspapers')->count(),
                'sources' => DB::table('newspaper_sources')->count(),
                'issues'  => DB::table('newspaper_issues')->count(),
                'noImage' => DB::table('newspaper_issues')->whereNull('imageUrl')->orWhere('imageUrl', '')->count(),
            ],
            'lastFetch' => json_decode((string) \App\Support\SettingStore::get('newspapers.last_fetch'), true),
        ]);
    }

    public function storeNewspaper(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'slug'     => 'nullable|string|max:255',
            'category' => 'nullable|string|max:64',
            'website'  => 'nullable|url|max:255',
            'telegram' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'displayOrder' => 'nullable|integer',
        ]);

        DB::table('newspapers')->insert([
            'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
            'name' => $data['name'],
            'slug' => $this->uniqueSlug('newspapers', $data['slug'] ?: $data['name']),
            'category' => $data['category'] ?: 'national',
            'website' => $data['website'] ?? null,
            'telegram' => $data['telegram'] ?? null,
            'description' => $data['description'] ?? null,
            'active' => 1,
            'displayOrder' => $data['displayOrder'] ?? ((int) DB::table('newspapers')->max('displayOrder') + 1),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'روزنامه اضافه شد.');
    }

    public function updateNewspaper(Request $request, $id)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:64',
            'website'  => 'nullable|url|max:255',
            'telegram' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'active'   => 'nullable|boolean',
        ]);

        DB::table('newspapers')->where('id', $id)->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? 'national',
            'website' => $data['website'] ?? null,
            'telegram' => $data['telegram'] ?? null,
            'description' => $data['description'] ?? null,
            'active' => array_key_exists('active', $data) ? (! empty($data['active']) ? 1 : 0) : DB::table('newspapers')->where('id', $id)->value('active'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'روزنامه به‌روزرسانی شد.');
    }

    public function destroyNewspaper($id)
    {
        DB::table('newspapers')->where('id', $id)->delete();

        return back()->with('success', 'روزنامه و شماره‌های آن حذف شد.');
    }

    public function storeNewspaperSource(Request $request)
    {
        $data = $request->validate([
            'newspaperId' => 'required|string',
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:official,archive,custom',
            'url'     => 'required|url|max:500',
            'priority' => 'nullable|integer',
            'parserType' => 'nullable|string|max:64',
            'active'  => 'nullable|boolean',
        ]);

        $paper = DB::table('newspapers')->where('id', $data['newspaperId'])->first();
        abort_if(! $paper, 404, 'روزنامه یافت نشد.');

        DB::table('newspaper_sources')->insert([
            'id' => 'cmt' . substr(str_replace('-', '', Str::uuid()->toString()), 0, 22),
            'newspaperId' => $data['newspaperId'],
            'name' => $data['name'],
            'type' => $data['type'],
            'url' => $data['url'],
            'priority' => $data['priority'] ?? 0,
            'active' => ! empty($data['active']) ? 1 : 0,
            'parserType' => $data['parserType'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'منبع اضافه شد.');
    }

    public function destroyNewspaperSource($id)
    {
        DB::table('newspaper_sources')->where('id', $id)->delete();

        return back()->with('success', 'منبع حذف شد.');
    }

    public function destroyNewspaperIssue($id)
    {
        DB::table('newspaper_issues')->where('id', $id)->delete();

        return back()->with('success', 'شماره حذف شد.');
    }
}
