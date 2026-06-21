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
                </tr>
            </thead>
            <tbody id="incidentsBody">
                @forelse($incidents ?? [] as $incident)
                <tr>
                    <td>{{ $incident->full_name }}</td>
                    <td>{{ $incident->location }}</td>
                    <td><span class="incident-type">{{ $incident->type }}</span></td>
                    <td><span class="incident-time">{{ $incident->time }}</span></td>
                    <td>
                        <span class="status-badge {{ $incident->status === 'Active' ? 'status-active' : 'status-resolved' }}">
                            {{ $incident->status }}
                        </span>
                    </td>
                </tr>
                @empty
                @php
                $rows = [
                    ['Rester Mendoza',    'Brgy. Cabuloan,<br>Urdaneta City, Pangasinan',    'HEAD ON COLLISION',        'April 20, 10:30 AM', 'Active'],
                    ['Darnil Castanieto', 'Brgy. Pinmaludpod,<br>Urdaneta City, Pangasinan', 'HEAD ON COLLISION',        'April 14, 11:30 PM', 'Resolved'],
                    ['Gian Rodriguez',    'Urdaneta Bypass Road',                             'DOORING ACCIDENT',         'April 22, 12:30 AM', 'Active'],
                    ['Adrian Sarmiento', 'Brgy. Cabuloan,<br>Urdaneta City, Pangasinan',    'LANE SWITCHING ACCIDENT',  'April 23, 9:30 PM',  'Active'],
                    ['Danilo Lingo',      'Brgy. Pinmaludpod,<br>Urdaneta City, Pangasinan', 'LOSS OF CONTROL',          'March 23, 10:30 AM', 'Resolved'],
                    ['Axel Oxiles',       'Urdaneta Bypass Road',                             'ROAD DEPARTURE',           'April 20, 2:45 AM',  'Active'],
                    ['John Paul Nitura',  'Brgy. Cabuloan,<br>Urdaneta City, Pangasinan',    'GROUP RIDING COLLISIONS',  'April 16, 10:30 AM', 'Active'],
                    ['Kurt Cruz',         'Brgy. Pinmaludpod,<br>Urdaneta City, Pangasinan', 'ROAD DEPARTURE',           'March 02, 4:30 PM',  'Resolved'],
                    ['Clark Ramirez',     'Urdaneta Bypass Road',                             'ROAD DEPARTURE',           'April 06, 6:34 AM',  'Active'],
                    ['Carlo Dizon',       'Brgy. Cabuloan,<br>Urdaneta City, Pangasinan',    'LANE SWITCHING ACCIDENT',  'April 20, 10:30 AM', 'Active'],
                    ['Carl Villanueva',   'Brgy. Pinmaludpod,<br>Urdaneta City, Pangasinan', 'LOSS OF CONTROL',          'April 01, 8:30 PM',  'Resolved'],
                    ['Mark Miranda',      'Urdaneta Bypass Road',                             'LOSS OF CONTROL',          'April 24, 7:31 AM',  'Active'],
                ];
                @endphp
                @foreach($rows as $r)
                <tr data-status="{{ $r[4] }}" data-date="{{ explode(' ', $r[3])[0] }}">
                    <td>{{ $r[0] }}</td>
                    <td>{!! $r[1] !!}</td>
                    <td><span class="incident-type">{{ $r[2] }}</span></td>
                    <td><span class="incident-time">{{ $r[3] }}</span></td>
                    <td>
                        <span class="status-badge {{ $r[4] === 'Active' ? 'status-active' : 'status-resolved' }}">
                            {{ $r[4] }}
                        </span>
                    </td>
                </tr>
                @endforeach
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
            const cells = [...r.querySelectorAll('td')].map(td => `"${td.innerText.replace(/\n/g,' ')}"`);
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
