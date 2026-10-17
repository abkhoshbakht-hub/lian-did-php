<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Changes the signed-in admin's password.
     * The admin panel form posts here with _method=PUT.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'وارد حساب نشده‌اید.'], 401);
        }

        // The panel form posts a field literally named "confirmation", while
        // Laravel's confirmed rule looks for password_confirmation.
        if (! $request->filled('password_confirmation') && $request->filled('confirmation')) {
            $request->merge(['password_confirmation' => $request->input('confirmation')]);
        }

        $v = $request->validate([
            'current'  => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current.required'   => 'رمز فعلی را وارد کنید.',
            'password.required'  => 'رمز جدید را وارد کنید.',
            'password.min'       => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز با رمز جدید یکسان نیست.',
        ]);

        if (! Hash::check($v['current'], $user->password)) {
            return response()->json([
                'message' => 'رمز فعلی درست نیست.',
                'errors'  => ['current' => ['رمز فعلی درست نیست.']],
            ], 422);
        }

        if (Hash::check($v['password'], $user->password)) {
            return response()->json([
                'message' => 'رمز جدید نباید با رمز فعلی یکسان باشد.',
                'errors'  => ['password' => ['رمز جدید نباید با رمز فعلی یکسان باشد.']],
            ], 422);
        }

        $user->forceFill(['password' => Hash::make($v['password'])])->save();

        return response()->json(['message' => 'رمز عبور با موفقیت تغییر کرد.']);
    }
}