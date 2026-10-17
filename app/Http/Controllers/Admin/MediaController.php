<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ImageProcessor;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Uploads an image, applying the saved image settings (size, aspect, format).
     * Nothing is stored when no image driver is available on the server.
     */
    public function upload(Request $request): JsonResponse
    {
        $maxMb = (float) (SettingStore::get('image_max_mb', '1') ?: 1);

        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:'.max(1, (int) ceil($maxMb * 1024)),
        ], [], ['file' => 'تصویر']);

        $result = ImageProcessor::process($request->file('file'), 'media');

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['error'] ?? 'بارگذاری تصویر ناموفق بود.',
            ], 422);
        }

        return response()->json([
            'path'    => $result['path'],
            'url'     => $result['url'],
            'width'   => $result['width'],
            'height'  => $result['height'],
            'format'  => $result['format'],
            'driver'  => $result['driver'],
            'message' => 'تصویر با موفقیت بارگذاری و بهینه شد.',
        ]);
    }
}