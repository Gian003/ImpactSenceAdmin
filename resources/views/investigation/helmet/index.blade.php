@extends('investigation.layouts.app')

@section('title', 'Device Management')

@section('content')

<h6 class="fw-bold mb-3" style="color:#111827;">Registered Riders</h6>

<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Device Model</th>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Age</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders ?? [] as $rider)
                <tr>
                    <td>{{ $rider->helmet?->device_code ?? 'No device' }}</td>
                    <td>{{ $rider->full_name }}</td>
                    <td>{{ $rider->date_of_birth ? now()->diffInYears($rider->date_of_birth) : 'N/A' }}</td>
                    <td>{{ $rider->phone_number ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No registered riders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
