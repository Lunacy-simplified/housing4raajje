{{-- A client's rental applications. Receives $applications. --}}
@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Applications</h1>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Property</th>
                        <th scope="col">Lease length</th>
                        <th scope="col">Start date</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td class="mono">{{ $application->reference }}</td>
                            <td>
                                <b>{{ $application->property->house_name }}</b>
                                <small>{{ $application->property->district->name }}</small>
                            </td>
                            <td>{{ $application->leaseTerm->label }}</td>
                            <td>{{ $application->requested_start_date->format('j M Y') }}</td>
                            <td>
                                <x-pill :status="$application->status" />
                                @if ($application->decision_note)
                                    <small>{{ $application->decision_note }}</small>
                                @endif
                            </td>
                            <td class="r">
                                @if ($application->status === 'submitted')
                                    <form method="POST" action="{{ route('client.applications.withdraw', $application) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="act">Withdraw</button>
                                    </form>
                                @elseif ($application->lease)
                                    <a href="{{ route('leases.show', $application->lease) }}" class="act">Open lease</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No applications yet. Apply from a property's page.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
