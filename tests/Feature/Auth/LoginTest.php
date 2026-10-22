<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public static function roleDashboards()
    {
        return [
            'system admin'        => ['system_admin', 'admin.dashboard'],
            'branch manager'      => ['branch_manager', 'manager.dashboard'],
            'property specialist' => ['property_specialist', 'staff.dashboard'],
            'receptionist'        => ['receptionist', 'staff.dashboard'],
            'owner'               => ['owner', 'owner.dashboard'],
            'client'              => ['client', 'client.dashboard'],
        ];
    }

    #[DataProvider('roleDashboards')]
    public function test_each_role_lands_on_its_own_dashboard($roleCode, $dashboardRoute)
    {
        $user = User::factory()->role($roleCode)->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route($dashboardRoute));
        $this->assertAuthenticatedAs($user);
    }

    public function test_empty_login_form_fails_validation()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);

        $this->assertGuest();
    }

    public function test_wrong_password_fails()
    {
        $user = User::factory()->role('client')->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in()
    {
        $user = User::factory()
            ->role('client')
            ->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'These login details are incorrect.',
        ]);
        $this->assertGuest();
    }

    public function test_email_login_is_case_insensitive()
    {
        $user = User::factory()->role('client')->create();

        $response = $this->post('/login', [
            'email' => strtoupper($user->email),
            'password' => 'password',
        ]);

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited_after_five_attempts()
    {
        $user = User::factory()->role('client')->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_guest_cannot_open_admin_dashboard()
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_admin_dashboard()
    {
        $client = User::factory()->role('client')->create();

        $response = $this->actingAs($client)->get('/admin');

        $response->assertForbidden();
    }

    public function test_user_can_log_out()
    {
        $user = User::factory()->role('client')->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logged_in_owner_visiting_login_is_redirected_to_owner_dashboard()
    {
        $owner = User::factory()->role('owner')->create();

        $response = $this->actingAs($owner)->get('/login');

        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_successful_login_records_last_login_timestamp()
    {
        $user = User::factory()
            ->role('client')
            ->create([
                'is_active' => true,
                'last_login_at' => null,
            ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
