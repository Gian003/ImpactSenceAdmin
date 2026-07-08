@extends('toc.layouts.app')

@section('title', 'Speed Zones')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible py-2 mb-4" style="font-size:.84rem;">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible py-2 mb-4" style="font-size:.84rem;">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<p class="text-muted mb-4" style="font-size:.85rem; max-width:640px;">
    Define a posted speed limit for a street or barangay. The Location Tracking
    page's Speed Reports panel compares this against real GPS speed samples
    collected from paired helmets within the zone's radius, flagging areas
    where riders are averaging above the limit.
</p>

{{-- ── ADD ZONE ──────────────────────────────────────────────────────────────── --}}
<h6 class="fw-bold mb-3" style="color:#111827;">Add Speed Zone</h6>

<div class="card border rounded-3 mb-5" style="border-color:#d1dde6 !important;">
    <div class="card-body">
        <form method="POST" action="{{ route('toc.speed-zones.store') }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label" style="font-size:.78rem; color:#4b5563;">Zone Name</label>
                    <input type="text" name="name" class="form-control form-control-sm"
                           placeholder="e.g. Urdaneta Bypass Road"
                           style="border-color:#c8d8e4; font-size:.83rem;"
                           value="{{ old('name') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#4b5563;">Latitude</label>
                    <input type="number" step="any" name="latitude" class="form-control form-control-sm"
                           placeholder="15.9755"
                           style="border-color:#c8d8e4; font-size:.83rem;"
                           value="{{ old('latitude') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#4b5563;">Longitude</label>
                    <input type="number" step="any" name="longitude" class="form-control form-control-sm"
                           placeholder="120.5651"
                           style="border-color:#c8d8e4; font-size:.83rem;"
                           value="{{ old('longitude') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#4b5563;">Radius (m)</label>
                    <input type="number" name="radius_meters" class="form-control form-control-sm"
                           placeholder="150" min="10" max="5000"
                           style="border-color:#c8d8e4; font-size:.83rem;"
                           value="{{ old('radius_meters', 150) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="font-size:.78rem; color:#4b5563;">Limit (kph)</label>
                    <input type="number" name="speed_limit_kph" class="form-control form-control-sm"
                           placeholder="40" min="1" max="200"
                           style="border-color:#c8d8e4; font-size:.83rem;"
                           value="{{ old('speed_limit_kph') }}" required>
                </div>
            </div>
            <div class="text-muted mt-2 mb-3" style="font-size:.75rem;">
                Tip: right-click any point on Google Maps to copy its coordinates.
            </div>
            <button type="submit" class="btn btn-sm text-white fw-semibold"
                    style="background:#1b3d52; font-size:.82rem;">
                Add Zone
            </button>
        </form>
    </div>
</div>

{{-- ── EXISTING ZONES ────────────────────────────────────────────────────────── --}}
<h6 class="fw-bold mb-3" style="color:#111827;">Registered Zones</h6>

<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Name</th>
                    <th class="fw-bold border-bottom">Coordinates</th>
                    <th class="fw-bold border-bottom">Radius</th>
                    <th class="fw-bold border-bottom">Limit</th>
                    <th class="fw-bold border-bottom">Added By</th>
                    <th class="fw-bold border-bottom"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($zones ?? [] as $zone)
                <tr>
                    <td>{{ $zone->name }}</td>
                    <td>{{ round($zone->latitude, 4) }}°N, {{ round($zone->longitude, 4) }}°E</td>
                    <td>{{ $zone->radius_meters }} m</td>
                    <td>{{ $zone->speed_limit_kph }} kph</td>
                    <td>{{ $zone->creator?->full_name ?? '—' }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('toc.speed-zones.destroy', $zone) }}"
                              onsubmit="return confirm('Remove this speed zone?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-danger" style="font-size:.78rem;">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No speed zones defined yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
