{{--
    Reset password page, opened from the email link (GET /reset-password/{token}, route "password.reset"). Handoff doc section 8.
    Receives $token and $email.
    Must keep: POST to route('password.update'), @csrf, hidden "token", fields email / password / password_confirmation,
    errors on "email" (bad or expired link) and "password".
--}}
@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div>
        <h1 class="t">Choose a new password</h1>
        <p class="sub">You'll be signed out on your other devices.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="fld">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                class="in @error('password') bad @enderror" aria-describedby="password-hint @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            <span id="password-hint" class="hint">At least 8 characters, with letters and numbers</span>
            @error('password')
                <span id="password-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="in">
        </div>

        <button type="submit" class="btn btn-p">Reset password</button>
    </form>

    <p class="foot" style="margin: 0">Link expired? <a href="{{ route('password.request') }}">Request a new one</a></p>
@endsection
