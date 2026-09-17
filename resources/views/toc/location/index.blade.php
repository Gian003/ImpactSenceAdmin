@extends('toc.layouts.app')

@section('title', 'Location Tracking')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/toc/location.css') }}?v={{ filemtime(public_path('css/toc/location.css')) }}">
@endpush

@section('content')

    {{-- SUCCESS FLASH — floated over the board rather than stacked above it.
         In the flow it pushed the map down by its own height, and it appears
         at exactly the moment an operator has just dispatched and wants to
         watch the unit move. --}}
    @if (session('dispatched'))
        <div class="alert alert-success alert-dismissible py-2 tracking-flash" style="font-size:.88rem;">
            {{ session('dispatched') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- The board fills the viewport and never scrolls: the map takes the
     whole area, and the alert cards float over its top-left corner instead
     of sitting in a strip underneath. Stacked, the two could not both be on
     screen at once — the operator scrolled down to read a card and lost
     sight of the map, or shrank the map to keep the cards visible, and the
     map is the thing they are dispatching from.

     The overlay is positioned by CSS against .tracking-layout, so the map
     markup still comes first in the DOM — tab order and screen-reader order
     stay "map, then alerts", matching how the page is used. --}}
    <div class="tracking-layout">

        {{-- MAP + OVERLAYS --}}
        <div class="map-wrap">
            <div id="map"></div>

            {{-- Top-right overlay stack: the panel-toggle buttons, plus a static map
         key explaining what every marker color/size/shape means. The key is
         always visible (not another toggle) since the point of it is to
         remove guesswork, not add one more thing to discover. Stacked here
         rather than bottom-right so it never gets covered by the
         Speed/Prone/Patrollers panels below, which are bottom-anchored. --}}
            <div class="map-overlay-topright">
                <div class="map-legend-card">
                    <button class="legend-btn" id="btnProne" onclick="togglePanel('prone')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            viewBox="0 0 24 24">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        Accident Prone Area
                    </button>
                    <div class="prone-sub" id="proneSub">
                        <div class="d-flex align-items-center gap-2" style="font-size:.86rem;">
                            <div class="prone-dot" style="background:#e53e3e;"></div> High
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:.86rem;">
                            <div class="prone-dot" style="background:#dd6b20;"></div> Average
                        </div>
                        <div class="d-flex align-items-center gap-2" style="font-size:.86rem;">
                            <div class="prone-dot" style="background:#d69e2e;"></div> Low
                        </div>
                    </div>
                    <button class="legend-btn" id="btnPatrollers" onclick="togglePanel('patrollers')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        Patrollers
                    </button>
                    <button class="legend-btn" id="btnTraffic" onclick="toggleTraffic()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M9 3v18" />
                            <path d="M15 3v18" />
                        </svg>
                        Live Traffic
                    </button>
                    <button class="legend-btn" id="btnSatellite" onclick="toggleSatellite()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M2 12h20" />
                            <path
                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        </svg>
                        Satellite View
                    </button>

                </div>

                <div class="map-key-card">
                    <div class="map-key-title">Map Key</div>

                    <div class="map-key-group-label">Accidents</div>
                    <div class="map-key-row"><span class="map-key-dot" style="background:#dc2626;"></span> Pending</div>
                    <div class="map-key-row"><span class="map-key-dot" style="background:#f59e0b;"></span> Dispatched
                    </div>
                    {{-- Added alongside the 'arrived' status. Without it the key
                         claimed every non-dispatched pin was Pending, including
                         incidents with a unit already on the scene. --}}
                    <div class="map-key-row"><span class="map-key-dot" style="background:#7c3aed;"></span> On scene
                    </div>
                    <div class="map-key-row"><span class="map-key-dot map-key-dot--sm"
                            style="background:#dc2626;"></span><span class="map-key-dot map-key-dot--lg"
                            style="background:#dc2626;"></span> Size = severity</div>
                    <div class="map-key-row"><span class="map-key-ring"></span> Pulsing ring = critical</div>

                    <div class="map-key-group-label">Patrol units</div>
                    <div class="map-key-row"><span class="map-key-dot" style="background:#2563eb;"></span> Standby</div>
                    <div class="map-key-row"><span class="map-key-dot" style="background:#f59e0b;"></span> Dispatched
                    </div>
                    <div class="map-key-row"><span class="map-key-arrow"></span> Arrow = direction of travel</div>
                    <div class="map-key-row"><span class="map-key-dot map-key-dot--faded"
                            style="background:#2563eb;"></span> Faded = no recent GPS update</div>

                    {{-- Only rendered while the Live Traffic layer is actually on;
                         toggleTraffic() adds .show. A key that explains a layer
                         the operator has switched off is just clutter on top of
                         the map. Colours are Google's, matching what the traffic
                         layer draws — see the note in location.css. --}}
                    <div class="map-key-traffic" id="mapKeyTraffic">
                        <div class="map-key-group-label">Live traffic</div>
                        <div class="map-key-row"><span class="map-key-line"
                                style="background:#63d668;"></span> Clear</div>
                        <div class="map-key-row"><span class="map-key-line"
                                style="background:#ff974d;"></span> Slowing</div>
                        <div class="map-key-row"><span class="map-key-line"
                                style="background:#f23c32;"></span> Congested</div>
                        <div class="map-key-row"><span class="map-key-line"
                                style="background:#811f1f;"></span> Stop-and-go</div>
                    </div>
                </div>
            </div>

            {{-- Accident Prone Area panel — real incidents ranked by density,
         grouped into ~111m areas (the heatmap layer plots every point;
         this ranks the areas so they're actually actionable). --}}
            <div class="map-panel" id="pronePanel">
                <div class="panel-card" style="min-width:420px; max-width:480px;">
                    {{-- Panel header — maroon bar, icon, title, live count
                         (maroon bar, icon, title, live count) so the two panels
                         read as the same kind of thing instead of one looking
                         like an afterthought next to the other. --}}
                    <div style="background:#7B1A2E; padding:10px 16px; display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none"
                                stroke="#F4C5D0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                viewBox="0 0 24 24">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                <polyline points="9 22 9 12 15 12 15 22" />
                            </svg>
                            <span style="color:#fff; font-size:.88rem; font-weight:700; letter-spacing:.03em;">Accident Prone Area</span>
                        </div>
                        <span style="font-size:.88rem; color:#F4C5D0;">
                            {{ ($incidentHotspots ?? collect())->count() }}
                            area{{ ($incidentHotspots ?? collect())->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    {{-- Scrollable body — capped the same way as the other panels, so
                         a full top-10 list of two-line rows (coordinates + geocoded
                         address) doesn't grow the panel past a comfortable height. --}}
                    <div class="panel-scroll">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">Area</th>
                                <th style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">Incidents</th>
                                <th style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">Severe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $tierColors = ['high' => '#e53e3e', 'average' => '#dd6b20', 'low' => '#d69e2e'];
                            @endphp
                            @forelse($incidentHotspots ?? [] as $h)
                                <tr class="hotspot-row" data-lat="{{ $h->lat_group }}" data-lng="{{ $h->lng_group }}">
                                    <td style="padding:9px 14px; border-bottom:1px solid #f5f5f5; vertical-align:top;">
                                        <div class="prone-dot" style="display:inline-block; background:{{ $tierColors[$h->tier] ?? '#d69e2e' }}; margin-right:6px; vertical-align:middle;"></div>
                                        <span class="loc-text" style="font-weight:600; color:#1e293b;">{{ $h->lat_group }}°N, {{ $h->lng_group }}°E</span>
                                        <span class="geo-text" style="display:block; font-size:.86rem; color:#64748b; margin-left:22px;"></span>
                                    </td>
                                    <td style="padding:9px 14px; border-bottom:1px solid #f5f5f5; vertical-align:top; font-weight:600; color:#374151;">{{ $h->incident_count }}</td>
                                    <td style="padding:9px 14px; border-bottom:1px solid #f5f5f5; vertical-align:top;">
                                        @if($h->severe_count > 0)
                                        <span style="display:inline-block; padding:2px 9px; border-radius:20px; font-size:.82rem; font-weight:700; background:#fef2f2; color:#b91c1c;">{{ $h->severe_count }}</span>
                                        @else
                                        <span style="color:#9ca3af;">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding:9px 14px; color:#6b7280; font-size:.85rem;">No incidents recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{-- Patrollers panel — rows are moved live between the two tables by
         upsertPatrolRow() in the script below as status changes arrive. --}}
            <div class="patrollers-panel" id="patrollersPanel">
                <div class="panel-card">
                    <div class="panel-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>STAND BY</th>
                                <th>LOCATION</th>
                            </tr>
                        </thead>
                        <tbody id="standByBody">
                            @forelse(($patrollers ?? collect())->where('status', 'off_duty') as $p)
                                <tr data-patrol-id="{{ $p->id }}"
                                    @if ($p->current_latitude) data-lat="{{ $p->current_latitude }}" data-lng="{{ $p->current_longitude }}" @endif>
                                    <td>{{ $p->full_name }}</td>
                                    <td>
                                        <span
                                            class="loc-text">{{ $p->current_latitude ? round($p->current_latitude, 4) . '°N, ' . round($p->current_longitude, 4) . '°E' : '—' }}</span>
                                        <span class="geo-text"
                                            style="display:block; font-size:.9rem; color:#64748b;"></span>
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-placeholder">
                                    <td colspan="2" style="color:#6b7280; font-size:.85rem;">No stand-by units</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="panel-card">
                    <div class="panel-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>IN ACTION</th>
                                <th>LOCATION</th>
                            </tr>
                        </thead>
                        <tbody id="inActionBody">
                            @forelse(($patrollers ?? collect())->where('status', 'dispatched') as $p)
                                <tr data-patrol-id="{{ $p->id }}"
                                    @if ($p->current_latitude) data-lat="{{ $p->current_latitude }}" data-lng="{{ $p->current_longitude }}" @endif>
                                    <td>{{ $p->full_name }}</td>
                                    <td>
                                        <span
                                            class="loc-text">{{ $p->current_latitude ? round($p->current_latitude, 4) . '°N, ' . round($p->current_longitude, 4) . '°E' : '—' }}</span>
                                        <span class="geo-text"
                                            style="display:block; font-size:.9rem; color:#64748b;"></span>
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-placeholder">
                                    <td colspan="2" style="color:#6b7280; font-size:.85rem;">No units in action</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="alerts-panel" id="alertsPanel">
            {{-- ACCIDENT ALERT CARDS (real DB incidents) --}}
            {{-- A new emergency reaches a screen reader through nothing at all
                 today: the panel has an audio beep and no accessible
                 announcement. This is the only assertive region on the page,
                 kept separate from the cards so the "3 minutes ago" ticker
                 cannot spam it.

                 Kept outside the collapsible body below: collapsing sets
                 display:none, which takes an element out of the accessibility
                 tree entirely — a collapsed panel would silently stop
                 announcing new emergencies. --}}
            <p id="alert-announcer" class="visually-hidden" role="status"
               aria-live="assertive" aria-atomic="true"></p>

            {{-- An overlay covers map the operator may want back. This gives it
                 back in one click without hiding the fact that alerts are
                 still open — the count stays on the collapsed bar. --}}
            <button type="button" class="alerts-panel-head" id="alertsPanelToggle"
                    aria-expanded="true" aria-controls="alertsPanelBody">
                <svg class="alerts-chevron" xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                     fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                     stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
                <span class="alerts-panel-title">Active Alerts</span>
                <span class="alerts-count" id="alertsCount">0</span>
            </button>

            <div class="alerts-panel-body" id="alertsPanelBody">

            <div id="no-incidents-banner" class="alert mb-3"
                style="background:#f0f7fa; border:1.5px solid #b8cdd9; font-size:.88rem; {{ ($pendingIncidents ?? collect())->isEmpty() ? '' : 'display:none;' }}">
                No incidents reported in the last {{ $liveWindowHours ?? 12 }} hours.
            </div>

            {{-- Everything still open from before the window. Deliberately a
                 count and a link rather than more cards: these need closing,
                 not dispatching, and drawing them as live alerts is what made
                 the board untrustworthy in the first place. --}}
            @if (($backlogCount ?? 0) > 0)
                <div class="alert mb-3 d-flex align-items-center justify-content-between gap-2"
                     style="background:#fffbeb; border:1px solid #fcd34d; color:#78350f; font-size:.85rem;">
                    <span>
                        <strong>{{ $backlogCount }}</strong>
                        older {{ Str::plural('incident', $backlogCount) }}
                        still open from before the last {{ $liveWindowHours ?? 12 }} hours.
                    </span>
                    <a href="{{ route('toc.incidents.index') }}"
                       style="color:#78350f; font-weight:600; white-space:nowrap;">Review them →</a>
                </div>
            @endif
            <div id="incident-cards-row" class="row g-3 mb-3"
                style="{{ ($pendingIncidents ?? collect())->isEmpty() ? 'display:none;' : '' }}">
                @foreach ($pendingIncidents ?? [] as $inc)
                    @php
                        // Every card used to be the same pink whether the
                        // incident was a fatal collision or a low-severity
                        // fall — 8 critical and 8 low looked identical on a
                        // board whose whole job is triage. Same palette as the
                        // map markers and the incidents list.
                        $sev = [
                            'critical' => ['#b91c1c', '#fef2f2'],
                            'high'     => ['#c2410c', '#fff7ed'],
                            'medium'   => ['#a16207', '#fefce8'],
                            'low'      => ['#15803d', '#f0fdf4'],
                        ][$inc->severity] ?? ['#64748b', '#f8fafc'];

                        $statusChip = [
                            'pending'    => ['#b91c1c', 'PENDING'],
                            'dispatched' => ['#2a7c5b', 'DISPATCHED'],
                            'arrived'    => ['#5b21b6', 'ON SCENE'],
                        ][$inc->status] ?? ['#64748b', strtoupper($inc->status)];
                    @endphp
                    {{-- One per row: the overlay is a narrow rail, and a
                         two-up grid inside it wrapped every field onto its own
                         line. --}}
                    <div class="col-12" data-incident-id="{{ $inc->id }}"
                        data-reported-at="{{ $inc->created_at->toISOString() }}"
                        data-severity="{{ $inc->severity }}">
                        <div class="p-3 position-relative rounded-3 alert-card"
                            style="background:{{ $sev[1] }}; border-left:5px solid {{ $sev[0] }};">
                            <span
                                class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                                style="top:12px; right:12px; width:28px; height:28px; background:{{ $sev[0] }}; font-size:1rem;">!</span>
                            <h6 class="fw-bold mb-2">Accident Alert!
                                <span class="badge ms-2"
                                    style="font-size:.88rem; background:{{ $statusChip[0] }};">
                                    {{ $statusChip[1] }}
                                </span>
                                <span class="badge ms-1"
                                      style="font-size:.88rem; background:{{ $sev[0] }};">
                                    {{ strtoupper($inc->severity) }}
                                </span>
                            </h6>
                            <div class="reported-line"
                                style="font-size:.88rem; color:#64748b; margin-top:-6px; margin-bottom:8px;">
                                Reported <span class="reported-ago">just now</span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.85rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" viewBox="0 0 24 24">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                        {{ $inc->rider?->full_name ?? 'Unknown rider' }}
                                    </div>
                                    <div class="ps-3" style="font-size:.88rem;">
                                        @if ($inc->rider?->phone_number)
                                            {{-- Click-to-call: the number was
                                                 plain text, so an operator
                                                 re-typed it by hand from a
                                                 crash alert. --}}
                                            <a href="tel:{{ preg_replace('/\D/', '', $inc->rider->phone_number) }}"
                                               class="text-dark fw-semibold" style="text-decoration:none;">
                                                {{ $inc->rider->phone_number }}
                                            </a>
                                        @else
                                            <span class="text-dark">&mdash;</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.85rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" viewBox="0 0 24 24">
                                            <circle cx="12" cy="10" r="3" />
                                            <path
                                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                                        </svg>
                                        Current Location
                                    </div>
                                    <div class="ps-3 lh-sm text-dark" style="font-size:.88rem;">
                                        {{ $inc->address ?? 'Unknown' }}</div>
                                </div>
                            </div>
                            {{-- Dispatch patrol (only for pending incidents) --}}
                            @if ($inc->status === 'pending' && ($patrollers ?? collect())->isNotEmpty())
                                <form method="POST" action="{{ route('toc.incidents.dispatch', $inc) }}"
                                    class="mt-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <select name="patrol_unit_id" class="form-select form-select-sm"
                                            style="font-size:.86rem;" required>
                                            <option value="">Select patrol unit…</option>
                                            @foreach ($patrollers as $p)
                                                <option value="{{ $p->id }}">{{ $p->full_name }}
                                                    ({{ $p->badge_number }})
                                                    — {{ $p->status }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm text-white fw-bold"
                                            style="background:#1b3d52; font-size:.86rem;">Dispatch</button>
                                    </div>
                                </form>
                            @elseif($inc->status === 'dispatched')
                                <div class="dispatch-note mt-2" style="font-size:.86rem; color:#2a7c5b;">
                                    ✓ Patrol dispatched: {{ $inc->patrolUnit?->full_name ?? '—' }}
                                </div>
                            @elseif($inc->status === 'arrived')
                                {{-- The unit has reported itself on scene. Without this
                                     branch an arrived incident showed no responder line at
                                     all, which reads on the board as "nobody is going". --}}
                                <div class="dispatch-note mt-2 fw-bold" style="font-size:.86rem; color:#5b21b6;">
                                    ● On scene: {{ $inc->patrolUnit?->full_name ?? '—' }}
                                    @if ($inc->arrived_at)
                                        <span class="fw-normal" style="color:#64748b;">since
                                            {{ $inc->arrived_at->format('h:i A') }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- The TOC incident view, not the investigation
                                 report: that page carries scene photographs and
                                 IRF records, which a dispatcher has no need of
                                 and which are not theirs to hold. --}}
                            <a href="{{ route('toc.incidents.show', $inc) }}"
                               class="d-inline-block mt-2"
                               style="font-size:.8rem; color:#1b3d52; font-weight:600; text-decoration:none;">
                                View incident →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Server-side data the external script needs — kept to the minimum:
         the 3 collections and 1 route template that genuinely can't be
         computed in plain JS. Everything else that used to be inline lives
         in public/js/toc/location.js now. --}}
    <script>
        window.LocationTrackingConfig = {
            pendingIncidents: @json($pendingIncidents ?? []),
            patrollers: @json($patrollers ?? []),
            // The same ranked, tiered areas the Accident Prone Area panel
            // lists. The map used to plot every raw incident coordinate through
            // Google's heatmap layer instead, so the map and the panel were two
            // different answers to the same question; now they are one, and the
            // page no longer ships one row per incident forever.
            incidentHotspots: @json($incidentHotspots ?? []),
            dispatchUrlTemplate: @json(route('toc.incidents.dispatch', ['incident' => '__ID__'])),
            // The TOC-side incident view. Pointedly not the investigation
            // report page, which the operator cannot open and which holds
            // evidentiary material they do not need to dispatch a unit.
            incidentReportUrlTemplate: @json(route('toc.incidents.show', ['incident' => '__ID__'])),
        };
    </script>
    <script src="{{ asset('js/toc/location.js') }}?v={{ filemtime(public_path('js/toc/location.js')) }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initMap"
        async defer
        onerror="document.getElementById('map').innerHTML = '&lt;div style=&quot;padding:20px;color:#b91c1c;font-size:.85rem;&quot;&gt;Failed to load Google Maps. Check your internet connection or API key.&lt;/div&gt;'">
    </script>
@endpush
