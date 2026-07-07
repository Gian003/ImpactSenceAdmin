<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImpactSense — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/toc/layout.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    {{-- SIDEBAR --}}
    <aside class="sidebar d-flex flex-column">

        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom border-white border-opacity-10">
            <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="PNP Urdaneta"
                 width="50" height="50" style="object-fit:contain; flex-shrink:0;">
            <div class="text-white fw-bold lh-sm" style="font-size:.88rem; letter-spacing:.05em;">
                PNP<br>URDANETA
            </div>
        </div>

        <nav class="flex-grow-1 py-3">
            <a href="{{ route('toc.dashboard') }}"
               class="nav-link {{ request()->routeIs('toc.dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('toc.location.tracking') }}"
               class="nav-link {{ request()->routeIs('toc.location*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                </svg>
                Location Tracking
            </a>

            <a href="{{ route('toc.helmet.index') }}"
               class="nav-link {{ request()->routeIs('toc.helmet*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/>
                    <path d="M3 12v2a9 9 0 0 0 18 0v-2"/>
                    <path d="M9 21h6"/>
                </svg>
                Registered Helmet
            </a>

            <a href="{{ route('toc.patrollers.index') }}"
               class="nav-link {{ request()->routeIs('toc.patrollers*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Patrollers Unit
            </a>

            <a href="{{ route('toc.patrol-registrations.index') }}"
               class="nav-link {{ request()->routeIs('toc.patrol-registrations*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                Patrol Registrations
                @php $pendingCount = \App\Models\PatrolRegistration::where('status','pending')->count(); @endphp
                @if($pendingCount)
                <span id="reg-badge"
                      class="badge rounded-pill ms-auto"
                      style="background:#e53e3e; font-size:.65rem; padding:.2rem .5rem;">
                    {{ $pendingCount }}
                </span>
                @else
                <span id="reg-badge" style="display:none;"
                      class="badge rounded-pill ms-auto"
                      style="background:#e53e3e; font-size:.65rem; padding:.2rem .5rem;">0</span>
                @endif
            </a>
        </nav>

        <div class="px-3 pt-3 pb-2 border-top border-white border-opacity-10">

            {{-- User info row --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="PNP"
                     width="38" height="38"
                     style="object-fit:contain; flex-shrink:0; border-radius:50%;">
                <div class="text-white lh-sm" style="font-size:.78rem; overflow:hidden;">
                    <div class="fw-bold" style="font-size:.82rem; letter-spacing:.04em;">PNP TCO</div>
                    <div style="opacity:.65; font-size:.7rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ Auth::guard('toc')->user()->full_name ?? 'Admin' }}
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

            <div class="d-flex align-items-center gap-3">
                {{-- Bell --}}
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
                        <li id="notificationDivider">
                            <hr class="dropdown-divider my-1">
                        </li>
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
        </div>

        <div class="px-4 pb-5">
            @yield('content')
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@if(config('broadcasting.connections.pusher.key'))
{{-- Pusher real-time listener — only loads when PUSHER_APP_KEY is configured --}}
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script>
(function () {
    const key     = @json(config('broadcasting.connections.pusher.key'));
    const cluster = @json(config('broadcasting.connections.pusher.options.cluster'));
    if (!key) return;

    const pusher  = new Pusher(key, { cluster });
    const channel = pusher.subscribe('incidents');

    // Exposed so other pages (e.g. location tracking) can subscribe to their
    // own channels on this same connection instead of opening a second one.
    window.pusherClient = pusher;

    // New incident arrives — add a row to the Recent Incidents table
    channel.bind('incident.reported', function (data) {
        const tbody = document.getElementById('incidents-tbody');
        if (!tbody) return;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${data.rider?.full_name ?? 'N/A'}</td>
            <td>${data.address ?? 'N/A'}</td>
            <td>${data.rider?.phone_number ?? 'N/A'}</td>
            <td>N/A</td>
            <td>N/A</td>
            <td></td>`;
        tbody.prepend(tr);

        // Flash the bell notification
        const bell = document.getElementById('bellBtn');
        if (bell) bell.classList.add('text-warning');
    });

    // Status changed (e.g. a patrol pressed "I'm On My Way" / "Mark as
    // Arrived" in the mobile app) — flash the bell and drop a real entry into
    // the notification dropdown. Previously this only tried to update a
    // .status-badge element that doesn't exist anywhere in the TOC views, so
    // patrol status updates produced no visible feedback at all.
    channel.bind('incident.status_updated', function (data) {
        const bell = document.getElementById('bellBtn');
        if (bell) bell.classList.add('text-warning');

        const divider = document.getElementById('notificationDivider');
        if (!divider) return;

        const patrolName = data.patrol_unit?.full_name;
        const label = patrolName
            ? `${patrolName} marked incident #${data.id} as ${data.status}`
            : `Incident #${data.id} status changed to ${data.status}`;

        const li = document.createElement('li');
        const span = document.createElement('span');
        span.className = 'dropdown-item';
        span.style.fontSize = '.82rem';
        span.textContent = label;
        li.appendChild(span);
        divider.parentNode.insertBefore(li, divider);
    });

    // New patrol registration submitted — bump the sidebar pending-count badge live
    const registrationsChannel = pusher.subscribe('patrol-registrations');
    registrationsChannel.bind('registration.submitted', function () {
        const regBadge = document.getElementById('reg-badge');
        if (!regBadge) return;
        const count = (parseInt(regBadge.textContent, 10) || 0) + 1;
        regBadge.textContent = count;
        regBadge.style.display = '';
    });
})();
</script>
@endif

@stack('scripts')
</body>
</html>
