{{--
    Log out must be a POST form (never a plain link), with @csrf. See the handoff doc, "Things every page shares".
--}}
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-n">Log out</button>
</form>
