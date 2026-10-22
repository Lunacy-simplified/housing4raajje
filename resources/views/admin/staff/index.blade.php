<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
</head>

<body>
    <h1>Staff Management</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>
        <a href="{{ route('admin.staff.create') }}">Create staff</a>
    </p>

    <form method="GET" action="{{ route('admin.staff.index') }}">
        <label for="role">Role</label>
        <select name="role" id="role">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->code }}" @selected(request('role') === $role->code)>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>

        <label for="branch_id">Branch</label>
        <select name="branch_id" id="branch_id">
            <option value="">All branches</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>

        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status') === 'active')>
                Active
            </option>
            <option value="inactive" @selected(request('status') === 'inactive')>
                Inactive
            </option>
        </select>

        <button type="submit">Filter</button>
        <a href="{{ route('admin.staff.index') }}">Clear filters</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Staff code</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Branch</th>
                <th>Job title</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staff as $member)
                <tr>
                    <td>{{ $member->staffProfile->staff_code }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->phone }}</td>
                    <td>{{ $member->role->name }}</td>
                    <td>{{ $member->staffProfile->branch?->name ?? '—' }}</td>
                    <td>{{ $member->staffProfile->job_title }}</td>
                    <td>{{ $member->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $staff->links('pagination::bootstrap-4') }}
</body>

</html>
