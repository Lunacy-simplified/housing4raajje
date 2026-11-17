{{-- Top menu for the owner and client portals. Add a line here when a new page is built. --}}
@php($user = auth()->user())

<nav class="tnav" id="menu" aria-label="Main menu">
    @include('partials.nav-link', ['route' => $user->dashboardRoute(), 'label' => 'Home'])
    @if ($user->hasRole('owner'))
        @include('partials.nav-link', ['route' => 'owner.properties.index', 'label' => 'Properties', 'match' => 'owner.properties.*'])
        @include('partials.nav-link', ['route' => 'leases.index', 'label' => 'Leases', 'match' => 'leases.*'])
        @include('partials.nav-link', ['route' => 'owner.earnings.index', 'label' => 'Earnings', 'match' => 'owner.earnings.*'])
    @else
        @include('partials.nav-link', ['route' => 'client.properties.index', 'label' => 'Search', 'match' => 'client.properties.*'])
        @include('partials.nav-link', ['route' => 'client.viewings.index', 'label' => 'Viewings', 'match' => 'client.viewings.*'])
        @include('partials.nav-link', ['route' => 'client.applications.index', 'label' => 'Applications', 'match' => 'client.applications.*'])
        @include('partials.nav-link', ['route' => 'leases.index', 'label' => 'Leases', 'match' => 'leases.*'])
    @endif
    @include('partials.nav-link', ['route' => 'maintenance.index', 'label' => 'Maintenance', 'match' => 'maintenance.*'])
    @include('partials.nav-link', ['route' => 'messages.index', 'label' => 'Messages', 'match' => 'messages.*'])
    @include('partials.account-links', ['class' => 'sm'])
</nav>
