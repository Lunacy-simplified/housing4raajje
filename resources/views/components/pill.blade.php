{{-- Status label: always words, colour is a second cue. <x-pill :status="$lease->status" /> --}}
@props(['status'])
@php
    $tone = match ($status) {
        'approved', 'active', 'available', 'paid', 'completed', 'resolved', 'verified' => 'b-ok',
        'pending', 'submitted', 'requested', 'scheduled', 'pending_signature', 'changes_requested', 'open',
        'triaged', 'assigned', 'in_progress', 'routed', 'under_maintenance', 'under_review', 'drafting' => 'b-warn',
        'rented' => 'b-info',
        'rejected', 'declined', 'terminated', 'no_show', 'urgent', 'high' => 'b-err',
        default => 'b-off',
    };
@endphp
<span class="b {{ $tone }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
