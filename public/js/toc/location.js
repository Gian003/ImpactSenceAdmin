// Location Tracking page — map, patrol/incident markers, dispatch, and the
// live-alert feed. Extracted from resources/views/toc/location/index.blade.php
// into a real .js file; all server-side data comes in via
// window.LocationTrackingConfig (set by a small inline <script> in that
// Blade file right before this one loads).

// Panel/legend toggling is defined at top level — independent of
        // initMap() below — so the Speed Reports and Patrollers tabs work even
        // if Google Maps fails to load (bad/restricted API key, no network,
        // quota exceeded). Only the Accident Prone Area heatmap actually needs
        // the map object, and degrades gracefully (sub-legend still shows,
        // heatmap layer just won't render) if `heatmap` never gets assigned.
        let map = null;
        let heatmap = null;
        let trafficLayer = null;
        let trafficOn = false;
        let satelliteOn = false;
        let activePanel = null;
        let directionsService = null;
        let infoWindow =
            null; // shared by patrol and incident markers in both script blocks, so only one bubble is ever open
        // Keyed by incident id, shared with the live-alert script block below, so a
        // dispatched/resolved status update can find and update/remove the marker
        // that was created either at page load or from a later live report.
        const incidentMarkerById = {};
        // Same key as incidentMarkerById — holds the actual incident facts (rider,
        // address, status, severity, assigned patrol unit, reported time) so a
        // marker's info window can be rebuilt live instead of freezing whatever was
        // true at the moment the marker was created.
        const incidentDataById = {};

        // Populated inside initMap() from the page-load snapshot; declared here (not
        // as a local const there) so the dispatch form built by incidentInfoContent
        // below — called from marker click handlers outside initMap()'s scope — can
        // see it too. Same staleness caveat as the card's own dispatch dropdown: it
        // reflects patrol status as of page load, not live updates.
        let patrollersData = [];

        // Mirrors the "Dispatch patrol" form already on the accident alert cards —
        // same route, same fields, same real POST-and-reload behavior — just
        // reachable straight from the marker for an operator who's already looking
        // at the map instead of having to go find the matching card.
        const dispatchUrlTemplate = window.LocationTrackingConfig.dispatchUrlTemplate;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function dispatchFormHtml(id) {
            if (!patrollersData.length) return '';
            const options = patrollersData.map(p =>
                `<option value="${p.id}">${p.full_name} (${p.badge_number ?? ''}) &mdash; ${p.status}</option>`
            ).join('');
            const action = dispatchUrlTemplate.replace('__ID__', id);
            return `
        <form method="POST" action="${action}" style="margin-top:8px;">
            <input type="hidden" name="_token" value="${csrfToken}">
            <select name="patrol_unit_id" required
                    style="font-size:.86rem; padding:3px 6px; border-radius:5px; border:1px solid #ccc; width:100%; margin-bottom:5px;">
                <option value="">Select patrol unit&hellip;</option>
                ${options}
            </select>
            <button type="submit"
                    style="width:100%; padding:5px; font-size:.86rem; font-weight:700; color:#fff; background:#1b3d52; border:none; border-radius:5px; cursor:pointer;">
                Dispatch
            </button>
        </form>`;
        }

        function formatAgo(ms) {
            const mins = Math.floor(ms / 60000);
            if (mins < 1) return 'just now';
            if (mins < 60) return `${mins}m ago`;
            const hrs = Math.floor(mins / 60);
            return `${hrs}h ${mins % 60}m ago`;
        }

        function incidentInfoContent(id) {
            const d = incidentDataById[id];
            if (!d) return '';
            const statusColors = {
                pending: '#b91c1c',
                dispatched: '#2a7c5b',
                resolved: '#64748b',
                false_alarm: '#64748b'
            };
            const agoText = d.reportedAt ? formatAgo(Date.now() - new Date(d.reportedAt).getTime()) : '—';

            let html = `<b>${d.riderName}</b><br>${d.riderPhone}<br>${d.address}`;
            html += `<br>Type: ${d.type ?? '—'} &middot; Severity: ${d.severity ?? '—'}`;
            html +=
                `<br><span style="color:${statusColors[d.status] ?? '#374151'}; font-weight:600;">${(d.status ?? '').toUpperCase()}</span>`;
            if (d.patrolUnitName) {
                html += `<br>Dispatched: ${d.patrolUnitName}${d.patrolUnitBadge ? ' (' + d.patrolUnitBadge + ')' : ''}`;
            }
            html += `<br><span style="font-size:.88rem; color:#64748b;">Reported ${agoText}</span>`;
            if (d.status === 'pending') {
                html += dispatchFormHtml(id);
            }
            return html;
        }
        const incidentCircleById = {}; // critical-severity pulsing halos, same lifecycle as incidentMarkerById
        let directionsRenderer = null;

        // Draws a real driving route (not the straight-line estimate shown by
        // default) from a patrol unit to an incident — only fires on a deliberate
        // click, since Directions is a billed API call unlike the map tiles that
        // load for free.
        window.routeToIncident = function(fromLat, fromLng, toLat, toLng) {
            if (!directionsService || !directionsRenderer) return;
            directionsService.route({
                origin: {
                    lat: fromLat,
                    lng: fromLng
                },
                destination: {
                    lat: toLat,
                    lng: toLng
                },
                travelMode: google.maps.TravelMode.DRIVING,
            }, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                } else {
                    alert('Could not calculate a route: ' + status);
                }
            });
        };

        window.togglePanel = function(panel) {
            const els = {
                speed: document.getElementById('speedPanel'),
                patrollers: document.getElementById('patrollersPanel'),
                prone: document.getElementById('pronePanel')
            };
            const btns = {
                speed: document.getElementById('btnSpeed'),
                prone: document.getElementById('btnProne'),
                patrollers: document.getElementById('btnPatrollers')
            };
            const proneSub = document.getElementById('proneSub');

            const closing = activePanel === panel;

            // Reset everything
            Object.values(els).forEach(e => e.classList.remove('show'));
            Object.values(btns).forEach(b => b.classList.remove('active'));
            proneSub.classList.remove('show');
            if (heatmap) heatmap.setMap(null);
            activePanel = null;

            if (closing) return;

            // Activate chosen panel
            activePanel = panel;
            btns[panel].classList.add('active');
            if (panel === 'speed') els.speed.classList.add('show');
            if (panel === 'patrollers') els.patrollers.classList.add('show');
            if (panel === 'prone') {
                proneSub.classList.add('show');
                els.prone.classList.add('show');
                if (heatmap) heatmap.setMap(map);
            }
        };

        // Live traffic is its own overlay, not one of the exclusive side panels
        // above — an operator may want it on together with any panel, so it toggles
        // independently rather than going through togglePanel().
        window.toggleTraffic = function() {
            if (!trafficLayer) return;
            trafficOn = !trafficOn;
            trafficLayer.setMap(trafficOn ? map : null);
            document.getElementById('btnTraffic').classList.toggle('active', trafficOn);
        };

        // Also independent of the exclusive panels — an operator may want satellite
        // imagery together with any data panel, or with traffic on top of it.
        window.toggleSatellite = function() {
            if (!map) return;
            satelliteOn = !satelliteOn;
            map.setMapTypeId(satelliteOn ? 'hybrid' :
                'roadmap'); // hybrid = satellite imagery with road/place labels still on
            document.getElementById('btnSatellite').classList.toggle('active', satelliteOn);
        };

        // Severity controls marker size — a minor report and a critical one
        // shouldn't look the same on a map an operator is scanning for what to act
        // on first. Status still controls color (red = pending, orange = handled).
        // Declared at top level (not inside initMap()) because the live-alert
        // script block below needs to call these too, for incidents reported or
        // updated after the initial page load.
        const severityIconSizes = {
            low: 22,
            medium: 26,
            high: 32,
            critical: 40
        };

        function incidentIcon(status, severity) {
            const size = severityIconSizes[severity] ?? severityIconSizes.high;
            return {
                url: status === 'dispatched' ?
                    'https://maps.google.com/mapfiles/ms/icons/orange-dot.png' :
                    'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                scaledSize: new google.maps.Size(size, size),
            };
        }

        // A pulsing halo under critical-severity incidents only — cheap (one rAF
        // loop per critical incident, and there should rarely be many at once) but
        // makes the "this one needs attention now" signal impossible to miss at a
        // glance, on top of the larger marker size above.
        function haloColor(status) {
            return status === 'dispatched' ? '#f59e0b' : '#dc2626';
        }

        function startCriticalHalo(id, lat, lng, status) {
            const circle = new google.maps.Circle({
                map,
                center: {
                    lat,
                    lng
                },
                radius: 60,
                fillColor: haloColor(status),
                fillOpacity: 0.18,
                strokeColor: haloColor(status),
                strokeOpacity: 0.45,
                strokeWeight: 1.5,
                clickable: false,
            });
            incidentCircleById[id] = circle;

            const start = performance.now();

            function frame(now) {
                if (!circle.getMap()) return; // removed — stop looping
                const t = ((now - start) % 1800) / 1800;
                const wave = (Math.sin(t * Math.PI * 2) + 1) / 2;
                circle.setRadius(60 + wave * 40);
                circle.setOptions({
                    fillOpacity: 0.22 - wave * 0.14,
                    strokeOpacity: 0.55 - wave * 0.3
                });
                requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        }

        function removeCriticalHalo(id) {
            if (incidentCircleById[id]) {
                incidentCircleById[id].setMap(null);
                delete incidentCircleById[id];
            }
        }

        function initMap() {
            const center = {
                lat: 15.9755,
                lng: 120.5651
            };

            map = new google.maps.Map(document.getElementById('map'), {
                center,
                zoom: 14,
                mapTypeId: 'roadmap',
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: true, // lets an operator drop the pegman onto a street for ground-level context before dispatching
                fullscreenControl: false,
                // Same declutter as the speed-zones picker map — POI/transit icons
                // (restaurants, bus stops, etc.) just compete for attention with the
                // accident and patrol markers this map actually exists to show.
                styles: [{
                        featureType: 'poi',
                        stylers: [{
                            visibility: 'off'
                        }]
                    },
                    {
                        featureType: 'transit',
                        stylers: [{
                            visibility: 'off'
                        }]
                    },
                ],
            });

            // Not attached to the map until toggleTraffic() turns it on.
            trafficLayer = new google.maps.TrafficLayer();

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map,
                suppressMarkers: true, // keep our own patrol/incident markers instead of Google's default A/B pins
                polylineOptions: {
                    strokeColor: '#7B1A2E',
                    strokeWeight: 4
                },
            });

            infoWindow = new google.maps.InfoWindow();

            function addMarker(lat, lng, id, status, severity, title) {
                const marker = new google.maps.Marker({
                    position: {
                        lat,
                        lng
                    },
                    map,
                    icon: incidentIcon(status, severity),
                    title,
                });
                marker.addListener('click', () => {
                    infoWindow.setContent(incidentInfoContent(id));
                    infoWindow.open(map, marker);
                });
                return marker;
            }

            // Plot real pending/dispatched incidents from the database. Each stays
            // an independent marker — no clustering — since bundling separate,
            // pending accidents into one "2" bubble on a dispatch map risks an
            // operator missing that there are two distinct incidents to act on.
            // Markers are removed (not just left to pile up) once a status update
            // reports the incident resolved — see the live-alert script block below.
            const incidents = window.LocationTrackingConfig.pendingIncidents;
            incidents.forEach(inc => {
                const lat = parseFloat(inc.latitude);
                const lng = parseFloat(inc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                incidentDataById[inc.id] = {
                    type: inc.type,
                    severity: inc.severity,
                    status: inc.status,
                    riderName: inc.rider ? inc.rider.full_name : 'Unknown rider',
                    riderPhone: inc.rider?.phone_number ?? '—',
                    address: inc.address || 'Location unavailable',
                    reportedAt: inc.created_at,
                    patrolUnitName: inc.patrol_unit?.full_name ?? null,
                    patrolUnitBadge: inc.patrol_unit?.badge_number ?? null,
                };

                incidentMarkerById[inc.id] = addMarker(lat, lng, inc.id, inc.status, inc.severity, incidentDataById[
                    inc.id].riderName);
                if (inc.severity === 'critical') startCriticalHalo(inc.id, lat, lng, inc.status);
            });

            // Patrol unit markers — seeded from the page-load snapshot, then kept
            // live via the 'patrol-locations' Pusher channel as units report their
            // GPS position every 30s (see PatrolAuthController::updateLocation).
            // Markers glide to each new fix instead of jumping, and rotate to face
            // the unit's last travel bearing. A unit shows as a plain circle until
            // it has moved at least once (no bearing to point yet).
            const patrolColors = {
                dispatched: '#f59e0b',
                off_duty: '#2563eb'
            };
            const patrolState = {}; // keyed by patrol unit id: { marker, lat, lng, heading, lastUpdate }
            const STALE_MS =
                90000; // 3x the 30s GPS ping interval — flags a unit whose phone/app may have stopped reporting

            function bearingBetween(lat1, lng1, lat2, lng2) {
                const toRad = d => d * Math.PI / 180,
                    toDeg = r => r * 180 / Math.PI;
                const dLng = toRad(lng2 - lng1);
                const y = Math.sin(dLng) * Math.cos(toRad(lat2));
                const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
                    Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);
                return (toDeg(Math.atan2(y, x)) + 360) % 360;
            }

            function patrolIcon(status, heading, stale) {
                return {
                    path: heading === null ? google.maps.SymbolPath.CIRCLE : google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: heading === null ? 7 : 6,
                    rotation: heading ?? 0,
                    fillColor: patrolColors[status] ?? patrolColors.off_duty,
                    fillOpacity: stale ? 0.35 : 1,
                    strokeColor: stale ? '#9ca3af' : '#fff',
                    strokeWeight: 1.5,
                };
            }

            function animateMarkerTo(marker, toLat, toLng, duration = 1200) {
                if (marker._animFrame) cancelAnimationFrame(marker._animFrame);
                const from = marker.getPosition();
                const fromLat = from.lat(),
                    fromLng = from.lng();
                const start = performance.now();

                function step(now) {
                    const t = Math.min(1, (now - start) / duration);
                    const eased = t * (2 - t); // ease-out, matches how Google's own live dot decelerates into place
                    marker.setPosition({
                        lat: fromLat + (toLat - fromLat) * eased,
                        lng: fromLng + (toLng - fromLng) * eased,
                    });
                    marker._animFrame = t < 1 ? requestAnimationFrame(step) : null;
                }
                marker._animFrame = requestAnimationFrame(step);
            }

            // Straight-line (haversine) distance to the nearest pending incident, with
            // a rough ETA at an assumed 40 km/h average patrol speed. Not a routed
            // ETA (that would need the paid Distance Matrix API) — labelled "est."
            // so it isn't mistaken for turn-by-turn accuracy.
            function haversineKm(lat1, lng1, lat2, lng2) {
                const R = 6371,
                    toRad = d => d * Math.PI / 180;
                const dLat = toRad(lat2 - lat1),
                    dLng = toRad(lng2 - lng1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                return 2 * R * Math.asin(Math.sqrt(a));
            }

            function findNearestIncident(lat, lng) {
                let best = null,
                    bestDist = Infinity;
                incidents.forEach(inc => {
                    const ilat = parseFloat(inc.latitude),
                        ilng = parseFloat(inc.longitude);
                    if (isNaN(ilat) || isNaN(ilng)) return;
                    const d = haversineKm(lat, lng, ilat, ilng);
                    if (d < bestDist) {
                        bestDist = d;
                        best = inc;
                    }
                });
                return best ? {
                    lat: parseFloat(best.latitude),
                    lng: parseFloat(best.longitude),
                    distanceKm: bestDist
                } : null;
            }

            function nearestIncidentInfo(lat, lng) {
                const nearest = findNearestIncident(lat, lng);
                if (!nearest) return '';
                const etaMin = Math.max(1, Math.round((nearest.distanceKm / 40) * 60));
                return `<br><span style="color:#b91c1c;">${nearest.distanceKm.toFixed(1)} km to nearest incident (~${etaMin} min est.)</span>` +
                    `<br><button type="button" onclick="routeToIncident(${lat}, ${lng}, ${nearest.lat}, ${nearest.lng})" ` +
                    `style="margin-top:4px; padding:3px 10px; font-size:.88rem; border:1px solid #7B1A2E; background:#fff; color:#7B1A2E; border-radius:6px; cursor:pointer;">` +
                    `Route to incident</button>`;
            }

            function infoContentFor(state) {
                let html = `<b>${state.name}</b><br>${state.badge ?? ''}<br>${state.status}`;
                const silentFor = Date.now() - state.lastUpdate;
                if (silentFor > STALE_MS) {
                    const mins = Math.floor(silentFor / 60000);
                    const secs = Math.floor((silentFor % 60000) / 1000);
                    const label = mins > 0 ? `${mins}m ${secs}s ago` : `${secs}s ago`;
                    html += `<br><span style="color:#b45309;">No GPS update — last seen ${label}</span>`;
                }
                if (state.status === 'dispatched') html += nearestIncidentInfo(state.lat, state.lng);
                return html;
            }

            // Fading trail — the last few real GPS fixes drawn as a short polyline
            // that gets more opaque toward the unit's current position. Only reflects
            // movement seen during this page session (no server-side position history
            // to seed it from), which is honest about what it actually shows.
            function updateTrail(state) {
                state.trailLines.forEach(line => line.setMap(null));
                state.trailLines = [];
                const pts = state.trail;
                for (let i = 0; i < pts.length - 1; i++) {
                    const opacity = 0.15 + 0.55 * (i / Math.max(1, pts.length - 2));
                    state.trailLines.push(new google.maps.Polyline({
                        path: [pts[i], pts[i + 1]],
                        strokeColor: patrolColors[state.status] ?? patrolColors.off_duty,
                        strokeOpacity: opacity,
                        strokeWeight: 3,
                        map,
                    }));
                }
            }

            function upsertPatrolMarker(p) {
                const lat = parseFloat(p.current_latitude);
                const lng = parseFloat(p.current_longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                let existing = patrolState[p.id];

                if (!existing) {
                    const marker = new google.maps.Marker({
                        position: {
                            lat,
                            lng
                        },
                        map,
                        icon: patrolIcon(p.status, null, false),
                        title: p.full_name
                    });
                    existing = patrolState[p.id] = {
                        marker,
                        lat,
                        lng,
                        heading: null,
                        status: p.status,
                        name: p.full_name,
                        badge: p.badge_number,
                        trail: [{
                            lat,
                            lng
                        }],
                        trailLines: [],
                        lastUpdate: Date.now(),
                    };
                    marker.addListener('click', () => {
                        infoWindow.setContent(infoContentFor(existing));
                        infoWindow.open(map, marker);
                    });
                    return;
                }

                existing.status = p.status;
                existing.name = p.full_name;
                existing.badge = p.badge_number;
                existing.lastUpdate = Date.now();

                const moved = lat !== existing.lat || lng !== existing.lng;
                if (moved) {
                    existing.heading = bearingBetween(existing.lat, existing.lng, lat, lng);
                    animateMarkerTo(existing.marker, lat, lng);
                    existing.lat = lat;
                    existing.lng = lng;

                    existing.trail.push({
                        lat,
                        lng
                    });
                    if (existing.trail.length > 8) existing.trail.shift();
                    updateTrail(existing);
                }
                existing.marker.setIcon(patrolIcon(existing.status, existing.heading, false));
            }

            // Periodic sweep — a marker (or table row) doesn't know on its own that
            // its last GPS ping went quiet, so this checks every 15s for units
            // overdue past STALE_MS and fades the marker / flags the row until a
            // fresh update arrives.
            setInterval(() => {
                const now = Date.now();
                Object.entries(patrolState).forEach(([id, state]) => {
                    const stale = (now - state.lastUpdate) > STALE_MS;
                    state.marker.setIcon(patrolIcon(state.status, state.heading, stale));
                    const note = document.querySelector(`tr[data-patrol-id="${id}"] .stale-note`);
                    if (note) note.style.display = stale ? '' : 'none';
                });
            }, 15000);

            // Stand By / In Action side tables — moves a patrol's row to whichever
            // table matches their current status, so a dispatch or resolution moves
            // the row live instead of waiting for a page refresh.
            function refreshEmptyPlaceholder(tbody, emptyText) {
                const hasRows = tbody.querySelector('tr[data-patrol-id]') !== null;
                const placeholder = tbody.querySelector('tr.empty-placeholder');
                if (hasRows && placeholder) placeholder.remove();
                if (!hasRows && !placeholder) {
                    const tr = document.createElement('tr');
                    tr.className = 'empty-placeholder';
                    const td = document.createElement('td');
                    td.colSpan = 2;
                    td.style.cssText = 'color:#6b7280; font-size:.85rem;';
                    td.textContent = emptyText;
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                }
            }

            // Turns a patrol unit's raw GPS fix into a readable location name (e.g.
            // "National Highway, Nancamaliran West, Urdaneta City") using Nominatim
            // (OpenStreetMap) — the same free, no-API-key geocoding service the
            // mobile app already uses for place search, rather than introducing a
            // second, billed provider for the reverse direction. Nominatim's fair-use
            // policy caps this at ~1 request/second, so calls are queued with a
            // spacing delay, and results are cached by rounded coordinate so a
            // stationary unit (or several units in the same area) doesn't re-query
            // the same spot repeatedly.
            const reverseGeocodeCache = {};
            let geocodeChain = Promise.resolve();

            function reverseGeocode(lat, lng) {
                const key = `${lat.toFixed(4)},${lng.toFixed(4)}`;
                if (key in reverseGeocodeCache) return reverseGeocodeCache[key];

                const result = geocodeChain.then(async () => {
                    try {
                        const res = await fetch(
                            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=17`
                        );
                        const data = res.ok ? await res.json() : null;
                        return data?.display_name ?? null;
                    } catch {
                        return null; // network hiccup or rate-limited — caller keeps the coordinate fallback
                    }
                });

                // The next call in the queue waits for this one plus a spacing
                // delay, keeping us comfortably under the 1 req/sec fair-use limit.
                geocodeChain = result.then(() => new Promise(r => setTimeout(r, 1100)));

                reverseGeocodeCache[key] = result;
                return result;
            }

            // Fills in the .geo-text line alongside the coordinates (never replaces
            // them) so an operator can see both at once and cross-check the resolved
            // address actually matches where the dot is on the map. Takes the row
            // element directly (not an id) so this works for any table that follows
            // the same .loc-text/.geo-text convention — patrol units and, below,
            // the Accident Prone Area hotspot rows.
            function upgradeLocationText(rowEl, lat, lng) {
                if (!rowEl || isNaN(lat) || isNaN(lng)) return;
                reverseGeocode(lat, lng).then(address => {
                    if (!address) return;
                    const el = rowEl.querySelector('.geo-text');
                    if (el) el.textContent = address;
                });
            }

            // Upgrade the rows already rendered at page load (data-lat/data-lng were
            // seeded server-side for exactly this purpose).
            document.querySelectorAll('#standByBody tr[data-lat], #inActionBody tr[data-lat]').forEach(tr => {
                upgradeLocationText(tr, parseFloat(tr.dataset.lat), parseFloat(tr.dataset.lng));
            });

            // Same upgrade for the Accident Prone Area ranked list.
            document.querySelectorAll('#pronePanel tr.hotspot-row[data-lat]').forEach(tr => {
                upgradeLocationText(tr, parseFloat(tr.dataset.lat), parseFloat(tr.dataset.lng));
            });

            function upsertPatrolRow(p) {
                const standByBody = document.getElementById('standByBody');
                const inActionBody = document.getElementById('inActionBody');
                if (!standByBody || !inActionBody) return;

                document.querySelectorAll(`tr[data-patrol-id="${p.id}"]`).forEach(tr => tr.remove());

                const lat = parseFloat(p.current_latitude);
                const lng = parseFloat(p.current_longitude);
                const locationText = (!isNaN(lat) && !isNaN(lng)) ? `${lat.toFixed(4)}°N, ${lng.toFixed(4)}°E` : '—';

                const tr = document.createElement('tr');
                tr.dataset.patrolId = p.id;
                const nameTd = document.createElement('td');
                nameTd.textContent = p.full_name;
                const locTd = document.createElement('td');
                const locSpan = document.createElement('span');
                locSpan.className = 'loc-text';
                locSpan.textContent = locationText;
                locTd.appendChild(locSpan);

                // Filled in by upgradeLocationText() below once the reverse-geocode
                // lookup resolves — shown alongside the coordinates, not instead of
                // them, so both are visible for cross-checking.
                const geoSpan = document.createElement('span');
                geoSpan.className = 'geo-text';
                geoSpan.style.cssText = 'display:block; font-size:.88rem; color:#64748b;';
                locTd.appendChild(geoSpan);

                // Hidden by default — a row only ever gets built here right after a
                // fresh update, so it's never stale at creation time. The periodic
                // sweep below is what reveals this once a unit goes quiet.
                const staleNote = document.createElement('span');
                staleNote.className = 'stale-note';
                staleNote.style.cssText = 'display:none; color:#b45309; font-size:.86rem; margin-left:6px;';
                staleNote.textContent = '(no GPS update)';
                locTd.appendChild(staleNote);

                tr.append(nameTd, locTd);

                (p.status === 'dispatched' ? inActionBody : standByBody).appendChild(tr);

                refreshEmptyPlaceholder(standByBody, 'No stand-by units');
                refreshEmptyPlaceholder(inActionBody, 'No units in action');

                // Only worth re-resolving when the unit has actually moved to a
                // meaningfully different spot — matches the ~11m precision of the
                // cache key above, so a unit idling at the same curb doesn't keep
                // re-querying the same address every 30s.
                upgradeLocationText(tr, lat, lng);
            }

            function handlePatrolUpdate(p) {
                upsertPatrolMarker(p);
                upsertPatrolRow(p);
            }

            patrollersData = window.LocationTrackingConfig.patrollers;
            patrollersData.forEach(handlePatrolUpdate);

            if (window.pusherClient) {
                window.pusherClient.subscribe('patrol-locations')
                    .bind('patrol.location_updated', handlePatrolUpdate);
            }

            // Accident-prone heatmap, weighted by real historical incident coordinates
            // (shown when "Accident Prone Area" is toggled)
            const incidentCoords = window.LocationTrackingConfig.allIncidentCoords;
            const heatmapPoints = incidentCoords
                .map(i => {
                    const lat = parseFloat(i.latitude);
                    const lng = parseFloat(i.longitude);
                    return (isNaN(lat) || isNaN(lng)) ? null : new google.maps.LatLng(lat, lng);
                })
                .filter(Boolean);

            heatmap = new google.maps.visualization.HeatmapLayer({
                data: heatmapPoints,
                radius: 40,
            });
        }
    

        // Real-time incident alert — fires when the IoT device posts a crash report
        // and the backend broadcasts it via Pusher (channel: incidents, event: incident.reported).
        // Adds a new alert card at the top of the page and a red dot on the map without refresh.
        (function() {
            if (!window.pusherClient) return;

            // Web Audio API alert tone — three short beeps to grab the operator's attention.
            function playAlertTone() {
                try {
                    const ctx = new(window.AudioContext || window.webkitAudioContext)();
                    [0, 0.25, 0.5].forEach(function(delay) {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'square';
                        osc.frequency.value = 880;
                        gain.gain.setValueAtTime(0.3, ctx.currentTime + delay);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.18);
                        osc.start(ctx.currentTime + delay);
                        osc.stop(ctx.currentTime + delay + 0.18);
                    });
                } catch (e) {}
            }

            function addIncidentCard(data) {
                const row = document.getElementById('incident-cards-row');
                const banner = document.getElementById('no-incidents-banner');
                if (!row) return;

                banner.style.display = 'none';
                row.style.display = '';

                const riderName = data.rider?.full_name ?? 'Unknown rider';
                const phone = data.rider?.phone_number ?? '—';
                const address = data.address ?? 'Location unavailable';

                incidentDataById[data.id] = {
                    type: data.type,
                    severity: data.severity,
                    status: data.status ?? 'pending',
                    riderName,
                    riderPhone: phone,
                    address,
                    reportedAt: data.reported_at ?? new Date().toISOString(),
                    patrolUnitName: null,
                    patrolUnitBadge: null,
                };

                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.dataset.incidentId = data.id;
                col.dataset.reportedAt = data.reported_at ?? new Date().toISOString();
                col.innerHTML = `
            <div class="p-3 position-relative rounded-3 border border-2"
                 style="background:#fde8e8; border-color:#d97070 !important;">
                <span class="position-absolute rounded-circle d-flex align-items-center justify-content-center fw-black text-white"
                      style="top:12px; right:12px; width:28px; height:28px; background:#1a1a1a; font-size:1rem;">!</span>
                <h6 class="fw-bold mb-2">Accident Alert!
                    <span class="badge ms-2" style="font-size:.88rem; background:#b91c1c;">PENDING</span>
                    <span class="badge ms-1" style="font-size:.88rem; background:#7B1A2E;">LIVE</span>
                    ${data.severity === 'critical' ? '<span class="badge ms-1" style="font-size:.88rem; background:#7B1A2E;">CRITICAL</span>' : ''}
                </h6>
                <div class="reported-line" style="font-size:.88rem; color:#64748b; margin-bottom:8px;">
                    Reported <span class="reported-ago">just now</span>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.85rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            ${riderName}
                        </div>
                        <div class="ps-3 text-dark" style="font-size:.88rem;">${phone}</div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-1 mb-1 fw-bold" style="font-size:.85rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                            Current Location
                        </div>
                        <div class="ps-3 lh-sm text-dark" style="font-size:.88rem;">${address}</div>
                    </div>
                </div>
                <div class="dispatch-note mt-1" style="font-size:.86rem; color:#7B1A2E; font-weight:600;">
                    Severity: ${data.severity ?? '—'} — refresh page to dispatch a patrol unit
                </div>
            </div>`;

                row.prepend(col);
                refreshReportedAgo();

                // Drop a new marker on the map (if Google Maps has loaded), sized by
                // severity the same way the page-load markers are.
                const lat = parseFloat(data.latitude);
                const lng = parseFloat(data.longitude);
                if (!isNaN(lat) && !isNaN(lng) && window.google?.maps && map) {
                    const marker = new google.maps.Marker({
                        position: {
                            lat,
                            lng
                        },
                        map,
                        icon: incidentIcon(data.status ?? 'pending', data.severity),
                        title: riderName,
                        animation: google.maps.Animation.DROP,
                    });
                    marker.addListener('click', () => {
                        infoWindow.setContent(incidentInfoContent(data.id));
                        infoWindow.open(map, marker);
                    });
                    incidentMarkerById[data.id] = marker;
                    if (data.severity === 'critical') startCriticalHalo(data.id, lat, lng, data.status ?? 'pending');
                    map.panTo({
                        lat,
                        lng
                    });
                }
            }

            // The cards and markers above only ever accumulated — dispatching or
            // resolving an incident elsewhere never removed anything here, so a
            // busy shift would leave every past accident piled up on the page and
            // the map indefinitely. This clears/updates them using the same
            // 'incident.status_updated' event the investigation dashboard already
            // listens for on this channel.
            function updateIncidentStatus(data) {
                const card = document.querySelector(`[data-incident-id="${data.id}"]`);
                const marker = incidentMarkerById[data.id];

                if (data.status === 'resolved' || data.status === 'false_alarm') {
                    if (card) card.remove();
                    if (marker) {
                        marker.setMap(null);
                        delete incidentMarkerById[data.id];
                    }
                    removeCriticalHalo(data.id);
                    delete incidentDataById[data.id];

                    const row = document.getElementById('incident-cards-row');
                    const banner = document.getElementById('no-incidents-banner');
                    if (row && !row.querySelector('[data-incident-id]')) {
                        row.style.display = 'none';
                        if (banner) banner.style.display = '';
                    }
                    return;
                }

                if (data.status === 'dispatched') {
                    if (incidentDataById[data.id]) {
                        incidentDataById[data.id].status = 'dispatched';
                        incidentDataById[data.id].severity = data.severity ?? incidentDataById[data.id].severity;
                        incidentDataById[data.id].patrolUnitName = data.patrol_unit?.full_name ?? null;
                        incidentDataById[data.id].patrolUnitBadge = data.patrol_unit?.badge_number ?? null;
                    }
                    if (card) {
                        const badge = card.querySelector('.badge');
                        if (badge) {
                            badge.textContent = 'DISPATCHED';
                            badge.style.background = '#2a7c5b';
                        }
                        card.querySelector('form')?.remove();

                        let note = card.querySelector('.dispatch-note');
                        if (!note) {
                            note = document.createElement('div');
                            note.className = 'dispatch-note mt-2';
                            note.style.cssText = 'font-size:.86rem; color:#2a7c5b;';
                            card.querySelector('.p-3')?.appendChild(note);
                        }
                        note.textContent = data.patrol_unit ?
                            `✓ Patrol dispatched: ${data.patrol_unit.full_name}${data.patrol_unit.badge_number ? ' (' + data.patrol_unit.badge_number + ')' : ''}` :
                            '✓ Patrol dispatched';
                    }
                    if (marker) {
                        marker.setIcon(incidentIcon('dispatched', data.severity));
                    }
                    if (incidentCircleById[data.id]) {
                        incidentCircleById[data.id].setOptions({
                            fillColor: haloColor('dispatched'),
                            strokeColor: haloColor('dispatched')
                        });
                    }
                }
            }

            // "Reported Xm ago" on every card — both the ones rendered at page load
            // and the ones injected live — refreshed on a timer since nothing else
            // would otherwise update that text as time passes. Uses the same
            // formatAgo() the marker info windows use (defined at top level).
            function refreshReportedAgo() {
                document.querySelectorAll('[data-reported-at]').forEach(el => {
                    const reportedAt = new Date(el.dataset.reportedAt).getTime();
                    if (isNaN(reportedAt)) return;
                    const label = el.querySelector('.reported-ago');
                    if (label) label.textContent = formatAgo(Date.now() - reportedAt);
                });
            }
            refreshReportedAgo();
            setInterval(refreshReportedAgo, 30000);

            window.pusherClient.subscribe('incidents')
                .bind('incident.reported', function(data) {
                    playAlertTone();
                    addIncidentCard(data);
                })
                .bind('incident.status_updated', updateIncidentStatus);
        }());
    