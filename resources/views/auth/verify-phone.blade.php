{{-- TEMPORARY: frontend team restyles this. Keep route('phone.verification.send'), route('phone.verification.verify'), @csrf, the field name "code" and @error('code'). --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your phone</title>
</head>

<body>
    <h1>Verify your phone</h1>

    <p>Phone number: {{ auth()->user()->phone ?? 'not set' }}</p>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    @if (auth()->user()->hasVerifiedPhone())
        <p>Phone verified ✓</p>
    @else
        <p>We'll text a 6-digit code to this number. It expires in 10 minutes.</p>

        <form method="POST" action="{{ route('phone.verification.send') }}">
            @csrf
            <button type="submit">Send code</button>
        </form>

        <form method="POST" action="{{ route('phone.verification.verify') }}">
            @csrf

            <div>
                <label for="code">Code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                    maxlength="6" required>
                @error('code')
                    <p role="alert" style="color:red">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit">Verify</button>
        </form>
    @endif

    <p><a href="{{ route(auth()->user()->dashboardRoute()) }}">Back to dashboard</a></p>
</body>

</html>
