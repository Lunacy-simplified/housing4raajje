{{-- Branch report for the manager. Receives $branch, $stats [label => value] and $staff with their activity counts. --}}
@extends('layouts.app')

@section('title', 'Branch report')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">Branch {{ $branch->branch_no }}</span>
            <h1 class="t">Welcome, {{ auth()->user()->name }}</h1>
            <p class="sub">{{ $branch->name }} branch, {{ now()->format('F Y') }}</p>
        </div>
    </div>

    @include('partials.verify')

    <div class="stats">
        @foreach ($stats as $label => $value)
            <div class="stat"><span class="hint">{{ $label }}</span><b>{{ $value }}</b></div>
        @endforeach
    </div>

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Staff member</th>
                        <th scope="col">Role</th>
                        <th scope="col" class="r">Properties approved</th>
                        <th scope="col" class="r">Viewings logged</th>
                        <th scope="col" class="r">Leases executed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        <tr>
                            <td><b>{{ $member->name }}</b><small class="mono">{{ $member->staffProfile->staff_code }}</small></td>
                            <td>{{ $member->role->name }}</td>
                            <td class="r">{{ $member->verified_properties_count }}</td>
                            <td class="r">{{ $member->logged_viewings_count }}</td>
                            <td class="r">{{ $member->executed_leases_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
