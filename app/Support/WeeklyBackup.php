<?php

namespace App\Support;

use Throwable;

/**
 * Weekly database backup.
 *
 * Triggered opportunistically: any cron hit (rss cron, cron-job.org, ...)
 * checks "is a new week due?" and runs the dump when it is — so no extra
 * cron registration is needed on the shared host.
 *
 * Destination is chosen by the admin in the panel:
 *   - 'host'     : keep the newest four dumps in storage/app/backups
 *   - 'telegram' : send the dump off the host to the configured chat
 *                  (falls back to 'host' when Telegram fails, so a broken
 *                  config never loses a backup)
 */
class WeeklyBackup
{
    private const INTERVAL_DAYS = 7;

    public static function enabled(): bool
    {
        return SettingStore::get('backup.weekly.enabled', '0') === '1';
    }

    public static function destination(): string
    {
        return SettingStore::get('backup.weekly.dest', 'host') === 'telegram' ? 'telegram' : 'host';
    }

    public static function setConfig(bool $enabled, string $dest): void
    {
        SettingStore::set('backup.weekly.enabled', $enabled ? '1' : '0');
        SettingStore::set('backup.weekly.dest', $dest === 'telegram' ? 'telegram' : 'host');
    }

    /** Run when due. Cheap no-op otherwise; never throws. */
    public static function maybeRun(): array
    {
        try {
            if (! self::enabled()) {
                return ['ran' => false, 'reason' => 'disabled'];
            }

            $last = SettingStore::get('backup.weekly.last_at');
            if ($last && \Carbon\Carbon::parse($last)->diffInHours(now()) < self::INTERVAL_DAYS * 24 - 2) {
                return ['ran' => false, 'reason' => 'not_due'];
            }

            // two cron hits can land minutes apart — the first one wins
            $lock = SettingStore::get('backup.weekly.lock_at');
            if ($lock && \Carbon\Carbon::parse($lock)->diffInMinutes(now()) < 30) {
                return ['ran' => false, 'reason' => 'locked'];
            }
            SettingStore::set('backup.weekly.lock_at', now()->toDateTimeString());

            $result = self::destination() === 'telegram' ? self::toTelegram() : self::toHost();

            if (! $result['ok']) {
                // Telegram failed: keep the dump on the host anyway.
                $host = self::toHost();
                $result = $host['ok'] ? $host + ['fellBack' => true] : $result;
            }

            if ($result['ok']) {
                SettingStore::set('backup.weekly.last_at', now()->toDateTimeString());
            }

            SettingStore::set('backup.weekly.result', json_encode(
                ['at' => now()->toDateTimeString()] + $result,
                JSON_UNESCAPED_UNICODE
            ));

            return $result;
        } catch (Throwable $e) {
            return ['ran' => false, 'reason' => 'error', 'message' => mb_substr($e->getMessage(), 0, 200)];
        }
    }

    private static function toTelegram(): array
    {
        $r = (new DatabaseBackup())->sendToTelegram(DatabaseBackup::filename('weekly'));

        return ['ok' => $r['ok'], 'target' => 'telegram', 'message' => $r['error']];
    }

    private static function toHost(): array
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $name = DatabaseBackup::filename('weekly');
        $path = $dir . '/' . $name;

        $out = fopen($path, 'wb');
        $ref = new \ReflectionClass(DatabaseBackup::class);
        $m   = $ref->getMethod('writeTo');
        $m->setAccessible(true);
        $m->invoke(new DatabaseBackup(), $out);
        fclose($out);

        // keep only the four most recent weekly dumps
        $files = glob($dir . '/liandid_weekly_*.sql') ?: [];
        rsort($files);
        foreach (array_slice($files, 4) as $old) {
            @unlink($old);
        }

        return ['ok' => true, 'target' => 'host', 'file' => $name, 'bytes' => (int) filesize($path)];
    }
}
