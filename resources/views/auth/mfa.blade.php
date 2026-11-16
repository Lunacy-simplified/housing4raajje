{{--
    MFA code page for admins and branch managers (GET /mfa, route "mfa.show"). Handoff doc section 10.
    Must keep: POST to route('mfa.verify') with field "code" (+ optional "trust_device" value 1),
    POST to route('mfa.resend'), @csrf on both, errors on "code", session('status').
--}}
@extends('layouts.auth')

@section('title', 'Enter your sign-in code')

@section('content')
    <div>
        <h1 class="t">Enter your sign-in code</h1>
        <p class="sub">We've emailed you a 6-digit code. It expires in 10 minutes and works once.</p>
    </div>

    @include('partials.status')

    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf

        <div class="fld">
            <label for="code">Sign-in code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus
                placeholder="000000"
                class="in mono code @error('code') bad @enderror" @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
            @error('code')
                <span id="code-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <label class="chk switch">Don't ask for a code on this device for 30 days <input type="checkbox" name="trust_device" value="1"></label>

        <button type="submit" class="btn btn-p">Verify</button>
    </form>

    <form method="POST" action="{{ route('mfa.resend') }}" class="sep">
        @csrf
        <span class="hint">Didn't get it? After 5 wrong tries you need a new code.</span>
        <button type="submit" class="btn btn-n">Send a new code</button>
    </form>

    <p class="foot"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
