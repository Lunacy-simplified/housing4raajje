{{-- An owner's properties. Receives $properties. --}}
@extends('layouts.app')

@section('title', 'My properties')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Owner</span>
            <h1 class="t">My properties</h1>
        </div>
        <div class="acts">
            <a href="{{ route('owner.properties.create') }}" class="btn btn-p">Register a property</a>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Address</th>
                        <th scope="col">Rooms</th>
                        <th scope="col">Monthly rent</th>
                        <th scope="col">Review</th>
                        <th scope="col">Availability</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($properties as $property)
                        <tr>
                            <td class="mono">{{ $property->reference }}</td>
                            <td>{{ $property->address() }}</td>
                            <td>{{ $property->rooms }}</td>
                            <td>MVR {{ number_format($property->monthly_rent, 2) }}</td>
                            <td><x-pill :status="$property->verification_status" /></td>
                            <td>
                                @if ($property->availability_status)
                                    <x-pill :status="$property->availability_status" />
                                @else
                                    —
                                @endif
                            </td>
                            <td class="r"><a href="{{ route('owner.properties.show', $property) }}" class="act">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">You haven't registered a property yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
