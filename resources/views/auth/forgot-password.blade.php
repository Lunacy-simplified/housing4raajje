<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>
</head>

<body>
    <h1>Forgot Password</h1>

    <p>Enter your email address and, if it is registered, we'll send you a password reset link.</p>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                autocomplete="email">
            @error('email')
                <p role="alert" style="color:red">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Send Password Reset Link</button>
    </form>

    <p><a href="{{ route('login') }}">Back to sign in</a></p>
</body>

</html>
