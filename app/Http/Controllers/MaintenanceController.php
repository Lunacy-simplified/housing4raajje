<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Owners and clients report repairs; the property's branch handles them. */
class MaintenanceController extends Controller
{
    const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function index(Request $request)
    {
        $user = $request->user();

        return view('maintenance', [
            'properties' => $this->properties($request)->get()->mapWithKeys(fn ($p) => [$p->id => "{$p->reference} · {$p->house_name}"]),
            'requests' => MaintenanceRequest::where('raised_by_user_id', $user->id)
                ->orWhereHas('property', fn ($q) => $q->where('owner_user_id', $user->id))
                ->with('property')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $property = $this->properties($request)->findOrFail($data['property_id']);
        $lease = $property->leases()->where('status', 'active')->first();

        $maintenance = new MaintenanceRequest($data);
        $maintenance->property_id = $property->id;
        $maintenance->lease_id = $lease?->id;
        $maintenance->raised_by_user_id = $user->id;
        $maintenance->save();

        // Tell the other side: the owner when a client reports, the tenant when the owner reports.
        Notice::to(
            $user->id === $property->owner_user_id ? $lease?->client : $property->owner,
            "New maintenance request for {$property->reference}",
            ucfirst($data['category']).': '.str($data['description'])->limit(120),
            route('maintenance.index')
        );

        return back()->with('status', 'Maintenance request sent to the branch.');
    }

    /** Properties the user may report on: an owner's approved ones, or the ones a client is renting. */
    private function properties(Request $request)
    {
        $user = $request->user();

        return $user->hasRole('owner')
            ? Property::where('owner_user_id', $user->id)->where('verification_status', 'approved')
            : Property::whereHas('leases', fn ($q) => $q->where('client_user_id', $user->id)->where('status', 'active'));
    }
}
