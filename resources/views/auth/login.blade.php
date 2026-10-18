{{-- TEMPORARY: keep route('login'), @csrf, the field names email/password/remember and @error('email'). --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sign in</title>
</head>

<body>
    <h1>Sign in</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email')
            <p style="color:red">{{ $message }}</p>
        @enderror
        <label>Password <input type="password" name="password" required></label>
        <label><input type="checkbox" name="remember"> Remember me</label>
        <button type="submit">Sign in</button>
    </form>
</body>

</html>
