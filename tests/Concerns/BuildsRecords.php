<?php

namespace Tests\Concerns;

use App\Models\Application;
use App\Models\Branch;
use App\Models\District;
use App\Models\Lease;
use App\Models\LeaseTerm;
use App\Models\PaymentMethod;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Short helpers that build the records the feature tests need. Tests using it must seed ($seed = true). */
trait BuildsRecords
{
    private int $phoneCounter = 7100000;

    protected function staff(string $role, string $branchCode = 'MLE'): User
    {
        $user = User::factory()->role($role)->create(['phone' => (string) $this->phoneCounter++]);
        $user->staffProfile()->create([
            'staff_code' => sprintf('H4R-STF-%04d', $user->id),
            'branch_id' => $role === 'system_admin' ? null : Branch::where('code', $branchCode)->value('id'),
        ]);

        return $user;
    }

    protected function owner(array $attributes = []): User
    {
        $user = User::factory()->role('owner')->create($attributes + ['phone' => (string) $this->phoneCounter++]);
        $user->ownerProfile()->create(['national_id_or_passport' => 'A'.$user->id]);

        return $user;
    }

    protected function client(array $profile = [], array $attributes = []): User
    {
        $user = User::factory()->role('client')->create($attributes + ['phone' => (string) $this->phoneCounter++]);
        $user->clientProfile()->create($profile);

        return $user;
    }

    /** A property in Henveiru (Malé branch) by default; pass 'district' => name to move it. */
    protected function property(?User $owner = null, string $status = 'approved', array $attributes = []): Property
    {
        $district = District::where('name', $attributes['district'] ?? 'Henveiru')->firstOrFail();
        unset($attributes['district']);

        $property = new Property($attributes + [
            'district_id' => $district->id,
            'house_name' => 'Rose Villa',
            'road' => 'Majeedhee Magu',
            'property_type' => 'apartment',
            'rooms' => 3,
            'monthly_rent' => 15000,
        ]);
        $property->owner_user_id = ($owner ?? $this->owner())->id;
        $property->branch_id = $district->branch_id;
        $property->verification_status = $status;
        $property->availability_status = $status === 'approved' ? 'available' : null;
        $property->submitted_at = $status === 'draft' ? null : now();
        $property->save();

        return $property;
    }

    protected function application(Property $property, User $client, string $status = 'submitted'): Application
    {
        $application = new Application([
            'lease_term_id' => LeaseTerm::where('months', 12)->value('id'),
            'requested_start_date' => today()->addWeek(),
            'occupants' => 2,
        ]);
        $application->property_id = $property->id;
        $application->client_user_id = $client->id;
        $application->status = $status;
        $application->submitted_at = now();
        $application->save();

        return $application;
    }

    /** A lease on the property; 'active' also adds both signatures and marks the property rented. */
    protected function lease(Property $property, User $client, string $status = 'active', array $attributes = []): Lease
    {
        $lease = new Lease($attributes + [
            'start_date' => today(),
            'term_months' => 12,
            'monthly_rent' => 15000,
            'advance_amount' => 23000,
            'payment_method_id' => PaymentMethod::where('code', 'bank_deposit')->value('id'),
        ]);
        $lease->application_id = $this->application($property, $client, 'approved')->id;
        $lease->property_id = $property->id;
        $lease->client_user_id = $client->id;
        $lease->owner_user_id = $property->owner_user_id;
        $lease->executed_by_user_id = $this->staff('property_specialist')->id;
        $lease->executed_at = now();
        $lease->status = $status;
        $lease->save();

        if ($status === 'active') {
            foreach (['client' => $client->id, 'owner' => $property->owner_user_id] as $party => $userId) {
                DB::table('lease_signatures')->insert([
                    'lease_id' => $lease->id, 'party' => $party, 'signed_by_user_id' => $userId,
                    'signed_at' => now(), 'verification_method' => 'password',
                ]);
            }
            $property->forceFill(['availability_status' => 'rented'])->save();
        }

        return $lease->refresh();
    }
}
