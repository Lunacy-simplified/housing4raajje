{{--
    Shared <head> contents for every layout.
    Pages set their tab title with @section('title', 'Sign in'); " · H4R" is added here.
    No web fonts: the system font is used (SF Pro on Apple devices). See docs/design.md.
    The "js" class tells the stylesheet that js/h4r.js is running (phone menu and stacked tables).
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
<title>@yield('title', 'Housing 4 Raajje') · H4R</title>
<link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('css/h4r.css') }}">
<script>document.documentElement.classList.add('js')</script>
<script src="{{ asset('js/h4r.js') }}" defer></script>
