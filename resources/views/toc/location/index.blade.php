@extends('toc.layouts.app')

@section('title', 'Location Tracking')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/toc/location.css') }}?v={{ filemtime(public_path('css/toc/location.css')) }}">
@endpush

@section('content')

    {{-- SUCCESS FLASH --}}
    @if (session('dispatched'))
        <div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.88rem;">
            {{ session('dispatched') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Map first (full width, sized to the viewport) so it's always fully
     visible without scrolling past anything; alert cards sit in their own
     capped, scrollable strip below it instead of pushing the map around —
     see location.css. Map markup comes before the alert cards in the HTML
     here (not just visually) so tab order and screen readers match what's
     shown on screen. --}}
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
                    <button class="legend-btn" id="btnSpeed" onclick="togglePanel('speed')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            viewBox="0 0 24 24">
                            <path d="M12 2a10 10 0 1 0 10 10" />
                            <path d="M12 6v6l4 2" />
                        </svg>
                        Speed Reports per Area
                    </button>
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
                </div>
            </div>

            {{-- Speed Reports panel — police-defined posted limits (Speed Zones)
         compared against real observed GPS speed samples within each
         zone's radius. Zones averaging above their limit are flagged and
         sorted to the top. --}}
            <div class="map-panel" id="speedPanel">
                <div class="panel-card" style="min-width:520px; max-width:600px;">
                    {{-- Panel header --}}
                    <div
                        style="background:#7B1A2E; padding:10px 16px; display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none"
                                stroke="#F4C5D0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                viewBox="0 0 24 24">
                                <path d="M12 2a10 10 0 1 0 10 10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            <span style="color:#fff; font-size:.88rem; font-weight:700; letter-spacing:.03em;">Speed
                                Reports per Area</span>
                        </div>
                        <span style="font-size:.88rem; color:#F4C5D0;">
                            {{ ($speedZoneStats ?? collect())->count() }}
                            zone{{ ($speedZoneStats ?? collect())->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    {{-- Scrollable body --}}
                    <div style="max-height:260px; overflow-y:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th
                                        style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">
                                        Zone</th>
                                    <th
                                        style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">
                                        Limit</th>
                                    <th
                                        style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">
                                        Observed Avg</th>
                                    <th
                                        style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">
                                        Speed Gauge</th>
                                    <th
                                        style="padding:9px 14px; background:#7B1A2E; color:#fff; font-size:.85rem; font-weight:700; border:none; white-space:nowrap;">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($speedZoneStats ?? [] as $z)
                                    @php
                                        $pct =
                                            $z->avg_speed !== null && $z->speed_limit_kph > 0
                                                ? min(round(($z->avg_speed / $z->speed_limit_kph) * 100), 140)
                                                : null;
                                        $barColor =
                                            $z->avg_speed === null
                                                ? '#d1d5db'
                                                : ($z->is_violating
                                                    ? '#e53e3e'
                                                    : '#2a7c5b');
                                    @endphp
                                    <tr>
                                        <td
                                            style="padding:10px 14px; font-size:.88rem; color:#1e293b; font-weight:600; border-bottom:1px solid #f5eeef; max-width:140px; word-break:break-word;">
                                            {{ $z->name }}
                                        </td>
                                        <td
                                            style="padding:10px 14px; font-size:.88rem; color:#475569; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                                            <span
                                                style="display:inline-block; padding:2px 9px; border-radius:20px; font-size:.88rem; font-weight:600; background:#fce7f3; color:#7B1A2E;">
                                                {{ $z->speed_limit_kph }} kph
                                            </span>
                                        </td>
                                        <td
                                            style="padding:10px 14px; font-size:.88rem; color:#374151; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                                            {{ $z->avg_speed !== null ? number_format($z->avg_speed, 1) . ' kph' : '—' }}
                                            @if ($z->avg_speed !== null && $z->sample_count > 0)
                                                <div style="font-size:.86rem; color:#6b7280; margin-top:1px;">
                                                    {{ $z->sample_count }} sample{{ $z->sample_count !== 1 ? 's' : '' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:10px 14px; border-bottom:1px solid #f5eeef; min-width:100px;">
                                            @if ($pct !== null)
                                                <div role="progressbar" aria-valuenow="{{ $pct }}"
                                                    aria-valuemin="0" aria-valuemax="100"
                                                    aria-label="{{ $z->name }}: {{ $pct }}% of speed limit{{ $z->is_violating ? ', exceeding limit' : '' }}"
                                                    style="position:relative; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden; min-width:80px;">
                                                    <div
                                                        style="position:absolute; top:0; left:0; height:100%; width:{{ min($pct, 100) }}%; background:{{ $barColor }}; border-radius:4px; transition:width .3s;">
                                                    </div>
                                                    @if ($z->is_violating)
                                                        <div
                                                            style="position:absolute; top:0; left:71.4%; height:100%; width:1.5px; background:#7B1A2E; opacity:.7;">
                                                        </div>
                                                    @endif
                                                </div>
                                                <div style="font-size:.86rem; color:#6b7280; margin-top:2px;">
                                                    {{ $pct }}% of limit</div>
                                            @else
                                                <div style="font-size:.88rem; color:#6b7280; font-style:italic;">no data
                                                </div>
                                            @endif
                                        </td>
                                        <td
                                            style="padding:10px 14px; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                                            @if ($z->avg_speed === null)
                                                <span
                                                    style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.88rem; font-weight:600; background:#f1f5f9; color:#475569;">No
                                                    data</span>
                                            @elseif($z->is_violating)
                                                <span
                                                    style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.88rem; font-weight:700; background:#fef2f2; color:#b91c1c;">⚠
                                                    Speeding</span>
                                            @else
                                                <span
                                                    style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.88rem; font-weight:700; background:#f0fdf4; color:#2a7c5b;">✓
                                                    OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            style="padding:24px 16px; text-align:center; color:#6b7280; font-size:.86rem;">
                                            No speed zones defined yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer --}}
                    <div
                        style="padding:8px 14px; border-top:1px solid #f5eeef; display:flex; align-items:center; justify-content:space-between; background:#fafafa;">
                        <span style="font-size:.88rem; color:#6b7280;">Progress bar = observed avg vs posted limit</span>
                        <a href="{{ route('toc.speed-zones.index') }}"
                            style="font-size:.86rem; color:#7B1A2E; font-weight:700; text-decoration:none;">
                            Manage Zones →
                        </a>
                    </div>
                </div>
            </div>

            {{-- Accident Prone Area panel — real incidents ranked by density,
         grouped into ~111m areas (the heatmap layer plots every point;
         this ranks the areas so they're actually actionable). --}}
            <div class="map-panel" id="pronePanel">
                <div class="panel-card" style="min-width:420px; max-width:480px;">
                    {{-- Panel header — matches the Speed Reports panel's treatment
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

                    {{-- Scrollable body — capped the same way as Speed Reports, so
                         a full top-10 list of two-line rows (coordinates + geocoded
                         address) doesn't grow the panel past a comfortable height. --}}
                    <div style="max-height:260px; overflow-y:auto;">
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
                <div class="panel-card">
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

        <div class="alerts-panel">
            {{-- ACCIDENT ALERT CARDS (real DB incidents) --}}
            <div id="no-incidents-banner" class="alert mb-3"
                style="background:#f0f7fa; border:1.5px solid #b8cdd9; font-size:.88rem; {{ ($pendingIncidents ?? collect())->isEmpty() ? '' : 'display:none;' }}">
                No active incidents at this time.
            </div>
            <div id="incident-cards-row" class="row g-3 mb-3"
                style="{{ ($pendingIncidents ?? collect())->isEmpty() ? 'display:none;' : '' }}">
                @foreach ($pendingIncidents ?? [] as $inc)
                    <div class="col-md-6" data-incident-id="{{ $inc->id }}"
                        data-reported-at="{{ $inc->created_at->toISOString() }}">
                        <div class="p-3 position-relative rounded-3 border border-2"
                            style="background:#fde8e8; border-color:#d97070 !important;">
                            <span
                                class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                                style="top:12px; right:12px; width:28px; height:28px; background:#1a1a1a; font-size:1rem;">!</span>
                            <h6 class="fw-bold mb-2">Accident Alert!
                                <span class="badge ms-2"
                                    style="font-size:.88rem; background:#{{ $inc->status === 'pending' ? 'b91c1c' : '2a7c5b' }};">
                                    {{ strtoupper($inc->status) }}
                                </span>
                                @if ($inc->severity === 'critical')
                                    <span class="badge ms-1" style="font-size:.88rem; background:#7B1A2E;">CRITICAL</span>
                                @endif
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
                                    <div class="ps-3 text-dark" style="font-size:.88rem;">
                                        {{ $inc->rider?->phone_number ?? '—' }}</div>
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
                            @endif
                        </div>
                    </div>
                @endforeach
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
            allIncidentCoords: @json($allIncidentCoords ?? []),
            dispatchUrlTemplate: @json(route('toc.incidents.dispatch', ['incident' => '__ID__'])),
        };
    </script>
    <script src="{{ asset('js/toc/location.js') }}?v={{ filemtime(public_path('js/toc/location.js')) }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=visualization&callback=initMap"
        async defer
        onerror="document.getElementById('map').innerHTML = '&lt;div style=&quot;padding:20px;color:#b91c1c;font-size:.85rem;&quot;&gt;Failed to load Google Maps. Check your internet connection or API key.&lt;/div&gt;'">
    </script>
@endpush
