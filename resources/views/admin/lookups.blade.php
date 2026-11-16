{{--
    Master data. Receives $lists: type => [heading, column, items], and $branches [id => name].
    Items are switched off, never deleted, so old records still make sense.
--}}
@extends('layouts.app')

@section('title', 'Master data')

@section('content')
    <div class="ph">
        <div>
            <span class="crumb">System</span>
            <h1 class="t">Master data</h1>
            <p class="sub">The lists other screens choose from. Switching an item off hides it from new records only.</p>
        </div>
    </div>

    @include('partials.errors')

    <div class="split">
        @foreach ($lists as $type => $list)
            <section class="card narrow">
                <h2 class="h">{{ $list['heading'] }}</h2>

                @foreach ($list['items'] as $item)
                    <form method="POST" action="{{ route('admin.lookups.toggle', [$type, $item->id]) }}" class="row">
                        @csrf
                        @method('PATCH')
                        <span @class(['muted' => ! $item->is_active])>{{ $item->{$list['column']} }}</span>
                        <button type="submit" class="act">{{ $item->is_active ? 'Switch off' : 'Switch on' }}</button>
                    </form>
                @endforeach

                <form method="POST" action="{{ route('admin.lookups.store', $type) }}" class="sep">
                    @csrf
                    <input name="{{ $list['column'] }}" class="in" placeholder="New name" aria-label="New name" required maxlength="60">
                    @if ($type === 'districts')
                        <select name="branch_id" class="in" aria-label="Branch" required>
                            <option value="">Handled by which branch?</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'lease-terms')
                        <input type="number" name="months" class="in" min="1" max="120" placeholder="Months" aria-label="Months" required>
                    @elseif ($type === 'feedback-categories')
                        <label class="chk"><input type="checkbox" name="is_rejection" value="1" checked> Counts as a reason for not renting</label>
                    @endif
                    <button type="submit" class="btn btn-n">Add</button>
                </form>
            </section>
        @endforeach
    </div>
@endsection
