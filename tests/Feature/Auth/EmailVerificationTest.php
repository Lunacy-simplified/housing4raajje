<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function unverifiedClient(): User
    {
        return User::factory()
            ->role('client')
            ->unverified()
            ->create();
    }

    private function verificationUrl(User $user, ?\DateTimeInterface $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiration ?? now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    public function test_valid_link_verifies_email(): void
    {
        $user = $this->unverifiedClient();

        $this->actingAs($user)
            ->get($this->verificationUrl($user))
            ->assertRedirect(route('client.dashboard'))
            ->assertSessionHas('status', 'Email verified.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_wrong_hash_is_rejected(): void
    {
        $user = $this->unverifiedClient();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('wrong@example.com'),
            ]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = $this->unverifiedClient();

        $url = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = $this->unverifiedClient();

        $this->actingAs($user)
            ->get($this->verificationUrl($user, now()->subMinute()))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_another_users_verification_link_is_rejected(): void
    {
        $userA = $this->unverifiedClient();
        $userB = $this->unverifiedClient();

        $this->actingAs($userB)
            ->get($this->verificationUrl($userA))
            ->assertForbidden();

        $this->assertFalse($userA->fresh()->hasVerifiedEmail());
        $this->assertFalse($userB->fresh()->hasVerifiedEmail());
    }

    public function test_guest_clicking_verification_link_is_redirected_to_login(): void
    {
        $user = $this->unverifiedClient();

        $this->get($this->verificationUrl($user))
            ->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = $this->unverifiedClient();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_does_not_receive_another_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()
            ->role('client')
            ->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Email already verified.');

        Notification::assertNothingSent();
    }

    public function test_unverified_user_can_access_client_dashboard(): void
    {
        $user = $this->unverifiedClient();

        $this->actingAs($user)
            ->get('/client')
            ->assertOk();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
