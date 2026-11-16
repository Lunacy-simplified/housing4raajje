{{--
    Layout for staff portals (admin, branch manager, property specialist, receptionist):
    top bar + side menu + main area. Only for logged-in users.

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
            <span class="chip">{{ $user->role?->name }}</span>

            <div class="who">
                <span class="who-t"><b>{{ $user->name }}</b><small>{{ $user->email }}</small></span>
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
