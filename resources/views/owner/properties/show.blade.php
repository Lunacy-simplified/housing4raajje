{{--
    Property dashboard for its owner.
    Receives $property, $viewings (completed, newest first), $reasons [label => percent], $lease (active or null), $rentPaid.
    Viewing history never shows client names (brief 2B: anonymized timeline).
--}}
@extends('layouts.app')

@section('title', $property->reference)

@section('content')
    @php($editable = in_array($property->verification_status, ['draft', 'changes_requested'], true))

    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('owner.properties.index') }}">My properties</a></span>
            <h1 class="t">{{ $property->house_name }}</h1>
            <p class="sub">
                <x-pill :status="$property->verification_status" />
                @if ($property->availability_status)
                    <x-pill :status="$property->availability_status" />
                @endif
            </p>
        </div>
        @if ($editable)
            <div class="acts">
                <a href="{{ route('owner.properties.edit', $property) }}" class="btn btn-n">Edit</a>
                <form method="POST" action="{{ route('owner.properties.submit', $property) }}">
                    @csrf
                    <button type="submit" class="btn btn-p">Submit for review</button>
                </form>
            </div>
        @endif
    </div>

    @include('partials.errors')

    <div class="split">
        <div class="stack wide">
            <section class="card">
                <h2 class="h">Details</h2>
                @include('partials.property-facts')
            </section>

            <section class="card">
                <h2 class="h">Photos</h2>
                @if ($property->photos->isNotEmpty())
                    <div class="photos">
                        @foreach ($property->photos as $photo)
                            <div>
                                <img class="photo" src="{{ route('photos.show', $photo) }}" alt="Photo of {{ $property->house_name }}">
                                <form method="POST" action="{{ route('owner.properties.photos.destroy', [$property, $photo]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="act">Remove</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('owner.properties.photos.store', $property) }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="file" name="photo" accept="image/*" aria-label="Photo" required>
                    <button type="submit" class="btn btn-s">Add photo</button>
                </form>
                <p class="hint">Up to 6 photos, 4 MB each.</p>
            </section>

            @if ($property->verification_status === 'approved')
                <section class="card flush">
                    <div class="sx">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th scope="col">Viewing date</th>
                                    <th scope="col">Verified by staff</th>
                                    <th scope="col">Feedback</th>
                                    <th scope="col">Staff note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($viewings as $viewing)
                                    <tr>
                                        <td>{{ ($viewing->scheduled_for ?? $viewing->logged_at)->format('d/m/Y') }}</td>
                                        <td>{{ $viewing->loggedBy->name }}</td>
                                        <td>{{ $viewing->feedbackCategory->label }}</td>
                                        <td>{{ $viewing->staff_note ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="empty">No completed viewings yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        @if ($property->verification_status === 'approved')
            <div class="stack narrow">
                <section class="card">
                    <h2 class="h">Availability</h2>
                    @if ($property->availability_status === 'rented')
                        <p class="hint">Rented. This changes back automatically when the lease ends.</p>
                    @else
                        <form method="POST" action="{{ route('owner.properties.availability', $property) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            @if ($property->availability_status === 'available')
                                <input type="hidden" name="availability_status" value="under_maintenance">
                                <button type="submit" class="btn btn-n">Mark as under maintenance</button>
                            @else
                                <input type="hidden" name="availability_status" value="available">
                                <button type="submit" class="btn btn-s">Mark as available</button>
                            @endif
                        </form>
                        <p class="hint">Under maintenance hides it from client searches.</p>
                    @endif
                </section>

                @if ($lease)
                    <section class="card">
                        <h2 class="h">Current lease</h2>
                        <dl class="kv">
                            <dt>Started</dt>
                            <dd>{{ $lease->start_date->format('j M Y') }}</dd>
                            <dt>Length</dt>
                            <dd>{{ $lease->term_months }} months</dd>
                            <dt>Ends</dt>
                            <dd>{{ $lease->end_date->format('j M Y') }}</dd>
                            <dt>This month's rent</dt>
                            <dd>{{ $rentPaid ? 'Paid' : 'Not paid yet' }}</dd>
                        </dl>
                        <a href="{{ route('leases.show', $lease) }}" class="btn btn-s">Open lease</a>
                    </section>
                @endif

                <section class="card">
                    <h2 class="h">Viewings</h2>
                    <div class="row">
                        <span class="hint">Times staff showed this property</span>
                        <b class="price">{{ $viewings->count() }}</b>
                    </div>
                    @foreach ($reasons as $label => $percent)
                        <div>
                            <div class="row"><span>{{ $label }}</span><span class="muted">{{ $percent }}%</span></div>
                            <div class="meter" role="img" aria-label="{{ $percent }}% of viewers: {{ $label }}"><i style="width: {{ $percent }}%"></i></div>
                        </div>
                    @endforeach
                    @if ($reasons->isEmpty())
                        <p class="hint">Reasons viewers gave for not choosing it will show here.</p>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection
