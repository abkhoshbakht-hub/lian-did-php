<?php

namespace App\Support;

/**
 * Shared secret for the cron URLs.
 *
 * Shared hosting cron jobs can only call a URL, so every scheduled task
 * hangs off one token. It is generated once and stored outside the repo.
 */
class CronToken
{
    public const SETTING = 'cron.token';

    public static function get(): string
    {
        $t = (string) SettingStore::get(self::SETTING, '');

        if ($t === '') {
            $t = self::generate();
            SettingStore::set(self::SETTING, $t);
        }

        return $t;
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function valid(string $candidate): bool
    {
        $real = self::get();

        return $real !== '' && $candidate !== '' && hash_equals($real, $candidate);
    }

    public static function rotate(): string
    {
        $t = self::generate();
        SettingStore::set(self::SETTING, $t);

        return $t;
    }
}