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

            <a href="{{ route('toc.speed-zones.index') }}"
               class="nav-link {{ request()->routeIs('toc.speed-zones*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24">
                    <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/>
                </svg>
                Speed Zones
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
                    // Colored by type (red = accident, blue = registration) so an
                    // operator can tell what a notification is about before even
                    // reading it — same color language the map already uses.
                    $bellItems = collect();
                    foreach ($bellIncidents as $inc) {
                        $bellItems->push([
                            'time'  => $inc->created_at,
                            'color' => '#dc2626',
                            'text'  => 'Accident reported: ' .
                                      ($inc->rider->full_name ?? 'Unknown rider') .
                                      ' — ' . ($inc->address ?? 'unknown location'),
                            'url'   => route('toc.location.tracking'),
                        ]);
                    }
                    foreach ($bellRegs as $reg) {
                        $bellItems->push([
                            'time'  => $reg->created_at,
                            'color' => '#2563eb',
                            'text'  => 'Patrol registration: ' . $reg->first_name . ' ' . $reg->last_name,
                            'url'   => route('toc.patrol-registrations.index'),
                        ]);
                    }
                    $bellItems = $bellItems->sortByDesc('time')->values();
                    $bellCountLabel = $bellItems->count() > 9 ? '9+' : (string) $bellItems->count();
                @endphp
                <div class="dropdown">
                    <button class="btn rounded-circle p-3 border-0 position-relative" style="background:#7B1A2E;"
                            id="bellBtn" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Notifications{{ isset($bellItems) && $bellItems->isNotEmpty() ? ' — '.$bellCountLabel.' unread' : '' }}">
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
                    {{-- Pre-loaded from DB + topped up by Pusher in real time --}}
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                        id="notificationList" style="min-width:300px; max-height:360px; overflow-y:auto;">
                        @forelse($bellItems as $item)
                        <li>
                            <a href="{{ $item['url'] }}"
                               class="dropdown-item d-flex align-items-start gap-2 py-2"
                               style="font-size:.8rem; white-space:normal; line-height:1.35; border-left:3px solid {{ $item['color'] }};">
                                <span style="width:8px; height:8px; border-radius:50%; background:{{ $item['color'] }}; flex-shrink:0; margin-top:5px;"></span>
                                <span class="flex-grow-1">
                                    <span class="d-block">{{ $item['text'] }}</span>
                                    <span class="text-muted notif-ago" data-time="{{ $item['time']->toISOString() }}" style="font-size:.72rem;">
                                        {{ $item['time']->diffForHumans() }}
                                    </span>
                                </span>
                            </a>
                        </li>
                        @if(!$loop->last)
                        <li><hr class="dropdown-divider my-0"></li>
                        @endif
                        @empty
                        <li id="notificationEmpty">
                            <span class="dropdown-item text-muted" style="font-size:.82rem;">No notifications yet</span>
                        </li>
                        @endforelse
                    </ul>
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
    // volumeFraction is 0–1; separated from playIncidentChime so the slider
    // can preview a level directly without going through the mute check.
    function playChimeTone(volumeFraction) {
        if (volumeFraction <= 0) return;
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const now = audioCtx.currentTime;
            const peakGain = 0.3 * volumeFraction;
            [880, 1108].forEach(function (freq, i) {
                const osc  = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                const start = now + i * 0.18;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(peakGain, start + 0.02);
                gain.gain.linearRampToValueAtTime(0, start + 0.16);
                osc.connect(gain).connect(audioCtx.destination);
                osc.start(start);
                osc.stop(start + 0.18);
            });
        } catch (e) {
            // Autoplay blocked or Web Audio unsupported — the banner and tab-
            // title flash still carry the alert either way.
        }
    }

    function playIncidentChime() {
        if (soundMuted) return;
        playChimeTone(soundVolume / 100);
    }

    if (soundVolumeSlider) {
        soundVolumeSlider.addEventListener('input', function () {
            soundVolume = parseInt(this.value, 10) || 0;
            this.title = `Incident alert volume: ${soundVolume}%`;
            try { window.localStorage.setItem('incidentSoundVolume', String(soundVolume)); } catch (e) { /* ignore */ }
        });
        // Fires once when the drag ends / arrow-key change commits — not on
        // every tick while dragging, so previews don't overlap each other.
        soundVolumeSlider.addEventListener('change', function () {
            playChimeTone(soundVolume / 100);
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
    function addNotification(text, color, url) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        const empty = document.getElementById('notificationEmpty');
        if (empty) empty.remove();

        const now = new Date().toISOString();
        const li = document.createElement('li');
        li.innerHTML = `<a href="${url}" class="dropdown-item d-flex align-items-start gap-2 py-2"
                            style="font-size:.8rem;white-space:normal;line-height:1.35;border-left:3px solid ${color};">
                            <span style="width:8px;height:8px;border-radius:50%;background:${color};flex-shrink:0;margin-top:5px;"></span>
                            <span class="flex-grow-1">
                                <span class="d-block">${text}</span>
                                <span class="text-muted notif-ago" data-time="${now}" style="font-size:.72rem;">just now</span>
                            </span>
                        </a>`;

        // Divider between new item and existing items
        if (list.children.length > 0) {
            const divLi = document.createElement('li');
            divLi.innerHTML = '<hr class="dropdown-divider my-0">';
            list.prepend(divLi);
        }
        list.prepend(li);

        // Bump badge counter (caps display at "9+" rather than silently
        // freezing at 9 with no indication there's more)
        const dot = document.getElementById('bellDot');
        if (dot) {
            const cur = dot.textContent.trim() === '9+' ? 10 : (parseInt(dot.textContent, 10) || 0);
            const next = cur + 1;
            dot.textContent = next > 9 ? '9+' : String(next);
            dot.style.display = '';
        }

        // Screen readers can't see the dropdown open/close state changing on
        // its own, and the menu's contents are display:none while collapsed
        // — this is what actually tells a screen-reader user something new
        // came in, whether or not the bell is open.
        const announcer = document.getElementById('notifAnnouncer');
        if (announcer) announcer.textContent = text;
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

        const incidentText = `New accident reported: ${data.rider?.full_name ?? 'Unknown rider'} at ${data.address ?? 'unknown location'}`;

        addNotification(incidentText, '#dc2626', locationTrackingUrl);
        startNavFlicker();
        playIncidentChime();
    });

    // Status changed (e.g. a patrol pressed "I'm On My Way" / "Mark as
    // Arrived" in the mobile app).
    channel.bind('incident.status_updated', function (data) {
        const patrolName = data.patrol_unit?.full_name;
        addNotification(
            patrolName
                ? `${patrolName} marked incident #${data.id} as ${data.status}`
                : `Incident #${data.id} status changed to ${data.status}`,
            '#b45309', // #f59e0b fails WCAG 1.4.11 (2.15:1) as a border/dot on white — verified with the same audit used elsewhere in this app
            locationTrackingUrl
        );
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

        addNotification(
            `New patrol registration: ${data.full_name ?? 'Unknown applicant'}`,
            '#2563eb',
            patrolRegistrationsUrl
        );
    });
})();
</script>
@endif

@stack('scripts')
</body>
</html>
