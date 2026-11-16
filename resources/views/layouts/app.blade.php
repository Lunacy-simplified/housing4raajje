{{--
    Layout for pages shared by every role (profile, messages, leases...).
    Staff get the sidebar layout; owners and clients get the top-menu layout.
--}}
@extends(auth()->user()->isStaff() ? 'layouts.portal' : 'layouts.portal-top')
