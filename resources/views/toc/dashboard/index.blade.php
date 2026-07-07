@extends('toc.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border: 1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Total Registered<br>Riders</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $totalRiders ?? 0 }}</span>
                    <span class="lh-sm" style="font-size:.78rem; color:#4b5563;">Registered Riders in<br>the System</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border: 1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Total Accident<br>Detected</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $totalAccidents ?? 0 }}</span>
                    <span class="lh-sm" style="font-size:.78rem; color:#4b5563;">Registered Rides in the<br>System</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border: 1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Active Devices</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $activeDevices ?? 0 }}</span>
                    <span class="lh-sm" style="font-size:.78rem; color:#4b5563;">Devices are currently<br>connected</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- RECENT INCIDENTS --}}
<h6 class="fw-bold mb-3" style="color:#111827;">Recent Incidents</h6>
<div class="card border rounded-3 mb-4" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Incident Location</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                    <th class="fw-bold border-bottom">Age</th>
                    <th class="fw-bold border-bottom">Address</th>
                    <th class="border-bottom text-end">
                        <a href="#" class="text-decoration-none" style="color:#4b7a96; font-weight:500;">View All</a>
                    </th>
                </tr>
            </thead>
            <tbody id="incidents-tbody">
                @forelse($recentIncidents ?? [] as $incident)
                <tr>
                    <td>{{ $incident->rider?->full_name ?? 'N/A' }}</td>
                    <td>{{ $incident->address ?? 'N/A' }}</td>
                    <td>{{ $incident->rider?->phone_number ?? 'N/A' }}</td>
                    <td>{{ $incident->rider?->date_of_birth ? now()->diffInYears($incident->rider->date_of_birth) : 'N/A' }}</td>
                    <td>{{ $incident->rider?->address ?? 'N/A' }}</td>
                    <td></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No incidents reported yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- RECENT REGISTERED RIDERS --}}
<h6 class="fw-bold mb-3" style="color:#111827;">Recent Registered Riders</h6>
<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Device Model</th>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Age</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                    <th class="border-bottom text-end">
                        <a href="{{ route('toc.helmet.index') }}" class="text-decoration-none" style="color:#4b7a96; font-weight:500;">View All</a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRiders ?? [] as $rider)
                <tr>
                    <td>{{ $rider->helmet?->device_code ?? 'No device' }}</td>
                    <td>{{ $rider->full_name }}</td>
                    <td>{{ $rider->date_of_birth ? now()->diffInYears($rider->date_of_birth) : 'N/A' }}</td>
                    <td>{{ $rider->phone_number ?? 'N/A' }}</td>
                    <td></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No registered riders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
