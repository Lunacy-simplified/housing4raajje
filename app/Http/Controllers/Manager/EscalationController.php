<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Escalation;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;

class EscalationController extends Controller
{
    public function index()
    {
        return view('manager.escalations', [
            'escalations' => Escalation::where('branch_id', $this->branchId())
                ->with('raisedBy', 'resolvedBy', 'lease', 'application', 'maintenanceRequest.property')
                ->orderByRaw("status = 'resolved'")->latest()->paginate(20),
        ]);
    }

    public function resolve(Request $request, Escalation $escalation)
    {
        $this->inBranch($escalation->branch_id);
        abort_unless($escalation->status === 'open', 403);

        $data = $request->validate(['resolution' => ['required', 'string', 'max:1000']]);

        $escalation->status = 'resolved';
        $escalation->resolution = $data['resolution'];
        $escalation->resolved_by_user_id = $request->user()->id;
        $escalation->resolved_at = now();
        $escalation->save();

        Audit::log('escalation.resolve', $escalation);
        Notice::to($escalation->raisedBy, 'Your escalation was resolved', str($data['resolution'])->limit(120), route('staff.dashboard'));

        return back()->with('status', 'Escalation resolved.');
    }
}
