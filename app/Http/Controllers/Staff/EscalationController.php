<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Escalation;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Staff pass a disputed lease, application or maintenance request up to their branch manager. */
class EscalationController extends Controller
{
    const SUBJECTS = [
        'lease' => [Lease::class, 'lease_id'],
        'application' => [Application::class, 'application_id'],
        'maintenance' => [MaintenanceRequest::class, 'maintenance_request_id'],
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_type' => ['required', Rule::in(array_keys(self::SUBJECTS))],
            'subject_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        [$model, $column] = self::SUBJECTS[$data['subject_type']];
        $subject = $model::with('property')->findOrFail($data['subject_id']);
        $this->inBranch($subject->property->branch_id);

        $escalation = new Escalation(['reason' => $data['reason']]);
        $escalation->branch_id = $this->branchId();
        $escalation->raised_by_user_id = $request->user()->id;
        $escalation->{$column} = $subject->id;
        $escalation->save();

        Notice::to(
            User::whereHas('role', fn ($q) => $q->where('code', 'branch_manager'))
                ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId()))->get(),
            'An issue was escalated to you',
            str($data['reason'])->limit(120),
            route('manager.escalations.index')
        );

        return back()->with('status', 'Escalated to the branch manager.');
    }
}
