<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register as {{ ucfirst($type) }}</title>
</head>

<body>
    <h1>Register as {{ ucfirst($type) }}</h1>

    <form method="POST" action="{{ route('register', $type) }}">
        @csrf

        {{-- Full name --}}
        <div>
            <label>
                Full name
                <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            </label>
            @error('name')
            <p style="color:red">{{ $message }}</p>
        @enderror
        </div>

        {{-- Email --}}
        <div>
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            @error('email')
            <p style="color:red">{{ $message }}</p>
        @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label>
                Phone
                <input type="tel" name="phone" value="{{ old('phone') }}" required>
            </label>
            @error('phone')
            <p style="color:red">{{ $message }}</p>
        @enderror
        </div>

        {{-- Owner-only field --}}
        @if ($type === 'owner')
            <div>
                <label>
                    National ID or passport
                    <input type="text" name="national_id_or_passport" value="{{ old('national_id_or_passport') }}"
                        required>
                </label>
                @error('national_id_or_passport')
                <p style="color:red">{{ $message }}</p>
            @enderror
            </div>
        @endif

        {{-- Password --}}
        <div>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            @error('password')
            <p style="color:red">{{ $message }}</p>
        @enderror
        </div>

        {{-- Confirm password --}}
        <div>
            <label>
                Confirm password
                <input type="password" name="password_confirmation" required>
            </label>
        </div>

        {{-- Terms and conditions --}}
        <div>
            <label>
                <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                I accept the terms and conditions
            </label>
            @error('terms')
            <p style="color:red">{{ $message }}</p>
        @enderror
        </div>

        <button type="submit">Register</button>
    </form>
</body>

</html>
