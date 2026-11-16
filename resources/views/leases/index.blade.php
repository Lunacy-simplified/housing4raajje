{{-- An owner's or client's leases. Receives $leases. --}}
@extends('layouts.app')

@section('title', 'Leases')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">{{ auth()->user()->role->name }}</span>
            <h1 class="t">Leases</h1>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Lease</th>
                        <th scope="col">Property</th>
                        <th scope="col">Runs</th>
                        <th scope="col" class="r">Monthly rent (MVR)</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leases as $lease)
                        <tr>
                            <td class="mono">{{ $lease->reference }}</td>
                            <td><b>{{ $lease->property->house_name }}</b><small>{{ $lease->property->district->name }}</small></td>
                            <td>{{ $lease->start_date->format('j M Y') }} to {{ $lease->end_date->format('j M Y') }}</td>
                            <td class="r">{{ number_format($lease->monthly_rent, 2) }}</td>
                            <td><x-pill :status="$lease->status" /></td>
                            <td class="r">
                                <a href="{{ route('leases.show', $lease) }}" class="act">
                                    {{ $lease->status === 'pending_signature' && ! $lease->signatures->contains('signed_by_user_id', auth()->id()) ? 'Read and sign' : 'Open' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No leases yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
