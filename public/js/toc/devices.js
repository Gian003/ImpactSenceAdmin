// TOC Device Management page — chart.js registration/incident trend charts
// and the pairing-key copy button. Extracted from
// resources/views/toc/devices/index.blade.php. Server data comes in via
// window.TocDevicesConfig (set by a small inline <script> in that Blade file
// right before this one loads).

function copyPk(btn, key) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.color = '#2a7c5b';
        setTimeout(() => { btn.textContent = orig; btn.style.color = '#6b7280'; }, 1500);
    });
}

const labels = window.TocDevicesConfig.chartLabels;

// ── Registrations trend (riders + devices) ───────────────────────────────────
(function () {
    const ctx = document.getElementById('regTrendChart').getContext('2d');

    const gradR = ctx.createLinearGradient(0, 0, 0, 200);
    gradR.addColorStop(0, 'rgba(76,29,149,.25)');
    gradR.addColorStop(1, 'rgba(76,29,149,0)');

    const gradD = ctx.createLinearGradient(0, 0, 0, 200);
    gradD.addColorStop(0, 'rgba(157,23,77,.20)');
    gradD.addColorStop(1, 'rgba(157,23,77,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Riders',
                    data: window.TocDevicesConfig.trendRiders,
                    borderColor: '#4c1d95',
                    borderWidth: 2.5,
                    fill: true,
                    backgroundColor: gradR,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#4c1d95',
                },
                {
                    label: 'Devices',
                    data: window.TocDevicesConfig.trendDevices,
                    borderColor: '#9d174d',
                    borderWidth: 2.5,
                    fill: true,
                    backgroundColor: gradD,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#9d174d',
                },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 12 }, boxWidth: 12, usePointStyle: true } },
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
})();

// ── Device status doughnut ───────────────────────────────────────────────────
(function () {
    const active   = window.TocDevicesConfig.activeDevices;
    const paired   = window.TocDevicesConfig.pairedDevices;
    const total    = window.TocDevicesConfig.totalDevices;
    const unpaired = Math.max(total - paired, 0);
    const inactive = Math.max(paired - active, 0);

    new Chart(document.getElementById('deviceStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive', 'Unpaired'],
            datasets: [{
                data: [active, inactive, unpaired],
                backgroundColor: ['#065f46', '#991b1b', '#94a3b8'],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 }, boxWidth: 12, usePointStyle: true } },
            }
        }
    });
})();

// ── Incident trend ───────────────────────────────────────────────────────────
(function () {
    const ctx = document.getElementById('incidentTrendChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 130);
    grad.addColorStop(0, 'rgba(123,26,46,.25)');
    grad.addColorStop(1, 'rgba(123,26,46,0)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Incidents',
                data: window.TocDevicesConfig.trendIncidents,
                backgroundColor: 'rgba(123,26,46,.75)',
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
