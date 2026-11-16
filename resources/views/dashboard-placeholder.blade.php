{{--
    Placeholder dashboard for all five portals until each gets a real one. Handoff doc section 3.
    Receives $portal (display name). Staff roles get the side-menu layout; owners and clients the top-menu one.
    session('status') is shown by the layout.
--}}
@extends(auth()->user()->staffProfile ? 'layouts.portal' : 'layouts.portal-top')

@section('title', $portal)

@section('content')
    @php($user = auth()->user())

    <div class="ph">
        <div>
            <span class="crumb">{{ $portal }}</span>
            <h1 class="t">Welcome, {{ $user->name }}</h1>
        </div>
    </div>

    <div class="split">
        <section class="card narrow">
            <div class="row">
                <h2 class="h">Email</h2>
                @if ($user->hasVerifiedEmail())
                    <span class="b b-ok">Email verified ✓</span>
                @else
                    <span class="b b-warn">Not verified</span>
                @endif
            </div>
            <p class="hint">{{ $user->email }}</p>

            @unless ($user->hasVerifiedEmail())
                <p class="hint">Verify your email to use restricted features.</p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-s">Resend verification email</button>
                </form>
            @endunless
        </section>

        <section class="card narrow">
            <div class="row">
                <h2 class="h">Phone</h2>
                @if ($user->hasVerifiedPhone())
                    <span class="b b-ok">Phone verified ✓</span>
                @else
                    <span class="b b-warn">Not verified</span>
                @endif
            </div>
            <p class="hint mono">{{ $user->phone ?? 'Not set' }}</p>

            @unless ($user->hasVerifiedPhone())
                <a href="{{ route('phone.verification.show') }}" class="btn btn-s">Verify my phone</a>
            @endunless
        </section>
    </div>
@endsection
