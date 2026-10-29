<?php

namespace App\Services;

use App\Models\OneTimeCode;
use App\Models\User;
use App\Notifications\OneTimeCodeNotification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    const EXPIRY_MINUTES = 10;
    const MAX_ATTEMPTS = 5;

    public function send(User $user, $purpose, $channel = 'email')
    {
        // Any older unused code stops working: only the newest one counts.
        OneTimeCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        // random_int is cryptographically secure; rand() is guessable.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OneTimeCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => $channel,
            'code_hash' => Hash::make($code),   // only the hash is stored
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $user->notify(new OneTimeCodeNotification($code, self::EXPIRY_MINUTES));
    }

    public function verify(User $user, $purpose, $code)
    {
        $otp = OneTimeCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }
}
