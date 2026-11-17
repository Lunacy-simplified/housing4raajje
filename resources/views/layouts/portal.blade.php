{{--
    Layout for staff portals (admin, branch manager, property specialist, receptionist):
    top bar (logo, notifications, the user's name, log out) + side menu + main area. Only for logged-in users.

    How to use it in a page:
        @extends('layouts.portal')
        @section('title', 'Staff')
        @section('content')
            ...the page...
        @endsection

    Success messages (session('status')) are shown automatically at the top of the main area.
--}}
@php($user = auth()->user())
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body>
    <div class="app">
        <header class="top">
            <a class="brand" href="{{ route($user->dashboardRoute()) }}" aria-label="H4R home">
                @include('partials.logo-mark')
            </a>
            <div class="who">
                <button type="button" class="btn btn-n menu-btn" aria-expanded="false" aria-controls="menu">Menu</button>
                @include('partials.account-links', ['class' => 'acct'])
                @include('partials.logout')
            </div>
        </header>

        <div class="shell">
            @include('partials.nav-side')

            <main class="main">
                @include('partials.status')
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
