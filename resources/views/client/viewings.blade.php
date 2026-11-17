{{-- A client's viewings. Receives $viewings. --}}
@extends('layouts.app')

@section('title', 'Viewings')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Viewings</h1>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Property</th>
                        <th scope="col">Requested</th>
                        <th scope="col">Booked for</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($viewings as $viewing)
                        <tr>
                            <td>
                                <a href="{{ route('client.properties.show', $viewing->property) }}"><b>{{ $viewing->property->house_name }}</b></a>
                                <small>{{ $viewing->property->district->name }}</small>
                            </td>
                            <td>{{ $viewing->requested_at->format('j M Y') }}</td>
                            <td>{{ $viewing->scheduled_for?->format('j M Y, g:i a') ?? '—' }}</td>
                            <td><x-pill :status="$viewing->status" /></td>
                            <td class="r">
                                @if (in_array($viewing->status, ['requested', 'scheduled'], true))
                                    <form method="POST" action="{{ route('client.viewings.cancel', $viewing) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="act">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No viewings yet. Request one from a property's page.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
