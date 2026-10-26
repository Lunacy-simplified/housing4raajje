<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
</head>

<body>
    <h1>Reset Password</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                autocomplete="email">
            @error('email')
                <p role="alert" style="color:red">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')
                <p role="alert" style="color:red">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                autocomplete="new-password">
        </div>

        <button type="submit">Reset Password</button>
    </form>

    <p><a href="{{ route('login') }}">Back to sign in</a></p>
</body>

</html>
