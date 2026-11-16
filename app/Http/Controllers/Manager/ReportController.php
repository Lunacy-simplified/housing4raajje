<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Models\Viewing;

/** Branch report: occupancy, money collected this month, and what each staff member has handled. */
class ReportController extends Controller
{
    public function index()
    {
        $branch = $this->branchId();
        $inBranch = fn ($q) => $q->where('branch_id', $branch);

        $approved = Property::where('branch_id', $branch)->where('verification_status', 'approved');
        $live = (clone $approved)->count();
        $rented = (clone $approved)->where('availability_status', 'rented')->count();

        return view('manager.report', [
            'branch' => auth()->user()->staffProfile->branch,
            'stats' => [
                'Live properties' => $live,
                'Rented' => $rented,
                'Occupancy' => $live ? round($rented / $live * 100).'%' : '—',
                'Properties to check' => Property::where('branch_id', $branch)->where('verification_status', 'pending')->count(),
                'Active leases' => Lease::where('status', 'active')->whereHas('property', $inBranch)->count(),
                'Rent collected this month (MVR)' => number_format(Payment::where('kind', 'rent')
                    ->whereBetween('received_on', [now()->startOfMonth(), now()->endOfMonth()])
                    ->whereHas('lease.property', $inBranch)->sum('amount'), 2),
                'Viewings completed this month' => Viewing::where('status', 'completed')
                    ->where('logged_at', '>=', now()->startOfMonth())->whereHas('property', $inBranch)->count(),
            ],
            'staff' => User::whereHas('staffProfile', $inBranch)->with('role', 'staffProfile')
                ->withCount([
                    'verifiedProperties',
                    'loggedViewings' => fn ($q) => $q->where('status', 'completed'),
                    'executedLeases',
                ])->orderBy('name')->get(),
        ]);
    }
}
