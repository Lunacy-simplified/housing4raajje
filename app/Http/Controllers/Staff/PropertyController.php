<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Verification queue: staff check each new listing in their branch before it goes live. */
class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'changes_requested', 'approved', 'rejected'])],
        ])['status'] ?? 'pending';

        return view('staff.properties.index', [
            'status' => $status,
            'properties' => Property::where('branch_id', $this->branchId())
                ->where('verification_status', $status)
                ->with('district', 'owner')->oldest('submitted_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Property $property)
    {
        $this->inBranch($property->branch_id);
        abort_if($property->verification_status === 'draft', 404);

        return view('staff.properties.show', [
            'property' => $property->load('district', 'branch', 'owner.ownerProfile', 'photos', 'verifiedBy'),
            // Lease matchmaker: clients whose budget, area and room count fit this listing.
            'matches' => $property->verification_status === 'approved'
                ? $property->matchingClients()->with('clientProfile')->limit(20)->get()
                : collect(),
        ]);
    }

    public function decide(Request $request, Property $property)
    {
        $this->inBranch($property->branch_id);
        abort_unless($property->verification_status === 'pending', 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'changes_requested', 'rejected'])],
            'note' => ['nullable', 'required_unless:decision,approved', 'string', 'max:1000'],
        ], ['note.required_unless' => 'Tell the owner why.']);

        $property->verification_status = $data['decision'];

        if ($data['decision'] === 'approved') {
            $property->availability_status = 'available';
            $property->verified_by_user_id = $request->user()->id;
            $property->verified_at = now();
        }

        $property->save();
        Audit::log('property.'.$data['decision'], $property, ['verification_status' => 'pending'], ['verification_status' => $data['decision']]);

        $outcome = ['approved' => 'approved and is now live', 'changes_requested' => 'sent back for changes', 'rejected' => 'rejected'][$data['decision']];
        Notice::to(
            $property->owner,
            "Property {$property->reference} was {$outcome}",
            $data['note'] ?? 'Clients can now find it in search.',
            route('owner.properties.show', $property)
        );

        if ($data['decision'] === 'approved') {
            Notice::to(
                $property->matchingClients()->get(),
                'A new property matches what you are looking for',
                "{$property->rooms} rooms in {$property->district->name} for MVR ".number_format($property->monthly_rent).' a month.',
                route('client.properties.show', $property)
            );
        }

        return redirect()->route('staff.properties.index')->with('status', "Property {$property->reference} {$outcome}.");
    }
}
