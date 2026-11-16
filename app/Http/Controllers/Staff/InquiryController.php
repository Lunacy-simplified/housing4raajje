<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Front desk: log walk-in, phone and email inquiries, then close them or route them to a specialist. */
class InquiryController extends Controller
{
    public function index()
    {
        return view('staff.inquiries', [
            'inquiries' => Inquiry::where('branch_id', $this->branchId())->with('loggedBy')
                ->orderByRaw("status <> 'open'")->latest()->paginate(20),
            'specialists' => $this->specialists()->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['walk_in', 'phone', 'email'])],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry = new Inquiry($data);
        $inquiry->branch_id = $this->branchId();
        $inquiry->logged_by_user_id = $request->user()->id;
        $inquiry->save();

        return back()->with('status', 'Inquiry logged.');
    }

    /** Turn an inquiry into a support ticket for a specialist in the same branch. */
    public function route(Request $request, Inquiry $inquiry)
    {
        $this->open($inquiry);

        $data = $request->validate(['assigned_to_user_id' => ['required', 'integer']]);
        $specialist = $this->specialists()->findOrFail($data['assigned_to_user_id']);

        DB::transaction(function () use ($request, $inquiry, $specialist) {
            $ticket = new SupportTicket(['subject' => $inquiry->subject, 'description' => $inquiry->details]);
            $ticket->inquiry_id = $inquiry->id;
            $ticket->branch_id = $inquiry->branch_id;
            $ticket->opened_by_user_id = $request->user()->id;
            $ticket->assigned_to_user_id = $specialist->id;
            $ticket->save();

            $inquiry->status = 'routed';
            $inquiry->save();
        });

        Notice::to($specialist, 'A support ticket was routed to you', $inquiry->subject, route('staff.tickets.index'));

        return back()->with('status', "Routed to {$specialist->name}.");
    }

    public function close(Inquiry $inquiry)
    {
        $this->open($inquiry);

        $inquiry->status = 'closed';
        $inquiry->save();

        return back()->with('status', 'Inquiry closed.');
    }

    private function open(Inquiry $inquiry): void
    {
        $this->inBranch($inquiry->branch_id);
        abort_unless($inquiry->status === 'open', 403);
    }

    private function specialists()
    {
        return User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('code', 'property_specialist'))
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId()))
            ->orderBy('name');
    }
}
