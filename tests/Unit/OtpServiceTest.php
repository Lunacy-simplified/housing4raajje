<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\OneTimeCodeNotification;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests OtpService directly instead of through the /mfa page,
 * so the page's rate limit (5 requests a minute) doesn't get in the way.
 */
class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private OtpService $otp;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->otp = app(OtpService::class);
        $this->user = User::factory()->role('system_admin')->create();
    }

    /** Send a code and return the plain code from the faked email. */
    private function sendCode(string $purpose = 'mfa_login'): string
    {
        $this->otp->send($this->user, $purpose);

        return Notification::sent($this->user, OneTimeCodeNotification::class)->last()->code;
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_codes_are_six_digits(): void
    {
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->sendCode());
    }

    public function test_a_code_works_only_once(): void
    {
        $code = $this->sendCode();

        $this->assertTrue($this->otp->verify($this->user, 'mfa_login', $code));
        $this->assertFalse($this->otp->verify($this->user, 'mfa_login', $code));
    }

    public function test_code_is_locked_after_five_wrong_attempts(): void
    {
        $code = $this->sendCode();

        for ($i = 0; $i < OtpService::MAX_ATTEMPTS; $i++) {
            $this->assertFalse($this->otp->verify($this->user, 'mfa_login', $this->wrongCode($code)));
        }

        // Even the right code no longer works.
        $this->assertFalse($this->otp->verify($this->user, 'mfa_login', $code));
    }

    public function test_four_wrong_attempts_still_allow_the_right_code(): void
    {
        $code = $this->sendCode();

        for ($i = 0; $i < OtpService::MAX_ATTEMPTS - 1; $i++) {
            $this->otp->verify($this->user, 'mfa_login', $this->wrongCode($code));
        }

        $this->assertTrue($this->otp->verify($this->user, 'mfa_login', $code));
    }

    public function test_code_for_one_purpose_cannot_be_used_for_another(): void
    {
        $code = $this->sendCode('mfa_login');

        $this->assertFalse($this->otp->verify($this->user, 'phone_verification', $code));
        $this->assertTrue($this->otp->verify($this->user, 'mfa_login', $code));
    }
}
