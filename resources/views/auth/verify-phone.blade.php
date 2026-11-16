{{--
    Phone verification page (GET /phone/verify, route "phone.verification.show"). Handoff doc section 11.
    Uses auth()->user()->phone and auth()->user()->hasVerifiedPhone().
    Must keep: POST to route('phone.verification.send') (no fields), POST to route('phone.verification.verify')
    with field "code", @csrf on both, errors on "code", session('status').
--}}
@extends('layouts.auth')

@section('title', 'Verify your phone')

@section('content')
    @php($user = auth()->user())

    <div>
        <h1 class="t">Verify your phone</h1>
        <p class="sub">Optional. For owners, your number only shows on your listings once it's verified.</p>
    </div>

    <div class="box row">
        <span><span class="muted">Phone number</span><br><b class="mono">{{ $user->phone ?? 'Not set' }}</b></span>
        @if ($user->hasVerifiedPhone())
            <span class="b b-ok">Phone verified ✓</span>
        @else
            <span class="b b-warn">Not verified</span>
        @endif
    </div>

    @include('partials.status')

    @unless ($user->hasVerifiedPhone())
        {{-- Sending an SMS takes a moment: the button is disabled after the first click so it can't be sent twice. --}}
        <form method="POST" action="{{ route('phone.verification.send') }}" onsubmit="this.querySelector('button').disabled = true">
            @csrf
            <span class="hint">We'll text a 6-digit code to this number. It expires in 10 minutes.</span>
            <button type="submit" class="btn btn-n">Send code</button>
        </form>

        <form method="POST" action="{{ route('phone.verification.verify') }}" class="sep">
            @csrf

            <div class="fld">
                <label for="code">Code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required
                    placeholder="000000"
                    class="in mono code @error('code') bad @enderror" aria-describedby="code-hint @error('code') code-error @enderror" @error('code') aria-invalid="true" @enderror>
                <span id="code-hint" class="hint">After 5 wrong tries, ask for a new code. Up to 5 codes a day.</span>
                @error('code')
                    <span id="code-error" class="err" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-p">Verify</button>
        </form>
    @endunless

    <p class="foot"><a href="{{ route($user->dashboardRoute()) }}">Back to dashboard</a></p>
@endsection
