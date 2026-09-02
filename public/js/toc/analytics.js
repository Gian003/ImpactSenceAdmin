// TOC Accident Analytics page — chart.js charts + Google Maps heatmap.
// Extracted from resources/views/toc/analytics/index.blade.php. Server data
// comes in via window.TocAnalyticsConfig (set by a small inline <script> in
// that Blade file right before this one loads).

// ── Server data ───────────────────────────────────────────────────────────────
const RAW_HEATMAP   = window.TocAnalyticsConfig.heatmapPoints;
const RAW_MONTHLY   = window.TocAnalyticsConfig.byMonth;
const RAW_SEVERITY  = window.TocAnalyticsConfig.bySeverity;
const RAW_DOW       = window.TocAnalyticsConfig.byDayOfWeek;
const RAW_HOUR      = window.TocAnalyticsConfig.byHour;

// ── Chart defaults ────────────────────────────────────────────────────────────
Chart.defaults.font.family = 'system-ui, sans-serif';
Chart.defaults.font.size   = 11;

// ── Monthly trend (bar) ───────────────────────────────────────────────────────
(function () {
    const labels = [];
    const counts = {};
    for (let i = 11; i >= 0; i--) {
        const d   = new Date();
        d.setMonth(d.getMonth() - i);
        const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        const lbl = d.toLocaleString('default', { month: 'short', year: '2-digit' });
        labels.push(lbl);
        counts[key] = 0;
    }
    RAW_MONTHLY.forEach(r => { if (counts[r.month] !== undefined) counts[r.month] = r.total; });

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Incidents',
                data: Object.values(counts),
                backgroundColor: Object.values(counts).map((v, _, a) =>
                    v === Math.max(...a) ? '#dc2626' : '#1b3d52'),
                borderRadius: 5,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            // Without this, "responsive" alone still derives height from the
            // container's *width* ÷ a fixed ratio, ignoring the canvas's
            // height="" attribute and the wrapper's CSS height entirely —
            // that's why this chart wasn't actually shrinking before.
            maintainAspectRatio: false,
            plugins: { legend: { display: false },
                       tooltip: { callbacks: { label: ctx => ` ${ctx.raw} incident${ctx.raw !== 1 ? 's' : ''}` } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
})();

// ── Severity donut ────────────────────────────────────────────────────────────
(function () {
    const colorMap = { critical:'#dc2626', high:'#f97316', medium:'#eab308', low:'#22c55e', minor:'#22c55e' };
    const labels  = RAW_SEVERITY.map(s => (s.severity ?? 'unknown').charAt(0).toUpperCase() + (s.severity ?? 'unknown').slice(1));
    const data    = RAW_SEVERITY.map(s => s.total);
    const colors  = RAW_SEVERITY.map(s => colorMap[s.severity] ?? '#94a3b8');

    new Chart(document.getElementById('severityChart'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '62%',
            plugins: { legend: { position: 'bottom', labels: { padding: 14, font: { size: 11 } } } }
        }
    });
})();

// ── Day of week (bar) ─────────────────────────────────────────────────────────
(function () {
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const data = days.map((_, i) => {
        const entry = Object.values(RAW_DOW).find(r => Number(r.day) === i + 1);
        return entry ? entry.total : 0;
    });
    const max = Math.max(...data);

    new Chart(document.getElementById('dowChart'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                label: 'Incidents',
                data,
                backgroundColor: data.map(v => v === max ? '#dc2626' : '#4b7a96'),
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
})();

// ── Hour of day (line) ────────────────────────────────────────────────────────
(function () {
    const labels = Array.from({ length: 24 }, (_, i) => {
        const h = i % 12 || 12;
        return `${h}${i < 12 ? 'am' : 'pm'}`;
    });
    const data = Array.from({ length: 24 }, (_, i) => {
        const entry = Object.values(RAW_HOUR).find(r => Number(r.hour) === i);
        return entry ? entry.total : 0;
    });

    new Chart(document.getElementById('hourChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Incidents',
                data,
                borderColor: '#1b3d52',
                backgroundColor: 'rgba(27,61,82,.1)',
                fill: true, tension: 0.4,
                pointRadius: 3, pointBackgroundColor: '#1b3d52',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } },
                x: { ticks: { maxTicksLimit: 8 }, grid: { display: false } }
            }
        }
    });
})();

// ── Google Maps hotspot markers ─────────────────────────────────────────────
// This used to be a google.maps.visualization.HeatmapLayer with a toggle to
// switch to individual markers. Google deprecated HeatmapLayer in May 2025
// and it has been non-functional since May 2026 — there's no direct
// replacement inside the Maps JS API itself (Google's own guidance points to
// a third-party WebGL library, deck.gl, for anyone who needs a true
// heatmap). Rather than pull in a whole new rendering library for this, the
// markers — already a fully separate, working code path — are now the only
// view: colored by severity so the same "where's it worse" signal still
// comes through, just as discrete points instead of a blended gradient.
let _map = null, _markers = [], _cluster = null;

function initMap() {
    const center = { lat: 15.9766, lng: 120.5719 }; // Urdaneta City

    _map = new google.maps.Map(document.getElementById('accident-map'), {
        zoom: 14,
        center,
        mapTypeId: 'roadmap',
        styles: [
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] }
        ],
    });

    if (!RAW_HEATMAP.length) return;

    const sevColor = { critical: '#dc2626', high: '#f97316', medium: '#eab308', low: '#22c55e', minor: '#22c55e' };
    const infoWindow = new google.maps.InfoWindow();

    // Markers are created without a `map` — MarkerClusterer (loaded below)
    // owns deciding whether each renders individually or folds into a
    // numbered cluster bubble, and setting `map` here would fight that.
    // This is a retrospective/all-time view (not a live dispatch map), so
    // unlike Location Tracking's incident markers, bundling nearby ones
    // together has no safety downside — it only helps readability as the
    // incident count grows.
    RAW_HEATMAP.forEach(p => {
        const marker = new google.maps.Marker({
            position: { lat: Number(p.latitude), lng: Number(p.longitude) },
            title: p.severity ?? 'unknown',
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                fillColor: sevColor[p.severity] ?? '#1b3d52',
                fillOpacity: 0.85,
                strokeWeight: 1.5,
                strokeColor: '#fff',
                scale: 9,
            },
        });
        marker.addListener('click', () => {
            const when = p.created_at ? new Date(p.created_at).toLocaleString(undefined, {
                dateStyle: 'medium', timeStyle: 'short',
            }) : 'Unknown date';
            infoWindow.setContent(
                `<b>${p.type ?? 'Incident'}</b> &middot; ${(p.severity ?? 'unknown').toUpperCase()}` +
                `<br>${p.address ?? 'Address unavailable'}` +
                `<br><span style="font-size:.85rem; color:#64748b;">${when}</span>`
            );
            infoWindow.open(_map, marker);
        });
        _markers.push(marker);
    });

    _cluster = new markerClusterer.MarkerClusterer({ map: _map, markers: _markers });

    // If data exists but all points are at 0,0 fall back gracefully
    if (RAW_HEATMAP.every(p => Number(p.latitude) === 0 && Number(p.longitude) === 0)) {
        _map.setCenter(center);
    } else {
        const bounds = new google.maps.LatLngBounds();
        RAW_HEATMAP.forEach(p => bounds.extend(
            new google.maps.LatLng(Number(p.latitude), Number(p.longitude))));
        _map.fitBounds(bounds, 40);
    }
}

// Connects the "Top Accident Prone Areas" sidebar (ranked by exact address
// text) to the map (clustered by real GPS proximity) — clicking a sidebar
// row pans/zooms to every point that shares that address, since a busy road
// can be one address spread across several distinct GPS clusters that would
// otherwise never visually line up with the sidebar's ranking on their own.
window.flyToArea = function (address) {
    if (!_map) return;
    const points = RAW_HEATMAP.filter(p => p.address === address);
    if (!points.length) return;

    const bounds = new google.maps.LatLngBounds();
    points.forEach(p => bounds.extend({ lat: Number(p.latitude), lng: Number(p.longitude) }));
    _map.fitBounds(bounds, 60);

    // A single matching point collapses fitBounds to a very tight/over-zoomed
    // view — pull back out to a sane street-level zoom in that case.
    google.maps.event.addListenerOnce(_map, 'bounds_changed', () => {
        if (_map.getZoom() > 17) _map.setZoom(17);
    });
};
