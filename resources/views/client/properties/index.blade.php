{{-- Property search for clients. Receives $properties (12 per page) and $branches [id => name]. --}}
@extends('layouts.app')

@section('title', 'Search')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Find a home</h1>
            <p class="sub">{{ $properties->total() }} {{ Str::plural('property', $properties->total()) }} available</p>
        </div>
    </div>

    <form method="GET" action="{{ route('client.properties.index') }}" class="flt">
        <x-select name="branch_id" label="Location" :options="$branches" :value="request('branch_id')" placeholder="Anywhere" />
        <x-field name="rooms" label="Rooms" type="number" min="1" :value="request('rooms')" />
        <x-field name="max_rent" label="Maximum rent (MVR)" type="number" min="0" :value="request('max_rent')" />
        <div class="acts">
            <button type="submit" class="btn btn-s">Search</button>
            <a href="{{ route('client.properties.index') }}" class="act">Clear</a>
        </div>
    </form>

    @if ($properties->isEmpty())
        <p class="empty">No properties match. Try a higher budget or another location.</p>
    @else
        <div class="grid">
            @foreach ($properties as $property)
                <a class="card" href="{{ route('client.properties.show', $property) }}">
                    @if ($photo = $property->photos->first())
                        <img class="photo" src="{{ route('photos.show', $photo) }}" alt="">
                    @else
                        <span class="photo"></span>
                    @endif
                    <div>
                        <span class="price">MVR {{ number_format($property->monthly_rent) }} <span class="hint">a month</span></span>
                        <b>{{ $property->house_name }}</b>
                        <span>{{ $property->rooms }} {{ Str::plural('room', $property->rooms) }} · {{ ucfirst($property->property_type) }}</span>
                        <span class="hint">{{ $property->district->name }}, {{ $property->branch->name }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($properties->hasPages())
        <div class="pg">
            <span>Showing {{ $properties->firstItem() }} to {{ $properties->lastItem() }} of {{ $properties->total() }}</span>
            {{ $properties->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endsection
