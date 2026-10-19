<?php

namespace App\Console\Commands;

use App\Support\NewspaperRetention;
use Illuminate\Console\Command;

class PruneNewspapers extends Command
{
    protected $signature = 'newspapers:prune
        {--dry-run : report what would be removed without deleting}';

    protected $description = 'Delete stored front-page images that are 8 to 30 days old (the last 7 days are always kept)';

    public function handle(): int
    {
        $this->info(NewspaperRetention::report());

        if ($this->option('dry-run')) {
            foreach (NewspaperRetention::windowPreview() as $r) {
                $this->line(sprintf('  would remove %-12s %s', $r['date'], $r['imageUrl']));
            }

            $this->info('dry run, nothing deleted.');

            return self::SUCCESS;
        }

        $result = NewspaperRetention::prune();

        $this->info(sprintf(
            'removed %d front page file(s), freed %.2f MB',
            $result['files'],
            $result['bytes'] / 1024 / 1024
        ));

        return self::SUCCESS;
    }
}