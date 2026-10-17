<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Key/value settings backed by the existing `site_settings` table.
 */
class SettingStore
{
    public const IMAGE_DEFAULTS = [
        'image_max_mb'  => '1',
        'image_quality' => '75',
        'image_max_dim' => '1280',
        'image_aspect'  => '16:9',
        'image_format'  => 'webp',
    ];

    public static function all(): array
    {
        return DB::table('site_settings')->pluck('value', 'key')->all();
    }

    public static function get(string $key, $default = null)
    {
        $row = DB::table('site_settings')->where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function set(string $key, $value): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
             'updated_at' => now()]
        );
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            if ($v !== null && $v !== '') self::set($k, $v);
        }
    }

    public static function setGroup(string $group, array $values): void
    {
        foreach ($values as $k => $v) {
            self::set("{$group}.{$k}", is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v);
        }
    }

    public static function group(string $group): array
    {
        $prefix = $group . '.';
        $out = [];

        foreach (self::all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $out[substr($key, strlen($prefix))] = $value;
            }
        }

        return $out;
    }

    public static function imageSettings(): array
    {
        $current = self::group('image');

        // stored values arrive as `image_max_mb` style keys
        $out = [];
        foreach (self::IMAGE_DEFAULTS as $key => $default) {
            $short = str_replace('image_', '', $key);
            $out[$key] = $current[$short] ?? $current[$key] ?? $default;
        }

        return $out;
    }
}
