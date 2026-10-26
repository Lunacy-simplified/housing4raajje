<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Notifications\StaffInvitation;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    private const STAFF_ROLES = [
        'system_admin',
        'branch_manager',
        'property_specialist',
        'receptionist',
    ];

    private function staffRoles()
    {
        return Role::whereIn('code', self::STAFF_ROLES)
            ->orderBy('code')
            ->get();
    }

    private function staffRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],

            'phone' => [
                'required',
                'regex:/^[79]\d{6}$/',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],

            'role' => ['required', Rule::in(self::STAFF_ROLES)],

            'branch_id' => [
                'nullable',
                'required_unless:role,system_admin',
                'prohibited_if:role,system_admin',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],

            'job_title' => ['nullable', 'string', 'max:80'],
            'hired_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::in(self::STAFF_ROLES)],
            'branch_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $staff = User::whereHas('staffProfile')
            ->with(['role', 'staffProfile.branch'])
            ->when($filters['role'] ?? null, function ($query, $role) {
                $query->whereHas('role', fn($q) => $q->where('code', $role));
            })

            ->when($filters['branch_id'] ?? null, function ($query, $branchId) {
                $query->whereHas('staffProfile', fn($q) => $q->where('branch_id', $branchId));
            })

            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })

            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.staff.index', [
            'staff' => $staff,
            'roles' => $this->staffRoles(),
            'branches' => Branch::orderBy('branch_no')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.staff.create', [
            'roles' => $this->staffRoles(),

            'branches' => Branch::where('is_active', true)
                ->orderBy('branch_no')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Normalize email before checking uniqueness.
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate($this->staffRules(), [
            'phone.regex' => 'Enter a Maldivian mobile number: 7 digits starting with 7 or 9.',
            'branch_id.required_unless' => 'Select a branch for this staff member.',
            'branch_id.prohibited_if' => 'System administrators must not be assigned to a branch.',
            'branch_id.exists' => 'Select an active branch.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $role = Role::where('code', $validated['role'])
                ->firstOrFail();

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Str::password(32),
            ]);

            $user->role_id = $role->id;
            $user->save();

            // Generate the next staff code.
            $last = StaffProfile::max('staff_code');
            $next = $last ? (int) substr($last, -4) + 1 : 1;
            $staffCode = sprintf('H4R-STF-%04d', $next);

            $user->staffProfile()->create([
                'staff_code' => $staffCode,
                'branch_id' => $validated['branch_id'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'hired_on' => $validated['hired_on'] ?? null,
            ]);

            return $user;
        });

        // Send the invitation after the database transaction commits.
        try {
            $token = Password::broker('staff_invites')->createToken($user);

            $user->notify(new StaffInvitation($token));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.staff.index')
                ->with(
                    'status',
                    "Staff account {$user->staffProfile->staff_code} created, but the invite email couldn't be sent. Use Resend invite."
                );
        }

        return redirect()
            ->route('admin.staff.index')
            ->with(
                'status',
                "Staff account {$user->staffProfile->staff_code} created for {$user->name}. An invitation email has been sent."
            );
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->staffProfile, 404);

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate($this->staffRules($user), [
            'phone.regex' => 'Enter a Maldivian mobile number: 7 digits starting with 7 or 9.',
            'branch_id.required_unless' => 'Select a branch for this staff member.',
            'branch_id.prohibited_if' => 'System administrators must not be assigned to a branch.',
            'branch_id.exists' => 'Select an active branch.',
        ]);

        if (
            $user->is($request->user())
            && $validated['role'] !== $user->role->code
        ) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role.',
            ]);
        }

        DB::transaction(function () use ($user, $validated) {
            $role = Role::where('code', $validated['role'])->firstOrFail();
            $emailChanged = $user->email !== $validated['email'];

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->role_id = $role->id;
            $user->save();

            $user->staffProfile->update([
                'branch_id' => $validated['branch_id'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'hired_on' => $validated['hired_on'] ?? null,
            ]);
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "Staff account {$user->staffProfile->staff_code} updated.");
    }


    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->staffProfile, 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if (
            $user->is($request->user())
            && ! $request->boolean('is_active')
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own administrative account.',
            ]);
        }

        $user->is_active = $request->boolean('is_active');
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with(
            'status',
            "Staff account {$user->staffProfile->staff_code} {$status} successfully."
        );
    }


    public function edit(User $user)
    {
        abort_unless($user->staffProfile, 404);

        return view('admin.staff.edit', [
            'user' => $user->load(['role', 'staffProfile.branch']),
            'roles' => $this->staffRoles(),
            'branches' => Branch::where('is_active', true)
                ->orderBy('branch_no')
                ->get(),
        ]);
    }

    public function resendInvite(User $user)
    {
        abort_unless($user->staffProfile, 404);

        if ($user->hasVerifiedEmail()) {
            return back()->with(
                'status',
                'This staff member has already set up their account.'
            );
        }

        try {
            $token = Password::broker('staff_invites')->createToken($user);

            $user->notify(new StaffInvitation($token));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'status',
                "The invitation email couldn't be sent. Please try again."
            );
        }

        return back()->with(
            'status',
            "A new invitation has been sent to {$user->email}."
        );
    }
}
