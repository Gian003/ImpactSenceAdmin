// Investigation Incident Report (view) page — the report location map and
// the "export/print" popup-window flow. Extracted from
// resources/views/investigation/incident-report/show.blade.php. Server data
// comes in via window.IncidentReportConfig (set by a small inline <script>
// in that Blade file right before this one loads). fullName/location are
// passed through @json() rather than raw string interpolation, which also
// fixes a latent bug: the original inline version would have broken (an
// unterminated JS string) if either value ever contained a single quote.

function initReportMap() {
        const lat = window.IncidentReportConfig.lat;
        const lng = window.IncidentReportConfig.lng;

        const map = new google.maps.Map(document.getElementById('reportMap'), {
            center: { lat, lng },
            zoom: 14,
            mapTypeId: 'roadmap',
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        const marker = new google.maps.Marker({
            position: { lat, lng },
            map,
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                scaledSize: new google.maps.Size(32, 32),
            },
        });

        const infoWindow = new google.maps.InfoWindow({
            content: '<b>' + window.IncidentReportConfig.fullName + '</b><br>' + window.IncidentReportConfig.location,
        });
        infoWindow.open(map, marker);
    }

    function exportReport() {
        const content = document.querySelector('.content').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`
            <html><head>
                <title>Incident Report</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="${window.IncidentReportConfig.incidentReportCssUrl}" rel="stylesheet">
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
