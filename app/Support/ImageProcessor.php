<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resizes / converts uploaded images according to the saved image settings.
 * Uses Imagick when available, otherwise GD, otherwise stores the original.
 */
class ImageProcessor
{
    /** @return array{ok:bool, path?:string, url?:string, driver?:string, error?:string} */
    public static function process(UploadedFile $file, ?string $subdir = null): array
    {
        $settings = SettingStore::imageSettings();

        if (! self::driver()) {
            return ['ok' => false, 'error' => 'هیچ کتابخانه پردازش تصویری (GD یا Imagick) روی سرور فعال نیست.'];
        }

        try {
            $image = self::read($file->getRealPath());

            if ($image === null) {
                return ['ok' => false, 'error' => 'فایل تصویر خوانده نشد.'];
            }

            [$width, $height] = $image['size'];

            $maxDim = (int) ($settings['image_max_dim'] ?? 1600);
            $aspect = $settings['image_aspect'] ?? 'free';

            [$tw, $th] = self::targetSize($width, $height, $maxDim, $aspect);
            $tw = max(1, $tw);
            $th = max(1, $th);

            $format = $settings['image_format'] ?? 'webp';
            $quality = (int) ($settings['image_quality'] ?? 82);

            if ($format === 'original') {
                $format = self::extToFormat($file->getClientOriginalExtension());
            }

            $name = Str::random(24).'.'.self::formatToExt($format);
            $dir = trim((string) ($subdir ?? 'media'), '/');
            $rel = ($dir !== '' ? $dir.'/' : '').$name;

            Storage::disk('public')->makeDirectory($dir);

            if (! self::write($image['handle'], $rel, $format, $quality, $tw, $th)) {
                return ['ok' => false, 'error' => 'ذخیره تصویر ناموفق بود.'];
            }

            return [
                'ok'     => true,
                'path'   => $rel,
                'url'    => '/storage/'.$rel,
                'driver' => self::driver(),
                'width'  => $tw,
                'height' => $th,
                'format' => $format,
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function driver(): ?string
    {
        if (extension_loaded('imagick')) {
            return 'imagick';
        }
        if (extension_loaded('gd')) {
            return 'gd';
        }

        return null;
    }

    private static function read(string $path): ?array
    {
        $driver = self::driver();

        if ($driver === 'imagick') {
            try {
                $im = new \Imagick($path);
                $im->setIteratorIndex(0);
                $s = $im->getImageGeometry();

                return ['handle' => $im, 'size' => [$s['width'], $s['height']]];
            } catch (\Throwable) {
                return null;
            }
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }
        $res = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };
        if ($res === false || $res === null) {
            return null;
        }

        return ['handle' => $res, 'size' => [$info[0], $info[1]]];
    }

    /** @return array{0:int,1:int} */
    private static function targetSize(int $w, int $h, int $maxDim, string $aspect): array
    {
        if ($aspect === 'free') {
            $scale = min(1.0, $maxDim / max($w, $h));

            return [(int) round($w * $scale), (int) round($h * $scale)];
        }

        [$aw, $ah] = array_map('intval', explode(':', $aspect));
        $target = $aw / $ah;

        // Fit inside the aspect box without cropping.
        if ($w / $h > $target) {
            $nw = min($w, $maxDim);
            $nh = (int) round($nw / $target);
        } else {
            $nh = min($h, $maxDim);
            $nw = (int) round($nh * $target);
        }

        return [$nw, $nh];
    }

    private static function write($handle, string $rel, string $format, int $quality, int $tw, int $th): bool
    {
        $target = Storage::disk('public')->path($rel);

        if (self::driver() === 'imagick') {
            /** @var \Imagick $handle */
            $out = clone $handle;
            $out->setIteratorIndex(0);
            $out->thumbnailImage($tw, $th);
            $out->setImageFormat($format);
            $out->setImageCompressionQuality($quality);
            if ($format === 'jpeg') {
                $out->setImageBackgroundColor('white');
            }
            $ok = $out->writeImage($target);
            $out->clear();
            $out->destroy();

            return (bool) $ok;
        }

        $res = imagecreatetruecolor($tw, $th);
        if ($format === 'jpeg' || $format === 'webp') {
            imagealphablending($res, false);
            imagesavealpha($res, true);
            $transparent = imagecolorallocatealpha($res, 255, 255, 255, 127);
            imagefilledrectangle($res, 0, 0, $tw, $th, $transparent);
        }
        imagecopyresampled($res, $handle, 0, 0, 0, 0, $tw, $th, imagesx($handle), imagesy($handle));

        $ok = match ($format) {
            'jpeg'  => imagejpeg($res, $target, $quality),
            'webp'  => imagewebp($res, $target, $quality),
            'png'   => imagepng($res, $target, (int) round(9 - ($quality / 100 * 9))),
            default => false,
        };

        imagedestroy($res);

        if ($ok) {
            $mirror = $_SERVER['HOME'] ?? '/home/liandidir';
            $pubTarget = $mirror.'/public_html/storage/'.$rel;
            @mkdir(dirname($pubTarget), 0755, true);
            @copy($target, $pubTarget);
        }

        return $ok;
    }

    private static function extToFormat(string $ext): string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'jpeg',
            'png'        => 'png',
            'webp'       => 'webp',
            'gif'        => 'jpeg',
            default      => 'jpeg',
        };
    }

    private static function formatToExt(string $format): string
    {
        return match ($format) {
            'jpeg' => 'jpg',
            'png'  => 'png',
            'webp' => 'webp',
            default => 'jpg',
        };
    }
}