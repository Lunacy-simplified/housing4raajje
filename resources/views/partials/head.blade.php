{{--
    Shared <head> contents for every layout.
    Pages set their tab title with @section('title', 'Sign in'); " · H4R" is added here.
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Housing 4 Raajje') · H4R</title>
<link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Literata:opsz,wght@7..72,400;7..72,500;7..72,600;7..72,700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/h4r.css') }}">
