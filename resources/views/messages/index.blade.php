{{-- Conversations. Owners and clients see their own; staff see every conversation in their branch. Receives $threads. --}}
@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">{{ auth()->user()->role->name }}</span>
            <h1 class="t">Messages</h1>
            @unless (auth()->user()->isStaff())
                <p class="sub">Start a conversation from a property's page. Branch staff can see and answer every message.</p>
            @endunless
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Subject</th>
                        <th scope="col">Property</th>
                        <th scope="col">Started by</th>
                        <th scope="col">Messages</th>
                        <th scope="col">Last message</th>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($threads as $thread)
                        <tr>
                            <td><b>{{ $thread->subject }}</b></td>
                            <td class="mono">{{ $thread->property?->reference ?? '—' }}</td>
                            <td>{{ $thread->createdBy->name }}</td>
                            <td>{{ $thread->messages_count }}</td>
                            <td class="muted">{{ $thread->updated_at->diffForHumans() }}</td>
                            <td class="r"><a href="{{ route('messages.show', $thread) }}" class="act">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No conversations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $threads->links('pagination::bootstrap-4') }}
@endsection
