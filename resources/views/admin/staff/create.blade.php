<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Staff Account</title>
</head>

<body>
    <h1>Create Staff Account</h1>

    @if (session('status'))
        <p style="color: green">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div style="color: red">
            <p>Please correct the following errors:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.staff.store') }}">
        @csrf

        <div>
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
        </div>

        <div>
            <label for="phone">Mobile number</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" pattern="[79][0-9]{6}"
                maxlength="7" required>
        </div>

        <div>
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="">Select a role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->code }}" @selected(old('role') === $role->code)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="branch_id">Branch</label>
            <select id="branch_id" name="branch_id">
                <option value="">No branch (system administrators only)</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                        {{ $branch->branch_no }} - {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="job_title">Job title (optional)</label>
            <input id="job_title" name="job_title" type="text" value="{{ old('job_title') }}" maxlength="80">
        </div>

        <div>
            <label for="hired_on">Hire date (optional)</label>
            <input id="hired_on" name="hired_on" type="date" value="{{ old('hired_on') }}"
                max="{{ date('Y-m-d') }}">
        </div>

        <button type="submit">Create Staff Account</button>
    </form>
</body>

</html>
