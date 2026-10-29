<?php

namespace Tests\Feature\Auth;

use App\Models\OneTimeCode;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\OneTimeCodeNotification;
use App\Services\TrustedDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeUser(string $roleCode): User
    {
        return User::factory()->role($roleCode)->create();   // password is "password"
    }

    private function login(User $user, array $extra = [])
    {
        return $this->post('/login', array_merge([
            'email' => $user->email,
            'password' => 'password',
        ], $extra));
    }

    /** The plain code only exists inside the email, so read it from the faked notification. */
    private function latestCode(User $user): string
    {
        return Notification::sent($user, OneTimeCodeNotification::class)->last()->code;
    }

    /** A 6-digit code that is guaranteed to be different from the real one. */
    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    /** Pretend this browser was trusted earlier: a device row whose token we know. */
    private function trustDevice(User $user, string $token, $expiresAt = null): TrustedDevice
    {
        return TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt ?? now()->addDays(30),
        ]);
    }

    // ---------------------------------------------------------------
    // Who gets MFA
    // ---------------------------------------------------------------

    public static function mfaRoles(): array
    {
        return [
            'system admin' => ['system_admin'],
            'branch manager' => ['branch_manager'],
        ];
    }

    #[DataProvider('mfaRoles')]
    public function test_mfa_roles_are_sent_to_the_code_page_instead_of_being_logged_in($roleCode): void
    {
        Notification::fake();

        $user = $this->makeUser($roleCode);

        $this->login($user)->assertRedirect(route('mfa.show'));

        $this->assertGuest();
        Notification::assertSentTo($user, OneTimeCodeNotification::class);
        $this->assertDatabaseHas('one_time_codes', [
            'user_id' => $user->id,
            'purpose' => 'mfa_login',
            'channel' => 'email',
        ]);
    }

    public function test_mfa_page_without_a_pending_login_redirects_to_login(): void
    {
        $this->get(route('mfa.show'))->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // Entering the code
    // ---------------------------------------------------------------

    public function test_correct_code_logs_the_user_in(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);

        $this->post(route('mfa.verify'), ['code' => $this->latestCode($admin)])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);
        $code = $this->latestCode($admin);

        $this->post(route('mfa.verify'), ['code' => $this->wrongCode($code)])
            ->assertSessionHasErrors(['code' => 'The code is incorrect or has expired.']);

        $this->assertGuest();
        $this->assertDatabaseHas('one_time_codes', [
            'user_id' => $admin->id,
            'attempts' => 1,
        ]);
    }

    public function test_expired_code_is_rejected(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);
        $code = $this->latestCode($admin);

        $this->travel(11)->minutes();

        $this->post(route('mfa.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'The code is incorrect or has expired.']);

        $this->assertGuest();
    }

    public function test_resend_replaces_the_old_code(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);
        $oldCode = $this->latestCode($admin);

        $this->post(route('mfa.resend'))
            ->assertSessionHas('status', 'A new code has been sent to your email.');

        $newCode = $this->latestCode($admin);

        // Two codes were created, and the first one has been marked as used.
        $this->assertSame(2, OneTimeCode::where('user_id', $admin->id)->count());
        $this->assertNotNull(
            OneTimeCode::where('user_id', $admin->id)->oldest('id')->first()->consumed_at
        );

        // The old code no longer works (skipped in the one-in-a-million case that both codes match).
        if ($oldCode !== $newCode) {
            $this->post(route('mfa.verify'), ['code' => $oldCode])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('mfa.verify'), ['code' => $newCode])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_code_is_stored_as_a_hash(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);
        $code = $this->latestCode($admin);

        $row = OneTimeCode::where('user_id', $admin->id)->latest('id')->first();

        $this->assertNotSame($code, $row->code_hash);
        $this->assertTrue(Hash::check($code, $row->code_hash));
    }

    public function test_user_is_not_logged_in_when_the_code_email_fails(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Mail server down'));

        $admin = $this->makeUser('system_admin');

        $this->login($admin)
            ->assertSessionHasErrors(['email' => "We couldn't send your sign-in code. Please try again."]);

        $this->assertGuest();
        $this->assertFalse(session()->has('mfa.user_id'));
    }

    // ---------------------------------------------------------------
    // Trusted devices
    // ---------------------------------------------------------------

    public function test_ticking_trust_device_stores_the_device_and_sets_the_cookie(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);

        $response = $this->post(route('mfa.verify'), [
            'code' => $this->latestCode($admin),
            'trust_device' => '1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertCookie(TrustedDeviceService::COOKIE);

        // The browser gets the token; the database only gets its sha256 hash.
        $token = $response->getCookie(TrustedDeviceService::COOKIE)->getValue();

        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('trusted_devices', ['token_hash' => $token]);
    }

    public function test_not_ticking_trust_device_stores_nothing(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');

        $this->login($admin);

        $this->post(route('mfa.verify'), ['code' => $this->latestCode($admin)])
            ->assertRedirect(route('admin.dashboard'))
            ->assertCookieMissing(TrustedDeviceService::COOKIE);

        $this->assertDatabaseMissing('trusted_devices', ['user_id' => $admin->id]);
    }

    public function test_trusted_device_skips_the_code(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');
        $this->trustDevice($admin, 'known-token');

        $this->withCookie(TrustedDeviceService::COOKIE, 'known-token');

        $this->login($admin)->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        Notification::assertNothingSent();
        $this->assertNotNull(TrustedDevice::where('user_id', $admin->id)->first()->last_used_at);
    }

    public function test_trusted_device_still_needs_the_correct_password(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');
        $this->trustDevice($admin, 'known-token');

        $this->withCookie(TrustedDeviceService::COOKIE, 'known-token');

        $this->login($admin, ['password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'These login details are incorrect.']);

        $this->assertGuest();
    }

    public function test_another_users_trusted_cookie_does_not_skip_the_code(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');
        $manager = $this->makeUser('branch_manager');
        $this->trustDevice($manager, 'managers-token');

        $this->withCookie(TrustedDeviceService::COOKIE, 'managers-token');

        $this->login($admin)->assertRedirect(route('mfa.show'));

        $this->assertGuest();
    }

    public function test_expired_trusted_device_does_not_skip_the_code(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');
        $this->trustDevice($admin, 'old-token', now()->subDay());

        $this->withCookie(TrustedDeviceService::COOKIE, 'old-token');

        $this->login($admin)->assertRedirect(route('mfa.show'));

        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // "Remember me"
    // ---------------------------------------------------------------

    public function test_remember_me_is_ignored_for_mfa_roles(): void
    {
        Notification::fake();

        $admin = $this->makeUser('system_admin');
        $this->trustDevice($admin, 'known-token');

        $this->withCookie(TrustedDeviceService::COOKIE, 'known-token');

        $this->login($admin, ['remember' => '1'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_remember_me_still_works_for_roles_without_mfa(): void
    {
        $client = $this->makeUser('client');

        $this->login($client, ['remember' => '1'])
            ->assertRedirect(route('client.dashboard'))
            ->assertCookie(Auth::guard()->getRecallerName());
    }
}
