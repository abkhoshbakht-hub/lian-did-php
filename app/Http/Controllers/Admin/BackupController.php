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

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $u;
            $bytes /= 1024;
        }
        return $bytes . ' TB';
    }
}
