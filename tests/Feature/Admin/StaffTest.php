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

    public function test_admin_can_deactivate_a_staff_member(): void
    {
        $staff = $this->makeStaff('receptionist', 'Aisha Receptionist');

        $this->actingAs($this->admin)
            ->from('/admin/staff')
            ->patch("/admin/staff/{$staff->id}/status", [
                'is_active' => '0',
            ])
            ->assertRedirect('/admin/staff')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_activate_an_inactive_staff_member(): void
    {
        $staff = $this->makeStaff(
            'receptionist',
            'Aisha Receptionist',
            'MLE',
            false,
        );

        $this->actingAs($this->admin)
            ->from('/admin/staff')
            ->patch("/admin/staff/{$staff->id}/status", [
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/staff')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => true,
        ]);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $staff = $this->makeStaff('receptionist', 'Aisha Receptionist');

        $this->actingAs($this->admin)
            ->from('/admin/staff')
            ->patch("/admin/staff/{$staff->id}/status", [
                'is_active' => 'invalid',
            ])
            ->assertRedirect('/admin/staff')
            ->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'is_active' => true,
        ]);
    }

    public function test_status_cannot_be_changed_for_a_client(): void
    {
        $client = User::factory()->role('client')->create();

        $this->actingAs($this->admin)
            ->patch("/admin/staff/{$client->id}/status", [
                'is_active' => '0',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $this->admin->staffProfile()->create([
            'staff_code' => 'H4R-STF-0999',
            'branch_id' => null,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.staff.status', $this->admin), [
                'is_active' => '0',
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_active' => true,
        ]);
    }

    public function test_edit_page_loads_and_shows_current_staff_name(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Current Staff Name');

        $this->actingAs($this->admin)
            ->get(route('admin.staff.edit', $staff))
            ->assertOk()
            ->assertSee('value="Current Staff Name"', false);
    }

    public function test_edit_page_returns_404_for_a_client(): void
    {
        $client = User::factory()->role('client')->create([
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.staff.edit', $client))
            ->assertNotFound();
    }

    public function test_admin_can_update_staff_details_and_is_redirected_to_list(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Old Staff Name', 'MLE');

        $newBranchId = Branch::where('code', 'HLM')->value('id');

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Updated Staff Name',
                'email' => $staff->email,
                'phone' => '7771234',
                'role' => 'property_specialist',
                'branch_id' => $newBranchId,
                'job_title' => 'Senior Property Specialist',
                'hired_on' => '2026-01-15',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Updated Staff Name',
            'phone' => '7771234',
        ]);

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $staff->id,
            'branch_id' => $newBranchId,
            'job_title' => 'Senior Property Specialist',
            'hired_on' => '2026-01-15',
        ]);
    }


    public function test_updating_with_unchanged_email_and_phone_succeeds(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Unchanged Details Staff');
        $staff->phone = '7771234';
        $staff->save();

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Updated Name',
                'email' => $staff->email,
                'phone' => $staff->phone,
                'role' => 'property_specialist',
                'branch_id' => $staff->staffProfile->branch_id,
                'job_title' => 'Property Specialist',
                'hired_on' => null,
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'email' => $staff->email,
            'phone' => '7771234',
            'name' => 'Updated Name',
        ]);
    }


    public function test_email_belonging_to_another_user_is_rejected(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Staff Being Edited');

        $otherUser = User::factory()->role('client')->create([
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $staff), [
                'name' => $staff->name,
                'email' => $otherUser->email,
                'phone' => '7771234',
                'role' => 'property_specialist',
                'branch_id' => $staff->staffProfile->branch_id,
                'job_title' => $staff->staffProfile->job_title,
                'hired_on' => null,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'email' => $staff->email,
        ]);
    }

    public function test_promoting_specialist_to_admin_clears_the_branch(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Future Admin', 'MLE');

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => '7771234',
                'role' => 'system_admin',
                'branch_id' => null,
                'job_title' => 'System Administrator',
                'hired_on' => null,
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $staff->refresh();
        $staff->load('role', 'staffProfile');

        $this->assertSame('system_admin', $staff->role->code);

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $staff->id,
            'branch_id' => null,
        ]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        // The controller checks for a staff profile before checking the role change.
        $this->admin->staffProfile()->create([
            'staff_code' => 'H4R-STF-0998',
            'branch_id' => null,
        ]);

        $branchId = Branch::where('code', 'MLE')->value('id');

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'phone' => '7771234',
                'role' => 'branch_manager',
                'branch_id' => $branchId,
                'job_title' => 'Branch Manager',
                'hired_on' => null,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'role_id' => $this->admin->role_id,
        ]);
    }

    public function test_submitted_staff_code_does_not_change_the_existing_code(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Staff Code Test');

        $originalStaffCode = $staff->staffProfile->staff_code;

        $this->actingAs($this->admin)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Updated Staff Code Test',
                'email' => $staff->email,
                'phone' => '7771234',
                'role' => 'property_specialist',
                'branch_id' => $staff->staffProfile->branch_id,
                'job_title' => 'Property Specialist',
                'hired_on' => null,
                'staff_code' => 'H4R-STF-9999',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $staff->id,
            'staff_code' => $originalStaffCode,
        ]);

        $this->assertDatabaseMissing('staff_profiles', [
            'user_id' => $staff->id,
            'staff_code' => 'H4R-STF-9999',
        ]);
    }

    public function test_branch_manager_gets_forbidden_when_updating_staff(): void
    {
        $staff = $this->makeStaff('property_specialist', 'Target Staff');

        $branchManager = User::factory()->role('branch_manager')->create([
            'is_active' => true,
        ]);

        $this->actingAs($branchManager)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Attempted Change',
                'email' => $staff->email,
                'phone' => '7771234',
                'role' => 'property_specialist',
                'branch_id' => $staff->staffProfile->branch_id,
                'job_title' => 'Property Specialist',
                'hired_on' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Target Staff',
        ]);
    }
}
