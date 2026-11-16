{{-- Verification queue for the staff member's branch. Receives $properties and $status (the current filter). --}}
@extends('layouts.app')

@section('title', 'Properties')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Work</span>
            <h1 class="t">Properties</h1>
            <p class="sub">Listings in your branch. Check each new one before it goes live.</p>
        </div>
    </div>

    <nav class="seg" aria-label="Review stage">
        @foreach (['pending' => 'To check', 'changes_requested' => 'Sent back', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
            <a href="{{ route('staff.properties.index', ['status' => $value]) }}" @class(['on' => $status === $value])>{{ $label }}</a>
        @endforeach
    </nav>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Address</th>
                        <th scope="col">Owner</th>
                        <th scope="col">Rooms</th>
                        <th scope="col">Monthly rent</th>
                        <th scope="col">Submitted</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($properties as $property)
                        <tr>
                            <td class="mono">{{ $property->reference }}</td>
                            <td>{{ $property->address() }}</td>
                            <td>{{ $property->owner->name }}</td>
                            <td>{{ $property->rooms }}</td>
                            <td>MVR {{ number_format($property->monthly_rent, 2) }}</td>
                            <td class="muted">{{ $property->submitted_at?->diffForHumans() ?? '—' }}</td>
                            <td class="r"><a href="{{ route('staff.properties.show', $property) }}" class="act">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">Nothing here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $properties->links('pagination::bootstrap-4') }}
@endsection
