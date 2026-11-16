{{-- Owner payouts. Receives $payouts (pending first) and $feePercent (the current management fee). --}}
@extends('layouts.app')

@section('title', 'Owner payouts')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Finance</span>
            <h1 class="t">Owner payouts</h1>
            <p class="sub">Rent collected for a month, minus the {{ $feePercent + 0 }}% management fee.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.payouts.generate') }}" class="flt">
        @csrf
        <x-field name="period_month" label="Prepare payouts for" type="month" :value="now()->format('Y-m')" required />
        <div class="acts">
            <button type="submit" class="btn btn-p">Prepare payouts</button>
        </div>
    </form>

    @error('bank_reference')
        <div class="al al-err" role="alert"><span>{{ $message }}</span></div>
    @enderror

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Owner</th>
                        <th scope="col">Month</th>
                        <th scope="col" class="r">Rent collected</th>
                        <th scope="col" class="r">Fee</th>
                        <th scope="col" class="r">To pay</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        @php($bank = $payout->owner->ownerProfile)
                        <tr>
                            <td>
                                <b>{{ $payout->owner->name }}</b>
                                <small>{{ $bank?->bank_name ? "{$bank->bank_name} · {$bank->bank_account_name} · {$bank->bank_account_no}" : 'No bank details yet' }}</small>
                            </td>
                            <td>{{ $payout->period_month->format('F Y') }}</td>
                            <td class="r">{{ number_format($payout->gross_amount, 2) }}</td>
                            <td class="r">{{ number_format($payout->fee_amount, 2) }}</td>
                            <td class="r"><b>{{ number_format($payout->net_amount, 2) }}</b></td>
                            <td>
                                @if ($payout->status === 'pending')
                                    <form method="POST" action="{{ route('admin.payouts.pay', $payout) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input name="bank_reference" class="in" placeholder="Transfer reference" aria-label="Transfer reference" required maxlength="60">
                                        <button type="submit" class="btn btn-s">Mark as paid</button>
                                    </form>
                                @else
                                    <x-pill status="paid" />
                                    <small>{{ $payout->paid_at->format('j M Y') }} · <span class="mono">{{ $payout->bank_reference }}</span></small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No payouts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $payouts->links('pagination::bootstrap-4') }}
@endsection
