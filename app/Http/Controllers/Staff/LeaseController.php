<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Lease;
use App\Models\PaymentMethod;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Lease execution: staff turn an approved application into a contract for both parties to sign. */
class LeaseController extends Controller
{
    public function index()
    {
        $inBranch = fn ($q) => $q->where('branch_id', $this->branchId());

        return view('staff.leases.index', [
            'ready' => Application::where('status', 'approved')->whereDoesntHave('lease')
                ->whereHas('property', $inBranch)->with('property', 'client', 'leaseTerm')->get(),
            'leases' => Lease::whereHas('property', $inBranch)->with('property', 'client', 'owner')->latest()->paginate(20),
        ]);
    }

    public function create(Application $application)
    {
        $this->ready($application);

        return view('staff.leases.create', [
            'application' => $application->load('property.district', 'client', 'leaseTerm'),
            'methods' => $this->methods(),
        ]);
    }

    public function store(Request $request, Application $application)
    {
        $this->ready($application);

        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'monthly_rent' => ['required', 'numeric', 'min:1', 'max:9999999'],
            'advance_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
        ]);

        $property = $application->property;

        // The database also enforces this: one live lease per property.
        if ($property->leases()->whereIn('status', ['pending_signature', 'active'])->exists()) {
            throw ValidationException::withMessages(['start_date' => 'This property already has a live lease.']);
        }

        $lease = new Lease($data + ['term_months' => $application->leaseTerm->months]);
        $lease->application_id = $application->id;
        $lease->property_id = $property->id;
        $lease->client_user_id = $application->client_user_id;
        $lease->owner_user_id = $property->owner_user_id;
        $lease->executed_by_user_id = $request->user()->id;
        $lease->executed_at = now();
        $lease->status = 'pending_signature';
        $lease->save();

        Audit::log('lease.execute', $lease);
        Notice::to(
            [$lease->client, $lease->owner],
            "Lease {$lease->reference} is ready to sign",
            "Please read and sign the lease for {$property->reference}.",
            route('leases.show', $lease)
        );

        return redirect()->route('staff.leases.show', $lease)
            ->with('status', "Lease {$lease->reference} created. Both parties were asked to sign.");
    }

    public function show(Lease $lease)
    {
        $this->inBranch($lease->property->branch_id);

        return view('staff.leases.show', [
            'lease' => $lease->load('property.district', 'client', 'owner', 'executedBy.staffProfile', 'paymentMethod', 'signatures', 'payments.paymentMethod'),
            'methods' => $this->methods(),
        ]);
    }

    public function terminate(Request $request, Lease $lease)
    {
        $this->inBranch($lease->property->branch_id);
        abort_unless(in_array($lease->status, ['pending_signature', 'active'], true), 403);

        $data = $request->validate(['termination_reason' => ['required', 'string', 'max:1000']]);

        $wasActive = $lease->status === 'active';
        $lease->status = $wasActive ? 'terminated' : 'cancelled';
        $lease->terminated_at = now();
        $lease->termination_reason = $data['termination_reason'];
        $lease->save();

        if ($wasActive) {
            $lease->property->forceFill(['availability_status' => 'available'])->save();
        }

        Audit::log('lease.'.$lease->status, $lease, [], ['reason' => $data['termination_reason']]);
        Notice::to(
            [$lease->client, $lease->owner],
            "Lease {$lease->reference} was {$lease->status}",
            $data['termination_reason'],
            route('leases.show', $lease)
        );

        return back()->with('status', "Lease {$lease->reference} {$lease->status}.");
    }

    private function ready(Application $application): void
    {
        $this->inBranch($application->property->branch_id);
        abort_unless($application->status === 'approved' && ! $application->lease, 403);
    }

    private function methods()
    {
        return PaymentMethod::where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }
}
