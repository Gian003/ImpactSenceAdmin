// Investigation Accident Analytics page — chart.js charts + Google Maps
// heatmap. Extracted from resources/views/investigation/analytics/index.blade.php.
// Server data comes in via window.InvestigationAnalyticsConfig (set by a
// small inline <script> in that Blade file right before this one loads).

const RAW_HEATMAP  = window.InvestigationAnalyticsConfig.heatmapPoints;
const RAW_MONTHLY  = window.InvestigationAnalyticsConfig.byMonth;
const RAW_SEVERITY = window.InvestigationAnalyticsConfig.bySeverity;
const RAW_DOW      = window.InvestigationAnalyticsConfig.byDayOfWeek;
const RAW_HOUR     = window.InvestigationAnalyticsConfig.byHour;

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

// Google Maps hotspot markers — was a HeatmapLayer with a toggle to switch
// to markers; Google deprecated HeatmapLayer in May 2025 and it's been
// non-functional since May 2026, with no direct in-API replacement. Markers
// (colored by severity) are now the only view — see the longer explanation
// in public/js/toc/analytics.js, same situation, same fix.
let _map = null, _markers = [], _cluster = null;

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

    const sc = { critical:'#dc2626', high:'#f97316', medium:'#eab308', low:'#22c55e', minor:'#22c55e' };
    const infoWindow = new google.maps.InfoWindow();

    // Markers are created without a `map` — MarkerClusterer (loaded below)
    // owns deciding whether each renders individually or folds into a
    // numbered cluster bubble. This is a retrospective/all-time view, not a
    // live dispatch map, so bundling nearby incidents has no safety
    // downside here — see public/js/toc/analytics.js for the fuller note.
    RAW_HEATMAP.forEach(p => {
        const marker = new google.maps.Marker({
            position: { lat: Number(p.latitude), lng: Number(p.longitude) },
            title: p.severity ?? 'unknown',
            icon: { path: google.maps.SymbolPath.CIRCLE,
                    fillColor: sc[p.severity] ?? '#1b3d52', fillOpacity: 0.85,
                    strokeWeight: 1.5, strokeColor: '#fff', scale: 9 },
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

    const bounds = new google.maps.LatLngBounds();
    RAW_HEATMAP.forEach(p => bounds.extend(
        new google.maps.LatLng(Number(p.latitude), Number(p.longitude))));
    if (!bounds.isEmpty()) _map.fitBounds(bounds, 40);
}

// Connects the "Top Accident Prone Areas" sidebar to the map — see
// public/js/toc/analytics.js for the fuller note.
window.flyToArea = function (address) {
    if (!_map) return;
    const points = RAW_HEATMAP.filter(p => p.address === address);
    if (!points.length) return;

    const bounds = new google.maps.LatLngBounds();
    points.forEach(p => bounds.extend({ lat: Number(p.latitude), lng: Number(p.longitude) }));
    _map.fitBounds(bounds, 60);

    google.maps.event.addListenerOnce(_map, 'bounds_changed', () => {
        if (_map.getZoom() > 17) _map.setZoom(17);
    });
};
