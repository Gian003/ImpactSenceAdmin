@extends('toc.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#fff; border: 1px solid #e8d5d9 !important; border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:48px; height:48px; border-radius:10px; background:#fce7f3; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                         stroke="#7B1A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:2rem; font-weight:900; color:#1e293b; line-height:1;">{{ $totalRiders ?? 0 }}</div>
                    <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Registered Riders in the System</div>
                    <div style="font-size:.78rem; font-weight:700; color:#7B1A2E; margin-top:1px;">Total Registered Riders</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#fff; border: 1px solid #e8d5d9 !important; border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:48px; height:48px; border-radius:10px; background:#fee2e2; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                         stroke="#991b1b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:2rem; font-weight:900; color:#1e293b; line-height:1;">{{ $totalAccidents ?? 0 }}</div>
                    <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Registered Rides in the System</div>
                    <div style="font-size:.78rem; font-weight:700; color:#7B1A2E; margin-top:1px;">Total Accident Detected</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#fff; border: 1px solid #e8d5d9 !important; border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:48px; height:48px; border-radius:10px; background:#fce7f3; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                         stroke="#7B1A2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z"/>
                        <path d="M3 12v2a9 9 0 0 0 18 0v-2"/>
                        <path d="M9 21h6"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:2rem; font-weight:900; color:#1e293b; line-height:1;">{{ $activeDevices ?? 0 }}</div>
                    <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Devices are currently connected</div>
                    <div style="font-size:.78rem; font-weight:700; color:#7B1A2E; margin-top:1px;">Active Devices</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- RECENT INCIDENTS --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="fw-bold mb-0" style="color:#1e293b;">Recent Incidents</h6>
    <a href="{{ route('investigation.incidents.index') }}" class="text-decoration-none"
       style="font-size:.78rem; color:#7B1A2E; font-weight:500;">View All</a>
</div>
<div class="card border-0 rounded-3 mb-4 overflow-hidden" style="border: 1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Incident Location</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Age</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Address</th>
                </tr>
            </thead>
            <tbody id="incidents-tbody">
                @forelse($recentIncidents ?? [] as $incident)
                <tr>
                    <td style="padding:11px 16px; color:#1e293b; border-bottom:1px solid #f5eeef;">{{ $incident->rider?->full_name ?? 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $incident->address ?? 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $incident->rider?->phone_number ?? 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $incident->rider?->date_of_birth ? now()->diffInYears($incident->rider->date_of_birth) : 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $incident->rider?->address ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No incidents reported yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- RECENT REGISTERED RIDERS --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="fw-bold mb-0" style="color:#1e293b;">Recent Registered Riders</h6>
    <a href="{{ route('toc.helmet.index') }}" class="text-decoration-none"
       style="font-size:.78rem; color:#7B1A2E; font-weight:500;">View All</a>
</div>
<div class="card border-0 rounded-3 overflow-hidden" style="border: 1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Device Model</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Age</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRiders ?? [] as $rider)
                <tr>
                    <td style="padding:11px 16px; color:#1e293b; border-bottom:1px solid #f5eeef; font-family:monospace; font-size:.8rem;">{{ $rider->helmet?->device_code ?? 'No device' }}</td>
                    <td style="padding:11px 16px; color:#1e293b; border-bottom:1px solid #f5eeef; font-weight:500;">{{ $rider->full_name }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->date_of_birth ? now()->diffInYears($rider->date_of_birth) : 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->phone_number ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No registered riders yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
