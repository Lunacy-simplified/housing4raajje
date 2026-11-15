{{--
    Edit staff (GET /admin/staff/{user}/edit, route "admin.staff.edit"). Handoff doc section 6.
    Receives: $user (with role, staffProfile, staffProfile.branch), $roles, $branches (active only).
    The page has separate forms. Must keep:
      a) details: POST + @method('PUT') to route('admin.staff.update', $user), same fields as create, old('field', current value)
      b) status:  POST + @method('PATCH') to route('admin.staff.status', $user), hidden is_active (0 or 1), errors on "is_active"
      c) invite:  POST to route('admin.staff.invite', $user), shown only while the invite is pending (email not verified)
    The staff code is shown as text only; it never changes.
--}}
@extends('layouts.portal')

@section('title', 'Edit ' . $user->name)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('admin.staff.index') }}">Staff</a> / Edit</span>
            <h1 class="t">{{ $user->name }}</h1>
            <p class="sub"><span class="mono">{{ $user->staffProfile->staff_code }}</span> · {{ $user->role->name }} · {{ $user->staffProfile->branch?->name ?? 'No branch' }}</p>
        </div>
        <div class="acts">
            @if ($user->is_active)
                <span class="b b-ok">Active</span>
            @else
                <span class="b b-off">Inactive</span>
            @endif
            @unless ($user->hasVerifiedEmail())
                <span class="b b-warn">Invite pending</span>
            @endunless
        </div>
    </div>

    @if ($errors->any())
        <div class="al al-err" role="alert"><span>Please fix the {{ Str::plural('error', $errors->count()) }} below.</span></div>
    @endif

    <div class="split">
        {{-- a) Details --}}
        <form action="{{ route('admin.staff.update', $user) }}" method="POST" class="card wide">
            @csrf
            @method('PUT')

            <h2 class="h">Details</h2>

            <div class="fgrid">
                <div class="fld">
                    <label for="name">Full name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="in @error('name') bad @enderror" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')
                        <span id="name-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="email">Work email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="in @error('email') bad @enderror" aria-describedby="email-hint @error('email') email-error @enderror" @error('email') aria-invalid="true" @enderror>
                    <span id="email-hint" class="hint">Changing it resets their email verification.</span>
                    @error('email')
                        <span id="email-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="phone">Mobile number</label>
                    <div class="pre">
                        <span>+960</span>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" required inputmode="numeric"
                            class="in @error('phone') bad @enderror" aria-describedby="phone-hint @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                    </div>
                    <span id="phone-hint" class="hint">Changing it resets their phone verification.</span>
                    @error('phone')
                        <span id="phone-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="role">Role</label>
                    <select id="role" name="role" required class="in @error('role') bad @enderror" @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                        @foreach ($roles as $role)
                            <option value="{{ $role->code }}" @selected(old('role', $user->role->code) === $role->code)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <span id="role-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="branch_id">Branch</label>
                    <select id="branch_id" name="branch_id" class="in @error('branch_id') bad @enderror" @error('branch_id') aria-invalid="true" aria-describedby="branch-error" @enderror>
                        <option value="">No branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->staffProfile->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <span id="branch-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="job_title">Job title (optional)</label>
                    <input id="job_title" type="text" name="job_title" value="{{ old('job_title', $user->staffProfile->job_title) }}" maxlength="80"
                        class="in @error('job_title') bad @enderror" @error('job_title') aria-invalid="true" aria-describedby="job-error" @enderror>
                    @error('job_title')
                        <span id="job-error" class="err">{{ $message }}</span>
                    @enderror
                </div>

                <div class="fld">
                    <label for="hired_on">Hire date (optional)</label>
                    <input id="hired_on" type="date" name="hired_on" value="{{ old('hired_on', optional($user->staffProfile->hired_on)->format('Y-m-d')) }}"
                        class="in @error('hired_on') bad @enderror" @error('hired_on') aria-invalid="true" aria-describedby="hired-error" @enderror>
                    @error('hired_on')
                        <span id="hired-error" class="err">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="acts" style="padding-top: 6px; border-top: 1px solid var(--line-2)">
                <button type="submit" class="btn btn-p">Save changes</button>
                <a href="{{ route('admin.staff.index') }}" class="btn btn-n">Back to staff list</a>
            </div>
        </form>

        <div class="stack narrow">
            {{-- c) Resend invite: only while the invite is pending --}}
            @if (!$user->hasVerifiedEmail())
                <form method="POST" action="{{ route('admin.staff.invite', $user) }}" class="card">
                    @csrf
                    <h2 class="h">Invitation pending</h2>
                    <p class="hint" style="margin: 0">This staff member hasn't set up their account yet. Invite links last 24 hours.</p>
                    <button type="submit" class="btn btn-s">Resend invite</button>
                </form>
            @endif

            {{-- b) Activate / deactivate --}}
            <form action="{{ route('admin.staff.status', $user) }}" method="POST" class="card">
                @csrf
                @method('PATCH')

                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">

                <h2 class="h">Account status</h2>
                <p class="hint" style="margin: 0">
                    Current status: <b>{{ $user->is_active ? 'Active' : 'Inactive' }}</b>.
                    @if ($user->is_active)
                        Deactivating signs them out on their next click and blocks sign-in.
                    @endif
                </p>
                @error('is_active')
                    <span class="err" role="alert">{{ $message }}</span>
                @enderror
                <button type="submit" @class(['btn', 'btn-d' => $user->is_active, 'btn-s' => !$user->is_active])>
                    {{ $user->is_active ? 'Deactivate account' : 'Activate account' }}
                </button>
            </form>

            <p class="hint" style="margin: 0">Passwords aren't changed here. Staff use "Forgot password?" on the sign-in page.</p>
        </div>
    </div>
@endsection
