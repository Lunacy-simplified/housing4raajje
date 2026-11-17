{{--
    Staff list for system administrators (GET /admin/staff, route "admin.staff.index"). Handoff doc section 4.
    Receives: $staff (20 per page), $roles (the four staff roles), $branches (all branches).
    Must keep: the GET filter form with fields role / branch_id / status (no @csrf on GET forms),
    and $staff->links('pagination::bootstrap-4') for the page numbers.
--}}
@extends('layouts.portal')

@section('title', 'Staff')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Staff</h1>
            <p class="sub">{{ $staff->total() }} {{ Str::plural('staff member', $staff->total()) }} found</p>
        </div>
        <div class="acts">
            <a href="{{ route('admin.staff.create') }}" class="btn btn-p">Create staff</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.staff.index') }}" class="flt">
        <div class="fld">
            <label for="role">Role</label>
            <select name="role" id="role" class="in">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->code }}" @selected(request('role') === $role->code)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="fld">
            <label for="branch_id">Branch</label>
            <select name="branch_id" id="branch_id" class="in">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="fld">
            <label for="status">Status</label>
            <select name="status" id="status" class="in">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>

        <div class="acts">
            <button type="submit" class="btn btn-s">Filter</button>
            <a href="{{ route('admin.staff.index') }}" class="act">Clear filters</a>
        </div>
    </form>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Staff code</th>
                        <th scope="col">Name</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Role</th>
                        <th scope="col">Branch</th>
                        <th scope="col">Job title</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last login</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td class="mono">{{ $member->staffProfile->staff_code }}</td>
                            <td><b>{{ $member->name }}</b><small>{{ $member->email }}</small></td>
                            <td>{{ $member->phone }}</td>
                            <td>{{ $member->role->name }}</td>
                            <td>{{ $member->staffProfile->branch?->name ?? '—' }}</td>
                            <td>{{ $member->staffProfile->job_title }}</td>
                            <td>
                                @if ($member->is_active)
                                    <span class="b b-ok">Active</span>
                                @else
                                    <span class="b b-off">Inactive</span>
                                @endif
                                @unless ($member->hasVerifiedEmail())
                                    <span class="b b-warn">Invite pending</span>
                                @endunless
                            </td>
                            <td class="muted">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="r"><a href="{{ route('admin.staff.edit', $member) }}" class="act">Edit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No staff match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($staff->hasPages())
        <div class="pg">
            <span>Showing {{ $staff->firstItem() }} to {{ $staff->lastItem() }} of {{ $staff->total() }}</span>
            {{ $staff->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endsection
