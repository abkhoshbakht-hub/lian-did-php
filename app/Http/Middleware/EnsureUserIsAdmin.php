<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isActive) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'حساب کاربری شما غیرفعال است.',
            ]);
        }

        if (! $user->isAdmin()) {
            abort(403, 'شما دسترسی به پنل مدیریت را ندارید.');
        }

        return $next($request);
    }
}
