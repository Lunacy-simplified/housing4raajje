{{-- Issues staff escalated to the branch manager, open ones first. Receives $escalations. --}}
@extends('layouts.app')

@section('title', 'Escalations')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Branch</span>
            <h1 class="t">Escalations</h1>
        </div>
    </div>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">About</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Raised by</th>
                        <th scope="col">Status</th>
                        <th scope="col">Resolution</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($escalations as $escalation)
                        <tr>
                            <td>
                                @if ($escalation->lease)
                                    <a href="{{ route('staff.leases.show', $escalation->lease) }}">Lease <span class="mono">{{ $escalation->lease->reference }}</span></a>
                                @elseif ($escalation->application)
                                    Application <span class="mono">{{ $escalation->application->reference }}</span>
                                @else
                                    Maintenance at <span class="mono">{{ $escalation->maintenanceRequest->property->reference }}</span>
                                @endif
                            </td>
                            <td>{{ $escalation->reason }}</td>
                            <td>{{ $escalation->raisedBy->name }}<small>{{ $escalation->created_at->diffForHumans() }}</small></td>
                            <td><x-pill :status="$escalation->status" /></td>
                            <td>
                                @if ($escalation->status === 'open')
                                    <form method="POST" action="{{ route('manager.escalations.resolve', $escalation) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input name="resolution" class="in" placeholder="What was decided" aria-label="Resolution" required maxlength="1000">
                                        <button type="submit" class="btn btn-s">Resolve</button>
                                    </form>
                                @else
                                    {{ $escalation->resolution }}<small>{{ $escalation->resolvedBy->name }}, {{ $escalation->resolved_at->format('j M Y') }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">Nothing has been escalated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $escalations->links('pagination::bootstrap-4') }}
@endsection
