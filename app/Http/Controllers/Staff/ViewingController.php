<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\FeedbackCategory;
use App\Models\Viewing;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Viewing log: book a slot, then record what the client said after the visit. */
class ViewingController extends Controller
{
    public function index(Request $request)
    {
        $open = $request->query('show') !== 'past';

        return view('staff.viewings', [
            'open' => $open,
            'categories' => FeedbackCategory::where('is_active', true)->orderBy('sort_order')->pluck('label', 'id'),
            'viewings' => Viewing::whereHas('property', fn ($q) => $q->where('branch_id', $this->branchId()))
                ->when($open, fn ($q) => $q->whereIn('status', ['requested', 'scheduled']), fn ($q) => $q->whereNotIn('status', ['requested', 'scheduled']))
                ->with('property.district', 'client', 'feedbackCategory', 'loggedBy')
                ->latest('requested_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function schedule(Request $request, Viewing $viewing)
    {
        $this->open($viewing);

        $data = $request->validate(['scheduled_for' => ['required', 'date', 'after:now']]);

        $viewing->scheduled_for = $data['scheduled_for'];
        $viewing->scheduled_by_user_id = $request->user()->id;
        $viewing->status = 'scheduled';
        $viewing->save();

        Notice::to(
            $viewing->client,
            'Your viewing is booked',
            "{$viewing->property->reference} on {$viewing->scheduled_for->format('j M Y, g:i a')}.",
            route('client.viewings.index')
        );

        return back()->with('status', 'Viewing booked and the client was told.');
    }

    /** Record the outcome. A completed viewing must have a standard feedback reason. */
    public function log(Request $request, Viewing $viewing)
    {
        $this->open($viewing);

        $data = $request->validate([
            'status' => ['required', Rule::in(['completed', 'no_show', 'declined', 'cancelled'])],
            'feedback_category_id' => ['nullable', 'required_if:status,completed', Rule::exists('feedback_categories', 'id')->where('is_active', true)],
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ], ['feedback_category_id.required_if' => 'Pick the main feedback reason.']);

        $viewing->status = $data['status'];
        $viewing->staff_note = $data['staff_note'] ?? null;

        if ($data['status'] === 'completed') {
            $viewing->feedback_category_id = $data['feedback_category_id'];
            $viewing->logged_by_user_id = $request->user()->id;
            $viewing->logged_at = now();
        }

        $viewing->save();

        return back()->with('status', 'Viewing outcome saved.');
    }

    private function open(Viewing $viewing): void
    {
        $this->inBranch($viewing->property->branch_id);
        abort_unless(in_array($viewing->status, ['requested', 'scheduled'], true), 403);
    }
}
