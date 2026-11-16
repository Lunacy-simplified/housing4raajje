{{--
    Register page for clients and owners (GET /register/client and /register/owner). Handoff doc section 2.
    Receives $type: 'client' or 'owner'.
    Must keep: POST to route('register', $type), @csrf, field names name / email / phone /
    national_id_or_passport (owners only) / password / password_confirmation / terms (value 1).
--}}
@extends('layouts.auth')

@section('title', 'Register as ' . ucfirst($type))

@section('content')
    <h1 class="t">Register as {{ ucfirst($type) }}</h1>

    <nav class="seg" aria-label="Account type">
        <a href="{{ route('register', 'client') }}" @class(['on' => $type === 'client']) @if ($type === 'client') aria-current="page" @endif>Client</a>
        <a href="{{ route('register', 'owner') }}" @class(['on' => $type === 'owner']) @if ($type === 'owner') aria-current="page" @endif>Property owner</a>
    </nav>

    <form method="POST" action="{{ route('register', $type) }}">
        @csrf

        <div class="fld">
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="255"
                class="in @error('name') bad @enderror" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')
                <span id="name-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="fld">
            <label for="phone">Mobile number</label>
            <div class="pre">
                <span>+960</span>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required inputmode="numeric" autocomplete="tel-national"
                    class="in @error('phone') bad @enderror" aria-describedby="phone-hint @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
            </div>
            <span id="phone-hint" class="hint">7 digits, starting with 7 or 9 (for example 7771234)</span>
            @error('phone')
                <span id="phone-error" class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        {{-- Owners only: the controller requires it for owners and ignores it for clients. --}}
        @if ($type === 'owner')
            <div class="fld">
                <label for="national_id_or_passport">National ID or passport number</label>
                <input id="national_id_or_passport" type="text" name="national_id_or_passport" value="{{ old('national_id_or_passport') }}" required maxlength="30"
                    class="in mono @error('national_id_or_passport') bad @enderror" @error('national_id_or_passport') aria-invalid="true" aria-describedby="nid-error" @enderror>
                @error('national_id_or_passport')
                    <span id="nid-error" class="err" role="alert">{{ $message }}</span>
                @enderror
            </div>
        @endif

        <div class="fld">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
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

        <div class="fld">
            <label class="chk">
                <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                I accept the terms and conditions
            </label>
            @error('terms')
                <span class="err" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-p">Create account</button>
    </form>

    <p class="foot">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
