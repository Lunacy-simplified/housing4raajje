<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Services\TrustedDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    // authentication
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));

        // Only active acc can log in.
        $credentials['is_active'] = true;

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages(['email' => 'These login details are incorrect.']);
        }

        $user = Auth::getLastAttempted();   // the user whose password just matched

        if ($user->requiresMfa() && ! app(TrustedDeviceService::class)->isTrusted($request, $user)) {
            try {
                app(OtpService::class)->send($user, 'mfa_login');
            } catch (\Throwable $e) {
                report($e);
                throw ValidationException::withMessages(['email' => "We couldn't send your sign-in code. Please try again."]);
            }

            $request->session()->put('mfa.user_id', $user->id);   // remember who is mid-login

            return redirect()->route('mfa.show');
        }

        $remember = ! $user->requiresMfa() && $request->boolean('remember');
        Auth::login($user, $remember);

        // Protect against session fixation after login.
        $request->session()->regenerate();

        // record the successful login timestamp.
        $request->user()
            ->forceFill(['last_login_at' => now()])
            ->save();

        return redirect()->intended(
            route($request->user()->dashboardRoute())
        );
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
