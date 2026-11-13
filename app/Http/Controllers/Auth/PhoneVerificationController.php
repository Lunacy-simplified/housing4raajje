<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    const DAILY_LIMIT = 5;

    public function show()
    {
        return view('auth.verify-phone');
    }

    public function send(Request $request, OtpService $otp)
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return back()->with('status', 'Your phone is already verified.');
        }

        if (! $user->phone) {
            return back()->withErrors(['code' => 'Add a phone number to your account first.']);
        }

        $sentInLastMinute = $user->oneTimeCodes()
            ->where('purpose', 'phone_verification')
            ->where('created_at', '>', now()->subMinute())
            ->exists();

        if ($sentInLastMinute) {
            return back()->withErrors(['code' => 'Please wait a minute before asking for another code.']);
        }

        $sentToday = $user->oneTimeCodes()
            ->where('purpose', 'phone_verification')
            ->where('created_at', '>', now()->subDay())
            ->count();

        if ($sentToday >= self::DAILY_LIMIT) {
            return back()->withErrors(['code' => "You've reached the limit of 5 codes in 24 hours. Please try again later."]);
        }

        try {
            $otp->send($user, 'phone_verification', 'sms');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['code' => "We couldn't send the code. Please try again later."]);
        }

        return back()->with('status', 'We sent a 6-digit code to your phone.');
    }

    public function verify(Request $request, OtpService $otp)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route($user->dashboardRoute())
                ->with('status', 'Your phone is already verified.');
        }

        if (! $otp->verify($user, 'phone_verification', $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'The code is incorrect or has expired.']);
        }

        // phone_verified_at is not fillable on purpose, so no form can set it.
        $user->forceFill(['phone_verified_at' => now()])->save();

        return redirect()->route($user->dashboardRoute())
            ->with('status', 'Phone verified.');
    }
}
