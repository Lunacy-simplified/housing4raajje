{{-- Audit log: who did what, newest first. Receives $entries (30 per page) and $action (the current filter). --}}
@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Audit log</h1>
            <p class="sub">Entries are never edited or deleted.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="flt">
        <x-field name="action" label="Action starts with" :value="$action" placeholder="lease, payment, property..." />
        <div class="acts">
            <button type="submit" class="btn btn-s">Filter</button>
            <a href="{{ route('admin.audit.index') }}" class="act">Clear</a>
        </div>
    </form>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">Who</th>
                        <th scope="col">Action</th>
                        <th scope="col">Record</th>
                        <th scope="col">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="muted">{{ $entry->created_at->format('j M Y, g:i a') }}</td>
                            <td>{{ $entry->user?->name ?? 'System' }}<small>{{ $entry->ip_address }}</small></td>
                            <td class="mono">{{ $entry->action }}</td>
                            <td class="mono">{{ $entry->table_name }} #{{ $entry->record_id }}</td>
                            <td class="mono">{{ $entry->new_values ? json_encode($entry->new_values) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No entries.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $entries->links('pagination::bootstrap-4') }}
@endsection
