@extends('investigation.layouts.app')

@section('title', 'Incident Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/investigation/incident-report.css') }}">
{{-- .status-badge / .status-active / .status-resolved, reused for the
     Generated Incident Records rows below. --}}
<link rel="stylesheet" href="{{ asset('css/investigation/incidents.css') }}">
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.95rem;">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible py-2 mb-3" style="font-size:.95rem;">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    // Every key here is always supplied by the controller (see
    // investigation.incident-report.show in routes/web.php) — $incident is
    // route-model-bound, so there's no "missing incident" case to fall back
    // for. vehicles/injured/roadCondition/weather are the exception: those
    // are genuinely nullable (an IoT crash report can't know them at the
    // moment it's created) and are handled as an explicit "not yet
    // recorded" state below rather than masked with a fake default.
    $timeline = $timeline ?? [];

    // Matches the severity color language used everywhere else in the app
    // (map markers, analytics charts) — see public/js/toc/analytics.js.
    $severityColors = [
        'critical' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'high'     => ['bg' => '#ffedd5', 'text' => '#9a3412'],
        'medium'   => ['bg' => '#fef9c3', 'text' => '#854d0e'],
        'low'      => ['bg' => '#d1fae5', 'text' => '#065f46'],
        'minor'    => ['bg' => '#d1fae5', 'text' => '#065f46'],
    ];
    $severityColor = $severityColors[strtolower($severity)] ?? ['bg' => '#e5e7eb', 'text' => '#374151'];

    // false_alarm gets its own neutral gray rather than being lumped in
    // with "resolved" green — a report worth reading differently than one
    // where an actual accident was handled and closed out.
    $statusColors = [
        'pending'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'dispatched'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'arrived'     => ['bg' => '#ede9fe', 'text' => '#5b21b6'],
        'resolved'    => ['bg' => '#d1fae5', 'text' => '#065f46'],
        'false_alarm' => ['bg' => '#f1f5f9', 'text' => '#475569'],
    ];
    $statusColor = $statusColors[$status ?? ''] ?? ['bg' => '#e5e7eb', 'text' => '#374151'];
    $statusLabel = $status ? ucwords(str_replace('_', ' ', $status)) : null;
@endphp

{{-- ACTION BUTTONS --}}
<div class="report-actions">
    <span class="severity-badge" style="background:{{ $severityColor['bg'] }}; color:{{ $severityColor['text'] }};">
        {{ $severity }} Severity
    </span>
    @if($statusLabel)
    <span class="severity-badge" style="background:{{ $statusColor['bg'] }}; color:{{ $statusColor['text'] }};">
        {{ $statusLabel }}
    </span>
    @endif
    <div class="report-actions-buttons">
        <button class="btn-report" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </button>
        <button class="btn-report" onclick="exportReport()">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
        </button>
        @isset($incident)
        {{-- Simple entry form, not the dense official one — same store()
             endpoint either way; the official form is still one click away
             from there ("Use Full Official Form") for cases that need it. --}}
        <a class="btn-report" href="{{ route('investigation.incident-records.simple.show', $incident) }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/><line x1="9" y1="11" x2="15" y2="11"/></svg>
            Generate IRF
        </a>
        @endisset
    </div>
</div>

{{-- TOP ROW: Summary | Details --}}
<div class="report-grid">

    {{-- LEFT: Incident Summary --}}
    <div class="report-card">

        {{-- Motorcycle / Accident Icon --}}
        <div class="incident-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 70" fill="currentColor">
                {{-- Motorcycle body --}}
                <ellipse cx="25" cy="52" rx="14" ry="14" fill="none" stroke="#111" stroke-width="4"/>
                <ellipse cx="75" cy="52" rx="14" ry="14" fill="none" stroke="#111" stroke-width="4"/>
                <path d="M25 52 L42 32 L58 32 L70 52" fill="none" stroke="#111" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M42 32 L48 20 L60 20 L65 32" fill="none" stroke="#111" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="58" y="24" width="16" height="10" rx="2" fill="#111"/>
                {{-- Rider silhouette --}}
                <circle cx="50" cy="16" r="5" fill="#111"/>
                <path d="M50 21 L46 36 L54 36 Z" fill="#111"/>
                {{-- Impact lines --}}
                <line x1="80" y1="20" x2="90" y2="10" stroke="#111" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="84" y1="24" x2="96" y2="20" stroke="#111" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="82" y1="30" x2="94" y2="30" stroke="#111" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <table class="report-table">
            <tbody>
                <tr>
                    <th scope="row">Full Name</th>
                    <td>{{ $fullName }}</td>
                </tr>
                <tr>
                    <th scope="row">Date/Time</th>
                    <td>{{ $datetime }}</td>
                </tr>
                <tr>
                    <th scope="row">Coordinates</th>
                    <td>{{ $coordinates }}</td>
                </tr>
                <tr>
                    <th scope="row">Reported By</th>
                    <td>{{ $reportedBy }}</td>
                </tr>
                <tr>
                    <th scope="row">Unit</th>
                    <td>{{ $unit }}</td>
                </tr>
                @if($deviceCode ?? null)
                {{-- Which physical IoT unit detected this — mainly an audit
                     trail: if one device starts producing repeated false
                     alarms, this is how you'd trace it back to that exact
                     device_code from the report itself. --}}
                <tr>
                    <th scope="row">Reporting Device</th>
                    <td>{{ $deviceCode }}{{ $deviceModel ? ' ('.$deviceModel.')' : '' }}</td>
                </tr>
                @endif
            </tbody>
        </table>

    </div>

    {{-- RIGHT: Incident Details --}}
    <div class="report-card">

        <div class="details-section-title">Incident Details</div>
        <div class="details-description">{{ $description }}</div>

        @if($notes ?? null)
        <div class="details-section-title">Notes</div>
        <div class="details-description">{{ $notes }}</div>
        @endif

        <div class="details-section-title d-flex align-items-center justify-content-between">
            <span>Involved</span>
            @isset($incident)
            {{-- An IoT crash report can't know any of these fields at the
                 moment it's created — they only exist once investigation
                 staff record what actually happened. --}}
            <button type="button" class="involved-edit-toggle" id="involvedEditToggle" aria-expanded="false"
                    aria-controls="involvedEditForm">Edit</button>
            @endisset
        </div>

        <table class="report-table" id="involvedDisplay">
            <tbody>
                <tr>
                    <th scope="row">Vehicle</th>
                    <td>{{ $vehicles ?? 'Not yet recorded' }}</td>
                </tr>
                <tr>
                    <th scope="row">Injured</th>
                    <td>{{ $injured ?? 'Not yet recorded' }}</td>
                </tr>
                <tr>
                    <th scope="row">Severity</th>
                    <td>{{ $severity }}</td>
                </tr>
                <tr>
                    <th scope="row">Road Condition</th>
                    <td>{{ $roadCondition ?? 'Not yet recorded' }}</td>
                </tr>
                <tr>
                    <th scope="row">Weather</th>
                    <td>{{ $weather ?? 'Not yet recorded' }}</td>
                </tr>
            </tbody>
        </table>

        @isset($incident)
        <form method="POST" action="{{ route('investigation.incident-report.update-details', $incident) }}"
              id="involvedEditForm" class="involved-edit-form" hidden>
            @csrf
            <div class="involved-edit-row">
                <label for="vehicles_involved">Vehicles Involved</label>
                <input type="number" name="vehicles_involved" id="vehicles_involved" min="0" max="255"
                       value="{{ old('vehicles_involved', $vehicles) }}">
            </div>
            <div class="involved-edit-row">
                <label for="injured_count">Injured</label>
                <input type="number" name="injured_count" id="injured_count" min="0" max="255"
                       value="{{ old('injured_count', $injured) }}">
            </div>
            <div class="involved-edit-row">
                <label for="road_condition">Road Condition</label>
                <select name="road_condition" id="road_condition">
                    <option value="" @selected(old('road_condition', $roadCondition) === null)>&mdash; Select &mdash;</option>
                    @foreach(['Dry', 'Wet', 'Icy', 'Under Repair'] as $opt)
                    <option value="{{ $opt }}" @selected(old('road_condition', $roadCondition) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="involved-edit-row">
                <label for="weather_condition">Weather</label>
                <select name="weather_condition" id="weather_condition">
                    <option value="" @selected(old('weather_condition', $weather) === null)>&mdash; Select &mdash;</option>
                    @foreach(['Clear', 'Cloudy', 'Rainy', 'Foggy', 'Stormy'] as $opt)
                    <option value="{{ $opt }}" @selected(old('weather_condition', $weather) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="involved-edit-actions">
                <button type="submit" class="btn-report btn-report-save">Save</button>
                <button type="button" class="btn-report" id="involvedEditCancel">Cancel</button>
            </div>
        </form>
        @endisset

    </div>

</div>

{{-- BOTTOM ROW: Timeline | Map --}}
<div class="report-bottom-grid">

    {{-- LEFT: Timeline / Activity Log --}}
    <div class="report-card">
        <div class="timeline-title">Timeline/Activity Log</div>
        <div class="timeline-log">
            @forelse($timeline as $entry)
            <div class="timeline-entry">
                <span class="timeline-time">{{ $entry['time'] }}</span>
                <span class="timeline-text">{{ $entry['description'] }}</span>
            </div>
            @empty
            @php
            $logs = [
                ['10:24 AM', 'Incident Reported by Pat. Reyes, Juan'],
                ['10:26 AM', 'Alert sent to nearby units'],
                ['10:31 AM', 'Patrol car 01 on rescue to location'],
                ['10:45 AM', 'Responded on scene'],
                ['11:06 AM', 'Incident updated'],
            ];
            @endphp
            @foreach($logs as $log)
            <div class="timeline-entry">
                <span class="timeline-time">{{ $log[0] }}</span>
                <span class="timeline-text">{{ $log[1] }}</span>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>

    {{-- RIGHT: Location Map --}}
    <div class="report-card">
        <div class="map-title">Location Map</div>
        <div class="map-container">
            <div id="reportMap"></div>
        </div>
    </div>

</div>

{{-- RESPONDER FIELD REPORTS — what the unit at the scene actually said.
     Deliberately its own section rather than folded into the details above:
     everything above is what the system recorded, this is a person's account,
     and the investigator signing the IRF has to be able to tell them apart.
     Read-only here — corrections are filed from the patrol app as a new
     supplemental report, so the sequence of what was said survives. --}}
@isset($fieldReports)
<div class="report-bottom-grid" style="margin-top:16px;">
    <div class="report-card" style="grid-column: 1 / -1;">
        <div class="timeline-title d-flex align-items-center justify-content-between">
            <span>Responder Field Reports</span>
            @if($fieldReports->isNotEmpty())
            <span class="badge rounded-pill" style="background:#5b21b6; font-size:.82rem;">
                {{ $fieldReports->count() }}
            </span>
            @endif
        </div>

        @if($fieldReports->isEmpty())
        <div class="timeline-log" style="color:#6b7280; font-size:.95rem;">
            No responding unit has filed a field report for this incident yet.
            Reports are submitted from the patrol app when the responder closes
            the incident.
        </div>
        @else
        @foreach($fieldReports as $report)
        @php
            $metres = $report->metresFromScene();
            $conditions = collect([
                'Vehicles involved' => $report->vehicles_involved,
                'Injured'           => $report->injured_count,
                'Road'              => $report->road_condition,
                'Weather'           => $report->weather_condition,
            ])->filter(fn ($v) => $v !== null && $v !== '');
        @endphp
        <div class="field-report">

            <div class="field-report-head">
                <div>
                    <div class="field-report-author">
                        {{ $report->patrolUnit?->full_name ?? 'Unattributed unit' }}
                        @if($report->patrolUnit?->badge_number)
                        <span class="field-report-badge">{{ $report->patrolUnit->badge_number }}</span>
                        @endif
                    </div>
                    <div class="field-report-meta">
                        Filed {{ $report->submitted_at?->format('d M Y, h:i A') ?? '—' }}
                        @if($metres !== null)
                        &middot;
                        {{-- Surfaced, not enforced. GPS fails indoors and a
                             responder may write up after leaving the scene —
                             but whoever relies on this account should be able
                             to see where it was written. --}}
                        <span @class(['field-report-far' => $metres > 500])>
                            {{ $metres < 1000 ? round($metres) . ' m' : round($metres / 1000, 1) . ' km' }}
                            from the recorded scene
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($conditions->isNotEmpty())
            <div class="field-report-conditions">
                @foreach($conditions as $label => $value)
                <span class="field-chip"><b>{{ $label }}:</b> {{ $value }}</span>
                @endforeach
            </div>
            @endif

            @if(trim((string) $report->narrative) !== '')
            <div class="field-report-narrative">{{ $report->narrative }}</div>
            @endif

            @if($report->photos->isNotEmpty())
            <div class="field-report-photos">
                @foreach($report->photos as $photo)
                @php $intact = $photo->integrityIntact(); @endphp
                <a class="field-photo" target="_blank" rel="noopener"
                   href="{{ route('incident-field-photos.show', $photo) }}"
                   title="{{ $photo->original_filename }} — {{ $photo->humanSize() }}">
                    @if($photo->exists())
                    <img src="{{ route('incident-field-photos.show', $photo) }}"
                         alt="Scene photograph filed by {{ $report->patrolUnit?->full_name ?? 'the responding unit' }}"
                         loading="lazy">
                    @else
                    <div class="field-photo-missing">File missing</div>
                    @endif
                    {{-- The digest is recorded at upload so someone can ask
                         "is this still the photograph that was taken?" — so
                         something has to actually ask. --}}
                    <span @class(['field-photo-seal', 'is-broken' => $intact === false])>
                        @if($intact === true)
                            ✓ verified
                        @elseif($intact === false)
                            ⚠ altered
                        @else
                            no digest
                        @endif
                    </span>
                </a>
                @endforeach
            </div>
            <div class="field-report-meta" style="margin-top:6px;">
                {{ $report->photos->count() }} {{ Str::plural('photograph', $report->photos->count()) }}
                &middot; stored privately, viewable only to signed-in TOC and investigation staff
            </div>
            @endif

        </div>
        @endforeach
        @endif
    </div>
</div>
@endisset

{{-- GENERATED INCIDENT RECORDS — populated when someone presses SAVE or
     SAVE & PRINT on the IRF for this incident (see
     investigation.incident-records.store). Previously the IRF only ever
     produced a printout with no trace of it here; now every save is
     recorded and listed below, and each row reopens that exact record. --}}
@isset($incidentRecords)
<div class="report-bottom-grid" style="margin-top:16px;">
    <div class="report-card" style="grid-column: 1 / -1;">
        <div class="timeline-title d-flex align-items-center justify-content-between">
            <span>Generated Incident Records (IRF)</span>
            @if($incidentRecords->isNotEmpty())
            <span class="badge rounded-pill" style="background:#1b3d52; font-size:.82rem;">
                {{ $incidentRecords->count() }}
            </span>
            @endif
        </div>
        @if($incidentRecords->isEmpty())
        <div class="timeline-log" style="color:#6b7280; font-size:.95rem;">
            No Incident Record Form has been generated for this incident yet.
        </div>
        @else
        <div class="table-responsive">
            <table class="report-table irf-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <th>Generated By</th>
                        <th>Printed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incidentRecords as $record)
                    <tr class="{{ $record->printed_at ? 'irf-record-printed' : '' }}">
                        <td>{{ $record->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <span class="status-badge {{ $record->printed_at ? 'status-resolved' : 'status-active' }}" style="font-size:.8rem; padding:.2rem .7rem;">
                                {{ $record->printed_at ? 'Saved & Printed' : 'Saved' }}
                            </span>
                        </td>
                        <td>{{ $record->generatedBy?->full_name ?? 'Unknown officer' }}</td>
                        <td>{{ $record->printed_at ? $record->printed_at->format('M d, h:i A') : '—' }}</td>
                        <td class="d-flex align-items-center gap-3">
                            <a href="{{ route('investigation.incident-records.reprint', $record) }}" class="irf-reopen-link">
                                Reopen &rarr;
                            </a>
                            {{-- Generates fresh from whatever's currently saved — works
                                 even for a record that's only ever been "Saved," not
                                 "Saved & Printed" yet, since it doesn't depend on
                                 printed_at being set. Previously the only way to see
                                 this was Reopen → wait for the ~60 fields to prefill →
                                 SAVE & VIEW PDF again, which needlessly re-saved the
                                 record just to look at something that already existed. --}}
                            <a href="{{ route('investigation.incident-records.pdf', $record) }}" target="_blank" class="irf-reopen-link">
                                View PDF
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endisset

@endsection

@push('scripts')
{{-- Server-side data the external script needs. --}}
<script>
    window.IncidentReportConfig = {
        lat: {{ $lat }},
        lng: {{ $lng }},
        fullName: @json($fullName),
        location: @json($location),
        incidentReportCssUrl: @json(asset('css/investigation/incident-report.css')),
    };
</script>
<script src="{{ asset('js/investigation/incident-report.js') }}?v={{ filemtime(public_path('js/investigation/incident-report.js')) }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initReportMap" async defer></script>
@endpush
