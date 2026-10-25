<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portal }} Dashboard - Housing4Raajje</title>
</head>

<body>
    <h1>{{ $portal }} Dashboard</h1>

    <p>Welcome, {{ auth()->user()->name }}!</p>

    <h2>Email Verification</h2>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if (auth()->user()->hasVerifiedEmail())
        <p>Email verified ✓</p>
    @else
        <p>Not verified</p>
        <p>Please verify your email to use restricted features.</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">Resend verification email</button>
        </form>

        <p>
            <a href="{{ route('verification.notice') }}">
                View verification instructions
            </a>
        </p>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</body>

</html>
