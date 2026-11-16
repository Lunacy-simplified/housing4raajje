<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** The client's and owner's view of their leases, and the digital sign-off. */
class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('leases.index', [
            'leases' => Lease::where(fn ($q) => $q->where('client_user_id', $user->id)->orWhere('owner_user_id', $user->id))
                ->with('property.district', 'signatures')->latest()->get(),
        ]);
    }

    public function show(Request $request, Lease $lease)
    {
        return view('leases.show', [
            'party' => $this->party($request, $lease),
            'lease' => $lease->load('property.district', 'client', 'owner', 'executedBy.staffProfile', 'paymentMethod', 'signatures', 'payments.paymentMethod'),
        ]);
    }

    /**
     * Sign the lease. The signer proves who they are by typing their password again.
     * When both the client and the owner have signed, the lease becomes active and the property is marked rented.
     */
    public function sign(Request $request, Lease $lease)
    {
        $party = $this->party($request, $lease);

        abort_unless($lease->status === 'pending_signature', 403);
        abort_if($lease->signatures->contains('party', $party), 403);

        $request->validate(['password' => ['required', 'current_password']]);

        DB::transaction(function () use ($request, $lease, $party) {
            DB::table('lease_signatures')->insert([
                'lease_id' => $lease->id,
                'party' => $party,
                'signed_by_user_id' => $request->user()->id,
                'signed_at' => now(),
                'verification_method' => 'password',
                'ip_address' => $request->ip(),
            ]);

            if ($lease->signatures()->count() === 2) {
                $lease->status = 'active';
                $lease->activated_at = now();
                $lease->save();
                $lease->property->forceFill(['availability_status' => 'rented'])->save();
            }
        });

        Audit::log('lease.sign', $lease, [], ['party' => $party]);

        if ($lease->status === 'active') {
            Notice::to(
                [$lease->client, $lease->owner],
                "Lease {$lease->reference} is now active",
                "Both parties have signed. It runs from {$lease->start_date->format('j M Y')} to {$lease->end_date->format('j M Y')}.",
                route('leases.show', $lease)
            );
        }

        return back()->with('status', $lease->status === 'active' ? 'Signed. The lease is now active.' : 'Signed. Waiting for the other party.');
    }

    /** Which side of the lease the signed-in user is on; 404 for anyone else. */
    private function party(Request $request, Lease $lease): string
    {
        return match ($request->user()->id) {
            $lease->client_user_id => 'client',
            $lease->owner_user_id => 'owner',
            default => abort(404),
        };
    }
}
