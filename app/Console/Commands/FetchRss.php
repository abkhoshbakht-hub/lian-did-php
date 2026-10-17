<?php

namespace App\Console\Commands;

use App\Support\RssFetcher;
use App\Support\RssSources;
use Illuminate\Console\Command;

class FetchRss extends Command
{
    protected $signature = 'rss:fetch
        {--budget=60 : wall-clock seconds the run may take}
        {--sources=6 : how many sources to touch this run}
        {--cooldown=60 : minutes before a source is checked again}
        {--fallbacks : also try the alternative feed URLs}
        {--prune=0 : delete pending items older than N days (0 = off)}';

    protected $description = 'Fetch RSS feeds and store new items as PENDING for admin approval';

    public function handle(): int
    {
        if ($days = (int) $this->option('prune')) {
            $n = RssFetcher::prunePending($days);
            $this->components->info("پاک‌سازی: {$n} خبر «در انتظار تأیید» حذف شد.");
        }

        $budget = max(5, (int) $this->option('budget'));
        $maxSources = max(1, (int) $this->option('sources'));
        $cooldown = max(0, (int) $this->option('cooldown'));

        $fetcher = new RssFetcher($budget, $maxSources, $cooldown, (bool) $this->option('fallbacks'));

        $this->components->info(sprintf(
            'شروع دریافت: حداکثر %d منبع، سقف %d ثانیه، فاصله %d دقیقه',
            $maxSources, $budget, $cooldown
        ));
        $this->line('');

        $rows = [];
        $t0 = microtime(true);

        $result = $fetcher->run(function (array $r) use (&$rows) {
            $rows[] = $r;
            $icon = match ($r['status']) {
                'SUCCESS' => '<fg=green>✓</>',
                'UNCHANGED' => '<fg=cyan>=</>',
                'COOLDOWN' => '<fg=yellow>·</>',
                default => '<fg=red>✗</>',
            };
            $this->line(sprintf(
                '  %s %-18s %-10s %2d خوانده، %2d جدید، %5dms%s',
                $icon,
                $r['source'],
                $r['status'],
                $r['fetched'] ?? 0,
                $r['new'] ?? 0,
                $r['ms'] ?? 0,
                $r['error'] ? "  <fg=red>{$r['error']}</>" : ''
            ));
        });

        $this->line('');

        if ($result['locked']) {
            $this->components->warn('یک دریافت دیگر در حال اجراست؛ این اجرا رد شد.');

            return self::SUCCESS;
        }

        $elapsed = round(microtime(true) - $t0, 1);

        $this->components->info(sprintf(
            'پایان در %ss — منابع: %d | موفق: %d | بدون تغییر: %d | ناموفق: %d | در فاصله: %d',
            $elapsed, $result['sources'], $result['ok'], $result['unchanged'],
            $result['failed'], $result['skippedCooldown']
        ));

        if ($result['newItems'] > 0) {
            $this->components->warn("{$result['newItems']} خبر جدید ثبت شد و منتظر تأیید شماست.");
            $this->line('  برای انتشار: پنل مدیریت ← اخبار لحظه‌ای ← تأیید');
        } else {
            $this->components->info('خبر جدیدی نبود.');
        }

        return self::SUCCESS;
    }
}
