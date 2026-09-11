@extends('toc.layouts.app')

@section('title', 'Incidents')

@push('styles')
<link href="{{ asset('css/toc/incidents.css') }}" rel="stylesheet">
@endpush

@section('content')

@php
    // Each triage tile is a link to its own filtered view, and knows when it
    // is the view currently on screen.
    $tiles = [
        ['key' => 'pending',    'label' => 'Pending',    'count' => $counts['pending'],
         'query' => ['status' => 'pending']],
        ['key' => 'dispatched', 'label' => 'Dispatched', 'count' => $counts['dispatched'],
         'query' => ['status' => 'dispatched']],
        ['key' => 'arrived',    'label' => 'On scene',   'count' => $counts['arrived'],
         'query' => ['status' => 'arrived']],
        ['key' => 'critical',   'label' => 'Critical',   'count' => $counts['critical'],
         'query' => ['status' => 'open', 'severity' => 'critical']],
        ['key' => 'ageing',     'label' => 'Ageing',     'count' => $counts['ageing'],
         'query' => ['status' => 'ageing']],
    ];

    $activeTile = match (true) {
        $severity === 'critical' && $status === 'open' => 'critical',
        $status === 'ageing' => 'ageing',
        in_array($status, ['pending', 'dispatched', 'arrived'], true) => $status,
        default => null,
    };
@endphp

<div class="page-head">
    <div>
        <h5>Incidents</h5>
    </div>
    <div style="font-size:.82rem; color:#64748b; text-align:right;">
        <strong style="color:#1e293b; font-size:1rem;">{{ $openCount }}</strong> still open
    </div>
</div>

<p class="page-lede">
    Everything the desk has received. Open incidents are listed oldest first — the ones sitting
    longest are the ones nobody has dealt with. The investigator's report form and scene
    photographs live in the investigation records, not here.
</p>

{{-- ── TRIAGE ───────────────────────────────────────────────────────────── --}}
<div class="triage-strip">
    @foreach ($tiles as $tile)
        <a href="{{ route('toc.incidents.index', $tile['query']) }}"
           class="triage-tile triage-tile--{{ $tile['key'] }} {{ $activeTile === $tile['key'] ? 'is-active' : '' }}">
            <div class="triage-value">{{ $tile['count'] }}</div>
            <div class="triage-label">
                {{ $tile['label'] }}
                @if ($tile['key'] === 'ageing')
                    <span style="text-transform:none; font-weight:400;">&gt; {{ $liveWindowHours }}h</span>
                @endif
            </div>
        </a>
    @endforeach
</div>

{{-- ── FILTERS ──────────────────────────────────────────────────────────── --}}
<form method="GET" class="filter-bar">
    <div class="filter-field" style="flex:1 1 240px;">
        <label for="f-q">Search</label>
        <input type="search" id="f-q" name="q" value="{{ $q }}" placeholder="Rider name or location"
               class="form-control form-control-sm">
    </div>
    <div class="filter-field">
        <label for="f-status">Status</label>
        <select id="f-status" name="status" class="form-select form-select-sm" style="min-width:150px;">
            <option value="open"        @selected($status === 'open')>Still open</option>
            <option value="ageing"      @selected($status === 'ageing')>Ageing (open &gt; {{ $liveWindowHours }}h)</option>
            <option value="all"         @selected($status === 'all')>All statuses</option>
            <option value="pending"     @selected($status === 'pending')>Pending</option>
            <option value="dispatched"  @selected($status === 'dispatched')>Dispatched</option>
            <option value="arrived"     @selected($status === 'arrived')>On scene</option>
            <option value="resolved"    @selected($status === 'resolved')>Resolved</option>
            <option value="false_alarm" @selected($status === 'false_alarm')>False alarm</option>
        </select>
    </div>
    <div class="filter-field">
        <label for="f-sev">Severity</label>
        <select id="f-sev" name="severity" class="form-select form-select-sm" style="min-width:135px;">
            <option value="">Any</option>
            @foreach (['critical', 'high', 'medium', 'low'] as $s)
                <option value="{{ $s }}" @selected($severity === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-sm btn-filter">Apply</button>
        @if ($q || $severity || $status !== 'open')
            <a href="{{ route('toc.incidents.index') }}" class="btn-filter-clear">Clear</a>
        @endif
    </div>
    <div class="ms-auto" style="font-size:.8rem; color:#64748b; align-self:center;">
        Showing {{ number_format($incidents->total()) }}
        {{ Str::plural('incident', $incidents->total()) }}
    </div>
</form>

{{-- ── LIST ─────────────────────────────────────────────────────────────── --}}
<div class="card-panel">
    @if ($incidents->isEmpty())
        <div class="empty-state">
            <span class="empty-state-icon">🔍</span>
            <div class="empty-state-title">No incidents match these filters</div>
            <div class="empty-state-hint">
                Try widening the search, or
                <a href="{{ route('toc.incidents.index') }}" style="color:#1b3d52; font-weight:600;">
                    clear the filters</a>.
            </div>
        </div>
    @else
        <div class="table-scroll">
            <table class="incidents-table">
                <thead>
                    <tr>
                        <th>Reported</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Rider</th>
                        <th>Location</th>
                        <th>Assigned unit</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incidents as $inc)
                        @php
                            $open = in_array($inc->status, ['pending', 'dispatched', 'arrived'], true);
                            $ageing = $open && $inc->created_at->lt(now()->subHours($liveWindowHours));
                        @endphp
                        <tr @class(['is-ageing' => $ageing])>
                            <td style="white-space:nowrap;">
                                {{ $inc->created_at->format('d M Y, h:i A') }}
                                <div class="cell-sub">
                                    {{ $inc->created_at->diffForHumans() }}
                                    @if ($ageing)
                                        <span class="ageing-flag">still open</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $inc->type)) }}</td>
                            <td><span class="sev-chip sev-{{ $inc->severity }}">{{ strtoupper($inc->severity) }}</span></td>
                            <td><span class="status-chip status-{{ str_replace('_', '-', $inc->status) }}">
                                {{ $inc->status === 'arrived' ? 'ON SCENE' : strtoupper(str_replace('_', ' ', $inc->status)) }}
                            </span></td>
                            <td>
                                {{ $inc->rider?->full_name ?? 'Unknown rider' }}
                                @if ($inc->rider?->phone_number)
                                    <div class="cell-sub">
                                        {{-- Sits above the row link so it stays
                                             clickable as a phone number. --}}
                                        <a href="tel:{{ preg_replace('/\D/', '', $inc->rider->phone_number) }}"
                                           style="position:relative; z-index:1;">
                                            {{ $inc->rider->phone_number }}</a>
                                    </div>
                                @endif
                            </td>
                            <td style="max-width:240px;">
                                <span title="{{ $inc->address }}">{{ Str::limit($inc->address ?? 'Unknown', 52) }}</span>
                            </td>
                            <td>{{ $inc->patrolUnit?->full_name ?? '—' }}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('toc.incidents.show', $inc) }}" class="view-link row-link">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if ($incidents->hasPages())
    <div class="mt-3">{{ $incidents->links() }}</div>
@endif

@endsection
