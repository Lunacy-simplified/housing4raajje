<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class StaffInviteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()
            ->role('system_admin')
            ->create();
    }

    private function validForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Hassan Ali',
            'email' => 'hassan@h4r.local',
            'phone' => '7781234',
            'role' => 'property_specialist',
            'branch_id' => Branch::where('code', 'MLE')->value('id'),
            'job_title' => 'Lettings Specialist',
            'hired_on' => '2026-01-15',
        ], $overrides);
    }

    /**
     * A staff member who has been invited but has not set a password yet.
     * Factory users are verified by default, so unverified() makes them "pending".
     */
    private function makePendingStaff(string $name = 'Pending Staff'): User
    {
        $user = User::factory()
            ->role('property_specialist')
            ->unverified()
            ->create(['name' => $name]);

        $user->staffProfile()->create([
            'staff_code' => sprintf('H4R-STF-%04d', $user->id),
            'branch_id' => Branch::where('code', 'MLE')->value('id'),
        ]);

        return $user;
    }

    /** A valid invite token, made the same way StaffController makes one. */
    private function inviteToken(User $user): string
    {
        return Password::broker('staff_invites')->createToken($user);
    }

    private function acceptInvite(User $user, string $token, string $password = 'NewPass123')
    {
        return $this->post(route('staff-invite.accept'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    // ---------------------------------------------------------------
    // Creating staff sends an invite
    // ---------------------------------------------------------------

    public function test_creating_staff_sends_an_invitation(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->validForm());

        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'hassan@h4r.local')->firstOrFail();

        Notification::assertSentTo($user, StaffInvitation::class);

        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_invitation_email_links_to_the_invite_page(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->validForm());

        $user = User::where('email', 'hassan@h4r.local')->firstOrFail();

        Notification::assertSentTo(
            $user,
            StaffInvitation::class,
            function (StaffInvitation $notification) use ($user) {
                // Build the email exactly as Laravel would, then look at its button link.
                $url = $notification->toMail($user)->actionUrl;

                return str_contains($url, '/staff-invite/')
                    && str_contains($url, urlencode($user->email));
            }
        );
    }

    public function test_staff_account_is_still_created_when_the_invite_email_fails(): void
    {
        // Make the email sending itself fail, as if the mail server were down.
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Mail server down'));

        $response = $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->validForm());

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas(
            'status',
            fn($status) => str_contains($status, 'Resend invite')
        );

        $this->assertDatabaseHas('users', ['email' => 'hassan@h4r.local']);
    }

    // ---------------------------------------------------------------
    // Accepting an invite
    // ---------------------------------------------------------------

    public function test_invite_page_shows_the_email(): void
    {
        $staff = $this->makePendingStaff();

        $this->get(route('staff-invite.show', [
            'token' => $this->inviteToken($staff),
            'email' => $staff->email,
        ]))
            ->assertOk()
            ->assertSee($staff->email);
    }

    public function test_staff_member_can_accept_invitation_and_set_password(): void
    {
        $staff = $this->makePendingStaff();

        $this->acceptInvite($staff, $this->inviteToken($staff), 'MySecurePass123')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your staff account is ready. You can sign in now.');

        $staff->refresh();

        $this->assertTrue(Hash::check('MySecurePass123', $staff->password));
        $this->assertTrue($staff->hasVerifiedEmail());
    }

    public function test_staff_member_can_sign_in_after_accepting_invitation(): void
    {
        $staff = $this->makePendingStaff();

        $this->acceptInvite($staff, $this->inviteToken($staff), 'MySecurePass123')
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'MySecurePass123',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_invite_is_still_valid_after_23_hours(): void
    {
        $staff = $this->makePendingStaff();
        $token = $this->inviteToken($staff);

        $this->travel(23)->hours();

        $this->acceptInvite($staff, $token)
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($staff->fresh()->hasVerifiedEmail());
    }

    public function test_invite_expires_after_24_hours(): void
    {
        $staff = $this->makePendingStaff();
        $token = $this->inviteToken($staff);
        $originalPasswordHash = $staff->password;

        $this->travel(25)->hours();

        $this->acceptInvite($staff, $token)
            ->assertSessionHasErrors([
                'email' => 'This invitation link is invalid or has expired.',
            ]);

        $staff->refresh();

        $this->assertSame($originalPasswordHash, $staff->password);
        $this->assertFalse($staff->hasVerifiedEmail());
    }

    public function test_invite_link_works_only_once(): void
    {
        $staff = $this->makePendingStaff();
        $token = $this->inviteToken($staff);

        $this->acceptInvite($staff, $token, 'FirstPass123')
            ->assertRedirect(route('login'));

        $this->acceptInvite($staff, $token, 'SecondPass123')
            ->assertSessionHasErrors([
                'email' => 'This invitation link is invalid or has expired.',
            ]);

        $this->assertTrue(Hash::check('FirstPass123', $staff->fresh()->password));
    }

    // ---------------------------------------------------------------
    // Invite tokens and reset tokens are kept apart
    // ---------------------------------------------------------------

    public function test_password_reset_token_cannot_be_used_to_accept_an_invite(): void
    {
        $staff = $this->makePendingStaff();

        // A normal 15-minute reset token (default broker, password_reset_tokens table).
        $resetToken = Password::createToken($staff);

        $this->acceptInvite($staff, $resetToken)
            ->assertSessionHasErrors([
                'email' => 'This invitation link is invalid or has expired.',
            ]);

        $this->assertFalse($staff->fresh()->hasVerifiedEmail());
    }

    public function test_invite_token_cannot_be_used_on_the_password_reset_page(): void
    {
        $staff = $this->makePendingStaff();

        $this->post(route('password.update'), [
            'token' => $this->inviteToken($staff),
            'email' => $staff->email,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertSessionHasErrors([
            'email' => 'This reset link is invalid or has expired.',
        ]);

        $this->assertFalse($staff->fresh()->hasVerifiedEmail());
    }

    // ---------------------------------------------------------------
    // Resending an invite
    // ---------------------------------------------------------------

    public function test_admin_can_resend_invite_to_pending_staff(): void
    {
        Notification::fake();

        $staff = $this->makePendingStaff();

        $this->actingAs($this->admin)
            ->from(route('admin.staff.edit', $staff))
            ->post(route('admin.staff.invite', $staff))
            ->assertRedirect(route('admin.staff.edit', $staff))
            ->assertSessionHas('status', "A new invitation has been sent to {$staff->email}.");

        Notification::assertSentTo($staff, StaffInvitation::class);

        // The token is stored in the invite table, not the password reset table.
        $this->assertDatabaseHas('staff_invite_tokens', ['email' => $staff->email]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $staff->email]);
    }

    public function test_resend_does_nothing_for_staff_who_already_set_up_their_account(): void
    {
        Notification::fake();

        $staff = $this->makePendingStaff();
        $staff->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($this->admin)
            ->from(route('admin.staff.edit', $staff))
            ->post(route('admin.staff.invite', $staff))
            ->assertRedirect(route('admin.staff.edit', $staff))
            ->assertSessionHas('status', 'This staff member has already set up their account.');

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('staff_invite_tokens', ['email' => $staff->email]);
    }

    public function test_resend_invite_returns_404_for_a_client(): void
    {
        Notification::fake();

        $client = User::factory()->role('client')->unverified()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.staff.invite', $client))
            ->assertNotFound();

        Notification::assertNothingSent();
    }

    public function test_non_admin_cannot_resend_invites(): void
    {
        Notification::fake();

        $staff = $this->makePendingStaff();
        $branchManager = User::factory()->role('branch_manager')->create();

        $this->actingAs($branchManager)
            ->post(route('admin.staff.invite', $staff))
            ->assertForbidden();

        Notification::assertNothingSent();
    }
}
