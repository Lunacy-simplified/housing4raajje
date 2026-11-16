<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Branch;
use App\Models\District;
use App\Models\FeedbackCategory;
use App\Models\Inquiry;
use App\Models\Lease;
use App\Models\LeaseTerm;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Property;
use App\Models\Role;
use App\Models\StaffCompensation;
use App\Models\User;
use App\Models\Viewing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Local-only sample records so every screen has something to show:
 * four properties at different stages, viewings with feedback, an application,
 * an active lease with payments and payouts, a maintenance request, an inquiry and a conversation.
 * Runs after DemoUserSeeder and uses its six accounts.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Property::exists()) {
            return;
        }

        $user = fn (string $name) => User::where('email', "{$name}@h4r.local")->firstOrFail();
        [$owner, $client, $specialist, $receptionist, $manager] = array_map($user, ['owner', 'client', 'specialist', 'reception', 'manager']);
        $male = Branch::where('code', 'MLE')->firstOrFail();

        // The demo accounts are fully set up, so nothing is blocked while trying the app.
        User::where('email', 'like', '%@h4r.local')->update(['email_verified_at' => now(), 'phone_verified_at' => now()]);
        $owner->ownerProfile->update(['bank_name' => 'Bank of Maldives', 'bank_account_name' => $owner->name, 'bank_account_no' => '7730000123456']);
        $client->clientProfile->update(['sex' => 'female', 'date_of_birth' => '1994-03-12']);
        $client->clientProfile->preferredBranches()->sync([$male->id]);

        $rose = $this->property($owner, 'Rose Villa', 'Majeedhee Magu', 'Henveiru', 3, 15000, 'approved', $specialist);
        $sea = $this->property($owner, 'Sea Breeze', 'Boduthakurufaanu Magu', 'Maafannu', 2, 12000, 'approved', $specialist);
        $this->property($owner, 'Coral Court', 'Sosun Magu', 'Galolhu', 2, 11000, 'pending');
        $this->property($owner, 'Palm Residence', 'Nirolhu Magu', 'Hulhumalé Phase 1', 4, 22000, 'draft');

        // Three past viewings of Rose Villa with feedback, and one the demo client has just asked for.
        $viewers = [
            ['Hassan Viewer', 'viewer1@h4r.local', '7000011', 'Financial constraint', 'Little expensive', 21],
            ['Mariyam Viewer', 'viewer2@h4r.local', '7000012', 'Financial constraint', 'Wanted it under 13,000', 14],
            ['Ibrahim Viewer', 'viewer3@h4r.local', '7000013', 'Location', 'Too far from work', 7],
        ];

        foreach ($viewers as [$name, $email, $phone, $reason, $note, $daysAgo]) {
            $viewer = new User(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => 'SecretClient123!']);
            $viewer->role_id = Role::where('code', 'client')->value('id');
            $viewer->email_verified_at = now();
            $viewer->save();
            $viewer->clientProfile()->create(['max_monthly_rent' => 14000]);

            $viewing = new Viewing(['scheduled_for' => now()->subDays($daysAgo), 'staff_note' => $note]);
            $viewing->forceFill([
                'property_id' => $rose->id, 'client_user_id' => $viewer->id, 'status' => 'completed',
                'requested_at' => now()->subDays($daysAgo + 2), 'scheduled_by_user_id' => $receptionist->id,
                'feedback_category_id' => FeedbackCategory::where('label', $reason)->value('id'),
                'logged_by_user_id' => $specialist->id, 'logged_at' => now()->subDays($daysAgo),
            ])->save();
        }

        (new Viewing)->forceFill(['property_id' => $rose->id, 'client_user_id' => $client->id])->save();

        // The demo client rents Sea Breeze (active lease, two months in) and has applied for Rose Villa.
        $term = LeaseTerm::where('months', 12)->firstOrFail();
        $start = now()->subMonths(2)->startOfMonth();
        $method = PaymentMethod::where('code', 'bank_deposit')->value('id');

        $approved = $this->application($sea, $client, $term, 'approved', $specialist);
        $this->application($rose, $client, $term, 'submitted');

        $lease = new Lease(['start_date' => $start, 'term_months' => 12, 'monthly_rent' => 12000, 'advance_amount' => 23000, 'payment_method_id' => $method]);
        $lease->forceFill([
            'application_id' => $approved->id, 'property_id' => $sea->id, 'client_user_id' => $client->id, 'owner_user_id' => $owner->id,
            'executed_by_user_id' => $specialist->id, 'executed_at' => $start, 'activated_at' => $start, 'status' => 'active',
        ])->save();
        $sea->forceFill(['availability_status' => 'rented'])->save();

        foreach (['client' => $client, 'owner' => $owner] as $party => $signer) {
            DB::table('lease_signatures')->insert([
                'lease_id' => $lease->id, 'party' => $party, 'signed_by_user_id' => $signer->id,
                'signed_at' => $start, 'verification_method' => 'password', 'ip_address' => '127.0.0.1',
            ]);
        }

        // Advance plus two months of rent. The first month has already been paid out to the owner.
        $payout = (new Payout)->forceFill([
            'owner_user_id' => $owner->id, 'period_month' => $start, 'gross_amount' => 12000, 'fee_percent' => 10,
            'fee_amount' => 1200, 'net_amount' => 10800, 'status' => 'paid', 'paid_at' => $start->copy()->addMonth(), 'bank_reference' => 'TRX-0001',
        ]);
        $payout->save();

        $this->payment($lease, 'advance', null, 23000, 'SLIP-1000', $start, $receptionist);
        $this->payment($lease, 'rent', $start, 12000, 'SLIP-1001', $start->copy()->addDays(2), $receptionist, $payout);
        $this->payment($lease, 'rent', $start->copy()->addMonth(), 12000, 'SLIP-1002', $start->copy()->addMonth()->addDays(3), $receptionist);

        $maintenance = new MaintenanceRequest(['category' => 'plumbing', 'priority' => 'high', 'description' => 'The kitchen tap leaks when turned off.']);
        $maintenance->forceFill(['property_id' => $sea->id, 'lease_id' => $lease->id, 'raised_by_user_id' => $client->id])->save();

        $inquiry = new Inquiry(['channel' => 'walk_in', 'contact_name' => 'Aishath Leena', 'contact_phone' => '7778899', 'subject' => 'Looking for a 2-room flat near Majeedhee Magu', 'details' => 'Budget around MVR 13,000. Wants to move next month.']);
        $inquiry->forceFill(['branch_id' => $male->id, 'logged_by_user_id' => $receptionist->id])->save();

        $thread = new MessageThread(['subject' => 'Is the furniture included?']);
        $thread->forceFill(['branch_id' => $male->id, 'property_id' => $rose->id, 'created_by_user_id' => $client->id])->save();
        $thread->participants()->sync([$client->id, $owner->id]);

        foreach ([[$client, 'Hello, does Rose Villa come with the sofa and beds shown in the photos?'], [$owner, 'Yes, all the furniture in the photos stays.'], [$specialist, 'We can show you the furniture list at the viewing.']] as [$sender, $body]) {
            (new Message(['body' => $body]))->forceFill(['thread_id' => $thread->id, 'sender_user_id' => $sender->id])->save();
        }

        (new StaffCompensation(['monthly_salary' => 14000, 'effective_from' => '2025-08-15', 'recorded_by_user_id' => $manager->id]))
            ->forceFill(['staff_user_id' => $specialist->id])->save();
    }

    private function property(User $owner, string $house, string $road, string $district, int $rooms, float $rent, string $status, ?User $verifier = null): Property
    {
        $district = District::where('name', $district)->firstOrFail();

        $property = new Property([
            'district_id' => $district->id, 'house_name' => $house, 'road' => $road, 'property_type' => 'apartment',
            'rooms' => $rooms, 'monthly_rent' => $rent, 'floor' => 2,
        ]);
        $property->forceFill([
            'owner_user_id' => $owner->id, 'branch_id' => $district->branch_id, 'verification_status' => $status,
            'submitted_at' => $status === 'draft' ? null : now()->subDays(30),
            'availability_status' => $status === 'approved' ? 'available' : null,
            'verified_by_user_id' => $status === 'approved' ? $verifier->id : null,
            'verified_at' => $status === 'approved' ? now()->subDays(28) : null,
        ])->save();

        return $property;
    }

    private function application(Property $property, User $client, LeaseTerm $term, string $status, ?User $reviewer = null): Application
    {
        $application = new Application([
            'lease_term_id' => $term->id, 'requested_start_date' => now()->addWeeks(2), 'occupants' => 2,
            'employer' => 'Maldives Ports Limited', 'income_bracket' => '30,000 to 50,000',
        ]);
        $application->forceFill([
            'property_id' => $property->id, 'client_user_id' => $client->id, 'status' => $status, 'submitted_at' => now()->subDays(3),
            'reviewed_at' => $reviewer ? now()->subDays(2) : null, 'reviewed_by_user_id' => $reviewer?->id,
        ])->save();

        return $application;
    }

    private function payment(Lease $lease, string $kind, $month, float $amount, string $slip, $receivedOn, User $recorder, ?Payout $payout = null): void
    {
        $payment = new Payment([
            'kind' => $kind, 'period_month' => $month, 'amount' => $amount,
            'payment_method_id' => $lease->payment_method_id, 'bank_reference' => $slip, 'received_on' => $receivedOn,
        ]);
        $payment->forceFill(['lease_id' => $lease->id, 'recorded_by_user_id' => $recorder->id, 'payout_id' => $payout?->id])->save();
    }
}
