<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ImpactSense') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/welcome.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center p-3 min-vh-100">

    <div class="hero-card w-100 p-4 p-md-5" style="max-width:860px;">

        {{-- TOP NAV --}}
        @if (Route::has('login'))
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('images/pnp-logo.png') }}" width="32" height="32"
                     style="object-fit:contain;" alt="PNP" onerror="this.style.display='none'">
                <span class="fw-bold" style="color:#1a3a4f; font-size:.85rem; letter-spacing:.05em;">
                    IMPACTSENSE
                </span>
            </div>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary-custom">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary-custom">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-custom">Register</a>
                    @endif
                @endauth
            </div>
        </div>
        @endif

        {{-- HERO --}}
        <div class="row g-4 align-items-center mb-4">

            {{-- Left: Branding --}}
            <div class="col-md-5 text-center text-md-start">
                <img src="{{ asset('images/pnp-logo.png') }}" alt="PNP Urdaneta"
                     width="130" height="130" style="object-fit:contain;"
                     class="mb-3" onerror="this.style.display='none'">
                <div class="brand-title mb-2">IMPACTSENSE</div>
                <p class="mb-1 lh-sm" style="color:#2c5f7a; font-size:.95rem;">
                    Real-time Accident Monitoring<br>and Response System
                </p>
                <p class="fw-bold mb-4" style="color:#1a3a4f; font-size:.88rem;">
                    PNP Urdaneta City, Pangasinan
                </p>
                <a href="{{ route('login') }}" class="btn btn-primary-custom px-4 py-2">
                    Get Started &rarr;
                </a>
            </div>

            {{-- Divider --}}
            <div class="col-auto d-none d-md-flex px-0">
                <div class="divider-v"></div>
            </div>

            {{-- Right: Features --}}
            <div class="col-md">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                             stroke="#1a3a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             viewBox="0 0 24 24">
                            <circle cx="12" cy="10" r="3"/>
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.88rem; color:#1a3a4f;">Location Tracking</div>
                        <div style="font-size:.78rem; color:#2c5f7a;">
                            Real-time GPS tracking of registered motorcycle riders across Urdaneta City.
                        </div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                             stroke="#1a3a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             viewBox="0 0 24 24">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.88rem; color:#1a3a4f;">Accident Detection</div>
                        <div style="font-size:.78rem; color:#2c5f7a;">
                            Automatic crash detection via smart helmets with instant alert dispatch.
                        </div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                             stroke="#1a3a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.88rem; color:#1a3a4f;">Patroller Dispatch</div>
                        <div style="font-size:.78rem; color:#2c5f7a;">
                            Coordinate PNP patrollers in real-time for faster emergency response.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <hr style="border-color:#8fb3c8;">

        {{-- STATS --}}
        <div class="row g-3">
            <div class="col-4">
                <div class="stat-badge">
                    <div class="stat-num">59</div>
                    <div class="stat-lbl">Registered Riders</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-badge">
                    <div class="stat-num">24</div>
                    <div class="stat-lbl">Accidents Detected</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-badge">
                    <div class="stat-num">30</div>
                    <div class="stat-lbl">Active Devices</div>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <p class="text-center mt-4 mb-0" style="font-size:.75rem; color:#4a7a96;">
            &copy; {{ date('Y') }} ImpactSense &mdash; PNP Urdaneta City, Pangasinan. All rights reserved.
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
