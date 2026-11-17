{{--
    Layout for account pages: sign in, register, MFA, forgot/reset password, staff invite, email and phone verification.
    A bordered box in the middle of the white page, with the full logo on top.

    How to use it in a page:
        @extends('layouts.auth')
        @section('title', 'Sign in')
        @section('content')
            ...the form...
        @endsection
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body>
    <div class="auth">
        <main class="auth-box">
            <div class="auth-logo">@include('partials.logo')</div>

            @yield('content')
        </main>
    </div>
</body>

</html>
