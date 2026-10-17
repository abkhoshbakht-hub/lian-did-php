<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Streams a SQL dump straight to the browser (or to Telegram).
 *
 * The dump is NEVER written to disk on the server: it is generated in memory
 * and piped out, so the backup ends up on the admin's own machine instead of
 * filling the hosting account.
 */
class DatabaseBackup
{
    /** Tables that never contain anything worth keeping. */
    private const SKIP = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    public function __construct(private array $only = []) {}

    public static function dbName(): string
    {
        return DB::connection()->getDatabaseName();
    }

    public function size(): array
    {
        $bytes = 0;
        $rows = 0;

        foreach ($this->tableNames() as $t) {
            $info = DB::selectOne('SELECT COUNT(*) AS c FROM `' . $t . '`');
            $rows += (int) $info->c;

            $size = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS s
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$t]
            );
            $bytes += (int) ($size->s ?? 0);
        }

        return ['rows' => $rows, 'bytes' => $bytes, 'tables' => count($this->tableNames())];
    }

    private function tableNames(): array
    {
        if ($this->only) return $this->only;

        $rows = DB::select("
            SELECT TABLE_NAME AS t FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ");

        return array_column(array_map(fn ($r) => (array) $r, $rows), 't');
    }

    /**
     * Sends the dump to the browser as a download.
     * Nothing is stored on the server.
     */
    public function download(string $filename): void
    {
        // free the buffers so the download is not buffered in memory
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'wb');
        $this->writeTo($out);
        fclose($out);
    }

    /**
     * Uploads the dump to a Telegram chat. The file goes to Telegram's
     * servers, so nothing is kept on the hosting account either.
     * Requires a bot token + chat id in the settings.
     */
    public function sendToTelegram(string $filename): array
    {
        $token = trim((string) SettingStore::get('backup.telegram_token', ''));
        $chatId = trim((string) SettingStore::get('backup.telegram_chat', ''));

        if ($token === '' || $chatId === '') {
            return ['ok' => false, 'error' => 'توکن یا شناسه چت تلگرام تنظیم نشده است.'];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'bk');
        $fh = fopen($tmp, 'wb');
        $this->writeTo($fh);
        fclose($fh);

        $ch = curl_init("https://api.telegram.org/bot{$token}/sendDocument");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'document' => new \CURLFile($tmp, 'application/sql', $filename),
                'caption' => 'پشتیبان‌گیری لیان دید — ' . date('Y/m/d H:i'),
            ],
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // always remove the temp file, even on failure
        @unlink($tmp);

        if ($err) return ['ok' => false, 'error' => $err];
        if ($code !== 200) return ['ok' => false, 'error' => "HTTP $code"];

        return ['ok' => true, 'error' => null];
    }

    /** Primary key column(s) for a table; empty when there is none. */
    private function primaryKey(string $table): array
    {
        $rows = DB::select(
            "SELECT COLUMN_NAME AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY'
             ORDER BY SEQ_IN_INDEX",
            [$table]
        );

        return array_column(array_map(fn ($r) => (array) $r, $rows), 'c');
    }

    /** Builds the SQL into the given stream. */
    private function writeTo($out): void
    {
        $db = self::dbName();

        fwrite($out, "-- پشتیبان‌گیری پایگاه داده لیان دید\n");
        fwrite($out, '-- Database: ' . $db . "\n");
        fwrite($out, '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n");
        fwrite($out, "SET NAMES utf8mb4;\n");
        fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tableNames() as $table) {
            if (in_array($table, self::SKIP, true)) continue;

            $create = DB::selectOne('SHOW CREATE TABLE `' . $table . '`');
            fwrite($out, "\n-- ── جدول {$table} ──\n");
            fwrite($out, 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n");
            fwrite($out, ($create->{'Create Table'} ?? '') . ";\n\n");

            $query = DB::table($table);

            // not every table has an `id` (article_tag, site_settings, ...)
            $pk = $this->primaryKey($table);
            if ($pk) {
                $query->orderBy($pk[0]);
            }

            $written = 0;
            $query->chunk(500, function ($rows) use ($out, $table, &$written) {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $cols = array_map(fn ($c) => '`' . $c . '`', array_keys($data));
                    $vals = array_map(fn ($v) => $this->quote($v), array_values($data));

                    fwrite($out, "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n");
                    $written++;
                }
            });

            fwrite($out, "\n-- {$written} row(s)\n");
        }

        fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function quote($value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string) $value;

        $escaped = str_replace(
            ["\\", "\0", "\n", "\r", "\x1a", "'", '"'],
            ["\\\\", "\\0", "\\n", "\\r", "\\Z", "\\'", '\\"'],
            (string) $value
        );

        return "'" . $escaped . "'";
    }

    public static function filename(): string
    {
        return 'liandid_php_' . date('Y-m-d_His') . '.sql';
    }
}
