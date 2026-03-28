<!DOCTYPE html>
<html>
<head>
<title>Map Test</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
#testmap { height: 400px; width: 100%; background: #333; }
body { font-family: sans-serif; padding: 20px; background: #111; color: #fff; }
.ok { color: #00C853; } .fail { color: #FF4757; }
</style>
</head>
<body>
<h2>Map Diagnostic Test</h2>
<div id="status">Loading...</div>
<div id="testmap"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('status').innerHTML = '';
var log = document.getElementById('status');

function ok(msg)   { log.innerHTML += '<p class="ok">✓ ' + msg + '</p>'; }
function fail(msg) { log.innerHTML += '<p class="fail">✗ ' + msg + '</p>'; }

if (typeof L !== 'undefined') {
    ok('Leaflet loaded: v' + L.version);
} else {
    fail('Leaflet NOT loaded (CDN blocked or error)');
}

try {
    var m = L.map('testmap').setView([28.6139, 77.2090], 12); // Delhi
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(m);
    ok('Leaflet map initialized on #testmap');
    setTimeout(function() { m.invalidateSize(); }, 200);
} catch(e) {
    fail('Map init error: ' + e.message);
}

if (navigator.geolocation) {
    ok('Geolocation API available in browser');
} else {
    fail('Geolocation NOT available (browser blocked)');
}

ok('Testing Nominatim (OSM geocoding)...');
fetch('https://nominatim.openstreetmap.org/search?format=json&q=Delhi&limit=1')
    .then(r => r.json())
    .then(data => {
        if (data.length > 0) ok('Nominatim geocoding works: ' + data[0].display_name.substring(0, 50));
        else fail('Nominatim returned empty results');
    })
    .catch(e => fail('Nominatim blocked: ' + e.message));
</script>
</body>
</html>
