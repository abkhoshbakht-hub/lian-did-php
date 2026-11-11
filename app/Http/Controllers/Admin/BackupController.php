<?php

namespace App\Http\Controllers\Admin;

use App\Support\DatabaseBackup;
use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BackupController
{
    public function index(): Response
    {
        $info = (new DatabaseBackup())->size();

        return Inertia::render('Admin/Backup', [
            'stats' => [
                'tables' => $info['tables'],
                'rows'   => $info['rows'],
                'size'   => $this->humanSize($info['bytes']),
            ],
            'telegram' => [
                'hasToken' => (bool) SettingStore::get('backup.telegram_token'),
                'hasChat'  => (bool) SettingStore::get('backup.telegram_chat'),
            ],
            'weekly' => [
                'enabled'   => SettingStore::get('backup.weekly.enabled', '0') === '1',
                'dest'      => SettingStore::get('backup.weekly.dest', 'host') === 'telegram' ? 'telegram' : 'host',
                'lastAt'    => SettingStore::get('backup.weekly.last_at'),
                'result'    => json_decode((string) SettingStore::get('backup.weekly.result', ''), true) ?: null,
                'hostFiles' => collect(glob(storage_path('app/backups/liandid_weekly_*.sql')) ?: [])
                    ->sortByDesc(fn ($f) => filemtime($f))
                    ->map(fn ($f) => ['name' => basename($f), 'size' => $this->humanSize((int) filesize($f))])
                    ->values()->all(),
            ],
            'lastBackup' => SettingStore::get('backup.last_at'),
        ]);
    }

    /**
     * Streams the dump to the browser. The file is never written on the server,
     * so it is saved outside the hosting account (on the admin's machine).
     */
    public function download()
    {
        (new DatabaseBackup())->download(DatabaseBackup::filename());
        SettingStore::set('backup.last_at', now()->toDateTimeString());

        return response()->noContent();
    }

    /** Sends the dump to Telegram so it is stored off the host as well. */
    public function sendTelegram()
    {
        $result = (new DatabaseBackup())->sendToTelegram(DatabaseBackup::filename());

        if ($result['ok']) {
            SettingStore::set('backup.last_at', now()->toDateTimeString());

            return back()->with('success', 'پشتیبان با موفقیت به تلگرام ارسال شد.');
        }

        return back()->with('error', 'ارسال ناموفق: ' . $result['error']);
    }

    public function saveTelegram(Request $request)
    {
        $data = $request->validate([
            'token' => 'nullable|string|max:200',
            'chat'  => 'nullable|string|max:100',
        ]);

        if (! empty($data['token'])) SettingStore::set('backup.telegram_token', trim($data['token']));
        if (! empty($data['chat']))  SettingStore::set('backup.telegram_chat', trim($data['chat']));

        return back()->with('success', 'تنظیمات تلگرام ذخیره شد.');
    }

    /** یک کلیک: فعال/خاموش کردن بکاپ هفتگی + انتخاب مقصد (هاست یا تلگرام). */
    public function saveWeekly(Request $request)
    {
        $data = $request->validate([
            'on'   => 'required|boolean',
            'dest' => 'required|in:host,telegram',
        ]);

        if ($data['on'] && $data['dest'] === 'telegram'
            && ((string) SettingStore::get('backup.telegram_token', '') === ''
                || (string) SettingStore::get('backup.telegram_chat', '') === '')) {
            return back()->with('error', 'برای ذخیرهٔ هفتگی در تلگرام، اول «توکن ربات» و «شناسه چت» را در همین صفحه ذخیره کنید.');
        }

        \App\Support\WeeklyBackup::setConfig((bool) $data['on'], $data['dest']);

        return back()->with('success', $data['on']
            ? ($data['dest'] === 'telegram'
                ? 'بکاپ هفتگی فعال شد — هر هفته به تلگرام ارسال می‌شود.'
                : 'بکاپ هفتگی فعال شد — هر هفته روی همین هاست ذخیره می‌شود (۴ نسخهٔ آخر نگه داشته می‌شود).')
            : 'بکاپ هفتگی خاموش شد.');
    }

    /** دانلود یکی از نسخه‌های هفتگی ذخیره‌شده روی هاست. */
    public function downloadWeekly(Request $request)
    {
        $name = basename((string) $request->query('f', ''));
        $path = storage_path('app/backups/' . $name);

        if ($name === '' || ! str_starts_with($name, 'liandid_weekly_') || ! is_file($path)) {
            abort(404);
        }

        return response()->download($path, $name);
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $u;
            $bytes /= 1024;
        }
        return $bytes . ' TB';
    }
}
