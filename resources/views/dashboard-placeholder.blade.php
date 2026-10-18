<h1>{{ $portal }} dashboard (placeholder)</h1>
<p>Signed in as {{ auth()->user()->name }}</p>
<form method="POST" action="{{ route('logout') }}">@csrf <button>Log out</button></form>
