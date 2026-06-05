(function (global) {
    'use strict';

    function parseCoord(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        var n = parseFloat(value);
        return isFinite(n) ? n : null;
    }

    function defaultCenter(config) {
        var sedeLat = parseCoord(config.sede && config.sede.lat);
        var sedeLng = parseCoord(config.sede && config.sede.lng);
        if (sedeLat !== null && sedeLng !== null) {
            return { lat: sedeLat, lng: sedeLng, zoom: 14 };
        }
        var puntos = config.puntos || [];
        if (puntos.length > 0) {
            var sumLat = 0;
            var sumLng = 0;
            puntos.forEach(function (p) {
                sumLat += p.lat;
                sumLng += p.lng;
            });
            return { lat: sumLat / puntos.length, lng: sumLng / puntos.length, zoom: 14 };
        }
        return { lat: -33.4489, lng: -70.6693, zoom: 11 };
    }

    function initMapaSocios(config) {
        var container = document.getElementById(config.containerId);
        if (!container || typeof L === 'undefined') {
            return null;
        }

        var center = defaultCenter(config);
        var map = L.map(container, { scrollWheelZoom: true }).setView([center.lat, center.lng], center.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var puntos = (config.puntos || []).filter(function (p) {
            return typeof p.lat === 'number' && typeof p.lng === 'number';
        });

        var heatLayer = null;
        if (typeof L.heatLayer === 'function' && puntos.length > 0) {
            var heatData = puntos.map(function (p) {
                return [p.lat, p.lng, 0.65];
            });
            heatLayer = L.heatLayer(heatData, {
                radius: 28,
                blur: 22,
                maxZoom: 17,
                minOpacity: 0.35,
                gradient: {
                    0.2: '#3b82f6',
                    0.5: '#22c55e',
                    0.75: '#eab308',
                    1.0: '#ef4444'
                }
            }).addTo(map);
        }

        var markersLayer = L.layerGroup();
        puntos.forEach(function (p) {
            var marker = L.circleMarker([p.lat, p.lng], {
                radius: 7,
                color: '#1d4ed8',
                weight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.75
            });
            var popup = '<strong>' + (p.label || 'Socio') + '</strong>';
            if (p.id_socio) {
                popup += '<br><span style="font-size:0.85rem;color:#64748b;">N° socio: ' + p.id_socio + '</span>';
            }
            marker.bindPopup(popup);
            markersLayer.addLayer(marker);
        });
        markersLayer.addTo(map);

        var sedeLat = parseCoord(config.sede && config.sede.lat);
        var sedeLng = parseCoord(config.sede && config.sede.lng);
        if (sedeLat !== null && sedeLng !== null) {
            L.marker([sedeLat, sedeLng], {
                title: config.sede.label || 'Sede'
            }).addTo(map).bindPopup('<strong>' + (config.sede.label || 'Sede organización') + '</strong><br><span style="font-size:0.85rem;color:#64748b;">Punto de referencia</span>');
        }

        if (puntos.length > 0) {
            var bounds = L.latLngBounds(puntos.map(function (p) {
                return [p.lat, p.lng];
            }));
            if (sedeLat !== null && sedeLng !== null) {
                bounds.extend([sedeLat, sedeLng]);
            }
            map.fitBounds(bounds.pad(0.12));
        }

        var toggleHeat = document.getElementById('toggleHeat');
        var toggleMarkers = document.getElementById('toggleMarkers');

        if (toggleHeat && heatLayer) {
            toggleHeat.addEventListener('change', function () {
                if (this.checked) {
                    map.addLayer(heatLayer);
                } else {
                    map.removeLayer(heatLayer);
                }
            });
        }

        if (toggleMarkers) {
            toggleMarkers.addEventListener('change', function () {
                if (this.checked) {
                    map.addLayer(markersLayer);
                } else {
                    map.removeLayer(markersLayer);
                }
            });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 150);

        return { map: map, heatLayer: heatLayer, markersLayer: markersLayer };
    }

    global.initMapaSocios = initMapaSocios;
})(window);
