{{--
    Side menu for staff portals. Each role only sees links it is allowed to open.
    Add a line here when a new page is built.
--}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
@endphp

<nav class="side" aria-label="Main menu">
    @if ($user->hasRole('system_admin'))
        @include('partials.nav-link', ['route' => 'admin.dashboard', 'label' => 'Overview'])
        <span class="grp">People</span>
        @include('partials.nav-link', ['route' => 'admin.staff.index', 'label' => 'Staff', 'match' => 'admin.staff.*'])
        <span class="grp">Finance</span>
        @include('partials.nav-link', ['route' => 'admin.payouts.index', 'label' => 'Owner payouts', 'match' => 'admin.payouts.*'])
        <span class="grp">System</span>
        @include('partials.nav-link', ['route' => 'admin.lookups.index', 'label' => 'Master data', 'match' => 'admin.lookups.*'])
        @include('partials.nav-link', ['route' => 'admin.settings.index', 'label' => 'Settings', 'match' => 'admin.settings.*'])
        @include('partials.nav-link', ['route' => 'admin.audit.index', 'label' => 'Audit log'])
    @else
        @if ($user->hasRole('branch_manager'))
            @include('partials.nav-link', ['route' => 'manager.dashboard', 'label' => 'Branch report'])
            @include('partials.nav-link', ['route' => 'manager.staff.index', 'label' => 'Branch staff', 'match' => 'manager.staff.*'])
            @include('partials.nav-link', ['route' => 'manager.escalations.index', 'label' => 'Escalations', 'match' => 'manager.escalations.*'])
        @else
            @include('partials.nav-link', ['route' => 'staff.dashboard', 'label' => 'Dashboard'])
        @endif
        <span class="grp">Work</span>
        @include('partials.nav-link', ['route' => 'staff.properties.index', 'label' => 'Properties', 'match' => 'staff.properties.*'])
        @include('partials.nav-link', ['route' => 'staff.applications.index', 'label' => 'Applications', 'match' => 'staff.applications.*'])
        @include('partials.nav-link', ['route' => 'staff.viewings.index', 'label' => 'Viewings', 'match' => 'staff.viewings.*'])
        @include('partials.nav-link', ['route' => 'staff.leases.index', 'label' => 'Leases', 'match' => 'staff.leases.*'])
        @include('partials.nav-link', ['route' => 'staff.maintenance.index', 'label' => 'Maintenance', 'match' => 'staff.maintenance.*'])
        <span class="grp">Front desk</span>
        @include('partials.nav-link', ['route' => 'staff.inquiries.index', 'label' => 'Inquiries', 'match' => 'staff.inquiries.*'])
        @include('partials.nav-link', ['route' => 'staff.tickets.index', 'label' => 'Support tickets', 'match' => 'staff.tickets.*'])
        @include('partials.nav-link', ['route' => 'staff.contacts.index', 'label' => 'Contacts', 'match' => 'staff.contacts.*'])
        @include('partials.nav-link', ['route' => 'messages.index', 'label' => 'Messages', 'match' => 'messages.*'])
    @endif

    <span class="grp">Account</span>
    @include('partials.nav-link', ['route' => 'notifications.index', 'label' => $unread ? "Notifications ($unread)" : 'Notifications'])
    @include('partials.nav-link', ['route' => 'profile.edit', 'label' => 'Profile'])
    @include('partials.nav-link', ['route' => 'phone.verification.show', 'label' => 'Verify phone', 'match' => 'phone.verification.*'])
</nav>
