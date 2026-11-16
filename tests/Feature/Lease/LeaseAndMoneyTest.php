<?php

namespace Tests\Feature\Lease;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Notifications\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsRecords;
use Tests\TestCase;

/** Lease execution, signing, payments and owner payouts (brief 2D, FR-1, FR-2). */
class LeaseAndMoneyTest extends TestCase
{
    use BuildsRecords, RefreshDatabase;

    protected $seed = true;

    private function leaseForm(): array
    {
        return [
            'start_date' => today()->addWeek()->toDateString(),
            'monthly_rent' => 15000,
            'advance_amount' => 23000,
            'payment_method_id' => PaymentMethod::where('code', 'bank_deposit')->value('id'),
        ];
    }

    public function test_specialist_creates_a_lease_from_an_approved_application(): void
    {
        $owner = $this->owner();
        $client = $this->client();
        $application = $this->application($this->property($owner), $client, 'approved');
        $specialist = $this->staff('property_specialist');

        $this->actingAs($specialist)->post(route('staff.leases.store', $application), $this->leaseForm())->assertRedirect();

        $lease = Lease::sole();
        $this->assertSame('pending_signature', $lease->status);
        $this->assertSame($specialist->id, $lease->executed_by_user_id);
        $this->assertNotNull($lease->executed_at);
        $this->assertSame($owner->id, $lease->owner_user_id);
        $this->assertSame(12, $lease->term_months);
        // The database works out the last day: start + 12 months - 1 day.
        $this->assertTrue($lease->end_date->equalTo(today()->addWeek()->addMonths(12)->subDay()));
        $this->assertSame(1, $client->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count());

        // The application can't be used for a second lease.
        $this->actingAs($specialist)->post(route('staff.leases.store', $application), $this->leaseForm())->assertForbidden();
    }

    public function test_a_lease_needs_an_approved_application_and_a_specialist(): void
    {
        $submitted = $this->application($this->property(), $this->client());
        $approved = $this->application($this->property(), $this->client(), 'approved');

        $this->actingAs($this->staff('property_specialist'))->post(route('staff.leases.store', $submitted), $this->leaseForm())->assertForbidden();
        $this->actingAs($this->staff('receptionist'))->post(route('staff.leases.store', $approved), $this->leaseForm())->assertForbidden();
    }

    public function test_lease_becomes_active_when_both_parties_sign_with_their_password(): void
    {
        $owner = $this->owner();
        $client = $this->client();
        $property = $this->property($owner);
        $lease = $this->lease($property, $client, 'pending_signature');

        $this->actingAs($client)->post(route('leases.sign', $lease), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->actingAs($this->client())->post(route('leases.sign', $lease), ['password' => 'password'])->assertNotFound();

        $this->actingAs($client)->post(route('leases.sign', $lease), ['password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('pending_signature', $lease->refresh()->status);
        $this->actingAs($client)->post(route('leases.sign', $lease), ['password' => 'password'])->assertForbidden();

        $this->actingAs($owner)->post(route('leases.sign', $lease), ['password' => 'password'])->assertSessionHasNoErrors();

        $lease->refresh();
        $this->assertSame('active', $lease->status);
        $this->assertNotNull($lease->activated_at);
        $this->assertSame('rented', $property->refresh()->availability_status);
        $this->assertDatabaseHas('lease_signatures', ['lease_id' => $lease->id, 'party' => 'owner', 'verification_method' => 'password']);

        // A rented property drops out of client search, and the owner sees the lease on the property page.
        $this->actingAs($client)->get(route('client.properties.index'))->assertDontSee($property->house_name);
        $this->actingAs($owner)->get(route('owner.properties.show', $property))->assertSee('Current lease');
    }

    public function test_ending_a_lease_early_frees_the_property(): void
    {
        $property = $this->property();
        $lease = $this->lease($property, $this->client());

        $this->actingAs($this->staff('property_specialist'))
            ->patch(route('staff.leases.terminate', $lease), ['termination_reason' => 'Client relocated.'])->assertSessionHasNoErrors();

        $this->assertSame('terminated', $lease->refresh()->status);
        $this->assertSame('available', $property->refresh()->availability_status);
    }

    public function test_staff_record_a_rent_payment_and_both_parties_are_told(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $client = $this->client();
        $lease = $this->lease($this->property($owner), $client);
        $receptionist = $this->staff('receptionist');

        $form = [
            'kind' => 'rent', 'period_month' => now()->format('Y-m'), 'amount' => 15000,
            'payment_method_id' => $lease->payment_method_id, 'bank_reference' => 'SLIP-1', 'received_on' => today()->toDateString(),
        ];

        $this->actingAs($receptionist)->post(route('staff.leases.payments.store', $lease), ['period_month' => ''] + $form)
            ->assertSessionHasErrors('period_month');
        $this->actingAs($receptionist)->post(route('staff.leases.payments.store', $lease), $form)->assertSessionHasNoErrors();

        $payment = Payment::sole();
        $this->assertSame($receptionist->id, $payment->recorded_by_user_id);
        $this->assertTrue($payment->period_month->equalTo(now()->startOfMonth()));
        Notification::assertSentTo([$client, $owner], Notice::class);

        // Staff from another branch can't touch this lease.
        $this->actingAs($this->staff('receptionist', 'VIL'))->post(route('staff.leases.payments.store', $lease), $form)->assertNotFound();
    }

    private function rentPayment(Lease $lease, float $amount, string $slip): Payment
    {
        $payment = new Payment([
            'kind' => 'rent', 'period_month' => now()->startOfMonth(), 'amount' => $amount,
            'payment_method_id' => $lease->payment_method_id, 'bank_reference' => $slip, 'received_on' => today(),
        ]);
        $payment->lease_id = $lease->id;
        $payment->recorded_by_user_id = $lease->executed_by_user_id;
        $payment->save();

        return $payment;
    }

    public function test_admin_prepares_and_pays_an_owner_payout_minus_the_fee(): void
    {
        $owner = $this->owner();
        $lease = $this->lease($this->property($owner), $this->client());
        $payment = $this->rentPayment($lease, 15000, 'SLIP-1');
        $admin = $this->staff('system_admin');

        $this->actingAs($admin)->post(route('admin.payouts.generate'), ['period_month' => now()->format('Y-m')])->assertSessionHasNoErrors();

        $payout = Payout::sole();
        $this->assertSame($owner->id, $payout->owner_user_id);
        $this->assertEquals(15000, $payout->gross_amount);
        $this->assertEquals(1500, $payout->fee_amount);   // 10% management fee from the settings
        $this->assertEquals(13500, $payout->net_amount);
        $this->assertSame($payout->id, $payment->refresh()->payout_id);

        // A late payment for the same month is added to the payout while it is still pending.
        $this->rentPayment($lease, 5000, 'SLIP-2');
        $this->actingAs($admin)->post(route('admin.payouts.generate'), ['period_month' => now()->format('Y-m')]);
        $this->assertEquals(18000, Payout::sole()->net_amount);

        $this->actingAs($admin)->patch(route('admin.payouts.pay', $payout), ['bank_reference' => 'TRX-9'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $payout->refresh()->status);

        $this->actingAs($owner)->get(route('owner.earnings.index'))->assertSee('18,000.00')->assertSee('TRX-9');
        $csv = $this->actingAs($owner)->get(route('owner.earnings.download'))->assertOk()->streamedContent();
        $this->assertStringContainsString('18000.00', $csv);
    }

    public function test_daily_job_expires_finished_leases_and_sends_renewal_reminders(): void
    {
        $ended = $this->lease($this->property(), $this->client(), 'active', ['start_date' => today()->subMonths(13)]);
        $endingSoonClient = $this->client();
        // Ends exactly 30 days from now: start + 12 months - 1 day = today + 30 days.
        $endingSoon = $this->lease($this->property(), $endingSoonClient, 'active', ['start_date' => today()->addDays(31)->subMonths(12)]);

        $this->artisan('leases:daily')->assertSuccessful();

        $this->assertSame('expired', $ended->refresh()->status);
        $this->assertSame('available', $ended->property->availability_status);
        $this->assertSame('active', $endingSoon->refresh()->status);
        $this->assertSame(1, $endingSoonClient->notifications()->count());
    }
}
