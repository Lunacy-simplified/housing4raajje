{{--
    Forgot password page (GET /forgot-password, route "password.request"). Handoff doc section 8.
    Must keep: POST to route('password.email'), @csrf, field "email", errors on "email", session('status').
    The status message is always the same whether or not the email exists, so the page never reveals who has an account.
--}}
@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div>
        <h1 class="t">Forgot your password?</h1>
        <p class="sub">Enter your email. If it's registered, we'll send you a link to choose a new password.</p>
    </div>

    @include('partials.status')

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="fld">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-p">Send reset link</button>
    </form>

    <p class="hint" style="margin: 0">The link works once and expires after 15 minutes.</p>
    <p class="foot" style="margin: 0"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
