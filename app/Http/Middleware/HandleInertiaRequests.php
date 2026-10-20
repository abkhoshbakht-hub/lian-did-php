<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = [];
        $calendarEvents = null;
        $calendarHolidays = null;
        $categories = [];

        try {
            $settings = \DB::table('site_settings')->pluck('value', 'key')->toArray();

            if (!empty($settings['calendarEvents'])) {
                $d = json_decode($settings['calendarEvents'], true);
                if (is_array($d) && count($d) > 0) $calendarEvents = $d;
            }
            if (!empty($settings['calendarHolidays'])) {
                $d = json_decode($settings['calendarHolidays'], true);
                if (is_array($d) && count($d) > 0) $calendarHolidays = $d;
            }

            $categories = \DB::table('categories')
                ->orderBy('order')
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug])
                ->toArray();
        } catch (\Throwable $e) {
            // DB unavailable — components fall back to built-in defaults
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'settings' => $settings,
            'calendarEvents' => $calendarEvents,
            'calendarHolidays' => $calendarHolidays,
            'categories' => $categories,
        ];
    }
}
