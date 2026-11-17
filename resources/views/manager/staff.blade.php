{{-- A manager's own branch staff and their salaries. Receives $staff (each with the current salary loaded, if any). --}}
@extends('layouts.app')

@section('title', 'Branch staff')

@section('content')
    <div class="ph">
        <div>
            <h1 class="t">Branch staff</h1>
            <p class="sub">Salaries are visible to you and the administrator only. Accounts are created by the administrator.</p>
        </div>
    </div>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Staff member</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Current salary</th>
                        <th scope="col">Record a new salary</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        @php($salary = $member->staffProfile->compensations->first())
                        <tr>
                            <td><b>{{ $member->name }}</b><small><span class="mono">{{ $member->staffProfile->staff_code }}</span> · {{ $member->staffProfile->job_title }}</small></td>
                            <td>{{ $member->role->name }}</td>
                            <td><span @class(['b', 'b-ok' => $member->is_active, 'b-off' => ! $member->is_active])>{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @if ($salary)
                                    MVR {{ number_format($salary->monthly_salary, 2) }}<small>since {{ $salary->effective_from->format('j M Y') }}</small>
                                @else
                                    Not recorded
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('manager.staff.salary', $member) }}" class="inline">
                                    @csrf
                                    <input type="number" name="monthly_salary" class="in" min="0" step="0.01" placeholder="MVR a month" aria-label="Monthly salary" required>
                                    <input type="date" name="effective_from" class="in" value="{{ now()->format('Y-m-d') }}" aria-label="From" required>
                                    <button type="submit" class="btn btn-n">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
