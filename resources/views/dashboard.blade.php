{{--
    Dashboard for every portal. Receives $portal (name) and $stats: a list of [label, number, link].
    Each tile is a real work queue for the signed-in role.
--}}
@extends('layouts.app')

@section('title', $portal)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">{{ $portal }}</span>
            <h1 class="t">Welcome, {{ auth()->user()->name }}</h1>
        </div>
    </div>

    @include('partials.verify')

    <div class="stats">
        @foreach ($stats as [$label, $value, $url])
            <a class="stat" href="{{ $url }}">
                <span class="hint">{{ $label }}</span>
                <b>{{ $value }}</b>
            </a>
        @endforeach
    </div>
@endsection
