{{-- Error banner for pages whose small forms sit inside table rows, where there's no room under each field. --}}
@if ($errors->any())
    <div class="al al-err" role="alert">
        <span>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </span>
    </div>
@endif
