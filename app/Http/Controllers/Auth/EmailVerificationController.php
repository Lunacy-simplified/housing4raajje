<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(
        EmailVerificationRequest $request
    ): RedirectResponse {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()
                ->route($request->user()->dashboardRoute())
                ->with('status', 'Email already verified.');
        }

        $request->fulfill();

        return redirect()
            ->route($request->user()->dashboardRoute())
            ->with('status', 'Email verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with(
            'status',
            'A new verification link has been sent to your email address.'
        );
    }
}
