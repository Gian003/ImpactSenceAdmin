@extends('admin.layouts.app')
@section('title', 'Command Overview')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- Stat row --}}
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label' => 'Total Incidents',    'value' => $stats['total_incidents'], 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
        ['label' => 'Pending',            'value' => $stats['pending'],         'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['label' => 'Resolved',           'value' => $stats['resolved'],        'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => '<polyline points="20 6 9 17 4 12"/>'],
        ['label' => 'Registered Riders',  'value' => $stats['total_riders'],    'bg' => '#ede9fe', 'color' => '#4c1d95', 'icon' => '<circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>'],
        ['label' => 'Active Devices',     'value' => $stats['active_devices'],  'bg' => '#fce7f3', 'color' => '#9d174d', 'icon' => '<path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/><path d="M3 12v2a9 9 0 0 0 18 0v-2"/><path d="M9 21h6"/>'],
        ['label' => 'Patrol Units',       'value' => $stats['patrol_units'],    'bg' => '#e0f2fe', 'color' => '#0c4a6e', 'icon' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'],
        ['label' => 'TOC Officers',       'value' => $stats['toc_officers'],    'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        ['label' => 'Investigation Off.', 'value' => $stats['inv_officers'],    'bg' => '#ede9fe', 'color' => '#4c1d95', 'icon' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
    ];
    @endphp

    @foreach($cards as $card)
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $card['bg'] }};">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                     stroke="{{ $card['color'] }}" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" viewBox="0 0 24 24">
                    {!! $card['icon'] !!}
                </svg>
            </div>
            <div>
                <div class="stat-value">{{ $card['value'] }}</div>
                <div class="stat-label">{{ $card['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">

    {{-- Recent incidents --}}
    <div class="col-lg-7">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0" style="color:#1e293b;">Recent Incidents</h6>
            <a href="{{ route('investigation.incidents.index') }}" target="_blank"
               style="font-size:.78rem; color:#7B1A2E; text-decoration:none; font-weight:500;">View All ↗</a>
        </div>
        <div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead>
                        <tr style="background:#7B1A2E;">
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">#</th>
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">Rider</th>
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">Type</th>
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">Severity</th>
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">Status</th>
                            <th style="padding:11px 14px; color:#fff; font-weight:700; font-size:.75rem; border:none;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentIncidents as $inc)
                        <tr>
                            <td style="padding:10px 14px; color:#64748b; border-bottom:1px solid #f5eeef;">{{ $inc->id }}</td>
                            <td style="padding:10px 14px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">{{ $inc->rider?->full_name ?? '—' }}</td>
                            <td style="padding:10px 14px; color:#475569; border-bottom:1px solid #f5eeef; text-transform:capitalize;">{{ str_replace('_',' ',$inc->type) }}</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #f5eeef;">
                                @php
                                $sc = match($inc->severity) {
                                    'critical' => 'background:#fee2e2;color:#991b1b',
                                    'high'     => 'background:#fef3c7;color:#92400e',
                                    'medium'   => 'background:#dbeafe;color:#1e40af',
                                    default    => 'background:#f1f5f9;color:#64748b',
                                };
                                @endphp
                                <span class="status-badge" style="{{ $sc }}">{{ ucfirst($inc->severity) }}</span>
                            </td>
                            <td style="padding:10px 14px; border-bottom:1px solid #f5eeef;">
                                @php
                                $ss = match($inc->status) {
                                    'pending'    => 'badge-pending',
                                    'dispatched' => 'badge-toc',
                                    'resolved'   => 'badge-accepted',
                                    default      => 'badge-expired',
                                };
                                @endphp
                                <span class="status-badge {{ $ss }}">{{ ucfirst($inc->status) }}</span>
                            </td>
                            <td style="padding:10px 14px; color:#64748b; font-size:.78rem; border-bottom:1px solid #f5eeef; white-space:nowrap;">{{ $inc->created_at->format('M d, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:#64748b;">No incidents yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Monthly chart --}}
    <div class="col-lg-5">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0" style="color:#1e293b;">Incidents — Last 6 Months</h6>
        </div>
        <div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important; height:calc(100% - 28px);">
            <div class="card-panel-body">
                <canvas id="monthChart" height="220"></canvas>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
new Chart(document.getElementById('monthChart'), {
    type: 'bar',
    data: {
        labels: @json($byMonth->pluck('month')),
        datasets: [{
            label: 'Incidents',
            data: @json($byMonth->pluck('total')),
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
</script>
@endpush
