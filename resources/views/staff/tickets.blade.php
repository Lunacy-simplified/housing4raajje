{{-- Support tickets for the branch, open ones first. Receives $tickets. --}}
@extends('layouts.app')

@section('title', 'Support tickets')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Front desk</span>
            <h1 class="t">Support tickets</h1>
            <p class="sub">Tickets are created by routing an inquiry to a specialist.</p>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Ticket</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Assigned to</th>
                        <th scope="col">Opened</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td><b>{{ $ticket->subject }}</b><small>{{ $ticket->description }}</small></td>
                            <td>{{ $ticket->inquiry?->contact_name ?? '—' }}<small>{{ $ticket->inquiry?->contact_phone }}</small></td>
                            <td>{{ $ticket->assignedTo?->name ?? 'Nobody' }}</td>
                            <td class="muted">{{ $ticket->created_at->diffForHumans() }} by {{ $ticket->openedBy->name }}</td>
                            <td>
                                <form method="POST" action="{{ route('staff.tickets.update', $ticket) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="in" aria-label="Status">
                                        @foreach (App\Http\Controllers\Staff\TicketController::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($ticket->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-n">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No tickets.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $tickets->links('pagination::bootstrap-4') }}
@endsection
