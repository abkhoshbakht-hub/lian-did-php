<?php

namespace App\Http\Controllers;

use App\Support\CronToken;
use App\Support\DatabaseBackup;
use App\Support\RssFetcher;
use App\Support\ServerHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints for the hosting provider's cron job.
 *
 * Shared hosting usually only offers "call this URL every N minutes",
 * so every scheduled task is exposed as a single guarded URL.
 */
class CronController extends Controller
{
    /** GET or POST; cron providers use either. */
    private function guard(Request $request): ?JsonResponse
    {
        if (! CronToken::valid((string) $request->query('token', ''))) {
            return response()->json(['ok' => false, 'message' => 'توکن نامعتبر است.'], 403);
        }

        return null;
    }

    /** Fetch RSS and drop pending items older than the retention window. */
    public function rss(Request $request): JsonResponse
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $budget  = max(10, (int) $request->query('budget', 60));
        $sources = max(1, (int) $request->query('sources', 6));
        $keep    = max(1, (int) $request->query('keep_days', 14));

        $t0 = microtime(true);
        $result = (new RssFetcher($budget, $sources, 0, false))->run();
        $pruned = RssFetcher::prunePending($keep);

        return response()->json([
            'ok'         => ! $result['locked'],
            'sources'    => $result['ok'] . '/' . ($result['ok'] + $result['failed']),
            'newItems'   => $result['newItems'],
            'pruned'     => $pruned,
            'elapsed'    => round(microtime(true) - $t0, 1),
        ]);
    }

    /** Build a database backup and stream it to the admin's browser or Telegram. */
    public function backup(Request $request): JsonResponse
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        $telegram = (string) \App\Support\SettingStore::get('backup.telegram_token', '') !== '';
        $name     = DatabaseBackup::filename();

        if ($telegram) {
            $result = (new DatabaseBackup())->sendToTelegram($name);

            return response()->json(['ok' => $result['ok'], 'target' => 'telegram', 'message' => $result['error']]);
        }

        // No Telegram configured: keep the newest dump on the host and report the
        // path so the admin can download it, or rotate to the previous file.
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $path = $dir . '/' . $name;
        $out  = fopen($path, 'wb');
        $ref  = new \ReflectionClass(DatabaseBackup::class);
        $m    = $ref->getMethod('writeTo');
        $m->setAccessible(true);
        $m->invoke(new DatabaseBackup(), $out);
        fclose($out);

        // keep only the three most recent dumps
        $files = glob($dir . '/liandid_php_*.sql') ?: [];
        rsort($files);
        foreach (array_slice($files, 3) as $old) {
            @unlink($old);
        }

        \App\Support\SettingStore::set('backup.last_at', now()->toDateTimeString());

        return response()->json([
            'ok'     => true,
            'target' => 'host',
            'file'   => basename($path),
            'bytes'  => filesize($path),
            'kept'   => count(array_slice($files, 0, 3)),
        ]);
    }

    /** A quick report the admin can run before moving to the real host. */
    public function health(Request $request): JsonResponse
    {
        if ($denied = $this->guard($request)) {
            return $denied;
        }

        return response()->json(ServerHealth::run());
    }
}