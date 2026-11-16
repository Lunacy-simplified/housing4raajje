{{-- Leases for the branch. Receives $ready (approved applications without a lease yet) and $leases. --}}
@extends('layouts.app')

@section('title', 'Leases')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Work</span>
            <h1 class="t">Leases</h1>
        </div>
    </div>

    @if ($ready->isNotEmpty())
        <section class="card flush">
            <div class="sx">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">Ready for a lease</th>
                            <th scope="col">Client</th>
                            <th scope="col">Lease wanted</th>
                            <th scope="col"><span class="sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ready as $application)
                            <tr>
                                <td><b>{{ $application->property->house_name }}</b><small class="mono">{{ $application->reference }}</small></td>
                                <td>{{ $application->client->name }}</td>
                                <td>{{ $application->leaseTerm->label }} from {{ $application->requested_start_date->format('j M Y') }}</td>
                                <td class="r">
                                    @if (auth()->user()->hasRole('property_specialist', 'branch_manager'))
                                        <a href="{{ route('staff.leases.create', $application) }}" class="act">Create lease</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Lease</th>
                        <th scope="col">Property</th>
                        <th scope="col">Client</th>
                        <th scope="col">Owner</th>
                        <th scope="col">Runs</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leases as $lease)
                        <tr>
                            <td class="mono">{{ $lease->reference }}</td>
                            <td>{{ $lease->property->house_name }}</td>
                            <td>{{ $lease->client->name }}</td>
                            <td>{{ $lease->owner->name }}</td>
                            <td>{{ $lease->start_date->format('j M Y') }} to {{ $lease->end_date->format('j M Y') }}</td>
                            <td><x-pill :status="$lease->status" /></td>
                            <td class="r"><a href="{{ route('staff.leases.show', $lease) }}" class="act">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">No leases yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $leases->links('pagination::bootstrap-4') }}
@endsection
