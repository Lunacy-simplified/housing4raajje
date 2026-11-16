{{--
    Side menu for staff portals (admin, branch manager, property specialist, receptionist).
    It only links to pages that exist. Add a line here when a new page is built.
    request()->routeIs('admin.staff.*') is true on every admin staff page, so that menu item is highlighted there.
--}}
@php($role = auth()->user()->role?->code)

<nav class="side" aria-label="Main menu">
    @if ($role === 'system_admin')
        <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>Overview</a>
        <span class="grp">People</span>
        <a href="{{ route('admin.staff.index') }}" @class(['on' => request()->routeIs('admin.staff.*')])>Staff</a>
    @elseif ($role === 'branch_manager')
        <a href="{{ route('manager.dashboard') }}" @class(['on' => request()->routeIs('manager.dashboard')])>Branch overview</a>
    @else
        <a href="{{ route('staff.dashboard') }}" @class(['on' => request()->routeIs('staff.dashboard')])>Dashboard</a>
    @endif

    <span class="grp">Account</span>
    <a href="{{ route('phone.verification.show') }}" @class(['on' => request()->routeIs('phone.verification.*')])>Verify phone</a>
</nav>
