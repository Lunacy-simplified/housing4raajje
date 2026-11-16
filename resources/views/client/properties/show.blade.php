{{--
    One listing, for a client. Receives $property, $terms [id => label], $viewing and $application (open ones, or null).
    BR-08: the owner's phone number is shown only once it's verified.
--}}
@extends('layouts.app')

@section('title', $property->house_name)

@section('content')
    @php($available = $property->availability_status === 'available')

    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('client.properties.index') }}">Search</a></span>
            <h1 class="t">{{ $property->house_name }}</h1>
            <p class="sub">{{ $property->district->name }}, {{ $property->branch->name }} · <x-pill :status="$property->availability_status" /></p>
        </div>
    </div>

    @error('viewing')
        <div class="al al-err" role="alert"><span>{{ $message }}</span></div>
    @enderror

    <div class="split">
        <div class="stack wide">
            @if ($property->photos->isNotEmpty())
                <div class="photos">
                    @foreach ($property->photos as $photo)
                        <img class="photo" src="{{ route('photos.show', $photo) }}" alt="Photo of {{ $property->house_name }}">
                    @endforeach
                </div>
            @endif

            <section class="card">
                <h2 class="h">Details</h2>
                @include('partials.property-facts')
                @if ($property->owner->hasVerifiedPhone())
                    <p class="hint">Owner's phone: <span class="mono">{{ $property->owner->phone }}</span></p>
                @endif
            </section>

            <form method="POST" action="{{ route('messages.store') }}" class="card">
                @csrf
                <h2 class="h">Ask a question</h2>
                <p class="hint">The owner and the branch staff can both see and answer your message.</p>
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                <x-field name="subject" label="Subject" maxlength="150" required />
                <x-textarea name="body" label="Message" maxlength="2000" required />
                <button type="submit" class="btn btn-s">Send message</button>
            </form>
        </div>

        @if ($available)
            <div class="stack narrow">
                <section class="card">
                    <h2 class="h">Viewing</h2>
                    @if ($viewing)
                        <p class="hint">
                            <x-pill :status="$viewing->status" />
                            {{ $viewing->scheduled_for ? $viewing->scheduled_for->format('j M Y, g:i a') : 'The branch will confirm a date and time.' }}
                        </p>
                    @else
                        <form method="POST" action="{{ route('client.viewings.store', $property) }}">
                            @csrf
                            <button type="submit" class="btn btn-s">Request a viewing</button>
                        </form>
                    @endif
                </section>

                <section class="card">
                    <h2 class="h">Apply to rent</h2>
                    @if ($application)
                        <p class="hint"><x-pill :status="$application->status" /> Application <span class="mono">{{ $application->reference }}</span> is with the branch.</p>
                    @elseif (! auth()->user()->hasVerifiedEmail())
                        <p class="hint">Verify your email before you apply.</p>
                        <a href="{{ route('verification.notice') }}" class="btn btn-n">Verify email</a>
                    @else
                        <form method="POST" action="{{ route('client.applications.store', $property) }}" class="stack">
                            @csrf
                            <x-select name="lease_term_id" label="Lease length" :options="$terms" placeholder="Choose a length" required />
                            <x-field name="requested_start_date" label="Start date" type="date" :min="now()->format('Y-m-d')" required />
                            <x-field name="occupants" label="People who will live there" type="number" min="1" max="20" value="1" required />
                            <x-field name="employer" label="Employer (optional)" maxlength="120" />
                            <x-select name="income_bracket" label="Monthly income in MVR (optional)" placeholder="Prefer not to say"
                                :options="array_combine(App\Http\Controllers\Client\ApplicationController::INCOME_BRACKETS, App\Http\Controllers\Client\ApplicationController::INCOME_BRACKETS)" />
                            <button type="submit" class="btn btn-p">Submit application</button>
                        </form>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection
