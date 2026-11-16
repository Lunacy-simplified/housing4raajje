<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\SystemParameter;
use App\Notifications\Notice;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Owner payouts: rent collected for a month, minus the management fee, paid to each owner. */
class PayoutController extends Controller
{
    public function index()
    {
        return view('admin.payouts', [
            'payouts' => Payout::with('owner.ownerProfile')->orderByRaw("status = 'paid'")->orderByDesc('period_month')->paginate(20),
            'feePercent' => SystemParameter::setting('management_fee_percent', 10),
        ]);
    }

    /**
     * Gather every rent payment for the month that hasn't been paid out yet into one payout per owner.
     * Running it again for the same month adds late payments to a payout that is still pending.
     */
    public function generate(Request $request)
    {
        $data = $request->validate(['period_month' => ['required', 'date_format:Y-m']]);
        $month = Carbon::createFromFormat('Y-m-d', $data['period_month'].'-01')->toDateString();
        $feePercent = (float) SystemParameter::setting('management_fee_percent', 10);

        $created = DB::transaction(function () use ($month, $feePercent) {
            $byOwner = Payment::where('kind', 'rent')->whereNull('payout_id')->whereDate('period_month', $month)
                ->with('lease')->get()->groupBy(fn ($payment) => $payment->lease->owner_user_id);

            $count = 0;

            foreach ($byOwner as $ownerId => $payments) {
                $payout = Payout::firstOrNew(['owner_user_id' => $ownerId, 'period_month' => $month]);

                // A payout that has already been sent is never changed.
                if ($payout->status === 'paid') {
                    continue;
                }

                $gross = (float) $payout->gross_amount + $payments->sum('amount');
                $fee = round($gross * $feePercent / 100, 2);

                $payout->forceFill([
                    'owner_user_id' => $ownerId,
                    'period_month' => $month,
                    'gross_amount' => $gross,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $fee,
                    'net_amount' => round($gross - $fee, 2),
                ])->save();

                Payment::whereIn('id', $payments->pluck('id'))->update(['payout_id' => $payout->id]);
                Audit::log('payout.generate', $payout, [], ['gross_amount' => $gross]);
                $count++;
            }

            return $count;
        });

        return back()->with('status', $created ? "{$created} payout(s) prepared for {$data['period_month']}." : 'No unpaid rent found for that month.');
    }

    public function pay(Request $request, Payout $payout)
    {
        abort_unless($payout->status === 'pending', 403);

        $data = $request->validate(['bank_reference' => ['required', 'string', 'max:60']]);

        $payout->forceFill(['status' => 'paid', 'paid_at' => now(), 'bank_reference' => $data['bank_reference']])->save();

        Audit::log('payout.pay', $payout, [], ['net_amount' => $payout->net_amount]);
        Notice::to(
            $payout->owner,
            'Your payout for '.$payout->period_month->format('F Y').' was sent',
            'MVR '.number_format($payout->net_amount, 2)." was transferred. Reference: {$data['bank_reference']}.",
            route('owner.earnings.index')
        );

        return back()->with('status', 'Payout marked as paid.');
    }
}
