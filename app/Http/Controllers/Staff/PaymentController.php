<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Payment;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Financial booking: staff record rent and advance payments received against a lease. */
class PaymentController extends Controller
{
    public function store(Request $request, Lease $lease)
    {
        $this->inBranch($lease->property->branch_id);
        abort_unless($lease->status === 'active', 403);

        $data = $request->validate([
            'kind' => ['required', Rule::in(['rent', 'advance'])],
            'period_month' => ['nullable', 'required_if:kind,rent', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'bank_reference' => ['required', 'string', 'max:60'],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
        ], ['period_month.required_if' => 'Choose the month this rent covers.']);

        // Months are stored as their first day. Advance payments have no month.
        $data['period_month'] = $data['kind'] === 'rent'
            ? Carbon::createFromFormat('Y-m-d', $data['period_month'].'-01')->toDateString()
            : null;

        $payment = new Payment($data);
        $payment->lease_id = $lease->id;
        $payment->recorded_by_user_id = $request->user()->id;
        $payment->save();

        Audit::log('payment.record', $payment, [], ['amount' => $payment->amount, 'kind' => $payment->kind]);
        Notice::to(
            [$lease->client, $lease->owner],
            "Payment {$payment->reference} received",
            'MVR '.number_format($payment->amount, 2)." ({$payment->kind}) was recorded for lease {$lease->reference}.",
            route('leases.show', $lease)
        );

        return back()->with('status', "Payment {$payment->reference} recorded.");
    }
}
