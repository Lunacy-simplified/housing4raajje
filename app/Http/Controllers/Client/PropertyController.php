<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LeaseTerm;
use App\Models\Property;
use Illuminate\Http\Request;

/** Property search: only approved, available listings are ever shown to clients. */
class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'rooms' => ['nullable', 'integer', 'min:1'],
            'max_rent' => ['nullable', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $properties = Property::where('verification_status', 'approved')
            ->where('availability_status', 'available')
            ->with('district', 'branch', 'photos')
            ->when($filters['rooms'] ?? null, fn ($q, $rooms) => $q->where('rooms', $rooms))
            ->when($filters['max_rent'] ?? null, fn ($q, $rent) => $q->where('monthly_rent', '<=', $rent))
            ->when($filters['branch_id'] ?? null, fn ($q, $branch) => $q->where('branch_id', $branch))
            ->orderBy('monthly_rent')
            ->paginate(12)
            ->withQueryString();

        return view('client.properties.index', [
            'properties' => $properties,
            'branches' => Branch::where('is_active', true)->orderBy('branch_no')->pluck('name', 'id'),
        ]);
    }

    public function show(Request $request, Property $property)
    {
        abort_unless($property->verification_status === 'approved', 404);

        $user = $request->user();

        return view('client.properties.show', [
            'property' => $property->load('district', 'branch', 'photos', 'owner'),
            'terms' => LeaseTerm::where('is_active', true)->orderBy('months')->pluck('label', 'id'),
            'viewing' => $user->viewings()->where('property_id', $property->id)->whereIn('status', ['requested', 'scheduled'])->first(),
            'application' => $user->applications()->where('property_id', $property->id)->whereIn('status', ['draft', 'submitted', 'under_review'])->first(),
        ]);
    }
}
