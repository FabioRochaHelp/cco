/**
 * Mapa Leaflet carregado via CDN (unpkg) para não depender de `npm install leaflet` no servidor de build.
 * Usa `$wire` no hospedeiro Livewire.
 */
let leafletCdnPromise = null;

async function loadLeafletFromCdn() {
    if (window.L) {
        return window.L;
    }
    if (leafletCdnPromise) {
        return leafletCdnPromise;
    }

    leafletCdnPromise = new Promise((resolve, reject) => {
        if (!document.querySelector('link[data-samu-leaflet-css]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.setAttribute('data-samu-leaflet-css', '1');
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.crossOrigin = 'anonymous';
        script.onload = () => resolve(window.L);
        script.onerror = () => reject(new Error('leaflet_cdn_load_failed'));
        document.head.appendChild(script);
    });

    return leafletCdnPromise;
}

let incidentOsmHooksInstalled = false;

function registerIncidentOsmAlpine() {
    const Alpine = window.Alpine;
    if (!Alpine?.data) {
        return;
    }

    if (window.__samuIncidentOsmAlpineDone) {
        return;
    }

    window.__samuIncidentOsmAlpineDone = true;

    Alpine.data('incidentOsmMap', () => ({
        map: null,
        marker: null,

        lw() {
            return this.$wire;
        },

        readLatLng() {
            const wire = this.lw();
            if (!wire) {
                return [-15.793889, -47.882778];
            }
            const lat = parseFloat(wire.latitude);
            const lng = parseFloat(wire.longitude);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                return [lat, lng];
            }

            return [-15.793889, -47.882778];
        },

        syncMarkerFromWire() {
            if (!this.map || !this.marker) {
                return;
            }
            const [lat, lng] = this.readLatLng();
            this.marker.setLatLng([lat, lng]);
            this.map.setView([lat, lng], this.map.getZoom(), { animate: false });
            requestAnimationFrame(() => this.map?.invalidateSize());
        },

        async init() {
            let L;
            try {
                L = await loadLeafletFromCdn();
            } catch {
                return;
            }

            const wire = this.lw();
            if (!wire || !this.$refs.mapEl) {
                return;
            }

            const [lat, lng] = this.readLatLng();
            this.map = L.map(this.$refs.mapEl, { zoomControl: true }).setView([lat, lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map);

            const icon = L.divIcon({
                className: 'incident-osm-pin',
                html: '<span class="block h-3.5 w-3.5 rounded-full border-2 border-cyan-700 bg-cyan-400 shadow-md ring-2 ring-white dark:border-cyan-300 dark:bg-cyan-500"></span>',
                iconSize: [14, 14],
                iconAnchor: [7, 7],
            });

            this.marker = L.marker([lat, lng], { draggable: true, icon }).addTo(this.map);

            this.marker.on('dragend', () => {
                const p = this.marker.getLatLng();
                wire.set('latitude', p.lat.toFixed(7));
                wire.set('longitude', p.lng.toFixed(7));
            });

            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                wire.set('latitude', e.latlng.lat.toFixed(7));
                wire.set('longitude', e.latlng.lng.toFixed(7));
            });

            const invalidate = () => {
                if (!this.map) {
                    return;
                }
                this.syncMarkerFromWire();
                setTimeout(() => this.map.invalidateSize(), 50);
            };

            window.addEventListener('incident-osm-invalidate', invalidate);

            requestAnimationFrame(invalidate);
        },
    }));
}

document.addEventListener('livewire:init', () => {
    registerIncidentOsmAlpine();

    if (!incidentOsmHooksInstalled && window.Livewire?.hook) {
        incidentOsmHooksInstalled = true;
        let morphInvalidateTimer = null;
        Livewire.hook('morph.updated', () => {
            clearTimeout(morphInvalidateTimer);
            morphInvalidateTimer = setTimeout(() => {
                window.dispatchEvent(new CustomEvent('incident-osm-invalidate'));
            }, 200);
        });
    }
});

document.addEventListener('alpine:init', () => {
    registerIncidentOsmAlpine();
});

// ---------------------------------------------------------------------------
// incidentRouteMap — mapa de percurso da ocorrência (detalhe, somente leitura)
// ---------------------------------------------------------------------------

function registerIncidentRouteMapAlpine() {
    const Alpine = window.Alpine;
    if (!Alpine?.data || window.__samuIncidentRouteMapDone) return;
    window.__samuIncidentRouteMapDone = true;

    Alpine.data('incidentRouteMap', (opts = {}) => ({
        map: null,
        polyline: null,
        state: 'idle', // idle | loading | loaded | empty | error | no_device

        routeUrl: opts.routeUrl ?? null,
        incidentLat: parseFloat(opts.incidentLat) || null,
        incidentLng: parseFloat(opts.incidentLng) || null,
        hasDevice: !!opts.hasDevice,
        vehiclePrefix: opts.vehiclePrefix ?? null,

        async init() {
            let L;
            try {
                L = await loadLeafletFromCdn();
            } catch {
                this.state = 'error';
                return;
            }

            if (!this.$refs.routeMapEl) return;

            const centerLat = this.incidentLat ?? -15.793889;
            const centerLng = this.incidentLng ?? -47.882778;
            const zoom = (this.incidentLat && this.incidentLng) ? 15 : 5;

            this.map = L.map(this.$refs.routeMapEl, { zoomControl: true }).setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            // Marcador fixo do local da ocorrência
            if (this.incidentLat && this.incidentLng) {
                const incidentIcon = L.divIcon({
                    className: '',
                    html: `<div style="width:14px;height:14px;border-radius:50%;background:#ef4444;border:2px solid #fff;box-shadow:0 0 0 2px #ef4444"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });
                L.marker([this.incidentLat, this.incidentLng], { icon: incidentIcon })
                    .addTo(this.map)
                    .bindPopup(`<b>Local da ocorrência</b>`);
            }

            requestAnimationFrame(() => this.map?.invalidateSize());

            if (!this.hasDevice) {
                this.state = 'no_device';
                return;
            }

            await this.fetchRoute(L);
        },

        async fetchRoute(L) {
            if (!this.routeUrl) { this.state = 'error'; return; }

            this.state = 'loading';

            try {
                const res = await fetch(this.routeUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const json = await res.json();

                if (!res.ok) {
                    this.state = 'error';
                    this._showErrorPopup(L, json.error ?? 'Erro ao carregar rota.');
                    return;
                }

                const points = json.points ?? [];

                if (points.length === 0) {
                    this.state = 'empty';
                    return;
                }

                const latlngs = points.map(p => [p.lat, p.lng]);

                this.polyline = L.polyline(latlngs, {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.85,
                }).addTo(this.map);

                // Marcador de início (empenho)
                const startIcon = L.divIcon({
                    className: '',
                    html: `<div style="width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #fff;box-shadow:0 0 0 2px #22c55e"></div>`,
                    iconSize: [12, 12], iconAnchor: [6, 6],
                });
                L.marker(latlngs[0], { icon: startIcon })
                    .addTo(this.map)
                    .bindPopup(`<b>Saída</b>${this.vehiclePrefix ? ' — ' + this.vehiclePrefix : ''}`);

                // Marcador de fim (retorno)
                const endIcon = L.divIcon({
                    className: '',
                    html: `<div style="width:12px;height:12px;border-radius:50%;background:#f59e0b;border:2px solid #fff;box-shadow:0 0 0 2px #f59e0b"></div>`,
                    iconSize: [12, 12], iconAnchor: [6, 6],
                });
                L.marker(latlngs[latlngs.length - 1], { icon: endIcon })
                    .addTo(this.map)
                    .bindPopup(`<b>Chegada à base</b>`);

                this.map.fitBounds(this.polyline.getBounds(), { padding: [32, 32] });
                this.state = 'loaded';

                requestAnimationFrame(() => this.map?.invalidateSize());

            } catch {
                this.state = 'error';
            }
        },

        _showErrorPopup(L, message) {
            if (this.incidentLat && this.incidentLng) {
                L.popup()
                    .setLatLng([this.incidentLat, this.incidentLng])
                    .setContent(`<span style="color:#ef4444">${message}</span>`)
                    .openOn(this.map);
            }
        },
    }));
}

document.addEventListener('alpine:init', () => {
    registerIncidentRouteMapAlpine();
});

document.addEventListener('livewire:init', () => {
    registerIncidentRouteMapAlpine();
});

// ---------------------------------------------------------------------------
// dispatchMap — mapa tático do CCO (ocorrências + posição de viaturas, Reverb)
// ---------------------------------------------------------------------------

function registerDispatchMapAlpine() {
    const Alpine = window.Alpine;
    if (!Alpine?.data || window.__samuDispatchMapDone) return;
    window.__samuDispatchMapDone = true;

    Alpine.data('dispatchMap', (opts = {}) => ({
        map: null,
        _L: null,
        _pollTimer: null,

        // marcadores indexados por id
        _incidentMarkers: {},
        _vehicleMarkers: {},

        // dados iniciais (passados como JSON do blade)
        incidents: opts.incidents ?? [],
        vehicles:  opts.vehicles  ?? [],
        incidentUrlBase: opts.incidentUrlBase ?? '',
        vehiclesUrl:     opts.vehiclesUrl     ?? '',

        async init() {
            let L;
            try { L = await loadLeafletFromCdn(); } catch { return; }
            if (!this.$refs.dispatchMapEl) return;

            this._L = L;

            const firstPoint = this._firstPoint();
            this.map = L.map(this.$refs.dispatchMapEl, { zoomControl: true })
                .setView(firstPoint ?? [-15.793889, -47.882778], firstPoint ? 13 : 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            this.incidents.forEach(i => this._addOrUpdateIncident(i));
            this.vehicles.forEach(v => this._addOrUpdateVehicle(v));

            this._fitAll();
            requestAnimationFrame(() => this.map?.invalidateSize());

            // Reverb — caminho rápido (funciona quando queue+reverb estão rodando)
            this._subscribeReverb();

            // Polling direto ao Traccar via endpoint PHP — fallback confiável (30s)
            this._startPolling();
        },

        destroy() {
            clearInterval(this._pollTimer);
        },

        // ── Polling de posições (independe de queue/reverb) ─────────────────
        _startPolling() {
            if (!this.vehiclesUrl) return;

            // Primeira busca imediata
            this._fetchVehicles();

            // Depois a cada 30s
            this._pollTimer = setInterval(() => this._fetchVehicles(), 30_000);
        },

        async _fetchVehicles() {
            try {
                const res  = await fetch(this.vehiclesUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const list = await res.json();
                list.forEach(v => this._addOrUpdateVehicle(v));
            } catch {
                // silencioso — Traccar pode estar temporariamente indisponível
            }
        },

        // ── Reverb — sub-segundo quando funcionando ──────────────────────────
        _subscribeReverb() {
            const Echo = window.Echo;
            if (!Echo) return;

            // Reutiliza canal já subscrito pelo app.js (evita duplicata)
            const ch = Echo.private('operations.dispatch');

            ch.listen('.vehicle.position.updated', (e) => {
                this._addOrUpdateVehicle(e);
            });

            ch.listen('.incident.created', (e) => {
                if (e.lat && e.lng) {
                    this._addOrUpdateIncident({
                        id:     e.incident_id,
                        lat:    e.lat,
                        lng:    e.lng,
                        talao:  e.talao,
                        year:   e.dispatch_year,
                        nature: e.nature ?? '—',
                        status: e.status,
                        url:    this._incidentUrl(e.incident_id),
                    });
                }
            });

            ch.listen('.unit.dispatched', (e) => {
                const m = this._incidentMarkers[e.incident_id];
                if (m) {
                    m.setIcon(this._incidentIcon('#2563eb'));
                } else if (e.lat && e.lng) {
                    this._addOrUpdateIncident({
                        id: e.incident_id, lat: e.lat, lng: e.lng,
                        talao: '', year: '', nature: '—',
                        status: e.status, url: this._incidentUrl(e.incident_id),
                    });
                }
            });

            ch.listen('.unit.released', (e) => {
                if (['closed', 'cancelled'].includes(e.status)) {
                    const m = this._incidentMarkers[e.incident_id];
                    if (m) { m.remove(); delete this._incidentMarkers[e.incident_id]; }
                }
            });
        },

        _addOrUpdateIncident(inc) {
            const L = this._L;
            if (!L || !this.map || !inc.lat || !inc.lng) return;

            const color    = inc.status !== 'open' ? '#2563eb' : '#ef4444';
            const existing = this._incidentMarkers[inc.id];
            const popup    = `<div style="min-width:160px">
                <b>Talão ${inc.talao}/${inc.year}</b><br>
                <span style="color:#6b7280;font-size:12px">${inc.nature}</span><br>
                <a href="${inc.url}" style="color:#2563eb;font-size:12px">ver ocorrência →</a>
            </div>`;

            if (existing) {
                existing.setLatLng([inc.lat, inc.lng]);
                existing.getPopup()?.setContent(popup);
                existing.setIcon(this._incidentIcon(color));
                return;
            }

            this._incidentMarkers[inc.id] = L.marker([inc.lat, inc.lng], {
                icon: this._incidentIcon(color),
            }).addTo(this.map).bindPopup(popup);
        },

        _addOrUpdateVehicle(veh) {
            const L = this._L;
            if (!L || !this.map || !veh.lat || !veh.lng) return;

            const moving   = (veh.speed_kmh ?? 0) > 2;
            const color    = moving ? '#22c55e' : '#64748b';
            const existing = this._vehicleMarkers[veh.vehicle_id];
            const popup    = `<div style="min-width:120px">
                <b>${veh.prefix}</b><br>
                <span style="color:#6b7280;font-size:12px">${(veh.speed_kmh ?? 0).toFixed(0)} km/h</span>
                ${veh.fix_time ? `<br><span style="color:#9ca3af;font-size:11px">${veh.fix_time}</span>` : ''}
            </div>`;

            if (existing) {
                existing.setLatLng([veh.lat, veh.lng]);
                existing.getPopup()?.setContent(popup);
                existing.setIcon(this._vehicleIcon(color, veh.prefix));
                return;
            }

            this._vehicleMarkers[veh.vehicle_id] = L.marker([veh.lat, veh.lng], {
                icon: this._vehicleIcon(color, veh.prefix),
                zIndexOffset: 100,
            }).addTo(this.map).bindPopup(popup);
        },

        _incidentIcon(color) {
            const L = this._L;
            return L.divIcon({
                className: '',
                html: `<div style="width:14px;height:14px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 0 0 2px ${color}"></div>`,
                iconSize: [14, 14], iconAnchor: [7, 7],
            });
        },

        _vehicleIcon(color, prefix) {
            const L     = this._L;
            const label = (prefix ?? '').slice(0, 5);
            return L.divIcon({
                className: '',
                html: `<div style="display:flex;flex-direction:column;align-items:center">
                    <div style="background:${color};color:#fff;font-size:9px;font-weight:700;padding:2px 5px;border-radius:4px;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,.4)">${label}</div>
                    <div style="width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-top:6px solid ${color}"></div>
                </div>`,
                iconSize: [36, 24], iconAnchor: [18, 24],
            });
        },

        _incidentUrl(id) {
            return this.incidentUrlBase.replace('__ID__', id);
        },

        _firstPoint() {
            const i = this.incidents.find(x => x.lat && x.lng);
            if (i) return [i.lat, i.lng];
            const v = this.vehicles.find(x => x.lat && x.lng);
            if (v) return [v.lat, v.lng];
            return null;
        },

        _fitAll() {
            const all = [
                ...Object.values(this._incidentMarkers),
                ...Object.values(this._vehicleMarkers),
            ];
            if (all.length === 0 || !this._L || !this.map) return;
            const group = this._L.featureGroup(all);
            this.map.fitBounds(group.getBounds().pad(0.2));
        },
    }));
}

document.addEventListener('alpine:init', () => { registerDispatchMapAlpine(); });
document.addEventListener('livewire:init', () => { registerDispatchMapAlpine(); });
