<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::in(['submitted', 'approved', 'rejected', 'withdrawn'])],
        ])['status'] ?? 'submitted';

        return view('staff.applications', [
            'status' => $status,
            'applications' => Application::where('status', $status)
                ->whereHas('property', fn ($q) => $q->where('branch_id', $this->branchId()))
                ->with('property.district', 'client.clientProfile', 'leaseTerm', 'lease')
                ->oldest('submitted_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function decide(Request $request, Application $application)
    {
        $this->inBranch($application->property->branch_id);
        abort_unless($application->status === 'submitted', 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], ['decision_note.required_if' => 'Give the client a reason.']);

        $application->status = $data['decision'];
        $application->decision_note = $data['decision_note'] ?? null;
        $application->reviewed_at = now();
        $application->reviewed_by_user_id = $request->user()->id;
        $application->save();

        Audit::log('application.'.$data['decision'], $application);
        Notice::to(
            $application->client,
            "Application {$application->reference} was {$data['decision']}",
            $data['decision_note'] ?? 'The branch will prepare your lease next.',
            route('client.applications.index')
        );

        return back()->with('status', "Application {$application->reference} {$data['decision']}.");
    }
}
