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

    function getCalleCentro(selectEl) {
        if (!selectEl || !selectEl.value) {
            return null;
        }
        var opt = selectEl.options[selectEl.selectedIndex];
        if (!opt) {
            return null;
        }
        var lat = parseFloat(opt.getAttribute('data-lat-centro'));
        var lng = parseFloat(opt.getAttribute('data-lng-centro'));
        if (isNaN(lat) || isNaN(lng)) {
            return null;
        }
        return { lat: lat, lng: lng };
    }

    function SocioGeorefMap(container, options) {
        this.container = container;
        this.prefix = container.getAttribute('data-prefix') || '';
        this.freeText = container.getAttribute('data-free-text') === '1';
        this.calleSelect = document.getElementById(container.getAttribute('data-calle-select') || 'calle_id');
        this.numeroInput = document.getElementById(container.getAttribute('data-numero-input') || 'numero_casa');
        this.direccionInput = this.freeText && this.numeroInput ? this.numeroInput : null;
        this.comuna = container.getAttribute('data-comuna') || '';
        this.latSede = parseFloat(container.getAttribute('data-lat-sede'));
        this.lngSede = parseFloat(container.getAttribute('data-lng-sede'));
        this.callesMap = options.callesMap || {};
        this.latInput = document.getElementById(this.prefix + 'latitud');
        this.lngInput = document.getElementById(this.prefix + 'longitud');
        this.linkInput = document.getElementById(this.prefix + 'link_google');
        this.statusEl = document.getElementById(this.prefix + 'georef_status');
        this.coordsTextEl = document.getElementById(this.prefix + 'georef_coords_text');
        this.coordsLinkEl = document.getElementById(this.prefix + 'georef_coords_link');
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

    SocioGeorefMap.prototype.updateCoordsDisplay = function () {
        var lat = this.latInput ? this.latInput.value.trim() : '';
        var lng = this.lngInput ? this.lngInput.value.trim() : '';
        var link = this.linkInput ? this.linkInput.value.trim() : '';

        if (this.coordsTextEl) {
            if (lat && lng) {
                this.coordsTextEl.textContent = lat + ', ' + lng;
            } else {
                this.coordsTextEl.textContent = 'Sin ubicación — seleccione dirección o mueva el marcador';
            }
        }

        if (this.coordsLinkEl) {
            if (lat && lng) {
                this.coordsLinkEl.href = link || buildGoogleMapsLink(lat, lng);
                this.coordsLinkEl.style.display = '';
            } else {
                this.coordsLinkEl.style.display = 'none';
            }
        }
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
        this.updateCoordsDisplay();
        if (!silent) {
            this.setStatus('Ubicación confirmada. Puede mover el marcador para afinar.');
        }
    };

    SocioGeorefMap.prototype.getFallbackCenter = function () {
        if (!isNaN(this.latSede) && !isNaN(this.lngSede)) {
            return [this.latSede, this.lngSede];
        }
        return DEFAULT_CENTER.slice();
    };

    SocioGeorefMap.prototype.ensureMap = function () {
        if (this.map || !this.mapEl || typeof L === 'undefined') {
            return;
        }
        var lat = parseFloat(this.latInput && this.latInput.value);
        var lng = parseFloat(this.lngInput && this.lngInput.value);
        var center = (!isNaN(lat) && !isNaN(lng)) ? [lat, lng] : this.getFallbackCenter();
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
        this.updateCoordsDisplay();
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

    SocioGeorefMap.prototype.centerOnCoords = function (lat, lng, message) {
        this.ensureMap();
        this.setCoords(lat.toFixed(7), lng.toFixed(7), true);
        this.marker.setLatLng([lat, lng]);
        this.map.setView([lat, lng], 17);
        this.setStatus(message || 'Ubicación aproximada. Ajuste el marcador si es necesario.');
    };

    SocioGeorefMap.prototype.fetchNominatim = function (query, onSuccess, onFail) {
        var self = this;
        this.setStatus('Buscando ubicación…');
        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=cl&q=' + encodeURIComponent(query);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!Array.isArray(data) || !data.length) {
                    onFail();
                    return;
                }
                var lat = parseFloat(data[0].lat);
                var lng = parseFloat(data[0].lon);
                if (isNaN(lat) || isNaN(lng)) {
                    onFail();
                    return;
                }
                onSuccess(lat, lng);
            })
            .catch(function () {
                onFail();
            });
    };

    SocioGeorefMap.prototype.geocode = function () {
        var self = this;

        if (this.freeText) {
            var direccion = this.direccionInput ? this.direccionInput.value.trim() : '';
            if (!direccion || !this.comuna) {
                this.setStatus('Indique su dirección para ubicar en el mapa.');
                return;
            }
            this.fetchNominatim(
                direccion + ', ' + this.comuna + ', Chile',
                function (lat, lng) {
                    self.centerOnCoords(lat, lng);
                },
                function () {
                    self.setStatus('No se encontró la dirección. Mueva el mapa manualmente.', true);
                    self.ensureMap();
                }
            );
            return;
        }

        var calle = getCalleName(this.calleSelect, this.callesMap);
        var numero = this.numeroInput ? this.numeroInput.value.trim() : '';
        if (!calle || !numero) {
            var centro = getCalleCentro(this.calleSelect);
            if (centro) {
                this.centerOnCoords(centro.lat, centro.lng, 'Centro de la calle. Ingrese número para afinar.');
                return;
            }
            this.setStatus('Seleccione calle e ingrese número para ubicar en el mapa.');
            return;
        }

        if (!this.comuna) {
            this.setStatus('Comuna de la organización no configurada.', true);
            return;
        }

        this.fetchNominatim(
            numero + ' ' + calle + ', ' + this.comuna + ', Chile',
            function (lat, lng) {
                self.centerOnCoords(lat, lng);
            },
            function () {
                var centro = getCalleCentro(self.calleSelect);
                if (centro) {
                    self.centerOnCoords(centro.lat, centro.lng, 'No se encontró el número. Centrado en la calle; ajuste el marcador.', true);
                    return;
                }
                if (!isNaN(self.latSede) && !isNaN(self.lngSede)) {
                    self.centerOnCoords(self.latSede, self.lngSede, 'No se encontró la dirección. Centrado en sede de la organización.', true);
                    return;
                }
                self.setStatus('No se encontró la dirección. Mueva el mapa manualmente.', true);
                self.ensureMap();
            }
        );
    };

    SocioGeorefMap.prototype.onCalleChange = function () {
        var centro = getCalleCentro(this.calleSelect);
        if (centro) {
            this.centerOnCoords(centro.lat, centro.lng, 'Centro de la calle cargado. Ingrese número para afinar.');
        }
        this.scheduleGeocode();
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
            this.calleSelect.addEventListener('change', this.onCalleChange.bind(this));
        }
        if (this.numeroInput && !this.freeText) {
            this.numeroInput.addEventListener('input', this._boundSchedule);
            this.numeroInput.addEventListener('change', this._boundSchedule);
        }
        if (this.direccionInput) {
            this.direccionInput.addEventListener('input', this._boundSchedule);
            this.direccionInput.addEventListener('change', this._boundSchedule);
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
            this.updateCoordsDisplay();
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
        this.updateCoordsDisplay();
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
