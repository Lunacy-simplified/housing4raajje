{{-- Earnings and payouts for an owner. Receives $payouts (newest month first). --}}
@extends('layouts.app')

@section('title', 'Earnings')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Earnings</h1>
            <p class="sub">Rent collected each month, minus the management fee.</p>
        </div>
        <div class="acts">
            <a href="{{ route('owner.earnings.download') }}" class="btn btn-n">Download statement (CSV)</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><span class="hint">Paid to you so far (MVR)</span><b>{{ number_format($payouts->where('status', 'paid')->sum('net_amount'), 2) }}</b></div>
        <div class="stat"><span class="hint">Waiting to be paid (MVR)</span><b>{{ number_format($payouts->where('status', 'pending')->sum('net_amount'), 2) }}</b></div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Month</th>
                        <th scope="col" class="r">Rent collected</th>
                        <th scope="col" class="r">Fee</th>
                        <th scope="col" class="r">Paid to you</th>
                        <th scope="col">Status</th>
                        <th scope="col">Bank reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr>
                            <td>{{ $payout->period_month->format('F Y') }}</td>
                            <td class="r">{{ number_format($payout->gross_amount, 2) }}</td>
                            <td class="r">{{ number_format($payout->fee_amount, 2) }} <span class="muted">({{ $payout->fee_percent + 0 }}%)</span></td>
                            <td class="r"><b>{{ number_format($payout->net_amount, 2) }}</b></td>
                            <td><x-pill :status="$payout->status" /></td>
                            <td class="mono">{{ $payout->bank_reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No payouts yet. They appear once rent has been collected.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
