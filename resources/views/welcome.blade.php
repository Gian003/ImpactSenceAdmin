<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ImpactSense') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/welcome.css') }}" rel="stylesheet">
</head>
<body>

<div class="hero-card">

    {{-- TOP NAV --}}
    <div class="top-nav">
        <div class="nav-brand">
            <img src="{{ asset('images/pnp_logo.png') }}" width="28" height="28"
                 style="object-fit:contain;" alt="PNP" onerror="this.style.display='none'">
            IMPACTSENSE
        </div>
        @if(Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary-custom">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary-custom">Log in</a>
            @endauth
        @endif
    </div>

    {{-- HERO BODY --}}
    <div class="hero-body">

        {{-- Left: Branding --}}
        <div class="hero-left">
            <img src="{{ asset('images/pnp_logo.png') }}" alt="PNP Urdaneta"
                 onerror="this.style.display='none'">
            <div class="brand-title">IMPACTSENSE</div>
            <p>Real-time Accident Monitoring<br>and Response System</p>
            <p class="pnp-label">PNP Urdaneta City, Pangasinan</p>
            <a href="{{ route('login') }}" class="btn-get-started">Get Started &rarr;</a>
        </div>

        <div class="divider-v"></div>

        {{-- Right: Features --}}
        <div class="hero-right">

            <div class="feature-item">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                         stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="feature-title">Location Tracking</div>
                    <div class="feature-desc">Real-time GPS tracking of registered motorcycle riders across Urdaneta City.</div>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                         stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         viewBox="0 0 24 24">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <div class="feature-title">Accident Detection</div>
                    <div class="feature-desc">Automatic crash detection via the ImpactSense device with instant alert dispatch.</div>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                         stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="feature-title">Patroller Dispatch</div>
                    <div class="feature-desc">Coordinate PNP patrollers in real-time for faster emergency response.</div>
                </div>
            </div>

        </div>
    </div>

    {{-- STATS --}}
    <div class="stats-row">
        <div class="stat-badge">
            <div class="stat-num">{{ $statRiders }}</div>
            <div class="stat-lbl">Registered Riders</div>
        </div>
        <div class="stat-badge">
            <div class="stat-num">{{ $statAccidents }}</div>
            <div class="stat-lbl">Accidents Detected</div>
        </div>
        <div class="stat-badge">
            <div class="stat-num">{{ $statDevices }}</div>
            <div class="stat-lbl">Active Devices</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="hero-footer">
        &copy; {{ date('Y') }} ImpactSense &mdash; PNP Urdaneta City, Pangasinan. All rights reserved.
    </div>

</div>

</body>
</html>
