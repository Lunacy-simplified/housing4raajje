{{-- Maintenance requests for an owner or client. Receives $properties [id => label] they may report on, and $requests. --}}
@extends('layouts.app')

@section('title', 'Maintenance')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Maintenance</h1>
        </div>
    </div>

    <div class="split">
        <section class="card flush wide">
            <div class="sx">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">Property</th>
                            <th scope="col">Problem</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col">Reported</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td class="mono">{{ $request->property->reference }}</td>
                                <td><b>{{ ucfirst($request->category) }}</b><small>{{ $request->description }}</small></td>
                                <td><x-pill :status="$request->priority" /></td>
                                <td><x-pill :status="$request->status" /></td>
                                <td class="muted">{{ $request->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No maintenance requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($properties->isNotEmpty())
            <form method="POST" action="{{ route('maintenance.store') }}" class="card narrow">
                @csrf
                <h2 class="h">Report a problem</h2>
                <x-select name="property_id" label="Property" :options="$properties" required />
                <x-select name="category" label="Type of problem" placeholder="Choose" required
                    :options="['plumbing' => 'Plumbing', 'electrical' => 'Electrical', 'appliance' => 'Appliance', 'structural' => 'Structural', 'other' => 'Other']" />
                <x-select name="priority" label="How urgent" value="medium" required
                    :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']" />
                <x-textarea name="description" label="What is wrong?" maxlength="2000" required />
                <button type="submit" class="btn btn-p">Send request</button>
            </form>
        @endif
    </div>
@endsection
