{{-- Profile self-management for every role. Owners add bank details; clients add their preferences. --}}
@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Account</span>
            <h1 class="t">Profile</h1>
            <p class="sub">{{ $user->email }} · {{ $user->role->name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="card limit">
        @csrf
        @method('PUT')

        <div class="fgrid">
            <x-field name="name" label="Full name" :value="$user->name" required />
            <x-field name="phone" label="Mobile number" :value="$user->phone" inputmode="numeric" required
                hint="7 digits, starting with 7 or 9. Changing it resets phone verification." />

            @if ($user->hasRole('owner'))
                <x-field name="national_id_or_passport" label="National ID or passport number" :value="$user->ownerProfile->national_id_or_passport" required />
                <x-field name="bank_name" label="Bank (for payouts)" :value="$user->ownerProfile->bank_name" />
                <x-field name="bank_account_name" label="Account name" :value="$user->ownerProfile->bank_account_name" />
                <x-field name="bank_account_no" label="Account number" :value="$user->ownerProfile->bank_account_no" />
            @elseif ($user->hasRole('client'))
                <x-field name="national_id_or_passport" label="National ID or passport number" :value="$user->clientProfile->national_id_or_passport" />
                <x-select name="sex" label="Sex" :options="['male' => 'Male', 'female' => 'Female']" :value="$user->clientProfile->sex" placeholder="Not given" />
                <x-field name="date_of_birth" label="Date of birth" type="date" :value="$user->clientProfile->date_of_birth?->format('Y-m-d')" />
                <x-field name="max_monthly_rent" label="Maximum monthly rent (MVR)" type="number" min="0" step="0.01" :value="$user->clientProfile->max_monthly_rent" />
                <x-field name="preferred_rooms" label="Rooms wanted" type="number" min="1" max="20" :value="$user->clientProfile->preferred_rooms" />
                <x-field name="preferred_term_months" label="Preferred lease length (months)" type="number" min="1" max="120" :value="$user->clientProfile->preferred_term_months" />
            @endif
        </div>

        @if ($user->hasRole('client'))
            <div class="fld">
                <label>Where would you like to live?</label>
                @foreach ($branches as $branch)
                    <label class="chk">
                        <input type="checkbox" name="branches[]" value="{{ $branch->id }}"
                            @checked(in_array($branch->id, old('branches', $user->clientProfile->preferredBranches->pluck('id')->all())))>
                        {{ $branch->name }}
                    </label>
                @endforeach
            </div>
        @endif

        @unless ($user->hasRole(...App\Models\User::MFA_ROLES))
            <label class="chk switch">Ask for an emailed code every time I sign in
                <input type="checkbox" name="mfa_enabled" value="1" @checked(old('mfa_enabled', $user->mfa_enabled))>
            </label>
        @endunless

        <div class="acts form-end">
            <button type="submit" class="btn btn-p">Save profile</button>
        </div>
    </form>
@endsection
