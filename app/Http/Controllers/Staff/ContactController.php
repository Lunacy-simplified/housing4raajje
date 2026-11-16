<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Front desk: find an owner or client and correct their basic contact details. */
class ContactController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        return view('staff.contacts', [
            'search' => $search,
            'contacts' => $search === '' ? collect() : User::whereHas('role', fn ($q) => $q->whereIn('code', ['owner', 'client']))
                ->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"))
                ->with('role')->orderBy('name')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('owner', 'client'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[79]\d{6}$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ], ['phone.regex' => 'Enter a Maldivian mobile number: 7 digits starting with 7 or 9.']);

        $old = $user->only('name', 'phone');

        // A new number isn't proven yet, and a code sent to the old number must stop working.
        if ($user->phone !== $data['phone']) {
            $user->phone_verified_at = null;
            $user->oneTimeCodes()->where('purpose', 'phone_verification')->whereNull('consumed_at')->update(['consumed_at' => now()]);
        }

        $user->fill($data)->save();
        Audit::log('contact.update', $user, $old, $data);

        return back()->with('status', "Contact details for {$user->name} updated.");
    }
}
