{{--
    Success message after a form, e.g. "Your password has been reset. You can sign in now."
    Controllers set it with ->with('status', '...'); it only shows when there is one.
--}}
@if (session('status'))
    <div class="al al-ok" role="status"><span>{{ session('status') }}</span></div>
@endif
