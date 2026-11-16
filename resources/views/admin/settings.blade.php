{{-- Global settings. Receives $settings. --}}
@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">System</span>
            <h1 class="t">Settings</h1>
        </div>
    </div>

    @include('partials.errors')

    <section class="card flush">
        <div class="sx">
            <table class="tbl">
                <thead>
                    <tr>
                        <th scope="col">Setting</th>
                        <th scope="col">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($settings as $setting)
                        <tr>
                            <td><b class="mono">{{ $setting->key }}</b><small>{{ $setting->description }}</small></td>
                            <td>
                                <form method="POST" action="{{ route('admin.settings.update', $setting) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="value" class="in" value="{{ $setting->value }}" min="0" max="365" step="0.01" aria-label="{{ $setting->key }}" required>
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
