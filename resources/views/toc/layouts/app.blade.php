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

            {{-- Grouped by how the work actually runs: what's watched live, who
                 staffs it, what it runs on, and what's reviewed afterwards.
                 Patrol Registrations sits high because it's the only nav item
                 that raises a count badge demanding action, and directly above
                 Personnel Roster because the roster page now links into it. --}}
            <div class="nav-section">Monitoring</div>

            {{-- The dispatcher's own incident list. The investigation section
                 has its own, behind a different guard and carrying case
                 material this desk has no need of. --}}
            <a href="{{ route('toc.incidents.index') }}"
               class="nav-link {{ request()->routeIs('toc.incidents.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                Incidents
            </a>

            <a href="{{ route('toc.activity.index') }}"
               class="nav-link {{ request()->routeIs('toc.activity.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                Desk Activity
            </a>

            <a href="{{ route('toc.location.tracking') }}" id="navLocationTracking"
               class="nav-link {{ request()->routeIs('toc.location*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                </svg>
                Location Tracking
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

            <div class="nav-section">Personnel</div>

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
                      style="background:#b91c1c; font-size:.65rem; padding:.2rem .5rem;">
                    {{ $pendingCount }}
                </span>
                @else
                <span id="reg-badge" style="display:none;"
                      class="badge rounded-pill ms-auto"
                      style="background:#b91c1c; font-size:.65rem; padding:.2rem .5rem;">0</span>
                @endif
            </a>

            <a href="{{ route('toc.personnel-roster.index') }}"
               class="nav-link {{ request()->routeIs('toc.personnel-roster*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M9 12h6"/>
                    <path d="M9 16h6"/>
                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                    <path d="M5 21h14a2 2 0 0 0 2-2V7l-5-5H7a2 2 0 0 0-2 2v3"/>
                    <path d="M3 15v4a2 2 0 0 0 2 2"/>
                </svg>
                Personnel Roster
            </a>

            <div class="nav-section">Devices &amp; Zones</div>

            <a href="{{ route('toc.devices.index') }}"
               class="nav-link {{ request()->routeIs('toc.devices*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/>
                    <path d="M3 12v2a9 9 0 0 0 18 0v-2"/>
                    <path d="M9 21h6"/>
                </svg>
                Device Management
            </a>

            <div class="nav-section">Records</div>

            <a href="{{ route('toc.analytics.index') }}"
               class="nav-link {{ request()->routeIs('toc.analytics*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                </svg>
                Accident Analytics
            </a>

            {{-- Call Recordings — the Twilio TTS alert calls placed to the TOC
                 hotline when a crash is detected. --}}
            <a href="{{ route('toc.call-recordings.index') }}"
               class="nav-link {{ request()->routeIs('toc.call-recordings*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
                Call Recordings
            </a>
        </nav>

        <div class="px-3 pt-3 pb-2 border-top border-white border-opacity-10">

            {{-- User info row --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="{{ asset('images/pnp_urdaneta_logo.png') }}" alt="PNP"
                     width="38" height="38"
                     style="object-fit:contain; flex-shrink:0; border-radius:50%;">
                <div class="text-white lh-sm" style="font-size:.78rem; overflow:hidden;">
                    <div class="fw-bold" style="font-size:.82rem; letter-spacing:.04em;">PNP TOC</div>
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
                @if(config('broadcasting.connections.pusher.key'))
                {{-- Incident sound toggle — defaults to on, since a silent
                     alert defeats the point on a safety system, but an
                     always-forced sound is how alerts get muted at the OS
                     level and ignored forever, so officers can turn it off
                     here instead. State persists per-browser via
                     localStorage (there's no server-side "this officer's
                     device" concept to store it against). --}}
                <div class="d-flex align-items-center gap-2" style="background:#7B1A2E; padding:8px 16px; border-radius:999px;">
                    <button type="button" class="btn p-0 border-0 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:22px; height:22px; background:transparent;"
                            id="soundToggleBtn" aria-pressed="false" aria-label="Mute incident alert sound">
                        <svg id="soundOnIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                             stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                        </svg>
                        <svg id="soundOffIcon" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                             stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <line x1="23" y1="9" x2="17" y2="15"></line>
                            <line x1="17" y1="9" x2="23" y2="15"></line>
                        </svg>
                    </button>
                    {{-- Releasing the slider plays a preview at the new level
                         (regardless of mute) so officers can hear what
                         they've picked instead of guessing until the next
                         real incident. --}}
                    <input type="range" id="soundVolumeSlider" class="form-range" style="width:80px; accent-color:#fff;"
                           min="0" max="100" step="5" value="70" aria-label="Incident alert volume">
                </div>

                {{-- Silence.

                     The alert repeats until the incident is dispatched, which
                     is the behaviour the desk asked for — but an alert that
                     genuinely cannot be stopped gets defeated at the OS level
                     instead, and then nothing is ever heard again. This stops
                     the repeat for the incidents currently sounding and leaves
                     the system armed for the next one, which the mute toggle
                     beside it does not.

                     Hidden until something is actually sounding, so the header
                     doesn't carry a dead control all shift. --}}
                <button type="button" id="silenceAlertBtn"
                        class="btn d-none align-items-center gap-2 fw-semibold text-white border-0"
                        style="background:#b91c1c; border-radius:999px; padding:8px 16px; font-size:.82rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                         stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <line x1="23" y1="9" x2="17" y2="15"></line>
                        <line x1="17" y1="9" x2="23" y2="15"></line>
                    </svg>
                    <span>Silence<span id="silenceAlertCount"></span></span>
                </button>
                @endif

                {{-- Bell --}}
                @php
                    $bellIncidents = \App\Models\Incident::with('rider')
                        ->orderByDesc('created_at')
                        ->limit(8)
                        ->get();
                    $bellRegs = \App\Models\PatrolRegistration::where('status','pending')
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();

                    // Icon per kind, defined once here and handed to the live
                    // script below as JSON. A server-rendered notification and
                    // one added by Pusher a second later have to be the same
                    // object; keeping two copies of this markup is how they
                    // drift apart.
                    $notifIcons = [
                        'incident' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                        'dispatch' => '<path d="M12 22s8-4.5 8-11a8 8 0 1 0-16 0c0 6.5 8 11 8 11z"/><circle cx="12" cy="11" r="3"/>',
                        'registration' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
                    ];

                    // Each row carries a short title and a separate detail line
                    // rather than one run-on sentence. The old single blob
                    // ("Accident reported: Juan Dela Cruz — 123 Some Very Long
                    // Street, Poblacion…") wrapped to three or four lines and
                    // gave the eye nothing to skim, which is what made a list of
                    // them unreadable.
                    $bellItems = collect();
                    foreach ($bellIncidents as $inc) {
                        $bellItems->push([
                            'time'   => $inc->created_at,
                            'kind'   => 'incident',
                            'title'  => 'Accident reported',
                            'detail' => ($inc->rider->full_name ?? 'Unknown rider') .
                                        ' · ' . ($inc->address ?: 'Location unavailable'),
                            'url'    => route('toc.location.tracking'),
                        ]);
                    }
                    foreach ($bellRegs as $reg) {
                        $bellItems->push([
                            'time'   => $reg->created_at,
                            'kind'   => 'registration',
                            'title'  => 'Patrol registration',
                            'detail' => trim($reg->first_name . ' ' . $reg->last_name),
                            'url'    => route('toc.patrol-registrations.index'),
                        ]);
                    }
                    $bellItems = $bellItems->sortByDesc('time')->values();
                    $bellCountLabel = $bellItems->count() > 9 ? '9+' : (string) $bellItems->count();
                @endphp
                <div class="dropdown">
                    <button class="btn rounded-circle p-3 border-0 position-relative" style="background:#7B1A2E;"
                            id="bellBtn" data-bs-toggle="dropdown" aria-expanded="false"
                            {{-- "unread" was a claim the app cannot back up: there is
                                 no read/unread state anywhere, the badge is simply how
                                 many recent items the bell is holding. --}}
                            aria-label="Notifications{{ isset($bellItems) && $bellItems->isNotEmpty() ? ' — '.$bellCountLabel.' recent' : '' }}">
                        @if($bellItems->isNotEmpty())
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              id="bellDot" style="font-size:.85rem; padding:.4rem .62rem; min-width:1.65rem;">
                            {{ $bellCountLabel }}
                        </span>
                        @else
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              id="bellDot" style="font-size:.85rem; padding:.4rem .62rem; min-width:1.65rem; display:none;">0</span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                             stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             viewBox="0 0 24 24">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </button>
                    {{-- Pre-loaded from DB + topped up by Pusher in real time.

                         A dropdown-menu that is its own scroll box cannot hold a
                         heading that stays put, so the menu is a shell: a pinned
                         header, a scrolling list, and a pinned footer. All the
                         item styling lives in layout.css under .notif-* — the
                         live script below emits the same class names, so there
                         is one design here, not two that drift. --}}
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 notif-menu">

                        <div class="notif-head">
                            <span class="notif-head-title">Notifications</span>
                            <span class="notif-head-count" id="notifHeadCount">{{ $bellItems->count() }}</span>
                        </div>

                        <ul class="notif-scroll" id="notificationList">
                            @forelse($bellItems as $item)
                                <li class="notif-item notif-item--{{ $item['kind'] }}">
                                    <a href="{{ $item['url'] }}" class="notif-link">
                                        <span class="notif-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                                {!! $notifIcons[$item['kind']] ?? '' !!}
                                            </svg>
                                        </span>
                                        <span class="notif-body">
                                            <span class="notif-title">{{ $item['title'] }}</span>
                                            <span class="notif-detail">{{ $item['detail'] }}</span>
                                            <span class="notif-ago" data-time="{{ $item['time']->toISOString() }}">
                                                {{ $item['time']->diffForHumans() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @empty
                                <li id="notificationEmpty" class="notif-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                         stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                    <span>Nothing to report</span>
                                    <small>New accidents and registrations appear here.</small>
                                </li>
                            @endforelse
                        </ul>

                        {{-- The bell only ever holds the newest handful. This is
                             the way to everything else, rather than letting an
                             operator scroll a dropdown looking for history that
                             was never in it. --}}
                        <a class="notif-foot" href="{{ route('toc.activity.index') }}">
                            View all desk activity
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none"
                                 stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                 stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    </div>
                    {{-- Announces new live notifications to screen readers even while
                         the dropdown is closed — nothing else does, since Bootstrap
                         hides the dropdown-menu contents (display:none) when collapsed. --}}
                    <div id="notifAnnouncer" aria-live="polite" class="visually-hidden"></div>
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
@php
    // The repeating alert has to survive a page load, because dispatching is a
    // form POST that reloads the page — a JS-only "is it sounding" flag would
    // be wiped by the very action that is supposed to stop it. So the loop is
    // driven by a server fact instead: incidents still sitting at "pending"
    // inside the live window. Anything older than that window is history, not
    // something to sound a siren about on a desk that has since changed shift.
    $unattendedIncidents = \App\Models\Incident::query()
        ->where('status', 'pending')
        ->where('created_at', '>=', now()->subHours(\App\Models\Incident::LIVE_WINDOW_HOURS))
        ->orderByDesc('created_at')
        ->get(['id', 'severity'])
        ->map(fn ($i) => ['id' => (int) $i->id, 'severity' => $i->severity])
        ->values();
@endphp
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

    const locationTrackingUrl    = @json(route('toc.location.tracking'));
    const patrolRegistrationsUrl = @json(route('toc.patrol-registrations.index'));

    // ── New-incident sidebar flicker ────────────────────────────────────
    // Skip it (not the bell or the sound — those still fire every time) if
    // they're already on Location Tracking: that page already plots the
    // incident live on the map, and the flicker exists to point them at a
    // page they're not currently looking at.
    const onLocationTrackingPage = window.location.pathname ===
        new URL(locationTrackingUrl, window.location.origin).pathname;

    const navLocationTracking = document.getElementById('navLocationTracking');
    let navFlickerTimer = null;

    function stopNavFlicker() {
        if (navFlickerTimer) {
            clearTimeout(navFlickerTimer);
            navFlickerTimer = null;
        }
        if (navLocationTracking) navLocationTracking.classList.remove('nav-alert-flicker');
    }

    function startNavFlicker() {
        if (onLocationTrackingPage || !navLocationTracking) return;

        navLocationTracking.classList.add('nav-alert-flicker');
        if (navFlickerTimer) clearTimeout(navFlickerTimer);
        navFlickerTimer = setTimeout(stopNavFlicker, 20000);
    }

    // Clicking through is the clearest possible acknowledgment — stop
    // flickering immediately instead of waiting out the timeout.
    if (navLocationTracking) navLocationTracking.addEventListener('click', stopNavFlicker);

    // ── Incident alert sound ────────────────────────────────────────────
    // Two short sine-wave beeps generated with the Web Audio API instead of
    // an audio file — no asset to host, no licensing question, and no
    // network request that could fail right when it matters most.
    let soundMuted = false;
    let soundVolume = 70; // 0–100, matches the slider's default value attribute
    try {
        soundMuted = window.localStorage.getItem('incidentSoundMuted') === 'true';
        const storedVolume = parseInt(window.localStorage.getItem('incidentSoundVolume'), 10);
        if (!isNaN(storedVolume)) soundVolume = Math.min(100, Math.max(0, storedVolume));
    } catch (e) { /* localStorage unavailable (private mode, etc.) — defaults above stand */ }

    const soundToggleBtn    = document.getElementById('soundToggleBtn');
    const soundOnIcon       = document.getElementById('soundOnIcon');
    const soundOffIcon      = document.getElementById('soundOffIcon');
    const soundVolumeSlider = document.getElementById('soundVolumeSlider');
    if (soundVolumeSlider) {
        soundVolumeSlider.value = String(soundVolume);
        soundVolumeSlider.title = `Incident alert volume: ${soundVolume}%`;
    }

    function updateSoundToggleUI() {
        if (!soundToggleBtn) return;
        soundToggleBtn.setAttribute('aria-pressed', String(soundMuted));
        soundToggleBtn.setAttribute('aria-label', soundMuted ? 'Unmute incident alert sound' : 'Mute incident alert sound');
        soundToggleBtn.title = soundMuted ? 'Incident alert sound: off' : 'Incident alert sound: on';
        if (soundOnIcon)  soundOnIcon.style.display  = soundMuted ? 'none' : '';
        if (soundOffIcon) soundOffIcon.style.display = soundMuted ? '' : 'none';
    }
    updateSoundToggleUI();

    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', function () {
            soundMuted = !soundMuted;
            try { window.localStorage.setItem('incidentSoundMuted', String(soundMuted)); } catch (e) { /* ignore */ }
            updateSoundToggleUI();
        });
    }

    let audioCtx = null;

    // A context created before the operator has clicked anything starts
    // suspended under the browser's autoplay policy, which made the first
    // alert on a freshly opened board silent. Resumed on demand, and primed
    // on the first interaction below.
    function getAudioContext() {
        try {
            if (!audioCtx) {
                const Ctor = window.AudioContext || window.webkitAudioContext;
                if (!Ctor) return null;
                audioCtx = new Ctor();
            }
            if (audioCtx.state === 'suspended') audioCtx.resume();
            return audioCtx;
        } catch (e) {
            return null;
        }
    }

    ['pointerdown', 'keydown'].forEach(function (evt) {
        window.addEventListener(evt, function primeAudio() {
            getAudioContext();
            window.removeEventListener(evt, primeAudio);
        }, { once: true });
    });

    /**
     * One swept note, drawn into any audio context.
     *
     * The alert was previously two flat sine notes, 880 then 1108 Hz, over
     * about a third of a second. A steady pitch is the easiest thing for the
     * ear to stop hearing — every emergency siren sweeps or alternates for
     * exactly that reason, because it is pitch *change* the ear cannot
     * habituate to. These glide instead.
     *
     * Takes the context and destination as arguments so the same drawing
     * code serves both the live context (the volume-slider preview) and the
     * offline one the repeating siren is rendered in.
     */
    function sweepTone(ctx, dest, fromHz, toHz, duration, startAt, volume) {
        const t    = ctx.currentTime + startAt;
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();

        // Triangle carries further than sine without the harshness of square,
        // which at alert volume makes people reach for the mute.
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(fromHz, t);
        osc.frequency.linearRampToValueAtTime(toHz, t + duration);

        // Short fades: an instant edge clicks, and a click is heard as a fault
        // rather than as an alert.
        gain.gain.setValueAtTime(0.0001, t);
        gain.gain.exponentialRampToValueAtTime(volume, t + 0.012);
        gain.gain.setValueAtTime(volume, t + duration - 0.04);
        gain.gain.exponentialRampToValueAtTime(0.0001, t + duration);

        osc.connect(gain).connect(dest);
        osc.start(t);
        osc.stop(t + duration + 0.02);
    }

    /**
     * Alert shape by severity, so the desk can tell how bad an incident is
     * without looking up. The payload has always carried severity; the tone
     * simply never used it.
     */
    const ALERT_PATTERNS = {
        critical: { cycles: 4, gap: 0.30, volume: 0.34, from: 700, to: 1180, dur: 0.22 },
        high:     { cycles: 3, gap: 0.30, volume: 0.28, from: 660, to: 1040, dur: 0.22 },
        medium:   { cycles: 2, gap: 0.32, volume: 0.22, from: 620, to:  900, dur: 0.22 },
        low:      { cycles: 1, gap: 0.00, volume: 0.18, from: 600, to:  820, dur: 0.24 },
    };

    // Draws one burst of a pattern into ctx, starting startAt seconds from now.
    function drawPattern(ctx, dest, pattern, volume, startAt) {
        for (let i = 0; i < pattern.cycles; i++) {
            const at = startAt + i * pattern.gap;
            // Up then straight back down, which reads as urgency rather than
            // as a notification chime.
            sweepTone(ctx, dest, pattern.from, pattern.to, pattern.dur, at, volume);
            sweepTone(ctx, dest, pattern.to, pattern.from, pattern.dur,
                at + pattern.dur * 0.55, volume * 0.75);
        }
    }

    // Length of one burst in seconds, tail included.
    function patternLength(pattern) {
        return (pattern.cycles - 1) * pattern.gap + pattern.dur * 0.55 + pattern.dur + 0.02;
    }

    // One-shot, on the live context. Used by the volume slider's preview.
    // volumeFraction is 0–1 and deliberately skips the mute check, so an
    // officer can hear the level they are choosing.
    function playChimeTone(volumeFraction, severity) {
        if (volumeFraction <= 0) return;
        const ctx = getAudioContext();
        if (!ctx) return;
        try {
            const p = ALERT_PATTERNS[severity] || ALERT_PATTERNS.high;
            drawPattern(ctx, ctx.destination, p, p.volume * volumeFraction, 0);
        } catch (e) {
            // Web Audio unsupported — the banner and tab-title flash still
            // carry the alert.
        }
    }

    // ── Repeating alert until the call is handled ───────────────────────
    //
    // A single chime is missed by anyone who stepped away from the desk for
    // the eight seconds it took. This repeats until the incident stops being
    // "pending" — which is exactly what dispatching a patrol unit does — or
    // until an officer silences it deliberately.
    //
    // Three things can stop it, and all three are acknowledgements:
    //   1. dispatch (or any status change off "pending"), broadcast live;
    //   2. the Silence button, which acknowledges only the incidents that are
    //      currently sounding and leaves the next alert armed;
    //   3. the incident no longer being pending on the next page load.
    // Muting is deliberately NOT one of them: mute silences the tab, and an
    // operator who muted last Tuesday should still see the counter climbing.

    const unattended = new Map();   // incident id -> severity

    const silenceBtn   = document.getElementById('silenceAlertBtn');
    const silenceCount = document.getElementById('silenceAlertCount');

    // Silenced ids live in localStorage so the siren does not start again the
    // moment the operator navigates to another page — the alert was already
    // acknowledged, and re-sounding it teaches people to mute the tab.
    const SILENCED_KEY = 'incidentAlertsSilenced';

    function readSilenced() {
        try {
            const raw = JSON.parse(window.localStorage.getItem(SILENCED_KEY) || '[]');
            return Array.isArray(raw) ? raw.map(Number).filter(n => !isNaN(n)) : [];
        } catch (e) {
            return [];
        }
    }

    function writeSilenced(ids) {
        try {
            // Capped, and newest-first: without a cap this grows for the life
            // of the browser profile. 200 covers far more than a shift.
            window.localStorage.setItem(SILENCED_KEY, JSON.stringify(ids.slice(0, 200)));
        } catch (e) { /* private mode — silencing then lasts for this page only */ }
    }

    // ── The siren ───────────────────────────────────────────────────────
    //
    // It used to be a single burst followed by 7–20 seconds of silence,
    // re-armed with setTimeout. That had three problems, and together they
    // made the "nonstop" alert mostly silence:
    //
    //   - the gap was the design: a high-severity call sounded for about a
    //     second, then said nothing for nine;
    //   - every Pusher event called clearTimeout and started the full interval
    //     again, so a busy board kept pushing the next alert further away;
    //   - browsers throttle timers in background tabs — Chrome to once a
    //     minute after five minutes hidden — so a minimised board, the exact
    //     case the alert exists for, could go a full minute between sounds.
    //
    // Now the pattern is rendered once into an audio buffer and played with
    // loop = true. The audio engine keeps time itself: no JavaScript timer
    // decides when the next burst plays, so nothing can throttle it or reset
    // it, and it genuinely does not stop until the call is handled.

    // Silence after each burst before it repeats. Chosen so every severity
    // cycles at roughly the same ~1.65s rhythm — what changes with severity is
    // how much of that rhythm is sound. Short enough to read as one continuous
    // alarm rather than a series of notifications.
    const SIREN_PAUSE = { critical: 0.40, high: 0.70, medium: 1.00, low: 1.30 };
    const SEVERITY_RANK = { critical: 4, high: 3, medium: 2, low: 1 };

    let sirenSource   = null;   // the looping AudioBufferSourceNode
    let sirenGain     = null;   // volume + mute, adjustable while it plays
    let sirenSeverity = null;   // what is playing now
    let sirenWanted   = null;   // what should be playing (buffers render async)
    let flickerTimer  = null;
    const sirenBuffers = {};    // severity -> Promise<AudioBuffer|null>

    function worstPendingSeverity() {
        let worst = 'low';
        unattended.forEach(function (severity) {
            const sev = ALERT_PATTERNS[severity] ? severity : 'high';
            if ((SEVERITY_RANK[sev] || 0) > (SEVERITY_RANK[worst] || 0)) worst = sev;
        });
        return worst;
    }

    function updateSilenceUI() {
        if (!silenceBtn) return;
        const n = unattended.size;
        silenceBtn.classList.toggle('d-none', n === 0);
        silenceBtn.classList.toggle('d-flex', n > 0);
        if (silenceCount) silenceCount.textContent = n > 1 ? ` (${n})` : '';
        silenceBtn.setAttribute('aria-label', n > 1
            ? `Silence the alert for ${n} incidents awaiting dispatch`
            : 'Silence the alert for the incident awaiting dispatch');
        silenceBtn.title = 'Stops the alert. The next incident still sounds.';
    }

    // Renders one burst plus its trailing pause into a buffer, once per
    // severity, and caches the result.
    function renderSirenBuffer(severity) {
        if (sirenBuffers[severity]) return sirenBuffers[severity];

        const ctx = getAudioContext();
        const Offline = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (!ctx || !Offline) return Promise.resolve(null);

        const p = ALERT_PATTERNS[severity] || ALERT_PATTERNS.high;
        const seconds = patternLength(p) + (SIREN_PAUSE[severity] ?? 0.7);

        try {
            const off = new Offline(1, Math.ceil(seconds * ctx.sampleRate), ctx.sampleRate);
            drawPattern(off, off.destination, p, p.volume, 0);
            sirenBuffers[severity] = Promise.resolve(off.startRendering()).catch(function () {
                return null;
            });
        } catch (e) {
            sirenBuffers[severity] = Promise.resolve(null);
        }
        return sirenBuffers[severity];
    }

    // Applied live, so moving the slider or pressing mute changes a siren that
    // is already sounding instead of waiting for the next one.
    function applySirenVolume() {
        if (!sirenGain || !audioCtx) return;
        const target = soundMuted ? 0 : soundVolume / 100;
        sirenGain.gain.setTargetAtTime(target, audioCtx.currentTime, 0.03);
    }

    function stopSource() {
        if (sirenSource) {
            try { sirenSource.stop(); } catch (e) { /* already stopped */ }
            sirenSource.disconnect();
            sirenSource = null;
        }
        sirenSeverity = null;
    }

    function startSiren(severity) {
        sirenWanted = severity;

        // Already playing this pattern: leave it alone. Restarting here is
        // what used to reset the rhythm on every Pusher event.
        if (sirenSource && sirenSeverity === severity) return;

        renderSirenBuffer(severity).then(function (buffer) {
            if (!buffer || sirenWanted !== severity) return;
            if (sirenSource && sirenSeverity === severity) return;

            const ctx = getAudioContext();
            if (!ctx) return;

            if (!sirenGain) {
                sirenGain = ctx.createGain();
                sirenGain.gain.value = 0;
                sirenGain.connect(ctx.destination);
            }

            stopSource();
            const src = ctx.createBufferSource();
            src.buffer = buffer;
            src.loop = true;
            src.connect(sirenGain);
            // If the page has not been clicked yet, the browser holds the
            // context suspended; this queues the loop and it starts on the
            // officer's first click (see primeAudio above).
            src.start();

            sirenSource   = src;
            sirenSeverity = severity;
            applySirenVolume();
        });
    }

    function stopSiren() {
        sirenWanted = null;
        stopSource();
        if (flickerTimer) {
            clearInterval(flickerTimer);
            flickerTimer = null;
        }
        stopNavFlicker();
    }

    // The single entry point: make the siren match what is unattended now.
    // Safe to call as often as events arrive — it only changes anything when
    // the answer changes.
    function refreshSiren() {
        if (unattended.size === 0) { stopSiren(); return; }

        startSiren(worstPendingSeverity());

        // The sidebar keeps flickering for as long as the siren sounds, so a
        // muted tab still shows something is outstanding. startNavFlicker
        // stops itself after 20s, so it is re-armed rather than set once.
        startNavFlicker();
        if (!flickerTimer) flickerTimer = setInterval(startNavFlicker, 15000);
    }

    if (soundToggleBtn)    soundToggleBtn.addEventListener('click', applySirenVolume);
    if (soundVolumeSlider) soundVolumeSlider.addEventListener('input', applySirenVolume);

    // Some browsers suspend audio on a hidden tab; bring it back on return.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && sirenWanted) getAudioContext();
    });

    // Starts sounding immediately, including on page load. The old version
    // waited a full interval after every reload before making any noise — and
    // dispatching a unit reloads the page, so a second waiting call went quiet
    // at exactly the moment the officer was back at the board.
    function markUnattended(id, severity) {
        if (id === undefined || id === null) return;
        const key = Number(id);
        if (isNaN(key)) return;
        if (readSilenced().indexOf(key) !== -1) return;   // already acknowledged

        unattended.set(key, severity || 'high');
        updateSilenceUI();
        refreshSiren();
    }

    function clearUnattended(id) {
        const key = Number(id);
        if (isNaN(key) || !unattended.has(key)) return;
        unattended.delete(key);
        updateSilenceUI();
        refreshSiren();
    }

    if (silenceBtn) {
        silenceBtn.addEventListener('click', function () {
            const ids = Array.from(unattended.keys());
            writeSilenced(ids.concat(readSilenced()));
            unattended.clear();
            updateSilenceUI();
            stopSiren();
        });
    }

    // A desk with the board open on two monitors would otherwise keep sounding
    // on the second screen after being silenced on the first. localStorage
    // fires this in the other tabs of the same browser.
    window.addEventListener('storage', function (e) {
        if (e.key !== SILENCED_KEY) return;
        readSilenced().forEach(clearUnattended);
    });

    // Seed from the server: everything still waiting on a dispatch right now.
    @json($unattendedIncidents).forEach(function (row) {
        markUnattended(row.id, row.severity);
    });
    updateSilenceUI();

    if (soundVolumeSlider) {
        soundVolumeSlider.addEventListener('input', function () {
            soundVolume = parseInt(this.value, 10) || 0;
            this.title = `Incident alert volume: ${soundVolume}%`;
            try { window.localStorage.setItem('incidentSoundVolume', String(soundVolume)); } catch (e) { /* ignore */ }
        });
        // Fires once when the drag ends / arrow-key change commits — not on
        // every tick while dragging, so previews don't overlap each other.
        soundVolumeSlider.addEventListener('change', function () {
            // Previews at "high", the most common severity, so the level being
            // set is representative rather than the quietest or loudest case.
            playChimeTone(soundVolume / 100, 'high');
        });
    }

    // "Xm ago" that actually ticks forward instead of freezing at whatever
    // diffForHumans() said on page load, or "Just now" forever for anything
    // added live — applies to every item, server-rendered or live-added,
    // since they all carry the same data-time attribute.
    function formatAgo(ms) {
        const mins = Math.floor(ms / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins}m ago`;
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `${hrs}h ${mins % 60}m ago`;
        return `${Math.floor(hrs / 24)}d ago`;
    }
    function refreshNotificationAgo() {
        document.querySelectorAll('.notif-ago').forEach(el => {
            const t = new Date(el.dataset.time).getTime();
            if (!isNaN(t)) el.textContent = formatAgo(Date.now() - t);
        });
    }
    refreshNotificationAgo();
    setInterval(refreshNotificationAgo, 30000);

    // Single source of truth for the bell dropdown — every real event (new
    // accident, status change, new registration) goes through this, so the
    // feed is consistent across every TOC page instead of being built from
    // page-specific server-rendered content. Colored/linked the same way as
    // the server-rendered items above, so a live-added notification looks
    // identical to one that was already there on page load.
    // The icon set the server rendered with, handed over verbatim so a live
    // notification is indistinguishable from one that was already on the page.
    const NOTIF_ICONS = @json($notifIcons);

    // Escaped because detail carries a rider's name and a geocoded address —
    // both free text that has reached us from a device in the field.
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // item: { kind, title, detail, url } — kind is one of the NOTIF_ICONS keys.
    //
    // Deliberately not a JSDoc block. This file is Blade, and Blade compiles
    // its directives inside <script> too: an "@param" line whose type carries
    // doubled braces is read as a directive followed by an echo expression,
    // and the page dies with "Undefined constant".
    function addNotification(item) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        const empty = document.getElementById('notificationEmpty');
        if (empty) empty.remove();

        const kind = NOTIF_ICONS[item.kind] ? item.kind : 'incident';
        const now  = new Date().toISOString();

        const li = document.createElement('li');
        li.className = 'notif-item notif-item--' + kind + ' notif-item--new';
        li.innerHTML = `<a href="${escapeHtml(item.url)}" class="notif-link">
                <span class="notif-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">${NOTIF_ICONS[kind]}</svg>
                </span>
                <span class="notif-body">
                    <span class="notif-title">${escapeHtml(item.title)}</span>
                    <span class="notif-detail">${escapeHtml(item.detail)}</span>
                    <span class="notif-ago" data-time="${now}">just now</span>
                </span>
            </a>`;

        list.prepend(li);

        // Bump badge counter (caps display at "9+" rather than silently
        // freezing at 9 with no indication there's more). The header count
        // beside it is uncapped — once the list is open there is room for the
        // real number, and "9+" over a list you are already reading is coy.
        const dot = document.getElementById('bellDot');
        if (dot) {
            const cur = dot.textContent.trim() === '9+' ? 10 : (parseInt(dot.textContent, 10) || 0);
            const next = cur + 1;
            dot.textContent = next > 9 ? '9+' : String(next);
            dot.style.display = '';
        }

        const headCount = document.getElementById('notifHeadCount');
        if (headCount) {
            headCount.textContent = String(list.querySelectorAll('.notif-item').length);
        }

        // Screen readers can't see the dropdown open/close state changing on
        // its own, and the menu's contents are display:none while collapsed
        // — this is what actually tells a screen-reader user something new
        // came in, whether or not the bell is open.
        const announcer = document.getElementById('notifAnnouncer');
        if (announcer) announcer.textContent = item.title + ': ' + item.detail;
    }

    // New incident arrives — add a row to the Recent Incidents table (if
    // present on this page) and a notification entry (on every page).
    channel.bind('incident.reported', function (data) {
        const tbody = document.getElementById('incidents-tbody');
        if (tbody) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${data.rider?.full_name ?? 'N/A'}</td>
                <td>${data.address ?? 'N/A'}</td>
                <td>${data.rider?.phone_number ?? 'N/A'}</td>
                <td>N/A</td>
                <td>N/A</td>
                <td></td>`;
            tbody.prepend(tr);
        }

        addNotification({
            kind:   'incident',
            title:  'Accident reported',
            detail: `${data.rider?.full_name ?? 'Unknown rider'} · ${data.address || 'Location unavailable'}`,
            url:    locationTrackingUrl,
        });
        startNavFlicker();
        // Starts the siren, which loops until the call is dispatched or
        // silenced.
        markUnattended(data.id, data.severity);
    });

    // Status changed (e.g. a patrol pressed "I'm On My Way" / "Mark as
    // Arrived" in the mobile app).
    channel.bind('incident.status_updated', function (data) {
        // Dispatching is what the desk does about an alert, so it is what
        // stops the alert. Any other move off "pending" (resolved, false
        // alarm) counts too — all of them mean somebody dealt with it.
        if (data.status && data.status !== 'pending') {
            clearUnattended(data.id);
        } else {
            markUnattended(data.id, data.severity);
        }

        const patrolName = data.patrol_unit?.full_name;
        addNotification({
            kind:   'dispatch',
            title:  `Incident #${data.id} — ${data.status}`,
            detail: patrolName ? `Marked by ${patrolName}` : 'Status changed at the desk',
            url:    locationTrackingUrl,
        });
    });

    // New patrol registration submitted — bump the sidebar pending-count
    // badge live and drop a notification entry.
    const registrationsChannel = pusher.subscribe('patrol-registrations');
    registrationsChannel.bind('registration.submitted', function (data) {
        const regBadge = document.getElementById('reg-badge');
        if (regBadge) {
            const count = (parseInt(regBadge.textContent, 10) || 0) + 1;
            regBadge.textContent = count;
            regBadge.style.display = '';
        }

        addNotification({
            kind:   'registration',
            title:  'Patrol registration',
            detail: data.full_name ?? 'Unknown applicant',
            url:    patrolRegistrationsUrl,
        });
    });
})();
</script>
@endif

@stack('scripts')
</body>
</html>
