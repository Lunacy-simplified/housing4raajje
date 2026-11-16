<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Owner earnings: one payout per month, rent collected minus the management fee. */
class EarningsController extends Controller
{
    public function index(Request $request)
    {
        return view('owner.earnings', [
            'payouts' => $request->user()->payouts()->orderByDesc('period_month')->get(),
        ]);
    }

    /** The same table as a CSV statement the owner can keep. */
    public function download(Request $request)
    {
        $payouts = $request->user()->payouts()->orderBy('period_month')->get();

        return response()->streamDownload(function () use ($payouts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Month', 'Rent collected (MVR)', 'Fee %', 'Fee (MVR)', 'Paid to you (MVR)', 'Status', 'Paid on', 'Bank reference']);
            foreach ($payouts as $payout) {
                fputcsv($out, [
                    $payout->period_month->format('Y-m'), $payout->gross_amount, $payout->fee_percent, $payout->fee_amount,
                    $payout->net_amount, $payout->status, $payout->paid_at?->format('Y-m-d'), $payout->bank_reference,
                ]);
            }
            fclose($out);
        }, 'h4r-earnings-statement.csv', ['Content-Type' => 'text/csv']);
    }
}
