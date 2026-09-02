// Patrollers Unit page — resolves each registered patroller's last known GPS
// fix into a readable location name, shown alongside the raw coordinates
// (never replacing them, so an operator can cross-check the two). No
// server-side config needed here: lat/lng are read straight off each row's
// data-lat/data-lng attributes (seeded server-side in
// resources/views/toc/patrollers/index.blade.php), the same convention used
// by the Location Tracking page's Patrollers overlay and Accident Prone Area
// panel — see public/js/toc/location.js.

// Turns a raw GPS fix into a readable location name (e.g. "National Highway,
// Nancamaliran West, Urdaneta City") using Nominatim (OpenStreetMap) — the
// same free, no-API-key geocoding service the mobile app already uses for
// place search, rather than introducing a second, billed provider for the
// reverse direction. Nominatim's fair-use policy caps this at ~1
// request/second, so calls are queued with a spacing delay, and results are
// cached by rounded coordinate so several patrollers in the same area (or
// one sitting still) don't re-query the same spot repeatedly.
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

    // The next call in the queue waits for this one plus a spacing delay,
    // keeping us comfortably under the 1 req/sec fair-use limit.
    geocodeChain = result.then(() => new Promise(r => setTimeout(r, 1100)));

    reverseGeocodeCache[key] = result;
    return result;
}

// Fills in the .geo-text line alongside the coordinates (never replaces
// them) so an operator can see both at once and cross-check the resolved
// address actually matches the row.
function upgradeLocationText(rowEl, lat, lng) {
    if (!rowEl || isNaN(lat) || isNaN(lng)) return;
    reverseGeocode(lat, lng).then(address => {
        if (!address) return;
        const el = rowEl.querySelector('.geo-text');
        if (el) el.textContent = address;
    });
}

document.querySelectorAll('tr[data-lat]').forEach(tr => {
    upgradeLocationText(tr, parseFloat(tr.dataset.lat), parseFloat(tr.dataset.lng));
});
