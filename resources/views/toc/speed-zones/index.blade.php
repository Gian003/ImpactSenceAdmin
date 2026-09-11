@extends('toc.layouts.app')

@section('title', 'Speed Zones')

@push('styles')
<link href="{{ asset('css/toc/speed-zones.css') }}" rel="stylesheet">
@endpush

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-dismissible py-2 mb-4" role="alert"
     style="background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; font-size:.84rem; border-radius:8px;">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-dismissible py-2 mb-4" role="alert"
     style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; font-size:.84rem; border-radius:8px;">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<p class="text-muted mb-3" style="font-size:.85rem; max-width:680px; color:#475569 !important;">
    Trace a stretch of road and give it a posted limit. GPS speed samples recorded within the
    corridor's half-width of that centreline, over the last {{ $windowDays ?? 7 }} days, are
    compared against the limit.
    Samples are anonymous — they carry no rider or device identity, so this page can show that a
    street runs over its limit but never who was riding.
</p>

{{-- An empty comparison table reads as a broken feature unless the page says
     why it is empty. Shown only while nothing has ever been recorded. --}}
@if(($totalSamples ?? 0) === 0)
<div class="alert d-flex align-items-start gap-2 mb-4"
     style="background:#fffbeb; border:1px solid #fcd34d; font-size:.83rem; color:#78350f;">
    <span style="font-size:1rem; line-height:1.2;">⚠</span>
    <div>
        <strong>No speed samples have been recorded yet.</strong>
        Zones below can be defined and will start reporting as soon as riders travel through them
        with the ImpactSense app open in navigation. Until then every zone reads
        &ldquo;No data&rdquo; — that is the absence of samples, not a fault in the zone.
    </div>
</div>
@endif

{{-- ── ADD / EDIT ZONE ───────────────────────────────────────────────────────
     One form for both. $editing is set when the page is opened with ?edit={id};
     the fields, validation and map picker are then identical to the add case,
     which is the point — a separate edit form drifts out of step with this one. --}}
<h6 class="fw-bold mb-2" style="color:#1e293b;">
    {{ $editing ? 'Edit Speed Zone' : 'Add Speed Zone' }}
</h6>

@if($errors->any())
<div class="alert mb-3" style="background:#fef2f2; border:1px solid #fca5a5; font-size:.83rem; color:#7f1d1d;">
    @foreach($errors->all() as $error)
    <div>{{ $error }}</div>
    @endforeach
</div>
@endif

{{-- Four decisions, shown as four steps. The tracing step in particular
     had no way of telling an operator it was finished — the map just sat
     there — so its status line carries the length and point count. States
     are driven live from the form by the script at the bottom. --}}
<div class="zone-steps" id="zone-steps">
    <div class="zone-step" data-step="name">
        <span class="zone-step-num">1</span>
        <span class="zone-step-label">Name the zone
            <span class="zone-step-hint">e.g. Urdaneta Bypass Road</span>
        </span>
    </div>
    <div class="zone-step" data-step="path">
        <span class="zone-step-num">2</span>
        <span class="zone-step-label">Trace the road
            <span class="zone-step-hint">Not traced yet</span>
        </span>
    </div>
    <div class="zone-step" data-step="limits">
        <span class="zone-step-num">3</span>
        <span class="zone-step-label">Width &amp; speed limit
            <span class="zone-step-hint">How wide, and what is posted</span>
        </span>
    </div>
    <div class="zone-step" data-step="save">
        <span class="zone-step-num">4</span>
        <span class="zone-step-label">Save the zone
            <span class="zone-step-hint">Finish the steps above</span>
        </span>
    </div>
</div>

<div class="card border-0 rounded-3 mb-4"
     style="border:1px solid {{ $editing ? '#7B1A2E' : '#e8d5d9' }} !important;">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ $editing ? route('toc.speed-zones.update', $editing) : route('toc.speed-zones.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Zone Name</label>
                    <input type="text" name="name" id="field-name" class="form-control form-control-sm"
                           placeholder="e.g. Urdaneta Bypass Road"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('name', $editing?->name) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">
                        Half-width (m)
                    </label>
                    <input type="number" name="radius_meters" id="field-radius"
                           class="form-control form-control-sm"
                           placeholder="20" min="5" max="500"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('radius_meters', $editing?->radius_meters ?? 20) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Limit (kph)</label>
                    <input type="number" name="speed_limit_kph" id="field-limit" class="form-control form-control-sm"
                           placeholder="40" min="1" max="200"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('speed_limit_kph', $editing?->speed_limit_kph) }}" required>
                </div>
            </div>

            {{-- Map picker. The coordinate boxes are gone: a corridor is a line
                 with a dozen vertices, and nobody types that. The drawn path
                 travels in a hidden field as JSON. --}}
            <input type="hidden" name="path" id="field-path"
                   value="{{ old('path') ? json_encode(old('path')) : ($editing ? json_encode($editing->path) : '') }}">

            <div class="tracer-bar">
                <button type="button" id="btn-draw" class="tracer-btn tracer-btn--primary">
                    Trace road
                </button>
                {{-- A misplaced point used to mean starting the whole road
                     again, which is why this exists. --}}
                <button type="button" id="btn-undo" class="tracer-btn" disabled>
                    Undo point
                </button>
                <button type="button" id="btn-clear" class="tracer-btn" disabled>
                    Clear
                </button>
                <span class="tracer-hint" id="tracer-hint">
                    Existing zones are drawn on the map so you can see what is already covered.
                </span>
            </div>

            {{-- Checked as you draw rather than only on submit, so an overlap
                 is caught before the round trip. Advisory: the server decides. --}}
            <div class="tracer-warning" id="tracer-warning">
                <span>⚠</span>
                <span id="tracer-warning-text"></span>
            </div>
            <div id="zone-picker-map" style="width:100%; height:620px; border-radius:10px; border:1px solid #e8d5d9; background:#f1f5f9; margin-bottom:16px;"></div>

            {{-- Off by default: overlapping zones double-count every sample in
                 the shared area, and a flagged street then gives no clue which
                 posted limit the reading was judged against. Ticking it is for
                 the deliberate case of a small zone inside a larger stretch. --}}
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1"
                       name="allow_overlap" id="allow-overlap"
                       {{ old('allow_overlap') ? 'checked' : '' }}
                       style="border-color:#c8d8e4;">
                <label class="form-check-label" for="allow-overlap"
                       style="font-size:.79rem; color:#475569;">
                    Allow this corridor to overlap an existing zone
                    <span style="color:#6b7280;">— samples in the shared area will count toward both</span>
                </label>
            </div>

            <button type="submit" class="btn btn-sm text-white fw-semibold px-4"
                    style="background:#7B1A2E; border-color:#7B1A2E; font-size:.82rem; border-radius:7px;">
                {{ $editing ? 'Save Changes' : 'Add Zone' }}
            </button>
            @if($editing)
            <a href="{{ route('toc.speed-zones.index') }}" class="btn btn-sm px-3"
               style="font-size:.82rem; color:#475569; text-decoration:none;">Cancel</a>
            @endif
        </form>
    </div>
</div>

{{-- ── EXISTING ZONES ────────────────────────────────────────────────────────── --}}
<h6 class="fw-bold mb-2" style="color:#1e293b;">Registered Zones</h6>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Zone Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Midpoint</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Coverage</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Speed Limit</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Observed Speed</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Speed Gauge</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Status</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Added By</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($zones ?? [] as $zone)
                @php
                    $stat      = ($speedZoneStats ?? collect())->get($zone->id);
                    $avgSpeed  = $stat?->avg_speed;
                    $p85       = $stat?->percentile_speed;
                    $maxSpeed  = $stat?->max_speed;
                    $samples   = $stat?->sample_count ?? 0;
                    $violating = $stat?->is_violating ?? false;
                    $reason    = $stat?->flag_reason;
                    $rate      = $stat?->violation_rate;
                    // The gauge tracks the 85th percentile rather than the mean:
                    // it is the figure the flag is based on, so the bar and the
                    // badge should never disagree.
                    $pct       = ($p85 !== null && $zone->speed_limit_kph > 0)
                                    ? min(round(($p85 / $zone->speed_limit_kph) * 100), 140)
                                    : null;
                    $barColor  = $p85 === null ? '#d1d5db' : ($violating ? '#b91c1c' : '#15803d');
                @endphp
                <tr>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:600; border-bottom:1px solid #f5eeef;">
                        {{ $zone->name }}
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef; font-size:.78rem; font-family:monospace;">
                        {{ round($zone->latitude, 4) }}°N, {{ round($zone->longitude, 4) }}°E
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        @php $len = $zone->pathLengthMeters(); @endphp
                        @if($len > 0)
                            {{ $len >= 1000 ? round($len / 1000, 2) . ' km' : round($len) . ' m' }} of road
                            <div style="font-size:.68rem; color:#6b7280;">±{{ $zone->radius_meters }} m wide</div>
                        @else
                            {{-- A zone still on its original single point. --}}
                            point &middot; {{ $zone->radius_meters }} m
                            <div style="font-size:.68rem; color:#b45309;">not traced yet</div>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fce7f3; color:#7B1A2E;">
                            {{ $zone->speed_limit_kph }} kph
                        </span>
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; color:#374151; font-size:.8rem; white-space:nowrap;">
                        @if($p85 !== null)
                        <div style="font-weight:600;">{{ $p85 }} kph
                            <span style="font-weight:400; color:#6b7280; font-size:.72rem;">85th pct</span>
                        </div>
                        <div style="font-size:.68rem; color:#6b7280; margin-top:1px;">
                            avg {{ $avgSpeed }} &middot; max {{ $maxSpeed }} kph
                        </div>
                        <div style="font-size:.68rem; color:#6b7280;">
                            {{ number_format($samples) }} sample{{ $samples !== 1 ? 's' : '' }}
                            &middot; {{ $rate }}% over limit
                        </div>
                        @else
                        —
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; min-width:110px;">
                        @if($pct !== null)
                        <div role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
                             aria-label="{{ $zone->name }}: {{ $pct }}% of speed limit{{ $violating ? ', exceeding limit' : '' }}"
                             style="position:relative; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                            <div style="position:absolute; top:0; left:0; height:100%; width:{{ min($pct, 100) }}%; background:{{ $barColor }}; border-radius:4px;"></div>
                        </div>
                        <div style="font-size:.67rem; color:#6b7280; margin-top:2px;">85th pct = {{ $pct }}% of limit</div>
                        @else
                        <div style="font-size:.74rem; color:#6b7280; font-style:italic;">no data</div>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        @if($avgSpeed === null)
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:600; background:#f1f5f9; color:#475569;">No data</span>
                        @elseif($violating && $reason === 'typical')
                            {{-- The street as a whole runs over its limit. --}}
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:700; background:#fef2f2; color:#b91c1c;">⚠ Typically over</span>
                            <div style="font-size:.66rem; color:#6b7280; margin-top:2px;">85th pct {{ $p85 - $zone->speed_limit_kph }} kph over</div>
                        @elseif($violating && $reason === 'tail')
                            {{-- Most riders comply; a persistent minority does not.
                                 Invisible to both the mean and the percentile, which
                                 is exactly why it gets its own badge. --}}
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:700; background:#fff7ed; color:#c2410c;">⚠ Speeding minority</span>
                            <div style="font-size:.66rem; color:#6b7280; margin-top:2px;">{{ $rate }}% over, peak {{ $maxSpeed }} kph</div>
                        @else
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:700; background:#f0fdf4; color:#2a7c5b;">✓ OK</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        {{ $zone->creator?->full_name ?? '—' }}
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; text-align:right;">
                        <div class="d-flex gap-3 justify-content-end align-items-center">
                            <a href="{{ route('toc.speed-zones.index', ['edit' => $zone->id]) }}#zone-picker-map"
                               style="color:#1b3d52; font-size:.78rem; font-weight:600; text-decoration:none;">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('toc.speed-zones.destroy', $zone) }}"
                                  onsubmit="return confirm('Remove &quot;{{ $zone->name }}&quot;? Its recorded samples stay, but nothing will be compared against a limit here.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="background:none; border:none; color:#991b1b; font-size:.78rem; font-weight:600; cursor:pointer; padding:0;">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No speed zones defined yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Existing zones, handed to the picker as data rather than generated as
// JavaScript inside a Blade loop: one JSON blob beats emitting code per zone.
// (Careful with the word "at-json" in comments here — Blade compiles this
// file before the browser ever sees it, directives inside <script> included.)
const ZONE_OVERLAYS = @json($zoneOverlays ?? []);
let _pickerMap = null;
let _drawnPath = [];          // [{lat, lng}, ...] — what the hidden field carries
let _drawnLine = null;        // the centreline the operator traced
let _corridorShapes = [];     // the half-width band drawn around it
let _clickListener = null;
let _tracing = false;

const $ = id => document.getElementById(id);
const pathField = () => $('field-path');

function halfWidth() {
    const v = parseInt($('field-radius')?.value, 10);
    return Number.isFinite(v) && v > 0 ? v : 20;
}

/* ── Geometry, mirroring SpeedZone on the server ──────────────────────────
   Only enough of it to warn about an overlap while drawing. The server does
   the real check, including road crossings this deliberately ignores; the
   point here is to catch the obvious case before a round trip, not to be the
   authority on it. */

const M_PER_DEG_LAT = 111320;

function pointToSegmentMeters(pt, a, b) {
    const mPerDegLng = M_PER_DEG_LAT * Math.cos(pt.lat * Math.PI / 180);
    const ax = (a.lng - pt.lng) * mPerDegLng, ay = (a.lat - pt.lat) * M_PER_DEG_LAT;
    const bx = (b.lng - pt.lng) * mPerDegLng, by = (b.lat - pt.lat) * M_PER_DEG_LAT;
    const dx = bx - ax, dy = by - ay;
    const len2 = dx * dx + dy * dy;

    if (len2 <= 0) return Math.hypot(ax, ay);

    const t = Math.max(0, Math.min(1, -(ax * dx + ay * dy) / len2));
    return Math.hypot(ax + t * dx, ay + t * dy);
}

function pathToPathMeters(p1, p2) {
    if (!p1.length || !p2.length) return Infinity;

    const segs = path => path.length === 1
        ? [[path[0], path[0]]]
        : path.slice(1).map((pt, i) => [path[i], pt]);

    let min = Infinity;
    for (const pt of p1) for (const [a, b] of segs(p2)) min = Math.min(min, pointToSegmentMeters(pt, a, b));
    for (const pt of p2) for (const [a, b] of segs(p1)) min = Math.min(min, pointToSegmentMeters(pt, a, b));
    return min;
}

/** Warns if what has been drawn would be refused on submit. */
function checkOverlap() {
    const box = $('tracer-warning');
    if (!_drawnPath.length) { box.classList.remove('show'); return; }

    const mine = halfWidth();
    for (const zone of ZONE_OVERLAYS) {
        if (zone.editing) continue;   // a zone cannot conflict with itself

        const gap = pathToPathMeters(_drawnPath, zone.path);
        if (gap < mine + zone.radius) {
            $('tracer-warning-text').innerHTML =
                `This comes within <b>${Math.round(gap)} m</b> of <b>${zone.name}</b>, ` +
                `which needs ${mine + zone.radius} m of clearance. Saving will be refused ` +
                `unless you tick &ldquo;allow overlap&rdquo; below.`;
            box.classList.add('show');
            return;
        }
    }
    box.classList.remove('show');
}

/* ── Drawing ─────────────────────────────────────────────────────────────── */

function clearCorridor() {
    _corridorShapes.forEach(s => s.setMap(null));
    _corridorShapes = [];
}

/**
 * Draws the area the zone actually covers, not just the line that was
 * clicked: each segment offset by the half-width on both sides, with a disc
 * at every vertex to round the joins — the same shape the server tests.
 */
function drawCorridor(path, metres) {
    clearCorridor();
    if (!path.length) return;

    const spherical = google.maps.geometry.spherical;
    const style = {
        map: _pickerMap, clickable: false,
        fillColor: '#1b3d52', fillOpacity: 0.18,
        strokeColor: '#1b3d52', strokeOpacity: 0.35, strokeWeight: 1,
    };

    for (let i = 1; i < path.length; i++) {
        const a = new google.maps.LatLng(path[i - 1].lat, path[i - 1].lng);
        const b = new google.maps.LatLng(path[i].lat, path[i].lng);
        const heading = spherical.computeHeading(a, b);
        _corridorShapes.push(new google.maps.Polygon({
            ...style,
            paths: [
                spherical.computeOffset(a, metres, heading + 90),
                spherical.computeOffset(b, metres, heading + 90),
                spherical.computeOffset(b, metres, heading - 90),
                spherical.computeOffset(a, metres, heading - 90),
            ],
        }));
    }

    path.forEach(p => _corridorShapes.push(new google.maps.Circle({
        ...style, center: { lat: p.lat, lng: p.lng }, radius: metres,
    })));
}

function pathLengthMeters(path) {
    let m = 0;
    for (let i = 1; i < path.length; i++) {
        m += google.maps.geometry.spherical.computeDistanceBetween(
            new google.maps.LatLng(path[i - 1].lat, path[i - 1].lng),
            new google.maps.LatLng(path[i].lat, path[i].lng));
    }
    return m;
}

function formatLength(m) {
    return m >= 1000 ? (m / 1000).toFixed(2) + ' km' : Math.round(m) + ' m';
}

/* ── Steps ───────────────────────────────────────────────────────────────── */

function setStep(name, state, hint) {
    const el = document.querySelector(`.zone-step[data-step="${name}"]`);
    if (!el) return;
    el.classList.toggle('is-done', state === 'done');
    el.classList.toggle('is-active', state === 'active');
    if (hint !== undefined) el.querySelector('.zone-step-hint').textContent = hint;
}

/**
 * Recomputes all four steps from the form's current state. Called on every
 * change rather than advanced by a wizard, so an operator who goes back and
 * empties a field sees the step reopen instead of staying falsely green.
 */
function refreshSteps() {
    const named = ($('field-name')?.value || '').trim().length > 0;
    const traced = _drawnPath.length > 0;
    const limit = parseInt($('field-limit')?.value, 10);
    const limited = Number.isFinite(limit) && limit > 0;

    setStep('name', named ? 'done' : 'active',
        named ? $('field-name').value.trim() : 'e.g. Urdaneta Bypass Road');

    let pathHint = 'Not traced yet';
    if (_tracing) {
        pathHint = _drawnPath.length
            ? `${_drawnPath.length} points, ${formatLength(pathLengthMeters(_drawnPath))} — click Finish when done`
            : 'Click along the road on the map';
    } else if (traced) {
        pathHint = _drawnPath.length === 1
            ? 'Single point — no road traced'
            : `${formatLength(pathLengthMeters(_drawnPath))} of road, ${_drawnPath.length} points`;
    }
    setStep('path', _tracing ? 'active' : (traced ? 'done' : (named ? 'active' : 'pending')), pathHint);

    setStep('limits', limited ? 'done' : (traced ? 'active' : 'pending'),
        limited ? `±${halfWidth()} m wide, ${limit} kph posted` : 'How wide, and what is posted');

    const ready = named && traced && limited;
    setStep('save', ready ? 'active' : 'pending',
        ready ? 'Ready — press Save below' : 'Finish the steps above');
}

/* ── Path state ──────────────────────────────────────────────────────────── */

function setPath(path) {
    _drawnPath = path;
    pathField().value = path.length ? JSON.stringify(path) : '';
    drawCorridor(path, halfWidth());
    $('btn-undo').disabled = !_tracing || path.length === 0;
    $('btn-clear').disabled = path.length === 0;
    checkOverlap();
    refreshSteps();
}

function syncFromLine() {
    setPath(_drawnLine.getPath().getArray()
        .map(p => ({ lat: +p.lat().toFixed(7), lng: +p.lng().toFixed(7) })));
}

function startTracing() {
    if (_tracing) { finishTracing(); return; }

    _tracing = true;
    if (_drawnLine) { _drawnLine.setMap(null); _drawnLine = null; }
    setPath([]);

    _drawnLine = new google.maps.Polyline({
        map: _pickerMap, path: [],
        strokeColor: '#1b3d52', strokeWeight: 3, editable: true,
    });

    ['insert_at', 'set_at', 'remove_at'].forEach(ev =>
        google.maps.event.addListener(_drawnLine.getPath(), ev, syncFromLine));

    _clickListener = _pickerMap.addListener('click', e => _drawnLine.getPath().push(e.latLng));

    // Double-clicking is the conventional way to end a drawn line, and it
    // must not also zoom the map underneath.
    _pickerMap.setOptions({ disableDoubleClickZoom: true });
    google.maps.event.addListenerOnce(_pickerMap, 'dblclick', finishTracing);

    $('zone-picker-map').classList.add('is-tracing');
    const btn = $('btn-draw');
    btn.textContent = 'Finish tracing';
    btn.classList.replace('tracer-btn--primary', 'tracer-btn--tracing');
    $('tracer-hint').innerHTML =
        'Click along the road to place points. <kbd>Backspace</kbd> undoes one, ' +
        '<kbd>Esc</kbd> or a double-click finishes. Points stay draggable afterwards.';
    refreshSteps();
}

function finishTracing() {
    if (!_tracing) return;
    _tracing = false;

    if (_clickListener) { google.maps.event.removeListener(_clickListener); _clickListener = null; }
    _pickerMap.setOptions({ disableDoubleClickZoom: false });
    $('zone-picker-map').classList.remove('is-tracing');

    const btn = $('btn-draw');
    btn.textContent = _drawnPath.length ? 'Trace again' : 'Trace road';
    btn.classList.replace('tracer-btn--tracing', 'tracer-btn--primary');
    $('btn-undo').disabled = true;
    $('tracer-hint').textContent = _drawnPath.length
        ? 'Drag any point to adjust the line. Existing zones are shown for comparison.'
        : 'Existing zones are drawn on the map so you can see what is already covered.';

    if (_drawnPath.length > 1) {
        const bounds = new google.maps.LatLngBounds();
        _drawnPath.forEach(p => bounds.extend(p));
        _pickerMap.fitBounds(bounds, 60);
    }
    refreshSteps();
}

function undoPoint() {
    if (!_drawnLine) return;
    const line = _drawnLine.getPath();
    if (line.getLength() > 0) line.removeAt(line.getLength() - 1);   // fires syncFromLine
}

function clearAll() {
    finishTracing();
    if (_drawnLine) { _drawnLine.setMap(null); _drawnLine = null; }
    setPath([]);
    $('btn-draw').textContent = 'Trace road';
}

/* ── Boot ────────────────────────────────────────────────────────────────── */

function initPickerMap() {
    _pickerMap = new google.maps.Map($('zone-picker-map'), {
        zoom: 15,
        center: { lat: 15.9766, lng: 120.5719 },
        mapTypeId: 'roadmap',
        styles: [
            { featureType: 'poi',     stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
        ],
    });

    // Existing zones, so coverage — and any overlap the form would reject —
    // is visible before submitting. The zone being edited is drawn in blue so
    // it reads as "the one you are moving", not another obstacle.
    ZONE_OVERLAYS.forEach(zone => {
        const colour = zone.editing ? '#2563eb' : '#7B1A2E';
        if (zone.path.length > 1) {
            new google.maps.Polyline({
                map: _pickerMap, path: zone.path, clickable: false,
                strokeColor: colour, strokeOpacity: 0.9, strokeWeight: 2,
            });
        }
        zone.path.forEach(p => new google.maps.Circle({
            map: _pickerMap, center: p, radius: zone.radius, clickable: false,
            fillColor: colour, fillOpacity: zone.editing ? 0.10 : 0.14,
            strokeColor: colour, strokeOpacity: 0.5, strokeWeight: 1,
        }));
    });

    // Whatever the form already holds: a zone being edited, or the path from
    // a submission the server bounced back.
    let initial = [];
    try { initial = JSON.parse(pathField().value || '[]'); } catch (e) { initial = []; }

    if (initial.length) {
        _drawnLine = new google.maps.Polyline({
            map: _pickerMap, path: initial,
            strokeColor: '#1b3d52', strokeWeight: 3, editable: true,
        });
        ['insert_at', 'set_at', 'remove_at'].forEach(ev =>
            google.maps.event.addListener(_drawnLine.getPath(), ev, syncFromLine));

        setPath(initial);
        $('btn-draw').textContent = 'Trace again';

        if (initial.length > 1) {
            const bounds = new google.maps.LatLngBounds();
            initial.forEach(p => bounds.extend(p));
            _pickerMap.fitBounds(bounds, 60);
        } else {
            _pickerMap.setCenter(initial[0]);
        }
    }

    $('btn-draw').addEventListener('click', startTracing);
    $('btn-undo').addEventListener('click', undoPoint);
    $('btn-clear').addEventListener('click', clearAll);

    // The band and the warning both depend on the width, so the number in
    // the box always means something visible.
    ['field-radius', 'field-limit', 'field-name'].forEach(id =>
        $(id)?.addEventListener('input', () => {
            drawCorridor(_drawnPath, halfWidth());
            checkOverlap();
            refreshSteps();
        }));

    document.addEventListener('keydown', e => {
        if (!_tracing) return;
        if (e.key === 'Escape') { e.preventDefault(); finishTracing(); }
        if (e.key === 'Backspace' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            undoPoint();
        }
    });

    refreshSteps();
}
</script>

<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=geometry&callback=initPickerMap">
</script>
@endpush
