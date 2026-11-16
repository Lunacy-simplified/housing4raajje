<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile', [
            'user' => $request->user()->load('role', 'ownerProfile', 'clientProfile.preferredBranches'),
            'branches' => Branch::where('is_active', true)->orderBy('branch_no')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[79]\d{6}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'mfa_enabled' => ['nullable', 'boolean'],
        ];

        if ($user->hasRole('owner')) {
            $rules += [
                'national_id_or_passport' => ['required', 'string', 'max:30', Rule::unique('owner_profiles')->ignore($user->id, 'user_id')],
                'bank_name' => ['nullable', 'string', 'max:80'],
                'bank_account_name' => ['nullable', 'string', 'max:120'],
                'bank_account_no' => ['nullable', 'string', 'max:40'],
            ];
        } elseif ($user->hasRole('client')) {
            $rules += [
                'national_id_or_passport' => ['nullable', 'string', 'max:30', Rule::unique('client_profiles')->ignore($user->id, 'user_id')],
                'sex' => ['nullable', Rule::in(['male', 'female'])],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'max_monthly_rent' => ['nullable', 'numeric', 'min:0'],
                'preferred_rooms' => ['nullable', 'integer', 'between:1,20'],
                'preferred_term_months' => ['nullable', 'integer', 'between:1,120'],
                'branches' => ['nullable', 'array'],
                'branches.*' => [Rule::exists('branches', 'id')->where('is_active', true)],
            ];
        }

        $data = $request->validate($rules, [
            'phone.regex' => 'Enter a Maldivian mobile number: 7 digits starting with 7 or 9.',
        ]);

        DB::transaction(function () use ($user, $data) {
            // A new number isn't proven yet, and a code sent to the old number must stop working.
            if ($user->phone !== $data['phone']) {
                $user->phone_verified_at = null;
                $user->oneTimeCodes()->where('purpose', 'phone_verification')->whereNull('consumed_at')->update(['consumed_at' => now()]);
            }

            $user->name = $data['name'];
            $user->phone = $data['phone'];

            // Admins and managers always use sign-in codes; everyone else may switch them on.
            if (! $user->hasRole(...User::MFA_ROLES)) {
                $user->mfa_enabled = (bool) ($data['mfa_enabled'] ?? false);
            }

            $user->save();

            if ($user->hasRole('owner')) {
                $user->ownerProfile->update(collect($data)->only('national_id_or_passport', 'bank_name', 'bank_account_name', 'bank_account_no')->all());
            } elseif ($user->hasRole('client')) {
                $user->clientProfile->update(collect($data)->only(
                    'national_id_or_passport', 'sex', 'date_of_birth', 'max_monthly_rent', 'preferred_rooms', 'preferred_term_months'
                )->all());
                $user->clientProfile->preferredBranches()->sync($data['branches'] ?? []);
            }
        });

        return back()->with('status', 'Profile saved.');
    }
}
