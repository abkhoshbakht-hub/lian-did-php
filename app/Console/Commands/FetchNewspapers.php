<?php

namespace App\Console\Commands;

use App\Support\NewspaperFetcher;
use Illuminate\Console\Command;

class FetchNewspapers extends Command
{
    protected $signature = 'newspapers:fetch
        {--sources=5 : how many active sources to try}
        {--paper= : only one newspaper id}';

    protected $description = 'Discover and store the front-page image for the newspapers';

    public function handle(): int
    {
        $max = max(1, (int) $this->option('sources'));
        $paper = $this->option('paper');

        $this->info("Fetching front pages (max {$max} sources)...");

        $t0 = microtime(true);
        $result = NewspaperFetcher::run($max, $paper ?: null);
        $elapsed = round(microtime(true) - $t0, 1);

        foreach ($result['details'] as $d) {
            if ($d['ok']) {
                $this->line(sprintf('  <info>OK</info>   %-28s %s', mb_substr($d['source'], 0, 26), $d['image']));
            } else {
                $this->line(sprintf('  <error>FAIL</error> %-28s %s', mb_substr($d['source'], 0, 26), $d['error']));
            }
        }

        $this->info("Done in {$elapsed}s — {$result['ok']} found, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}