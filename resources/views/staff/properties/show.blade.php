{{--
    One listing, for branch staff. Receives $property and $matches (clients the matchmaker found; empty until approved).
    Only specialists and managers see the decision form.
--}}
@extends('layouts.app')

@section('title', $property->reference)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('staff.properties.index') }}">Properties</a></span>
            <h1 class="t">{{ $property->house_name }}</h1>
            <p class="sub">
                <x-pill :status="$property->verification_status" />
                @if ($property->availability_status)
                    <x-pill :status="$property->availability_status" />
                @endif
                @if ($property->verifiedBy)
                    Approved by {{ $property->verifiedBy->name }} on {{ $property->verified_at->format('j M Y') }}
                @endif
            </p>
        </div>
    </div>

    <div class="split">
        <div class="stack wide">
            <section class="card">
                <h2 class="h">Details</h2>
                @include('partials.property-facts')
            </section>

            @if ($property->photos->isNotEmpty())
                <div class="photos">
                    @foreach ($property->photos as $photo)
                        <img class="photo" src="{{ route('photos.show', $photo) }}" alt="Photo of {{ $property->house_name }}">
                    @endforeach
                </div>
            @endif

            @if ($property->verification_status === 'approved')
                <section class="card flush">
                    <div class="sx">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th scope="col">Matching client</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Maximum rent</th>
                                    <th scope="col">Rooms wanted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($matches as $client)
                                    <tr>
                                        <td><b>{{ $client->name }}</b><small>{{ $client->email }}</small></td>
                                        <td>{{ $client->phone }}</td>
                                        <td>MVR {{ number_format($client->clientProfile->max_monthly_rent, 2) }}</td>
                                        <td>{{ $client->clientProfile->preferred_rooms ?? 'Any' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="empty">No client's budget and preferred location match this property yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <div class="stack narrow">
            <section class="card">
                <h2 class="h">Owner</h2>
                <dl class="kv">
                    <dt>Name</dt>
                    <dd>{{ $property->owner->name }}</dd>
                    <dt>Phone</dt>
                    <dd>{{ $property->owner->phone }} {{ $property->owner->hasVerifiedPhone() ? '(verified)' : '(not verified)' }}</dd>
                    <dt>ID or passport</dt>
                    <dd class="mono">{{ $property->owner->ownerProfile->national_id_or_passport }}</dd>
                </dl>
            </section>

            @if ($property->verification_status === 'pending' && auth()->user()->hasRole('property_specialist', 'branch_manager'))
                <form method="POST" action="{{ route('staff.properties.decide', $property) }}" class="card">
                    @csrf
                    @method('PATCH')
                    <h2 class="h">Decision</h2>
                    <x-select name="decision" label="Outcome" placeholder="Choose" required
                        :options="['approved' => 'Approve and publish', 'changes_requested' => 'Send back for changes', 'rejected' => 'Reject']" />
                    <x-textarea name="note" label="Note to the owner" hint="Needed unless you approve." maxlength="1000" />
                    <button type="submit" class="btn btn-p">Save decision</button>
                </form>
            @endif
        </div>
    </div>
@endsection
