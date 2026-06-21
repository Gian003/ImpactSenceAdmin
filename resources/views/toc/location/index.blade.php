@extends('toc.layouts.app')

@section('title', 'Location Tracking')

@push('notifications')
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Mendoza has arrived</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Castanieto has arrived</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Castanieto is on the way</span></li>
    <li><span class="dropdown-item" style="font-size:.82rem;">P01 Mendoza is on the way</span></li>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/toc/location.css') }}">
@endpush

@section('content')

{{-- ACCIDENT ALERT CARDS --}}
<div class="row g-3 mb-3">

    {{-- Alert 1 --}}
    <div class="col-md-6">
        <div class="p-3 position-relative rounded-3 border border-2"
             style="background:#fde8e8; border-color:#d97070 !important;">
            <span class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                  style="top:12px; right:12px; width:28px; height:28px; background:#1a1a1a; font-size:1rem;">!</span>
            <h6 class="fw-bold mb-3">Accident Alert!</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Rester Mendoza
                    </div>
                    <div class="ps-3 text-dark" style="font-size:.8rem;">09123456789</div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        Current Location
                    </div>
                    <div class="ps-3 lh-sm text-dark" style="font-size:.8rem;">Barangay Pinmaludpod,<br>Urdaneta City, Pangasinan</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert 2 --}}
    <div class="col-md-6">
        <div class="p-3 position-relative rounded-3 border border-2"
             style="background:#fde8e8; border-color:#d97070 !important;">
            <span class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                  style="top:12px; right:12px; width:28px; height:28px; background:#1a1a1a; font-size:1rem;">!</span>
            <h6 class="fw-bold mb-3">Accident Alert!</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Darnil Castanieto
                    </div>
                    <div class="ps-3 text-dark" style="font-size:.8rem;">09123456789</div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.75rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                        Current Location
                    </div>
                    <div class="ps-3 lh-sm text-dark" style="font-size:.8rem;">Barangay Cabuloan,<br>Urdaneta City, Pangasinan</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MAP + OVERLAYS --}}
<div class="map-wrap">
    <div id="map"></div>

    {{-- Legend toggle card --}}
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

    {{-- Speed Reports panel --}}
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

    {{-- Patrollers panel --}}
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
<script>
function initMap() {
    const center = { lat: 15.9755, lng: 120.5651 };

    const map = new google.maps.Map(document.getElementById('map'), {
        center,
        zoom: 14,
        mapTypeId: 'roadmap',
        zoomControl: true,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
    });

    const infoWindow = new google.maps.InfoWindow();

    const redDotIcon = {
        url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
        scaledSize: new google.maps.Size(32, 32),
    };

    function addMarker(lat, lng, name, address) {
        const marker = new google.maps.Marker({ position: { lat, lng }, map, icon: redDotIcon, title: name });
        marker.addListener('click', () => {
            infoWindow.setContent(`<b>${name}</b><br>${address}`);
            infoWindow.open(map, marker);
        });
        return marker;
    }

    addMarker(15.963, 120.553, 'Rester Mendoza',    'Brgy. Pinmaludpod, Urdaneta City');
    addMarker(15.981, 120.582, 'Darnil Castanieto', 'Brgy. Cabuloan, Urdaneta City');

    // Accident-prone area polygons (shown when "Accident Prone Area" is toggled)
    const pronePolygons = [
        new google.maps.Polygon({ paths: [{lat:15.972,lng:120.545},{lat:15.970,lng:120.565},{lat:15.960,lng:120.560},{lat:15.962,lng:120.540}], strokeColor:'#e53e3e', strokeWeight:1, fillColor:'#e53e3e', fillOpacity:.35 }),
        new google.maps.Polygon({ paths: [{lat:15.978,lng:120.572},{lat:15.976,lng:120.590},{lat:15.965,lng:120.588},{lat:15.967,lng:120.570}], strokeColor:'#e53e3e', strokeWeight:1, fillColor:'#e53e3e', fillOpacity:.35 }),
        new google.maps.Polygon({ paths: [{lat:15.985,lng:120.553},{lat:15.983,lng:120.570},{lat:15.972,lng:120.568},{lat:15.974,lng:120.550}], strokeColor:'#dd6b20', strokeWeight:1, fillColor:'#dd6b20', fillOpacity:.30 }),
        new google.maps.Polygon({ paths: [{lat:15.958,lng:120.570},{lat:15.956,lng:120.588},{lat:15.945,lng:120.585},{lat:15.947,lng:120.567}], strokeColor:'#d69e2e', strokeWeight:1, fillColor:'#d69e2e', fillOpacity:.28 }),
    ];

    let activePanel = null;

    window.togglePanel = function(panel) {
        const els  = { speed: document.getElementById('speedPanel'), patrollers: document.getElementById('patrollersPanel') };
        const btns = { speed: document.getElementById('btnSpeed'), prone: document.getElementById('btnProne'), patrollers: document.getElementById('btnPatrollers') };
        const proneSub = document.getElementById('proneSub');

        const closing = activePanel === panel;

        // Reset everything
        Object.values(els).forEach(e => e.classList.remove('show'));
        Object.values(btns).forEach(b => b.classList.remove('active'));
        proneSub.classList.remove('show');
        pronePolygons.forEach(p => p.setMap(null));
        activePanel = null;

        if (closing) return;

        // Activate chosen panel
        activePanel = panel;
        btns[panel].classList.add('active');
        if (panel === 'speed')       els.speed.classList.add('show');
        if (panel === 'patrollers')  els.patrollers.classList.add('show');
        if (panel === 'prone')       { proneSub.classList.add('show'); pronePolygons.forEach(p => p.setMap(map)); }
    };
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA1Pg5n88KZWoCCmyEM_1ohx-elRiAVWtY&callback=initMap" async defer></script>
@endpush
