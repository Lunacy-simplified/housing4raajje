{{-- In-app notifications, newest first. Opening the page marks them all as read. --}}
@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Notifications</h1>
        </div>
    </div>

    <section class="card flush">
        <div class="sx">
        <table class="tbl">
            <tbody>
                @forelse ($notifications as $notification)
                    <tr>
                        <td>
                            <b>{{ $notification->data['title'] }}</b>
                            @if (! $notification->read_at)
                                <span class="b b-warn">New</span>
                            @endif
                            <small>{{ $notification->data['body'] }}</small>
                        </td>
                        <td class="muted">{{ $notification->created_at->diffForHumans() }}</td>
                        <td class="r">
                            @if ($notification->data['url'])
                                <a href="{{ $notification->data['url'] }}" class="act">Open</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty">Nothing yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
@endsection
