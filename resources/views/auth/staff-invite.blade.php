<form method="POST" action="{{ route('staff-invite.accept') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>

    @error('email')
        <p>{{ $message }}</p>
    @enderror

    <label for="password">New password</label>
    <input id="password" type="password" name="password" required autocomplete="new-password">

    @error('password')
        <p>{{ $message }}</p>
    @enderror

    <label for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

    <button type="submit">Set password</button>
</form>
