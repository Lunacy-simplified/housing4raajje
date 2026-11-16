<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Inquiry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payout;
use App\Models\Property;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Viewing;
use Illuminate\Http\Request;

/** Each role's home page: a few counts, each linking to the list behind it. */
class DashboardController extends Controller
{
    public function owner(Request $request)
    {
        $user = $request->user();

        return $this->page('Owner', [
            ['My properties', $user->ownedProperties()->count(), route('owner.properties.index')],
            ['Active leases', $user->ownerLeases()->where('status', 'active')->count(), route('leases.index')],
            ['Leases to sign', $user->leasesToSign()->count(), route('leases.index')],
            ['Pending payouts', $user->payouts()->where('status', 'pending')->count(), route('owner.earnings.index')],
        ]);
    }

    public function client(Request $request)
    {
        $user = $request->user();

        return $this->page('Client', [
            ['Upcoming viewings', $user->viewings()->whereIn('status', ['requested', 'scheduled'])->count(), route('client.viewings.index')],
            ['Open applications', $user->applications()->where('status', 'submitted')->count(), route('client.applications.index')],
            ['Leases to sign', $user->leasesToSign()->count(), route('leases.index')],
            ['Active leases', $user->clientLeases()->where('status', 'active')->count(), route('leases.index')],
        ]);
    }

    public function staff(Request $request)
    {
        $branch = $this->branchId();
        $inBranch = fn ($query) => $query->where('branch_id', $branch);

        return $this->page('Staff', [
            ['Properties to check', Property::where('branch_id', $branch)->where('verification_status', 'pending')->count(), route('staff.properties.index')],
            ['Applications to review', Application::where('status', 'submitted')->whereHas('property', $inBranch)->count(), route('staff.applications.index')],
            ['Viewings to schedule', Viewing::where('status', 'requested')->whereHas('property', $inBranch)->count(), route('staff.viewings.index')],
            ['Leases awaiting signature', Lease::where('status', 'pending_signature')->whereHas('property', $inBranch)->count(), route('staff.leases.index')],
            ['Open maintenance', MaintenanceRequest::whereNotIn('status', ['resolved', 'closed'])->whereHas('property', $inBranch)->count(), route('staff.maintenance.index')],
            ['Open inquiries', Inquiry::where('branch_id', $branch)->where('status', 'open')->count(), route('staff.inquiries.index')],
            ['My open tickets', SupportTicket::where('assigned_to_user_id', $request->user()->id)->whereIn('status', ['open', 'in_progress'])->count(), route('staff.tickets.index')],
        ]);
    }

    public function admin()
    {
        $count = fn (string ...$roles) => User::whereHas('role', fn ($q) => $q->whereIn('code', $roles))->count();

        return $this->page('Admin', [
            ['Staff accounts', $count(...User::STAFF_ROLES), route('admin.staff.index')],
            ['Owners', $count('owner'), route('admin.audit.index')],
            ['Clients', $count('client'), route('admin.audit.index')],
            ['Pending payouts', Payout::where('status', 'pending')->count(), route('admin.payouts.index')],
            ['Audit entries today', AuditLog::whereDate('created_at', today())->count(), route('admin.audit.index')],
        ]);
    }

    private function page(string $portal, array $stats)
    {
        return view('dashboard', ['portal' => $portal, 'stats' => $stats]);
    }
}
