<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            'If that email is registered, we\'ve sent a reset link.'
        );
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function showInvite(Request $request, string $token)
    {
        return view('auth.staff-invite', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request)
    {
        return $this->resetWith(
            $request,
            'users',
            'Your password has been reset. You can sign in now.'
        );
    }

    public function acceptInvite(Request $request)
    {
        return $this->resetWith(
            $request,
            'staff_invites',
            'Your staff account is ready. You can sign in now.'
        );
    }

    private function resetWith(
        Request $request,
        string $brokerName,
        string $successMessage
    ) {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::defaults(),
            ],
        ]);

        $status = Password::broker($brokerName)->reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, string $password) {
                $user->password = $password;

                // Invalidate remembered devices.
                $user->setRememberToken(Str::random(60));

                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                $user->save();

                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();

                $user->trustedDevices()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', $successMessage);
        }

        $errorMessage = $brokerName === 'staff_invites'
            ? 'This invitation link is invalid or has expired.'
            : 'This reset link is invalid or has expired.';

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => $errorMessage,
            ]);
    }
}
