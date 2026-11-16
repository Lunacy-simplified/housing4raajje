{{-- Front desk inquiry log. Receives $inquiries (open first) and $specialists [id => name] in the same branch. --}}
@extends('layouts.app')

@section('title', 'Inquiries')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Front desk</span>
            <h1 class="t">Inquiries</h1>
        </div>
    </div>

    <div class="split">
        <section class="card flush wide">
            <div class="sx">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th scope="col">Inquiry</th>
                            <th scope="col">From</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inquiries as $inquiry)
                            <tr>
                                <td><b>{{ $inquiry->subject }}</b><small>{{ $inquiry->details }}</small></td>
                                <td>
                                    {{ $inquiry->contact_name }}
                                    <small>{{ $inquiry->contact_phone }} · {{ str_replace('_', ' ', $inquiry->channel) }} · {{ $inquiry->created_at->diffForHumans() }}</small>
                                </td>
                                <td><x-pill :status="$inquiry->status" /></td>
                                <td>
                                    @if ($inquiry->status === 'open')
                                        <form method="POST" action="{{ route('staff.inquiries.route', $inquiry) }}" class="inline">
                                            @csrf
                                            <select name="assigned_to_user_id" class="in" aria-label="Specialist" required>
                                                <option value="">Send to a specialist</option>
                                                @foreach ($specialists as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-s">Route</button>
                                        </form>
                                        <form method="POST" action="{{ route('staff.inquiries.close', $inquiry) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="act">Close without a ticket</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">No inquiries logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <form method="POST" action="{{ route('staff.inquiries.store') }}" class="card narrow">
            @csrf
            <h2 class="h">Log an inquiry</h2>
            <x-select name="channel" label="How they got in touch" :options="['walk_in' => 'Walk-in', 'phone' => 'Phone', 'email' => 'Email']" required />
            <x-field name="contact_name" label="Name" maxlength="120" required />
            <x-field name="contact_phone" label="Phone (optional)" maxlength="20" />
            <x-field name="subject" label="Subject" maxlength="150" required />
            <x-textarea name="details" label="Notes (optional)" maxlength="2000" />
            <button type="submit" class="btn btn-p">Log inquiry</button>
        </form>
    </div>

    {{ $inquiries->links('pagination::bootstrap-4') }}
@endsection
