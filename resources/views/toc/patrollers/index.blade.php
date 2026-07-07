@extends('toc.layouts.app')

@section('title', 'Patrollers Unit')

@section('content')

<h6 class="fw-bold mb-3" style="color:#111827;">Registered Patrollers</h6>

<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold border-bottom">Full Name</th>
                    <th class="fw-bold border-bottom">Location</th>
                    <th class="fw-bold border-bottom">Status</th>
                    <th class="fw-bold border-bottom">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patrollers ?? [] as $patroller)
                <tr>
                    <td>{{ $patroller->full_name }}</td>
                    <td>{{ $patroller->current_latitude ? round($patroller->current_latitude,4).'°N, '.round($patroller->current_longitude,4).'°E' : '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $patroller->status)) }}</td>
                    <td>{{ $patroller->mobile_number ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No registered patrollers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
