let map, pickupMarker, dropMarker, routeLine;
let pickupLatLng = null, dropLatLng = null;


function initMap(containerId = 'map', lat = 20.5937, lng = 78.9629, zoom = 5) {
    map = L.map(containerId, { zoomControl: true }).setView([lat, lng], zoom);

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
        crossOrigin: true
    });

    const cartoLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© CartoDB',
        maxZoom: 19,
        crossOrigin: true
    });

    osmLayer.addTo(map);

    let osmErrorCount = 0;
    osmLayer.on('tileerror', function() {
        osmErrorCount++;
        if (osmErrorCount === 3) {
            map.removeLayer(osmLayer);
            cartoLayer.addTo(map);
        }
    });

    map.on('click', function(e) {
        if (typeof onMapClick === 'function') {
            onMapClick(e.latlng.lat, e.latlng.lng);
        }
    });

    return map;
}


function makeIcon(color = '#00C853', label = 'P') {
    return L.divIcon({
        html: `<div style="
            background:${color};
            color:#fff;
            width:36px;height:36px;
            border-radius:50% 50% 50% 0;
            transform:rotate(-45deg);
            display:flex;align-items:center;justify-content:center;
            box-shadow:0 4px 12px rgba(0,0,0,0.4);
            border:2px solid rgba(255,255,255,0.3);
        "><span style="transform:rotate(45deg);font-weight:700;font-size:13px">${label}</span></div>`,
        className: '',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -38]
    });
}


function setPickupMarker(lat, lng, label = '') {
    if (!map) return;
    if (pickupMarker) map.removeLayer(pickupMarker);
    pickupMarker = L.marker([lat, lng], { icon: makeIcon('#00C853', 'P'), draggable: true })
        .addTo(map)
        .bindPopup(label || 'Pickup Location')
        .openPopup();

    pickupMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        pickupLatLng = { lat: pos.lat, lng: pos.lng };
        document.getElementById('pickup_lat').value = pos.lat;
        document.getElementById('pickup_lng').value = pos.lng;
        reverseGeocodeToField(pos.lat, pos.lng, 'pickup');
        updateRoute();
    });

    pickupLatLng = { lat, lng };
    updateRoute();
}


function setDropMarker(lat, lng, label = '') {
    if (!map) return;
    if (dropMarker) map.removeLayer(dropMarker);
    dropMarker = L.marker([lat, lng], { icon: makeIcon('#FF4757', 'D'), draggable: true })
        .addTo(map)
        .bindPopup(label || 'Drop Location')
        .openPopup();

    dropMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        dropLatLng = { lat: pos.lat, lng: pos.lng };
        document.getElementById('drop_lat').value = pos.lat;
        document.getElementById('drop_lng').value = pos.lng;
        reverseGeocodeToField(pos.lat, pos.lng, 'drop');
        updateRoute();
    });

    dropLatLng = { lat, lng };
    updateRoute();
}


function updateRoute() {
    if (!pickupLatLng || !dropLatLng) return;

    if (routeLine) map.removeLayer(routeLine);

    const p = pickupLatLng, d = dropLatLng;
    routeLine = L.polyline(
        [[p.lat, p.lng], [d.lat, d.lng]],
        { color: '#00C853', weight: 3, opacity: 0.8, dashArray: '8, 6' }
    ).addTo(map);

    const bounds = L.latLngBounds([p.lat, p.lng], [d.lat, d.lng]);
    map.fitBounds(bounds, { padding: [60, 60] });

    const dist = haversine(p.lat, p.lng, d.lat, d.lng);
    if (typeof onDistanceCalculated === 'function') {
        onDistanceCalculated(dist);
    }
}


function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2))
        * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return parseFloat((R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))).toFixed(2));
}
function deg2rad(d) { return d * (Math.PI / 180); }


function geocodeAddress(query, callback) {
    const base = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
    const url = base + '/user/geocode.php?action=search&q=' + encodeURIComponent(query);
    fetch(url)
        .then(r => r.json())
        .then(data => callback(Array.isArray(data) ? data : []))
        .catch(() => callback([]));
}


function reverseGeocodeToField(lat, lng, type) {
    const base = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
    const url = base + '/user/geocode.php?action=reverse&lat=' + lat + '&lng=' + lng;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            const addr = data.display_name || (parseFloat(lat).toFixed(4) + ', ' + parseFloat(lng).toFixed(4));
            const fieldId = type === 'pickup' ? 'pickup_location' : 'drop_location';
            const el = document.getElementById(fieldId);
            if (el) el.value = addr;
        })
        .catch(() => {});
}


function reverseGeocode(lat, lng, typeOrCallback) {
    const base = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
    const url = base + '/user/geocode.php?action=reverse&lat=' + lat + '&lng=' + lng;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            const addr = data.display_name || (parseFloat(lat).toFixed(4) + ', ' + parseFloat(lng).toFixed(4));
            if (typeof typeOrCallback === 'function') {
                typeOrCallback(addr);
            } else {
                reverseGeocodeToField(lat, lng, typeOrCallback);
            }
        })
        .catch(() => {
            if (typeof typeOrCallback === 'function') {
                typeOrCallback(parseFloat(lat).toFixed(4) + ', ' + parseFloat(lng).toFixed(4));
            }
        });
}


function detectLocation() {
    if (!navigator.geolocation) {
        showToast('Geolocation not supported. Please type your location manually.', 'warning');
        return;
    }
    const btn = document.getElementById('detectLocationBtn');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Detecting...'; }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            const latEl = document.getElementById('pickup_lat');
            const lngEl = document.getElementById('pickup_lng');
            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;

            setPickupMarker(lat, lng, 'Your Location');

            if (map) {
                map.setView([lat, lng], 14);
                map.invalidateSize();
            }

            reverseGeocodeToField(lat, lng, 'pickup');

            if (typeof checkLocationsReady === 'function') checkLocationsReady();

            if (btn) { btn.disabled = false; btn.textContent = '📍'; }
        },
        (err) => {
            let msg = 'Could not detect location.';
            if (err.code === 1) msg = 'Location permission denied. Please type your address manually.';
            else if (err.code === 2) msg = 'Location unavailable. Try typing your address.';
            showToast(msg, 'warning');
            if (btn) { btn.disabled = false; btn.textContent = '📍'; }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}



function setupAutocomplete(inputId, type) {
    const input = document.getElementById(inputId);
    if (!input) return;

    let timeout = null;
    let dropdown = null;

    function removeDropdown() {
        if (dropdown) { dropdown.remove(); dropdown = null; }
    }

    function positionDropdown() {
        if (!dropdown) return;
        const rect = input.getBoundingClientRect();
        dropdown.style.top  = (rect.bottom + window.scrollY) + 'px';
        dropdown.style.left = (rect.left + window.scrollX) + 'px';
        dropdown.style.width = rect.width + 'px';
    }

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const q = this.value.trim();
        if (q.length < 3) { removeDropdown(); return; }

        timeout = setTimeout(() => {
            geocodeAddress(q, (results) => {
                removeDropdown();
                if (!results.length) return;

                dropdown = document.createElement('div');
                dropdown.className = 'geocode-dropdown';
                dropdown.style.cssText = `
                    position:absolute;z-index:99999;background:#1A1A2E;
                    border:1px solid rgba(255,255,255,0.1);border-radius:8px;
                    max-height:200px;overflow-y:auto;
                    box-shadow:0 8px 24px rgba(0,0,0,0.4);margin-top:2px;
                `;

                results.forEach(r => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding:10px 14px;cursor:pointer;font-size:0.85rem;color:#f5f5f5;border-bottom:1px solid rgba(255,255,255,0.05);transition:background 0.15s;';
                    item.textContent = r.display_name;
                    item.onmouseover = () => item.style.background = 'rgba(0,200,83,0.1)';
                    item.onmouseout  = () => item.style.background = '';

                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                    });

                    item.addEventListener('click', () => {
                        const lat = parseFloat(r.lat);
                        const lng = parseFloat(r.lon);

                        input.value = r.display_name;

                        if (type === 'pickup') {
                            document.getElementById('pickup_lat').value = lat;
                            document.getElementById('pickup_lng').value = lng;
                            setPickupMarker(lat, lng, r.display_name);
                        } else {
                            document.getElementById('drop_lat').value = lat;
                            document.getElementById('drop_lng').value = lng;
                            setDropMarker(lat, lng, r.display_name);
                        }
                        removeDropdown();
                        if (typeof checkLocationsReady === 'function') checkLocationsReady();
                    });
                    dropdown.appendChild(item);
                });

                document.body.appendChild(dropdown);
                positionDropdown();
            });
        }, 400);
    });

    window.addEventListener('scroll', positionDropdown, { passive: true });
    window.addEventListener('resize', positionDropdown, { passive: true });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && (!dropdown || !dropdown.contains(e.target))) {
            removeDropdown();
        }
    });
}


if (typeof onDistanceCalculated !== 'function') {
    function onDistanceCalculated(distKm) {
        console.log('Distance:', distKm, 'km');
    }
}


function showRideOnMap(pickupLat, pickupLng, dropLat, dropLng) {
    if (!map) return;
    setPickupMarker(pickupLat, pickupLng, 'User Pickup');
    setDropMarker(dropLat, dropLng, 'Drop Point');
}


let driverLiveMarker = null;
window.setDriverMarker = function(lat, lng) {
    if (!map) return;
    const icon = L.divIcon({
        html: `<div style="font-size:1.8rem;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.5))">🚗</div>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });
    if (driverLiveMarker) {
        driverLiveMarker.setLatLng([lat, lng]);
    } else {
        driverLiveMarker = L.marker([lat, lng], { icon }).addTo(map).bindPopup('Driver Location');
    }
    map.panTo([lat, lng]);
};


function drawRouteOnMap(pLat, pLng, dLat, dLng) {
    setPickupMarker(pLat, pLng);
    setDropMarker(dLat, dLng);
}
