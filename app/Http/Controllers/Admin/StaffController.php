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
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    private const STAFF_ROLES = [
        'system_admin',
        'branch_manager',
        'property_specialist',
        'receptionist',
    ];

    public function create()
    {
        return view('admin.staff.create', [
            'roles' => Role::whereIn('code', self::STAFF_ROLES)
                ->orderBy('code')
                ->get(),

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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'regex:/^[79]\d{6}$/',
                'unique:users,phone',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],

            'role' => [
                'required',
                Rule::in(self::STAFF_ROLES),
            ],

            'branch_id' => [
                'nullable',
                'required_unless:role,system_admin',
                'prohibited_if:role,system_admin',
                Rule::exists('branches', 'id')
                    ->where('is_active', true),
            ],

            'job_title' => [
                'nullable',
                'string',
                'max:80',
            ],

            'hired_on' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ], [
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
                'password' => $validated['password'],
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

        return redirect()
            ->route('admin.staff.create')
            ->with(
                'status',
                "Staff account {$user->staffProfile->staff_code} created for {$user->name}."
            );
    }
}
