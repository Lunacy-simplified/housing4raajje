<?php

namespace Tests\Feature\Property;

use App\Models\District;
use App\Models\Property;
use App\Notifications\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsRecords;
use Tests\TestCase;

/** Owner registers a property, the branch checks it, and it goes live (brief 2B, 2D). */
class PropertyListingTest extends TestCase
{
    use BuildsRecords, RefreshDatabase;

    protected $seed = true;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'district_id' => District::where('name', 'Hulhumalé Phase 1')->value('id'),
            'house_name' => 'Sea Breeze',
            'road' => 'Nirolhu Magu',
            'property_type' => 'flat',
            'rooms' => 2,
            'monthly_rent' => 12000,
        ];
    }

    public function test_owner_registers_a_draft_and_the_branch_comes_from_the_district(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('owner.properties.store'), $this->form())->assertRedirect();

        $property = Property::sole();
        $this->assertSame($owner->id, $property->owner_user_id);
        $this->assertSame('draft', $property->verification_status);
        $this->assertSame('HLM', $property->branch->code);
        $this->assertSame('H4R-APT-'.str_pad($property->id, 4, '0', STR_PAD_LEFT), $property->reference);
    }

    public function test_owner_with_unverified_email_cannot_register_a_property(): void
    {
        $owner = $this->owner(['email_verified_at' => null]);

        $this->actingAs($owner)->post(route('owner.properties.store'), $this->form())
            ->assertRedirect(route('verification.notice'));

        $this->assertSame(0, Property::count());
    }

    public function test_property_form_is_validated(): void
    {
        $this->actingAs($this->owner())
            ->post(route('owner.properties.store'), $this->form(['rooms' => 0, 'monthly_rent' => 0, 'property_type' => 'villa']))
            ->assertSessionHasErrors(['rooms', 'monthly_rent', 'property_type']);
    }

    public function test_owner_submits_a_draft_and_can_no_longer_edit_it(): void
    {
        $owner = $this->owner();
        $property = $this->property($owner, 'draft');

        $this->actingAs($owner)->post(route('owner.properties.submit', $property))->assertRedirect();

        $this->assertSame('pending', $property->refresh()->verification_status);
        $this->assertNotNull($property->submitted_at);
        $this->actingAs($owner)->get(route('owner.properties.edit', $property))->assertForbidden();
    }

    public function test_owner_cannot_open_another_owners_property(): void
    {
        $property = $this->property();

        $this->actingAs($this->owner())->get(route('owner.properties.show', $property))->assertNotFound();
    }

    public function test_owner_adds_and_removes_a_photo(): void
    {
        Storage::fake('local');
        $owner = $this->owner();
        $property = $this->property($owner, 'draft');

        $this->actingAs($owner)->post(route('owner.properties.photos.store', $property), [
            'photo' => UploadedFile::fake()->create('front.jpg', 200, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $photo = $property->photos()->sole();
        Storage::disk('local')->assertExists($photo->file_path);
        $this->actingAs($owner)->get(route('photos.show', $photo))->assertOk();

        $this->actingAs($owner)->delete(route('owner.properties.photos.destroy', [$property, $photo]));
        Storage::disk('local')->assertMissing($photo->file_path);
        $this->assertSame(0, $property->photos()->count());
    }

    public function test_owner_switches_availability_but_not_while_rented(): void
    {
        $owner = $this->owner();
        $property = $this->property($owner);

        $this->actingAs($owner)->patch(route('owner.properties.availability', $property), ['availability_status' => 'under_maintenance']);
        $this->assertSame('under_maintenance', $property->refresh()->availability_status);

        $property->forceFill(['availability_status' => 'rented'])->save();
        $this->actingAs($owner)->patch(route('owner.properties.availability', $property), ['availability_status' => 'available'])
            ->assertSessionHasErrors('availability_status');
    }

    public function test_staff_only_see_properties_in_their_own_branch(): void
    {
        $male = $this->property(null, 'pending', ['house_name' => 'Male House']);
        $hulhumale = $this->property(null, 'pending', ['house_name' => 'Hulhumale House', 'district' => 'Hulhumalé Phase 1']);
        $specialist = $this->staff('property_specialist', 'MLE');

        $this->actingAs($specialist)->get(route('staff.properties.index'))
            ->assertOk()->assertSee('Male House')->assertDontSee('Hulhumale House');
        $this->actingAs($specialist)->get(route('staff.properties.show', $hulhumale))->assertNotFound();
        $this->actingAs($specialist)->patch(route('staff.properties.decide', $hulhumale), ['decision' => 'approved'])->assertNotFound();
        $this->actingAs($specialist)->get(route('staff.properties.show', $male))->assertOk();
    }

    public function test_specialist_approves_a_property_and_matching_clients_are_told(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $property = $this->property($owner, 'pending');
        $specialist = $this->staff('property_specialist');
        $match = $this->client(['max_monthly_rent' => 20000]);
        $match->clientProfile->preferredBranches()->attach($property->branch_id);
        $tooPoor = $this->client(['max_monthly_rent' => 9000]);
        $tooPoor->clientProfile->preferredBranches()->attach($property->branch_id);
        $elsewhere = $this->client(['max_monthly_rent' => 20000]);

        $this->actingAs($specialist)->patch(route('staff.properties.decide', $property), ['decision' => 'approved'])
            ->assertRedirect(route('staff.properties.index'));

        $property->refresh();
        $this->assertSame('approved', $property->verification_status);
        $this->assertSame('available', $property->availability_status);
        $this->assertSame($specialist->id, $property->verified_by_user_id);
        $this->assertDatabaseHas('audit_log', ['action' => 'property.approved', 'record_id' => $property->id, 'user_id' => $specialist->id]);
        Notification::assertSentTo($owner, Notice::class);
        Notification::assertSentTo($match, Notice::class);
        Notification::assertNotSentTo([$tooPoor, $elsewhere], Notice::class);

        $this->actingAs($specialist)->get(route('staff.properties.show', $property))->assertSee($match->name)->assertDontSee($tooPoor->name);
    }

    public function test_sending_back_or_rejecting_needs_a_note(): void
    {
        $property = $this->property(null, 'pending');
        $specialist = $this->staff('property_specialist');

        $this->actingAs($specialist)->patch(route('staff.properties.decide', $property), ['decision' => 'changes_requested'])
            ->assertSessionHasErrors('note');

        $this->actingAs($specialist)->patch(route('staff.properties.decide', $property), ['decision' => 'changes_requested', 'note' => 'Add the unit number.']);
        $this->assertSame('changes_requested', $property->refresh()->verification_status);
        $this->assertNull($property->availability_status);
    }

    public function test_receptionist_cannot_decide_on_a_property(): void
    {
        $property = $this->property(null, 'pending');

        $this->actingAs($this->staff('receptionist'))
            ->patch(route('staff.properties.decide', $property), ['decision' => 'approved'])->assertForbidden();
    }
}
