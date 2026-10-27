<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('mfa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.mfa');
    }

    public function verify(Request $request, OtpService $otp)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = User::find($request->session()->get('mfa.user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $otp->verify($user, 'mfa_login', $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The code is incorrect or has expired.']);
        }

        // Only now are they logged in. No "remember me" for MFA roles.
        $request->session()->forget('mfa.user_id');
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function resend(Request $request, OtpService $otp)
    {
        $user = User::find($request->session()->get('mfa.user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        try {
            $otp->send($user, 'mfa_login');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['code' => "We couldn't send a new code. Please try again."]);
        }

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
