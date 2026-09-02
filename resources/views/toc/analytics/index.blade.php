@extends('toc.layouts.app')

@section('title', 'Accident Analytics')

@push('styles')
<style>
.stat-card {
    background: #dce8f0;
    border: 1.5px solid #b8cdd9 !important;
    border-radius: 12px;
}
.stat-card.danger-card {
    background: #fde8e8;
    border-color: #f8b4b4 !important;
}
.stat-card.success-card {
    background: #e8f5e9;
    border-color: #a5d6a7 !important;
}
.stat-value {
    font-size: 2rem;
    font-weight: 900;
    color: #111827;
    line-height: 1;
}
.stat-label {
    font-size: .74rem;
    color: #4b5563;
    margin-top: .25rem;
}
.stat-title {
    font-size: .8rem;
    font-weight: 700;
    color: #1b3d52;
    margin-bottom: .5rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.chart-card {
    border: 1.5px solid #d1dde6;
    border-radius: 12px;
    background: #fff;
}
#accident-map {
    width: 100%;
    height: 380px;
    border-radius: 10px;
    background: #e8eef2;
}
.prone-rank {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: .68rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
    background: #1b3d52;
}
.prone-rank.r1 { background: #dc2626; }
.prone-rank.r2 { background: #f97316; }
.prone-rank.r3 { background: #eab308; color: #111; }
.toggle-btn {
    font-size: .74rem;
    border-radius: 6px;
    padding: .3rem .75rem;
    border: 1.5px solid #b8cdd9;
    background: transparent;
    color: #1b3d52;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.toggle-btn.active {
    background: #1b3d52;
    color: #fff;
    border-color: #1b3d52;
}
</style>
@endpush

@section('content')

{{-- SUMMARY STAT CARDS ────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
        <div class="card stat-card border-0 h-100 p-3">
            <div class="stat-title">Total Incidents</div>
            <div class="stat-value">{{ $total }}</div>
            <div class="stat-label">All-time recorded accidents</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card danger-card border-0 h-100 p-3">
            <div class="stat-title" style="color:#991b1b;">Critical Cases</div>
            <div class="stat-value" style="color:#dc2626;">{{ $criticalCount }}</div>
            <div class="stat-label" style="color:#7f1d1d;">High-severity incidents</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card border-0 h-100 p-3">
            <div class="stat-title">Most Prone Area</div>
            <div style="font-size:.9rem; font-weight:800; color:#111827; line-height:1.3; margin-top:.2rem;">
                {{ $pronestAreas->first()?->address ?? 'N/A' }}
            </div>
            <div class="stat-label">{{ $pronestAreas->first()?->total ?? 0 }} incidents at this location</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card success-card border-0 h-100 p-3">
            <div class="stat-title" style="color:#1a5c2a;">Resolution Rate</div>
            <div class="stat-value" style="color:#15803d;">
                {{ $total > 0 ? round($resolvedCount / $total * 100) : 0 }}%
            </div>
            <div class="stat-label" style="color:#14532d;">{{ $resolvedCount }} of {{ $total }} resolved</div>
        </div>
    </div>

</div>

{{-- HEATMAP + PRONE AREAS ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Google Maps Heatmap --}}
    <div class="col-lg-8">
        <div class="chart-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0" style="color:#1b3d52; font-size:.95rem;">Accident Hotspot Map</h6>
                    {{-- Google deprecated google.maps.visualization.HeatmapLayer in
                         May 2025 and it's been non-functional since May 2026 — no
                         drop-in replacement exists in the Maps JS API itself, so
                         this now shows individually colored markers (already a
                         fully working, separate code path) instead of a gradient. --}}
                    <small class="text-muted" style="font-size:.73rem;">
                        All {{ $heatmapPoints->count() }} recorded incident locations — color shows severity
                        (red = critical, orange = high, yellow = medium, green = low)
                    </small>
                </div>
            </div>
            <div id="accident-map"></div>
        </div>
    </div>

    {{-- Top Prone Areas --}}
    <div class="col-lg-4">
        <div class="chart-card p-3 h-100" style="overflow-y:auto; max-height:460px;">
            <h6 class="fw-bold mb-3" style="color:#1b3d52; font-size:.95rem;">
                Top Accident Prone Areas
            </h6>

            @forelse($pronestAreas as $i => $area)
            @php $pct = $pronestAreas->first()->total > 0
                    ? round($area->total / $pronestAreas->first()->total * 100) : 0; @endphp
            {{-- Clicking pans/zooms the map to this area's incidents. The
                 sidebar ranks by exact address text; the map clusters by real
                 GPS proximity, so a busy road (one address, several distinct
                 points along it) won't show as a single matching bubble on
                 the map — this is what actually connects the two views
                 instead of leaving them looking inconsistent side by side. --}}
            <div class="d-flex align-items-start gap-2 mb-3" style="cursor:pointer;"
                 onclick='flyToArea(@json($area->address))' title="Show on map">
                <div class="prone-rank r{{ $i + 1 }}">{{ $i + 1 }}</div>
                <div class="flex-grow-1 min-w-0">
                    <div style="font-size:.8rem; font-weight:600; color:#1b1b1b; line-height:1.3;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $area->address }}
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <div class="flex-grow-1" style="height:5px; border-radius:4px; background:#e2e8f0; overflow:hidden;">
                            <div style="width:{{ $pct }}%; height:100%;
                                        background:{{ $i === 0 ? '#dc2626' : ($i === 1 ? '#f97316' : '#1b3d52') }};
                                        border-radius:4px; transition:width .4s;"></div>
                        </div>
                        <span style="font-size:.74rem; font-weight:700; color:#1b3d52; min-width:22px; text-align:right;">
                            {{ $area->total }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted text-center py-4" style="font-size:.84rem;">No incident data recorded yet.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- MONTHLY TREND + SEVERITY ───────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    <div class="col-lg-8">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Monthly Incident Trend</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Last 12 months</small>
            {{-- The canvas's old height="" attribute never actually took effect —
                 Chart.js's "responsive" mode derives height from container width
                 unless maintainAspectRatio:false is set (now is, in analytics.js),
                 and that mode needs an actual sized wrapper to size against. --}}
            <div style="height:220px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Severity Breakdown</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Distribution by severity level</small>
            <div style="height:220px;">
                <canvas id="severityChart"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- DAY OF WEEK + HOUR OF DAY ──────────────────────────────────────────────── --}}
<div class="row g-3">

    <div class="col-lg-6">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Incidents by Day of Week</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Which days have the most accidents</small>
            <div style="height:200px;">
                <canvas id="dowChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Incidents by Hour of Day</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Peak hours for motorcycle accidents</small>
            <div style="height:200px;">
                <canvas id="hourChart"></canvas>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

{{-- Server-side data the external script needs. --}}
<script>
    window.TocAnalyticsConfig = {
        heatmapPoints: @json($heatmapPoints),
        byMonth: @json($byMonth),
        bySeverity: @json($bySeverity),
        byDayOfWeek: @json($byDayOfWeek),
        byHour: @json($byHour),
    };
</script>
<script src="{{ asset('js/toc/analytics.js') }}?v={{ filemtime(public_path('js/toc/analytics.js')) }}"></script>
{{-- Groups nearby markers into a numbered cluster bubble instead of letting
     them overlap as the incident count grows — must load before the async
     Google Maps script below, since initMap() (defined in analytics.js
     above) uses the markerClusterer global. --}}
<script src="https://cdn.jsdelivr.net/npm/@googlemaps/markerclusterer@2.6.2/dist/index.min.js"></script>

<script async
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initMap">
</script>
@endpush
