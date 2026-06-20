@extends('investigation.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border:1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Total Registered<br>Riders</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $totalRiders ?? 59 }}</span>
                    <span class="lh-sm" style="font-size:.78rem; color:#4b5563;">Registered Riders in<br>the System</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border:1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Total Accident<br>Detected</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $totalAccidents ?? 24 }}</span>
                    <span class="lh-sm" style="font-size:.78rem; color:#4b5563;">Registered Rides in the<br>System</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 h-100" style="background:#dce8f0; border:1.5px solid #b8cdd9 !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:#111827;">Active Devices</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-black lh-1" style="font-size:2.4rem; color:#111827;">{{ $activeDevices ?? 30 }}</span>
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
                        <a href="{{ route('investigation.incidents.index') }}" class="text-decoration-none" style="color:#4b7a96; font-weight:500;">View All</a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentIncidents ?? [] as $incident)
                <tr>
                    <td>{{ $incident->full_name }}</td>
                    <td>{{ $incident->location }}</td>
                    <td>{{ $incident->contact_number }}</td>
                    <td>{{ $incident->age }}</td>
                    <td>{{ $incident->address }}</td>
                    <td></td>
                </tr>
                @empty
                <tr><td>Darnil Castanieto</td><td>Brgy. Cabuloan,<br>Urdaneta City, Pangasinan</td><td>09123456789</td><td>35</td><td>Brgy. San Vicente East,<br>Urdaneta City, Pangasinan</td><td></td></tr>
                <tr><td>Rester Mendoza</td><td>Brgy. Pinmaludpod,<br>Urdaneta City, Pangasinan</td><td>09987456321</td><td>26</td><td>Brgy. Sugcong,<br>Urdaneta City Pangasinan</td><td></td></tr>
                <tr><td>Gian Rodriguez</td><td>Urdaneta Bypass Road</td><td>09147853698</td><td>23</td><td>Brgy. Camantiles,<br>Urdaneta City, Pangasinan</td><td></td></tr>
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
                        <a href="{{ route('investigation.helmet.index') }}" class="text-decoration-none" style="color:#4b7a96; font-weight:500;">View All</a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRiders ?? [] as $rider)
                <tr>
                    <td>{{ $rider->device_model }}</td>
                    <td>{{ $rider->full_name }}</td>
                    <td>{{ $rider->age }}</td>
                    <td>{{ $rider->contact_number }}</td>
                    <td></td>
                </tr>
                @empty
                <tr><td>ITK-BLK4-GRP5-MDL1</td><td>Rester Mendoza</td><td>20</td><td>09123645871</td><td></td></tr>
                <tr><td>ITK-BLK4-GRP5-MDL2</td><td>Darnil Castanieto</td><td>20</td><td>09663322558</td><td></td></tr>
                <tr><td>ITK-BLK4-GRP5-MDL3</td><td>Gian Rodriguez</td><td>20</td><td>09875461235</td><td></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
