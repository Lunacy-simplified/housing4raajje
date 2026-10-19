<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffTest extends TestCase
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

    private function validForm(array $overrides = [])
    {
        return array_merge([
            'name' => 'Hassan Ali',
            'email' => 'hassan@h4r.local',
            'phone' => '7781234',
            'role' => 'property_specialist',
            'branch_id' => Branch::where('code', 'MLE')->value('id'),
            'job_title' => 'Lettings Specialist',
            'hired_on' => '2026-01-15',
            'password' => 'Temp1234',
            'password_confirmation' => 'Temp1234',
        ], $overrides);
    }


    public function test_admin_can_create_a_property_specialist()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm());

        $response->assertRedirect(route('admin.staff.create'));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('staff_profiles', 1);

        $user = User::where('email', 'hassan@h4r.local')->firstOrFail();

        $this->assertSame('property_specialist', $user->role->code);
        $this->assertSame('H4R-STF-0001', $user->staffProfile->staff_code);
        $this->assertSame(
            Branch::where('code', 'MLE')->value('id'),
            $user->staffProfile->branch_id
        );
    }

    public function test_staff_codes_continue_from_the_highest_existing_number()
    {
        $existingStaff = User::factory()
            ->role('property_specialist')
            ->create();

        $existingStaff->staffProfile()->create([
            'staff_code' => 'H4R-STF-0004',
            'branch_id' => Branch::where('code', 'MLE')->value('id'),
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm());

        $response->assertRedirect(route('admin.staff.create'));

        $newStaff = User::where('email', 'hassan@h4r.local')->firstOrFail();

        $this->assertSame('H4R-STF-0005', $newStaff->staffProfile->staff_code);
    }

    public function test_system_admin_can_be_created_without_a_branch()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'email' => 'newadmin@h4r.local',
                'phone' => '7791234',
                'role' => 'system_admin',
                'branch_id' => null,
            ]));

        $response->assertRedirect(route('admin.staff.create'));

        $newAdmin = User::where('email', 'newadmin@h4r.local')->firstOrFail();

        $this->assertSame('system_admin', $newAdmin->role->code);
        $this->assertNull($newAdmin->staffProfile->branch_id);
    }

    public function test_system_admin_cannot_be_assigned_a_branch()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'role' => 'system_admin',
            ]));

        $response->assertSessionHasErrors('branch_id');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('staff_profiles', 0);
    }

    public function test_other_staff_must_have_a_branch()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'branch_id' => null,
            ]));

        $response->assertSessionHasErrors('branch_id');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('staff_profiles', 0);
    }


    public function test_cannot_create_an_owner_or_client_through_staff_management(): void
    {
        foreach (['owner', 'client'] as $role) {
            $response = $this->actingAs($this->admin)
                ->post('/admin/staff', $this->validForm([
                    'email' => $role . '@h4r.local',
                    'phone' => $role === 'owner' ? '7791234' : '7795678',
                    'role' => $role,
                ]));

            $response->assertSessionHasErrors('role');
        }

        $this->assertDatabaseCount('staff_profiles', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_inactive_branch_is_rejected(): void
    {
        $branch = Branch::where('code', 'MLE')->firstOrFail();
        $branch->update(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'branch_id' => $branch->id,
            ]));

        $response->assertSessionHasErrors('branch_id');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('staff_profiles', 0);
    }

    public function test_future_hire_date_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'hired_on' => now()->addDay()->toDateString(),
            ]));

        $response->assertSessionHasErrors('hired_on');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('staff_profiles', 0);
    }

    public function test_non_admin_cannot_access_staff_management(): void
    {
        $branchManager = User::factory()
            ->role('branch_manager')
            ->create();

        $this->actingAs($branchManager)
            ->get('/admin/staff/create')
            ->assertForbidden();

        $this->actingAs($branchManager)
            ->post('/admin/staff', $this->validForm([
                'email' => 'blocked@h4r.local',
                'phone' => '7791234',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@h4r.local',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {

        $this->get('/admin/staff/create')
            ->assertRedirect(route('login'));

        $this->post('/admin/staff', $this->validForm())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
    }

    public function test_temporary_password_is_stored_as_a_hash(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm())
            ->assertRedirect(route('admin.staff.create'));

        $user = User::where('email', 'hassan@h4r.local')->firstOrFail();

        $this->assertTrue(Hash::check('Temp1234', $user->password));
        $this->assertNotSame('Temp1234', $user->password);
    }
}
