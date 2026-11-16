{{--
    Apartment Registration Form, used to create and to edit. Receives $property (new or existing) and $districts.
    The branch is never chosen by hand: it comes from the district.
--}}
@extends('layouts.app')

@section('title', $property->exists ? 'Edit property' : 'Register a property')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('owner.properties.index') }}">My properties</a></span>
            <h1 class="t">{{ $property->exists ? 'Edit '.$property->reference : 'Register a property' }}</h1>
            <p class="sub">It's saved as a draft first. The branch for its district reviews it after you submit.</p>
        </div>
    </div>

    <form method="POST" class="card limit"
        action="{{ $property->exists ? route('owner.properties.update', $property) : route('owner.properties.store') }}">
        @csrf
        @if ($property->exists)
            @method('PUT')
        @endif

        <div class="fgrid">
            <x-select name="property_type" label="Property type" :options="['apartment' => 'Apartment', 'flat' => 'Flat']" :value="$property->property_type" required />
            <x-field name="rooms" label="Rooms" type="number" min="1" max="20" :value="$property->rooms" required />
            <x-field name="house_name" label="House or plot name" :value="$property->house_name" maxlength="80" required />
            <x-field name="road" label="Road (magu or goalhi)" :value="$property->road" maxlength="80" required />
            <x-select name="district_id" label="District" :options="$districts" :value="$property->district_id" placeholder="Choose a district" required />
            <x-field name="unit_no" label="Unit number (optional)" :value="$property->unit_no" maxlength="10" />
            <x-field name="floor" label="Floor (optional)" type="number" min="0" max="100" :value="$property->floor" />
            <x-field name="monthly_rent" label="Monthly rent (MVR)" type="number" min="1" step="0.01" :value="$property->monthly_rent" required />
        </div>

        <div class="acts form-end">
            <button type="submit" class="btn btn-p">{{ $property->exists ? 'Save changes' : 'Save draft' }}</button>
            <a href="{{ $property->exists ? route('owner.properties.show', $property) : route('owner.properties.index') }}" class="btn btn-n">Cancel</a>
        </div>
    </form>
@endsection
