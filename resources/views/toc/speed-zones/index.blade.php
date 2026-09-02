@extends('toc.layouts.app')

@section('title', 'Speed Zones')

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

<p class="text-muted mb-4" style="font-size:.85rem; max-width:640px; color:#475569 !important;">
    Define a posted speed limit for a street or barangay. Speed samples collected from paired
    devices within the zone's radius are compared against the limit — areas where riders average
    above it are flagged on the TOC dashboard.
</p>

{{-- ── ADD ZONE ──────────────────────────────────────────────────────────────── --}}
<h6 class="fw-bold mb-2" style="color:#1e293b;">Add Speed Zone</h6>

<div class="card border-0 rounded-3 mb-4" style="border:1px solid #e8d5d9 !important;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('toc.speed-zones.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Zone Name</label>
                    <input type="text" name="name" class="form-control form-control-sm"
                           placeholder="e.g. Urdaneta Bypass Road"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('name') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Latitude</label>
                    <input type="number" step="any" name="latitude" id="field-lat"
                           class="form-control form-control-sm"
                           placeholder="15.9755"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('latitude') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Longitude</label>
                    <input type="number" step="any" name="longitude" id="field-lng"
                           class="form-control form-control-sm"
                           placeholder="120.5651"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('longitude') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Radius (m)</label>
                    <input type="number" name="radius_meters" class="form-control form-control-sm"
                           placeholder="150" min="10" max="5000"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('radius_meters', 150) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Limit (kph)</label>
                    <input type="number" name="speed_limit_kph" class="form-control form-control-sm"
                           placeholder="40" min="1" max="200"
                           style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                           value="{{ old('speed_limit_kph') }}" required>
                </div>
            </div>

            {{-- Map picker --}}
            <div class="mt-3 mb-1" style="font-size:.75rem; color:#64748b;">
                Click on the map to fill in coordinates automatically, or type them above.
            </div>
            <div id="zone-picker-map" style="width:100%; height:620px; border-radius:10px; border:1px solid #e8d5d9; background:#f1f5f9; margin-bottom:16px;"></div>

            <button type="submit" class="btn btn-sm text-white fw-semibold px-4"
                    style="background:#7B1A2E; border-color:#7B1A2E; font-size:.82rem; border-radius:7px;">
                Add Zone
            </button>
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
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Coordinates</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Radius</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Speed Limit</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Live Avg</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Speed Gauge</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Status</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Added By</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($zones ?? [] as $zone)
                @php
                    $stat     = ($speedZoneStats ?? collect())->get($zone->id);
                    $avgSpeed = $stat?->avg_speed;
                    $samples  = $stat?->sample_count ?? 0;
                    $violating = $stat?->is_violating ?? false;
                    $pct      = ($avgSpeed !== null && $zone->speed_limit_kph > 0)
                                    ? min(round(($avgSpeed / $zone->speed_limit_kph) * 100), 140)
                                    : null;
                    $barColor = $avgSpeed === null ? '#d1d5db' : ($violating ? '#e53e3e' : '#2a7c5b');
                @endphp
                <tr>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:600; border-bottom:1px solid #f5eeef;">
                        {{ $zone->name }}
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef; font-size:.78rem; font-family:monospace;">
                        {{ round($zone->latitude, 4) }}°N, {{ round($zone->longitude, 4) }}°E
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        {{ $zone->radius_meters }} m
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fce7f3; color:#7B1A2E;">
                            {{ $zone->speed_limit_kph }} kph
                        </span>
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; color:#374151; font-size:.8rem; white-space:nowrap;">
                        {{ $avgSpeed !== null ? $avgSpeed . ' kph' : '—' }}
                        @if($samples > 0)
                        <div style="font-size:.68rem; color:#6b7280; margin-top:1px;">{{ $samples }} sample{{ $samples !== 1 ? 's' : '' }}</div>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; min-width:110px;">
                        @if($pct !== null)
                        <div role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
                             aria-label="{{ $zone->name }}: {{ $pct }}% of speed limit{{ $violating ? ', exceeding limit' : '' }}"
                             style="position:relative; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                            <div style="position:absolute; top:0; left:0; height:100%; width:{{ min($pct, 100) }}%; background:{{ $barColor }}; border-radius:4px;"></div>
                        </div>
                        <div style="font-size:.67rem; color:#6b7280; margin-top:2px;">{{ $pct }}% of limit</div>
                        @else
                        <div style="font-size:.74rem; color:#6b7280; font-style:italic;">no data</div>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        @if($avgSpeed === null)
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:600; background:#f1f5f9; color:#475569;">No data</span>
                        @elseif($violating)
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:700; background:#fef2f2; color:#b91c1c;">⚠ Speeding</span>
                        @else
                            <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.71rem; font-weight:700; background:#f0fdf4; color:#2a7c5b;">✓ OK</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        {{ $zone->creator?->full_name ?? '—' }}
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; text-align:right;">
                        <form method="POST" action="{{ route('toc.speed-zones.destroy', $zone) }}"
                              onsubmit="return confirm('Remove this speed zone?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    style="background:none; border:none; color:#991b1b; font-size:.78rem; font-weight:600; cursor:pointer; padding:0;">
                                Remove
                            </button>
                        </form>
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
let _pickerMap = null;
let _pickerMarker = null;

function initPickerMap() {
    const defaultCenter = { lat: 15.9766, lng: 120.5719 };

    _pickerMap = new google.maps.Map(document.getElementById('zone-picker-map'), {
        zoom: 15,
        center: defaultCenter,
        mapTypeId: 'roadmap',
        styles: [
            { featureType: 'poi',     stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
        ],
    });

    // Pre-fill existing zones as circle overlays so the user can see coverage
    @foreach($zones ?? [] as $zone)
    new google.maps.Circle({
        map: _pickerMap,
        center: { lat: {{ (float) $zone->latitude }}, lng: {{ (float) $zone->longitude }} },
        radius: {{ (int) $zone->radius_meters }},
        fillColor: '#7B1A2E',
        fillOpacity: 0.15,
        strokeColor: '#7B1A2E',
        strokeWeight: 1.5,
    });
    @endforeach

    _pickerMap.addListener('click', function (e) {
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();

        document.getElementById('field-lat').value = lat.toFixed(7);
        document.getElementById('field-lng').value = lng.toFixed(7);

        if (_pickerMarker) {
            _pickerMarker.setPosition(e.latLng);
        } else {
            _pickerMarker = new google.maps.Marker({
                position: e.latLng,
                map: _pickerMap,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#7B1A2E', fillOpacity: 1,
                    strokeColor: '#fff', strokeWeight: 2, scale: 9,
                },
            });
        }
    });
}
</script>

<script async
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initPickerMap">
</script>
@endpush
