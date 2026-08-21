@extends('toc.layouts.app')

@section('title', 'Device Management')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.dm-stat {
    background: #fff;
    border: 1px solid #e8d5d9;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.dm-stat-icon {
    width: 42px; height: 42px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.dm-stat-val  { font-size: 1.55rem; font-weight: 700; color: #1e293b; line-height: 1; }
.dm-stat-lbl  { font-size: .75rem; color: #64748b; margin-top: 2px; }
.dm-chart-card {
    background: #fff;
    border: 1px solid #e8d5d9;
    border-radius: 10px;
    overflow: hidden;
}
.dm-chart-header {
    padding: 14px 18px;
    border-bottom: 1px solid #f5eeef;
    font-weight: 600;
    font-size: .875rem;
    color: #1e293b;
}
.dm-chart-body { padding: 18px; }
</style>
@endpush

@section('content')

{{-- Stat summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dm-stat">
            <div class="dm-stat-icon" style="background:#ede9fe;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none"
                     stroke="#4c1d95" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                </svg>
            </div>
            <div>
                <div class="dm-stat-val">{{ $totalRiders }}</div>
                <div class="dm-stat-lbl">Registered Riders</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dm-stat">
            <div class="dm-stat-icon" style="background:#fce7f3;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none"
                     stroke="#9d174d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/>
                    <path d="M3 12v2a9 9 0 0 0 18 0v-2"/>
                    <path d="M9 21h6"/>
                </svg>
            </div>
            <div>
                <div class="dm-stat-val">{{ $totalDevices }}</div>
                <div class="dm-stat-lbl">Total Devices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dm-stat">
            <div class="dm-stat-icon" style="background:#d1fae5;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none"
                     stroke="#065f46" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <div class="dm-stat-val">{{ $activeDevices }}</div>
                <div class="dm-stat-lbl">Active Devices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dm-stat">
            <div class="dm-stat-icon" style="background:#e0f2fe;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none"
                     stroke="#0c4a6e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
            </div>
            <div>
                <div class="dm-stat-val">{{ $pairedDevices }}</div>
                <div class="dm-stat-lbl">Paired Devices</div>
            </div>
        </div>
    </div>
</div>

{{-- Charts row --}}
<div class="row g-3 mb-4">

    {{-- Riders & Devices trend --}}
    <div class="col-lg-7">
        <div class="dm-chart-card h-100">
            <div class="dm-chart-header">Registrations — Last 7 Days</div>
            <div class="dm-chart-body">
                <canvas id="regTrendChart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Device status doughnut --}}
    <div class="col-lg-5">
        <div class="dm-chart-card h-100">
            <div class="dm-chart-header">Device Status</div>
            <div class="dm-chart-body d-flex align-items-center justify-content-center" style="min-height:230px;">
                <canvas id="deviceStatusChart" style="max-height:220px;"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- Incidents trend --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="dm-chart-card">
            <div class="dm-chart-header">Incident Reports — Last 7 Days</div>
            <div class="dm-chart-body">
                <canvas id="incidentTrendChart" height="130"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Rider table --}}
<h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
    Rider — Device Registry
</h6>
<div class="dm-chart-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Device Code</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Age</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders as $rider)
                <tr>
                    <td style="padding:11px 16px; color:#334155; border-bottom:1px solid #f5eeef;">
                        @if($rider->helmet)
                            <span style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem;">
                                {{ $rider->helmet->device_code }}
                            </span>
                        @else
                            <span style="color:#94a3b8;">No device</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">{{ $rider->full_name }}</td>
                    <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">{{ $rider->date_of_birth ? now()->diffInYears($rider->date_of_birth) : 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">{{ $rider->phone_number ?? 'N/A' }}</td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        @if($rider->helmet && $rider->helmet->is_active)
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46;">Active</span>
                        @elseif($rider->helmet)
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b;">Inactive</span>
                        @else
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#f1f5f9; color:#64748b;">Unpaired</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No registered riders yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const labels = @json($chartLabels);

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
                    data: @json($trendRiders),
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
                    data: @json($trendDevices),
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
    const active   = {{ $activeDevices }};
    const paired   = {{ $pairedDevices }};
    const total    = {{ $totalDevices }};
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
                data: @json($trendIncidents),
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
</script>
@endpush
