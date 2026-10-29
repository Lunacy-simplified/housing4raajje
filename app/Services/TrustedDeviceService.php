<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class TrustedDeviceService
{
    const COOKIE = 'h4r_trusted_device';
    const DAYS = 30;

    public function trust(Request $request, User $user)
    {
        $token = Str::random(64);

        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'expires_at' => now()->addDays(self::DAYS),
        ]);

        Cookie::queue(self::COOKIE, $token, 60 * 24 * self::DAYS);
    }

    public function isTrusted(Request $request, User $user)
    {
        $token = $request->cookie(self::COOKIE);

        if (! $token) {
            return false;
        }

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }
}
