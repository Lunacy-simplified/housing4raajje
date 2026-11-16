{{-- Find an owner or client and correct their contact details. Receives $search and $contacts (empty until a search is made). --}}
@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Front desk</span>
            <h1 class="t">Contacts</h1>
            <p class="sub">Search owners and clients by name, email or phone.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('staff.contacts.index') }}" class="flt">
        <x-field name="q" label="Search" :value="$search" required />
        <div class="acts">
            <button type="submit" class="btn btn-s">Search</button>
        </div>
    </form>

    @include('partials.errors')

    @if ($search !== '')
        <section class="card flush">
            <div class="sx">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">Account</th>
                            <th scope="col">Name and mobile number</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $contact)
                            <tr>
                                <td><b>{{ $contact->email }}</b><small>{{ $contact->role->name }}</small></td>
                                <td>
                                    <form method="POST" action="{{ route('staff.contacts.update', $contact) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" class="in" value="{{ $contact->name }}" aria-label="Name" required maxlength="255">
                                        <input name="phone" class="in" value="{{ $contact->phone }}" aria-label="Mobile number" inputmode="numeric" required>
                                        <button type="submit" class="btn btn-n">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty">Nobody matches "{{ $search }}".</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
