<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes newspaper front pages that are no longer wanted.
 *
 * Retention rule requested by the site owner:
 *   - the most recent 7 days stay untouched
 *   - issues between 8 and 30 days old have their image file deleted and the
 *     row kept, pointing back at the newspaper's own page (originalUrl)
 *
 * Rows outside the 8-30 day window are never touched, so nothing the owner did
 * not ask to remove is removed.
 */
class NewspaperRetention
{
    public const KEEP_DAYS    = 7;
    public const DELETE_DAYS = 30;

    public static function stats(): array
    {
        $now = now();

        return [
            'keep_before' => $now->copy()->subDays(self::KEEP_DAYS),
            'delete_before' => $now->copy()->subDays(self::DELETE_DAYS),
            'in_window' => self::window()->count(),
            'bytes' => self::windowBytes(),
        ];
    }

    /** Rows whose date falls inside the 8..30 day removal window. */
    private static function window()
    {
        return DB::table('newspaper_issues')
            ->where('imageUrl', 'like', 'newspapers/%')
            ->where('date', '<', now()->subDays(self::KEEP_DAYS))
            ->where('date', '>=', now()->subDays(self::DELETE_DAYS))
            ->get(['id', 'date', 'imageUrl', 'thumbnailUrl', 'originalUrl']);
    }

    private static function windowBytes(): int
    {
        $n = 0;
        foreach (self::window() as $row) {
            $p = Storage::disk('public')->path((string) $row->imageUrl);
            if (is_file($p)) $n += filesize($p);
        }

        return $n;
    }

    /**
     * Deletes the image files in the 8..30 day window and points those rows at
     * the newspaper's own page instead. The rows themselves stay, so the
     * archive of past front pages is still listed.
     *
     * @return array{files:int, bytes:int, rows:int}
     */
    public static function prune(): array
    {
        $deletedFiles = 0;
        $freedBytes    = 0;
        $rows          = 0;

        foreach (self::window() as $row) {
            $freedBytes += self::deleteFile((string) $row->imageUrl);
            if ((string) $row->thumbnailUrl !== (string) $row->imageUrl) {
                $freedBytes += self::deleteFile((string) $row->thumbnailUrl);
            }

            $updated = DB::table('newspaper_issues')
                ->where('id', $row->id)
                ->update([
                    // keep the archive visible, but without our stored copy
                    'imageUrl'     => (string) $row->originalUrl ?: null,
                    'thumbnailUrl' => (string) $row->originalUrl ?: null,
                    'updated_at'   => now(),
                ]);

            if ($updated) {
                $deletedFiles++;
                $rows++;
            }
        }

        return ['files' => $deletedFiles, 'bytes' => $freedBytes, 'rows' => $rows];
    }

    private static function deleteFile(string $rel): int
    {
        if ($rel === '' || ! str_starts_with($rel, 'newspapers/')) {
            return 0;
        }

        $path = Storage::disk('public')->path($rel);
        if (! is_file($path)) {
            return 0;
        }

        $size = filesize($path);

        if (@unlink($path)) {
            return $size;
        }

        return 0;
    }

    /** Rows in the removal window, for the dry-run listing. */
    public static function windowPreview(): array
    {
        return self::window()->map(fn ($r) => [
            'date' => mb_substr((string) $r->date, 0, 10),
            'imageUrl' => (string) $r->imageUrl,
            'bytes' => is_file(Storage::disk('public')->path((string) $r->imageUrl))
                ? filesize(Storage::disk('public')->path((string) $r->imageUrl))
                : 0,
        ])->all();
    }

    /** Command entry point. */
    public static function report(): string
    {
        $s = self::stats();

        return sprintf(
            "keep the last %d days; %d issue(s) between %d and %d days old, %s to free",
            self::KEEP_DAYS,
            $s['in_window'],
            self::KEEP_DAYS + 1,
            self::DELETE_DAYS,
            number_format($s['bytes'] / 1024 / 1024, 2) . ' MB'
        );
    }
}