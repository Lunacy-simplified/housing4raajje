<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    const STATUSES = ['submitted', 'triaged', 'assigned', 'in_progress', 'resolved', 'closed'];

    public function index()
    {
        return view('staff.maintenance', [
            'requests' => MaintenanceRequest::whereHas('property', fn ($q) => $q->where('branch_id', $this->branchId()))
                ->with('property', 'raisedBy', 'assignedTo')
                ->orderByRaw("status in ('resolved', 'closed')")->latest()->paginate(20),
        ]);
    }

    /** Move a request along. Whoever picks it up becomes the person handling it. */
    public function update(Request $request, MaintenanceRequest $maintenance)
    {
        $this->inBranch($maintenance->property->branch_id);

        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);

        $maintenance->status = $data['status'];
        $maintenance->assigned_to_user_id ??= in_array($data['status'], ['submitted', 'triaged'], true) ? null : $request->user()->id;
        $maintenance->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($maintenance->resolved_at ?? now()) : null;
        $maintenance->save();

        Notice::to(
            $maintenance->raisedBy,
            "Maintenance request for {$maintenance->property->reference} is now ".str_replace('_', ' ', $data['status']),
            str($maintenance->description)->limit(120),
            route('maintenance.index')
        );

        return back()->with('status', 'Maintenance request updated.');
    }
}
