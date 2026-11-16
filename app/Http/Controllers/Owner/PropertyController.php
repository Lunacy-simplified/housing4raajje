<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The owner's side of the Apartment Registration Form and the property dashboard. */
class PropertyController extends Controller
{
    const MAX_PHOTOS = 6;

    public function index(Request $request)
    {
        return view('owner.properties.index', [
            'properties' => $request->user()->ownedProperties()->with('district')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('owner.properties.form', ['property' => new Property, 'districts' => $this->districts()]);
    }

    public function store(Request $request)
    {
        $property = new Property($this->validated($request));
        $property->owner_user_id = $request->user()->id;
        $property->branch_id = $property->district->branch_id;
        $property->save();

        return redirect()->route('owner.properties.show', $property)
            ->with('status', 'Saved as a draft. Add photos, then submit it for review.');
    }

    public function show(Property $property)
    {
        $this->own($property);

        $completed = $property->viewings()->where('status', 'completed')
            ->with('feedbackCategory', 'loggedBy')->latest('logged_at')->get();
        $rejections = $completed->filter(fn ($viewing) => $viewing->feedbackCategory->is_rejection);
        $lease = $property->leases()->where('status', 'active')->first();

        return view('owner.properties.show', [
            'property' => $property->load('district', 'branch', 'photos'),
            'viewings' => $completed,
            // Share of rejections per reason, e.g. ['Financial constraint' => 70, 'Location' => 30].
            'reasons' => $rejections->countBy(fn ($viewing) => $viewing->feedbackCategory->label)
                ->map(fn ($count) => (int) round($count / $rejections->count() * 100))->sortDesc(),
            'lease' => $lease,
            'rentPaid' => $lease?->payments()->where('kind', 'rent')
                ->whereDate('period_month', now()->startOfMonth())->exists(),
        ]);
    }

    public function edit(Property $property)
    {
        $this->editable($property);

        return view('owner.properties.form', ['property' => $property, 'districts' => $this->districts()]);
    }

    public function update(Request $request, Property $property)
    {
        $this->editable($property);

        $property->fill($this->validated($request));
        $property->branch_id = District::findOrFail($property->district_id)->branch_id;
        $property->save();

        return redirect()->route('owner.properties.show', $property)->with('status', 'Changes saved.');
    }

    /** Send the listing to the branch that covers its district. */
    public function submit(Property $property)
    {
        $this->editable($property);

        $property->verification_status = 'pending';
        $property->submitted_at = now();
        $property->save();

        return back()->with('status', "Submitted to the {$property->branch->name} branch for review.");
    }

    /** Owners switch between Available and Under maintenance. Rented is set by an active lease. */
    public function availability(Request $request, Property $property)
    {
        $this->own($property);

        $data = $request->validate(['availability_status' => ['required', Rule::in(['available', 'under_maintenance'])]]);

        if ($property->verification_status !== 'approved' || $property->availability_status === 'rented') {
            throw ValidationException::withMessages(['availability_status' => 'Availability can only be changed on an approved property that is not rented.']);
        }

        $property->availability_status = $data['availability_status'];
        $property->save();

        return back()->with('status', 'Availability updated.');
    }

    public function photoStore(Request $request, Property $property)
    {
        $this->own($property);

        $request->validate(['photo' => ['required', 'image', 'max:4096']]);

        if ($property->photos()->count() >= self::MAX_PHOTOS) {
            throw ValidationException::withMessages(['photo' => 'A property can have up to '.self::MAX_PHOTOS.' photos.']);
        }

        $property->photos()->create([
            'file_path' => $request->file('photo')->store('property-photos', 'local'),
            'sort_order' => $property->photos()->count(),
        ]);

        return back()->with('status', 'Photo added.');
    }

    public function photoDestroy(Property $property, PropertyPhoto $photo)
    {
        $this->own($property);
        abort_unless($photo->property_id === $property->id, 404);

        Storage::disk('local')->delete($photo->file_path);
        $photo->delete();

        return back()->with('status', 'Photo removed.');
    }

    private function own(Property $property): void
    {
        abort_unless($property->owner_user_id === auth()->id(), 404);
    }

    /** Only drafts and listings sent back for changes can be edited or submitted. */
    private function editable(Property $property): void
    {
        $this->own($property);
        abort_unless(in_array($property->verification_status, ['draft', 'changes_requested'], true), 403);
    }

    private function districts()
    {
        return District::where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'district_id' => ['required', Rule::exists('districts', 'id')->where('is_active', true)],
            'house_name' => ['required', 'string', 'max:80'],
            'road' => ['required', 'string', 'max:80'],
            'unit_no' => ['nullable', 'string', 'max:10'],
            'floor' => ['nullable', 'integer', 'between:0,100'],
            'property_type' => ['required', Rule::in(['apartment', 'flat'])],
            'rooms' => ['required', 'integer', 'between:1,20'],
            'monthly_rent' => ['required', 'numeric', 'min:1', 'max:9999999'],
        ]);
    }
}
