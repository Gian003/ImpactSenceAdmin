<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImpactSense — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/investigation/layout.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    {{-- SIDEBAR --}}
    <aside class="sidebar d-flex flex-column">

        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom border-white border-opacity-10">
            <img src="{{ asset('images/pnp-urdaneta.png') }}" alt="PNP Urdaneta"
                 width="50" height="50" style="object-fit:contain; flex-shrink:0;">
            <div class="text-white fw-bold lh-sm" style="font-size:.88rem; letter-spacing:.05em;">
                PNP<br>URDANETA
            </div>
        </div>

        <nav class="flex-grow-1 py-3">

            {{-- Dashboard --}}
            <a href="{{ route('investigation.dashboard') }}"
               class="nav-link {{ request()->routeIs('investigation.dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            {{-- Incidents --}}
            <a href="{{ route('investigation.incidents.index') }}"
               class="nav-link {{ request()->routeIs('investigation.incidents*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                Incidents
            </a>

            {{-- Incident Records --}}
            <a href="{{ route('investigation.incident-records.index') }}"
               class="nav-link {{ request()->routeIs('investigation.incident-records*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Incident Record
            </a>

            {{-- Incident Report --}}
            <a href="{{ route('investigation.incident-report.index') }}"
               class="nav-link {{ request()->routeIs('investigation.incident-report*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Incident Report
            </a>

            {{-- Registered Helmet --}}
            <a href="{{ route('investigation.helmet.index') }}"
               class="nav-link {{ request()->routeIs('investigation.helmet*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/>
                    <path d="M3 12v2a9 9 0 0 0 18 0v-2"/>
                    <path d="M9 21h6"/>
                </svg>
                Registered Helmet
            </a>

        </nav>

        <div class="px-3 pt-3 pb-2 border-top border-white border-opacity-10">

            {{-- User info row --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="{{ asset('images/pnp-investigation.png') }}" alt="PNP Investigation"
                     width="38" height="38"
                     style="object-fit:contain; flex-shrink:0; border-radius:50%;">
                <div class="text-white lh-sm" style="font-size:.78rem; overflow:hidden;">
                    <div class="fw-bold" style="font-size:.82rem; letter-spacing:.02em;">Investigation PCO</div>
                    <div style="opacity:.65; font-size:.7rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ Auth::guard('investigation')->user()->full_name ?? 'Officer' }}
                    </div>
                </div>
            </div>

            {{-- Logout button --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="btn btn-sm w-100 d-flex align-items-center justify-content-center gap-1"
                        style="background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.2);
                               font-size:.76rem; font-weight:600; border-radius:8px; padding:.4rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Log Out
                </button>
            </form>

        </div>

    </aside>

    {{-- MAIN --}}
    <main class="flex-grow-1 overflow-hidden">

        <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <h1 class="fw-bold mb-0" style="font-size:1.7rem;">@yield('title', 'Dashboard')</h1>

            <div class="dropdown">
                <button class="btn rounded-circle p-2 border-0" style="background:#1b3d52;"
                        id="bellBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                         stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:230px;">
                    @stack('notifications')
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger" style="font-size:.82rem;">
                                Log out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <div class="px-4 pb-5">
            @yield('content')
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
