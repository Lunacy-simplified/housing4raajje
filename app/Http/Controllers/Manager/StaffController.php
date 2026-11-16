<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\StaffCompensation;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** A branch manager's view of their own branch's staff, including salaries (hidden from other staff). */
class StaffController extends Controller
{
    public function index()
    {
        return view('manager.staff', [
            'staff' => User::whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId()))
                ->with(['role', 'staffProfile.compensations' => fn ($q) => $q->whereNull('effective_to')])
                ->orderBy('name')->get(),
        ]);
    }

    /** Record a new salary. The previous one ends the day before the new one starts. */
    public function salary(Request $request, User $user)
    {
        $this->inBranch($user->staffProfile?->branch_id);

        $data = $request->validate([
            'monthly_salary' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'effective_from' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($request, $user, $data) {
            StaffCompensation::where('staff_user_id', $user->id)->whereNull('effective_to')
                ->update(['effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString()]);

            $salary = new StaffCompensation($data + ['recorded_by_user_id' => $request->user()->id]);
            $salary->staff_user_id = $user->id;
            $salary->save();

            Audit::log('salary.record', $salary, [], ['staff_user_id' => $user->id, 'monthly_salary' => $data['monthly_salary']]);
        });

        return back()->with('status', "Salary recorded for {$user->name}.");
    }
}
