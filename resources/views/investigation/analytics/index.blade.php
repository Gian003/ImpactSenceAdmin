@extends('investigation.layouts.app')

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

    <div class="col-lg-8">
        <div class="chart-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0" style="color:#1b3d52; font-size:.95rem;">Accident Hotspot Map</h6>
                    <small class="text-muted" style="font-size:.73rem;">
                        All {{ $heatmapPoints->count() }} recorded incident locations — red zones are high-risk areas
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <button class="toggle-btn active" id="btn-heatmap" onclick="setMode('heatmap')">Heatmap</button>
                    <button class="toggle-btn" id="btn-markers" onclick="setMode('markers')">Markers</button>
                </div>
            </div>
            <div id="accident-map"></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card p-3 h-100" style="overflow-y:auto; max-height:460px;">
            <h6 class="fw-bold mb-3" style="color:#1b3d52; font-size:.95rem;">Top Accident Prone Areas</h6>

            @forelse($pronestAreas as $i => $area)
            @php $pct = $pronestAreas->first()->total > 0
                    ? round($area->total / $pronestAreas->first()->total * 100) : 0; @endphp
            <div class="d-flex align-items-start gap-2 mb-3">
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
                                        border-radius:4px;"></div>
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
            <canvas id="monthlyChart" height="110"></canvas>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Severity Breakdown</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Distribution by severity level</small>
            <canvas id="severityChart" height="190"></canvas>
        </div>
    </div>

</div>

{{-- DAY OF WEEK + HOUR OF DAY ──────────────────────────────────────────────── --}}
<div class="row g-3">

    <div class="col-lg-6">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Incidents by Day of Week</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Which days have the most accidents</small>
            <canvas id="dowChart" height="150"></canvas>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card p-3">
            <h6 class="fw-bold mb-1" style="color:#1b3d52; font-size:.95rem;">Incidents by Hour of Day</h6>
            <small class="text-muted d-block mb-3" style="font-size:.73rem;">Peak hours for motorcycle accidents</small>
            <canvas id="hourChart" height="150"></canvas>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
const RAW_HEATMAP  = @json($heatmapPoints);
const RAW_MONTHLY  = @json($byMonth);
const RAW_SEVERITY = @json($bySeverity);
const RAW_DOW      = @json($byDayOfWeek);
const RAW_HOUR     = @json($byHour);

Chart.defaults.font.family = 'system-ui, sans-serif';
Chart.defaults.font.size   = 11;

// Monthly trend
(function () {
    const labels = [], counts = {};
    for (let i = 11; i >= 0; i--) {
        const d = new Date();
        d.setMonth(d.getMonth() - i);
        const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        labels.push(d.toLocaleString('default', { month: 'short', year: '2-digit' }));
        counts[key] = 0;
    }
    RAW_MONTHLY.forEach(r => { if (counts[r.month] !== undefined) counts[r.month] = r.total; });
    const vals = Object.values(counts), max = Math.max(...vals);
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Incidents', data: vals,
            backgroundColor: vals.map(v => v === max ? '#dc2626' : '#1b3d52'),
            borderRadius: 5, borderSkipped: false }] },
        options: { responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                      x: { grid: { display: false } } } }
    });
})();

// Severity donut
(function () {
    const cm = { critical:'#dc2626', high:'#f97316', medium:'#eab308', low:'#22c55e', minor:'#22c55e' };
    new Chart(document.getElementById('severityChart'), {
        type: 'doughnut',
        data: { labels: RAW_SEVERITY.map(s => (s.severity ?? 'unknown').charAt(0).toUpperCase() + (s.severity ?? 'unknown').slice(1)),
                datasets: [{ data: RAW_SEVERITY.map(s => s.total),
                    backgroundColor: RAW_SEVERITY.map(s => cm[s.severity] ?? '#94a3b8'),
                    borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, cutout: '62%',
            plugins: { legend: { position: 'bottom', labels: { padding: 14 } } } }
    });
})();

// Day of week
(function () {
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const data = days.map((_, i) => {
        const e = Object.values(RAW_DOW).find(r => Number(r.day) === i + 1);
        return e ? e.total : 0;
    });
    const max = Math.max(...data);
    new Chart(document.getElementById('dowChart'), {
        type: 'bar',
        data: { labels: days, datasets: [{ label: 'Incidents', data,
            backgroundColor: data.map(v => v === max ? '#dc2626' : '#4b7a96'),
            borderRadius: 5 }] },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                      x: { grid: { display: false } } } }
    });
})();

// Hour of day
(function () {
    const labels = Array.from({ length: 24 }, (_, i) => `${i % 12 || 12}${i < 12 ? 'am' : 'pm'}`);
    const data   = Array.from({ length: 24 }, (_, i) => {
        const e = Object.values(RAW_HOUR).find(r => Number(r.hour) === i);
        return e ? e.total : 0;
    });
    new Chart(document.getElementById('hourChart'), {
        type: 'line',
        data: { labels, datasets: [{ label: 'Incidents', data,
            borderColor: '#1b3d52', backgroundColor: 'rgba(27,61,82,.1)',
            fill: true, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#1b3d52' }] },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                      x: { ticks: { maxTicksLimit: 8 }, grid: { display: false } } } }
    });
})();

// Google Maps heatmap
let _map = null, _heatLayer = null, _markers = [];

function initMap() {
    const center = { lat: 15.9766, lng: 120.5719 };
    _map = new google.maps.Map(document.getElementById('accident-map'), {
        zoom: 14, center, mapTypeId: 'roadmap',
        styles: [
            { featureType: 'poi',     stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] }
        ],
    });
    if (!RAW_HEATMAP.length) return;

    const sw = { critical:5, high:3, medium:2, low:1, minor:1 };
    const sc = { critical:'#dc2626', high:'#f97316', medium:'#eab308', low:'#22c55e', minor:'#22c55e' };

    _heatLayer = new google.maps.visualization.HeatmapLayer({
        data: RAW_HEATMAP.map(p => ({
            location: new google.maps.LatLng(Number(p.latitude), Number(p.longitude)),
            weight: sw[p.severity] ?? 1,
        })),
        map: _map, radius: 45,
        gradient: [
            'rgba(0,255,255,0)','rgba(0,255,255,1)','rgba(0,191,255,1)',
            'rgba(0,127,255,1)','rgba(0,0,255,1)','rgba(63,0,128,1)',
            'rgba(127,0,64,1)','rgba(191,0,31,1)','rgba(255,0,0,1)',
        ],
    });

    RAW_HEATMAP.forEach(p => {
        _markers.push(new google.maps.Marker({
            position: new google.maps.LatLng(Number(p.latitude), Number(p.longitude)),
            map: null,
            icon: { path: google.maps.SymbolPath.CIRCLE,
                    fillColor: sc[p.severity] ?? '#1b3d52', fillOpacity: 0.85,
                    strokeWeight: 1.5, strokeColor: '#fff', scale: 9 },
        }));
    });

    const bounds = new google.maps.LatLngBounds();
    RAW_HEATMAP.forEach(p => bounds.extend(
        new google.maps.LatLng(Number(p.latitude), Number(p.longitude))));
    if (!bounds.isEmpty()) _map.fitBounds(bounds, 40);
}

function setMode(mode) {
    if (!_map) return;
    if (mode === 'heatmap') {
        _heatLayer?.setMap(_map);
        _markers.forEach(m => m.setMap(null));
    } else {
        _heatLayer?.setMap(null);
        _markers.forEach(m => m.setMap(_map));
    }
    document.getElementById('btn-heatmap').classList.toggle('active', mode === 'heatmap');
    document.getElementById('btn-markers').classList.toggle('active', mode === 'markers');
}
</script>

<script async
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=visualization&callback=initMap">
</script>
@endpush
