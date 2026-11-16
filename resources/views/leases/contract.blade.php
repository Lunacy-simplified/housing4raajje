{{--
    The lease itself, shared by the staff page and the client/owner page.
    Needs $lease with property.district, client, owner, executedBy.staffProfile, paymentMethod, signatures and payments loaded.
--}}
<section class="card">
    <h2 class="h">Lease agreement</h2>
    <dl class="kv">
        <dt>Property</dt>
        <dd>{{ $lease->property->address() }} <span class="mono muted">{{ $lease->property->reference }}</span></dd>
        <dt>Owner</dt>
        <dd>{{ $lease->owner->name }}</dd>
        <dt>Client</dt>
        <dd>{{ $lease->client->name }}</dd>
        <dt>Term</dt>
        <dd>{{ $lease->term_months }} months, {{ $lease->start_date->format('j M Y') }} to {{ $lease->end_date->format('j M Y') }}</dd>
        <dt>Monthly rent</dt>
        <dd>MVR {{ number_format($lease->monthly_rent, 2) }}</dd>
        <dt>Advance or deposit</dt>
        <dd>MVR {{ number_format($lease->advance_amount, 2) }}</dd>
        <dt>Payment method</dt>
        <dd>{{ $lease->paymentMethod->name }}</dd>
        <dt>Executed by</dt>
        <dd>{{ $lease->executedBy->name }} <span class="mono muted">{{ $lease->executedBy->staffProfile?->staff_code }}</span> on {{ $lease->executed_at->format('j M Y') }}</dd>
        @foreach (['client' => 'Client signature', 'owner' => 'Owner signature'] as $party => $label)
            @php($signature = $lease->signatures->firstWhere('party', $party))
            <dt>{{ $label }}</dt>
            <dd>{{ $signature ? 'Signed '.$signature->signed_at->format('j M Y, g:i a') : 'Not signed yet' }}</dd>
        @endforeach
        @if ($lease->termination_reason)
            <dt>Ended early</dt>
            <dd>{{ $lease->terminated_at->format('j M Y') }}: {{ $lease->termination_reason }}</dd>
        @endif
    </dl>
</section>

<section class="card flush">
    <div class="sx">
        <table class="tbl">
            <thead>
                <tr>
                    <th scope="col">Receipt</th>
                    <th scope="col">For</th>
                    <th scope="col" class="r">Amount (MVR)</th>
                    <th scope="col">Method</th>
                    <th scope="col">Bank reference</th>
                    <th scope="col">Received</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lease->payments->sortByDesc('received_on') as $payment)
                    <tr>
                        <td class="mono">{{ $payment->reference }}</td>
                        <td>{{ $payment->kind === 'rent' ? 'Rent, '.$payment->period_month->format('F Y') : 'Advance' }}</td>
                        <td class="r">{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->paymentMethod->name }}</td>
                        <td class="mono">{{ $payment->bank_reference }}</td>
                        <td>{{ $payment->received_on->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No payments recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
