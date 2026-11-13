<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\OneTimeCodeNotification;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeClient($phone = '7771234'): User
    {
        return User::factory()->role('client')->create(['phone' => $phone]);
    }

    /** Press "Send code" on the phone verification page. */
    private function sendCode(User $user)
    {
        return $this->actingAs($user)
            ->from(route('phone.verification.show'))
            ->post(route('phone.verification.send'));
    }

    /** Submit a code on the phone verification page. */
    private function verifyCode(User $user, string $code)
    {
        return $this->actingAs($user)
            ->from(route('phone.verification.show'))
            ->post(route('phone.verification.verify'), ['code' => $code]);
    }

    /** The plain code only exists inside the SMS, so read it from the faked notification. */
    private function latestCode(User $user): string
    {
        return Notification::sent($user, OneTimeCodeNotification::class)->last()->code;
    }

    /** A 6-digit code that is guaranteed to be different from the real one. */
    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    // ---------------------------------------------------------------
    // Access
    // ---------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('phone.verification.show'))->assertRedirect(route('login'));
        $this->post(route('phone.verification.send'))->assertRedirect(route('login'));
        $this->post(route('phone.verification.verify'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_verification_page_loads(): void
    {
        $this->actingAs($this->makeClient())
            ->get(route('phone.verification.show'))
            ->assertOk();
    }

    // ---------------------------------------------------------------
    // Sending a code
    // ---------------------------------------------------------------

    public function test_sending_a_code_texts_it_to_the_users_phone(): void
    {
        Notification::fake();
        $user = $this->makeClient();

        $this->sendCode($user)
            ->assertRedirect(route('phone.verification.show'))
            ->assertSessionHas('status', 'We sent a 6-digit code to your phone.');

        Notification::assertSentTo(
            $user,
            OneTimeCodeNotification::class,
            fn ($notification) => $notification->channel === 'sms'
        );

        $this->assertDatabaseHas('one_time_codes', [
            'user_id' => $user->id,
            'purpose' => 'phone_verification',
            'channel' => 'sms',
        ]);
    }

    public function test_already_verified_phone_gets_no_code(): void
    {
        Notification::fake();
        $user = User::factory()->role('client')->create([
            'phone' => '7771234',
            'phone_verified_at' => now(),
        ]);

        $this->sendCode($user)
            ->assertSessionHas('status', 'Your phone is already verified.');

        Notification::assertNothingSent();
    }

    public function test_user_without_a_phone_gets_an_error_and_no_code(): void
    {
        Notification::fake();
        $user = $this->makeClient(null);

        $this->sendCode($user)
            ->assertSessionHasErrors(['code' => 'Add a phone number to your account first.']);

        Notification::assertNothingSent();
    }

    public function test_a_second_code_within_a_minute_is_refused(): void
    {
        Notification::fake();
        $user = $this->makeClient();

        $this->sendCode($user)->assertSessionHasNoErrors();

        $this->sendCode($user)
            ->assertSessionHasErrors(['code' => 'Please wait a minute before asking for another code.']);

        Notification::assertSentToTimes($user, OneTimeCodeNotification::class, 1);
    }

    public function test_no_more_than_five_codes_in_24_hours(): void
    {
        Notification::fake();
        $user = $this->makeClient();
        $otp = app(OtpService::class);

        // Five codes, two minutes apart, so the one-per-minute rule never blocks them.
        for ($i = 0; $i < 5; $i++) {
            $otp->send($user, 'phone_verification', 'sms');
            $this->travel(2)->minutes();
        }

        $this->sendCode($user)
            ->assertSessionHasErrors(['code' => "You've reached the limit of 5 codes in 24 hours. Please try again later."]);

        Notification::assertSentToTimes($user, OneTimeCodeNotification::class, 5);
    }

    public function test_codes_older_than_24_hours_do_not_count_towards_the_limit(): void
    {
        Notification::fake();
        $user = $this->makeClient();
        $otp = app(OtpService::class);

        for ($i = 0; $i < 5; $i++) {
            $otp->send($user, 'phone_verification', 'sms');
            $this->travel(2)->minutes();
        }

        $this->travel(25)->hours();

        $this->sendCode($user)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'We sent a 6-digit code to your phone.');

        Notification::assertSentToTimes($user, OneTimeCodeNotification::class, 6);
    }

    public function test_failed_sms_shows_a_friendly_error_without_the_real_reason(): void
    {
        config([
            'services.sms.driver' => 'msgowl',
            'services.msgowl.access_key' => 'test-key',
            'services.msgowl.sender_id' => 'H4R',
        ]);
        Http::fake([
            'rest.msgowl.com/*' => Http::response(['message' => 'Insufficient balance'], 402),
        ]);

        $user = $this->makeClient();

        // The exact message proves the user never sees "Insufficient balance".
        $this->sendCode($user)
            ->assertRedirect(route('phone.verification.show'))
            ->assertSessionHasErrors(['code' => "We couldn't send the code. Please try again later."]);

        Http::assertSentCount(1);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    // ---------------------------------------------------------------
    // Entering the code
    // ---------------------------------------------------------------

    public function test_correct_code_verifies_the_phone(): void
    {
        Notification::fake();
        $user = $this->makeClient();

        $this->sendCode($user);

        $this->verifyCode($user, $this->latestCode($user))
            ->assertRedirect(route('client.dashboard'))
            ->assertSessionHas('status', 'Phone verified.');

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_wrong_code_does_not_verify_the_phone(): void
    {
        Notification::fake();
        $user = $this->makeClient();

        $this->sendCode($user);

        $this->verifyCode($user, $this->wrongCode($this->latestCode($user)))
            ->assertRedirect(route('phone.verification.show'))
            ->assertSessionHasErrors(['code' => 'The code is incorrect or has expired.']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_code_must_be_six_digits(): void
    {
        $user = $this->makeClient();

        $this->verifyCode($user, '12345')->assertSessionHasErrors('code');
        $this->verifyCode($user, 'abcdef')->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_a_sign_in_code_cannot_verify_a_phone(): void
    {
        Notification::fake();
        $user = $this->makeClient();

        // A code for a different purpose (MFA sign-in) must not work here.
        app(OtpService::class)->send($user, 'mfa_login');

        $this->verifyCode($user, $this->latestCode($user))
            ->assertSessionHasErrors(['code' => 'The code is incorrect or has expired.']);

        $this->assertNull($user->fresh()->phone_verified_at);
    }
}
