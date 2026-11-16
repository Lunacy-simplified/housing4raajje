{{-- Create a lease from an approved application. Receives $application and $methods [id => name]. --}}
@extends('layouts.app')

@section('title', 'Create lease')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('staff.leases.index') }}">Leases</a></span>
            <h1 class="t">Create lease</h1>
            <p class="sub">
                {{ $application->client->name }} · {{ $application->property->house_name }}, {{ $application->property->district->name }}
                · {{ $application->leaseTerm->label }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('staff.leases.store', $application) }}" class="card limit">
        @csrf

        <div class="fgrid">
            <x-field name="start_date" label="Rent start date" type="date" :value="max($application->requested_start_date, today())->format('Y-m-d')" required
                hint="The end date is worked out from the {{ $application->leaseTerm->label }} term." />
            <x-field name="monthly_rent" label="Monthly rent (MVR)" type="number" min="1" step="0.01" :value="$application->property->monthly_rent" required />
            <x-field name="advance_amount" label="Advance payment or deposit (MVR)" type="number" min="0" step="0.01" value="0" required />
            <x-select name="payment_method_id" label="Payment method" :options="$methods" placeholder="Choose" required />
        </div>

        <p class="hint">Creating the lease records you as the staff member responsible and asks the client and the owner to sign.</p>

        <div class="acts form-end">
            <button type="submit" class="btn btn-p">Create lease</button>
            <a href="{{ route('staff.leases.index') }}" class="btn btn-n">Cancel</a>
        </div>
    </form>
@endsection
