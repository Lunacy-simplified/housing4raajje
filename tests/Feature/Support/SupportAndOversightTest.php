<?php

namespace Tests\Feature\Support;

use App\Models\District;
use App\Models\Escalation;
use App\Models\Inquiry;
use App\Models\MaintenanceRequest;
use App\Models\MessageThread;
use App\Models\StaffCompensation;
use App\Models\SupportTicket;
use App\Models\SystemParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsRecords;
use Tests\TestCase;

/** Maintenance, messaging, front desk, escalations, manager and admin pages, and profiles. */
class SupportAndOversightTest extends TestCase
{
    use BuildsRecords, RefreshDatabase;

    protected $seed = true;

    public function test_tenant_reports_maintenance_and_staff_move_it_along(): void
    {
        $owner = $this->owner();
        $client = $this->client();
        $property = $this->property($owner);
        $lease = $this->lease($property, $client);
        $form = ['property_id' => $property->id, 'category' => 'plumbing', 'priority' => 'high', 'description' => 'Kitchen tap leaks.'];

        // Someone who doesn't rent the property can't report on it.
        $this->actingAs($this->client())->post(route('maintenance.store'), $form)->assertNotFound();

        $this->actingAs($client)->post(route('maintenance.store'), $form)->assertSessionHasNoErrors();

        $request = MaintenanceRequest::sole();
        $this->assertSame($lease->id, $request->lease_id);
        $this->assertSame(1, $owner->notifications()->count());   // FR-2: the owner is alerted
        $this->actingAs($owner)->get(route('maintenance.index'))->assertSee('Kitchen tap leaks.');

        $specialist = $this->staff('property_specialist');
        $this->actingAs($specialist)->patch(route('staff.maintenance.update', $request), ['status' => 'resolved']);

        $request->refresh();
        $this->assertSame('resolved', $request->status);
        $this->assertSame($specialist->id, $request->assigned_to_user_id);
        $this->assertNotNull($request->resolved_at);
        $this->assertSame(1, $client->notifications()->count());
    }

    public function test_client_messages_the_owner_and_branch_staff_supervise(): void
    {
        $owner = $this->owner();
        $client = $this->client();
        $property = $this->property($owner);

        $this->actingAs($client)->post(route('messages.store'), [
            'property_id' => $property->id, 'subject' => 'Furniture', 'body' => 'Is the sofa included?',
        ])->assertRedirect();

        $thread = MessageThread::sole();
        $this->assertSame($property->branch_id, $thread->branch_id);
        $this->assertSame(1, $owner->notifications()->count());

        $this->actingAs($owner)->get(route('messages.show', $thread))->assertOk()->assertSee('Is the sofa included?');
        $this->actingAs($owner)->post(route('messages.reply', $thread), ['body' => 'Yes, it is.']);
        $this->actingAs($this->staff('receptionist'))->get(route('messages.show', $thread))->assertOk()->assertSee('Yes, it is.');
        $this->actingAs($this->staff('receptionist'))->get(route('messages.index'))->assertSee('Furniture');

        // Not for other clients, nor staff of another branch.
        $this->actingAs($this->client())->get(route('messages.show', $thread))->assertNotFound();
        $this->actingAs($this->staff('receptionist', 'HLM'))->get(route('messages.show', $thread))->assertNotFound();
        $this->assertSame(2, $thread->messages()->count());
    }

    public function test_receptionist_logs_an_inquiry_and_routes_it_to_a_specialist(): void
    {
        $receptionist = $this->staff('receptionist');
        $specialist = $this->staff('property_specialist');
        $otherBranch = $this->staff('property_specialist', 'HLM');

        $this->actingAs($receptionist)->post(route('staff.inquiries.store'), [
            'channel' => 'walk_in', 'contact_name' => 'Aishath', 'subject' => 'Wants a 2-room flat',
        ])->assertSessionHasNoErrors();
        $inquiry = Inquiry::sole();

        $this->actingAs($receptionist)->post(route('staff.inquiries.route', $inquiry), ['assigned_to_user_id' => $otherBranch->id])->assertNotFound();
        $this->actingAs($receptionist)->post(route('staff.inquiries.route', $inquiry), ['assigned_to_user_id' => $specialist->id]);

        $ticket = SupportTicket::sole();
        $this->assertSame('routed', $inquiry->refresh()->status);
        $this->assertSame($specialist->id, $ticket->assigned_to_user_id);
        $this->assertSame($inquiry->id, $ticket->inquiry_id);

        $this->actingAs($specialist)->patch(route('staff.tickets.update', $ticket), ['status' => 'resolved']);
        $this->assertNotNull($ticket->refresh()->resolved_at);
    }

    public function test_front_desk_corrects_a_contact_and_the_phone_needs_verifying_again(): void
    {
        $client = $this->client([], ['name' => 'Old Name', 'phone_verified_at' => now()]);
        $receptionist = $this->staff('receptionist');

        $this->actingAs($receptionist)->get(route('staff.contacts.index', ['q' => 'Old Na']))->assertSee($client->email);
        $this->actingAs($receptionist)->put(route('staff.contacts.update', $client), ['name' => 'New Name', 'phone' => '9998877'])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('New Name', $client->name);
        $this->assertNull($client->phone_verified_at);
        $this->actingAs($receptionist)->put(route('staff.contacts.update', $receptionist), ['name' => 'X', 'phone' => '9998876'])->assertNotFound();
    }

    public function test_staff_escalate_and_the_branch_manager_resolves(): void
    {
        $lease = $this->lease($this->property(), $this->client());
        $specialist = $this->staff('property_specialist');
        $manager = $this->staff('branch_manager');

        $this->actingAs($specialist)->post(route('staff.escalations.store'), [
            'subject_type' => 'lease', 'subject_id' => $lease->id, 'reason' => 'Owner disputes the deposit.',
        ])->assertSessionHasNoErrors();

        $escalation = Escalation::sole();
        $this->assertSame($lease->id, $escalation->lease_id);
        $this->assertSame(1, $manager->notifications()->count());

        $this->actingAs($this->staff('branch_manager', 'HLM'))->patch(route('manager.escalations.resolve', $escalation), ['resolution' => 'x'])->assertNotFound();
        $this->actingAs($manager)->get(route('manager.escalations.index'))->assertSee('Owner disputes the deposit.');
        $this->actingAs($manager)->patch(route('manager.escalations.resolve', $escalation), ['resolution' => 'Deposit stays as signed.']);

        $this->assertSame('resolved', $escalation->refresh()->status);
        $this->assertSame($manager->id, $escalation->resolved_by_user_id);
    }

    public function test_manager_sees_the_branch_report_and_records_salaries(): void
    {
        $manager = $this->staff('branch_manager');
        $specialist = $this->staff('property_specialist');
        $otherBranch = $this->staff('property_specialist', 'HLM');
        $this->lease($this->property(), $this->client());

        $this->actingAs($manager)->get(route('manager.dashboard'))->assertOk()->assertSee('Occupancy')->assertSee('100%');
        $this->actingAs($manager)->get(route('manager.staff.index'))->assertSee($specialist->name)->assertDontSee($otherBranch->name);

        $this->actingAs($manager)->post(route('manager.staff.salary', $specialist), ['monthly_salary' => 12000, 'effective_from' => '2026-01-01']);
        $this->actingAs($manager)->post(route('manager.staff.salary', $specialist), ['monthly_salary' => 14000, 'effective_from' => '2026-07-01']);
        $this->actingAs($manager)->post(route('manager.staff.salary', $otherBranch), ['monthly_salary' => 1, 'effective_from' => '2026-07-01'])->assertNotFound();

        $this->assertSame('2026-06-30', StaffCompensation::where('monthly_salary', 12000)->sole()->effective_to->toDateString());
        $this->assertNull(StaffCompensation::where('monthly_salary', 14000)->sole()->effective_to);

        // Salaries stay out of reach of ordinary staff.
        $this->actingAs($specialist)->get(route('manager.staff.index'))->assertForbidden();
    }

    public function test_admin_manages_settings_master_data_and_reads_the_audit_log(): void
    {
        $admin = $this->staff('system_admin');
        $fee = SystemParameter::where('key', 'management_fee_percent')->sole();

        $this->actingAs($admin)->patch(route('admin.settings.update', $fee), ['value' => 12.5])->assertSessionHasNoErrors();
        $this->assertEquals(12.5, $fee->refresh()->value);

        $this->actingAs($admin)->post(route('admin.lookups.store', 'districts'), [
            'name' => 'Hulhumalé Phase 3', 'branch_id' => $this->staff('receptionist', 'HLM')->branchId(),
        ])->assertSessionHasNoErrors();
        $district = District::where('name', 'Hulhumalé Phase 3')->sole();

        $this->actingAs($admin)->patch(route('admin.lookups.toggle', ['districts', $district->id]));
        $this->assertFalse($district->refresh()->is_active);

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()->assertSee('setting.update')->assertSee('lookup.create');
        $this->actingAs($admin)->get(route('admin.lookups.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payouts.index'))->assertOk();
        $this->actingAs($this->staff('branch_manager'))->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_owner_and_client_keep_their_own_profile(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->put(route('profile.update'), [
            'name' => 'Owner One', 'phone' => $owner->phone, 'national_id_or_passport' => 'A999999',
            'bank_name' => 'BML', 'bank_account_name' => 'Owner One', 'bank_account_no' => '7730000111222',
        ])->assertSessionHasNoErrors();
        $this->assertSame('BML', $owner->ownerProfile->refresh()->bank_name);

        $client = $this->client();
        $branch = $this->staff('receptionist', 'VIL')->branchId();
        $this->actingAs($client)->put(route('profile.update'), [
            'name' => 'Client One', 'phone' => $client->phone, 'sex' => 'female', 'date_of_birth' => '1995-04-02',
            'max_monthly_rent' => 18000, 'preferred_rooms' => 2, 'branches' => [$branch], 'mfa_enabled' => 1,
        ])->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertEquals(18000, $client->clientProfile->max_monthly_rent);
        $this->assertSame([$branch], $client->clientProfile->preferredBranches->pluck('id')->all());
        $this->assertTrue($client->mfa_enabled);
        $this->actingAs($client)->get(route('profile.edit'))->assertOk();
    }

    public function test_a_client_who_switched_on_sign_in_codes_is_asked_for_one(): void
    {
        $client = $this->client([], ['mfa_enabled' => true]);

        $this->post(route('login'), ['email' => $client->email, 'password' => 'password'])->assertRedirect(route('mfa.show'));
        $this->assertGuest();
    }

    public function test_every_dashboard_and_list_page_opens_for_its_role(): void
    {
        $pages = [
            'owner' => ['owner.dashboard', 'owner.properties.index', 'owner.properties.create', 'owner.earnings.index', 'leases.index', 'maintenance.index', 'messages.index', 'notifications.index'],
            'client' => ['client.dashboard', 'client.properties.index', 'client.viewings.index', 'client.applications.index', 'leases.index', 'maintenance.index', 'messages.index'],
            'receptionist' => ['staff.dashboard', 'staff.properties.index', 'staff.applications.index', 'staff.viewings.index', 'staff.leases.index', 'staff.maintenance.index', 'staff.inquiries.index', 'staff.tickets.index', 'staff.contacts.index', 'messages.index', 'profile.edit'],
            'branch_manager' => ['manager.dashboard', 'manager.staff.index', 'manager.escalations.index', 'staff.properties.index'],
            'system_admin' => ['admin.dashboard', 'admin.payouts.index', 'admin.lookups.index', 'admin.settings.index', 'admin.audit.index', 'notifications.index'],
        ];

        foreach ($pages as $role => $routes) {
            $user = match ($role) {
                'owner' => $this->owner(),
                'client' => $this->client(),
                default => $this->staff($role),
            };

            foreach ($routes as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
        }

        // Each portal is closed to the other roles.
        $this->actingAs($this->client())->get(route('owner.properties.index'))->assertForbidden();
        $this->actingAs($this->owner())->get(route('staff.dashboard'))->assertForbidden();
        $this->actingAs($this->staff('receptionist'))->get(route('admin.audit.index'))->assertForbidden();
    }
}
