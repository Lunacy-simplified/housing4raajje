{{-- TEMPORARY: frontend team restyles this. Keep route('mfa.verify'), route('mfa.resend'), @csrf, the field name "code" and @error('code'). --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter your sign-in code</title>
</head>

<body>
    <h1>Enter your sign-in code</h1>

    <p>We've emailed you a 6-digit code. It expires in 10 minutes.</p>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf

        <div>
            <label for="code">Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                maxlength="6" required autofocus>
            @error('code')
                <p role="alert" style="color:red">{{ $message }}</p>
            @enderror
        </div>
        <label><input type="checkbox" name="trust_device" value="1"> Don't ask for a code on this device for 30
            days</label>
        <button type="submit">Verify</button>
    </form>

    <form method="POST" action="{{ route('mfa.resend') }}">
        @csrf
        <button type="submit">Send a new code</button>
    </form>

    <p><a href="{{ route('login') }}">Back to sign in</a></p>
</body>

</html>
