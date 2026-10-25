<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(string $type)
    {
        return view('auth.register', ['type' => $type]);
    }

    public function store(Request $request, string $type)
    {
        // normalize the email before validating it.
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        // normalize the id before validation
        if ($request->filled('national_id_or_passport')) {
            $request->merge([
                'national_id_or_passport' => strtoupper(
                    trim($request->input('national_id_or_passport'))
                ),
            ]);
        }

        $rules = [
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

            'terms' => ['accepted'],
        ];

        // Owners must provide a unique national ID or passport number.
        if ($type === 'owner') {
            $rules['national_id_or_passport'] = [
                'required',
                'string',
                'max:30',
                'unique:owner_profiles,national_id_or_passport',
            ];
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'Enter a Maldivian mobile number: 7 digits starting with 7 or 9.',
        ]);

        // Create the user and profile together in a transaction.
        $user = DB::transaction(function () use ($validated, $type) {
            $role = Role::where('code', $type)->firstOrFail();

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
            ]);

            $user->role_id = $role->id;
            $user->save();

            if ($type === 'client') {
                $user->clientProfile()->create([]);
            } else {
                $user->ownerProfile()->create([
                    'national_id_or_passport' =>
                    $validated['national_id_or_passport'],
                ]);
            }

            return $user;
        });

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route($user->dashboardRoute());
    }
}
