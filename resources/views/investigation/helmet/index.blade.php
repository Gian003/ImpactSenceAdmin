@extends('investigation.layouts.app')

@section('title', 'Device Management')

@section('content')

<h6 class="fw-bold mb-2" style="color:#1e293b;">Registered Riders</h6>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
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
                @forelse($riders ?? [] as $rider)
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
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->date_of_birth ? now()->diffInYears($rider->date_of_birth) : 'N/A' }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $rider->phone_number ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No registered riders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
