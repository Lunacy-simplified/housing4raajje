<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
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

    private function makeStaff(
        string $role,
        string $name,
        ?string $branchCode = 'MLE',
        bool $active = true,
    ): User {
        $user = User::factory()->role($role)->create([
            'name' => $name,
            'is_active' => $active,
        ]);

        $branchId = $branchCode
            ? Branch::where('code', $branchCode)->value('id')
            : null;

        $user->staffProfile()->create([
            'staff_code' => sprintf('H4R-STF-%04d', $user->id),
            'branch_id' => $branchId,
        ]);

        return $user;
    }

    public function test_admin_can_create_a_property_specialist()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm());

        $response->assertRedirect(route('admin.staff.index'));
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

        $response->assertRedirect(route('admin.staff.index'));

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

        $response->assertRedirect(route('admin.staff.index'));

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


    public function test_cannot_create_an_owner_or_client_through_staff_management()
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

    public function test_inactive_branch_is_rejected()
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

    public function test_future_hire_date_is_rejected()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm([
                'hired_on' => now()->addDay()->toDateString(),
            ]));

        $response->assertSessionHasErrors('hired_on');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('staff_profiles', 0);
    }

    public function test_non_admin_cannot_access_staff_management()
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

    public function test_guest_is_redirected_to_login()
    {

        $this->get('/admin/staff/create')
            ->assertRedirect(route('login'));

        $this->post('/admin/staff', $this->validForm())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
    }

    public function test_temporary_password_is_stored_as_a_hash()
    {
        $this->actingAs($this->admin)
            ->post('/admin/staff', $this->validForm())
            ->assertRedirect(route('admin.staff.index'));

        $user = User::where('email', 'hassan@h4r.local')->firstOrFail();

        $this->assertTrue(Hash::check('Temp1234', $user->password));
        $this->assertNotSame('Temp1234', $user->password);
    }

    public function test_admin_can_view_the_staff_list()
    {
        $this->makeStaff('property_specialist', 'Hassan Ali');

        $this->actingAs($this->admin)
            ->get('/admin/staff')
            ->assertOk()
            ->assertSee('Hassan Ali');
    }

    public function test_owners_and_clients_are_not_listed()
    {
        User::factory()->role('owner')->create([
            'name' => 'Olivia Owner',
        ]);

        User::factory()->role('client')->create([
            'name' => 'Caleb Client',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/staff')
            ->assertOk()
            ->assertDontSee('Olivia Owner')
            ->assertDontSee('Caleb Client');
    }

    public function test_staff_list_can_be_filtered_by_role()
    {
        $this->makeStaff('property_specialist', 'Hassan Specialist');
        $this->makeStaff('receptionist', 'Aisha Receptionist');

        $this->actingAs($this->admin)
            ->get('/admin/staff?role=receptionist')
            ->assertOk()
            ->assertSee('Aisha Receptionist')
            ->assertDontSee('Hassan Specialist');
    }

    public function test_staff_list_can_be_filtered_by_branch()
    {
        $this->makeStaff('property_specialist', 'Hassan Male');
        $this->makeStaff('receptionist', 'Aisha Hulhumale', 'HLM');

        $hulhumaleBranchId = Branch::where('code', 'HLM')->value('id');

        $this->actingAs($this->admin)
            ->get('/admin/staff?branch_id=' . $hulhumaleBranchId)
            ->assertOk()
            ->assertSee('Aisha Hulhumale')
            ->assertDontSee('Hassan Male');
    }

    public function test_staff_list_can_be_filtered_by_status()
    {
        $this->makeStaff('property_specialist', 'Hassan Active', 'MLE', true);
        $this->makeStaff('receptionist', 'Aisha Inactive', 'MLE', false);

        $this->actingAs($this->admin)
            ->get('/admin/staff?status=inactive')
            ->assertOk()
            ->assertSee('Aisha Inactive')
            ->assertDontSee('Hassan Active');
    }

    public function test_invalid_staff_list_filters_are_rejected()
    {
        $this->actingAs($this->admin)
            ->get('/admin/staff?branch_id=abc')
            ->assertRedirect()
            ->assertSessionHasErrors('branch_id');

        $this->actingAs($this->admin)
            ->get('/admin/staff?role=owner')
            ->assertRedirect()
            ->assertSessionHasErrors('role');
    }

    public function test_staff_list_is_paginated_to_twenty_per_page()
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->makeStaff(
                'property_specialist',
                sprintf('Specialist %02d', $i),
            );
        }

        $response = $this->actingAs($this->admin)
            ->get('/admin/staff')
            ->assertOk()
            ->assertViewHas(
                'staff',
                fn($staff) =>
                $staff->count() === 20 && $staff->total() === 25
            );

        $response->assertViewHas('staff', fn($staff) => $staff->currentPage() === 1);

        $this->actingAs($this->admin)
            ->get('/admin/staff?page=2')
            ->assertOk()
            ->assertViewHas(
                'staff',
                fn($staff) =>
                $staff->count() === 5
                    && $staff->total() === 25
                    && $staff->currentPage() === 2
            );
    }

    public function test_staff_list_preserves_filters_in_pagination_urls()
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->makeStaff(
                'receptionist',
                sprintf('Receptionist %02d', $i),
            );
        }

        $this->actingAs($this->admin)
            ->get('/admin/staff?role=receptionist')
            ->assertOk()
            ->assertViewHas(
                'staff',
                fn($staff) =>
                str_contains($staff->nextPageUrl() ?? '', 'role=receptionist')
            );
    }

    public function test_non_admin_cannot_view_staff_list()
    {
        $branchManager = User::factory()->role('branch_manager')->create();

        $this->actingAs($branchManager)
            ->get('/admin/staff')
            ->assertForbidden();
    }
}
