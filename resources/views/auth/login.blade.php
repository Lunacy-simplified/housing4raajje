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
            <div class="row">
                <label for="password">Password</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="in @error('password') bad @enderror" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <span id="password-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <label class="chk switch">Remember me <input type="checkbox" name="remember"></label>

        <button type="submit" class="btn btn-p">Sign in</button>
    </form>

    <p class="foot">No account yet? Register as a <a href="{{ route('register', 'client') }}">client</a> or a <a href="{{ route('register', 'owner') }}">property owner</a>.<br>Staff accounts are created by an administrator.</p>
@endsection
