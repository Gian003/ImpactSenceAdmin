@extends('investigation.layouts.app')

@section('title', 'Incidents')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/investigation/incidents.css') }}">
@endpush

@section('content')

{{-- The filters follow the same questions the IRF's Item D asks of a
     narrative — who, what, when, where — so an officer narrows this list
     using the categories they already fill the form in with.
     Why/how deliberately has no filter: it lives in the narrative, which is
     free text on the record itself, not a property a list can sort by. --}}
<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">

    {{-- WHO / WHERE — one box, because a rider's name and a barangay are
         both things an officer half-remembers and types rather than picks. --}}
    <div class="input-group flex-grow-1" style="min-width:220px;">
        <span class="input-group-text bg-white" style="border-color:#c8d8e4;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                 stroke="#6b7280" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <input type="text" id="searchInput" class="form-control"
               placeholder="Search rider or location..."
               style="border-color:#c8d8e4;">
    </div>

    {{-- WHAT — how bad, and what kind --}}
    <select class="form-select" id="severityFilter" style="max-width:150px; border-color:#c8d8e4;">
        <option value="">All Severity</option>
        <option value="critical">Critical</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
    </select>

    <select class="form-select" id="typeFilter" style="max-width:150px; border-color:#c8d8e4;">
        <option value="">All Types</option>
        @foreach($incidentTypes ?? [] as $type)
        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
        @endforeach
    </select>

    {{-- The four states actually stored, rather than the old Active/Resolved
         pair that filed false alarms in with genuinely resolved cases. --}}
    <select class="form-select" id="statusFilter" style="max-width:160px; border-color:#c8d8e4;">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="dispatched">Dispatched</option>
        <option value="resolved">Resolved</option>
        <option value="false_alarm">False Alarm</option>
    </select>

    {{-- WHEN — month and year, since the data already spans two years --}}
    <select class="form-select" id="dateFilter" style="max-width:170px; border-color:#c8d8e4;">
        <option value="">All Dates</option>
        @foreach($incidentMonths ?? [] as $month)
        <option value="{{ $month }}">{{ $month }}</option>
        @endforeach
    </select>

    <button class="btn text-white fw-semibold px-4" onclick="exportTable()"
            style="background:#7B1A2E; border-color:#7B1A2E; border-radius:7px;">
        Export
    </button>
</div>

{{-- Live count, so it's obvious when a filter combination has narrowed the
     list to nothing rather than the table just appearing broken. --}}
<p class="text-muted mb-3" style="font-size:.85rem;">
    Showing <span id="resultCount">{{ count($incidents ?? []) }}</span>
    of {{ count($incidents ?? []) }} incidents
    <button type="button" id="clearFilters" class="btn btn-link p-0 ms-2"
            style="font-size:.85rem; color:#7B1A2E; display:none;">Clear filters</button>
</p>

{{-- INCIDENTS TABLE --}}
<div class="card border-0 rounded-3 overflow-hidden" style="border: 1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="incidents-table w-100" id="incidentsTable">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>IRF</th>
                </tr>
            </thead>
            <tbody id="incidentsBody">
                @forelse($incidents ?? [] as $incident)
                @php
                    $statusClass = match($incident->status) {
                        'pending'     => 'status-pending',
                        'dispatched'  => 'status-dispatched',
                        'resolved'    => 'status-resolved',
                        'false_alarm' => 'status-false-alarm',
                        default       => 'status-pending',
                    };
                @endphp
                <tr data-status="{{ $incident->status }}"
                    data-severity="{{ $incident->severity }}"
                    data-type="{{ $incident->type }}"
                    data-date="{{ $incident->created_at->format('F Y') }}"
                    onclick="window.location='{{ route('investigation.incident-report.show', $incident) }}'"
                    style="cursor:pointer;">
                    <td>{{ $incident->rider?->full_name ?? 'N/A' }}</td>
                    <td>{{ $incident->address ?? 'N/A' }}</td>
                    <td><span class="incident-type">{{ strtoupper(str_replace('_', ' ', $incident->type)) }}</span></td>
                    <td>
                        <span class="sev-badge sev-{{ $incident->severity }}">
                            {{ ucfirst($incident->severity) }}
                        </span>
                    </td>
                    <td><span class="incident-time">{{ $incident->created_at->format('M d, Y h:i A') }}</span></td>
                    <td>
                        <span class="status-badge {{ $statusClass }}">
                            {{ ucwords(str_replace('_', ' ', $incident->status)) }}
                        </span>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="{{ route('investigation.incident-records.show', $incident) }}"
                           class="incident-action-link">
                            Generate
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4" style="font-size:.83rem;">No incidents recorded yet.</td></tr>
                @endforelse
                {{-- Shown by the filter script when a combination matches nothing;
                     an empty table with no explanation reads as a broken page. --}}
                <tr id="noMatchRow" style="display:none;">
                    <td colspan="7" class="text-center text-muted py-4" style="font-size:.83rem;">
                        No incidents match these filters.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const filters = {
        search:   document.getElementById('searchInput'),
        severity: document.getElementById('severityFilter'),
        type:     document.getElementById('typeFilter'),
        status:   document.getElementById('statusFilter'),
        date:     document.getElementById('dateFilter'),
    };

    const resultCount  = document.getElementById('resultCount');
    const noMatchRow   = document.getElementById('noMatchRow');
    const clearButton  = document.getElementById('clearFilters');

    // Every data row, excluding the two placeholder rows which must never be
    // counted as results or hidden by a search term.
    function dataRows() {
        return [...document.querySelectorAll('#incidentsBody tr[data-status]')];
    }

    function filterTable() {
        const search   = filters.search.value.toLowerCase().trim();
        const severity = filters.severity.value;
        const type     = filters.type.value;
        const status   = filters.status.value;
        const date     = filters.date.value;

        let shown = 0;

        dataRows().forEach(row => {
            const matches =
                (!search   || row.textContent.toLowerCase().includes(search)) &&
                (!severity || row.dataset.severity === severity) &&
                (!type     || row.dataset.type     === type)     &&
                (!status   || row.dataset.status   === status)   &&
                (!date     || row.dataset.date     === date);

            row.style.display = matches ? '' : 'none';
            if (matches) shown++;
        });

        resultCount.textContent = shown;

        const anyFilterActive = Boolean(search || severity || type || status || date);
        noMatchRow.style.display  = (shown === 0 && dataRows().length > 0) ? '' : 'none';
        clearButton.style.display = anyFilterActive ? '' : 'none';
    }

    Object.values(filters).forEach(el => {
        el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', filterTable);
    });

    clearButton.addEventListener('click', () => {
        Object.values(filters).forEach(el => { el.value = ''; });
        filterTable();
    });

    // Exports exactly what's on screen, so a filtered view and its CSV always
    // agree. The trailing IRF column is dropped — it's an action, not data.
    function exportTable() {
        const rows = dataRows().filter(r => r.style.display !== 'none');
        const header = 'Full Name,Location,Type,Severity,Time,Status\n';
        const csv = header + rows.map(r => {
            const cells = [...r.querySelectorAll('td')].slice(0, -1)
                .map(td => `"${td.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""')}"`);
            return cells.join(',');
        }).join('\n');

        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'incidents.csv';
        a.click();
        URL.revokeObjectURL(url);
    }
</script>
@endpush
