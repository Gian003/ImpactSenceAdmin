@extends('toc.layouts.app')

@section('title', 'Incident #' . $incident->id)

@push('styles')
<link href="{{ asset('css/toc/incidents.css') }}" rel="stylesheet">
@endpush

@section('content')

@php
    $sev = [
        'critical' => '#b91c1c', 'high' => '#c2410c',
        'medium'   => '#a16207', 'low'  => '#15803d',
    ][$incident->severity] ?? '#64748b';

    $statusChip = [
        'pending'     => 'status-pending',
        'dispatched'  => 'status-dispatched',
        'arrived'     => 'status-arrived',
        'resolved'    => 'status-resolved',
        'false_alarm' => 'status-false-alarm',
    ][$incident->status] ?? 'status-false-alarm';
@endphp

@if (session('dispatched'))
    <div class="alert py-2 mb-3"
         style="background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; font-size:.85rem;">
        {{ session('dispatched') }}
    </div>
@endif

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
    <h5 class="fw-bold mb-0" style="color:#1e293b;">
        Incident #{{ $incident->id }}
        <span class="sev-chip sev-{{ $incident->severity }} ms-1">{{ strtoupper($incident->severity) }}</span>
        <span class="status-chip {{ $statusChip }} ms-1">
            {{ $incident->status === 'arrived' ? 'ON SCENE' : strtoupper(str_replace('_', ' ', $incident->status)) }}
        </span>
    </h5>
    <a href="{{ route('toc.incidents.index') }}" style="font-size:.83rem; color:#475569; text-decoration:none;">
        ← Back to incidents
    </a>
</div>
<p class="text-muted mb-4" style="font-size:.83rem;">
    Reported {{ $incident->created_at->format('d M Y, h:i A') }}
    ({{ $incident->created_at->diffForHumans() }})
</p>

{{-- ── FACTS ────────────────────────────────────────────────────────────── --}}
<div class="detail-grid mb-4">
    <div class="detail-item" style="border-left:4px solid {{ $sev }};">
        <div class="detail-label">Type</div>
        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $incident->type)) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Rider</div>
        <div class="detail-value">{{ $incident->rider?->full_name ?? 'Unknown rider' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Contact</div>
        <div class="detail-value">
            @if ($incident->rider?->phone_number)
                <a href="tel:{{ preg_replace('/\D/', '', $incident->rider->phone_number) }}">
                    {{ $incident->rider->phone_number }}</a>
            @else
                &mdash;
            @endif
        </div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Assigned unit</div>
        <div class="detail-value">
            {{ $incident->patrolUnit?->full_name ?? 'Not dispatched' }}
            @if ($incident->patrolUnit?->badge_number)
                <span style="font-weight:400; color:#64748b;">({{ $incident->patrolUnit->badge_number }})</span>
            @endif
        </div>
    </div>
    <div class="detail-item" style="grid-column:span 2;">
        <div class="detail-label">Location</div>
        <div class="detail-value">{{ $incident->address ?? 'Unknown' }}</div>
        @if ($incident->latitude !== null && $incident->longitude !== null)
            <div class="cell-sub" style="font-family:monospace;">
                {{ number_format((float) $incident->latitude, 6) }},
                {{ number_format((float) $incident->longitude, 6) }}
                &middot;
                <a href="https://www.google.com/maps?q={{ $incident->latitude }},{{ $incident->longitude }}"
                   target="_blank" rel="noopener">Open in Maps ↗</a>
            </div>
        @endif
    </div>
    <div class="detail-item">
        <div class="detail-label">Device</div>
        <div class="detail-value">
            {{ $incident->device?->device_code ?? 'App-reported' }}
        </div>
    </div>
</div>

{{-- ── DISPATCH ─────────────────────────────────────────────────────────── --}}
@if ($incident->status === 'pending' && $patrollers->isNotEmpty())
    <div class="card-panel p-3 mb-4">
        <div class="fw-bold mb-2" style="font-size:.9rem; color:#1e293b;">Dispatch a unit</div>
        <form method="POST" action="{{ route('toc.incidents.dispatch', $incident) }}"
              class="d-flex gap-2 flex-wrap align-items-center">
            @csrf
            <select name="patrol_unit_id" class="form-select form-select-sm"
                    style="max-width:340px; font-size:.85rem;" required>
                <option value="">Select patrol unit…</option>
                @foreach ($patrollers as $p)
                    {{-- A unit already on a call cannot be sent to a second one. --}}
                    <option value="{{ $p->id }}" @disabled($p->status === 'dispatched')>
                        {{ $p->full_name }} ({{ $p->badge_number }})
                        — {{ $p->status === 'dispatched' ? 'on a call' : 'stand by' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm text-white fw-semibold px-3"
                    style="background:#1b3d52; font-size:.83rem;">Dispatch</button>
        </form>
    </div>
@endif

{{-- ── TIMELINE ─────────────────────────────────────────────────────────── --}}
<div class="card-panel p-3 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="fw-bold" style="font-size:.9rem; color:#1e293b;">History</div>
        @if ($events->contains('reconstructed', true))
            {{-- Said plainly rather than left to be assumed. These entries were
                 inferred from timestamp columns after the fact, so they know
                 when something happened but not who did it — nothing recorded
                 that at the time. --}}
            <span style="font-size:.72rem; color:#92400e; background:#fef3c7;
                         padding:2px 9px; border-radius:999px; font-weight:700;">
                Partly reconstructed
            </span>
        @endif
    </div>

    <ul class="toc-timeline">
        @forelse ($events as $event)
            <li @class(['is-reconstructed' => $event->reconstructed])>
                <time>{{ $event->occurred_at->format('d M Y, h:i A') }}</time>
                <span class="event-dot" style="background:{{ $event->colour() }};"></span>
                <span>
                    {{ $event->describe() }}
                    @if ($event->reconstructed)
                        <span class="reconstructed-flag">reconstructed</span>
                    @endif
                    @if (! empty($event->payload['notes']))
                        <div class="cell-sub" style="white-space:pre-wrap;">{{ $event->payload['notes'] }}</div>
                    @endif
                </span>
            </li>
        @empty
            <li><span class="text-muted">Nothing recorded for this incident yet.</span></li>
        @endforelse
    </ul>
</div>

{{-- ── RESPONDER ACCOUNT ────────────────────────────────────────────────── --}}
@if ($incident->fieldReports->isNotEmpty())
    <div class="card-panel p-3 mb-4">
        <div class="fw-bold mb-1" style="font-size:.9rem; color:#1e293b;">What the responding unit reported</div>
        <div style="font-size:.78rem; color:#64748b;">
            The written account and scene conditions, which bear on how many units a scene needs.
            Scene photographs are evidentiary and stay in the investigation record.
        </div>

        @foreach ($incident->fieldReports as $report)
            @php
                $conditions = collect([
                    'Vehicles' => $report->vehicles_involved,
                    'Injured'  => $report->injured_count,
                    'Road'     => $report->road_condition,
                    'Weather'  => $report->weather_condition,
                ])->filter(fn ($v) => $v !== null && $v !== '');
            @endphp
            <div class="field-note">
                <div style="font-size:.84rem; font-weight:700; color:#1b3d52;">
                    {{ $report->patrolUnit?->full_name ?? 'Unattributed unit' }}
                    <span style="font-weight:400; color:#64748b;">
                        &middot; filed {{ $report->submitted_at?->format('d M Y, h:i A') ?? '—' }}
                        @if ($report->photos->isNotEmpty())
                            &middot; {{ $report->photos->count() }}
                            {{ Str::plural('photograph', $report->photos->count()) }} on file
                        @endif
                    </span>
                </div>

                @if ($conditions->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($conditions as $label => $value)
                            <span style="background:#f1f5f9; color:#334155; border-radius:6px; padding:2px 8px; font-size:.8rem;">
                                <b>{{ $label }}:</b> {{ $value }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if (trim((string) $report->narrative) !== '')
                    <div class="field-note-narrative">{{ $report->narrative }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if ($incident->notes)
    <div class="card-panel p-3 mb-4">
        <div class="fw-bold mb-2" style="font-size:.9rem; color:#1e293b;">Notes</div>
        <div style="font-size:.88rem; color:#334155; white-space:pre-wrap;">{{ $incident->notes }}</div>
    </div>
@endif

@endsection
