<?php

namespace App\Support;

/**
 * Reports what the hosting account must provide before the site can move.
 * Run from the admin panel, not from the browser, so it sees the real server.
 */
class ServerHealth
{
    public static function run(): array
    {
        $checks = [];

        $checks['php_version'] = [
            'ok'   => version_compare(PHP_VERSION, '8.2.0', '>='),
            'want' => '>= 8.2',
            'got'  => PHP_VERSION,
        ];

        $checks['database'] = [
            'ok'   => in_array(config('database.default'), ['mysql', 'mariadb'], true),
            'want' => 'mysql/mariadb',
            'got'  => config('database.default'),
        ];

        $checks['gd'] = [
            'ok'   => extension_loaded('gd'),
            'want' => 'enabled',
            'got'   => extension_loaded('gd') ? 'enabled' : 'missing',
        ];

        $checks['imagick'] = [
            'ok'   => true,
            'want' => 'optional',
            'got'   => extension_loaded('imagick') ? 'enabled' : 'not installed (GD is used instead)',
        ];

        $checks['pdo_mysql'] = [
            'ok'   => extension_loaded('pdo_mysql'),
            'want' => 'enabled',
            'got'   => extension_loaded('pdo_mysql') ? 'enabled' : 'missing',
        ];

        $checks['mbstring'] = [
            'ok'   => extension_loaded('mbstring'),
            'want' => 'enabled',
            'got'   => extension_loaded('mbstring') ? 'enabled' : 'missing',
        ];

        $checks['openssl'] = [
            'ok'   => extension_loaded('openssl'),
            'want' => 'enabled',
            'got'   => extension_loaded('openssl') ? 'enabled' : 'missing',
        ];

        $checks['curl'] = [
            'ok'   => extension_loaded('curl'),
            'want' => 'enabled',
            'got'   => extension_loaded('curl') ? 'enabled' : 'missing',
        ];

        $checks['fileinfo'] = [
            'ok'   => extension_loaded('fileinfo'),
            'want' => 'enabled',
            'got'   => extension_loaded('fileinfo') ? 'enabled' : 'missing',
        ];

        $checks['storage_writable'] = [
            'ok'   => is_writable(storage_path('app/public')),
            'want' => 'writable',
            'got'   => is_writable(storage_path('app/public')) ? 'writable' : 'NOT writable',
        ];

        // is_link() does not detect Windows junctions, so fall back to is_dir().
        $storage = public_path('storage');
        $checks['public_storage_link'] = [
            'ok'   => is_dir($storage) && is_writable($storage),
            'want' => 'linked and writable',
            'got'   => is_dir($storage) ? (is_writable($storage) ? 'linked, writable' : 'linked, NOT writable') : 'missing',
        ];

        $checks['max_execution_time'] = [
            'ok'   => (int) ini_get('max_execution_time') >= 60 || (int) ini_get('max_execution_time') === 0,
            'want' => '>= 60s',
            'got'   => ini_get('max_execution_time') . 's',
        ];

        $checks['memory_limit'] = [
            'ok'   => self::bytes(ini_get('memory_limit')) >= 128 * 1024 * 1024,
            'want' => '>= 128M',
            'got'   => ini_get('memory_limit'),
        ];

        $checks['upload_max_filesize'] = [
            'ok'   => self::bytes(ini_get('upload_max_filesize')) >= 2 * 1024 * 1024,
            'want' => '>= 2M',
            'got'   => ini_get('upload_max_filesize'),
        ];

        $checks['cron_token'] = [
            'ok'   => CronToken::get() !== '',
            'want' => 'generated',
            'got'   => CronToken::get() !== '' ? 'generated' : 'missing',
        ];

        $failed = array_filter($checks, fn ($c) => ! $c['ok']);

        return [
            'ok'     => $failed === [],
            'checks' => $checks,
            'failed' => array_keys($failed),
            'php'    => PHP_VERSION,
            'time'   => now()->toDateTimeString(),
        ];
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($v, -1));
        $n = (int) $v;

        return match ($unit) {
            'g' => $n * 1024 * 1024 * 1024,
            'm' => $n * 1024 * 1024,
            'k' => $n * 1024,
            default => $n,
        };
    }
}