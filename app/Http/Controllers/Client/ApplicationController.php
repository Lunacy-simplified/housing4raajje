<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    const INCOME_BRACKETS = ['Under 15,000', '15,000 to 30,000', '30,000 to 50,000', 'Over 50,000'];

    public function index(Request $request)
    {
        return view('client.applications', [
            'applications' => $request->user()->applications()->with('property.district', 'leaseTerm', 'lease')->latest()->get(),
        ]);
    }

    /** Apply to rent a property for one of the fixed lease lengths. */
    public function store(Request $request, Property $property)
    {
        abort_unless($property->verification_status === 'approved' && $property->availability_status === 'available', 404);

        $data = $request->validate([
            'lease_term_id' => ['required', Rule::exists('lease_terms', 'id')->where('is_active', true)],
            'requested_start_date' => ['required', 'date', 'after_or_equal:today'],
            'occupants' => ['required', 'integer', 'between:1,20'],
            'employer' => ['nullable', 'string', 'max:120'],
            'income_bracket' => ['nullable', Rule::in(self::INCOME_BRACKETS)],
        ]);

        $user = $request->user();

        // The database also enforces this: one open application per client and property.
        if ($user->applications()->where('property_id', $property->id)->whereIn('status', ['draft', 'submitted', 'under_review'])->exists()) {
            throw ValidationException::withMessages(['lease_term_id' => 'You already have an open application for this property.']);
        }

        $application = new Application($data);
        $application->property_id = $property->id;
        $application->client_user_id = $user->id;
        $application->status = 'submitted';
        $application->submitted_at = now();
        $application->save();

        return redirect()->route('client.applications.index')
            ->with('status', "Application {$application->reference} submitted. The branch will review it.");
    }

    public function withdraw(Request $request, Application $application)
    {
        abort_unless($application->client_user_id === $request->user()->id, 404);
        abort_unless($application->status === 'submitted', 403);

        $application->status = 'withdrawn';
        $application->save();

        return back()->with('status', 'Application withdrawn.');
    }
}
