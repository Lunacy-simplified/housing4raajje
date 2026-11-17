{{-- Rental applications for the branch. Receives $applications and $status (the current filter). --}}
@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    @php($decides = auth()->user()->hasRole('property_specialist', 'branch_manager'))

    <div class="ph">
        <div>
            <h1 class="t">Applications</h1>
        </div>
    </div>

    <nav class="seg" aria-label="Status">
        @foreach (['submitted' => 'To review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $value => $label)
            <a href="{{ route('staff.applications.index', ['status' => $value]) }}" @class(['on' => $status === $value])>{{ $label }}</a>
        @endforeach
    </nav>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Property</th>
                        <th scope="col">Client</th>
                        <th scope="col">Lease wanted</th>
                        <th scope="col">Household</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td class="mono">{{ $application->reference }}</td>
                            <td>
                                <b>{{ $application->property->house_name }}</b>
                                <small>MVR {{ number_format($application->property->monthly_rent, 2) }} · {{ $application->property->district->name }}</small>
                            </td>
                            <td>
                                <b>{{ $application->client->name }}</b>
                                <small>{{ $application->client->phone }} · budget {{ $application->client->clientProfile->max_monthly_rent ? 'MVR '.number_format($application->client->clientProfile->max_monthly_rent) : 'not given' }}</small>
                            </td>
                            <td>{{ $application->leaseTerm->label }}<small>from {{ $application->requested_start_date->format('j M Y') }}</small></td>
                            <td>{{ $application->occupants }} {{ Str::plural('person', $application->occupants) }}<small>{{ $application->employer ?? 'No employer given' }} · {{ $application->income_bracket ?? 'income not given' }}</small></td>
                            <td>
                                @if ($application->status === 'submitted' && $decides)
                                    <form method="POST" action="{{ route('staff.applications.decide', $application) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input name="decision_note" class="in" placeholder="Reason (needed to reject)" aria-label="Reason" maxlength="1000">
                                        <button type="submit" name="decision" value="approved" class="btn btn-s">Approve</button>
                                        <button type="submit" name="decision" value="rejected" class="btn btn-d">Reject</button>
                                    </form>
                                @elseif ($application->lease)
                                    <a href="{{ route('staff.leases.show', $application->lease) }}" class="act">Open lease</a>
                                @elseif ($application->status === 'approved' && $decides)
                                    <a href="{{ route('staff.leases.create', $application) }}" class="act">Create lease</a>
                                @else
                                    <span class="muted">{{ $application->decision_note ?? '—' }}</span>
                                @endif
                                @include('partials.escalate', ['type' => 'application', 'id' => $application->id])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">Nothing here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $applications->links('pagination::bootstrap-4') }}
@endsection
