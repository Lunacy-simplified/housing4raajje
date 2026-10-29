<?php

namespace Tests\Feature\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_forgot_password_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertSee('Forgot Password');
    }

    public function test_reset_password_page_is_accessible_with_a_token(): void
    {
        $response = $this->get('/reset-password/example-token?email=client%40h4r.local');

        $response->assertOk();
        $response->assertSee('Reset Password');
    }

    public function test_registered_email_receives_a_password_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()
            ->role('client')
            ->create([
                'email' => 'client@h4r.local',
            ]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            'status',
            'If that email is registered, we\'ve sent a reset link.'
        );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_receives_the_same_generic_response(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'unknown@h4r.local',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            'status',
            'If that email is registered, we\'ve sent a reset link.'
        );

        Notification::assertNothingSent();
    }

    public function test_email_address_is_case_insensitive(): void
    {
        Notification::fake();

        $user = User::factory()
            ->role('client')
            ->create([
                'email' => 'client@h4r.local',
            ]);

        $response = $this->post('/forgot-password', [
            'email' => 'CLIENT@H4R.LOCAL',
        ]);

        $response->assertSessionHas(
            'status',
            'If that email is registered, we\'ve sent a reset link.'
        );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_page_displays_the_email_address(): void
    {
        $user = User::factory()
            ->role('client')
            ->create([
                'email' => 'client@h4r.local',
            ]);

        $token = app('auth.password.broker')->createToken($user);

        $response = $this->get(
            '/reset-password/' . $token . '?email=' . urlencode($user->email)
        );

        $response->assertOk();
        $response->assertSee($user->email);
    }


    public function test_valid_token_resets_the_password(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(
            'status',
            'Your password has been reset. You can sign in now.'
        );

        $this->assertTrue(
            Hash::check('NewPass123', $user->fresh()->password)
        );
    }

    public function test_reset_token_can_only_be_used_once(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $response = $this->from('/reset-password/' . $token . '?email=' . urlencode($user->email))
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'AnotherPass456',
                'password_confirmation' => 'AnotherPass456',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertTrue(
            Hash::check('NewPass123', $user->fresh()->password)
        );
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'made-up-token',
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertTrue(
            Hash::check('OldPass123', $user->fresh()->password)
        );
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $token = Password::createToken($user);

        $this->travel(16)->minutes();

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertTrue(
            Hash::check('OldPass123', $user->fresh()->password)
        );
    }

    public function test_weak_or_mismatched_passwords_are_rejected(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $token = Password::createToken($user);

        // A weak password should fail validation.
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');

        // A mismatched confirmation should also fail validation.
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'DifferentPass123',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check('OldPass123', $user->fresh()->password)
        );
    }

    public function test_reset_form_does_not_reveal_unknown_emails(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'made-up-token',
            'email' => 'unknown@h4r.local',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This reset link is invalid or has expired.',
        ]);
    }


    public function test_password_reset_verifies_an_unverified_email(): void
    {
        $user = User::factory()->role('client')->unverified()->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $this->assertFalse($user->hasVerifiedEmail());

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_password_reset_invalidates_other_database_sessions(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        DB::table('sessions')->insert([
            'id' => 'other-device',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PasswordResetTest',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('sessions', [
            'id' => 'other-device',
            'user_id' => $user->id,
        ]);
    }

    public function test_password_reset_changes_the_remember_token(): void
    {
        $user = User::factory()->role('client')->create([
            'email' => 'client@h4r.local',
            'password' => 'OldPass123',
        ]);

        $oldRememberToken = $user->remember_token;
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ]);

        $response->assertRedirect(route('login'));

        $user->refresh();

        $this->assertNotSame(
            $oldRememberToken,
            $user->remember_token
        );

        $this->assertNotEmpty($user->remember_token);
    }

    public function test_password_reset_removes_trusted_devices(): void
    {
        $user = User::factory()->role('system_admin')->create([
            'email' => 'admin@h4r.local',
            'password' => 'OldPass123',
        ]);

        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'known-token'),
            'expires_at' => now()->addDays(30),
        ]);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('trusted_devices', ['user_id' => $user->id]);
    }

    public function test_logged_in_users_cannot_access_forgot_password_page(): void
    {
        $user = User::factory()->role('client')->create();

        $response = $this->actingAs($user)->get('/forgot-password');

        $response->assertRedirect(route('client.dashboard'));
    }
}
