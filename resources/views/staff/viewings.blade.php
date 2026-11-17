{{--
    Viewing log for the branch. Receives $viewings, $open (true = requested and booked ones), $categories [id => label].
    Anyone in the branch can book a slot; only specialists and managers record the outcome.
--}}
@extends('layouts.app')

@section('title', 'Viewings')

@section('content')
    @php($logs = auth()->user()->hasRole('property_specialist', 'branch_manager'))

    <div class="ph">
        <div>
            <h1 class="t">Viewings</h1>
        </div>
    </div>

    <nav class="seg" aria-label="Which viewings">
        <a href="{{ route('staff.viewings.index') }}" @class(['on' => $open])>Open</a>
        <a href="{{ route('staff.viewings.index', ['show' => 'past']) }}" @class(['on' => ! $open])>Past</a>
    </nav>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Property</th>
                        <th scope="col">Client</th>
                        <th scope="col">When</th>
                        <th scope="col">Status</th>
                        <th scope="col">{{ $open ? 'Actions' : 'Feedback' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($viewings as $viewing)
                        <tr>
                            <td>
                                <b>{{ $viewing->property->house_name }}</b>
                                <small class="mono">{{ $viewing->property->reference }}</small>
                            </td>
                            <td><b>{{ $viewing->client->name }}</b><small>{{ $viewing->client->phone }}</small></td>
                            <td>
                                {{ $viewing->scheduled_for?->format('j M Y, g:i a') ?? 'Not booked' }}
                                <small>Asked {{ $viewing->requested_at->diffForHumans() }}</small>
                            </td>
                            <td><x-pill :status="$viewing->status" /></td>
                            <td>
                                @if ($open)
                                    <details>
                                        <summary>{{ $viewing->scheduled_for ? 'Rebook' : 'Book a time' }}</summary>
                                        <form method="POST" action="{{ route('staff.viewings.schedule', $viewing) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="datetime-local" name="scheduled_for" class="in" aria-label="Date and time"
                                                value="{{ $viewing->scheduled_for?->format('Y-m-d\TH:i') }}" required>
                                            <button type="submit" class="btn btn-s">{{ $viewing->scheduled_for ? 'Rebook' : 'Book' }}</button>
                                        </form>
                                    </details>
                                    @if ($logs)
                                        <details>
                                            <summary>Record outcome</summary>
                                            <form method="POST" action="{{ route('staff.viewings.log', $viewing) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="in" aria-label="Outcome" required>
                                                    <option value="">Outcome</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="no_show">No show</option>
                                                    <option value="declined">Declined</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                                <select name="feedback_category_id" class="in" aria-label="Main feedback">
                                                    <option value="">Main feedback</option>
                                                    @foreach ($categories as $id => $label)
                                                        <option value="{{ $id }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="staff_note" class="in" placeholder="What the client said" aria-label="Staff note" maxlength="1000">
                                                <button type="submit" class="btn btn-n">Save outcome</button>
                                            </form>
                                        </details>
                                    @endif
                                @else
                                    {{ $viewing->feedbackCategory?->label ?? '—' }}
                                    <small>{{ $viewing->staff_note }} @if ($viewing->loggedBy) · logged by {{ $viewing->loggedBy->name }} @endif</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">Nothing here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $viewings->links('pagination::bootstrap-4') }}
@endsection
