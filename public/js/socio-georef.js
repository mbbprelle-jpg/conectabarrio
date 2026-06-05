(function (global) {
    'use strict';

    var DEFAULT_CENTER = [-33.4489, -70.6693];
    var DEFAULT_ZOOM = 13;
    var geocodeTimer = null;

    function buildGoogleMapsLink(lat, lng) {
        return ('https://www.google.com/maps?q=' + lat + ',' + lng).toLowerCase();
    }

    function getCalleName(selectEl, callesMap) {
        if (!selectEl) {
            return '';
        }
        var id = selectEl.value;
        if (id && callesMap[id]) {
            return callesMap[id];
        }
        var opt = selectEl.options[selectEl.selectedIndex];
        return opt ? opt.text.trim() : '';
    }

    function SocioGeorefMap(container, options) {
        this.container = container;
        this.prefix = container.getAttribute('data-prefix') || '';
        this.calleSelect = document.getElementById(container.getAttribute('data-calle-select') || 'calle_id');
        this.numeroInput = document.getElementById(container.getAttribute('data-numero-input') || 'numero_casa');
        this.comuna = container.getAttribute('data-comuna') || '';
        this.callesMap = options.callesMap || {};
        this.latInput = document.getElementById(this.prefix + 'latitud');
        this.lngInput = document.getElementById(this.prefix + 'longitud');
        this.linkInput = document.getElementById(this.prefix + 'link_google');
        this.statusEl = document.getElementById(this.prefix + 'georef_status');
        this.mapEl = document.getElementById(this.prefix + 'georef_map');
        this.map = null;
        this.marker = null;
        this._boundSchedule = this.scheduleGeocode.bind(this);
    }

    SocioGeorefMap.prototype.setStatus = function (message, isError) {
        if (!this.statusEl) {
            return;
        }
        this.statusEl.textContent = message || '';
        this.statusEl.style.color = isError ? 'var(--danger, #ef4444)' : 'var(--text-muted)';
    };

    SocioGeorefMap.prototype.setCoords = function (lat, lng, silent) {
        if (this.latInput) {
            this.latInput.value = lat;
        }
        if (this.lngInput) {
            this.lngInput.value = lng;
        }
        if (this.linkInput) {
            this.linkInput.value = buildGoogleMapsLink(lat, lng);
        }
        if (!silent) {
            this.setStatus('Ubicación confirmada. Puede mover el marcador para afinar.');
        }
    };

    SocioGeorefMap.prototype.ensureMap = function () {
        if (this.map || !this.mapEl || typeof L === 'undefined') {
            return;
        }
        var lat = parseFloat(this.latInput && this.latInput.value);
        var lng = parseFloat(this.lngInput && this.lngInput.value);
        var center = (!isNaN(lat) && !isNaN(lng)) ? [lat, lng] : DEFAULT_CENTER.slice();
        var zoom = (!isNaN(lat) && !isNaN(lng)) ? 17 : DEFAULT_ZOOM;

        this.map = L.map(this.mapEl, { scrollWheelZoom: true }).setView(center, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(this.map);

        var self = this;
        this.marker = L.marker(center, { draggable: true }).addTo(this.map);
        this.marker.on('dragend', function () {
            var pos = self.marker.getLatLng();
            self.setCoords(pos.lat.toFixed(7), pos.lng.toFixed(7));
        });

        if (!isNaN(lat) && !isNaN(lng)) {
            this.setStatus('Ubicación cargada. Puede mover el marcador si no es exacta.');
        }
    };

    SocioGeorefMap.prototype.refreshView = function () {
        if (!this.map) {
            return;
        }
        var lat = parseFloat(this.latInput && this.latInput.value);
        var lng = parseFloat(this.lngInput && this.lngInput.value);
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        this.marker.setLatLng([lat, lng]);
        this.map.setView([lat, lng], 17);
    };

    SocioGeorefMap.prototype.geocode = function () {
        var calle = getCalleName(this.calleSelect, this.callesMap);
        var numero = this.numeroInput ? this.numeroInput.value.trim() : '';
        if (!calle || !numero || !this.comuna) {
            this.setStatus('Seleccione calle e ingrese número para ubicar en el mapa.');
            return;
        }

        var self = this;
        this.setStatus('Buscando ubicación…');
        var query = numero + ' ' + calle + ', ' + this.comuna + ', Chile';
        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=cl&q=' + encodeURIComponent(query);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!Array.isArray(data) || !data.length) {
                    self.setStatus('No se encontró la dirección. Mueva el mapa manualmente.', true);
                    self.ensureMap();
                    return;
                }
                var lat = parseFloat(data[0].lat);
                var lng = parseFloat(data[0].lon);
                if (isNaN(lat) || isNaN(lng)) {
                    self.setStatus('No se pudo interpretar la ubicación.', true);
                    return;
                }
                self.ensureMap();
                self.setCoords(lat.toFixed(7), lng.toFixed(7));
                self.marker.setLatLng([lat, lng]);
                self.map.setView([lat, lng], 17);
            })
            .catch(function () {
                self.setStatus('Error al geocodificar. Intente nuevamente.', true);
                self.ensureMap();
            });
    };

    SocioGeorefMap.prototype.scheduleGeocode = function () {
        var self = this;
        clearTimeout(geocodeTimer);
        geocodeTimer = setTimeout(function () {
            self.geocode();
        }, 600);
    };

    SocioGeorefMap.prototype.bindEvents = function () {
        if (this.calleSelect) {
            this.calleSelect.addEventListener('change', this._boundSchedule);
        }
        if (this.numeroInput) {
            this.numeroInput.addEventListener('input', this._boundSchedule);
            this.numeroInput.addEventListener('change', this._boundSchedule);
        }
    };

    SocioGeorefMap.prototype.loadFromValues = function (lat, lng, link) {
        if (lat && lng) {
            if (this.latInput) {
                this.latInput.value = lat;
            }
            if (this.lngInput) {
                this.lngInput.value = lng;
            }
            if (this.linkInput) {
                this.linkInput.value = link || buildGoogleMapsLink(lat, lng);
            }
            this.ensureMap();
            this.refreshView();
        }
    };

    SocioGeorefMap.prototype.refreshLayout = function () {
        var self = this;
        if (!this.map) {
            this.ensureMap();
        }
        if (this.map) {
            setTimeout(function () {
                self.map.invalidateSize();
                self.refreshView();
            }, 200);
        }
    };

    SocioGeorefMap.prototype.init = function () {
        this.bindEvents();
        var lat = this.latInput && this.latInput.value;
        var lng = this.lngInput && this.lngInput.value;
        if (lat && lng) {
            this.ensureMap();
        }
    };

    global.initSocioGeorefMaps = function (callesMap) {
        var instances = {};
        document.querySelectorAll('[data-socio-georef]').forEach(function (el) {
            var prefix = el.getAttribute('data-prefix') || '';
            var instance = new SocioGeorefMap(el, { callesMap: callesMap || {} });
            instance.init();
            instances[prefix || 'default'] = instance;
        });
        return instances;
    };
})(window);
