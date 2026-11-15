{{--
    Create staff (GET /admin/staff/create, route "admin.staff.create"). Handoff doc section 5.
    Receives: $roles (code + name), $branches (active branches: id, name, branch_no).
    Must keep: POST to route('admin.staff.store'), @csrf, field names name / email / phone / role / branch_id / job_title / hired_on,
    the first branch option with value "" (required for system administrators). No password fields: staff get an invite email.
--}}
@extends('layouts.portal')

@section('title', 'Create staff')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('admin.staff.index') }}">Staff</a> / Create</span>
            <h1 class="t">Create staff account</h1>
            <p class="sub">A staff code is made automatically. They get an invite email and choose their own password.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="al al-err" role="alert"><span>Please fix the {{ Str::plural('error', $errors->count()) }} below.</span></div>
    @endif

    <form method="POST" action="{{ route('admin.staff.store') }}" class="card" style="max-width: 760px">
        @csrf

        <div class="fgrid">
            <div class="fld">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="in @error('name') bad @enderror" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <span id="name-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="email">Work email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="in @error('email') bad @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <span id="email-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="phone">Mobile number</label>
                <div class="pre">
                    <span>+960</span>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" pattern="[79][0-9]{6}" maxlength="7" required inputmode="numeric"
                        class="in @error('phone') bad @enderror" aria-describedby="phone-hint @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                </div>
                <span id="phone-hint" class="hint">7 digits, starting with 7 or 9</span>
                @error('phone')
                    <span id="phone-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="role">Role</label>
                <select id="role" name="role" required class="in @error('role') bad @enderror" @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                    <option value="">Select a role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->code }}" @selected(old('role') === $role->code)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role')
                    <span id="role-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="branch_id">Branch</label>
                <select id="branch_id" name="branch_id" class="in @error('branch_id') bad @enderror" aria-describedby="branch-hint @error('branch_id') branch-error @enderror" @error('branch_id') aria-invalid="true" @enderror>
                    <option value="">No branch (system administrators only)</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->branch_no }} - {{ $branch->name }}</option>
                    @endforeach
                </select>
                <span id="branch-hint" class="hint">Every role except System Administrator needs a branch.</span>
                @error('branch_id')
                    <span id="branch-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="job_title">Job title (optional)</label>
                <input id="job_title" name="job_title" type="text" value="{{ old('job_title') }}" maxlength="80"
                    class="in @error('job_title') bad @enderror" @error('job_title') aria-invalid="true" aria-describedby="job-error" @enderror>
                @error('job_title')
                    <span id="job-error" class="err">{{ $message }}</span>
                @enderror
            </div>

            <div class="fld">
                <label for="hired_on">Hire date (optional)</label>
                <input id="hired_on" name="hired_on" type="date" value="{{ old('hired_on') }}" max="{{ date('Y-m-d') }}"
                    class="in @error('hired_on') bad @enderror" @error('hired_on') aria-invalid="true" aria-describedby="hired-error" @enderror>
                @error('hired_on')
                    <span id="hired-error" class="err">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="acts" style="padding-top: 6px; border-top: 1px solid var(--line-2)">
            <button type="submit" class="btn btn-p">Create staff account</button>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-n">Cancel</a>
        </div>
    </form>
@endsection
