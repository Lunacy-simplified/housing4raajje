{{--
    Layout for the owner and client portals: top bar with the menu in it, then a centred main area.
    Only for logged-in users. Used the same way as layouts.portal:
        @extends('layouts.portal-top')
        @section('title', 'Home')
        @section('content') ... @endsection

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

            @include('partials.nav-top')

            <div class="who">
                <button type="button" class="btn btn-n menu-btn" aria-expanded="false" aria-controls="menu">Menu</button>
                <span class="who-t"><b>{{ $user->name }}</b><small>{{ $user->role?->name }}</small></span>
                @include('partials.logout')
            </div>
        </header>

        <main class="wrap">
            @include('partials.status')
            @yield('content')
        </main>
    </div>
</body>

</html>
