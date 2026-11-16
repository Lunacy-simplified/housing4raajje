<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    public function index()
    {
        return view('staff.tickets', [
            'tickets' => SupportTicket::where('branch_id', $this->branchId())->with('assignedTo', 'openedBy', 'inquiry')
                ->orderByRaw("status in ('resolved', 'closed')")->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $this->inBranch($ticket->branch_id);

        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);

        $ticket->status = $data['status'];
        $ticket->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?? now()) : null;
        $ticket->save();

        return back()->with('status', 'Ticket updated.');
    }
}
