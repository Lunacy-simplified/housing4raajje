<h1>Edit Staff Member</h1>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('admin.staff.update', $user) }}" method="POST">
    @csrf
    @method('PUT')

    <p>
        <strong>Staff Code:</strong>
        {{ $user->staffProfile->staff_code }}
    </p>

    <label for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>

    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>

    <label for="phone">Phone</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" required>

    <label for="role">Role</label>
    <select id="role" name="role" required>
        @foreach ($roles as $role)
            <option value="{{ $role->code }}" @selected(old('role', $user->role->code) === $role->code)>
                {{ $role->code }}
            </option>
        @endforeach
    </select>

    <label for="branch_id">Branch</label>
    <select id="branch_id" name="branch_id">
        <option value="">No branch</option>
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->staffProfile->branch_id) === (string) $branch->id)>
                {{ $branch->name }}
            </option>
        @endforeach
    </select>

    <label for="job_title">Job Title</label>
    <input id="job_title" type="text" name="job_title"
        value="{{ old('job_title', $user->staffProfile->job_title) }}">

    <label for="hired_on">Hire Date</label>
    <input id="hired_on" type="date" name="hired_on"
        value="{{ old('hired_on', optional($user->staffProfile->hired_on)->format('Y-m-d')) }}">

    <button type="submit">Save Changes</button>
</form>

<hr>

<h2>Account Status</h2>

<p>
    Current status:
    {{ $user->is_active ? 'Active' : 'Inactive' }}
</p>

<form action="{{ route('admin.staff.status', $user) }}" method="POST">
    @csrf
    @method('PATCH')

    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">

    <button type="submit">
        {{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}
    </button>
</form>

<a href="{{ route('admin.staff.index') }}">Back to Staff List</a>
