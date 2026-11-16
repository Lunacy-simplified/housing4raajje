{{-- One menu link, highlighted when its route (or the $match pattern) is the current page. --}}
<a href="{{ route($route) }}" @class(['on' => request()->routeIs($match ?? $route)])>{{ $label }}</a>
