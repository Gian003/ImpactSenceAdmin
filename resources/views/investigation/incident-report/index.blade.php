@extends('investigation.layouts.app')

@section('title', 'Incident Report')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('css/investigation/incident-report.css') }}">
@endpush

@section('content')

@php
    // Default values used when no $incident is passed from a controller yet.
    // Replace each line with $incident->property once a controller is wired up.
    $fullName      = $incident->full_name      ?? 'Rester Mendoza';
    $datetime      = $incident->datetime       ?? 'April 20, 10:30 AM';
    $coordinates   = $incident->coordinates    ?? '(15.9765° N, 120.5715° E)';
    $reportedBy    = $incident->reported_by    ?? 'Pat. Reyes, Juan';
    $unit          = $incident->unit           ?? 'Patrol Car 01';
    $description   = $incident->description   ?? 'A collision between a motorcycle and a van near of the intersection of Brrgy. Cabuloan, Urdaneta City';
    $vehicles      = $incident->vehicles      ?? 2;
    $injured       = $incident->injured       ?? 1;
    $severity      = $incident->severity      ?? 'Moderate';
    $roadCondition = $incident->road_condition ?? 'Wet';
    $weather       = $incident->weather       ?? 'Cloudy';
    $location      = $incident->location      ?? 'Brgy. Cabuloan, Urdaneta City';
    $lat           = $incident->lat           ?? 15.963;
    $lng           = $incident->lng           ?? 120.553;
    $timeline      = $incident->timeline      ?? [];
@endphp

{{-- ACTION BUTTONS --}}
<div class="report-actions">
    <button class="btn-report" onclick="window.print()">Print</button>
    <button class="btn-report" onclick="exportReport()">Export</button>
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

        <div class="summary-field">
            <div class="field-label">Full Name</div>
            <div class="field-value">{{ $fullName }}</div>
        </div>

        <div class="summary-field">
            <div class="field-label">Date/Time</div>
            <div class="field-value">{{ $datetime }}</div>
        </div>

        <div class="summary-field">
            <div class="field-label">Coordinates</div>
            <div class="field-value">{{ $coordinates }}</div>
        </div>

        <div class="summary-field">
            <div class="field-label">Reported By</div>
            <div class="field-value">{{ $reportedBy }}</div>
        </div>

        <div class="summary-field">
            <div class="field-label">Unit</div>
            <div class="field-value">{{ $unit }}</div>
        </div>

    </div>

    {{-- RIGHT: Incident Details --}}
    <div class="report-card">

        <div class="details-section-title">Incident Details</div>
        <div class="details-description">{{ $description }}</div>

        <div class="details-section-title">Involved</div>
        <ul class="involved-list">
            <li>
                <span class="inv-label">Vehicle</span>
                <span class="inv-value">: {{ $vehicles }}</span>
            </li>
            <li>
                <span class="inv-label">Injured</span>
                <span class="inv-value">: {{ $injured }}</span>
            </li>
            <li>
                <span class="inv-label">Severity</span>
                <span class="inv-value">: {{ $severity }}</span>
            </li>
            <li>
                <span class="inv-label">Road Condition</span>
                <span class="inv-value">: {{ $roadCondition }}</span>
            </li>
            <li>
                <span class="inv-label">Weather</span>
                <span class="inv-value">: {{ $weather }}</span>
            </li>
        </ul>

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
                <span class="timeline-time">{{ $entry->time }}</span>
                <span class="timeline-text">{{ $entry->description }}</span>
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

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const lat = {{ $lat }};
    const lng = {{ $lng }};

    const map = L.map('reportMap', { zoomControl: true }).setView([lat, lng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 18
    }).addTo(map);

    const redIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
    });

    L.marker([lat, lng], { icon: redIcon })
        .addTo(map)
        .bindPopup('<b>{{ $fullName }}</b><br>{{ $location }}')
        .openPopup();

    function exportReport() {
        const content = document.querySelector('.content').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`
            <html><head>
                <title>Incident Report</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="{{ asset('css/investigation/incident-report.css') }}" rel="stylesheet">
                <style>
                    .report-actions { display:none; }
                    body { padding: 24px; background:#fff; }
                </style>
            </head><body>${content}</body></html>
        `);
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); win.close(); }, 800);
    }
</script>
@endpush
