{{-- Maintenance requests for the branch, open ones first. Receives $requests. --}}
@extends('layouts.app')

@section('title', 'Maintenance')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Maintenance</h1>
        </div>
    </div>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Property</th>
                        <th scope="col">Problem</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Reported by</th>
                        <th scope="col">Handled by</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td><b>{{ $request->property->house_name }}</b><small class="mono">{{ $request->property->reference }}</small></td>
                            <td><b>{{ ucfirst($request->category) }}</b><small>{{ $request->description }}</small></td>
                            <td><x-pill :status="$request->priority" /></td>
                            <td>{{ $request->raisedBy->name }}<small>{{ $request->raisedBy->phone }} · {{ $request->created_at->diffForHumans() }}</small></td>
                            <td>{{ $request->assignedTo?->name ?? 'Nobody yet' }}</td>
                            <td>
                                <form method="POST" action="{{ route('staff.maintenance.update', $request) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="in" aria-label="Status">
                                        @foreach (App\Http\Controllers\Staff\MaintenanceController::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($request->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-n">Update</button>
                                </form>
                                @include('partials.escalate', ['type' => 'maintenance', 'id' => $request->id])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No maintenance requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $requests->links('pagination::bootstrap-4') }}
@endsection
