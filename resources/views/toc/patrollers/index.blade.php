@extends('toc.layouts.app')

@section('title', 'Patrollers Unit')

@section('content')

<h6 class="fw-bold mb-2" style="color:#1e293b;">Registered Patrollers</h6>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Location</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Status</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patrollers ?? [] as $patroller)
                <tr @if($patroller->current_latitude) data-lat="{{ $patroller->current_latitude }}" data-lng="{{ $patroller->current_longitude }}" @endif>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">{{ $patroller->full_name }}</td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        <span class="loc-text">{{ $patroller->current_latitude ? round($patroller->current_latitude,4).'°N, '.round($patroller->current_longitude,4).'°E' : '—' }}</span>
                        <span class="geo-text" style="display:block; font-size:.86rem; color:#64748b;"></span>
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        @php
                        $statusStyle = match($patroller->status) {
                            'available'  => 'background:#d1fae5; color:#065f46',
                            'dispatched' => 'background:#fce7f3; color:#7B1A2E',
                            'off_duty'   => 'background:#f1f5f9; color:#64748b',
                            default      => 'background:#f1f5f9; color:#64748b',
                        };
                        @endphp
                        <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; {{ $statusStyle }}">
                            {{ ucfirst(str_replace('_', ' ', $patroller->status)) }}
                        </span>
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">{{ $patroller->mobile_number ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No registered patrollers yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/toc/patrollers.js') }}?v={{ filemtime(public_path('js/toc/patrollers.js')) }}"></script>
@endpush
