@extends('toc.layouts.app')

@section('title', 'Desk Activity')

@push('styles')
<link href="{{ asset('css/toc/incidents.css') }}" rel="stylesheet">
@endpush

@section('content')

@php
    $duration = function (?int $seconds): string {
        if ($seconds === null) {
            return 'No data';
        }
        if ($seconds < 60) {
            return $seconds . ' sec';
        }
        if ($seconds < 3600) {
            return round($seconds / 60) . ' min';
        }
        if ($seconds < 86400) {
            return round($seconds / 3600, 1) . ' hrs';
        }
        return round($seconds / 86400, 1) . ' days';
    };

    $dayLabel = function (\Illuminate\Support\Carbon $d): string {
        if ($d->isToday())     { return 'Today'; }
        if ($d->isYesterday()) { return 'Yesterday'; }
        return $d->isCurrentYear() ? $d->format('l, d F') : $d->format('l, d F Y');
    };
@endphp

<div class="page-head">
    <div>
        <h5>Desk Activity</h5>
    </div>
    <div style="font-size:.82rem; color:#64748b; text-align:right;">
        <strong style="color:#1e293b; font-size:1rem;">{{ number_format($events->total()) }}</strong>
        {{ Str::plural('entry', $events->total()) }} &middot; {{ $ranges[$range] }}
    </div>
</div>

<p class="page-lede">
    Everything the desk has done, across every incident. Each entry is written as it happens and is
    never edited afterwards — a correction is another entry, so the sequence of what was done, and
    by whom, survives intact.
</p>

{{-- ── RESPONSE TIMES ───────────────────────────────────────────────────── --}}
<div class="metric-strip">
    <div class="metric-tile @if($medianToDispatch === null) is-empty @endif">
        <div class="metric-title">Time to dispatch</div>
        <div class="metric-value">{{ $duration($medianToDispatch) }}</div>
        <div class="metric-note">
            @if ($dispatchSample === 0)
                nothing dispatched in this period
            @else
                median across {{ $dispatchSample }} {{ Str::plural('incident', $dispatchSample) }}
            @endif
        </div>
    </div>

    <div class="metric-tile @if($medianToArrive === null) is-empty @endif">
        <div class="metric-title">Dispatch to on scene</div>
        <div class="metric-value">{{ $duration($medianToArrive) }}</div>
        <div class="metric-note">
            @if ($arriveSample === 0)
                no arrivals recorded in this period
            @else
                median across {{ $arriveSample }} {{ Str::plural('arrival', $arriveSample) }}
            @endif
        </div>
    </div>

    <div class="metric-tile">
        <div class="metric-title">Entries logged</div>
        <div class="metric-value">{{ number_format($events->total()) }}</div>
        <div class="metric-note">{{ $ranges[$range] }}</div>
    </div>
</div>

{{-- Medians, not means: one call dispatched three weeks late would drag an
     average into meaninglessness, and the backlog shows that has happened. --}}
<p class="text-muted mb-3" style="font-size:.75rem;">
    Response times are medians, so a single very late dispatch cannot distort them.
</p>

{{-- ── FILTERS ──────────────────────────────────────────────────────────── --}}
<form method="GET" class="filter-bar">
    <div class="filter-field">
        <label for="f-range">Period</label>
        <select id="f-range" name="range" class="form-select form-select-sm" style="min-width:150px;">
            @foreach ($ranges as $key => $label)
                <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label for="f-type">Event</label>
        <select id="f-type" name="type" class="form-select form-select-sm" style="min-width:170px;">
            <option value="">All events</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>
                    {{ ucfirst(str_replace('_', ' ', $t)) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label for="f-actor">Acted by</label>
        <select id="f-actor" name="actor" class="form-select form-select-sm" style="min-width:160px;">
            <option value="">Anyone</option>
            <option value="toc"           @selected($actor === 'toc')>TOC desk</option>
            <option value="patrol"        @selected($actor === 'patrol')>Patrol unit</option>
            <option value="rider"         @selected($actor === 'rider')>Rider</option>
            <option value="investigation" @selected($actor === 'investigation')>Investigator</option>
            <option value="system"        @selected($actor === 'system')>Device / system</option>
        </select>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-sm btn-filter">Apply</button>
        @if ($type || $actor || $range !== '7d')
            <a href="{{ route('toc.activity.index') }}" class="btn-filter-clear">Clear</a>
        @endif
    </div>
</form>

@if ($reconstructedCount > 0)
    <div class="alert mb-3 py-2" style="background:#fffbeb; border:1px solid #fcd34d; color:#78350f; font-size:.8rem;">
        <strong>{{ $reconstructedCount }}</strong> of the entries below were reconstructed from
        timestamps for incidents that predate this log. Their times are real; nothing recorded who
        acted, so they name no one.
    </div>
@endif

{{-- ── FEED ─────────────────────────────────────────────────────────────── --}}
<div class="card-panel p-0" style="overflow:hidden;">
    @forelse ($events->getCollection()->groupBy(fn ($e) => $e->occurred_at->toDateString()) as $day => $dayEvents)
        @php $date = \Illuminate\Support\Carbon::parse($day); @endphp
        <div class="activity-day">
            <span>{{ $dayLabel($date) }}</span>
            <span class="day-count">
                {{ $dayEvents->count() }} {{ Str::plural('entry', $dayEvents->count()) }}
            </span>
        </div>

        @foreach ($dayEvents as $event)
            <div class="activity-row @if($event->reconstructed) is-reconstructed @endif"
                 style="border-left-color:{{ $event->colour() }};">
                <time>{{ $event->occurred_at->format('H:i') }}</time>
                <span class="activity-icon" style="background:{{ $event->colour() }};"
                      aria-hidden="true">{{ $event->glyph() }}</span>
                <div class="activity-body">
                    <div class="activity-what">
                        {{ $event->describe() }}
                        @if ($event->reconstructed)
                            <span class="reconstructed-flag">reconstructed</span>
                        @endif
                    </div>
                    @if ($event->incident)
                        <div class="cell-sub">
                            <a href="{{ route('toc.incidents.show', $event->incident) }}">
                                Incident #{{ $event->incident->id }}
                            </a>
                            &middot; {{ $event->incident->rider?->full_name ?? 'Unknown rider' }}
                            @if ($event->incident->address)
                                &middot; {{ Str::limit($event->incident->address, 58) }}
                            @endif
                        </div>
                    @else
                        {{-- The incident was deleted; the entry outlives it. --}}
                        <div class="cell-sub">Incident no longer on file</div>
                    @endif
                </div>
            </div>
        @endforeach
    @empty
        <div class="empty-state">
            <span class="empty-state-icon">🗒️</span>
            <div class="empty-state-title">Nothing recorded in this period</div>
            <div class="empty-state-hint">
                Try a wider period, or
                <a href="{{ route('toc.activity.index', ['range' => 'all']) }}"
                   style="color:#1b3d52; font-weight:600;">look at all time</a>.
            </div>
        </div>
    @endforelse
</div>

@if ($events->hasPages())
    <div class="mt-3">{{ $events->links() }}</div>
@endif

@endsection
