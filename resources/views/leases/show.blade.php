{{-- One lease, for its client or owner. Receives $lease and $party ('client' or 'owner'). Signing asks for the password again. --}}
@extends('layouts.app')

@section('title', $lease->reference)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('leases.index') }}">Leases</a></span>
            <h1 class="t">{{ $lease->reference }}</h1>
            <p class="sub"><x-pill :status="$lease->status" /></p>
        </div>
    </div>

    <div class="split">
        <div class="stack wide">
            @include('leases.contract')
        </div>

        @if ($lease->status === 'pending_signature' && ! $lease->signatures->contains('party', $party))
            <form method="POST" action="{{ route('leases.sign', $lease) }}" class="card narrow">
                @csrf
                <h2 class="h">Sign this lease</h2>
                <p class="hint">By signing you agree to the terms shown. Enter your password to confirm it's you. The lease starts once both the client and the owner have signed.</p>
                <x-field name="password" label="Your password" type="password" autocomplete="current-password" required />
                <button type="submit" class="btn btn-p">Sign lease</button>
            </form>
        @endif
    </div>
@endsection
