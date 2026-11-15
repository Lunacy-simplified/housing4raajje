{{--
    Sign in page (GET /login, route "login"). See docs/frontend-handoff.md section 1.
    Must keep: POST to route('login'), @csrf, field names email / password / remember,
    login errors on "email", and session('status') (e.g. after a password reset).
--}}
@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="t">Sign in</h1>

    @include('partials.status')

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="fld">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <div style="display: flex; justify-content: space-between; gap: 8px">
                <label for="password">Password</label>
                <a href="{{ route('password.request') }}" style="font-size: 13px">Forgot password?</a>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="in @error('password') bad @enderror" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <span id="password-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <label class="chk"><input type="checkbox" name="remember"> Remember me</label>

        <button type="submit" class="btn btn-p">Sign in</button>
    </form>

    <div style="border-top: 1px solid var(--line); padding-top: 16px; display: flex; flex-direction: column; gap: 8px">
        <span class="hint">No account yet?</span>
        <a href="{{ route('register', 'client') }}" class="btn btn-n">Register as a client</a>
        <a href="{{ route('register', 'owner') }}" class="btn btn-n">Register as a property owner</a>
    </div>

    <p class="foot" style="margin: 0">Staff accounts are created by an administrator.</p>
@endsection
