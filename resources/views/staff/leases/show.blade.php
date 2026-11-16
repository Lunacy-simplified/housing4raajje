{{-- One lease, for branch staff: the contract, payments, and the forms to record a payment or end it. Receives $lease and $methods. --}}
@extends('layouts.app')

@section('title', $lease->reference)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('staff.leases.index') }}">Leases</a></span>
            <h1 class="t">{{ $lease->reference }}</h1>
            <p class="sub"><x-pill :status="$lease->status" /></p>
        </div>
    </div>

    <div class="split">
        <div class="stack wide">
            @include('leases.contract')
        </div>

        <div class="stack narrow">
            @if ($lease->status === 'active')
                <form method="POST" action="{{ route('staff.leases.payments.store', $lease) }}" class="card">
                    @csrf
                    <h2 class="h">Record a payment</h2>
                    <x-select name="kind" label="Payment for" :options="['rent' => 'Rent', 'advance' => 'Advance or deposit']" value="rent" required />
                    <x-field name="period_month" label="Rent month" type="month" :value="now()->format('Y-m')" hint="Leave as it is for an advance." />
                    <x-field name="amount" label="Amount (MVR)" type="number" min="0.01" step="0.01" :value="$lease->monthly_rent" required />
                    <x-select name="payment_method_id" label="Paid by" :options="$methods" :value="$lease->payment_method_id" required />
                    <x-field name="bank_reference" label="Slip or transaction number" maxlength="60" required />
                    <x-field name="received_on" label="Received on" type="date" :value="now()->format('Y-m-d')" :max="now()->format('Y-m-d')" required />
                    <button type="submit" class="btn btn-p">Record payment</button>
                </form>
            @endif

            @if (in_array($lease->status, ['pending_signature', 'active'], true))
                <section class="card">
                    <h2 class="h">Problems</h2>
                    @if (auth()->user()->hasRole('property_specialist', 'branch_manager'))
                        <form method="POST" action="{{ route('staff.leases.terminate', $lease) }}" class="stack">
                            @csrf
                            @method('PATCH')
                            <x-textarea name="termination_reason" label="{{ $lease->status === 'active' ? 'End this lease early' : 'Cancel before signing' }}" maxlength="1000" required />
                            <button type="submit" class="btn btn-d">{{ $lease->status === 'active' ? 'End lease' : 'Cancel lease' }}</button>
                        </form>
                    @endif
                    @include('partials.escalate', ['type' => 'lease', 'id' => $lease->id])
                </section>
            @endif
        </div>
    </div>
@endsection
