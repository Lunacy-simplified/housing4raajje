<?php

namespace Tests\Feature\Views;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Step 2 of the front end: the 11 existing pages were restyled.
 *
 * These tests load each real page through its real route and check two things:
 *   1. the "contract" from docs/frontend-handoff.md is still there
 *      (form action, method, @csrf, field names, error messages), so the backend keeps working;
 *   2. the page uses the new design (shared layout, stylesheet, logo, error styling).
 */
class PageContractTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeStaff(string $role, string $name = 'Test Staff', bool $verified = true, bool $active = true): User
    {
        $factory = User::factory()->role($role);
        if (! $verified) {
            $factory = $factory->unverified();
        }

        $user = $factory->create(['name' => $name, 'is_active' => $active]);
        $user->staffProfile()->create([
            'staff_code' => sprintf('H4R-STF-%04d', $user->id),
            'branch_id' => Branch::where('code', 'MLE')->value('id'),
        ]);

        return $user;
    }

    /** Checks the page uses the shared design (layout + stylesheet). */
    private function assertUsesDesign($response): void
    {
        $response->assertSee(asset('css/h4r.css'), false);
    }

    /** Checks a form posts to the right place and has the security token. */
    private function assertPostForm($response, string $action): void
    {
        $response->assertSee('method="POST" action="' . $action . '"', false);
        $response->assertSee('name="_token"', false);
    }

    // ---------------------------------------------------------------
    // Sign in
    // ---------------------------------------------------------------

    public function test_login_page_keeps_its_fields_and_links(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('aria-label="H4R Property Management logo"', false);
        $this->assertPostForm($response, route('login'));
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('href="' . route('password.request') . '"', false);
        $response->assertSee('href="' . route('register', 'client') . '"', false);
        $response->assertSee('href="' . route('register', 'owner') . '"', false);
    }

    public function test_login_error_is_shown_under_email_and_email_is_refilled(): void
    {
        $user = User::factory()->role('client')->create(['email' => 'client@example.com']);

        $this->from(route('login'))->post(route('login'), [
            'email' => 'client@example.com',
            'password' => 'wrong-password',
        ]);

        $response = $this->get(route('login'));
        $response->assertSee('class="err"', false);
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('value="client@example.com"', false);
        $response->assertDontSee('value="wrong-password"', false);
    }

    public function test_login_page_shows_the_status_message(): void
    {
        $this->withSession(['status' => 'Your password has been reset. You can sign in now.'])
            ->get(route('login'))
            ->assertSee('role="status"', false)
            ->assertSee('Your password has been reset. You can sign in now.');
    }

    // ---------------------------------------------------------------
    // Register
    // ---------------------------------------------------------------

    public function test_client_register_page_has_no_id_field(): void
    {
        $response = $this->get(route('register', 'client'))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('register', 'client'));
        foreach (['name', 'email', 'phone', 'password', 'password_confirmation', 'terms'] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }
        $response->assertSee('name="terms" value="1"', false);
        $response->assertDontSee('name="national_id_or_passport"', false);
    }

    public function test_owner_register_page_has_the_id_field(): void
    {
        $response = $this->get(route('register', 'owner'))->assertOk();

        $this->assertPostForm($response, route('register', 'owner'));
        $response->assertSee('name="national_id_or_passport"', false);
    }

    public function test_register_errors_show_and_old_values_are_kept_but_not_passwords(): void
    {
        $this->from(route('register', 'owner'))->post(route('register', 'owner'), [
            'name' => 'Aminath Test',
            'email' => 'not-an-email',
            'phone' => '3301100',
            'national_id_or_passport' => 'A123456',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response = $this->get(route('register', 'owner'));
        $response->assertSee('value="Aminath Test"', false);
        $response->assertSee('value="A123456"', false);
        $response->assertSee('id="email-error"', false);
        $response->assertSee('id="phone-error"', false);
        $response->assertDontSee('value="secret123"', false);
    }

    // ---------------------------------------------------------------
    // MFA code
    // ---------------------------------------------------------------

    public function test_mfa_page_keeps_both_forms_and_the_code_field(): void
    {
        Notification::fake();
        $admin = User::factory()->role('system_admin')->create();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.show'));

        $response = $this->get(route('mfa.show'))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('mfa.verify'));
        $this->assertPostForm($response, route('mfa.resend'));
        $response->assertSee('name="code"', false);
        $response->assertSee('inputmode="numeric"', false);
        $response->assertSee('autocomplete="one-time-code"', false);
        $response->assertSee('name="trust_device" value="1"', false);
    }

    // ---------------------------------------------------------------
    // Forgot / reset password
    // ---------------------------------------------------------------

    public function test_forgot_password_page_keeps_its_form(): void
    {
        $response = $this->get(route('password.request'))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('password.email'));
        $response->assertSee('name="email"', false);
        $response->assertSee('href="' . route('login') . '"', false);
    }

    public function test_reset_password_page_keeps_the_hidden_token_and_email(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'someone@example.com']))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('password.update'));
        $response->assertSee('name="token" value="abc123"', false);
        $response->assertSee('value="someone@example.com"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    // ---------------------------------------------------------------
    // Staff invite
    // ---------------------------------------------------------------

    public function test_staff_invite_page_keeps_the_hidden_token_and_email(): void
    {
        $response = $this->get(route('staff-invite.show', ['token' => 'xyz789', 'email' => 'new@h4r.local']))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('staff-invite.accept'));
        $response->assertSee('name="token" value="xyz789"', false);
        $response->assertSee('value="new@h4r.local"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    // ---------------------------------------------------------------
    // Email and phone verification
    // ---------------------------------------------------------------

    public function test_verify_email_page_has_resend_and_logout(): void
    {
        $client = User::factory()->role('client')->unverified()->create();

        $response = $this->actingAs($client)->get(route('verification.notice'))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('verification.send'));
        $this->assertPostForm($response, route('logout'));
    }

    public function test_verify_phone_page_for_an_unverified_phone(): void
    {
        $client = User::factory()->role('client')->create(['phone' => '7781122', 'phone_verified_at' => null]);

        $response = $this->actingAs($client)->get(route('phone.verification.show'))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('7781122');
        $response->assertSee('Not verified');
        $response->assertSee('action="' . route('phone.verification.send') . '"', false);
        $response->assertSee("this.querySelector('button').disabled = true", false);
        $this->assertPostForm($response, route('phone.verification.verify'));
        $response->assertSee('name="code"', false);
        $response->assertSee('autocomplete="one-time-code"', false);
    }

    public function test_verify_phone_page_for_a_verified_phone_has_no_forms(): void
    {
        $client = User::factory()->role('client')->create(['phone' => '7781122', 'phone_verified_at' => now()]);

        $response = $this->actingAs($client)->get(route('phone.verification.show'))->assertOk();

        $response->assertSee('Phone verified ✓');
        $response->assertDontSee('action="' . route('phone.verification.send') . '"', false);
        $response->assertDontSee('name="code"', false);
        $response->assertSee('href="' . route('client.dashboard') . '"', false);
    }

    // ---------------------------------------------------------------
    // Admin: staff list
    // ---------------------------------------------------------------

    public function test_staff_list_uses_the_portal_layout_and_has_edit_links(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $member = $this->makeStaff('receptionist', 'Mariyam Liusha', verified: false);

        $response = $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('aria-label="H4R"', false);              // logo mark in the top bar
        $response->assertSee('href="' . route('admin.staff.create') . '"', false);
        $response->assertSee('href="' . route('admin.staff.edit', $member) . '"', false);
        $response->assertSee('Invite pending');
        $response->assertSee('method="GET" action="' . route('admin.staff.index') . '"', false);
        foreach (['role', 'branch_id', 'status'] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }
    }

    public function test_staff_list_shows_a_message_when_no_one_matches(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $this->makeStaff('receptionist', 'Active Person');

        $this->actingAs($admin)
            ->get(route('admin.staff.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('No staff match these filters.');
    }

    public function test_staff_list_prints_page_numbers_when_there_are_many(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        for ($i = 1; $i <= 21; $i++) {
            $this->makeStaff('property_specialist', sprintf('Specialist %02d', $i));
        }

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertSee('Showing 1 to 20 of 21');
    }

    // ---------------------------------------------------------------
    // Admin: create staff
    // ---------------------------------------------------------------

    public function test_create_staff_page_keeps_its_fields(): void
    {
        $admin = User::factory()->role('system_admin')->create();

        $response = $this->actingAs($admin)->get(route('admin.staff.create'))->assertOk();

        $this->assertUsesDesign($response);
        $this->assertPostForm($response, route('admin.staff.store'));
        foreach (['name', 'email', 'phone', 'role', 'branch_id', 'job_title', 'hired_on'] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }
        $response->assertSee('<option value="">No branch (system administrators only)</option>', false);
        $response->assertDontSee('type="password"', false);
    }

    public function test_create_staff_errors_appear_under_their_fields(): void
    {
        $admin = User::factory()->role('system_admin')->create();

        $this->actingAs($admin)->from(route('admin.staff.create'))->post(route('admin.staff.store'), [
            'name' => 'New Person',
            'email' => 'bad-email',
            'phone' => '123',
            'role' => 'property_specialist',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.create'));
        $response->assertSee('role="alert"', false);
        $response->assertSee('id="email-error"', false);
        $response->assertSee('id="phone-error"', false);
        $response->assertSee('value="New Person"', false);
    }

    // ---------------------------------------------------------------
    // Admin: edit staff
    // ---------------------------------------------------------------

    public function test_edit_page_keeps_its_three_forms_for_a_pending_member(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $member = $this->makeStaff('receptionist', 'Pending Person', verified: false);

        $response = $this->actingAs($admin)->get(route('admin.staff.edit', $member))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('action="' . route('admin.staff.update', $member) . '" method="POST"', false);
        $response->assertSee('name="_method" value="PUT"', false);
        $response->assertSee('action="' . route('admin.staff.status', $member) . '" method="POST"', false);
        $response->assertSee('name="_method" value="PATCH"', false);
        $response->assertSee('name="is_active" value="0"', false);
        $this->assertPostForm($response, route('admin.staff.invite', $member));
        $response->assertSee($member->staffProfile->staff_code);
    }

    public function test_edit_page_hides_resend_invite_once_set_up(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $member = $this->makeStaff('receptionist', 'Set Up Person', verified: true);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $member))
            ->assertOk()
            ->assertDontSee('action="' . route('admin.staff.invite', $member) . '"', false);
    }

    public function test_edit_page_offers_activate_for_an_inactive_member(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $member = $this->makeStaff('receptionist', 'Inactive Person', active: false);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $member))
            ->assertOk()
            ->assertSee('name="is_active" value="1"', false)
            ->assertSee('Activate account');
    }

    public function test_deactivating_yourself_shows_the_error_on_the_page(): void
    {
        $admin = User::factory()->role('system_admin')->create();
        $admin->staffProfile()->create(['staff_code' => 'H4R-STF-0999', 'branch_id' => null]);

        $this->actingAs($admin)
            ->from(route('admin.staff.edit', $admin))
            ->patch(route('admin.staff.status', $admin), ['is_active' => '0']);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $admin))
            ->assertSee('class="err" role="alert"', false);
    }

    // ---------------------------------------------------------------
    // Dashboards
    // ---------------------------------------------------------------

    public function test_staff_dashboard_uses_the_side_menu_layout(): void
    {
        $admin = $this->makeStaff('system_admin', 'Dash Admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('Welcome, Dash Admin');
        $response->assertSee('class="side"', false);
        $this->assertPostForm($response, route('logout'));
    }

    public function test_client_dashboard_uses_the_top_menu_layout_and_shows_verification(): void
    {
        $client = User::factory()->role('client')->unverified()->create(['phone_verified_at' => null]);

        $response = $this->actingAs($client)->get(route('client.dashboard'))->assertOk();

        $this->assertUsesDesign($response);
        $response->assertSee('class="tnav"', false);
        $response->assertSee('Not verified');
        $this->assertPostForm($response, route('verification.send'));
        $response->assertSee('href="' . route('phone.verification.show') . '"', false);
    }
}
