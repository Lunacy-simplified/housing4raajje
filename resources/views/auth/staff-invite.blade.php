{{--
    Accept staff invite page, opened from the invite email (GET /staff-invite/{token}, route "staff-invite.show"). Handoff doc section 9.
    Receives $token and $email.
    Must keep: POST to route('staff-invite.accept'), @csrf, hidden "token", fields email / password / password_confirmation,
    errors on "email" (bad or expired link) and "password".
--}}
@extends('layouts.auth')

@section('title', 'Set up your account')

@section('content')
    <div>
        <h1 class="t">Set up your account</h1>
        <p class="sub">Welcome to the team. Choose a password to finish setting up your staff account.</p>
    </div>

    <form method="POST" action="{{ route('staff-invite.accept') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="fld">
            <label for="email">Work email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                class="in @error('password') bad @enderror" aria-describedby="password-hint @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            <span id="password-hint" class="hint">At least 8 characters, with letters and numbers</span>
            @error('password')
                <span id="password-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="in">
        </div>

        <button type="submit" class="btn btn-p">Set password</button>
    </form>

    <p class="hint" style="margin: 0">This link works once and is valid for 24 hours. If it has expired, ask your administrator to resend it.</p>
@endsection
