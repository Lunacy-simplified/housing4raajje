{{--
    Top menu for the owner and client portals.
    It only links to pages that exist. Add a line here when a new page is built.
--}}
@php($home = auth()->user()->dashboardRoute())

<nav class="tnav" aria-label="Main menu">
    <a href="{{ route($home) }}" @class(['on' => request()->routeIs($home)])>Home</a>
    <a href="{{ route('phone.verification.show') }}" @class(['on' => request()->routeIs('phone.verification.*')])>Verify phone</a>
</nav>
