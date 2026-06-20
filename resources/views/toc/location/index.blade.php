@extends('toc.layouts.app')

@section('title', 'Location Tracking')

@push('notifications')
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Mendoza has arrived</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Castanieto has arrived</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Castanieto is on the way</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Mendoza is on the way</span></li>
@endpush

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('css/toc/location.css') }}">
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="alert-accident p-3">
            <div class="alert-bang">!</div>
            <h6 class="fw-bold mb-3">Accident Alert!</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.75rem; font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Rester Mendoza
                    </div>
                    <div class="ps-3" style="font-size:.8rem; color:#333;">09123456789</div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.75rem; font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        Current Location
                    </div>
                    <div class="ps-3 lh-sm" style="font-size:.8rem; color:#333;">Barangay Pinmaludpod,<br>Urdaneta City, Pangasinan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert-accident p-3">
            <div class="alert-bang">!</div>
            <h6 class="fw-bold mb-3">Accident Alert!</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.75rem; font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Darnil Castanieto
                    </div>
                    <div class="ps-3" style="font-size:.8rem; color:#333;">09123456789</div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.75rem; font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        Current Location
                    </div>
                    <div class="ps-3 lh-sm" style="font-size:.8rem; color:#333;">Barangay Cabuloan,<br>Urdaneta City, Pangasinan</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="map-wrap">
    <div id="map"></div>
    <div class="map-legend-card">
        <button class="legend-btn" id="btnSpeed" onclick="togglePanel('speed')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/></svg>
            Speed Reports per Area
        </button>
        <button class="legend-btn" id="btnProne" onclick="togglePanel('prone')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Accident Prone Area
        </button>
        <div class="prone-sub" id="proneSub">
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#e53e3e;"></div> High</div>
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#dd6b20;"></div> Average</div>
            <div class="d-flex align-items-center gap-2" style="font-size:.78rem;"><div class="prone-dot" style="background:#d69e2e;"></div> Low</div>
        </div>
        <button class="legend-btn" id="btnPatrollers" onclick="togglePanel('patrollers')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Patrollers
        </button>
    </div>
    <div class="map-panel" id="speedPanel">
        <div class="panel-card" style="min-width:380px;">
            <table>
                <thead><tr><th>Street</th><th>Barangay</th><th>Recommended Speed</th></tr></thead>
                <tbody>
                    <tr><td>Vladimir V. Lalas</td><td>Brgy. Cabuloan</td><td>50 kph</td></tr>
                    <tr><td>Anabel T. Ganancial</td><td>Brgy. Pinmaludpod</td><td>66 kph</td></tr>
                    <tr><td>Jesus D. Tambalo</td><td>Urdaneta Bypass Road</td><td>90 kph</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="patrollers-panel" id="patrollersPanel">
        <div class="panel-card">
            <table>
                <thead><tr><th>STAND BY</th><th>LOCATION</th></tr></thead>
                <tbody>
                    <tr><td>Vladimir V. Lalas</td><td>Brgy. Tipuso, Urdaneta City, Pangasinan</td></tr>
                    <tr><td>Anabel T. Ganancial</td><td>Brgy. Nancayasan, Urdaneta City, Pangasinan</td></tr>
                    <tr><td>Jesus D. Tambalo</td><td>Brgy. Mabanogbog, Urdaneta City, Pangasinan</td></tr>
                </tbody>
            </table>
        </div>
        <div class="panel-card">
            <table>
                <thead><tr><th>IN ACTION</th><th>LOCATION</th></tr></thead>
                <tbody>
                    <tr><td>Gian Rodriguez</td><td>Urdaneta Bypass Road</td></tr>
                    <tr><td>Rester Mendoza</td><td>Brgy. Pinmaludpod, Urdaneta City, Pangasinan</td></tr>
                    <tr><td>Darnil Castanieto</td><td>Brgy. Cabuloan, Urdaneta City, Pangasinan</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map', { zoomControl: true }).setView([15.9755, 120.5651], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 18 }).addTo(map);
    const redIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowSize: [41,41] });
    L.marker([15.963, 120.553], { icon: redIcon }).addTo(map).bindPopup('<b>Rester Mendoza</b><br>Brgy. Pinmaludpod');
    L.marker([15.981, 120.582], { icon: redIcon }).addTo(map).bindPopup('<b>Darnil Castanieto</b><br>Brgy. Cabuloan');
    const proneGroup = L.layerGroup([
        L.polygon([[15.972,120.545],[15.970,120.565],[15.960,120.560],[15.962,120.540]], { color:'#e53e3e', fillColor:'#e53e3e', fillOpacity:.35, weight:1 }),
        L.polygon([[15.978,120.572],[15.976,120.590],[15.965,120.588],[15.967,120.570]], { color:'#e53e3e', fillColor:'#e53e3e', fillOpacity:.35, weight:1 }),
        L.polygon([[15.985,120.553],[15.983,120.570],[15.972,120.568],[15.974,120.550]], { color:'#dd6b20', fillColor:'#dd6b20', fillOpacity:.30, weight:1 }),
        L.polygon([[15.958,120.570],[15.956,120.588],[15.945,120.585],[15.947,120.567]], { color:'#d69e2e', fillColor:'#d69e2e', fillOpacity:.28, weight:1 }),
    ]);
    let activePanel = null;
    function togglePanel(panel) {
        const els  = { speed: document.getElementById('speedPanel'), patrollers: document.getElementById('patrollersPanel') };
        const btns = { speed: document.getElementById('btnSpeed'), prone: document.getElementById('btnProne'), patrollers: document.getElementById('btnPatrollers') };
        const proneSub = document.getElementById('proneSub');
        if (activePanel === panel) { Object.values(els).forEach(e => e.classList.remove('show')); Object.values(btns).forEach(b => b.classList.remove('active')); proneSub.classList.remove('show'); proneGroup.removeFrom(map); activePanel = null; return; }
        Object.values(els).forEach(e => e.classList.remove('show')); Object.values(btns).forEach(b => b.classList.remove('active')); proneSub.classList.remove('show'); proneGroup.removeFrom(map);
        activePanel = panel; btns[panel].classList.add('active');
        if (panel === 'speed') els.speed.classList.add('show');
        if (panel === 'patrollers') els.patrollers.classList.add('show');
        if (panel === 'prone') { proneSub.classList.add('show'); proneGroup.addTo(map); }
    }
</script>
@endpush
