@extends('investigation.layouts.app')

@section('title', 'Device Management')

@push('styles')
<style>
    /* Same class names/styles as toc/devices/index.blade.php's .dm-stat, so
       the two dashboards' device stat cards read as one consistent design
       instead of two different looks for the same kind of data. */
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
        width: 42px;
        height: 42px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dm-stat-val {
        font-size: 1.55rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    .dm-stat-lbl {
        font-size: .75rem;
        color: #64748b;
        margin-top: 2px;
    }
</style>
@endpush

@section('content')

{{-- Stat summary — this page was previously just a bare table with no
     at-a-glance numbers at all. --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dm-stat">
            <div class="dm-stat-icon" style="background:#ede9fe;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#4c1d95"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#9d174d"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#065f46"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#0c4a6e"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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

<h6 class="fw-bold mb-2" style="color:#1e293b;">Registered Riders</h6>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    {{-- Was labeled "Device Model" but the cell below has always
                         shown device_code, not device->model — fixed to match
                         what's actually displayed (same as toc/devices' identical
                         column, which is correctly labeled "Device Code"). --}}
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Device Code</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Age</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders ?? [] as $rider)
                <tr>
                    <td style="padding:11px 16px; color:#334155; border-bottom:1px solid #f5eeef;">
                        @if($rider->device)
                            <span style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem;">
                                {{ $rider->device->device_code }}
                            </span>
                        @else
                            <span style="color:#64748b;">No device</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">{{ $rider->full_name }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->date_of_birth ? $rider->date_of_birth->age : 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->phone_number ?? 'N/A' }}</td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        @if($rider->device && $rider->device->is_active)
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46;">Active</span>
                        @elseif($rider->device)
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b;">Inactive</span>
                        @else
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#f1f5f9; color:#64748b;">Unpaired</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No registered riders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
