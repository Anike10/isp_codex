(() => {
    const config = window.NETWORK_PARTY_LOCATIONS_CONFIG || {};
    const defaultViewStorageKey = 'network-party-locations-default-view-v1';
    const fallbackDefaultView = {
        center: [89.1219, 23.9013],
        zoom: 14,
    };

    const basemaps = {
        voyager: {
            label: 'Voyager',
            tiles: [
                'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
            ],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
        osm: {
            label: 'OpenStreetMap',
            tiles: [
                'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png',
            ],
            attribution: '&copy; OpenStreetMap contributors',
        },
        light: {
            label: 'Light',
            tiles: [
                'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png',
                'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png',
            ],
            attribution: '&copy; Stadia Maps &copy; OpenStreetMap contributors',
        },
        dark: {
            label: 'Dark',
            tiles: [
                'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png',
                'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png',
            ],
            attribution: '&copy; Stadia Maps &copy; OpenStreetMap contributors',
        },
        satellite: {
            label: 'Satellite',
            tiles: [
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            ],
            attribution: 'Tiles &copy; Esri',
        },
        google_road: {
            label: 'Google Road',
            tiles: [
                'https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
                'https://mt2.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
                'https://mt3.google.com/vt/lyrs=r&x={x}&y={y}&z={z}',
            ],
            attribution: '&copy; Google',
        },
        google_satellite: {
            label: 'Google Sat',
            tiles: [
                'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                'https://mt2.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                'https://mt3.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
            ],
            attribution: '&copy; Google',
        },
    };

    const state = {
        map: null,
        activeBasemap: 'google_road',
        allCustomers: new Map(),
        customers: new Map(),
        pendingPartyLocationCustomerId: null,
        selectedCustomerId: null,
        searchToken: 0,
        popup: null,
    };

    const dom = {
        basemapTools: document.getElementById('partyBasemapTools'),
        searchForm: document.getElementById('partyLocationSearchForm'),
        searchInput: document.getElementById('partyLocationQuery'),
        searchStatus: document.getElementById('partyLocationSearchStatus'),
        partyList: document.getElementById('partyList'),
        partyStats: document.getElementById('partyLocationStats'),
        mapStatus: document.getElementById('mapStatus'),
        placementPanel: document.getElementById('partyPlacementPanel'),
        placementTitle: document.getElementById('partyPlacementTitle'),
        placementInfo: document.getElementById('partyPlacementInfo'),
        cancelPlacementBtn: document.getElementById('cancelPartyPlacementBtn'),
    };

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        if (typeof window.maplibregl === 'undefined') {
            setStatus('Map library could not load. Refresh the page and try again.');
            return;
        }
        if (!config.customersUrl) {
            setStatus('Customers API URL is missing. Please refresh the page after deployment.');
            return;
        }

        const mapContainer = document.getElementById('networkMap');
        if (!mapContainer) {
            setStatus('Map container missing. Refresh the page and try again.');
            return;
        }

        const defaultView = loadDefaultView();
        const basemapEntries = Object.entries(basemaps).map(([key, basemap]) => [
            `basemap-${key}`,
            {
                type: 'raster',
                tiles: basemap.tiles,
                tileSize: 256,
                attribution: basemap.attribution,
            },
        ]);

        try {
            state.map = new maplibregl.Map({
                container: mapContainer,
                style: {
                    version: 8,
                    sources: Object.fromEntries(basemapEntries),
                    layers: Object.keys(basemaps).map((key) => ({
                        id: `basemap-${key}`,
                        type: 'raster',
                        source: `basemap-${key}`,
                        layout: {
                            visibility: key === state.activeBasemap ? 'visible' : 'none',
                        },
                    })),
                },
                center: defaultView.center,
                zoom: defaultView.zoom,
                maxZoom: 22,
                doubleClickZoom: false,
            });

            state.map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
            state.map.on('load', onMapLoad);
            requestAnimationFrame(() => {
                state.map.resize();
            });
            state.map.resize();
            bindGlobalEvents();
        } catch (error) {
            console.error('Party location map initialization failed.', error);
            setStatus(`Map could not start: ${error.message}`);
        }
    }

    function onMapLoad() {
        addLayers();
        state.map.on('click', onMapClick);
        state.map.on('mouseenter', 'party-location-circle', function () {
            state.map.getCanvas().style.cursor = 'pointer';
        });
        state.map.on('mouseleave', 'party-location-circle', function () {
            state.map.getCanvas().style.cursor = '';
        });

        loadCustomers();
    }

    function bindGlobalEvents() {
        bindBasemapTools();
        if (dom.searchForm) {
            dom.searchForm.addEventListener('submit', onPartySearchSubmit);
        }
        if (dom.partyList) {
            dom.partyList.addEventListener('click', onPartyListClick);
            dom.partyList.addEventListener('dblclick', onPartyListDblClick);
        }
        if (dom.cancelPlacementBtn) {
            dom.cancelPlacementBtn.addEventListener('click', cancelPartyLocationPlacement);
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                cancelPartyLocationPlacement();
            }
        });
        if (dom.searchInput) {
            dom.searchInput.addEventListener('input', function () {
                clearSearchStatus();
            });
        }
    }

    function bindBasemapTools() {
        if (!dom.basemapTools) {
            return;
        }

        dom.basemapTools.querySelectorAll('.basemap-tool').forEach((button) => {
            button.addEventListener('click', function () {
                setBasemap(button.dataset.basemap);
            });
        });
    }

    function onPartySearchSubmit(event) {
        event.preventDefault();
        const query = (dom.searchInput?.value || '').trim();
        loadCustomers(query);
    }

    function onPartyListClick(event) {
        const button = event.target.closest('[data-party-action]');
        if (!button) {
            return;
        }

        event.preventDefault();
        const action = button.dataset.partyAction;
        const customerId = button.dataset.customerId;
        if (!customerId) {
            return;
        }

        if (action === 'focus') {
            focusCustomer(customerId);
            return;
        }

        if (action === 'add' || action === 'edit') {
            requestPartyLocationPlacement(customerId);
            return;
        }

        if (action === 'remove') {
            removePartyLocation(customerId);
            return;
        }

        if (action === 'fiber-removed') {
            markFiberRemoved(customerId);
        }
    }

    function onPartyListDblClick(event) {
        const inlineField = event.target.closest('[data-inline-field]');
        if (!inlineField) {
            return;
        }

        if (!dom.partyList?.contains(inlineField)) {
            return;
        }

        event.preventDefault();
        startInlineEdit(inlineField);
    }

    function onMapClick(event) {
        if (state.pendingPartyLocationCustomerId) {
            placePartyLocationFromMapClick(state.pendingPartyLocationCustomerId, event.lngLat.lng, event.lngLat.lat);
            return;
        }

        const clicked = state.map.queryRenderedFeatures(event.point, {
            layers: ['party-location-circle'],
        });

        if (!clicked.length) {
            return;
        }

        const customerId = clicked[0].properties?.customer_id;
        if (!customerId) {
            return;
        }

        focusCustomer(customerId);
    }

    function setBasemap(basemapKey) {
        if (!basemaps[basemapKey] || !state.map?.isStyleLoaded()) {
            return;
        }

        state.activeBasemap = basemapKey;
        state.map.setPaintProperty('party-location-halo', 'circle-color', '#0f766e');
        Object.keys(basemaps).forEach((key) => {
            state.map.setLayoutProperty(
                `basemap-${key}`,
                'visibility',
                key === basemapKey ? 'visible' : 'none'
            );
        });

        dom.basemapTools?.querySelectorAll('.basemap-tool').forEach((button) => {
            button.classList.toggle('active', button.dataset.basemap === basemapKey);
        });

        setStatus(`${basemaps[basemapKey].label} map selected.`);
        saveDefaultView({
            center: state.map.getCenter().toArray(),
            zoom: state.map.getZoom(),
        });
    }

    function addLayers() {
        state.map.addSource('party-locations', {
            type: 'geojson',
            data: emptyCollection(),
        });

        state.map.addLayer({
            id: 'party-location-halo',
            type: 'circle',
            source: 'party-locations',
            paint: {
                'circle-color': ['case', ['==', ['get', 'fiber_removal_pending'], true], '#f04438', '#f97316'],
                'circle-radius': 14,
                'circle-opacity': 0.06,
                'circle-stroke-width': 0,
            },
        });

        state.map.addLayer({
            id: 'party-location-circle',
            type: 'circle',
            source: 'party-locations',
            paint: {
                'circle-radius': 7,
                'circle-color': ['case', ['==', ['get', 'fiber_removal_pending'], true], '#f04438', '#16a34a'],
                'circle-stroke-color': ['case', ['==', ['get', 'fiber_removal_pending'], true], '#7a271a', '#ffffff'],
                'circle-stroke-width': 2,
            },
        });

    }

    async function loadCustomers(query = '') {
        if (!dom.mapStatus) {
            return;
        }

        state.searchToken += 1;
        const requestToken = state.searchToken;
        setStatus('Loading parties...');
        clearSearchStatus();

        const url = new URL(config.customersUrl);
        const trimmed = (query || '').trim();

        if (trimmed) {
            const localMatches = findMatchingCustomers(trimmed);
            if (state.allCustomers.size) {
                state.customers = new Map(localMatches.map((feature) => [String(feature.properties?.customer_id || ''), feature]));
                renderPartyLocationSource();
                renderPartyList();
                updatePartyStats();
                setStatus(`${localMatches.length} matching parties shown.`);
                setSearchStatus(`${localMatches.length} parties found for "${trimmed}".`);
                return;
            }

            url.searchParams.set('q', trimmed);
        } else {
            url.searchParams.delete('q');
        }

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(await responseErrorMessage(response, 'Unable to load parties.'));
            }

            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
            if (!contentType.includes('application/json')) {
                const body = await response.text().catch(() => '');
                if (body.toLowerCase().includes('<html') && body.toLowerCase().includes('login')) {
                    throw new Error('Session expired. Please sign in again to load parties.');
                }

                throw new Error('Invalid response from customers API.');
            }

            const collection = await response.json();
            const features = Array.isArray(collection.features) ? collection.features : [];

            if (requestToken !== state.searchToken) {
                return;
            }

            if (!trimmed) {
                state.allCustomers = new Map();
            }

            state.customers = new Map();
            features.forEach((feature) => {
                const customerId = feature?.properties?.customer_id;
                if (!customerId) {
                    return;
                }

                state.customers.set(String(customerId), feature);
                if (!trimmed) {
                    state.allCustomers.set(String(customerId), feature);
                }
            });

            renderPartyLocationSource();
            renderPartyList();
            updatePartyStats();

            const locationCount = features.filter(customerHasLocation).length;
            setStatus(trimmed
                ? `${features.length} matching parties shown.`
                : `${features.length} parties loaded. ${locationCount} locations shown on the map.`);

            if (trimmed) {
                setSearchStatus(`${features.length} parties found for "${trimmed}".`);
            } else {
                setSearchStatus('');
            }

            const initialCustomerId = String(config.initialCustomerId || '').trim();
            if (initialCustomerId) {
                focusCustomer(initialCustomerId);
                config.initialCustomerId = null;
            }
        } catch (error) {
            setStatus(error.message);
            if (dom.partyList) {
                dom.partyList.innerHTML = `<div class="search-empty error">${escapeHtml(error.message)}</div>`;
            }
        }
    }

    function renderPartyLocationSource() {
        const locationFeatures = [...state.customers.values()].filter(customerHasLocation);
        setSourceData('party-locations', {
            type: 'FeatureCollection',
            features: locationFeatures,
        });
    }

    function renderPartyList() {
        if (!dom.partyList) {
            return;
        }

        if (!state.customers.size) {
            dom.partyList.innerHTML = '<div class="search-empty">No parties found.</div>';
            return;
        }

        const rows = [...state.customers.values()].sort((a, b) => {
            const firstName = (formatPartyDisplayName(a) || '').toLowerCase();
            const secondName = (formatPartyDisplayName(b) || '').toLowerCase();
            if (firstName === secondName) {
                return formatPartyLabel(a).localeCompare(formatPartyLabel(b));
            }

            return firstName.localeCompare(secondName);
        });

        dom.partyList.innerHTML = rows.map((feature) => {
            const properties = feature.properties || {};
            const customerId = String(properties.customer_id || '').trim();
            const label = formatPartyLabel(feature);
            const nameValue = String(formatPartyDisplayName(feature) || '').trim();
            const userName = formatPartyUserName(feature);
            const phoneValue = String(properties.phone || '').trim();
            const userIdValue = String(properties.connection_id || properties.mikrotik_username || '').trim();
            const statusText = formatPartyStatus(properties.status);
            const statusClass = formatPartyStatusClass(properties.status);
            const comment = formatPartyComment(feature);
            const hasLocation = customerHasLocation(feature);
            const locationText = hasLocation ? 'Location saved' : 'No location';
            const inlineUpdateUrl = partyInlineUpdateUrl(feature);
            const addressText = String(properties.address || '').trim();

            const details = compactJoin([
                addressText ? `Address: ${addressText}` : '',
                comment ? `Comment: ${comment}` : '',
            ]);

            return `
                <div class="party-list-item ${hasLocation ? 'has-location' : ''} ${properties.fiber_removal_pending ? 'is-deleted' : ''} ${String(state.selectedCustomerId) === customerId ? 'is-active' : ''}" data-customer-row="${escapeHtml(customerId)}">
                    <div class="party-list-title">${escapeHtml(label)}${properties.deleted ? ' <span class="party-deleted-tag">deleted</span>' : ''}</div>
                    <div class="party-list-meta">
                        <span>Name: ${inlineFieldHtml('name', customerId, inlineUpdateUrl, nameValue, 'Not provided')}</span>
                        <span><span class="badge ${escapeHtml(statusClass)}">${escapeHtml(statusText)}</span></span>
                        <span>User: ${escapeHtml(userName)}</span>
                        <span>Mobile: ${inlineFieldHtml('phone', customerId, inlineUpdateUrl, phoneValue, 'Not provided')}</span>
                        <span>User ID: ${inlineFieldHtml('connection_id', customerId, inlineUpdateUrl, userIdValue, 'Not assigned')}</span>
                        <span>${escapeHtml(locationText)}</span>
                        ${details ? `<span>${escapeHtml(details)}</span>` : ''}
                    </div>
                    <div class="party-list-actions">
                        <button type="button" class="search-result-action search-result-action--focus" data-party-action="focus" data-customer-id="${escapeHtml(customerId)}">View on map</button>
                        <button type="button" class="${hasLocation ? 'search-result-action' : 'search-result-action search-result-action--primary'}" data-party-action="${hasLocation ? 'edit' : 'add'}" data-customer-id="${escapeHtml(customerId)}">${hasLocation ? 'Edit location' : 'Add location'}</button>
                        <button type="button" class="search-result-action" data-copy-party="${escapeHtml(customerId)}">Copy</button>
                        <a href="#" class="search-result-action search-result-action--primary" data-whatsapp-share="${escapeHtml(customerId)}" data-share-label="${escapeHtml(label || 'Party')}" ${hasLocation ? '' : 'data-share-disabled=\"1\"'}>Hotspot share</a>
                        ${hasLocation ? `<button type="button" class="search-result-action search-result-action--danger" data-party-action="remove" data-customer-id="${escapeHtml(customerId)}">Delete location</button>` : ''}
                        ${properties.fiber_removal_pending ? `<button type="button" class="search-result-action search-result-action--danger" data-party-action="fiber-removed" data-customer-id="${escapeHtml(customerId)}">Fiber removed — hide from map</button>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        bindPartyListInlineActions();
    }

    function updatePartyStats() {
        if (!dom.partyStats) {
            return;
        }

        const total = state.customers.size;
        const withLocation = [...state.customers.values()].filter(customerHasLocation).length;
        dom.partyStats.textContent = `Total parties: ${total} | Locations set: ${withLocation}`;
    }

    function focusCustomer(customerId) {
        const feature = state.customers.get(String(customerId));
        if (!feature) {
            setStatus(`Party ${escapeHtml(customerId)} not found.`);
            return false;
        }

        if (!customerHasLocation(feature)) {
            requestPartyLocationPlacement(customerId);
            return false;
        }

        state.selectedCustomerId = String(customerId);
        renderPartyList();

        const coordinates = feature.geometry?.coordinates;
        const rowId = String(customerId);
        const row = document.querySelector(`[data-customer-row="${CSS.escape(rowId)}"]`);
        if (row) {
            row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        state.map.flyTo({
            center: coordinates,
            zoom: Math.max(state.map.getZoom(), 17),
            duration: 750,
        });
        openPartyPopup(feature);

        const label = formatPartyLabel(feature);
        setStatus(`${label} focused on the map.`);
        return true;
    }

    function openPartyPopup(feature) {
        if (!feature || !customerHasLocation(feature)) {
            return;
        }

        const properties = feature.properties || {};
        const customerId = String(properties.customer_id || '').trim();
        const customerName = formatPartyDisplayName(feature);
        const userName = formatPartyUserName(properties);
        const statusText = formatPartyStatus(properties.status);
        const statusClass = formatPartyStatusClass(properties.status);
        const inlineUpdateUrl = partyInlineUpdateUrl(feature);
        const phoneValue = String(properties.phone || '').trim();
        const userIdValue = String(properties.connection_id || properties.mikrotik_username || '').trim();
        const shareControls = shareButtonsMarkup(feature);

        if (state.popup) {
            state.popup.remove();
            state.popup = null;
        }

        state.popup = new maplibregl.Popup({ offset: 16 })
            .setLngLat(feature.geometry.coordinates)
            .setHTML(`
                <p class="popup-title">${escapeHtml((userName && userName !== 'Not provided' ? userName : '') || customerName || `Party #${customerId}`)}</p>
                <p class="popup-meta">
                    <span class="popup-meta-id">Party #${escapeHtml(customerId)}</span>
                    <span class="badge-sep">|</span>
                    <span class="badge ${properties.deleted ? 'inactive' : escapeHtml(statusClass)}">${properties.deleted ? 'Deleted' : escapeHtml(statusText)}</span>
                    ${properties.fiber_removal_pending ? '<span class="badge-sep">|</span><span class="popup-fiber-flag">fiber not removed</span>' : ''}
                </p>
                <dl class="popup-details">
                    <div><dt>Name</dt><dd>${inlineFieldHtml('name', customerId, inlineUpdateUrl, String(customerName || ''), 'Not provided')}</dd></div>
                    <div><dt>Party ID</dt><dd>${escapeHtml(formatPartyLabel(feature) || `Party #${customerId}`)}</dd></div>
                    <div><dt>User Name</dt><dd>${inlineFieldHtml('connection_id', customerId, inlineUpdateUrl, userIdValue, 'Not assigned')}</dd></div>
                    <div><dt>Active Status</dt><dd><span class="badge ${escapeHtml(statusClass)}">${escapeHtml(statusText)}</span></dd></div>
                    <div><dt>Comment</dt><dd>${escapeHtml(properties.comment || 'Not provided')}</dd></div>
                    <div><dt>Phone</dt><dd>${inlineFieldHtml('phone', customerId, inlineUpdateUrl, phoneValue, 'Not provided')}</dd></div>
                    <div><dt>Address</dt><dd>${escapeHtml(properties.address || 'Not provided')}</dd></div>
                    <div><dt>Share</dt><dd>${shareControls}</dd></div>
                    <div><dt>Actions</dt><dd><button type="button" class="search-result-action search-result-action--primary" data-popup-action="edit" data-popup-party="${escapeHtml(customerId)}">Edit location</button> <button type="button" class="search-result-action search-result-action--danger" data-popup-action="remove" data-popup-party="${escapeHtml(customerId)}">Delete location</button>${properties.fiber_removal_pending ? ` <button type="button" class="search-result-action search-result-action--danger" data-popup-action="fiber-removed" data-popup-party="${escapeHtml(customerId)}">Fiber removed — hide from map</button>` : ''}</dd></div>
                </dl>
            `)
            .addTo(state.map);

        const popupElement = state.popup.getElement();
        const editButton = popupElement.querySelector('[data-popup-action="edit"]');
        const removeButton = popupElement.querySelector('[data-popup-action="remove"]');
        const fiberRemovedButton = popupElement.querySelector('[data-popup-action="fiber-removed"]');
        const copyButton = popupElement.querySelector('[data-copy-party]');
        const whatsappButton = popupElement.querySelector('[data-whatsapp-share]');
        const copyTarget = findPartyFeature(customerId);

        if (editButton) {
            editButton.addEventListener('click', function () {
                const selectedId = editButton.dataset.popupParty;
                requestPartyLocationPlacement(selectedId);
                if (state.popup) {
                    state.popup.remove();
                    state.popup = null;
                }
            });
        }

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                const selectedId = removeButton.dataset.popupParty;
                removePartyLocation(selectedId);
                if (state.popup) {
                    state.popup.remove();
                    state.popup = null;
                }
            });
        }

        if (fiberRemovedButton) {
            fiberRemovedButton.addEventListener('click', function () {
                markFiberRemoved(fiberRemovedButton.dataset.popupParty);
            });
        }

        if (copyButton && copyTarget) {
            copyButton.addEventListener('click', async function () {
                await copyPartyShareText(buildPartyShareText(copyTarget), copyButton);
            });
        }

        if (whatsappButton && copyTarget) {
            bindPartyWhatsappButton(whatsappButton, copyTarget);
            whatsappButton.addEventListener('click', (event) => {
                if (!customerHasLocation(copyTarget)) {
                    event.preventDefault();
                }
            });
        }

        bindPartyListInlineFields(popupElement);
    }

    function requestPartyLocationPlacement(customerId) {
        const feature = state.customers.get(String(customerId));
        if (!feature) {
            return;
        }

        state.pendingPartyLocationCustomerId = String(customerId);
        state.map.getCanvas().style.cursor = 'crosshair';

        const properties = feature.properties || {};
        const customerName = formatPartyDisplayName(feature) || 'Name not provided';
        const userName = formatPartyUserName(properties);
        const activeStatusText = formatPartyStatus(properties.status);
        const placementHeader = customerHasLocation(feature)
            ? `Edit location for Party #${customerId}`
            : `Set new location for Party #${customerId}`;

        if (dom.placementPanel) {
            dom.placementTitle.textContent = placementHeader;
            dom.placementInfo.innerHTML = `
                <span class="detail"><span class="label">Party Name:</span><strong>${escapeHtml(customerName)}</strong></span>
                <span class="detail"><span class="label">Party ID:</span><strong>#${escapeHtml(customerId)}</strong></span>
                <span class="detail"><span class="label">User:</span><strong>${escapeHtml(userName)}</strong></span>
                <span class="detail"><span class="label">Active:</span><strong><span class="badge ${escapeHtml(formatPartyStatusClass(properties.status))}">${escapeHtml(activeStatusText)}</span></strong></span>
                <span class="detail"><span class="label">Comment:</span><strong>${escapeHtml(properties.comment || 'Not provided')}</strong></span>
            `;
            dom.placementPanel.hidden = false;
        }

        setStatus(`Placement mode: click on the map to save location for Party #${customerId}.`);
    }

    function cancelPartyLocationPlacement() {
        if (!state.pendingPartyLocationCustomerId) {
            if (dom.placementPanel) {
                dom.placementPanel.hidden = true;
            }
            return;
        }

        state.pendingPartyLocationCustomerId = null;
        state.map.getCanvas().style.cursor = '';
        if (dom.placementPanel) {
            dom.placementPanel.hidden = true;
            dom.placementInfo.innerHTML = '';
            dom.placementTitle.textContent = 'Place party location';
        }

        setStatus('Party location placement cancelled.');
    }

    async function placePartyLocationFromMapClick(customerId, longitude, latitude) {
        const feature = await savePartyLocation(customerId, {
            map_latitude: latitude,
            map_longitude: longitude,
        });

        if (!feature) {
            return;
        }

        upsertPartyFeature(feature);
        state.pendingPartyLocationCustomerId = null;
        state.map.getCanvas().style.cursor = '';
        if (dom.placementPanel) {
            dom.placementPanel.hidden = true;
            dom.placementInfo.innerHTML = '';
            dom.placementTitle.textContent = 'Place party location';
        }

        setStatus(`Party #${customerId} location saved.`);
        focusCustomer(customerId);
    }

    async function savePartyLocation(customerId, payload) {
        const url = `${config.customersUrl}/${encodeURIComponent(customerId)}/location`;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(await responseErrorMessage(response, 'Unable to save party location.'));
            }

            return await response.json();
        } catch (error) {
            setStatus(error.message);
            return null;
        }
    }

    async function markFiberRemoved(customerId) {
        const feature = state.customers.get(String(customerId));
        const label = formatPartyLabel(feature || { customer_id: customerId });
        const url = feature?.properties?.fiber_removed_url
            || `${config.customersUrl}/${encodeURIComponent(customerId)}/fiber-removed`;
        if (!confirm(`Confirm the drop fiber for ${label} has been removed?\nThe marker will be taken off the map.`)) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error(await responseErrorMessage(response, 'Unable to update this party.'));
            }

            state.customers.delete(String(customerId));
            state.allCustomers.delete(String(customerId));
            renderPartyLocationSource();
            renderPartyList();
            updatePartyStats();
            if (state.popup) {
                state.popup.remove();
                state.popup = null;
            }
            setStatus(`${label} removed from the map — fiber cleanup recorded.`);
        } catch (error) {
            setStatus(error.message);
        }
    }

    async function removePartyLocation(customerId) {
        const feature = state.customers.get(String(customerId));
        const label = formatPartyLabel(feature || { customer_id: customerId });
        if (!confirm(`Delete map location for ${label}?`)) {
            return;
        }

        const url = `${config.customersUrl}/${encodeURIComponent(customerId)}/location`;

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error(await responseErrorMessage(response, 'Unable to remove party location.'));
            }

            const updated = await response.json();
            upsertPartyFeature(updated);
            if (String(state.pendingPartyLocationCustomerId) === String(customerId)) {
                cancelPartyLocationPlacement();
            }

            if (state.popup) {
                state.popup.remove();
                state.popup = null;
            }

            setStatus(`Party #${customerId} location removed.`);
        } catch (error) {
            setStatus(error.message);
        }
    }

    function upsertPartyFeature(feature) {
        const customerId = feature?.properties?.customer_id;
        if (!customerId) {
            return;
        }

        state.customers.set(String(customerId), feature);
        renderPartyLocationSource();
        renderPartyList();
        updatePartyStats();
    }

    function customerHasLocation(feature) {
        const longitude = Number(feature?.geometry?.coordinates?.[0]);
        const latitude = Number(feature?.geometry?.coordinates?.[1]);
        return Number.isFinite(longitude) && Number.isFinite(latitude);
    }

    function setSourceData(sourceId, data) {
        const source = state.map?.getSource(sourceId);
        if (!source || typeof source.setData !== 'function') {
            return;
        }

        source.setData(data);
    }

    function emptyCollection() {
        return { type: 'FeatureCollection', features: [] };
    }

    function setStatus(message) {
        if (dom.mapStatus) {
            dom.mapStatus.textContent = message;
        }
    }

    function setSearchStatus(message) {
        if (!dom.searchStatus) {
            return;
        }

        if (message) {
            dom.searchStatus.textContent = message;
            dom.searchStatus.hidden = false;
        } else {
            dom.searchStatus.hidden = true;
            dom.searchStatus.textContent = '';
        }
    }

    function clearSearchStatus() {
        if (dom.searchStatus) {
            dom.searchStatus.hidden = true;
            dom.searchStatus.textContent = '';
        }
    }

    function bindPartyListInlineActions() {
        if (!dom.partyList) {
            return;
        }

        dom.partyList.querySelectorAll('[data-copy-party]').forEach((button) => {
            const feature = findPartyFeature(button.dataset.copyParty);
            if (!feature) {
                return;
            }

            button.addEventListener('click', async () => {
                await copyPartyShareText(buildPartyShareText(feature), button);
            }, { once: true });
        });

        dom.partyList.querySelectorAll('[data-whatsapp-share]').forEach((button) => {
            const feature = findPartyFeature(button.dataset.whatsappShare);
            if (!feature) {
                return;
            }

            bindPartyWhatsappButton(button, feature);
            button.addEventListener('click', (event) => {
                if (!customerHasLocation(feature)) {
                    event.preventDefault();
                }
            }, { once: true });
        });
    }

    function bindPartyListInlineFields(container) {
        const root = container || dom.partyList;
        if (!root) {
            return;
        }

        root.querySelectorAll('[data-inline-field]').forEach((field) => {
            if (field.classList) {
                field.classList.add('inline-edit-field');
            }
            field.title = 'Double click to edit';
            field.addEventListener('dblclick', (event) => {
                event.preventDefault();
                startPartyInlineEdit(field);
            }, { once: true });
        });
    }

    function bindPartyWhatsappButton(button, feature) {
        if (!button || !feature) {
            return;
        }

        const hasLocation = customerHasLocation(feature);
        const shareText = buildPartyShareText(feature);
        button.href = hasLocation ? `https://wa.me/?text=${encodeURIComponent(shareText)}` : '#';
        button.classList.toggle('is-disabled', !hasLocation);
        button.setAttribute('aria-disabled', hasLocation ? 'false' : 'true');
        if (hasLocation) {
            button.removeAttribute('tabindex');
        } else {
            button.setAttribute('tabindex', '-1');
        }
    }

    function shareButtonsMarkup(feature) {
        const properties = feature?.properties || {};
        const customerId = String(properties.customer_id || '').trim();
        const hasLocation = customerHasLocation(feature);
        const primaryClass = 'search-result-action search-result-action--primary';
        const disabled = hasLocation ? '' : ' is-disabled';
        return `<button type="button" class="${primaryClass}" data-copy-party="${escapeHtml(customerId)}">Copy</button><br><a href="#" class="${primaryClass}${disabled}" data-whatsapp-share="${escapeHtml(customerId)}"${hasLocation ? '' : ' data-share-disabled="1"'}>Hotspot share</a>`;
    }

    function partyInlineUpdateUrl(feature) {
        const properties = feature?.properties || {};
        const raw = String(properties.show_url || '').trim();
        if (!raw) {
            return '';
        }

        return raw.endsWith('/') ? `${raw}inline` : `${raw}/inline`;
    }

    function inlineFieldHtml(field, customerId, inlineUrl, value, emptyText) {
        const cleanedValue = value === null || value === undefined ? '' : String(value);
        const normalized = cleanedValue.trim();
        const displayValue = normalized || emptyText;
        const safeField = escapeHtml(field);
        const safeCustomer = escapeHtml(customerId);
        const safeUrl = escapeHtml(inlineUrl);
        const safeDisplay = escapeHtml(displayValue);
        const safeRaw = escapeHtml(normalized);
        const safeEmpty = escapeHtml(emptyText);

        return inlineUrl
            ? `<span class="inline-edit-field" data-inline-field="${safeField}" data-inline-url="${safeUrl}" data-customer-id="${safeCustomer}" data-inline-value="${safeRaw}" data-inline-empty-text="${safeEmpty}">${safeDisplay}</span>`
            : safeDisplay;
    }

    async function startPartyInlineEdit(fieldElement) {
        const field = fieldElement?.dataset?.inlineField;
        const customerId = fieldElement?.dataset?.customerId;
        const inlineUrl = fieldElement?.dataset?.inlineUrl;
        if (!field || !customerId || !inlineUrl) {
            return;
        }

        const currentValue = fieldElement.dataset.inlineValue || '';
        const emptyText = fieldElement.dataset.inlineEmptyText || 'Not provided';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentValue;
        input.className = 'inline-edit-input';
        input.autocomplete = 'off';
        input.dataset.inlineInput = '1';

        const restore = (value) => {
            const normalized = value === null || value === undefined ? '' : String(value).trim();
            fieldElement.textContent = normalized || emptyText;
            fieldElement.dataset.inlineValue = normalized;
        };

        let isSaving = false;
        const finish = async (shouldSave) => {
            if (isSaving) {
                return;
            }

            isSaving = true;
            const nextValue = String(input.value || '').trim();
            if (!shouldSave || nextValue === currentValue) {
                restore(currentValue);
                return;
            }

            try {
                const response = await fetch(inlineUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({
                        field,
                        value: nextValue,
                    }),
                });

                if (!response.ok) {
                    throw new Error(await responseErrorMessage(response, 'Unable to update this value.'));
                }

                const payload = await response.json();
                const value = payload.value;
                upsertPartyFeatureValue(customerId, field, value);
                restore(value);
            } catch (error) {
                setStatus(error.message);
                restore(currentValue);
            }
        };

        fieldElement.dataset.inlineEditing = '1';
        fieldElement.replaceChildren(input);
        input.focus();
        input.select();

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                finish(true).then(() => {
                    fieldElement.dataset.inlineEditing = '0';
                });
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(false);
                fieldElement.dataset.inlineEditing = '0';
            }
        });
        input.addEventListener('blur', () => {
            finish(true);
            fieldElement.dataset.inlineEditing = '0';
        });
    }

    function upsertPartyFeatureValue(customerId, field, value) {
        const normalized = value === null || value === undefined ? '' : String(value).trim();
        const maps = [state.customers, state.allCustomers];
        maps.forEach((collection) => {
            const feature = collection.get(String(customerId));
            if (!feature?.properties) {
                return;
            }

            const properties = { ...feature.properties };
            if (field === 'connection_id') {
                properties.connection_id = normalized;
                properties.mikrotik_username = normalized;
            } else {
                properties[field] = normalized;
            }

            collection.set(String(customerId), { ...feature, properties });
        });

        const activeFeature = state.customers.get(String(customerId));
        renderPartyLocationSource();
        renderPartyList();
        updatePartyStats();

        if (activeFeature && state.popup) {
            openPartyPopup(activeFeature);
        }
    }

    function findPartyFeature(customerId) {
        const id = String(customerId || '').trim();
        if (!id) {
            return null;
        }

        return state.customers.get(id) || state.allCustomers.get(id) || null;
    }

    function buildPartyMapUrl(feature) {
        const longitude = Number(feature?.geometry?.coordinates?.[0]);
        const latitude = Number(feature?.geometry?.coordinates?.[1]);
        if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) {
            return '';
        }

        return `https://maps.google.com/?q=${latitude.toFixed(8)},${longitude.toFixed(8)}`;
    }

    function buildPartyShareText(feature) {
        const properties = feature?.properties || {};
        const name = formatPartyDisplayName(feature) || 'Not provided';
        const phone = properties.phone || 'Not provided';
        const userId = properties.connection_id || properties.mikrotik_username || 'Not assigned';
        const statusText = formatPartyStatus(properties.status);
        const mapUrl = buildPartyMapUrl(feature);
        const address = properties.address || 'Not provided';
        const customerLabel = formatPartyLabel(feature) || `Party #${properties.customer_id || 'Unknown'}`;

        return [
            customerLabel,
            `Name: ${name}`,
            `Mobile: ${phone}`,
            `User ID: ${userId}`,
            `Address: ${address}`,
            `Active Status: ${statusText}`,
            mapUrl ? `Map location: ${mapUrl}` : 'Map location: not set',
        ].join('\n');
    }

    async function copyPartyShareText(text, triggerButton) {
        if (!text) {
            return;
        }

        const buttonOriginalText = triggerButton?.textContent || '';
        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const fallback = document.createElement('textarea');
            fallback.value = text;
            fallback.setAttribute('readonly', 'readonly');
            fallback.style.position = 'fixed';
            fallback.style.opacity = '0';
            document.body.appendChild(fallback);
            fallback.select();
            document.execCommand('copy');
            fallback.remove();
        }

        if (triggerButton) {
            triggerButton.textContent = 'Copied';
            window.setTimeout(() => {
                triggerButton.textContent = buttonOriginalText || 'Copy';
            }, 1000);
        }
    }

    function loadDefaultView() {
        try {
            const saved = JSON.parse(localStorage.getItem(defaultViewStorageKey) || 'null');
            if (isValidDefaultView(saved)) {
                return saved;
            }
        } catch (error) {
            localStorage.removeItem(defaultViewStorageKey);
        }

        return { ...fallbackDefaultView };
    }

    function saveDefaultView(view) {
        localStorage.setItem(defaultViewStorageKey, JSON.stringify(view));
    }

    function isValidDefaultView(view) {
        return Array.isArray(view?.center)
            && view.center.length === 2
            && Number.isFinite(Number(view.center[0]))
            && Number.isFinite(Number(view.center[1]))
            && Number.isFinite(Number(view.zoom))
            && Number(view.zoom) >= 1
            && Number(view.zoom) <= 22;
    }

    async function responseErrorMessage(response, fallback) {
        try {
            const payload = await response.json();
            if (payload?.message) {
                return payload.message;
            }

            if (typeof payload?.error === 'string') {
                return payload.error;
            }
        } catch (error) {
            // Ignore.
        }

        return fallback;
    }

    function formatPartyDisplayName(customerOrFeature) {
        const properties = customerOrFeature?.properties ?? customerOrFeature ?? {};
        const rawName = String(properties.name || '').trim();
        const id = String(properties.customer_id || '').trim();
        const customerName = String(properties.customer_name || '').trim();

        if (rawName && rawName !== id && !/^\d+$/.test(rawName)) {
            return rawName;
        }

        if (customerName && customerName !== id && !/^\d+$/.test(customerName)) {
            return customerName;
        }

        return '';
    }

    function formatPartyLabel(customerOrFeature) {
        const properties = customerOrFeature?.properties ?? customerOrFeature ?? {};
        const id = String(properties.customer_id || '').trim();
        if (!id) {
            return '';
        }

        return `Party #${id}`;
    }

    function findMatchingCustomers(query) {
        const normalized = String(query || '').trim().toLowerCase();
        if (!normalized) {
            return [...state.allCustomers.values()];
        }

        const fields = (feature) => {
            const properties = feature?.properties || {};
            return [
                String(properties.customer_id || ''),
                String(properties.name || ''),
                String(properties.phone || ''),
                String(properties.connection_id || ''),
                String(properties.mikrotik_username || ''),
                String(properties.address || ''),
                String(properties.comment || ''),
            ];
        };

        return [...state.allCustomers.values()].filter((feature) => fields(feature).some((field) => String(field).toLowerCase().includes(normalized)));
    }

    function formatPartyComment(customerOrFeature) {
        const properties = customerOrFeature?.properties ?? customerOrFeature ?? {};
        return String(properties.comment || '').trim();
    }

    function formatPartyUserName(customerOrFeature) {
        const properties = customerOrFeature?.properties ?? customerOrFeature ?? {};
        return String(properties.connection_id || properties.mikrotik_username || 'Not provided').trim();
    }

    function formatPartyStatus(rawStatus) {
        const status = String(rawStatus || '').trim();
        if (!status) {
            return 'Not provided';
        }

        return status.toLowerCase() === 'inactive'
            ? 'Inactive'
            : status.toLowerCase() === 'deactivated'
            ? 'Inactive'
            : 'Active';
    }

    function formatPartyStatusClass(rawStatus) {
        const normalized = String(rawStatus || '').trim().toLowerCase();
        return normalized === 'active'
            ? 'active'
            : (normalized === 'inactive' || normalized === 'deactivated')
                ? 'inactive'
                : 'inactive';
    }

    function compactJoin(values) {
        return values
            .filter((value) => value !== undefined && value !== null && String(value).trim() !== '')
            .join(' | ');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll('\'', '&#039;');
    }
})();
