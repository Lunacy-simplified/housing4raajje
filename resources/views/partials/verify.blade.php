{{-- Email and phone verification reminders, shown on dashboards until both are verified. --}}
@php($user = auth()->user())
@unless ($user->hasVerifiedEmail() && $user->hasVerifiedPhone())
    <div class="split">
        @unless ($user->hasVerifiedEmail())
            <section class="card narrow">
                <div class="row">
                    <h2 class="h">Email</h2>
                    <span class="b b-warn">Not verified</span>
                </div>
                <p class="hint">{{ $user->email }}. Verify your email to use restricted features.</p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-s">Resend verification email</button>
                </form>
            </section>
        @endunless
        @unless ($user->hasVerifiedPhone())
            <section class="card narrow">
                <div class="row">
                    <h2 class="h">Phone</h2>
                    <span class="b b-warn">Not verified</span>
                </div>
                <p class="hint mono">{{ $user->phone ?? 'Not set' }}</p>
                <a href="{{ route('phone.verification.show') }}" class="btn btn-s">Verify my phone</a>
            </section>
        @endunless
    </div>
@endunless
