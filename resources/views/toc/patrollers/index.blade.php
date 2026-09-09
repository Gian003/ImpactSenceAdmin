@extends('toc.layouts.app')

@section('title', 'Patrollers Unit')

@section('content')

<h6 class="fw-bold mb-2" style="color:#1e293b;">Registered Patrollers</h6>
<p class="text-muted mb-3" style="font-size:.85rem;">
    Presence and duty are separate: <em>Online</em> means the officer's app has checked in
    within the last {{ \App\Models\PatrolUnit::ONLINE_WITHIN_MINUTES }} minutes,
    while <em>Duty</em> shows whether they're currently tied up on a call.
</p>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Full Name</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Presence</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Location</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Duty</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">Contact Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patrollers ?? [] as $patroller)
                @php $online = $patroller->isOnline(); @endphp
                <tr @if($patroller->current_latitude) data-lat="{{ $patroller->current_latitude }}" data-lng="{{ $patroller->current_longitude }}" @endif>
                    <td style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">{{ $patroller->full_name }}</td>

                    {{-- Presence — derived from the heartbeat, never from a login
                         flag, so it expires on its own when the app is closed. --}}
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        <span style="display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:.78rem; color:{{ $online ? '#065f46' : '#64748b' }};">
                            <span style="width:7px; height:7px; border-radius:50%; background:{{ $online ? '#10b981' : '#cbd5e1' }};"></span>
                            {{ $online ? 'Online' : 'Offline' }}
                        </span>
                        <span style="display:block; font-size:.76rem; color:#94a3b8; margin-top:2px;">
                            {{ $patroller->last_seen_at?->diffForHumans() ?? 'Never checked in' }}
                        </span>
                    </td>

                    {{-- Coordinates are seeded here; public/js/toc/patrollers.js
                         resolves them to a place name into .geo-text without
                         replacing them. A stale fix is labelled as such so an
                         old position is never mistaken for a live one. --}}
                    <td style="padding:11px 16px; color:{{ $online ? '#475569' : '#94a3b8' }}; border-bottom:1px solid #f5eeef;">
                        @if($patroller->current_latitude)
                            @unless($online)
                                <span style="display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8;">Last known</span>
                            @endunless
                            <span class="loc-text">{{ round($patroller->current_latitude, 4) }}&deg;N, {{ round($patroller->current_longitude, 4) }}&deg;E</span>
                            <span class="geo-text" style="display:block; font-size:.86rem; color:{{ $online ? '#64748b' : '#a8b3c0' }};"></span>
                        @else
                            &mdash;
                        @endif
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
                    <td colspan="5" class="text-center text-muted py-4">No registered patrollers yet.</td>
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
