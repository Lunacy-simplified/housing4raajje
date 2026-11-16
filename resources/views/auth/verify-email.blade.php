{{--
    Email verification notice (GET /email/verify, route "verification.notice"). Handoff doc section 7.
    Shown to logged-in users who try something that needs a verified email.
    Must keep: POST to route('verification.send') with @csrf, the logout form, session('status'), and any errors.
--}}
@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <div>
        <h1 class="t">Please verify your email to continue</h1>
        <p class="sub">We've sent a verification link to your email address. Open the email and click the link to verify your account.</p>
    </div>

    @include('partials.status')

    @if ($errors->any())
        <div class="al al-err" role="alert">
            <span>
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-p">Resend verification email</button>
    </form>

    @include('partials.logout')
@endsection
