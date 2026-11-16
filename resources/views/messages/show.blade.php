{{-- One conversation. Receives $thread with messages and their senders. Your own messages sit on the right. --}}
@extends('layouts.app')

@section('title', $thread->subject)

@section('content')
    <div class="ph">
        <div>
            <span class="crumb"><a href="{{ route('messages.index') }}">Messages</a></span>
            <h1 class="t">{{ $thread->subject }}</h1>
            @if ($thread->property)
                <p class="sub">About {{ $thread->property->house_name }} <span class="mono">{{ $thread->property->reference }}</span></p>
            @endif
        </div>
    </div>

    <section class="card limit">
        <div class="msgs">
            @foreach ($thread->messages as $message)
                <div @class(['msg', 'me' => $message->sender_user_id === auth()->id()])>
                    {{ $message->body }}
                    <small>{{ $message->sender->name }} ({{ $message->sender->role->name }}) · {{ $message->created_at->format('j M, g:i a') }}</small>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('messages.reply', $thread) }}" class="sep">
            @csrf
            <x-textarea name="body" label="Reply" maxlength="2000" required />
            <button type="submit" class="btn btn-p">Send</button>
        </form>
    </section>
@endsection
