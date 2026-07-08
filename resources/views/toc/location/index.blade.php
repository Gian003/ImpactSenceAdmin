@extends('toc.layouts.app')

@section('title', 'Location Tracking')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/toc/location.css') }}">
@endpush

@section('content')

{{-- SUCCESS FLASH --}}
@if(session('dispatched'))
<div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.84rem;">
    {{ session('dispatched') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ACCIDENT ALERT CARDS (real DB incidents) --}}
@if(($pendingIncidents ?? collect())->isEmpty())
<div class="alert mb-3" style="background:#f0f7fa; border:1.5px solid #b8cdd9; font-size:.84rem;">
    No active incidents at this time.
</div>
@else
<div class="row g-3 mb-3">
    @foreach($pendingIncidents as $inc)
    <div class="col-md-6">
        <div class="p-3 position-relative rounded-3 border border-2"
             style="background:#fde8e8; border-color:#d97070 !important;">
            <span class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                  style="top:12px; right:12px; width:28px; height:28px; background:#1a1a1a; font-size:1rem;">!</span>
            <h6 class="fw-bold mb-2">Accident Alert!
                <span class="badge ms-2" style="font-size:.7rem; background:#{{ $inc->status === 'pending' ? 'e53e3e' : '2a7c5b' }};">
                    {{ strtoupper($inc->status) }}
                </span>
            </h6>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        {{ $inc->rider?->full_name ?? 'Unknown rider' }}
                    </div>
                    <div class="ps-3 text-dark" style="font-size:.8rem;">{{ $inc->rider?->phone_number ?? '—' }}</div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        Current Location
                    </div>
                    <div class="ps-3 lh-sm text-dark" style="font-size:.8rem;">{{ $inc->address ?? 'Unknown' }}</div>
                </div>
            </div>
            {{-- Dispatch patrol (only for pending incidents) --}}
            @if($inc->status === 'pending' && ($patrollers ?? collect())->isNotEmpty())
            <form method="POST" action="{{ route('toc.incidents.dispatch', $inc) }}" class="mt-2">
                @csrf
                <div class="input-group input-group-sm">
                    <select name="patrol_unit_id" class="form-select form-select-sm" style="font-size:.78rem;" required>
                        <option value="">Select patrol unit…</option>
                        @foreach($patrollers as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }} ({{ $p->badge_number }}) — {{ $p->status }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm text-white fw-bold"
                            style="background:#1b3d52; font-size:.78rem;">Dispatch</button>
                </div>
            </form>
            @elseif($inc->status === 'dispatched')
            <div class="mt-2" style="font-size:.78rem; color:#2a7c5b;">
                ✓ Patrol dispatched: {{ $inc->patrolUnit?->full_name ?? '—' }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- MAP + OVERLAYS --}}
<div class="map-wrap">
    <div id="map"></div>

    {{-- Legend toggle card --}}
    <div class="map-legend-card">
        <button class="legend-btn" id="btnSpeed" onclick="togglePanel('speed')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/></svg>
            Speed Reports per Area
        </button>
        <button class="legend-btn" id="btnProne" onclick="togglePanel('prone')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Accident Prone Area
        </button>
        <div class="prone-sub" id="proneSub">
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#e53e3e;"></div> High</div>
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#dd6b20;"></div> Average</div>
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#d69e2e;"></div> Low</div>
        </div>
        <button class="legend-btn" id="btnPatrollers" onclick="togglePanel('patrollers')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Patrollers
        </button>
    </div>

    {{-- Speed Reports panel — police-defined posted limits (Speed Zones)
         compared against real observed GPS speed samples within each
         zone's radius. Zones averaging above their limit are flagged and
         sorted to the top. --}}
    <div class="map-panel" id="speedPanel">
        <div class="panel-card" style="min-width:420px;">
            <table>
                <thead><tr><th>Zone</th><th>Limit</th><th>Observed Avg</th><th>Samples</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($speedZoneStats ?? [] as $z)
                    <tr>
                        <td>{{ $z->name }}</td>
                        <td>{{ $z->speed_limit_kph }} kph</td>
                        <td>{{ $z->avg_speed !== null ? $z->avg_speed . ' kph' : '—' }}</td>
                        <td>{{ $z->sample_count }}</td>
                        <td>
                            @if($z->avg_speed === null)
                                <span style="color:#9ca3af;">No data</span>
                            @elseif($z->is_violating)
                                <span style="color:#e53e3e; font-weight:700;">⚠ Speeding</span>
                            @else
                                <span style="color:#2a7c5b; font-weight:700;">OK</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="color:#9ca3af; font-size:.75rem;">No speed zones defined yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-2 text-end" style="border-top:1px solid #eee;">
                <a href="{{ route('toc.speed-zones.index') }}" style="font-size:.78rem; color:#1b3d52; font-weight:600;">
                    Manage Speed Zones →
                </a>
            </div>
        </div>
    </div>

    {{-- Accident Prone Area panel — real incidents ranked by density,
         grouped into ~111m areas (the heatmap layer plots every point;
         this ranks the areas so they're actually actionable). --}}
    <div class="map-panel" id="pronePanel">
        <div class="panel-card" style="min-width:340px;">
            <table>
                <thead><tr><th>Area</th><th>Incidents</th><th>Severe</th></tr></thead>
                <tbody>
                    @forelse($incidentHotspots ?? [] as $h)
                    <tr>
                        <td>{{ $h->lat_group }}°N, {{ $h->lng_group }}°E</td>
                        <td>{{ $h->incident_count }}</td>
                        <td>{{ $h->severe_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="color:#9ca3af; font-size:.75rem;">No incidents recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Patrollers panel — rows are moved live between the two tables by
         upsertPatrolRow() in the script below as status changes arrive. --}}
    <div class="patrollers-panel" id="patrollersPanel">
        <div class="panel-card">
            <table>
                <thead><tr><th>STAND BY</th><th>LOCATION</th></tr></thead>
                <tbody id="standByBody">
                    @forelse(($patrollers ?? collect())->where('status', 'off_duty') as $p)
                    <tr data-patrol-id="{{ $p->id }}">
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->current_latitude ? round($p->current_latitude,4).'°N, '.round($p->current_longitude,4).'°E' : '—' }}</td>
                    </tr>
                    @empty
                    <tr class="empty-placeholder"><td colspan="2" style="color:#9ca3af; font-size:.75rem;">No stand-by units</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-card">
            <table>
                <thead><tr><th>IN ACTION</th><th>LOCATION</th></tr></thead>
                <tbody id="inActionBody">
                    @forelse(($patrollers ?? collect())->where('status', 'dispatched') as $p)
                    <tr data-patrol-id="{{ $p->id }}">
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->current_latitude ? round($p->current_latitude,4).'°N, '.round($p->current_longitude,4).'°E' : '—' }}</td>
                    </tr>
                    @empty
                    <tr class="empty-placeholder"><td colspan="2" style="color:#9ca3af; font-size:.75rem;">No units in action</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Panel/legend toggling is defined at top level — independent of
// initMap() below — so the Speed Reports and Patrollers tabs work even
// if Google Maps fails to load (bad/restricted API key, no network,
// quota exceeded). Only the Accident Prone Area heatmap actually needs
// the map object, and degrades gracefully (sub-legend still shows,
// heatmap layer just won't render) if `heatmap` never gets assigned.
let map = null;
let heatmap = null;
let activePanel = null;

window.togglePanel = function(panel) {
    const els  = { speed: document.getElementById('speedPanel'), patrollers: document.getElementById('patrollersPanel'), prone: document.getElementById('pronePanel') };
    const btns = { speed: document.getElementById('btnSpeed'), prone: document.getElementById('btnProne'), patrollers: document.getElementById('btnPatrollers') };
    const proneSub = document.getElementById('proneSub');

    const closing = activePanel === panel;

    // Reset everything
    Object.values(els).forEach(e => e.classList.remove('show'));
    Object.values(btns).forEach(b => b.classList.remove('active'));
    proneSub.classList.remove('show');
    if (heatmap) heatmap.setMap(null);
    activePanel = null;

    if (closing) return;

    // Activate chosen panel
    activePanel = panel;
    btns[panel].classList.add('active');
    if (panel === 'speed')       els.speed.classList.add('show');
    if (panel === 'patrollers')  els.patrollers.classList.add('show');
    if (panel === 'prone') {
        proneSub.classList.add('show');
        els.prone.classList.add('show');
        if (heatmap) heatmap.setMap(map);
    }
};

function initMap() {
    const center = { lat: 15.9755, lng: 120.5651 };

    map = new google.maps.Map(document.getElementById('map'), {
        center,
        zoom: 14,
        mapTypeId: 'roadmap',
        zoomControl: true,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
    });

    const infoWindow = new google.maps.InfoWindow();

    const redDotIcon = {
        url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
        scaledSize: new google.maps.Size(32, 32),
    };

    function addMarker(lat, lng, name, address) {
        const marker = new google.maps.Marker({ position: { lat, lng }, map, icon: redDotIcon, title: name });
        marker.addListener('click', () => {
            infoWindow.setContent(`<b>${name}</b><br>${address}`);
            infoWindow.open(map, marker);
        });
        return marker;
    }

    // Plot real pending/dispatched incidents from the database
    const incidents = @json($pendingIncidents ?? []);
    incidents.forEach(inc => {
        const lat = parseFloat(inc.latitude);
        const lng = parseFloat(inc.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const name = inc.rider ? inc.rider.full_name : 'Unknown rider';
        const address = inc.address || 'Location unavailable';
        addMarker(lat, lng, name, address);
    });

    // Patrol unit markers — seeded from the page-load snapshot, then kept
    // live via the 'patrol-locations' Pusher channel as units report their
    // GPS position every 30s (see PatrolAuthController::updateLocation).
    const patrolIcons = {
        dispatched: { url: 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png', scaledSize: new google.maps.Size(32, 32) },
        off_duty:   { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',   scaledSize: new google.maps.Size(32, 32) },
    };
    const patrolMarkers = {}; // keyed by patrol unit id

    function upsertPatrolMarker(p) {
        const lat = parseFloat(p.current_latitude);
        const lng = parseFloat(p.current_longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const icon = patrolIcons[p.status] ?? patrolIcons.off_duty;

        if (patrolMarkers[p.id]) {
            patrolMarkers[p.id].setPosition({ lat, lng });
            patrolMarkers[p.id].setIcon(icon);
        } else {
            const marker = new google.maps.Marker({ position: { lat, lng }, map, icon, title: p.full_name });
            marker.addListener('click', () => {
                infoWindow.setContent(`<b>${p.full_name}</b><br>${p.badge_number ?? ''}<br>${p.status}`);
                infoWindow.open(map, marker);
            });
            patrolMarkers[p.id] = marker;
        }
    }

    // Stand By / In Action side tables — moves a patrol's row to whichever
    // table matches their current status, so a dispatch or resolution moves
    // the row live instead of waiting for a page refresh.
    function refreshEmptyPlaceholder(tbody, emptyText) {
        const hasRows = tbody.querySelector('tr[data-patrol-id]') !== null;
        const placeholder = tbody.querySelector('tr.empty-placeholder');
        if (hasRows && placeholder) placeholder.remove();
        if (!hasRows && !placeholder) {
            const tr = document.createElement('tr');
            tr.className = 'empty-placeholder';
            const td = document.createElement('td');
            td.colSpan = 2;
            td.style.cssText = 'color:#9ca3af; font-size:.75rem;';
            td.textContent = emptyText;
            tr.appendChild(td);
            tbody.appendChild(tr);
        }
    }

    function upsertPatrolRow(p) {
        const standByBody  = document.getElementById('standByBody');
        const inActionBody = document.getElementById('inActionBody');
        if (!standByBody || !inActionBody) return;

        document.querySelectorAll(`tr[data-patrol-id="${p.id}"]`).forEach(tr => tr.remove());

        const lat = parseFloat(p.current_latitude);
        const lng = parseFloat(p.current_longitude);
        const locationText = (!isNaN(lat) && !isNaN(lng)) ? `${lat.toFixed(4)}°N, ${lng.toFixed(4)}°E` : '—';

        const tr = document.createElement('tr');
        tr.dataset.patrolId = p.id;
        const nameTd = document.createElement('td');
        nameTd.textContent = p.full_name;
        const locTd = document.createElement('td');
        locTd.textContent = locationText;
        tr.append(nameTd, locTd);

        (p.status === 'dispatched' ? inActionBody : standByBody).appendChild(tr);

        refreshEmptyPlaceholder(standByBody, 'No stand-by units');
        refreshEmptyPlaceholder(inActionBody, 'No units in action');
    }

    function handlePatrolUpdate(p) {
        upsertPatrolMarker(p);
        upsertPatrolRow(p);
    }

    const patrollersData = @json($patrollers ?? []);
    patrollersData.forEach(handlePatrolUpdate);

    if (window.pusherClient) {
        window.pusherClient.subscribe('patrol-locations')
            .bind('patrol.location_updated', handlePatrolUpdate);
    }

    // Accident-prone heatmap, weighted by real historical incident coordinates
    // (shown when "Accident Prone Area" is toggled)
    const incidentCoords = @json($allIncidentCoords ?? []);
    const heatmapPoints = incidentCoords
        .map(i => {
            const lat = parseFloat(i.latitude);
            const lng = parseFloat(i.longitude);
            return (isNaN(lat) || isNaN(lng)) ? null : new google.maps.LatLng(lat, lng);
        })
        .filter(Boolean);

    heatmap = new google.maps.visualization.HeatmapLayer({
        data: heatmapPoints,
        radius: 40,
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=visualization&callback=initMap"
        async defer
        onerror="document.getElementById('map').innerHTML = '&lt;div style=&quot;padding:20px;color:#b91c1c;font-size:.85rem;&quot;&gt;Failed to load Google Maps. Check your internet connection or API key.&lt;/div&gt;'"></script>
@endpush
