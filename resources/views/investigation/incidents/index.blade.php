@extends('investigation.layouts.app')

@section('title', 'Incidents')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/investigation/incidents.css') }}">
@endpush

@section('content')

{{-- FILTER BAR --}}
<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">

    {{-- Search --}}
    <div class="input-group flex-grow-1" style="min-width:200px;">
        <span class="input-group-text bg-white" style="border-color:#c8d8e4;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                 stroke="#6b7280" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <input type="text" id="searchInput" class="form-control"
               placeholder="Search Incidents..."
               style="border-color:#c8d8e4;">
    </div>

    {{-- Status filter --}}
    <select class="form-select" id="statusFilter" style="max-width:150px; border-color:#c8d8e4;">
        <option value="">All Status</option>
        <option value="Active">Active</option>
        <option value="Resolved">Resolved</option>
    </select>

    {{-- Date filter --}}
    <select class="form-select" id="dateFilter" style="max-width:150px; border-color:#c8d8e4;">
        <option value="">All Dates</option>
        <option value="April">April</option>
        <option value="March">March</option>
        <option value="February">February</option>
    </select>

    {{-- Export --}}
    <button class="btn text-white fw-semibold px-4" onclick="exportTable()"
            style="background:#1b3d52; border-color:#1b3d52;">
        Export
    </button>

</div>

{{-- INCIDENTS TABLE --}}
<div class="card border rounded-3" style="border-color:#d1dde6 !important;">
    <div class="table-responsive">
        <table class="incidents-table w-100" id="incidentsTable">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>IRF</th>
                </tr>
            </thead>
            <tbody id="incidentsBody">
                @forelse($incidents ?? [] as $incident)
                @php
                    $uiStatus = in_array($incident->status, ['pending','dispatched']) ? 'Active' : 'Resolved';
                @endphp
                <tr data-status="{{ $uiStatus }}" data-date="{{ $incident->created_at->format('F') }}"
                    onclick="window.location='{{ route('investigation.incident-report.show', $incident) }}'"
                    style="cursor:pointer;">
                    <td>{{ $incident->rider?->full_name ?? 'N/A' }}</td>
                    <td>{{ $incident->address ?? 'N/A' }}</td>
                    <td><span class="incident-type">{{ strtoupper($incident->type) }}</span></td>
                    <td><span class="incident-time">{{ $incident->created_at->format('F d, h:i A') }}</span></td>
                    <td>
                        <span class="status-badge {{ $uiStatus === 'Active' ? 'status-active' : 'status-resolved' }}">
                            {{ $uiStatus }}
                        </span>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="{{ route('investigation.incident-records.show', $incident) }}"
                           class="text-decoration-none" style="color:#4b7a96; font-weight:600; font-size:.8rem;">
                            Generate
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4" style="font-size:.83rem;">No incidents recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const date   = document.getElementById('dateFilter').value;

        document.querySelectorAll('#incidentsBody tr').forEach(row => {
            const text       = row.textContent.toLowerCase();
            const rowStatus  = row.dataset.status ?? '';
            const rowDate    = row.dataset.date   ?? '';

            const matchSearch = text.includes(search);
            const matchStatus = !status || rowStatus === status;
            const matchDate   = !date   || rowDate === date;

            row.style.display = (matchSearch && matchStatus && matchDate) ? '' : 'none';
        });
    }

    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('dateFilter').addEventListener('change', filterTable);

    function exportTable() {
        const rows  = [...document.querySelectorAll('#incidentsBody tr')]
                        .filter(r => r.style.display !== 'none');
        const header = 'Full Name,Location,Type,Time,Status\n';
        const csv    = header + rows.map(r => {
            const cells = [...r.querySelectorAll('td')].slice(0, -1).map(td => `"${td.innerText.replace(/\n/g,' ')}"`);
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
