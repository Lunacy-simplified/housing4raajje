<?php

namespace Tests\Feature\Property;

use App\Models\Application;
use App\Models\FeedbackCategory;
use App\Models\LeaseTerm;
use App\Models\Viewing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsRecords;
use Tests\TestCase;

/** Client search, viewings and applications (brief 2C, 2D). */
class SearchViewingApplicationTest extends TestCase
{
    use BuildsRecords, RefreshDatabase;

    protected $seed = true;

    public function test_search_shows_only_live_properties_and_applies_filters(): void
    {
        $this->property(null, 'approved', ['house_name' => 'Cheap Three', 'rooms' => 3, 'monthly_rent' => 9000]);
        $this->property(null, 'approved', ['house_name' => 'Dear Three', 'rooms' => 3, 'monthly_rent' => 30000]);
        $this->property(null, 'approved', ['house_name' => 'Hulhu Two', 'rooms' => 2, 'monthly_rent' => 8000, 'district' => 'Hulhumalé Phase 1']);
        $this->property(null, 'pending', ['house_name' => 'Unchecked']);
        $this->property(null, 'approved', ['house_name' => 'Being Fixed'])->forceFill(['availability_status' => 'under_maintenance'])->save();
        $client = $this->client();

        $this->actingAs($client)->get(route('client.properties.index'))
            ->assertSee('Cheap Three')->assertSee('Dear Three')->assertSee('Hulhu Two')
            ->assertDontSee('Unchecked')->assertDontSee('Being Fixed');

        $this->actingAs($client)->get(route('client.properties.index', ['rooms' => 3, 'max_rent' => 10000]))
            ->assertSee('Cheap Three')->assertDontSee('Dear Three')->assertDontSee('Hulhu Two');
    }

    public function test_owner_phone_is_shown_only_when_verified(): void
    {
        $verified = $this->owner(['phone' => '7770001', 'phone_verified_at' => now()]);
        $unverified = $this->owner(['phone' => '7770002']);
        $client = $this->client();

        $this->actingAs($client)->get(route('client.properties.show', $this->property($verified)))->assertSee('7770001');
        $this->actingAs($client)->get(route('client.properties.show', $this->property($unverified)))->assertDontSee('7770002');
    }

    public function test_client_requests_one_viewing_at_a_time_and_can_cancel(): void
    {
        $property = $this->property();
        $client = $this->client();

        $this->actingAs($client)->post(route('client.viewings.store', $property))->assertSessionHasNoErrors();
        $this->actingAs($client)->post(route('client.viewings.store', $property))->assertSessionHasErrors('viewing');

        $viewing = Viewing::sole();
        $this->assertSame('requested', $viewing->status);

        $this->actingAs($client)->patch(route('client.viewings.cancel', $viewing));
        $this->assertSame('cancelled', $viewing->refresh()->status);
    }

    public function test_staff_book_a_viewing_and_log_feedback_the_owner_then_sees(): void
    {
        $owner = $this->owner();
        $property = $this->property($owner);
        $client = $this->client();
        $this->actingAs($client)->post(route('client.viewings.store', $property));
        $viewing = Viewing::sole();

        $this->actingAs($this->staff('receptionist'))
            ->patch(route('staff.viewings.schedule', $viewing), ['scheduled_for' => now()->addDays(2)->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $viewing->refresh()->status);

        $specialist = $this->staff('property_specialist');
        $this->actingAs($specialist)->patch(route('staff.viewings.log', $viewing), ['status' => 'completed'])
            ->assertSessionHasErrors('feedback_category_id');

        $this->actingAs($specialist)->patch(route('staff.viewings.log', $viewing), [
            'status' => 'completed',
            'feedback_category_id' => FeedbackCategory::where('label', 'Financial constraint')->value('id'),
            'staff_note' => 'Little expensive',
        ])->assertSessionHasNoErrors();

        $viewing->refresh();
        $this->assertSame('completed', $viewing->status);
        $this->assertSame($specialist->id, $viewing->logged_by_user_id);

        // The owner sees the count, the reason share and the note, but never the client's name.
        $this->actingAs($owner)->get(route('owner.properties.show', $property))
            ->assertSee('Financial constraint')->assertSee('100%')->assertSee('Little expensive')
            ->assertSee($specialist->name)->assertDontSee($client->name);
    }

    public function test_receptionist_cannot_log_a_viewing_outcome(): void
    {
        $property = $this->property();
        $this->actingAs($this->client())->post(route('client.viewings.store', $property));

        $this->actingAs($this->staff('receptionist'))
            ->patch(route('staff.viewings.log', Viewing::sole()), ['status' => 'no_show'])->assertForbidden();
    }

    private function applicationForm(): array
    {
        return [
            'lease_term_id' => LeaseTerm::where('months', 60)->value('id'),
            'requested_start_date' => today()->addWeek()->toDateString(),
            'occupants' => 3,
        ];
    }

    public function test_client_applies_once_and_can_withdraw(): void
    {
        $property = $this->property();
        $client = $this->client();

        $this->actingAs($client)->post(route('client.applications.store', $property), $this->applicationForm())
            ->assertRedirect(route('client.applications.index'));
        $this->actingAs($client)->post(route('client.applications.store', $property), $this->applicationForm())
            ->assertSessionHasErrors('lease_term_id');

        $application = Application::sole();
        $this->assertSame('submitted', $application->status);
        $this->assertSame(60, $application->leaseTerm->months);

        $this->actingAs($client)->patch(route('client.applications.withdraw', $application));
        $this->assertSame('withdrawn', $application->refresh()->status);
    }

    public function test_client_with_unverified_email_cannot_apply(): void
    {
        $client = $this->client([], ['email_verified_at' => null]);

        $this->actingAs($client)->post(route('client.applications.store', $this->property()), $this->applicationForm())
            ->assertRedirect(route('verification.notice'));
    }

    public function test_specialist_approves_or_rejects_an_application_in_their_branch(): void
    {
        $client = $this->client();
        $application = $this->application($this->property(), $client);
        $specialist = $this->staff('property_specialist');

        $this->actingAs($specialist)->patch(route('staff.applications.decide', $application), ['decision' => 'rejected'])
            ->assertSessionHasErrors('decision_note');
        $this->actingAs($this->staff('property_specialist', 'HLM'))
            ->patch(route('staff.applications.decide', $application), ['decision' => 'approved'])->assertNotFound();

        $this->actingAs($specialist)->patch(route('staff.applications.decide', $application), ['decision' => 'approved']);

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertSame($specialist->id, $application->reviewed_by_user_id);
        $this->assertSame(1, $client->notifications()->count());
    }
}
