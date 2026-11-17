{{--
    Notifications and the signed-in user's name (their profile).
    Shown in the top bar on wide screens (class "acct") and at the end of the menu on small ones (class "sm").
    "Verify phone" is only there until the phone is verified.
--}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
@endphp
<a href="{{ route('notifications.index') }}" @class([$class, 'on' => request()->routeIs('notifications.index')])>{{ $unread ? "Notifications ($unread)" : 'Notifications' }}</a>
<a href="{{ route('profile.edit') }}" @class([$class, 'on' => request()->routeIs('profile.edit')])>{{ $class === 'sm' ? 'Profile' : $user->name }}</a>
@unless ($user->hasVerifiedPhone())
    <a href="{{ route('phone.verification.show') }}" @class([$class, 'on' => request()->routeIs('phone.verification.*')])>Verify phone</a>
@endunless
