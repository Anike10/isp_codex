(function () {
    const config = window.NETWORK_MAP_CONFIG;
    const emptyCollection = () => ({ type: 'FeatureCollection', features: [] });
    const defaultViewStorageKey = 'network-map-default-view-kushtia-v2';
    const fallbackDefaultView = {
        center: [89.1219, 23.9013],
        zoom: 14,
    };
    const visibilityStorageKey = 'network-map-visible-types-v2';
    const hiddenFeaturesStorageKey = 'network-map-hidden-features';
    let endpointDropdownSequence = 0;
    let partySearchDebounce = null;

    const state = {
        map: null,
        activeBasemap: 'google_road',
        activeTool: null,
        activeNodeType: null,
        draftLine: [],
        // Per-vertex customer snap for the fiber tool: same length as draftLine,
        // each entry is {id, name} when that vertex was placed on a customer pin,
        // otherwise null.
        draftLineCustomers: [],
        pathMarkers: [],
        pendingEndpointLink: null,
        draggingNode: null,
        nodeDragJustFinished: false,
        relocatingFeatureId: null,
        hoverPopup: null,
        hoverHideTimer: null,
        placementPreview: null,
        selectedFeatureId: null,
        selectedBendPoint: null,
        linkTargetFeatureId: null,
        corePanel: null,
        oltPanel: null,
        pendingPortLink: null,
        visibleTypes: new Set(['router', 'switch', 'olt', 'splitter', 'tj_box', 'onu', 'fiber_cable']),
        hiddenFeatureIds: new Set(),
        features: new Map(),
        customerFeatures: new Map(),
        customerLocationIndex: new Map(),
        partySearchCustomerIds: new Set(),
        customerPopup: null,
        featurePopup: null,
        pendingPartyLocationCustomerId: null,
        editingFeatureId: null,
        dirty: false,
        history: [],
        historyIndex: -1,
        restoringHistory: false,
    };

    const nodeColors = {
        router: '#d92d20',
        switch: '#175cd3',
        olt: '#7a5af8',
        splitter: '#b54708',
        tj_box: '#027a48',
        onu: '#0086c9',
    };

    const componentLabels = {
        router: 'Router',
        switch: 'Switch',
        olt: 'OLT',
        splitter: 'Splitter',
        tj_box: 'TJ Box',
        onu: 'ONU',
        fiber_cable: 'Fiber Cable',
    };
    const visibilityItems = [
        ['router', 'Routers'],
        ['switch', 'Switches'],
        ['olt', 'OLTs'],
        ['splitter', 'Splitters'],
        ['party_locations', 'Party Locations'],
        ['party_usernames', 'User Names'],
        ['tj_box', 'TJ Boxes'],
        ['onu', 'ONUs'],
        ['fiber_cable', 'Fiber Links'],
    ];

    const corePalette = [
        ['Blue', '#1d4ed8'],
        ['Orange', '#f97316'],
        ['Green', '#16a34a'],
        ['Brown', '#92400e'],
        ['Slate', '#64748b'],
        ['White', '#f8fafc'],
        ['Red', '#dc2626'],
        ['Black', '#111827'],
        ['Yellow', '#eab308'],
        ['Violet', '#7c3aed'],
        ['Rose', '#e11d48'],
        ['Aqua', '#06b6d4'],
    ];
    const ponLinePalette = [
        '#175cd3',
        '#12b76a',
        '#7a5af8',
        '#f79009',
        '#06aed4',
        '#b42318',
        '#0e9384',
        '#93370d',
        '#6938ef',
        '#c11574',
        '#2e90fa',
        '#039855',
    ];
    const multiPonFiberColor = '#d92d20';

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
                'https://a.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                'https://b.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                'https://c.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
            ],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
        dark: {
            label: 'Dark',
            tiles: [
                'https://a.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
                'https://b.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
                'https://c.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
            ],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
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
                'https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                'https://mt2.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
            ],
            attribution: '&copy; Google',
        },
        google_satellite: {
            label: 'Google Satellite',
            tiles: [
                'https://mt0.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                'https://mt2.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
            ],
            attribution: '&copy; Google',
        },
    };

    const formSchemas = {
        router: [
            input('name', 'Name', 'Core Router 01', true),
            input('brand', 'Brand', 'MikroTik'),
            input('ip_address', 'IP Address', '10.10.10.1'),
            input('total_ports', 'Total Ports', '24', false, 'number'),
            input('available_ports', 'Available Ports', '6', false, 'number'),
            ...nodeMetaFields(),
        ],
        switch: [
            input('name', 'Name', 'Distribution Switch 01', true),
            input('brand', 'Brand', 'Cisco'),
            input('ip_address', 'IP Address', '10.10.20.1'),
            input('total_ports', 'Total Ports', '24', false, 'number'),
            input('available_ports', 'Available Ports', '8', false, 'number'),
            ...nodeMetaFields(),
        ],
        olt: [
            input('name', 'Name', 'OLT Central Office', true),
            input('brand', 'Brand', 'Huawei'),
            input('ip_address', 'IP Address', '10.10.30.1'),
            input('total_ports', 'Total Ports', '16', false, 'number'),
            input('available_ports', 'Available Ports', '4', false, 'number'),
            ...nodeMetaFields(),
        ],
        splitter: [
            input('name', 'Name', 'SPLITTER-01', true),
            tjBoxSelect('splitter_parent_tj_box_id', 'Inside TJ Box'),
            select('splitter_type', 'Type', ['1:2', '1:4', '1:8', '1:16'], true),
            input('parent_olt_port', 'Parent OLT/Port', 'OLT-01 PON 1/1'),
            input('splitter_input_fiber_code', 'Input Fiber Code/ID', 'F-CO-OLT-001'),
            color('splitter_input_tube_color', 'Input Tube Color'),
            color('splitter_input_core_color', 'Input Core Color'),
            dynamicMap('splitter_ports', 'Splitter IN/OUT Color Map', 'splitter_port_map'),
            textarea('splitter_output_map', 'Output Port/Core Notes', 'OUT-01 -> Drop cable DC-001, Tube Blue, Core Orange\nOUT-02 -> Drop cable DC-002, Tube Blue, Core Green'),
            textarea('splice_details', 'Splice Details', 'Input core blue spliced to splitter IN; outputs mapped by port.'),
            ...nodeMetaFields(),
        ],
        tj_box: [
            input('box_name', 'Box Name', 'TJ-BOX-041', true),
            input('address', 'Address', 'Road 12, Block C'),
            color('fiber_core_color', 'Fiber Core Color'),
            input('connected_port', 'Connected Port', 'Splitter 1:8 Port 03'),
            ...nodeMetaFields(),
        ],
        onu: [
            input('client_name', 'Client Name', 'Customer Name', true),
            input('address', 'Address', 'House 10, Road 12'),
            color('fiber_core_color', 'Fiber Core Color'),
            input('connected_port', 'Connected Port', 'TJ-BOX-041 Port 02'),
            ...nodeMetaFields(),
        ],
        fiber_cable: [
            input('fiber_code', 'Fiber Code/ID', 'F-OLT-SPL-001', true),
            select('core_count', 'Core Count', ['1F', '2F', '4F', '6F', '12F', '24F'], true),
            select('cable_type', 'Cable Type', ['Overhead', 'Underground'], true),
            input('a_end_device_port', 'A-End Device/Port', 'OLT-01 PON 1/1'),
            color('a_end_tube_color', 'A-End Tube Color'),
            color('a_end_core_color', 'A-End Core Color'),
            input('z_end_device_port', 'Z-End Device/Port', 'Splitter SP-01 IN'),
            color('z_end_tube_color', 'Z-End Tube Color'),
            color('z_end_core_color', 'Z-End Core Color'),
            input('splitter_input_port', 'Splitter Input Port', 'SP-01 IN'),
            input('splitter_output_port', 'Splitter Output Port', 'OUT-01'),
            color('splitter_output_core_color', 'Splitter Output Core Color'),
            input('connected_fiber_code', 'Connected Fiber/Drop Code', 'DC-ONU-001'),
            color('connected_fiber_core_color', 'Connected Fiber Core Color'),
            input('length_meters', 'Length (meters)', 'Auto-calculated', true, 'number', true),
            linkEndpoints('endpoint_links', 'Linked Endpoints'),
            dynamicMap('core_mappings', 'Fiber Core IN/OUT Color Map', 'fiber_core_map'),
            textarea('splice_details', 'Core/Splice Notes', 'A Blue/Blue -> Splitter IN\nSplitter OUT-01 Orange -> Drop DC-ONU-001 Green'),
        ],
    };

    const photoField = photos('photos', 'Photos');

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        if (typeof window.maplibregl === 'undefined') {
            setStatus('Map library could not load. Refresh the page and try again.');
            return;
        }

        const defaultView = loadDefaultView();
        state.visibleTypes = loadVisibleTypes();
        state.hiddenFeatureIds = loadHiddenFeatureIds();

        try {
            state.map = new maplibregl.Map({
                container: 'networkMap',
                style: {
                    version: 8,
                    glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
                    sources: Object.fromEntries(Object.entries(basemaps).map(([key, basemap]) => [
                        `basemap-${key}`,
                        {
                            type: 'raster',
                            tiles: basemap.tiles,
                            tileSize: 256,
                            attribution: basemap.attribution,
                        },
                    ])),
                    layers: Object.keys(basemaps).map((key) => ({
                        id: `basemap-${key}`,
                        type: 'raster',
                        source: `basemap-${key}`,
                        layout: { visibility: key === state.activeBasemap ? 'visible' : 'none' },
                    })),
                },
                center: defaultView.center,
                zoom: defaultView.zoom,
                maxZoom: 22,
                doubleClickZoom: false,
            });

            state.map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
            state.map.on('load', onMapLoad);
            bindUi();
        } catch (error) {
            console.error('Network map initialization failed.', error);
            setStatus(`Map could not start: ${error.message}`);
        }
    }

    function onMapLoad() {
        addNetworkLayers();
        loadTopology();
        loadCustomerLocations();
        updatePartyLocationLayerVisibility();

        state.map.on('click', handleMapClick);
        state.map.on('dblclick', function (event) {
            if (state.activeTool === 'fiber') {
                event.preventDefault();
                finishFiberDraft();
                return;
            }

            if (!handleFeatureDetails(event)) {
                event.preventDefault();
                centerEmptyMapPoint(event.point);
            }
        });
        state.map.on('mouseenter', 'network-nodes-circle', showHoverDetails);
        state.map.on('mouseleave', 'network-nodes-circle', hideHoverDetails);
        state.map.on('mouseenter', 'customer-locations-circle', showCustomerHoverSummary);
        state.map.on('mouseleave', 'customer-locations-circle', hideHoverDetails);
        state.map.on('mouseenter', 'customer-locations-label', showCustomerHoverSummary);
        state.map.on('mouseleave', 'customer-locations-label', hideHoverDetails);
        state.map.on('mouseenter', 'network-links-line-hit', showHoverDetails);
        state.map.on('mouseleave', 'network-links-line-hit', hideHoverDetails);
        state.map.on('mousedown', 'network-nodes-circle', startNodeDrag);
        state.map.on('mousemove', handleMapMouseMove);
        state.map.on('mouseleave', clearPlacementPreview);
        state.map.on('mouseup', finishNodeDrag);
        state.map.getContainer().addEventListener('dragover', (event) => {
            if (state.pendingPortLink) {
                event.preventDefault();
            }
        });
        state.map.getContainer().addEventListener('drop', handlePortDrop);
    }

    function addNetworkLayers() {
        state.map.addSource('network-nodes', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('customer-locations', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('party-search-highlight', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('network-links', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('endpoint-core-links', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('draft-line', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('draft-points', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('selection-highlight', { type: 'geojson', data: emptyCollection() });
        state.map.addSource('link-target-highlight', { type: 'geojson', data: emptyCollection() });

        state.map.addLayer({
            id: 'network-links-line',
            type: 'line',
            source: 'network-links',
            paint: {
                'line-color': ['get', '_map_line_color'],
                'line-width': ['interpolate', ['linear'], ['zoom'], 10, 2, 15, 5],
                'line-opacity': 0.9,
                'line-offset': ['coalesce', ['get', '_map_line_offset'], 0],
            },
        });

        state.map.addLayer({
            id: 'network-links-line-hit',
            type: 'line',
            source: 'network-links',
            paint: {
                'line-color': '#000',
                'line-width': 18,
                'line-opacity': 0,
                'line-offset': ['coalesce', ['get', '_map_line_offset'], 0],
            },
        });

        state.map.addLayer({
            id: 'endpoint-core-links-line',
            type: 'line',
            source: 'endpoint-core-links',
            paint: {
                'line-color': ['coalesce', ['get', 'color_hex'], '#f79009'],
                'line-width': ['interpolate', ['linear'], ['zoom'], 10, 2, 16, 4],
                'line-opacity': 0.95,
                'line-dasharray': [1.2, 1],
                'line-offset': ['coalesce', ['get', '_map_line_offset'], 0],
            },
        });

        state.map.addLayer({
            id: 'selection-link-highlight',
            type: 'line',
            source: 'selection-highlight',
            filter: ['==', ['geometry-type'], 'LineString'],
            paint: {
                'line-color': '#fdb022',
                'line-width': ['interpolate', ['linear'], ['zoom'], 10, 6, 15, 10],
                'line-opacity': 0.9,
            },
        });

        state.map.addLayer({
            id: 'draft-line-layer',
            type: 'line',
            source: 'draft-line',
            paint: {
                'line-color': '#f79009',
                'line-width': 4,
                'line-dasharray': [1.5, 1],
            },
        });

        state.map.addLayer({
            id: 'draft-points-layer',
            type: 'circle',
            source: 'draft-points',
            paint: {
                'circle-radius': ['case', ['==', ['get', 'point_index'], 1], 8, 6],
                'circle-color': ['case', ['==', ['get', 'point_index'], 1], '#12b76a', '#f79009'],
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 3,
                'circle-opacity': 0.95,
            },
        });

        state.map.addLayer({
            id: 'network-nodes-circle',
            type: 'circle',
            source: 'network-nodes',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 7, 16, 12],
                'circle-color': [
                    'match',
                    ['get', 'component_type'],
                    'router', nodeColors.router,
                    'switch', nodeColors.switch,
                    'olt', nodeColors.olt,
                    'splitter', nodeColors.splitter,
                    'tj_box', nodeColors.tj_box,
                    'onu', nodeColors.onu,
                    '#667085',
                ],
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 2,
            },
        });

        state.map.addLayer({
            id: 'selection-node-highlight',
            type: 'circle',
            source: 'selection-highlight',
            filter: ['==', ['geometry-type'], 'Point'],
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 13, 16, 19],
                'circle-color': 'rgba(253, 176, 34, .24)',
                'circle-stroke-color': '#fdb022',
                'circle-stroke-width': 4,
            },
        });

        state.map.addLayer({
            id: 'link-target-highlight',
            type: 'circle',
            source: 'link-target-highlight',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 17, 16, 24],
                'circle-color': 'rgba(18, 183, 106, .22)',
                'circle-stroke-color': '#12b76a',
                'circle-stroke-width': 5,
            },
        });

        state.map.addLayer({
            id: 'network-nodes-label',
            type: 'symbol',
            source: 'network-nodes',
            layout: {
                'text-field': ['coalesce', ['get', 'name'], ['get', 'box_name'], ['get', 'client_name'], ['get', 'component_type']],
                'text-size': 12,
                'text-offset': [0, 1.35],
                'text-anchor': 'top',
                'text-allow-overlap': false,
            },
            paint: {
                'text-color': '#172033',
                'text-halo-color': '#ffffff',
                'text-halo-width': 1.5,
            },
        });

        state.map.addLayer({
            id: 'customer-locations-halo',
            type: 'circle',
            source: 'customer-locations',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 9, 16, 16],
                'circle-color': 'rgba(15, 118, 110, .2)',
                'circle-stroke-color': 'rgba(15, 118, 110, .36)',
                'circle-stroke-width': 2,
            },
        });

        state.map.addLayer({
            id: 'customer-locations-circle',
            type: 'circle',
            source: 'customer-locations',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 5, 16, 9],
                'circle-color': ['case',
                    ['==', ['get', 'fiber_removal_pending'], true], '#f04438',
                    ['==', ['get', 'status'], 'active'], '#0f766e',
                    '#667085'],
                'circle-stroke-color': ['case', ['==', ['get', 'fiber_removal_pending'], true], '#7a271a', '#ffffff'],
                'circle-stroke-width': 2,
            },
        });

        state.map.addLayer({
            id: 'customer-locations-label',
            type: 'symbol',
            source: 'customer-locations',
            layout: {
                'text-field': ['coalesce', ['get', 'connection_id'], ['get', 'mikrotik_username'], ['get', 'name'], ['concat', 'Party #', ['to-string', ['get', 'customer_id']]]],
                'text-font': ['Open Sans Semibold'],
                'text-size': ['interpolate', ['linear'], ['zoom'], 10, 10, 16, 13],
                'text-anchor': 'center',
                'text-allow-overlap': true,
                'text-ignore-placement': true,
            },
            paint: {
                'text-color': ['case',
                    ['==', ['get', 'fiber_removal_pending'], true], '#b42318',
                    ['==', ['get', 'status'], 'active'], '#075f56',
                    '#475467'],
                'text-halo-color': '#ffffff',
                'text-halo-width': 3,
            },
        });

        state.map.addLayer({
            id: 'party-search-highlight-ring',
            type: 'circle',
            source: 'party-search-highlight',
            paint: {
                'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 14, 16, 22],
                'circle-color': 'rgba(247, 144, 9, .2)',
                'circle-stroke-color': '#f04438',
                'circle-stroke-width': ['interpolate', ['linear'], ['zoom'], 10, 4, 16, 6],
                'circle-opacity': 0.96,
            },
        });
    }

    function bindUi() {
        document.getElementById('unmappedPartyFilter').addEventListener('input', renderUnmappedPartyList);

        document.querySelectorAll('.basemap-tool').forEach((button) => {
            button.addEventListener('click', function () {
                setBasemap(button.dataset.basemap);
            });
        });

        document.querySelectorAll('.map-tool').forEach((button) => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.map-tool').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.activeTool = button.dataset.tool;
                state.activeNodeType = button.dataset.nodeType || null;
                state.draftLine = [];
                state.draftLineCustomers = [];
                clearPathMarkers();
                updateDraftLine();
                updatePlacementCursor();
                setStatus(state.activeTool === 'fiber'
                    ? 'Fiber draw mode: click route vertices, then double-click or press Enter.'
                    : `Node draw mode: click the map to place a ${componentLabels[state.activeNodeType]}.`);
            });
        });

        document.getElementById('cancelDraft').addEventListener('click', cancelDrawing);
        document.getElementById('saveTopology').addEventListener('click', persistTopology);
        document.getElementById('undoTopology')?.addEventListener('click', undoTopology);
        document.getElementById('redoTopology')?.addEventListener('click', redoTopology);
        document.getElementById('exportGeojson').addEventListener('click', toggleGeoJsonPreview);
        document.getElementById('customerSearch').addEventListener('submit', searchCustomer);
        document.getElementById('customerIdQuery').addEventListener('input', (event) => {
            window.clearTimeout(partySearchDebounce);
            if (!event.currentTarget.value.trim()) {
                clearPartySearchSelection();
                return;
            }

            partySearchDebounce = window.setTimeout(() => {
                document.getElementById('customerSearch').requestSubmit();
            }, 300);
        });
        document.getElementById('locationSearch').addEventListener('submit', searchLocation);
        document.getElementById('defaultViewForm').addEventListener('submit', applyDefaultViewForm);
        document.getElementById('useCurrentView').addEventListener('click', saveCurrentDefaultView);
        document.getElementById('startPartyPlacementBtn').addEventListener('click', () => {
            const panel = document.getElementById('partyPlacementPanel');
            const customerId = panel?.dataset.customerId;
            if (customerId) {
                requestPartyLocationPlacement(customerId);
            }
        });
        document.getElementById('cancelPartyPlacementBtn').addEventListener('click', cancelPartyLocationPlacement);
        syncDefaultViewInputs(loadDefaultView());
        renderVisibilityControls();
        document.getElementById('showAllMapItems').addEventListener('click', () => setAllMapVisibility(true));
        document.getElementById('hideAllMapItems').addEventListener('click', () => setAllMapVisibility(false));
        document.getElementById('closeFeatureForm').addEventListener('click', closeFeatureForm);
        document.getElementById('deleteFeature').addEventListener('click', deleteCurrentFeature);
        document.getElementById('featureForm').addEventListener('submit', saveFeatureForm);

        document.addEventListener('keydown', function (event) {
            if (isTypingTarget(event.target)) {
                return;
            }

            if ((event.ctrlKey || event.metaKey) && !event.altKey) {
                const key = event.key.toLowerCase();
                if (key === 'z' && !event.shiftKey) {
                    event.preventDefault();
                    undoTopology();
                    return;
                }
                if (key === 'y' || (key === 'z' && event.shiftKey)) {
                    event.preventDefault();
                    redoTopology();
                    return;
                }
            }

            if (event.key === 'Escape') {
                closeFiberCorePanel();
                closeOltPortPanel();
                closeFeatureForm();
                cancelDrawing();
                if (state.pendingPartyLocationCustomerId) {
                    cancelPartyLocationPlacement();
                }
            }
            if (event.key === 'Backspace' || event.key === 'Delete') {
                event.preventDefault();
                deleteSelectedMapItem();
            }
            if (event.key === 'Enter' && state.activeTool === 'fiber' && state.draftLine.length >= 2) {
                event.preventDefault();
                finishFiberDraft();
            }
        });
    }

    function setBasemap(basemapKey) {
        if (!basemaps[basemapKey] || !state.map?.isStyleLoaded()) return;

        state.activeBasemap = basemapKey;
        Object.keys(basemaps).forEach((key) => {
            state.map.setLayoutProperty(
                `basemap-${key}`,
                'visibility',
                key === basemapKey ? 'visible' : 'none'
            );
        });

        document.querySelectorAll('.basemap-tool').forEach((button) => {
            button.classList.toggle('active', button.dataset.basemap === basemapKey);
        });
        setStatus(`${basemaps[basemapKey].label} map selected.`);
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

        return { ...fallbackDefaultView, center: [...fallbackDefaultView.center] };
    }

    function isValidDefaultView(view) {
        return Array.isArray(view?.center)
            && view.center.length === 2
            && Number.isFinite(Number(view.center[0]))
            && Number(view.center[0]) >= -180
            && Number(view.center[0]) <= 180
            && Number.isFinite(Number(view.center[1]))
            && Number(view.center[1]) >= -90
            && Number(view.center[1]) <= 90
            && Number.isFinite(Number(view.zoom))
            && Number(view.zoom) >= 1
            && Number(view.zoom) <= 22;
    }

    function syncDefaultViewInputs(view) {
        document.getElementById('defaultLng').value = Number(view.center[0]).toFixed(6);
        document.getElementById('defaultLat').value = Number(view.center[1]).toFixed(6);
        document.getElementById('defaultZoom').value = Number(view.zoom).toFixed(1);
    }

    function readDefaultViewInputs() {
        const lat = Number(document.getElementById('defaultLat').value);
        const lng = Number(document.getElementById('defaultLng').value);
        const zoom = Number(document.getElementById('defaultZoom').value);

        if (!Number.isFinite(lat) || lat < -90 || lat > 90) {
            throw new Error('Latitude must be between -90 and 90.');
        }
        if (!Number.isFinite(lng) || lng < -180 || lng > 180) {
            throw new Error('Longitude must be between -180 and 180.');
        }
        if (!Number.isFinite(zoom) || zoom < 1 || zoom > 22) {
            throw new Error('Zoom must be between 1 and 22.');
        }

        return { center: [lng, lat], zoom };
    }

    function saveDefaultView(view) {
        localStorage.setItem(defaultViewStorageKey, JSON.stringify(view));
        syncDefaultViewInputs(view);
    }

    function applyDefaultViewForm(event) {
        event.preventDefault();

        try {
            const view = readDefaultViewInputs();
            saveDefaultView(view);
            state.map.flyTo({ center: view.center, zoom: view.zoom, duration: 650 });
            setStatus('Default map view saved and applied.');
        } catch (error) {
            setStatus(error.message);
        }
    }

    function saveCurrentDefaultView() {
        const center = state.map.getCenter();
        const view = {
            center: [center.lng, center.lat],
            zoom: state.map.getZoom(),
        };

        saveDefaultView(view);
        setStatus('Current map position saved as default view.');
    }

        function loadVisibleTypes() {
        try {
            const saved = JSON.parse(localStorage.getItem(visibilityStorageKey) || 'null');
            if (Array.isArray(saved)) {
                const allowed = new Set(visibilityItems.map(([type]) => type));
                const normalized = new Set(saved.filter((type) => allowed.has(type)));
                if (normalized.size === 0) {
                    return normalized;
                }

                return normalized;
            }
        } catch (error) {
            localStorage.removeItem(visibilityStorageKey);
        }

        return new Set(visibilityItems.map(([type]) => type));
    }

    function loadHiddenFeatureIds() {
        try {
            const saved = JSON.parse(localStorage.getItem(hiddenFeaturesStorageKey) || 'null');
            if (Array.isArray(saved)) {
                return new Set(saved.map(String));
            }
        } catch (error) {
            localStorage.removeItem(hiddenFeaturesStorageKey);
        }

        return new Set();
    }

    function renderVisibilityControls(counts = {}, features = [...state.features.values()]) {
        const container = document.getElementById('visibilityControls');
        const openTypes = new Set([...container.querySelectorAll('.visibility-group[open]')].map((group) => group.dataset.visibilityGroup));
        container.innerHTML = visibilityItems.map(([type, label]) => {
            const isPartyLayer = type === 'party_locations' || type === 'party_usernames';
            const typeFeatures = isPartyLayer ? [] : features
                .filter((feature) => featureVisibilityType(feature) === type)
                .sort((first, second) => featureDisplayName(first).localeCompare(featureDisplayName(second)));
            const detailsMarkup = isPartyLayer
                ? `<div class="visibility-empty">${type === 'party_usernames' ? 'Show or hide User Names independently.' : 'Show or hide party location dots independently.'}</div>`
                : `${typeFeatures.length ? typeFeatures.map((feature) => visibilityFeatureOption(feature)).join('') : '<div class="visibility-empty">No items yet.</div>'}`;

            return `
                <details class="visibility-group" data-visibility-group="${escapeHtml(type)}" ${openTypes.has(type) ? 'open' : ''}>
                    <summary class="visibility-option">
                        <input type="checkbox" value="${escapeHtml(type)}" data-type-visibility="${escapeHtml(type)}" ${state.visibleTypes.has(type) ? 'checked' : ''}>
                        <span>${escapeHtml(label)}</span>
                        <strong data-visibility-count="${escapeHtml(type)}">${escapeHtml(String(counts[type] || 0))}</strong>
                    </summary>
                    <div class="visibility-details">
                        ${detailsMarkup}
                    </div>
                </details>
            `;
        }).join('');

        container.querySelectorAll('[data-type-visibility]').forEach((inputElement) => {
            inputElement.addEventListener('click', (event) => event.stopPropagation());
            inputElement.addEventListener('change', () => {
                if (inputElement.checked) {
                    state.visibleTypes.add(inputElement.value);
                } else {
                    state.visibleTypes.delete(inputElement.value);
                }

                persistVisibleTypes();
                clearHiddenSelection();
                refreshSources();
                updatePartyLocationLayerVisibility();
                setStatus('Map visibility updated.');
            });
        });

        container.querySelectorAll('[data-feature-visibility]').forEach((inputElement) => {
            inputElement.addEventListener('change', () => {
                if (inputElement.checked) {
                    state.hiddenFeatureIds.delete(inputElement.value);
                } else {
                    state.hiddenFeatureIds.add(inputElement.value);
                }

                persistHiddenFeatureIds();
                clearHiddenSelection();
                refreshSources();
                setStatus('Item visibility updated.');
            });
        });

        container.querySelectorAll('[data-visibility-select]').forEach((button) => {
            button.addEventListener('click', () => {
                selectVisibilityFeature(button.dataset.visibilitySelect);
            });
        });

        container.querySelectorAll('[data-visibility-delete]').forEach((button) => {
            button.addEventListener('click', () => {
                deleteVisibilityFeature(button.dataset.visibilityDelete);
            });
        });

        updatePartyLocationLayerVisibility();
    }

    function visibilityFeatureOption(feature) {
        const featureId = String(feature.properties.id || feature.id);
        return `
            <div class="visibility-feature-option">
                <label>
                    <input type="checkbox" value="${escapeHtml(featureId)}" data-feature-visibility ${state.hiddenFeatureIds.has(featureId) ? '' : 'checked'}>
                    <span>${escapeHtml(featureDisplayName(feature))}</span>
                </label>
                <button type="button" data-visibility-select="${escapeHtml(featureId)}">Select</button>
                <button type="button" data-visibility-delete="${escapeHtml(featureId)}">Delete</button>
            </div>
        `;
    }

    function selectVisibilityFeature(featureId) {
        const feature = state.features.get(featureId);
        if (!feature) return;

        state.hiddenFeatureIds.delete(featureId);
        state.visibleTypes.add(featureVisibilityType(feature));
        persistHiddenFeatureIds();
        persistVisibleTypes();
        refreshSources();
        openExistingFeature(featureId);
        setStatus(`${featureDisplayName(feature)} selected.`);
    }

    function deleteVisibilityFeature(featureId) {
        const feature = state.features.get(featureId);
        if (!feature) return;

        const blockedReason = deleteBlockedReason(feature);
        if (blockedReason) {
            setStatus(blockedReason);
            return;
        }

        deleteFeatureById(featureId);
    }

    function featureDisplayName(feature) {
        const props = feature.properties || {};
        return props.fiber_code
            || props.box_name
            || props.client_name
            || props.name
            || props.address
            || componentLabels[featureVisibilityType(feature)]
            || 'Map item';
    }

    function updateVisibilityCounts(counts) {
        visibilityItems.forEach(([type]) => {
            const element = document.querySelector(`[data-visibility-count="${type}"]`);
            if (element) {
                element.textContent = counts[type] || 0;
            }
        });
    }

    function setAllMapVisibility(visible) {
        state.visibleTypes = visible
            ? new Set(visibilityItems.map(([type]) => type))
            : new Set();
        state.hiddenFeatureIds = visible
            ? new Set()
            : new Set([...state.features.values()].map((feature) => String(feature.properties.id || feature.id)));
        persistVisibleTypes();
        persistHiddenFeatureIds();
        document.querySelectorAll('#visibilityControls input[type="checkbox"]').forEach((inputElement) => {
            inputElement.checked = visible;
        });
        clearHiddenSelection();
        refreshSources();
        updatePartyLocationLayerVisibility();
        setStatus(visible ? 'All map items shown.' : 'All map items hidden.');
    }

    function persistVisibleTypes() {
        localStorage.setItem(visibilityStorageKey, JSON.stringify([...state.visibleTypes]));
    }

    function persistHiddenFeatureIds() {
        localStorage.setItem(hiddenFeaturesStorageKey, JSON.stringify([...state.hiddenFeatureIds]));
    }

    function featureVisibilityType(feature) {
        return feature.properties.component_type || (feature.geometry.type === 'LineString' ? 'fiber_cable' : '');
    }

    function isFeatureVisible(feature) {
        const featureId = String(feature.properties.id || feature.id);
        return state.visibleTypes.has(featureVisibilityType(feature)) && !state.hiddenFeatureIds.has(featureId);
    }

    function updatePartyLocationLayerVisibility() {
        const dotVisibility = state.visibleTypes.has('party_locations') ? 'visible' : 'none';
        const usernameVisibility = state.visibleTypes.has('party_usernames') ? 'visible' : 'none';
        ['customer-locations-halo', 'customer-locations-circle'].forEach((layerId) => {
            if (state.map?.getLayer(layerId)) {
                state.map.setLayoutProperty(layerId, 'visibility', dotVisibility);
            }
        });

        if (state.map?.getLayer('customer-locations-label')) {
            state.map.setLayoutProperty('customer-locations-label', 'visibility', usernameVisibility);
        }
    }

    function withMapLineColor(feature) {
        return {
            ...feature,
            properties: {
                ...feature.properties,
                _map_line_color: fiberLineColor(feature),
            },
        };
    }

    function fiberLineColor(feature) {
        const completedCore = buildFiberCoreRowsForFeature(feature)
            .filter((row) => row.in_point && row.out_point && row.color_hex)
            .sort((first, second) => Number(first.core) - Number(second.core))[0];
        if (completedCore) {
            return completedCore.color_hex;
        }

        const ponPorts = fiberPonPorts(feature);
        if (ponPorts.length > 1) {
            return multiPonFiberColor;
        }

        if (ponPorts.length === 1) {
            return ponLinePalette[stableStringIndex(ponPorts[0], ponLinePalette.length)];
        }

        return feature.properties.cable_type === 'Underground' ? '#7f56d9' : '#12b76a';
    }

    function fiberPonPorts(feature) {
        const props = feature.properties || {};
        const values = [
            props.a_end_device_port,
            props.z_end_device_port,
            props.splitter_input_port,
            props.splitter_output_port,
            props.splice_details,
            props.parent_olt_port,
        ];

        (props.core_mappings || []).forEach((row) => {
            values.push(row.in_point, row.out_point, row.note);
        });

        return [...new Set(values.flatMap(extractPonPorts))];
    }

    function extractPonPorts(value) {
        const text = String(value || '').toUpperCase();
        const matches = text.match(/\bPON[\s:#-]*[A-Z0-9/.-]+/g) || [];

        return matches.map((match) => match.replace(/\s+/g, ' ').trim());
    }

    function stableStringIndex(value, size) {
        let hash = 0;
        for (let index = 0; index < value.length; index++) {
            hash = ((hash << 5) - hash + value.charCodeAt(index)) | 0;
        }

        return Math.abs(hash) % size;
    }

    function clearHiddenSelection() {
        const selected = state.selectedFeatureId ? state.features.get(state.selectedFeatureId) : null;
        if (selected && !isFeatureVisible(selected)) {
            clearSelection();
            clearPathMarkers();
        }
    }

    async function searchLocation(event) {
        event.preventDefault();

        const queryInput = document.getElementById('locationQuery');
        const resultsPanel = document.getElementById('searchResults');
        const query = queryInput.value.trim();

        if (!query) {
            return;
        }

        resultsPanel.hidden = false;
        resultsPanel.innerHTML = '<div class="search-empty">Searching...</div>';
        setStatus(`Searching location: ${query}`);

        try {
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.searchParams.set('format', 'jsonv2');
            url.searchParams.set('limit', '6');
            url.searchParams.set('countrycodes', 'bd');
            url.searchParams.set('q', query);

            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Location search failed.');
            }

            const results = await response.json();
            if (!results.length) {
                resultsPanel.innerHTML = '<div class="search-empty">No location found.</div>';
                setStatus('No location found.');
                return;
            }

            resultsPanel.innerHTML = results.map((result, index) => `
                <button type="button" class="search-result" data-result-index="${index}">
                    <strong>${escapeHtml(result.name || result.display_name.split(',')[0])}</strong>
                    <span>${escapeHtml(result.display_name)}</span>
                </button>
            `).join('');

            resultsPanel.querySelectorAll('.search-result').forEach((button) => {
                button.addEventListener('click', () => {
                    const result = results[Number(button.dataset.resultIndex)];
                    flyToSearchResult(result);
                    resultsPanel.hidden = true;
                });
            });

            flyToSearchResult(results[0]);
        } catch (error) {
            resultsPanel.innerHTML = `<div class="search-empty">${escapeHtml(error.message)}</div>`;
            setStatus(error.message);
        }
    }

    function flyToSearchResult(result) {
        const lng = Number(result.lon);
        const lat = Number(result.lat);
        if (!Number.isFinite(lng) || !Number.isFinite(lat)) {
            return;
        }

        state.map.flyTo({
            center: [lng, lat],
            zoom: Math.max(state.map.getZoom(), 15),
            duration: 850,
        });
        setStatus(`Location selected: ${result.display_name}`);
    }

    async function loadCustomerLocations() {
        try {
            const response = await fetch(config.customersUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(await responseErrorMessage(response, 'Unable to load party locations.'));

            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
            if (!contentType.includes('application/json')) {
                const body = await response.text().catch(() => '');
                if (body.toLowerCase().includes('<html') && body.toLowerCase().includes('login')) {
                    throw new Error('Session expired. Please sign in again to load parties.');
                }

                throw new Error('Invalid response from customers API.');
            }

            const collection = await response.json();
            const customerFeatures = Array.isArray(collection.features) ? collection.features : [];
            state.customerFeatures = new Map(customerFeatures.map((feature) => [String(feature.properties.customer_id), feature]));
            rebuildCustomerLocationSource();
            renderUnmappedPartyList();
            refreshStatsFromData();

            if (config.initialCustomerId) {
                focusCustomer(String(config.initialCustomerId));
            } else {
                document.getElementById('customerSearchResult').hidden = true;
            }
        } catch (error) {
            setCustomerSearchResult(error.message, true);
        }
    }

    function getPartySearchTokens(feature) {
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
    }

    function matchesPartySearch(feature, query) {
        const normalized = String(query || '').trim().toLowerCase();
        if (!normalized) {
            return true;
        }

        return getPartySearchTokens(feature).some((value) => value.toLowerCase().includes(normalized));
    }

    function searchPartyFeaturesLocally(query) {
        const all = [...state.customerFeatures.values()];
        if (!query || !all.length) {
            return all;
        }

        return all.filter((feature) => matchesPartySearch(feature, query));
    }

    function uniquePartyMatches(matches) {
        return matches.filter((feature, index, all) => {
            return index === all.findIndex((item) => String(item.properties.customer_id) === String(feature.properties.customer_id));
        });
    }

    function mappedPartyMatches(matches) {
        return uniquePartyMatches(matches).filter((feature) => customerHasLocation(feature));
    }

    function refreshPartySearchHighlightSource() {
        const features = [...state.partySearchCustomerIds]
            .map((customerId) => state.customerFeatures.get(String(customerId)))
            .filter((feature) => feature && customerHasLocation(feature));

        setSourceData('party-search-highlight', {
            type: 'FeatureCollection',
            features,
        });

        return features;
    }

    function setPartySearchHighlights(matches) {
        state.partySearchCustomerIds = new Set(uniquePartyMatches(matches)
            .map((feature) => String(feature?.properties?.customer_id || ''))
            .filter(Boolean));

        return refreshPartySearchHighlightSource();
    }

    function clearPartySearchSelection() {
        state.partySearchCustomerIds.clear();
        setSourceData('party-search-highlight', emptyCollection());

        const resultPanel = document.getElementById('customerSearchResult');
        if (resultPanel) {
            resultPanel.hidden = true;
            resultPanel.innerHTML = '';
        }

        if (state.customerPopup) {
            state.customerPopup.remove();
            state.customerPopup = null;
        }

        const url = new URL(window.location.href);
        url.searchParams.delete('customer_id');
        window.history.replaceState({}, '', url);
    }

    function framePartySearchMatches(matches) {
        const locatedMatches = mappedPartyMatches(matches);
        if (!state.map || locatedMatches.length === 0) {
            return locatedMatches;
        }

        if (locatedMatches.length === 1) {
            const feature = locatedMatches[0];
            state.map.flyTo({
                center: feature.geometry.coordinates,
                zoom: Math.max(state.map.getZoom(), 17),
                duration: 850,
            });
            showCustomerPopup(feature);

            const url = new URL(window.location.href);
            url.searchParams.set('customer_id', feature.properties.customer_id);
            window.history.replaceState({}, '', url);
            return locatedMatches;
        }

        if (state.customerPopup) {
            state.customerPopup.remove();
            state.customerPopup = null;
        }

        const coordinates = locatedMatches.map((feature) => feature.geometry.coordinates);
        const first = coordinates[0];
        const allAtSamePoint = coordinates.every((coordinate) => coordinate[0] === first[0] && coordinate[1] === first[1]);
        if (allAtSamePoint) {
            state.map.flyTo({ center: first, zoom: Math.max(state.map.getZoom(), 17), duration: 850 });
        } else {
            const bounds = new maplibregl.LngLatBounds(first, first);
            coordinates.slice(1).forEach((coordinate) => bounds.extend(coordinate));
            const wideLayout = state.map.getContainer().clientWidth > 980;
            state.map.fitBounds(bounds, {
                padding: {
                    top: 96,
                    right: 48,
                    bottom: 72,
                    left: wideLayout ? 372 : 48,
                },
                maxZoom: 17,
                duration: 850,
            });
        }

        const url = new URL(window.location.href);
        url.searchParams.delete('customer_id');
        window.history.replaceState({}, '', url);
        return locatedMatches;
    }

    function applyPartySearchMatches(matches, query) {
        const uniqueMatches = uniquePartyMatches(matches);
        renderPartySearchResults(uniqueMatches);
        setPartySearchHighlights(uniqueMatches);
        const locatedMatches = framePartySearchMatches(uniqueMatches);

        if (uniqueMatches.length === 1 && locatedMatches.length === 1) {
            setStatus(`1 party found for "${query}" and highlighted on the map.`);
            return;
        }

        if (uniqueMatches.length === 1) {
            setStatus(`${formatPartyLabel(uniqueMatches[0]) || query} has no saved map location.`);
            return;
        }

        const missingCount = uniqueMatches.length - locatedMatches.length;
        setStatus(`${uniqueMatches.length} parties found; ${locatedMatches.length} highlighted on the map${missingCount ? `, ${missingCount} without a saved location` : ''}.`);
    }

    async function searchCustomer(event) {
        event.preventDefault();
        window.clearTimeout(partySearchDebounce);
        const input = document.getElementById('customerIdQuery');
        const query = input.value.trim();

        if (!query) {
            clearPartySearchSelection();
            setStatus('Type part of a party name to search.');
            return;
        }

        const resultPanel = document.getElementById('customerSearchResult');
        resultPanel.hidden = false;
        resultPanel.innerHTML = '<div class="search-empty">Searching parties...</div>';
        setStatus(`Searching party: ${query}`);

        const cachedMatches = searchPartyFeaturesLocally(query);
        if (cachedMatches.length > 0) {
            applyPartySearchMatches(cachedMatches, query);
            return;
        }

        try {
            const matches = await searchPartiesByQuery(query, 'q');

            if (matches.length > 0) {
                cacheCustomerSearchMatches(matches);
                applyPartySearchMatches(matches, query);
                return;
            }

            setPartySearchHighlights([]);
            setCustomerSearchResult(`No party found for "${query}".`, true);
            setStatus('No party found.');
        } catch (error) {
            const localFallback = searchPartyFeaturesLocally(query);
            if (localFallback.length) {
                applyPartySearchMatches(localFallback, query);
                return;
            }

            if (!/^\d+$/.test(query) && /numeric party id/i.test(error.message || '')) {
                try {
                    const fallbackMatches = await searchPartiesByQuery(query, 'customer_id');
                    if (fallbackMatches.length) {
                        cacheCustomerSearchMatches(fallbackMatches);
                        applyPartySearchMatches(fallbackMatches, query);
                        return;
                    }
                } catch (_) {
                    // keep original error below
                }
            }

            setPartySearchHighlights([]);
            resultPanel.innerHTML = `<div class="search-empty">${escapeHtml(error.message)}</div>`;
            setStatus(error.message);
        }
    }

    async function searchPartiesByQuery(query, paramName = 'q') {
        const url = new URL(config.customersUrl);
        const trimmed = (query || '').trim();
        const canSearch = trimmed.length > 0;

        if (canSearch) {
            url.searchParams.set(paramName, trimmed);
        } else {
            url.searchParams.delete(paramName);
        }

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(await responseErrorMessage(response, 'Unable to search party.'));
        }

        const result = await response.json();
        return Array.isArray(result.features) ? result.features : [];
    }

    function cacheCustomerSearchMatches(matches) {
        matches.forEach((feature) => {
            const customerId = feature?.properties?.customer_id;
            if (!customerId) {
                return;
            }

            upsertPartyFeature(feature);
        });
    }

    function renderPartySearchResults(matches) {
        const resultsPanel = document.getElementById('customerSearchResult');
        const uniqueMatches = uniquePartyMatches(matches);
        if (!uniqueMatches.length) {
            resultsPanel.innerHTML = '<div class="search-empty">No matching party found.</div>';
            resultsPanel.hidden = false;
            return;
        }

        const listHtml = uniqueMatches
            .slice(0, 60)
            .map((feature) => {
                const id = String(feature?.properties?.customer_id || '');
                const userName = formatPartyUserName(feature);
                const deleted = feature?.properties?.deleted;
                return `<button type="button" class="party-username-result" ${deleted ? 'data-deleted="1"' : ''} data-focus-customer="${escapeHtml(id)}">${escapeHtml(userName)}${deleted ? ' <span class="party-deleted-tag">deleted</span>' : ''}</button>`;
            })
            .join('');

        const hasMore = uniqueMatches.length > 60;
        resultsPanel.innerHTML = `<div class="party-username-list">${listHtml}</div>${hasMore ? `<div class="search-empty">Showing first 60 of ${uniqueMatches.length} user names.</div>` : ''}`;
        resultsPanel.hidden = false;

        resultsPanel.querySelectorAll('[data-focus-customer]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const customerId = button.dataset.focusCustomer;
                const feature = state.customerFeatures.get(String(customerId));
                setPartySearchInput(formatPartyUserName(feature));
                resultsPanel.hidden = true;
                focusCustomer(customerId, { preserveInput: true });
            });
        });
    }

    function renderPartyNoLocationPrompt(feature) {
        const userName = formatPartyUserName(feature);

        const resultsPanel = document.getElementById('customerSearchResult');
        if (!resultsPanel) {
            return;
        }

        resultsPanel.innerHTML = `
            <div class="party-username-list"><span class="party-username-result is-static">${escapeHtml(userName)}</span></div>
            <div class="search-empty">No saved map location.</div>
        `;
        resultsPanel.hidden = false;
    }

    function focusCustomer(customerId, options = {}) {
        const feature = state.customerFeatures.get(String(customerId));
        if (!options.preserveInput) {
            setPartySearchInput(customerId);
        }
        if (!feature || !customerHasLocation(feature)) {
            if (feature) {
                renderPartyNoLocationPrompt(feature);
            } else {
                setCustomerSearchResult('Selected party has no saved map location.', true);
            }
            return false;
        }

        const label = formatPartyLabel(feature);
        const userName = formatPartyUserName(feature);
        setPartySearchHighlights([feature]);
        state.map.flyTo({
            center: feature.geometry.coordinates,
            zoom: Math.max(state.map.getZoom(), 17),
            duration: 850,
        });
        showCustomerPopup(feature);
        setCustomerSearchResult(`Showing ${userName}.`);
        setStatus(`${label} selected on the map.`);

        const url = new URL(window.location.href);
        url.searchParams.set('customer_id', customerId);
        window.history.replaceState({}, '', url);
        return true;
    }

    function showCustomerPopup(feature) {
        hideHoverDetails();
        const properties = feature.properties || {};
        const siblingPorts = customerSiblingsAtLocation(feature.geometry?.coordinates || []);
        const canEdit = customerHasLocation(feature);
        const displayName = formatPartyDisplayName(properties);
        const userName = formatPartyUserName(properties);
        const statusText = properties.deleted ? 'Deleted' : formatPartyStatus(properties.status);
        const statusClass = properties.deleted ? 'inactive' : formatPartyStatusClass(properties.status);
        const inlineUpdateUrl = mapPartyInlineUpdateUrl(feature);
        const shareControls = mapPartyShareButtonsMarkup(feature);
        const userId = properties.connection_id || properties.mikrotik_username || '';
        const siblingSummary = siblingPorts.length > 1
            ? siblingPorts.map((item, index) => {
                return `<div>${escapeHtml(`Port ${String(index + 1).padStart(2, '0')}: ${formatPartyLabel(item)}`)}</div>`;
            }).join('')
            : '';
        const comment = formatPartyComment(properties);
        const partyIdLabel = formatPartyLabel(properties);
        const headTitle = (userName && userName !== 'Not provided' ? userName : '') || displayName || partyIdLabel;
        const removeButton = canEdit
            ? `<button type="button" class="search-result-action search-result-action--danger" data-remove-party-location="${escapeHtml(String(properties.customer_id))}">Remove location</button>`
            : '';
        const profileLink = properties.show_url
            ? `<a class="customer-popup-action" href="${escapeHtml(properties.show_url)}">View profile</a>`
            : '';
        const fiberRemovedButton = properties.fiber_removal_pending
            ? `<button type="button" class="search-result-action search-result-action--danger" data-fiber-removed="${escapeHtml(String(properties.customer_id))}">Fiber removed — hide from map</button>`
            : '';

        state.customerPopup?.remove();
        state.customerPopup = new maplibregl.Popup({
            offset: 18,
            maxWidth: '360px',
            className: 'customer-map-popup',
        })
            .setLngLat(feature.geometry.coordinates)
            .setHTML(`
                <section class="customer-popup-shell">
                    <header class="customer-popup-head">
                        <div>
                            <span class="customer-popup-eyebrow">${properties.fiber_removal_pending ? 'Deleted party · fiber not removed' : 'Customer location'}</span>
                            <p class="popup-title">${escapeHtml(headTitle)}</p>
                            <p class="popup-meta"><span class="popup-meta-id">${escapeHtml(partyIdLabel)}</span></p>
                        </div>
                        <span class="badge ${statusClass}">${escapeHtml(statusText)}</span>
                    </header>
                    <dl class="popup-details">
                        <div class="popup-detail-card popup-detail-card--wide popup-detail-card--name"><dt>Name</dt><dd>${mapPartyInlineFieldHtml('name', properties.customer_id, inlineUpdateUrl, String(displayName || ''), 'Not provided')}</dd></div>
                        <div class="popup-detail-card"><dt>Party ID</dt><dd>${escapeHtml(formatPartyLabel(properties) || 'N/A')}</dd></div>
                        <div class="popup-detail-card"><dt>User Name</dt><dd>${mapPartyInlineFieldHtml('connection_id', properties.customer_id, inlineUpdateUrl, String(userId || ''), 'Not assigned')}</dd></div>
                        <div class="popup-detail-card"><dt>Active Status</dt><dd><span class="badge ${statusClass}">${escapeHtml(statusText)}</span></dd></div>
                        <div class="popup-detail-card"><dt>Phone</dt><dd>${mapPartyInlineFieldHtml('phone', properties.customer_id, inlineUpdateUrl, String(properties.phone || ''), 'Not provided')}</dd></div>
                        <div class="popup-detail-card popup-detail-card--wide"><dt>Comment</dt><dd>${mapPartyInlineFieldHtml('comment', properties.customer_id, inlineUpdateUrl, String(comment || ''), 'Not provided')}</dd></div>
                        <div class="popup-detail-card popup-detail-card--wide"><dt>Address</dt><dd>${mapPartyInlineFieldHtml('address', properties.customer_id, inlineUpdateUrl, String(properties.address || ''), 'Not provided')}</dd></div>
                        ${siblingSummary ? `<div class="popup-detail-card popup-detail-card--wide"><dt>Multi-port at location</dt><dd>${siblingSummary}</dd></div>` : ''}
                        <div class="popup-detail-card popup-detail-card--wide popup-detail-card--share"><dt>Share</dt><dd><div class="customer-popup-share">${shareControls}</div></dd></div>
                        <div class="popup-detail-card popup-detail-card--wide popup-detail-card--actions"><dt>Actions</dt><dd><div class="customer-popup-actions">${profileLink}${removeButton}${fiberRemovedButton}</div></dd></div>
                    </dl>
                </section>
            `)
            .addTo(state.map);

        const removeButtonElement = state.customerPopup.getElement().querySelector('[data-remove-party-location]');
        if (removeButtonElement) {
            removeButtonElement.addEventListener('click', () => {
                const customerId = removeButtonElement.dataset.removePartyLocation;
                removePartyLocation(customerId);
            });
        }

        const fiberRemovedElement = state.customerPopup.getElement().querySelector('[data-fiber-removed]');
        if (fiberRemovedElement) {
            fiberRemovedElement.addEventListener('click', () => {
                markCustomerFiberRemoved(fiberRemovedElement.dataset.fiberRemoved, feature);
            });
        }

        bindMapPartySearchActions(state.customerPopup.getElement());
        bindMapPartySearchInlineFields(state.customerPopup.getElement());
    }

    function customerHasLocation(feature) {
        return !!customerLocationKey(feature?.geometry?.coordinates || []);
    }

    function countPartyLocations() {
        let total = 0;
        state.customerFeatures.forEach((feature) => {
            if (customerHasLocation(feature)) {
                total += 1;
            }
        });

        return total;
    }

    function renderUnmappedPartyList() {
        const list = document.getElementById('unmappedPartyList');
        const count = document.getElementById('unmappedPartyCount');
        const filter = document.getElementById('unmappedPartyFilter');
        if (!list || !count || !filter) return;

        const query = String(filter.value || '').trim().toLowerCase();
        const unmappedParties = [...state.customerFeatures.values()]
            .filter((feature) => !customerHasLocation(feature))
            .sort((first, second) => Number(first.properties?.customer_id || 0) - Number(second.properties?.customer_id || 0));
        const matches = query
            ? unmappedParties.filter((feature) => {
                const properties = feature.properties || {};
                return [
                    properties.customer_id,
                    formatPartyDisplayName(properties),
                    properties.connection_id,
                    properties.mikrotik_username,
                    properties.phone,
                ].some((value) => String(value || '').toLowerCase().includes(query));
            })
            : unmappedParties;

        count.textContent = query ? `${matches.length}/${unmappedParties.length}` : String(unmappedParties.length);

        const summary = document.getElementById('unmappedPartySummary');
        if (summary) {
            const total = state.customerFeatures.size;
            const mapped = total - unmappedParties.length;
            summary.textContent = total
                ? `${mapped} of ${total} parties mapped · ${unmappedParties.length} unmapped`
                : 'No parties loaded yet.';
        }

        if (!matches.length) {
            list.innerHTML = `<div class="unmapped-party-empty">${unmappedParties.length ? 'No matching unmapped party.' : 'All parties have map locations.'}</div>`;
            return;
        }

        list.innerHTML = matches.map((feature) => {
            const properties = feature.properties || {};
            const customerId = String(properties.customer_id || '');
            const userName = formatPartyUserName(properties);
            const displayName = formatPartyDisplayName(properties) || 'Name not provided';
            const phone = String(properties.phone || '').trim();
            const secondary = [displayName, phone && phone !== 'Not provided' ? phone : ''].filter(Boolean).join(' | ');

            return `<div class="unmapped-party-row">
                <div class="unmapped-party-info">
                    <strong>${escapeHtml(`Party #${customerId} | ${userName}`)}</strong>
                    <span>${escapeHtml(secondary)}</span>
                </div>
                <button type="button" class="unmapped-party-add" data-add-unmapped-party="${escapeHtml(customerId)}">Add</button>
            </div>`;
        }).join('');

        list.querySelectorAll('[data-add-unmapped-party]').forEach((button) => {
            button.addEventListener('click', () => {
                requestPartyLocationPlacement(button.dataset.addUnmappedParty);
            });
        });
    }

    function refreshStatsFromData() {
        if (!state.map) {
            return;
        }

        renderStats([...state.features.values()]);
    }

    function upsertPartyFeature(feature) {
        const customerId = feature?.properties?.customer_id;
        if (!customerId) {
            return;
        }

        state.customerFeatures.set(String(customerId), feature);
        rebuildCustomerLocationSource();
        renderUnmappedPartyList();

        const resultPanel = document.getElementById('customerSearchResult');
        if (resultPanel && !resultPanel.hidden) {
            const query = document.getElementById('customerIdQuery')?.value?.trim() || '';
            if (query) {
                renderPartySearchResults(searchPartyFeaturesLocally(query));
            }
        }

        refreshStatsFromData();
    }

    function rebuildCustomerLocationSource() {
        const locationFeatures = [];
        const locationIndex = new Map();

        state.customerFeatures.forEach((feature) => {
            if (!customerHasLocation(feature)) {
                return;
            }

            const key = customerLocationKey(feature.geometry.coordinates);
            if (!key) {
                return;
            }

            locationFeatures.push(feature);

            const existing = locationIndex.get(key) || [];
            existing.push(feature);
            locationIndex.set(key, existing);
        });

        state.customerLocationIndex = locationIndex;
        setSourceData('customer-locations', {
            type: 'FeatureCollection',
            features: locationFeatures,
        });
        refreshPartySearchHighlightSource();
    }

    function renderPartyPlacementPanel(feature) {
        const panel = document.getElementById('partyPlacementPanel');
        const info = document.getElementById('partyPlacementInfo');
        const title = document.getElementById('partyPlacementTitle');
        const customerId = feature?.properties?.customer_id;
        if (!panel || !info || !title || !customerId) {
            return;
        }

        panel.dataset.customerId = String(customerId);
        title.textContent = `Place Party #${customerId} on map`;

        const properties = feature.properties || {};
        const displayName = formatPartyDisplayName(properties);
        const details = [
            ['Name', displayName || 'Not provided'],
            ['Party ID', `#${String(customerId)}`],
            ['Comment', properties.comment || 'Not provided'],
            ['Mobile', properties.phone || 'Not provided'],
            ['User ID', properties.connection_id || properties.mikrotik_username || 'Not provided'],
            ['Address', properties.address || 'Not provided'],
        ];

        info.innerHTML = details
            .map(([label, value]) => `<span class="detail"><span class="label">${escapeHtml(label)}:</span><strong>${escapeHtml(String(value))}</strong></span>`)
            .join('');
        panel.hidden = false;
    }

    function clearPartyPlacementPanel() {
        const panel = document.getElementById('partyPlacementPanel');
        if (!panel) {
            return;
        }

        panel.hidden = true;
        panel.removeAttribute('data-customer-id');
        panel.dataset.customerId = '';
        document.getElementById('partyPlacementInfo').innerHTML = '';
        document.getElementById('partyPlacementTitle').textContent = 'Place party location';
    }

    function requestPartyLocationPlacement(customerId) {
        const feature = state.customerFeatures.get(String(customerId));
        if (!feature) {
            return;
        }

        state.pendingPartyLocationCustomerId = String(customerId);
        if (state.map?.getCanvas()) {
            state.map.getCanvas().style.cursor = 'crosshair';
        }

        setPartySearchInput(customerId);
        renderPartyPlacementPanel(feature);
        setStatus(`Add mode: click map to set location for party #${customerId}.`);
    }

    function cancelPartyLocationPlacement() {
        if (!state.pendingPartyLocationCustomerId) {
            clearPartyPlacementPanel();
            return;
        }

        state.pendingPartyLocationCustomerId = null;
        if (state.map?.getCanvas()) {
            state.map.getCanvas().style.cursor = '';
        }
        clearPartyPlacementPanel();

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
        if (state.map?.getCanvas()) {
            state.map.getCanvas().style.cursor = '';
        }
        clearPartyPlacementPanel();

        setCustomerSearchResult(`Saved location for Party #${customerId}.`);
        setStatus(`Party #${customerId} saved on the map.`);
        focusCustomer(customerId);
        state.customerPopup = null;
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
            state.pendingPartyLocationCustomerId = String(customerId);
            setStatus(error.message);
            return null;
        }
    }

    async function markCustomerFiberRemoved(customerId, feature) {
        if (!customerId) {
            return;
        }

        const url = feature?.properties?.fiber_removed_url
            || `${config.customersUrl}/${encodeURIComponent(customerId)}/fiber-removed`;
        const label = formatPartyLabel(feature || { properties: { customer_id: customerId } });
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

            state.customerFeatures.delete(String(customerId));
            rebuildCustomerLocationSource();
            renderUnmappedPartyList();
            refreshStatsFromData();
            if (state.customerPopup) {
                state.customerPopup.remove();
                state.customerPopup = null;
            }
            setStatus(`${label} removed from the map — fiber cleanup recorded.`);
        } catch (error) {
            setStatus(error.message);
        }
    }

    async function removePartyLocation(customerId) {
        if (!customerId) {
            return;
        }

        const feature = state.customerFeatures.get(String(customerId));
        const label = formatPartyLabel(feature || { customer_id: customerId });
        if (!confirm(`Remove saved map location for ${label}?`)) {
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
            const placementPanel = document.getElementById('partyPlacementPanel');
            if (String(placementPanel?.dataset.customerId) === String(customerId)) {
                clearPartyPlacementPanel();
            }
            setCustomerSearchResult(`Party #${customerId} location removed.`);
            setStatus(`Party #${customerId} no longer has a map location.`);
            if (state.customerPopup) {
                state.customerPopup.remove();
                state.customerPopup = null;
            }
        } catch (error) {
            setStatus(error.message);
        }
    }

    function setCustomerSearchResult(message, isError = false) {
        const panel = document.getElementById('customerSearchResult');
        panel.hidden = false;
        panel.innerHTML = `<div class="search-empty${isError ? ' error' : ''}">${escapeHtml(message)}</div>`;
    }

    function bindMapPartySearchActions(root = null) {
        const container = root || document.getElementById('customerSearchResult');
        if (!container) {
            return;
        }

        container.querySelectorAll('[data-copy-party]').forEach((button) => {
            const feature = state.customerFeatures.get(String(button.dataset.copyParty || ''));
            if (!feature) {
                return;
            }

            button.addEventListener('click', async () => {
                await copyMapPartyShareText(buildMapPartyShareText(feature), button);
            }, { once: true });
        });

        container.querySelectorAll('[data-whatsapp-share]').forEach((button) => {
            const feature = state.customerFeatures.get(String(button.dataset.whatsappShare || ''));
            if (!feature) {
                return;
            }

            mapPartyWhatsappButton(button, feature);
            button.addEventListener('click', (event) => {
                if (!customerHasLocation(feature)) {
                    event.preventDefault();
                }
            }, { once: true });
        });
    }

    function bindMapPartySearchInlineFields(root = null) {
        const container = root || document.getElementById('customerSearchResult');
        if (!container) {
            return;
        }

        container.querySelectorAll('[data-inline-field]').forEach((fieldElement) => {
            fieldElement.classList.add('inline-edit-field');
            fieldElement.title = 'Double click to edit';
            fieldElement.addEventListener('dblclick', (event) => {
                event.preventDefault();
                startMapPartyInlineEdit(fieldElement);
            }, { once: true });
        });
    }

    function mapPartyInlineUpdateUrl(feature) {
        const properties = feature?.properties || {};
        const raw = String(properties.show_url || '').trim();
        if (!raw) {
            return '';
        }

        return raw.endsWith('/') ? `${raw}inline` : `${raw}/inline`;
    }

    function mapPartyInlineFieldHtml(field, customerId, inlineUrl, value, emptyText) {
        const normalized = (value === null || value === undefined ? '' : String(value)).trim();
        const displayValue = normalized || emptyText;
        const safeField = escapeHtml(field);
        const safeCustomerId = escapeHtml(customerId);
        const safeInlineUrl = escapeHtml(inlineUrl);
        const safeDisplay = escapeHtml(displayValue);
        const safeValue = escapeHtml(normalized);
        const safeEmptyText = escapeHtml(emptyText);

        return inlineUrl
            ? `<span class="inline-edit-field" data-inline-field="${safeField}" data-inline-url="${safeInlineUrl}" data-customer-id="${safeCustomerId}" data-inline-value="${safeValue}" data-inline-empty-text="${safeEmptyText}">${safeDisplay}</span>`
            : safeDisplay;
    }

    async function startMapPartyInlineEdit(fieldElement) {
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
                upsertMapPartyFeatureValue(customerId, field, value);
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
                finish(true);
                fieldElement.dataset.inlineEditing = '0';
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

    function upsertMapPartyFeatureValue(customerId, field, value) {
        const normalized = value === null || value === undefined ? '' : String(value).trim();
        const feature = state.customerFeatures.get(String(customerId));
        if (!feature || !feature.properties) {
            return;
        }

        const nextFeature = { ...feature, properties: { ...feature.properties } };
        if (field === 'connection_id') {
            nextFeature.properties.connection_id = normalized;
            nextFeature.properties.mikrotik_username = normalized;
        } else {
            nextFeature.properties[field] = normalized;
        }

        state.customerFeatures.set(String(customerId), nextFeature);
        rebuildCustomerLocationSource();
        renderUnmappedPartyList();
        const resultPanel = document.getElementById('customerSearchResult');
        if (resultPanel && !resultPanel.hidden) {
            renderPartySearchResults([...state.customerFeatures.values()]);
        }

        refreshStatsFromData();
        if (state.customerPopup) {
            const popupFeature = state.customerFeatures.get(String(customerId));
            if (popupFeature) {
                showCustomerPopup(popupFeature);
            }
        }
    }

    function mapPartyShareButtonsMarkup(feature) {
        const properties = feature?.properties || {};
        const customerId = String(properties.customer_id || '').trim();
        const hasLocation = customerHasLocation(feature);
        const primaryClass = 'search-result-action search-result-action--primary';
        const disabled = hasLocation ? '' : ' is-disabled';
        const shareState = hasLocation ? '' : ' data-share-disabled="1"';

        return `<button type="button" class="search-result-action" data-copy-party="${escapeHtml(customerId)}">Copy</button>`
            + `<a href="#" class="${primaryClass}${disabled}" data-whatsapp-share="${escapeHtml(customerId)}"${shareState}>WhatsApp</a>`;
    }

    function mapPartyWhatsappButton(button, feature) {
        if (!button || !feature) {
            return;
        }

        const hasLocation = customerHasLocation(feature);
        const shareText = buildMapPartyShareText(feature);
        button.href = hasLocation ? `https://wa.me/?text=${encodeURIComponent(shareText)}` : '#';
        button.classList.toggle('is-disabled', !hasLocation);
        button.setAttribute('aria-disabled', hasLocation ? 'false' : 'true');
        if (!hasLocation) {
            button.setAttribute('tabindex', '-1');
        } else {
            button.removeAttribute('tabindex');
        }
    }

    function buildMapPartyMapUrl(feature) {
        const longitude = Number(feature?.geometry?.coordinates?.[0]);
        const latitude = Number(feature?.geometry?.coordinates?.[1]);
        if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) {
            return '';
        }

        return `https://maps.google.com/?q=${latitude.toFixed(8)},${longitude.toFixed(8)}`;
    }

    function buildMapPartyShareText(feature) {
        const properties = feature?.properties || {};
        const name = formatPartyDisplayName(feature) || 'Not provided';
        const phone = properties.phone || 'Not provided';
        const userId = properties.connection_id || properties.mikrotik_username || 'Not assigned';
        const statusText = properties.deleted ? 'Deleted' : formatPartyStatus(properties.status);
        const mapUrl = buildMapPartyMapUrl(feature);
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

    async function copyMapPartyShareText(text, triggerButton) {
        if (!text) {
            return;
        }

        const originalText = triggerButton?.textContent || '';
        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const backup = document.createElement('textarea');
            backup.value = text;
            backup.setAttribute('readonly', 'readonly');
            backup.style.position = 'fixed';
            backup.style.opacity = '0';
            document.body.appendChild(backup);
            backup.select();
            document.execCommand('copy');
            backup.remove();
        }

        if (triggerButton) {
            triggerButton.textContent = 'Copied';
            window.setTimeout(() => {
                triggerButton.textContent = originalText || 'Copy';
            }, 1000);
        }
    }

    function customerLocationKey(coordinates) {
        const longitude = Number(coordinates[0]);
        const latitude = Number(coordinates[1]);

        if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) {
            return null;
        }

        return `${longitude.toFixed(8)},${latitude.toFixed(8)}`;
    }

    function customerSiblingsAtLocation(coordinates) {
        const key = customerLocationKey(coordinates);
        if (!key) return [];

        return state.customerLocationIndex.get(key) || [];
    }

    async function loadTopology() {
        try {
            const response = await fetch(config.indexUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(await responseErrorMessage(response, 'Unable to load network topology.'));

            const collection = await response.json();
            state.features = new Map((collection.features || []).map((feature) => [feature.properties.id || feature.id, feature]));
            const repairedLegacyLinks = repairLegacySplitterLinks();
            refreshSources();
            if (repairedLegacyLinks) {
                state.dirty = true;
                await persistTopology();
                setStatus('Topology loaded. Existing splitter links were synchronized on both ends.');
            } else {
                setStatus('Topology loaded. Map centered on Kushtia district.');
            }
            // Baseline for undo/redo.
            state.history = [topologySnapshot()];
            state.historyIndex = 0;
            updateHistoryButtons();
        } catch (error) {
            setStatus(error.message);
        }
    }

    const HISTORY_LIMIT = 60;

    function topologySnapshot() {
        return JSON.stringify(toFeatureCollection());
    }

    function pushHistory() {
        if (state.restoringHistory) return;
        const snap = topologySnapshot();
        if (state.history[state.historyIndex] === snap) return;
        state.history = state.history.slice(0, state.historyIndex + 1);
        state.history.push(snap);
        while (state.history.length > HISTORY_LIMIT) {
            state.history.shift();
        }
        state.historyIndex = state.history.length - 1;
        updateHistoryButtons();
    }

    function updateHistoryButtons() {
        const undo = document.getElementById('undoTopology');
        const redo = document.getElementById('redoTopology');
        if (undo) undo.disabled = state.historyIndex <= 0;
        if (redo) redo.disabled = state.historyIndex >= state.history.length - 1;
    }

    async function applyHistorySnapshot(snapJson) {
        state.restoringHistory = true;
        try {
            const collection = JSON.parse(snapJson);
            const activePathFeatureId = currentPathFeatureId();
            state.features = new Map((collection.features || []).map((feature) => [feature.properties.id || feature.id, feature]));
            clearSelection();
            clearPathMarkers();
            closeFiberCorePanel();
            closeOltPortPanel();
            closeFeatureForm();
            refreshSources();
            if (activePathFeatureId && state.features.has(activePathFeatureId)) {
                showPathMarkers(activePathFeatureId);
            }
            state.dirty = true;
            await persistTopology();
        } finally {
            state.restoringHistory = false;
        }
        updateHistoryButtons();
    }

    async function undoTopology() {
        if (state.historyIndex <= 0) return;
        state.historyIndex -= 1;
        await applyHistorySnapshot(state.history[state.historyIndex]);
        setStatus('Undo applied.');
    }

    async function redoTopology() {
        if (state.historyIndex >= state.history.length - 1) return;
        state.historyIndex += 1;
        await applyHistorySnapshot(state.history[state.historyIndex]);
        setStatus('Redo applied.');
    }

    async function persistTopology() {
        pushHistory();
        const button = document.getElementById('saveTopology');
        button.disabled = true;
        setStatus('Saving topology...');

        try {
            const response = await fetch(config.storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify(toFeatureCollection()),
            });

            if (!response.ok) {
                throw new Error(await responseErrorMessage(response, 'The topology could not be saved.'));
            }

            const collection = await response.json();
            const activePathFeatureId = currentPathFeatureId();
            state.features = new Map((collection.features || []).map((feature) => [feature.properties.id || feature.id, feature]));
            state.dirty = false;
            refreshSources();
            if (activePathFeatureId) {
                showPathMarkers(activePathFeatureId);
            }
            setStatus('Network topology saved.');
        } catch (error) {
            setStatus(error.message);
        } finally {
            button.disabled = false;
        }
    }

    async function handleMapClick(event) {
        if (state.nodeDragJustFinished) {
            state.nodeDragJustFinished = false;
            return;
        }

        if (state.pendingPartyLocationCustomerId) {
            await placePartyLocationFromMapClick(state.pendingPartyLocationCustomerId, event.lngLat.lng, event.lngLat.lat);
            return;
        }

        if (state.relocatingFeatureId) {
            const feature = state.features.get(state.relocatingFeatureId);
            state.relocatingFeatureId = null;
            state.map.getCanvas().style.cursor = '';
            if (feature && feature.geometry.type === 'Point') {
                feature.geometry.coordinates = [event.lngLat.lng, event.lngLat.lat];
                moveLinkedFiberEndpoints(feature);
                refreshSources();
                selectFeature(feature.properties.id || feature.id);
                state.dirty = true;
                persistTopology();
                setStatus(`${featureDisplayName(feature)} moved to the new location.`);
            }
            return;
        }

        const clicked = state.map.queryRenderedFeatures(event.point, {
            layers: ['customer-locations-label', 'customer-locations-circle', 'network-nodes-circle', 'network-links-line-hit'],
        });

        if (clicked.length) {
            if (clicked[0].layer.id === 'customer-locations-circle' || clicked[0].layer.id === 'customer-locations-label') {
                const customer = state.customerFeatures.get(String(clicked[0].properties.customer_id));

                // With the fiber tool active, a customer pin is a route endpoint:
                // snap this vertex onto the pin and remember which party it is.
                if (state.activeTool === 'fiber' && customer && Array.isArray(customer.geometry?.coordinates)) {
                    state.draftLine.push(customer.geometry.coordinates.slice());
                    state.draftLineCustomers.push({
                        id: String(customer.properties.customer_id),
                        name: customerEndpointLabel(customer.properties),
                    });
                    updateDraftLine();
                    setStatus(`${state.draftLine.length} fiber vertices added (snapped to ${customerEndpointLabel(customer.properties)}). Double-click or Enter to finish.`);
                    return;
                }

                if (customer) {
                    setPartySearchInput(customer.properties.customer_id);
                    focusCustomer(String(customer.properties.customer_id));
                }
                return;
            }

            const featureId = clicked[0].properties.id;
            const feature = state.features.get(featureId);
            if (state.pendingEndpointLink && feature?.properties.component_type === 'tj_box') {
                linkPendingEndpointToTjBox(feature);
                return;
            }

            if (state.pendingEndpointLink) {
                setStatus('Select a TJ Box to link this fiber endpoint.');
                return;
            }

            selectMapFeature(featureId);
            showFeaturePopup(featureId);
            return;
        }

        if (state.activeTool === 'node' && state.activeNodeType) {
            const feature = {
                type: 'Feature',
                id: uuid(),
                geometry: { type: 'Point', coordinates: [event.lngLat.lng, event.lngLat.lat] },
                properties: {
                    id: uuid(),
                    feature_type: 'node',
                    component_type: state.activeNodeType,
                    ...defaultPropertiesFor(state.activeNodeType),
                },
            };
            feature.id = feature.properties.id;
            state.features.set(feature.id, feature);
            clearPathMarkers();
            refreshSources();
            selectFeature(feature.id);
            openFeatureForm(feature.id, true);

            // One node per tool pick: deactivate so the next map click does not
            // drop another. Re-select the tool to add more nodes.
            const placedLabel = componentLabels[feature.properties.component_type] || 'Node';
            state.activeTool = null;
            state.activeNodeType = null;
            document.querySelectorAll('.map-tool').forEach((item) => item.classList.remove('active'));
            updatePlacementCursor();
            setStatus(`${placedLabel} placed. Pick a node tool again to add another.`);
            return;
        }

        if (state.activeTool === 'fiber') {
            state.draftLine.push([event.lngLat.lng, event.lngLat.lat]);
            state.draftLineCustomers.push(null);
            updateDraftLine();
            setStatus(`${state.draftLine.length} fiber vertices added. Double-click or press Enter to finish.`);
        }
    }

    function customerEndpointLabel(props = {}) {
        const id = String(props.customer_id || '').trim();
        const name = String(props.connection_id || props.mikrotik_username || props.customer_name || '').trim();
        return name ? `${name} (Party #${id})` : `Party #${id}`;
    }

    function finishFiberDraft() {
        if (state.draftLine.length < 2) {
            setStatus('Add at least two route points before finishing a fiber cable.');
            return;
        }

        const id = uuid();
        const lengthMeters = lineLengthMeters(state.draftLine);
        const aCustomer = state.draftLineCustomers[0] || null;
        const zCustomer = state.draftLineCustomers[state.draftLineCustomers.length - 1] || null;
        const endpointProps = {};
        if (aCustomer) {
            endpointProps.a_end_customer_id = aCustomer.id;
            endpointProps.a_end_customer_name = aCustomer.name;
            endpointProps.a_end_device_port = aCustomer.name;
        }
        if (zCustomer) {
            endpointProps.z_end_customer_id = zCustomer.id;
            endpointProps.z_end_customer_name = zCustomer.name;
            endpointProps.z_end_device_port = zCustomer.name;
        }
        // A fiber that lands directly on a customer pin is a drop cable — 2 core.
        if (aCustomer || zCustomer) {
            endpointProps.core_count = '2F';
        }
        const feature = {
            type: 'Feature',
            id,
            geometry: { type: 'LineString', coordinates: state.draftLine.slice() },
            properties: {
                id,
                feature_type: 'link',
                component_type: 'fiber_cable',
                ...defaultPropertiesFor('fiber_cable'),
                length_meters: Number(lengthMeters.toFixed(2)),
                ...endpointProps,
            },
        };

        state.features.set(id, feature);
        state.draftLine = [];
        state.draftLineCustomers = [];
        updateDraftLine();
        refreshSources();
        selectFeature(id);
        showPathMarkers(id);
        openFeatureForm(id, true);
    }

    function openExistingFeature(id) {
        if (!id || !state.features.has(id)) return;
        selectMapFeature(id);
        openFeatureForm(id, false);
        showFeaturePopup(id);
    }

    function selectMapFeature(featureId) {
        const feature = state.features.get(featureId);
        if (!feature) return;

        selectFeature(featureId);
        if (feature.geometry.type === 'LineString') {
            showPathMarkers(featureId);
            closeOltPortPanel();
            setStatus(`${featureDisplayName(feature)} selected. Double-click for details.`);
            return;
        }

        clearPathMarkers();
        if (isPortLinkDevice(feature)) {
            openOltPortPanel(featureId);
        } else {
            closeOltPortPanel();
        }
        setStatus(`${featureDisplayName(feature)} selected. Drag to move, double-click for details.`);
    }

    function handleFeatureDetails(event) {
        const clicked = state.map.queryRenderedFeatures(event.point, {
            layers: ['network-nodes-circle', 'network-links-line-hit'],
        });

        if (!clicked.length) return false;

        event.preventDefault();
        openExistingFeature(clicked[0].properties.id);
        return true;
    }

    function centerEmptyMapPoint(clickedPoint) {
        state.map.stop();
        const bounds = state.map.getCanvas().getBoundingClientRect();
        const visibleCenter = visibleMapCenterPoint(bounds);
        const nextCenter = state.map.unproject([
            (bounds.width / 2) + clickedPoint.x - visibleCenter.x,
            (bounds.height / 2) + clickedPoint.y - visibleCenter.y,
        ]);
        state.map.jumpTo({ center: [nextCenter.lng, nextCenter.lat] });
    }

    function showHoverDetails(event) {
        if (state.activeTool) return;
        state.map.getCanvas().style.cursor = 'pointer';
        const featureId = event.features?.[0]?.properties?.id;
        const feature = state.features.get(featureId);
        if (!feature) return;

        hideHoverDetailsNow();
        if (state.pendingEndpointLink && feature.properties.component_type === 'tj_box') {
            state.linkTargetFeatureId = featureId;
            updateLinkTargetSource();
        }
        state.hoverPopup = new maplibregl.Popup({ closeButton: false, closeOnClick: false, offset: 12 })
            .setLngLat(event.lngLat)
            .setHTML(popupHtml(feature))
            .addTo(state.map);

        // Keep the card alive on hover so its Edit / Relocate buttons are usable.
        const popupEl = state.hoverPopup.getElement();
        popupEl.addEventListener('mouseenter', clearHoverHideTimer);
        popupEl.addEventListener('mouseleave', hideHoverDetails);
        popupEl.querySelector('[data-edit-feature]')?.addEventListener('click', () => {
            hideHoverDetailsNow();
            openExistingFeature(featureId);
        });
        popupEl.querySelector('[data-relocate-feature]')?.addEventListener('click', () => {
            hideHoverDetailsNow();
            startFeatureRelocate(featureId);
        });
    }

    function clearHoverHideTimer() {
        if (state.hoverHideTimer) {
            clearTimeout(state.hoverHideTimer);
            state.hoverHideTimer = null;
        }
    }

    function showCustomerHoverSummary(event) {
        if (state.activeTool) return;
        clearHoverHideTimer();

        const renderedFeature = event.features?.[0];
        const customerId = String(renderedFeature?.properties?.customer_id || '');
        const feature = state.customerFeatures.get(customerId) || renderedFeature;
        if (!feature) return;

        hideHoverDetailsNow();
        state.map.getCanvas().style.cursor = 'pointer';

        const properties = feature.properties || {};
        const statusText = properties.deleted ? 'Deleted' : formatPartyStatus(properties.status);
        const statusClass = properties.deleted ? 'inactive' : formatPartyStatusClass(properties.status);
        const name = formatPartyDisplayName(properties) || 'Not provided';
        const userId = properties.connection_id || properties.mikrotik_username || 'Not assigned';
        const phone = properties.phone || 'Not provided';
        const canRelocate = customerHasLocation(feature);

        state.hoverPopup = new maplibregl.Popup({
            closeButton: false,
            closeOnClick: false,
            offset: 14,
            maxWidth: '270px',
            className: 'customer-hover-popup',
        })
            .setLngLat(feature.geometry?.coordinates || event.lngLat)
            .setHTML(`
                <article class="customer-hover-summary">
                    <header>
                        <span>${escapeHtml(formatPartyLabel(properties))}</span>
                        <span class="badge ${statusClass}">${escapeHtml(statusText)}</span>
                    </header>
                    <strong>${escapeHtml(name)}</strong>
                    <dl>
                        <div><dt>User ID</dt><dd>${escapeHtml(userId)}</dd></div>
                        <div><dt>Phone</dt><dd>${escapeHtml(phone)}</dd></div>
                    </dl>
                    ${canRelocate ? `<button type="button" class="search-result-action" data-relocate-party="${escapeHtml(customerId)}">Relocate</button>` : ''}
                    <small>Click pin for full details</small>
                </article>
            `)
            .addTo(state.map);

        // Keep the card alive while the pointer is on it so its button is usable.
        const popupEl = state.hoverPopup.getElement();
        popupEl.addEventListener('mouseenter', clearHoverHideTimer);
        popupEl.addEventListener('mouseleave', hideHoverDetails);
        popupEl.querySelector('[data-relocate-party]')?.addEventListener('click', () => {
            hideHoverDetailsNow();
            requestPartyLocationPlacement(customerId);
        });
    }

    function hideHoverDetails() {
        clearHoverHideTimer();
        state.hoverHideTimer = setTimeout(hideHoverDetailsNow, 180);
    }

    function hideHoverDetailsNow() {
        clearHoverHideTimer();
        if (!state.activeTool) {
            state.map.getCanvas().style.cursor = '';
        }
        if (state.hoverPopup) {
            state.hoverPopup.remove();
            state.hoverPopup = null;
        }
    }

    function openFeatureForm(id, isNew) {
        const feature = state.features.get(id);
        if (!feature) return;

        state.editingFeatureId = id;
        const componentType = feature.properties.component_type;
        document.getElementById('formMode').textContent = isNew ? 'New feature' : 'Edit feature';
        document.getElementById('formTitle').textContent = componentLabels[componentType] || 'Infrastructure Details';
        document.getElementById('deleteFeature').hidden = isNew;
        renderFields(componentType, formPropertiesFor(feature));
        document.getElementById('featureModal').hidden = false;
    }

    function formPropertiesFor(feature) {
        if (feature.geometry.type !== 'Point') {
            return feature.properties;
        }

        return {
            ...feature.properties,
            longitude: Number(feature.geometry.coordinates[0]).toFixed(6),
            latitude: Number(feature.geometry.coordinates[1]).toFixed(6),
        };
    }

    function updatePointCoordinatesFromForm(feature, data) {
        if (feature.geometry.type !== 'Point') {
            return;
        }

        const lat = Number(data.get('latitude'));
        const lng = Number(data.get('longitude'));

        if (!Number.isFinite(lat) || lat < -90 || lat > 90) {
            throw new Error('Latitude must be between -90 and 90.');
        }
        if (!Number.isFinite(lng) || lng < -180 || lng > 180) {
            throw new Error('Longitude must be between -180 and 180.');
        }

        feature.geometry.coordinates = [lng, lat];
    }

    function renderFields(componentType, properties) {
        const target = document.getElementById('featureFields');
        const schema = [...(formSchemas[componentType] || []), photoField];
        target.innerHTML = schema.map((field) => fieldHtml(field, properties[field.name])).join('');
        hydrateSearchableFormLists(target, schema);
        hydrateDynamicMaps(target, properties);
        bindEndpointUnlinkButtons(target);
    }

    async function saveFeatureForm(event) {
        event.preventDefault();

        const feature = state.features.get(state.editingFeatureId);
        if (!feature) return;
        const submitButton = event.currentTarget.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const data = new FormData(event.currentTarget);
            for (const [key, value] of data.entries()) {
                if (!['photo_files', 'latitude', 'longitude'].includes(key)) {
                    feature.properties[key] = value;
                }
            }

            updatePointCoordinatesFromForm(feature, data);
            const dynamicMaps = serializeDynamicMaps(event.currentTarget);
            if (feature.properties.component_type === 'splitter') {
                clearDirectDeviceLinksForFeature(feature);
            }
            Object.assign(feature.properties, dynamicMaps);
            feature.properties.photos = [
                ...serializeExistingPhotos(event.currentTarget),
                ...await uploadSelectedPhotos(event.currentTarget),
            ];
            syncSplitterParent(feature);
            if (feature.properties.component_type === 'splitter') {
                repairLegacySplitterLinks();
                autoDrawSplitterDropFibers(feature);
            }
            if (feature.geometry.type === 'Point') {
                moveLinkedFiberEndpoints(feature);
            }

            if (feature.geometry.type === 'LineString') {
                feature.properties.length_meters = Number(lineLengthMeters(feature.geometry.coordinates).toFixed(2));
            }

            state.features.set(feature.properties.id || feature.id, feature);
            state.dirty = true;
            closeFeatureForm();
            refreshSources();
            updateGeoJsonPreview();
            await persistTopology();
        } catch (error) {
            setStatus(error.message);
        } finally {
            submitButton.disabled = false;
        }
    }

    function deleteCurrentFeature() {
        if (!state.editingFeatureId) return;
        if (deleteFeatureById(state.editingFeatureId)) {
            closeFeatureForm();
        }
    }

    function closeFeatureForm() {
        document.getElementById('featureModal').hidden = true;
        state.editingFeatureId = null;
    }

    function cancelDrawing() {
        state.activeTool = null;
        state.activeNodeType = null;
        state.draftLine = [];
        state.draftLineCustomers = [];
        if (state.relocatingFeatureId) {
            state.relocatingFeatureId = null;
            state.map.getCanvas().style.cursor = '';
        }
        clearPathMarkers();
        clearLinkTarget();
        clearSelection();
        clearPlacementPreview();
        closeFiberCorePanel();
        closeOltPortPanel();
        document.querySelectorAll('.map-tool').forEach((item) => item.classList.remove('active'));
        updateDraftLine();
        updatePlacementCursor();
        setStatus('Draw mode cleared.');
    }

    function deleteSelectedMapItem() {
        if (state.selectedBendPoint) {
            deleteSelectedBendPoint();
            return;
        }

        if (state.selectedFeatureId) {
            deleteFeatureById(state.selectedFeatureId);
        }
    }

    function deleteFeatureById(featureId) {
        if (!featureId || !state.features.has(featureId)) return false;

        const feature = state.features.get(featureId);
        const blockedReason = deleteBlockedReason(feature);
        if (blockedReason) {
            setStatus(blockedReason);
            return false;
        }

        state.features.delete(featureId);
        state.hiddenFeatureIds.delete(String(featureId));
        removeLooseReferencesToFeature(feature);
        persistHiddenFeatureIds();
        clearPathMarkers();
        clearLinkTarget();
        clearSelection();
        state.dirty = true;
        refreshSources();
        persistTopology();
        setStatus('Selected item deleted.');
        return true;
    }

    function deleteBlockedReason(feature) {
        if (!feature) return '';

        const featureId = String(feature.properties.id || feature.id);
        const name = featureDisplayName(feature);
        const ownLinks = ownLinkCount(feature);
        if (ownLinks > 0) {
            return `${name} has ${ownLinks} linked item(s). Unlink all links before deleting.`;
        }

        const externalLinks = [...state.features.values()].filter((candidate) => {
            if (String(candidate.properties.id || candidate.id) === featureId) return false;
            return referencesFeature(candidate, featureId);
        });
        if (externalLinks.length > 0) {
            return `${name} is linked from ${externalLinks.length} other item(s). Unlink them before deleting.`;
        }

        return '';
    }

    function ownLinkCount(feature) {
        const props = feature.properties || {};
        let count = Object.keys(props.endpoint_links || {}).length;
        count += (props.connected_ports || []).length;
        const portLinks = {
            ...(props.olt_port_links || {}),
            ...(props.port_links || {}),
        };
        const portLinkKeys = Object.keys(portLinks);
        count += portLinkKeys.length;
        count += (props.splitter_ports || []).filter((row) => {
            return (row.connected_fiber || row.connected_core) && !portLinkKeys.includes(row.port);
        }).length;

        return count;
    }

    function referencesFeature(feature, targetFeatureId) {
        const props = feature.properties || {};

        if (props.splitter_parent_tj_box_id === targetFeatureId) {
            return true;
        }

        if (Object.values(props.endpoint_links || {}).some((link) => String(link.node_id) === targetFeatureId)) {
            return true;
        }

        const portLinks = {
            ...(props.olt_port_links || {}),
            ...(props.port_links || {}),
        };
        if (Object.values(portLinks).some((link) => String(link.fiber_id) === targetFeatureId
            || String(link.target_device_id) === targetFeatureId)) {
            return true;
        }

        return (props.connected_ports || []).some((port) => String(port.fiber_id) === targetFeatureId);
    }

    function removeLooseReferencesToFeature(targetFeature) {
        const targetNames = featureReferenceNames(targetFeature);
        if (!targetNames.length) return;

        state.features.forEach((feature) => {
            const ports = feature.properties?.splitter_ports;
            if (!Array.isArray(ports)) return;

            feature.properties.splitter_ports = ports.map((port) => {
                if (!targetNames.includes(String(port.connected_fiber || '').trim())) {
                    return port;
                }

                return {
                    ...port,
                    connected_fiber: '',
                    connected_core: '',
                    note: '',
                };
            });
        });
    }

    function featureReferenceNames(feature) {
        const props = feature.properties || {};
        return [
            props.id,
            feature.id,
            props.fiber_code,
            props.name,
            props.box_name,
            props.client_name,
        ]
            .map((value) => String(value || '').trim())
            .filter(Boolean);
    }

    function deleteSelectedBendPoint() {
        const { featureId, coordinateIndex } = state.selectedBendPoint;
        const feature = state.features.get(featureId);
        if (!feature || feature.geometry.type !== 'LineString') {
            clearSelectedBendPoint();
            return;
        }

        if (coordinateIndex <= 0 || coordinateIndex >= feature.geometry.coordinates.length - 1 || feature.geometry.coordinates.length <= 2) {
            setStatus('Fiber endpoint cannot be deleted. Use Unlink or move the endpoint.');
            clearSelectedBendPoint();
            showPathMarkers(featureId);
            return;
        }

        feature.geometry.coordinates.splice(coordinateIndex, 1);
        feature.properties.length_meters = Number(lineLengthMeters(feature.geometry.coordinates).toFixed(2));
        state.dirty = true;
        refreshSources();
        showPathMarkers(featureId);
        persistTopology();
        setStatus('Fiber bend point deleted.');
    }

    function refreshSources() {
        const features = [...state.features.values()];
        const visibleFeatures = features.filter(isFeatureVisible);
        const nodes = visibleFeatures.filter((feature) => feature.geometry.type === 'Point');
        const links = withParallelLineOffsets(visibleFeatures
            .filter((feature) => feature.geometry.type === 'LineString')
            .map(withMapLineColor));

        setSourceData('network-nodes', { type: 'FeatureCollection', features: nodes });
        setSourceData('network-links', { type: 'FeatureCollection', features: links });
        updateEndpointCoreLinks(features);
        updateSelectionSource();
        updateLinkTargetSource();
        renderStats(features);
    }

    function updateDraftLine() {
        const lineFeatures = state.draftLine.length >= 2
            ? [{ type: 'Feature', geometry: { type: 'LineString', coordinates: state.draftLine }, properties: {} }]
            : [];
        const pointFeatures = state.draftLine.map((coordinate, index) => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: coordinate },
            properties: { point_index: index + 1 },
        }));

        setSourceData('draft-line', { type: 'FeatureCollection', features: lineFeatures });
        setSourceData('draft-points', { type: 'FeatureCollection', features: pointFeatures });
    }

    function updateEndpointCoreLinks(features) {
        const overlays = [];
        const overlayIds = new Set();
        const visibleFiberIds = new Set(features
            .filter((feature) => feature.geometry.type === 'LineString' && isFeatureVisible(feature))
            .map((feature) => String(feature.properties.id || feature.id)));

        features.forEach((device) => {
            if (!isPortLinkDevice(device) || !isFeatureVisible(device)) return;

            Object.entries(devicePortLinks(device)).forEach(([port, link]) => {
                if (isDirectDeviceLink(link)) {
                    const target = state.features.get(link.target_device_id);
                    if (!target || !isFeatureVisible(target)) return;

                    const deviceId = String(device.properties.id || device.id);
                    const targetId = String(target.properties.id || target.id);
                    const directLinkId = [`${deviceId}:${port}`, `${targetId}:${link.target_port || ''}`].sort().join('-');
                    if (overlayIds.has(directLinkId)) return;
                    overlayIds.add(directLinkId);
                    overlays.push({
                        type: 'Feature',
                        geometry: {
                            type: 'LineString',
                            coordinates: [device.geometry.coordinates, target.geometry.coordinates],
                        },
                        properties: {
                            id: `direct-router-${directLinkId}`,
                            link_type: link.link_type,
                            medium: link.medium || 'Fiber',
                            color_hex: link.color_hex || (link.medium === 'Copper' ? '#b54708' : '#06b6d4'),
                        },
                    });
                    return;
                }

                const fiber = state.features.get(link.fiber_id);
                if (!fiber || !visibleFiberIds.has(String(fiber.properties.id || fiber.id))) return;

                const coreRow = buildFiberCoreRowsForFeature(fiber).find((row) => Number(row.core) === Number(link.core));
                const target = closestCoordinateOnLine(device.geometry.coordinates, fiber.geometry.coordinates);
                overlays.push({
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: [device.geometry.coordinates, target],
                    },
                    properties: {
                        id: `${device.properties.id || device.id}-${port}-${link.fiber_id}-${link.core}`,
                        port,
                        color_hex: link.color_hex || coreRow?.color_hex || '#f79009',
                    },
                });
            });
        });

        appendSplitterPortLinks(features, overlays, overlayIds);

        setSourceData('endpoint-core-links', {
            type: 'FeatureCollection',
            features: withParallelLineOffsets(overlays),
        });
    }

    function withParallelLineOffsets(features) {
        const groups = new Map();
        features.forEach((feature) => {
            const direction = canonicalLineDirection(feature.geometry?.coordinates || []);
            if (direction.reverse) {
                feature.geometry = {
                    ...feature.geometry,
                    coordinates: [...feature.geometry.coordinates].reverse(),
                };
            }
            const key = direction.key;
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(feature);
        });

        groups.forEach((group) => {
            group.sort((first, second) => lineStableId(first).localeCompare(lineStableId(second), undefined, {
                numeric: true,
                sensitivity: 'base',
            }));
            group.forEach((feature, index) => {
                feature.properties = {
                    ...feature.properties,
                    _map_line_offset: (index - ((group.length - 1) / 2)) * 7,
                };
            });
        });

        return features;
    }

    function canonicalLineDirection(coordinates) {
        const normalized = coordinates.map((coordinate) => coordinate
            .slice(0, 2)
            .map((value) => Number(value).toFixed(7))
            .join(','));
        const forward = normalized.join(';');
        const reverse = [...normalized].reverse().join(';');

        const shouldReverse = forward.localeCompare(reverse) > 0;

        return {
            key: shouldReverse ? reverse : forward,
            reverse: shouldReverse,
        };
    }

    function lineStableId(feature) {
        return String(feature.properties?.id || feature.id || feature.properties?.fiber_code || 'line');
    }

    function appendSplitterPortLinks(features, overlays, overlayIds) {
        const splitters = features.filter((feature) => feature.geometry.type === 'Point'
            && feature.properties.component_type === 'splitter');
        const portsByLabel = new Map();

        splitters.forEach((splitter) => {
            const name = featureDisplayName(splitter);
            buildSplitterRowsForFeature(splitter).forEach((row) => {
                portsByLabel.set(`${name} ${row.port}`.toLowerCase(), { splitter, row });
            });
        });

        splitters.forEach((splitter) => {
            if (!isFeatureVisible(splitter)) return;

            buildSplitterRowsForFeature(splitter).forEach((row) => {
                const target = portsByLabel.get(String(row.connected_fiber || '').trim().toLowerCase());
                if (!target || target.splitter === splitter || !isFeatureVisible(target.splitter)) return;

                const splitterId = String(splitter.properties.id || splitter.id);
                const targetId = String(target.splitter.properties.id || target.splitter.id);
                const overlayId = [splitterId, row.port, targetId, target.row.port].sort().join('-');
                if (overlayIds.has(overlayId)) return;
                overlayIds.add(overlayId);

                overlays.push({
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: [target.splitter.geometry.coordinates, splitter.geometry.coordinates],
                    },
                    properties: {
                        id: `splitter-link-${overlayId}`,
                        link_type: 'splitter_core',
                        color_hex: target.row.color_hex || coreColorHex(row.connected_core) || row.color_hex || '#f79009',
                    },
                });
            });
        });
    }

    function coreColorHex(colorName) {
        const normalized = String(colorName || '').trim().toLowerCase();
        return corePalette.find(([name]) => name.toLowerCase() === normalized)?.[1] || '';
    }

    function closestCoordinateOnLine(point, coordinates) {
        if (!Array.isArray(coordinates) || coordinates.length === 0) return point;

        let closest = coordinates[0];
        let closestDistance = Number.POSITIVE_INFINITY;
        for (let index = 0; index < coordinates.length - 1; index++) {
            const projected = closestPointOnSegment(point, coordinates[index], coordinates[index + 1]);
            const distance = squaredDistance(point, projected);
            if (distance < closestDistance) {
                closest = projected;
                closestDistance = distance;
            }
        }

        return closest;
    }

    function closestPointOnSegment(point, start, end) {
        const dx = end[0] - start[0];
        const dy = end[1] - start[1];
        const lengthSquared = dx * dx + dy * dy;
        if (lengthSquared === 0) return start;

        const t = Math.max(0, Math.min(1, ((point[0] - start[0]) * dx + (point[1] - start[1]) * dy) / lengthSquared));
        return [start[0] + t * dx, start[1] + t * dy];
    }

    function squaredDistance(a, b) {
        const dx = a[0] - b[0];
        const dy = a[1] - b[1];
        return dx * dx + dy * dy;
    }

    function showPathMarkers(featureId) {
        const feature = state.features.get(featureId);
        clearPathMarkers();

        if (!feature || feature.geometry.type !== 'LineString' || !isFeatureVisible(feature)) {
            return;
        }

        const coordinates = feature.geometry.coordinates;
        coordinates.forEach((coordinate, index) => {
            const isEndpoint = index === 0 || index === coordinates.length - 1;
            const marker = createPathMarker(isEndpoint ? 'endpoint' : 'bend')
                .setLngLat(coordinate)
                .addTo(state.map);
            const markerElement = marker.getElement();
            setPathMarkerLabel(markerElement, isEndpoint ? (index === 0 ? 'A' : 'Z') : '');
            markerElement.classList.toggle('a-end', index === 0);
            markerElement.classList.toggle('z-end', index === coordinates.length - 1);
            markerElement.addEventListener('click', (event) => {
                event.stopPropagation();
                if (isEndpoint) {
                    state.pendingEndpointLink = { featureId, coordinateIndex: index };
                    markerElement.classList.add('linking');
                    setStatus('Now click a TJ Box to bind this fiber endpoint.');
                } else {
                    selectBendPoint(featureId, index, markerElement);
                }
            });
            markerElement.addEventListener('mouseenter', () => {
                if (!isEndpoint) {
                    setStatus('Hovering a bend point: click to select it, then press Backspace/Delete to remove.');
                } else {
                    setStatus(`${index === 0 ? 'A' : 'Z'} endpoint: drag to move or click to link with a TJ Box.`);
                }
            });
            markerElement.addEventListener('dblclick', (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (isEndpoint) {
                    openFiberCorePanel(featureId, index, marker.getLngLat());
                }
            });

            marker.on('drag', () => {
                const lngLat = marker.getLngLat();
                moveFiberVertex(feature, index, [lngLat.lng, lngLat.lat]);
                if (isEndpoint) {
                    markerElement.classList.add('linking');
                    const tjFeature = nearestTjBox(lngLat);
                    state.linkTargetFeatureId = tjFeature?.properties.id || null;
                    updateLinkTargetSource();
                }
                refreshSources();
            });

            marker.on('dragend', () => {
                markerElement.classList.remove('linking');
                if (isEndpoint) {
                    const linked = tryLinkEndpointNearTjBox(featureId, index, marker.getLngLat());
                    if (linked) {
                        clearLinkTarget();
                        return;
                    }
                }

                clearLinkTarget();
                state.dirty = true;
                persistTopology();
            });

            state.pathMarkers.push({ marker, featureId, kind: 'vertex' });
        });

        for (let index = 0; index < coordinates.length - 1; index++) {
            const midpoint = midpointCoordinate(coordinates[index], coordinates[index + 1]);
            const marker = createPathMarker('midpoint')
                .setLngLat(midpoint)
                .addTo(state.map);
            setPathMarkerLabel(marker.getElement(), '+');

            marker.on('dragend', () => {
                const lngLat = marker.getLngLat();
                feature.geometry.coordinates.splice(index + 1, 0, [lngLat.lng, lngLat.lat]);
                feature.properties.length_meters = Number(lineLengthMeters(feature.geometry.coordinates).toFixed(2));
                state.dirty = true;
                refreshSources();
                showPathMarkers(featureId);
                persistTopology();
                setStatus('Fiber path point added and saved.');
            });

            state.pathMarkers.push({ marker, featureId, kind: 'midpoint' });
        }

        setStatus('Fiber edit mode: drag solid points to move path; drag small middle points to add bends.');
    }

    function clearPathMarkers() {
        state.pathMarkers.forEach((item) => item.marker.remove());
        state.pathMarkers = [];
        clearSelectedBendPoint();
    }

    function currentPathFeatureId() {
        return state.pathMarkers.find((item) => item.kind === 'vertex')?.featureId || null;
    }

    function createPathMarker(kind) {
        const element = document.createElement('div');
        element.className = `path-marker ${kind}`;
        element.innerHTML = '<button type="button" class="path-handle"><span class="marker-dot"><span class="marker-label"></span></span><span class="marker-tooltip"></span></button>';
        const labels = {
            bend: 'Click to select bend point. Drag to move.',
            endpoint: 'Click to link endpoint. Double-click for core link.',
            midpoint: 'Drag to add a fiber path point',
        };
        element.querySelector('.path-handle').title = labels[kind] || labels.bend;
        element.querySelector('.marker-tooltip').textContent = labels[kind] || labels.bend;

        return new maplibregl.Marker({ element, draggable: true, anchor: 'center', offset: [0, 0] });
    }

    function setPathMarkerLabel(element, label) {
        const labelElement = element.querySelector('.marker-label');
        if (labelElement) {
            labelElement.textContent = label;
        }
    }

    function openFiberCorePanel(featureId, coordinateIndex, lngLat) {
        const feature = state.features.get(featureId);
        if (!feature || feature.geometry.type !== 'LineString') return;

        closeFiberCorePanel();

        const point = state.map.project(lngLat);
        const panel = document.createElement('section');
        panel.className = 'fiber-core-panel';
        panel.style.left = `${Math.min(Math.max(point.x + 16, 12), state.map.getContainer().clientWidth - 380)}px`;
        panel.style.top = `${Math.min(Math.max(point.y + 16, 12), state.map.getContainer().clientHeight - 420)}px`;
        panel.innerHTML = fiberCorePanelHtml(feature, coordinateIndex);
        state.map.getContainer().appendChild(panel);
        state.corePanel = panel;

        setupSearchableDropdown(
            panel.querySelector('[data-searchable-dropdown="splitter_port"]'),
            splitterPortOptions(),
            'Type splitter name or port'
        );

        panel.querySelector('.core-panel-close').addEventListener('click', closeFiberCorePanel);
        panel.querySelectorAll('[data-core-link]').forEach((button) => {
            button.addEventListener('click', () => {
                const splitterPort = panel.querySelector('input[name="splitter_port"]')?.value;
                if (!splitterPort) {
                    setStatus('Choose a splitter port from the dropdown first.');
                    return;
                }
                linkFiberCoreToSplitter(featureId, Number(button.dataset.coreIndex), button.dataset.coreLink, splitterPort);
            });
        });

        setStatus('Select a core color, choose splitter IN/OUT, then link.');
    }

    function closeFiberCorePanel() {
        if (state.corePanel) {
            state.corePanel.remove();
            state.corePanel = null;
        }
    }

    function openOltPortPanel(featureId) {
        const device = state.features.get(featureId);
        if (!isPortLinkDevice(device)) return;

        closeOltPortPanel();

        const point = state.map.project(device.geometry.coordinates);
        const panel = document.createElement('section');
        panel.className = 'olt-port-panel';
        panel.style.left = `${Math.min(Math.max(point.x + 16, 12), state.map.getContainer().clientWidth - 400)}px`;
        panel.style.top = `${Math.min(Math.max(point.y + 16, 12), state.map.getContainer().clientHeight - 460)}px`;
        panel.innerHTML = oltPortPanelHtml(device);
        state.map.getContainer().appendChild(panel);
        state.oltPanel = panel;

        panel.querySelector('.olt-panel-close').addEventListener('click', closeOltPortPanel);
        panel.querySelectorAll('[data-port-link]').forEach((button) => {
            button.addEventListener('dragstart', (event) => {
                state.pendingPortLink = { deviceId: featureId, port: button.dataset.portLink };
                event.dataTransfer.effectAllowed = 'link';
                event.dataTransfer.setData('text/plain', JSON.stringify(state.pendingPortLink));
                setStatus(`Drag ${button.dataset.portLink} onto a fiber cable to link a core.`);
            });
            button.addEventListener('dragend', () => {
                state.pendingPortLink = null;
            });
        });
        panel.querySelectorAll('[data-port-tree]').forEach((button) => {
            button.addEventListener('click', () => {
                renderOltPortTree(featureId, button.dataset.portTree);
            });
        });
        panel.querySelectorAll('[data-port-direct]').forEach((button) => {
            button.addEventListener('click', () => {
                openDirectDeviceLinkPanel(featureId, button.dataset.portDirect);
            });
        });
        panel.querySelectorAll('[data-port-unlink]').forEach((button) => {
            button.addEventListener('click', () => {
                unlinkDevicePortFromFiberCore(featureId, button.dataset.portUnlink);
            });
        });
    }

    function closeOltPortPanel() {
        if (state.oltPanel) {
            state.oltPanel.remove();
            state.oltPanel = null;
        }
        state.pendingPortLink = null;
    }

    function oltPortPanelHtml(device) {
        const links = devicePortLinks(device);
        const rows = devicePorts(device).map((port) => {
            const link = links[port];
            const directAction = !link || isDirectDeviceLink(link)
                ? `<button type="button" data-port-direct="${escapeHtml(port)}">${link ? 'Edit' : 'Direct'}</button>`
                : '';
            return `
                <div class="olt-port-row">
                    <button type="button" draggable="true" data-port-link="${escapeHtml(port)}">${escapeHtml(link ? port : `Make Link: ${port}`)}</button>
                    <span>${link ? escapeHtml(deviceLinkDescription(link)) : 'No link'}</span>
                    ${directAction}
                    ${link ? `<button type="button" data-port-unlink="${escapeHtml(port)}">Unlink</button>` : ''}
                    <button type="button" data-port-tree="${escapeHtml(port)}" ${link ? '' : 'disabled'}>Tree</button>
                </div>
            `;
        }).join('');
        return `
            <div class="olt-panel-head">
                <div>
                    <strong>${escapeHtml(featureDisplayName(device))}</strong>
                    <span>Drag a port onto another device or fiber cable, or use Direct searchable list</span>
                </div>
                <button type="button" class="olt-panel-close">x</button>
            </div>
            <div class="olt-port-list">${rows}</div>
            <div class="olt-tree-view" data-olt-tree-view></div>
        `;
    }

    function deviceLinkDescription(link) {
        if (isDirectDeviceLink(link)) {
            const target = state.features.get(link.target_device_id);
            return `${target ? featureDisplayName(target) : 'Router'} ${link.target_port || ''} / ${link.medium || 'Fiber'}`;
        }

        return `${link.fiber_code || 'Fiber'} / ${link.color_name || `Core ${link.core}`}`;
    }

    function openDirectDeviceLinkPanel(deviceId, sourcePort, preferredTargetId = null) {
        const device = state.features.get(deviceId);
        if (!isPortLinkDevice(device)) return;

        const existingLink = devicePortLinks(device)[sourcePort];
        const options = directDevicePortOptions(deviceId, existingLink);
        const selectedMedium = existingLink?.medium === 'Copper' ? 'Copper' : 'Fiber';
        if (options.length === 0) {
            setStatus('No free endpoint is available on another device.');
            return;
        }

        closeOltPortPanel();
        closeFiberCorePanel();
        const point = state.map.project(device.geometry.coordinates);
        const panel = document.createElement('section');
        panel.className = 'fiber-core-panel direct-router-panel';
        panel.style.left = `${Math.min(Math.max(point.x + 16, 12), state.map.getContainer().clientWidth - 380)}px`;
        panel.style.top = `${Math.min(Math.max(point.y + 16, 12), state.map.getContainer().clientHeight - 300)}px`;
        panel.innerHTML = `
            <div class="core-panel-head">
                <div>
                    <strong>${escapeHtml(devicePortLabel(device, sourcePort))}</strong>
                    <span>Direct Equipment Link</span>
                </div>
                <button type="button" class="core-panel-close">x</button>
            </div>
            <label class="core-panel-select">Target Device / Port
                ${searchableDropdownHtml('direct_device_port', 'Type device name or port')}
            </label>
            <label class="core-panel-select">Medium
                ${searchableDropdownHtml('direct_link_medium', 'Type or choose medium', selectedMedium, selectedMedium, true)}
            </label>
            <label class="core-panel-select">Link Color
                <input type="color" name="direct_link_color" value="${escapeHtml(existingLink?.color_hex || mediumLinkColor(existingLink?.medium || 'Fiber'))}">
            </label>
            <button type="button" class="btn" data-save-direct-device-link>Save Direct Link</button>
        `;
        state.map.getContainer().appendChild(panel);
        state.corePanel = panel;

        panel.querySelector('.core-panel-close').addEventListener('click', closeFiberCorePanel);
        setupSearchableDropdown(
            panel.querySelector('[data-searchable-dropdown="direct_device_port"]'),
            options,
            'Type device name or port'
        );
        setupSearchableDropdown(
            panel.querySelector('[data-searchable-dropdown="direct_link_medium"]'),
            ['Fiber', 'Copper'].map((medium) => ({ value: medium, label: medium, search: medium })),
            'Type or choose medium',
            null,
            { allowCustom: true }
        );
        const targetInput = panel.querySelector('[data-dropdown-search]');
        const targetValueInput = panel.querySelector('input[name="direct_device_port"]');
        const selectedOption = options.find((option) => option.value === directLinkTargetValue(existingLink))
            || options.find((option) => preferredTargetId && option.feature_id === preferredTargetId);
        if (selectedOption) {
            targetInput.value = selectedOption.label;
            targetValueInput.value = selectedOption.value;
        }
        const mediumInput = panel.querySelector('input[name="direct_link_medium"]');
        const colorInput = panel.querySelector('input[name="direct_link_color"]');
        mediumInput.addEventListener('change', () => {
            colorInput.value = mediumLinkColor(mediumInput.value);
        });
        panel.querySelector('[data-save-direct-device-link]').addEventListener('click', () => {
            const targetValue = targetValueInput?.value;
            const medium = panel.querySelector('input[name="direct_link_medium"]')?.value;
            const colorHex = colorInput?.value;
            if (!targetValue) {
                setStatus('Choose a target device port from the dropdown.');
                return;
            }

            saveDirectDeviceLink(deviceId, sourcePort, targetValue, medium, colorHex);
            closeFiberCorePanel();
        });
        setStatus('Choose any target device port, medium, and link color.');
    }

    function directDevicePortOptions(sourceDeviceId, existingLink = null) {
        const options = [];
        state.features.forEach((feature) => {
            if (!isPortLinkDevice(feature)
                || String(feature.properties.id || feature.id) === String(sourceDeviceId)) return;

            const links = devicePortLinks(feature);
            devicePorts(feature).filter((port) => !links[port]
                || (String(feature.properties.id || feature.id) === String(existingLink?.target_device_id)
                    && port === existingLink?.target_port)).forEach((port) => {
                options.push({
                    value: `${feature.properties.id || feature.id}::${port}`,
                    label: `${featureDisplayName(feature)} / ${port}`,
                    search: `${featureDisplayName(feature)} ${port}`,
                    feature_id: String(feature.properties.id || feature.id),
                });
            });
        });

        return options.sort((first, second) => first.label.localeCompare(second.label, undefined, {
            numeric: true,
            sensitivity: 'base',
        }));
    }

    function saveDirectDeviceLink(sourceDeviceId, sourcePort, targetValue, medium, colorHex) {
        const separatorIndex = targetValue.indexOf('::');
        if (separatorIndex < 1) return;

        const targetDeviceId = targetValue.slice(0, separatorIndex);
        const targetPort = targetValue.slice(separatorIndex + 2);
        const sourceDevice = state.features.get(sourceDeviceId);
        const targetDevice = state.features.get(targetDeviceId);
        if (!isPortLinkDevice(sourceDevice) || !isPortLinkDevice(targetDevice)) return;

        const existingLink = devicePortLinks(sourceDevice)[sourcePort];
        const targetExistingLink = devicePortLinks(targetDevice)[targetPort];
        const targetIsCurrentPeer = isDirectDeviceLink(existingLink)
            && String(existingLink.target_device_id) === String(targetDeviceId)
            && existingLink.target_port === targetPort
            && isDirectDeviceLink(targetExistingLink)
            && String(targetExistingLink.target_device_id) === String(sourceDeviceId)
            && targetExistingLink.target_port === sourcePort;
        if (targetExistingLink && !targetIsCurrentPeer) {
            setStatus('The selected target port is already linked.');
            return;
        }
        if (existingLink && isDirectDeviceLink(existingLink)) {
            removeDirectDeviceLink(sourceDevice, sourcePort, existingLink);
        }
        if (devicePortLinks(sourceDevice)[sourcePort] || devicePortLinks(targetDevice)[targetPort]) {
            setStatus('One of the selected device ports is already linked.');
            return;
        }

        const normalizedMedium = medium === 'Copper' ? 'Copper' : 'Fiber';
        const normalizedColor = /^#[0-9a-f]{6}$/i.test(colorHex || '') ? colorHex : mediumLinkColor(normalizedMedium);
        assignDirectDeviceLink(sourceDevice, sourcePort, targetDevice, targetPort, normalizedMedium, normalizedColor);

        state.dirty = true;
        refreshSources();
        persistTopology();
        openOltPortPanel(sourceDeviceId);
        setStatus(`${devicePortLabel(sourceDevice, sourcePort)} linked directly to ${devicePortLabel(targetDevice, targetPort)} via ${normalizedMedium}.`);
    }

    function assignDirectDeviceLink(sourceDevice, sourcePort, targetDevice, targetPort, medium, colorHex) {
        const sourceDeviceId = String(sourceDevice.properties.id || sourceDevice.id);
        const targetDeviceId = String(targetDevice.properties.id || targetDevice.id);
        sourceDevice.properties.port_links = {
            ...(sourceDevice.properties.port_links || {}),
            [sourcePort]: {
                link_type: 'direct_device',
                target_device_id: targetDeviceId,
                target_port: targetPort,
                medium,
                color_hex: colorHex,
            },
        };
        targetDevice.properties.port_links = {
            ...(targetDevice.properties.port_links || {}),
            [targetPort]: {
                link_type: 'direct_device',
                target_device_id: sourceDeviceId,
                target_port: sourcePort,
                medium,
                color_hex: colorHex,
            },
        };
        updateDirectLinkPortRow(sourceDevice, sourcePort, targetDevice, targetPort, medium, colorHex);
        updateDirectLinkPortRow(targetDevice, targetPort, sourceDevice, sourcePort, medium, colorHex);
    }

    function updateDirectLinkPortRow(device, port, peer, peerPort, medium, colorHex) {
        if (device.properties.component_type !== 'splitter') return;

        const rows = buildSplitterRowsForFeature(device);
        const row = rows.find((item) => item.port === port);
        if (!row) return;

        row.connected_fiber = devicePortLabel(peer, peerPort);
        row.connected_core = row.connected_core || row.color_name;
        row.medium = medium;
        row.color_hex = colorHex;
        row.note = compactJoin([row.note, 'Synchronized direct link']);
        device.properties.splitter_ports = rows;
    }

    function clearDirectLinkPortRow(device, port) {
        if (device.properties.component_type !== 'splitter') return;

        const rows = buildSplitterRowsForFeature(device);
        const row = rows.find((item) => item.port === port);
        if (!row) return;

        row.connected_fiber = '';
        row.connected_core = '';
        row.note = '';
        device.properties.splitter_ports = rows;
    }

    function clearDirectDeviceLinksForFeature(feature) {
        Object.entries(devicePortLinks(feature)).forEach(([port, link]) => {
            if (isDirectDeviceLink(link)) {
                removeDirectDeviceLink(feature, port, link);
            }
        });
    }

    function repairLegacySplitterLinks() {
        const endpoints = new Map();
        state.features.forEach((feature) => {
            if (!isPortLinkDevice(feature)) return;
            devicePorts(feature).forEach((port) => {
                endpoints.set(devicePortLabel(feature, port).toLowerCase(), { feature, port });
            });
        });

        let repaired = false;
        state.features.forEach((splitter) => {
            if (splitter.geometry.type !== 'Point' || splitter.properties.component_type !== 'splitter') return;

            const rows = buildSplitterRowsForFeature(splitter);
            let splitterChanged = false;
            rows.forEach((row) => {
                const target = endpoints.get(String(row.connected_fiber || '').trim().toLowerCase());
                if (!target || target.feature === splitter) return;

                const sourceLinks = devicePortLinks(splitter);
                const targetLinks = devicePortLinks(target.feature);
                if (sourceLinks[row.port] || targetLinks[target.port]) return;

                const medium = row.medium === 'Copper' ? 'Copper' : 'Fiber';
                const colorHex = /^#[0-9a-f]{6}$/i.test(row.color_hex || '')
                    ? row.color_hex
                    : mediumLinkColor(medium);
                assignDirectDeviceLink(splitter, row.port, target.feature, target.port, medium, colorHex);
                row.medium = medium;
                splitterChanged = true;
                repaired = true;

            });

            if (splitterChanged) {
                splitter.properties.splitter_ports = rows;
            }
        });

        return repaired;
    }

    /**
     * For each splitter OUT row that points at an endpoint which is not another
     * port-link device (e.g. a customer pin), draw a 2-core drop fiber from the
     * splitter to that endpoint automatically, and remove the auto drops whose
     * row was cleared or retargeted.
     */
    function autoDrawSplitterDropFibers(splitter) {
        if (!splitter || splitter.geometry?.type !== 'Point' || splitter.properties.component_type !== 'splitter') return;

        const splitterId = String(splitter.properties.id || splitter.id);
        const splitterCoords = splitter.geometry.coordinates;

        const endpointCoords = new Map();
        state.customerFeatures.forEach((feature) => {
            if (Array.isArray(feature.geometry?.coordinates)) {
                endpointCoords.set(customerEndpointLabel(feature.properties || {}).toLowerCase(), {
                    coords: feature.geometry.coordinates.slice(),
                    customer: feature.properties || {},
                });
            }
        });
        state.features.forEach((feature) => {
            if (feature.geometry?.type === 'Point' && feature !== splitter && isPortLinkDevice(feature)) {
                // Port-link devices are handled by repairLegacySplitterLinks; skip.
                endpointCoords.delete(devicePortLabel(feature, '').trim().toLowerCase());
            }
        });

        const rows = buildSplitterRowsForFeature(splitter);
        const wantedPorts = new Map();
        rows.forEach((row) => {
            if (row.port === 'IN') return;
            const label = String(row.connected_fiber || '').trim();
            if (!label) return;
            const match = endpointCoords.get(label.toLowerCase());
            if (match) {
                wantedPorts.set(row.port, { row, ...match });
            }
        });

        // Drop stale auto fibers for this splitter.
        [...state.features.values()].forEach((feature) => {
            const src = feature.properties?.auto_drop_source;
            if (typeof src !== 'string' || !src.startsWith(`${splitterId}|`)) return;
            const port = src.slice(splitterId.length + 1);
            if (!wantedPorts.has(port)) {
                state.features.delete(feature.properties.id || feature.id);
            }
        });

        wantedPorts.forEach(({ row, coords, customer }, port) => {
            const source = `${splitterId}|${port}`;
            let fiber = [...state.features.values()].find((feature) => feature.properties?.auto_drop_source === source);
            const coreColor = row.connected_core || row.color_name || 'Blue';
            const aLabel = `${featureDisplayName(splitter)} ${port}`;

            if (fiber) {
                fiber.geometry.coordinates = [splitterCoords.slice(), coords.slice()];
                fiber.properties.a_end_device_port = aLabel;
                fiber.properties.z_end_device_port = row.connected_fiber;
                fiber.properties.length_meters = Number(lineLengthMeters(fiber.geometry.coordinates).toFixed(2));
                return;
            }

            const id = uuid();
            fiber = {
                type: 'Feature',
                id,
                geometry: { type: 'LineString', coordinates: [splitterCoords.slice(), coords.slice()] },
                properties: {
                    id,
                    feature_type: 'link',
                    component_type: 'fiber_cable',
                    ...defaultPropertiesFor('fiber_cable'),
                    fiber_code: `DROP-${featureDisplayName(splitter)}-${port}`.replace(/\s+/g, ''),
                    core_count: '2F',
                    cable_type: 'Overhead',
                    a_end_device_port: aLabel,
                    a_end_core_color: coreColor,
                    z_end_device_port: row.connected_fiber,
                    z_end_core_color: coreColor,
                    z_end_customer_id: customer.customer_id ? String(customer.customer_id) : undefined,
                    z_end_customer_name: customer.customer_id ? row.connected_fiber : undefined,
                    auto_drop_source: source,
                    length_meters: Number(lineLengthMeters([splitterCoords, coords]).toFixed(2)),
                    note: `Auto drop from ${aLabel}`,
                },
            };
            state.features.set(id, fiber);
        });
    }

    function mediumLinkColor(medium) {
        return medium === 'Copper' ? '#b54708' : '#06b6d4';
    }

    function isDirectDeviceLink(link) {
        return ['direct_router', 'direct_device'].includes(link?.link_type);
    }

    function directLinkTargetValue(link) {
        return isDirectDeviceLink(link) && link.target_device_id && link.target_port
            ? `${link.target_device_id}::${link.target_port}`
            : '';
    }

    function handlePortDrop(event) {
        if (!state.pendingPortLink) return;

        event.preventDefault();
        const rect = state.map.getContainer().getBoundingClientRect();
        const point = [event.clientX - rect.left, event.clientY - rect.top];
        const features = state.map.queryRenderedFeatures(point, {
            layers: ['network-nodes-circle', 'network-links-line-hit'],
        });
        const targetDeviceId = features.find((feature) => feature.layer?.id === 'network-nodes-circle')?.properties?.id;
        if (targetDeviceId && String(targetDeviceId) !== String(state.pendingPortLink.deviceId)) {
            openDirectDeviceLinkPanel(
                state.pendingPortLink.deviceId,
                state.pendingPortLink.port,
                String(targetDeviceId)
            );
            state.pendingPortLink = null;
            return;
        }

        const fiberId = features.find((feature) => feature.layer?.id === 'network-links-line-hit')?.properties?.id;
        const fiber = fiberId ? state.features.get(fiberId) : null;

        if (!fiber || fiber.geometry.type !== 'LineString') {
            setStatus('Drop the port onto another device or a fiber cable.');
            state.pendingPortLink = null;
            return;
        }

        linkDevicePortToFiberCore(state.pendingPortLink.deviceId, state.pendingPortLink.port, fiber);
        state.pendingPortLink = null;
    }

    function linkDevicePortToFiberCore(deviceId, port, fiber) {
        const device = state.features.get(deviceId);
        if (!isPortLinkDevice(device)) return;

        closeFiberCorePanel();
        const rows = buildFiberCoreRowsForFeature(fiber);
        const point = state.map.project(device.geometry.coordinates);
        const panel = document.createElement('section');
        panel.className = 'fiber-core-panel core-choice-panel';
        panel.style.left = `${Math.min(Math.max(point.x + 16, 12), state.map.getContainer().clientWidth - 380)}px`;
        panel.style.top = `${Math.min(Math.max(point.y + 16, 12), state.map.getContainer().clientHeight - 220)}px`;
        panel.innerHTML = `
            <div class="core-panel-head">
                <div>
                    <strong>${escapeHtml(devicePortLabel(device, port))}</strong>
                    <span>Choose a core from ${escapeHtml(fiber.properties.fiber_code || 'Fiber')}</span>
                </div>
                <button type="button" class="core-panel-close">x</button>
            </div>
            <label class="core-panel-select">Fiber Core
                ${searchableDropdownHtml('fiber_core', 'Type core number or color')}
            </label>
        `;
        state.map.getContainer().appendChild(panel);
        state.corePanel = panel;

        panel.querySelector('.core-panel-close').addEventListener('click', closeFiberCorePanel);
        setupSearchableDropdown(
            panel.querySelector('[data-searchable-dropdown="fiber_core"]'),
            rows.map((row) => ({
                value: String(row.core),
                label: `C${row.core} ${row.color_name}`,
                color_hex: row.color_hex,
                search: `${row.core} core ${row.color_name}`,
            })),
            'Type core number or color',
            (coreNumber) => {
                completeDevicePortCoreLink(deviceId, port, fiber, coreNumber);
                closeFiberCorePanel();
            }
        );
        setStatus('Type a core number or color, then choose it from the dropdown.');
    }

    function completeDevicePortCoreLink(deviceId, port, fiber, input) {
        const device = state.features.get(deviceId);
        if (!isPortLinkDevice(device)) return;

        const endpointLabel = devicePortLabel(device, port);
        let rows = buildFiberCoreRowsForFeature(fiber);
        let row = findFiberCoreRow(rows, input);
        if (!row) {
            setStatus('No matching fiber core found.');
            return;
        }

        clearExistingPortCoreReference(device, port, endpointLabel);
        rows = buildFiberCoreRowsForFeature(fiber);
        row = findFiberCoreRow(rows, input);

        const deviceName = featureDisplayName(device);
        row.in_point = endpointLabel;
        row.note = compactJoin([row.note, 'Port linked']);
        fiber.properties.core_mappings = rows;
        const link = {
            fiber_id: fiber.properties.id || fiber.id,
            fiber_code: fiber.properties.fiber_code || 'Fiber',
            core: row.core,
            color_name: row.color_name,
            color_hex: row.color_hex,
        };
        device.properties.port_links = {
            ...(device.properties.port_links || {}),
            [port]: link,
        };
        if (device.properties.component_type === 'olt') {
            device.properties.olt_port_links = {
                ...(device.properties.olt_port_links || {}),
                [port]: link,
            };
        }
        if (device.properties.component_type === 'splitter') {
            updateSplitterPortLink(device, port, fiber, row);
        }

        state.dirty = true;
        refreshSources();
        persistTopology();
        openOltPortPanel(deviceId);
        setStatus(`${deviceName} ${port} linked to ${fiber.properties.fiber_code || 'fiber'} ${row.color_name} core.`);
    }

    function unlinkDevicePortFromFiberCore(deviceId, port) {
        const device = state.features.get(deviceId);
        if (!isPortLinkDevice(device)) return;

        const link = devicePortLinks(device)[port];
        if (!link) return;

        if (isDirectDeviceLink(link)) {
            removeDirectDeviceLink(device, port, link);
            state.dirty = true;
            refreshSources();
            persistTopology();
            openOltPortPanel(deviceId);
            setStatus(`${devicePortLabel(device, port)} direct link removed.`);
            return;
        }

        const fiber = state.features.get(link.fiber_id);
        const endpointLabel = devicePortLabel(device, port);
        if (fiber) {
            fiber.properties.core_mappings = buildFiberCoreRowsForFeature(fiber).map((row) => {
                if (Number(row.core) === Number(link.core) && row.in_point === endpointLabel) {
                    return { ...row, in_point: '' };
                }
                return row;
            });
        }

        const remainingLinks = { ...(device.properties.port_links || {}) };
        delete remainingLinks[port];
        device.properties.port_links = remainingLinks;

        if (device.properties.component_type === 'olt') {
            const remainingOltLinks = { ...(device.properties.olt_port_links || {}) };
            delete remainingOltLinks[port];
            device.properties.olt_port_links = remainingOltLinks;
        }
        if (device.properties.component_type === 'splitter') {
            clearSplitterPortLink(device, port);
        }

        state.dirty = true;
        refreshSources();
        persistTopology();
        openOltPortPanel(deviceId);
        setStatus(`${endpointLabel} unlinked from fiber core.`);
    }

    function clearExistingPortCoreReference(device, port, endpointLabel) {
        const existingLink = devicePortLinks(device)[port];
        if (!existingLink) return;

        if (isDirectDeviceLink(existingLink)) {
            removeDirectDeviceLink(device, port, existingLink);
            return;
        }

        const existingFiber = state.features.get(existingLink.fiber_id);
        if (!existingFiber) return;

        existingFiber.properties.core_mappings = buildFiberCoreRowsForFeature(existingFiber).map((row) => {
            if (Number(row.core) === Number(existingLink.core) && row.in_point === endpointLabel) {
                return { ...row, in_point: '' };
            }
            return row;
        });
    }

    function removeDirectDeviceLink(device, port, link) {
        const remainingSourceLinks = { ...(device.properties.port_links || {}) };
        delete remainingSourceLinks[port];
        device.properties.port_links = remainingSourceLinks;
        clearDirectLinkPortRow(device, port);

        const target = state.features.get(link.target_device_id);
        if (!target) return;

        const targetLinks = { ...(target.properties.port_links || {}) };
        const reciprocal = targetLinks[link.target_port];
        if (isDirectDeviceLink(reciprocal)
            && String(reciprocal.target_device_id) === String(device.properties.id || device.id)
            && reciprocal.target_port === port) {
            delete targetLinks[link.target_port];
            target.properties.port_links = targetLinks;
            clearDirectLinkPortRow(target, link.target_port);
        }
    }

    function isPortLinkDevice(feature) {
        return feature
            && feature.geometry.type === 'Point'
            && ['router', 'switch', 'olt', 'splitter', 'tj_box', 'onu'].includes(feature.properties.component_type);
    }

    function devicePorts(feature) {
        if (feature.properties.component_type === 'splitter') {
            const count = Number(String(feature.properties.splitter_type || '1:8').split(':')[1] || 8);
            return ['IN', ...Array.from({ length: count }, (_, index) => `OUT-${String(index + 1).padStart(2, '0')}`)];
        }

        if (feature.properties.component_type === 'tj_box') {
            return Array.from({ length: 16 }, (_, index) => `Port ${index + 1}`);
        }

        if (feature.properties.component_type === 'onu') {
            return ['PON', 'LAN 1', 'LAN 2', 'LAN 3', 'LAN 4'];
        }

        const totalPorts = Math.max(1, Number(feature.properties.total_ports || (feature.properties.component_type === 'olt' ? 8 : 24)));
        const prefix = feature.properties.component_type === 'olt' ? 'PON' : 'Port';
        return Array.from({ length: totalPorts }, (_, index) => `${prefix} ${index + 1}`);
    }

    function devicePortLinks(feature) {
        return {
            ...(feature.properties.olt_port_links || {}),
            ...(feature.properties.port_links || {}),
        };
    }

    function devicePortLabel(feature, port) {
        return `${featureDisplayName(feature)} ${port}`;
    }

    function updateSplitterPortLink(splitter, port, fiber, coreRow) {
        const rows = buildSplitterRowsForFeature(splitter);
        const row = rows.find((item) => item.port === port);
        if (!row) return;

        row.connected_fiber = fiber.properties.fiber_code || fiber.properties.id || 'Fiber';
        row.connected_core = `${coreRow.color_name} C${coreRow.core}`;
        row.note = `Linked to ${fiber.properties.fiber_code || 'fiber'}`;
        splitter.properties.splitter_ports = rows;
    }

    function clearSplitterPortLink(splitter, port) {
        const rows = buildSplitterRowsForFeature(splitter);
        const row = rows.find((item) => item.port === port);
        if (!row) return;

        row.connected_fiber = '';
        row.connected_core = '';
        row.note = '';
        splitter.properties.splitter_ports = rows;
    }

    function linkOltPortToFiberCore(oltId, port, fiber) {
        linkDevicePortToFiberCore(oltId, port, fiber);
    }

    function findFiberCoreRow(rows, input) {
        const normalized = String(input).trim().toLowerCase();
        return rows.find((row) => String(row.core) === normalized || row.color_name.toLowerCase() === normalized);
    }

    function renderOltPortTree(oltId, port) {
        const olt = state.features.get(oltId);
        const link = olt ? devicePortLinks(olt)[port] : null;
        const container = state.oltPanel?.querySelector('[data-olt-tree-view]');
        if (!container || !link) return;

        if (isDirectDeviceLink(link)) {
            const target = state.features.get(link.target_device_id);
            container.innerHTML = `
                <div class="olt-tree-node"><strong>${escapeHtml(featureDisplayName(olt))} ${escapeHtml(port)}</strong></div>
                <div class="olt-tree-branch">
                    <div class="olt-tree-node">${escapeHtml(link.medium || 'Fiber')} direct link</div>
                    <div class="olt-tree-node">${escapeHtml(target ? featureDisplayName(target) : 'Router')} ${escapeHtml(link.target_port || '')}</div>
                </div>
            `;
            return;
        }

        const fiber = state.features.get(link.fiber_id);
        const rows = fiber ? buildFiberCoreRowsForFeature(fiber) : [];
        const core = rows.find((row) => Number(row.core) === Number(link.core));
        container.innerHTML = `
            <div class="olt-tree-node"><strong>${escapeHtml(featureDisplayName(olt))} ${escapeHtml(port)}</strong></div>
            <div class="olt-tree-branch">
                <div class="olt-tree-node">${escapeHtml(link.fiber_code || 'Fiber')} / ${escapeHtml(link.color_name || `Core ${link.core}`)}</div>
                ${core ? downstreamCoreTreeHtml(fiber, core) : '<div class="olt-tree-empty">Fiber core not found.</div>'}
            </div>
        `;
    }

    function downstreamCoreTreeHtml(fiber, core) {
        const items = [
            core.out_point ? `OUT: ${core.out_point}` : '',
            core.note ? `Note: ${core.note}` : '',
            fiber.properties.endpoint_links?.a ? `A-End: ${formatEndpointLink(fiber.properties.endpoint_links.a)}` : '',
            fiber.properties.endpoint_links?.z ? `Z-End: ${formatEndpointLink(fiber.properties.endpoint_links.z)}` : '',
        ].filter(Boolean);

        return items.length
            ? `<div class="olt-tree-branch">${items.map((item) => `<div class="olt-tree-node">${escapeHtml(item)}</div>`).join('')}</div>`
            : '<div class="olt-tree-empty">No downstream link yet.</div>';
    }

    function fiberCorePanelHtml(feature, coordinateIndex) {
        const rows = buildFiberCoreRowsForFeature(feature);
        const endpointName = coordinateIndex === 0 ? 'A-End' : 'Z-End';

        return `
            <div class="core-panel-head">
                <div>
                    <strong>${escapeHtml(feature.properties.fiber_code || 'Fiber')}</strong>
                    <span>${endpointName} Core Link</span>
                </div>
                <button type="button" class="core-panel-close">x</button>
            </div>
            <label class="core-panel-select">Splitter Port
                ${searchableDropdownHtml('splitter_port', 'Type splitter name or port')}
            </label>
            <div class="core-chip-grid">
                ${rows.map((row) => `
                    <div class="core-chip-row">
                        <span class="core-chip" style="border-color:${escapeHtml(row.color_hex)}">
                            <i style="background:${escapeHtml(row.color_hex)}"></i>
                            C${escapeHtml(String(row.core))} ${escapeHtml(row.color_name)}
                        </span>
                        <button type="button" data-core-index="${row.core}" data-core-link="in_point">IN</button>
                        <button type="button" data-core-index="${row.core}" data-core-link="out_point">OUT</button>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function buildFiberCoreRowsForFeature(feature) {
        const count = Number(String(feature.properties.core_count || '4F').replace('F', '') || 4);
        const existing = mapByKey(feature.properties.core_mappings || [], 'key');

        return Array.from({ length: count }, (_, index) => {
            const palette = corePalette[index % 12];
            const core = index + 1;
            const key = `core-${core}`;
            const saved = existing.get(key) || {};
            return {
                key,
                tube: Math.floor(index / 12) + 1,
                core,
                color_name: saved.color_name || palette[0],
                color_hex: saved.color_hex || palette[1],
                in_point: saved.in_point || '',
                out_point: saved.out_point || '',
                note: saved.note || '',
            };
        });
    }

    function splitterPortOptions() {
        const ports = [];
        state.features.forEach((feature) => {
            if (feature.geometry.type !== 'Point' || feature.properties.component_type !== 'splitter') return;

            const name = feature.properties.name || 'Splitter';
            buildSplitterRowsForFeature(feature).forEach((row) => {
                const label = `${name} ${row.port}`;
                ports.push({
                    label,
                    value: label,
                    color_hex: row.color_hex,
                    search: `${name} ${row.port} ${row.color_name}`,
                });
            });
        });

        return ports.sort((first, second) => first.label.localeCompare(second.label, undefined, {
            numeric: true,
            sensitivity: 'base',
        }));
    }

    function searchableDropdownHtml(name, placeholder, selectedLabel = '', selectedValue = '', required = false) {
        return `
            <span class="searchable-dropdown" data-searchable-dropdown="${escapeHtml(name)}">
                <input type="search" data-dropdown-search role="combobox" aria-autocomplete="list" aria-expanded="false" autocomplete="off" placeholder="${escapeHtml(placeholder)}" value="${escapeHtml(selectedLabel)}" ${required ? 'required' : ''}>
                <input type="hidden" name="${escapeHtml(name)}" data-dropdown-value value="${escapeHtml(selectedValue)}">
                <span class="searchable-dropdown-list" data-dropdown-list role="listbox" hidden></span>
            </span>
        `;
    }

    function setupSearchableDropdown(container, options, placeholder, onSelect = null, settings = {}) {
        if (!container) return;

        const searchInput = container.querySelector('[data-dropdown-search]');
        const valueInput = container.querySelector('[data-dropdown-value]');
        const list = container.querySelector('[data-dropdown-list]');
        const allowCustom = Boolean(settings.allowCustom);
        searchInput.placeholder = placeholder;

        const notifyValueChange = () => {
            valueInput.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const rankedOptions = (query) => {
            const normalizedQuery = String(query || '').trim().toLowerCase();
            return options
                .filter((option) => !normalizedQuery || String(option.search || option.label).toLowerCase().includes(normalizedQuery))
                .sort((first, second) => {
                    const firstLabel = first.label.toLowerCase();
                    const secondLabel = second.label.toLowerCase();
                    const firstStarts = normalizedQuery && firstLabel.startsWith(normalizedQuery) ? 0 : 1;
                    const secondStarts = normalizedQuery && secondLabel.startsWith(normalizedQuery) ? 0 : 1;
                    return firstStarts - secondStarts || first.label.localeCompare(second.label, undefined, {
                        numeric: true,
                        sensitivity: 'base',
                    });
                });
        };

        const renderOptions = () => {
            const matches = rankedOptions(searchInput.value);
            list.innerHTML = matches.length
                ? matches.map((option) => `
                    <button type="button" role="option" data-dropdown-option="${escapeHtml(option.value)}">
                        ${option.color_hex ? `<i style="background:${escapeHtml(option.color_hex)}"></i>` : ''}
                        <span>${escapeHtml(option.label)}</span>
                    </button>
                `).join('')
                : '<em>No matching option</em>';
            list.hidden = false;
            searchInput.setAttribute('aria-expanded', 'true');
        };

        searchInput.addEventListener('focus', renderOptions);
        searchInput.addEventListener('input', () => {
            valueInput.value = allowCustom ? searchInput.value : '';
            notifyValueChange();
            renderOptions();
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                list.hidden = true;
                searchInput.setAttribute('aria-expanded', 'false');
            }
            if (event.key === 'Enter' && !list.hidden) {
                const firstOption = list.querySelector('[data-dropdown-option]');
                if (firstOption) {
                    event.preventDefault();
                    firstOption.click();
                }
            }
        });
        searchInput.addEventListener('blur', () => {
            window.setTimeout(() => {
                list.hidden = true;
                searchInput.setAttribute('aria-expanded', 'false');
            }, 120);
        });
        list.addEventListener('mousedown', (event) => event.preventDefault());
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-dropdown-option]');
            if (!button) return;

            const option = options.find((item) => item.value === button.dataset.dropdownOption);
            if (!option) return;

            searchInput.value = option.label;
            valueInput.value = option.value;
            notifyValueChange();
            list.hidden = true;
            searchInput.setAttribute('aria-expanded', 'false');
            if (onSelect) onSelect(option.value, option);
        });
    }

    function linkFiberCoreToSplitter(featureId, coreNumber, side, splitterPort) {
        const feature = state.features.get(featureId);
        if (!feature || !splitterPort) return;

        const rows = buildFiberCoreRowsForFeature(feature);
        const row = rows.find((item) => item.core === coreNumber);
        if (!row) return;

        row[side] = splitterPort;
        feature.properties.core_mappings = rows;
        feature.properties.splice_details = `${row.color_name} C${row.core} linked to ${splitterPort}`;
        updateSplitterPortFromCoreLink(feature, row, splitterPort);
        state.dirty = true;
        refreshSources();
        persistTopology();
        closeFiberCorePanel();
        setStatus(`${row.color_name} core linked to ${splitterPort}.`);
    }

    function updateSplitterPortFromCoreLink(fiberFeature, coreRow, splitterPort) {
        const splitter = findSplitterByPortLabel(splitterPort);
        if (!splitter) return;

        const port = splitterPort.endsWith(' IN') ? 'IN' : (splitterPort.match(/OUT-\d+/)?.[0] || 'IN');
        const existingRows = buildSplitterRowsForFeature(splitter);
        const row = existingRows.find((item) => item.port === port);
        if (!row) return;

        row.connected_fiber = fiberFeature.properties.fiber_code || fiberFeature.properties.id || 'Fiber';
        row.connected_core = `${coreRow.color_name} C${coreRow.core}`;
        row.note = `Linked from ${fiberFeature.properties.fiber_code || 'fiber'}`;
        splitter.properties.splitter_ports = existingRows;
    }

    function findSplitterByPortLabel(splitterPort) {
        return [...state.features.values()].find((feature) => {
            return feature.geometry.type === 'Point'
                && feature.properties.component_type === 'splitter'
                && splitterPort.startsWith(feature.properties.name || 'Splitter');
        });
    }

    function buildSplitterRowsForFeature(splitter) {
        const existing = mapByKey(splitter.properties.splitter_ports || [], 'port');
        const count = Number(String(splitter.properties.splitter_type || '1:8').split(':')[1] || 8);
        const input = existing.get('IN') || {};
        const rows = [{
            port: 'IN',
            color_name: input.color_name || 'Blue',
            color_hex: input.color_hex || '#1d4ed8',
            medium: input.medium === 'Copper' ? 'Copper' : 'Fiber',
            connected_fiber: input.connected_fiber || '',
            connected_core: input.connected_core || '',
            note: input.note || '',
        }];

        for (let index = 0; index < count; index++) {
            const palette = corePalette[index % 12];
            const port = `OUT-${String(index + 1).padStart(2, '0')}`;
            const saved = existing.get(port) || {};
            rows.push({
                port,
                color_name: saved.color_name || palette[0],
                color_hex: saved.color_hex || palette[1],
                medium: saved.medium === 'Copper' ? 'Copper' : 'Fiber',
                connected_fiber: saved.connected_fiber || '',
                connected_core: saved.connected_core || '',
                note: saved.note || '',
            });
        }

        return rows;
    }

    function midpointCoordinate(from, to) {
        return [(from[0] + to[0]) / 2, (from[1] + to[1]) / 2];
    }

    function linkPendingEndpointToTjBox(tjFeature) {
        const pending = state.pendingEndpointLink;
        const fiber = pending ? state.features.get(pending.featureId) : null;
        if (!fiber || fiber.geometry.type !== 'LineString') {
            state.pendingEndpointLink = null;
            clearLinkTarget();
            return false;
        }

        const port = window.prompt('TJ Box port number for this fiber IN:', suggestedTjPort(tjFeature));
        if (!port) {
            setStatus('Fiber endpoint link cancelled.');
            state.pendingEndpointLink = null;
            clearLinkTarget();
            return false;
        }

        const endpointKey = pending.coordinateIndex === 0 ? 'a' : 'z';
        fiber.properties.endpoint_links = {
            ...(fiber.properties.endpoint_links || {}),
            [endpointKey]: {
                node_id: tjFeature.properties.id,
                node_type: 'tj_box',
                node_name: tjFeature.properties.box_name || tjFeature.properties.name || 'TJ Box',
                port,
            },
        };
        fiber.geometry.coordinates[pending.coordinateIndex] = [...tjFeature.geometry.coordinates];
        fiber.properties.length_meters = Number(lineLengthMeters(fiber.geometry.coordinates).toFixed(2));
        fiber.properties[endpointKey === 'a' ? 'a_end_device_port' : 'z_end_device_port'] = `${fiber.properties.endpoint_links[endpointKey].node_name} Port ${port}`;
        tjFeature.properties.connected_ports = upsertConnectedPort(tjFeature.properties.connected_ports, {
            fiber_id: fiber.properties.id || fiber.id,
            endpoint: endpointKey,
            port,
        });

        state.pendingEndpointLink = null;
        clearLinkTarget();
        state.dirty = true;
        refreshSources();
        showPathMarkers(fiber.properties.id || fiber.id);
        persistTopology();
        setStatus(`Fiber endpoint linked to ${fiber.properties.endpoint_links[endpointKey].node_name} port ${port}.`);

        return true;
    }

    function tryLinkEndpointNearTjBox(featureId, coordinateIndex, lngLat) {
        const tjFeature = nearestTjBox(lngLat);
        if (!tjFeature) {
            return false;
        }

        state.pendingEndpointLink = { featureId, coordinateIndex };
        return linkPendingEndpointToTjBox(tjFeature);
    }

    function nearestTjBox(lngLat, maxDistancePx = 72) {
        if (!state.map) return null;

        const target = state.map.project(lngLat);
        let best = null;

        state.features.forEach((feature) => {
            if (feature.geometry.type !== 'Point' || feature.properties.component_type !== 'tj_box') {
                return;
            }

            const point = state.map.project(feature.geometry.coordinates);
            const distance = Math.hypot(point.x - target.x, point.y - target.y);
            if (distance <= maxDistancePx && (!best || distance < best.distance)) {
                best = { feature, distance };
            }
        });

        return best?.feature || null;
    }

    function selectFeature(featureId) {
        state.selectedFeatureId = featureId;
        updateSelectionSource();
    }

    function clearSelection() {
        state.selectedFeatureId = null;
        clearSelectedBendPoint();
        closeOltPortPanel();
        updateSelectionSource();
    }

    function selectBendPoint(featureId, coordinateIndex, element) {
        state.selectedBendPoint = { featureId, coordinateIndex };
        state.pathMarkers.forEach((item) => item.marker.getElement().classList.remove('selected'));
        element.classList.add('selected');
        selectFeature(featureId);
        setStatus('Bend point selected. Press Backspace/Delete to remove it.');
    }

    function clearSelectedBendPoint() {
        state.selectedBendPoint = null;
        state.pathMarkers.forEach((item) => item.marker.getElement().classList.remove('selected'));
    }

    function updateSelectionSource() {
        const feature = state.selectedFeatureId ? state.features.get(state.selectedFeatureId) : null;
        setSourceData('selection-highlight', {
            type: 'FeatureCollection',
            features: feature && isFeatureVisible(feature) ? [feature] : [],
        });
    }

    function clearLinkTarget() {
        state.linkTargetFeatureId = null;
        updateLinkTargetSource();
    }

    function updateLinkTargetSource() {
        const feature = state.linkTargetFeatureId ? state.features.get(state.linkTargetFeatureId) : null;
        setSourceData('link-target-highlight', {
            type: 'FeatureCollection',
            features: feature && isFeatureVisible(feature) ? [feature] : [],
        });
    }

    function unlinkFiberEndpoint(featureId, endpointKey) {
        const fiber = state.features.get(featureId);
        if (!fiber?.properties.endpoint_links?.[endpointKey]) return;

        removeTjConnectedPort(fiber, endpointKey);
        delete fiber.properties.endpoint_links[endpointKey];
        state.dirty = true;
        refreshSources();
        showPathMarkers(featureId);
        persistTopology();
        setStatus(`Fiber ${endpointKey.toUpperCase()} endpoint unlinked.`);
    }

    function suggestedTjPort(tjFeature) {
        const used = [...state.features.values()]
            .filter((feature) => feature.geometry.type === 'LineString')
            .flatMap((feature) => Object.values(feature.properties.endpoint_links || {}))
            .filter((link) => link.node_id === tjFeature.properties.id)
            .map((link) => Number(String(link.port).replace(/\D/g, '')))
            .filter(Boolean);

        return String((Math.max(0, ...used) || 0) + 1);
    }

    function moveFiberVertex(fiber, coordinateIndex, coordinate) {
        const endpointKey = coordinateIndex === 0
            ? 'a'
            : (coordinateIndex === fiber.geometry.coordinates.length - 1 ? 'z' : null);
        const link = endpointKey ? fiber.properties.endpoint_links?.[endpointKey] : null;

        if (link?.node_id && state.features.has(link.node_id)) {
            const node = state.features.get(link.node_id);
            node.geometry.coordinates = coordinate;
            moveLinkedFiberEndpoints(node);
            return;
        }

        fiber.geometry.coordinates[coordinateIndex] = coordinate;
        fiber.properties.length_meters = Number(lineLengthMeters(fiber.geometry.coordinates).toFixed(2));
    }

    function upsertConnectedPort(ports, nextPort) {
        const existing = Array.isArray(ports) ? ports : [];
        return [
            ...existing.filter((port) => !(port.fiber_id === nextPort.fiber_id && port.endpoint === nextPort.endpoint)),
            nextPort,
        ];
    }

    function removeTjConnectedPort(fiber, endpointKey) {
        const link = fiber.properties.endpoint_links?.[endpointKey];
        const tj = link?.node_id ? state.features.get(link.node_id) : null;
        if (!tj?.properties?.connected_ports) return;

        tj.properties.connected_ports = tj.properties.connected_ports.filter((port) => {
            return !(port.fiber_id === (fiber.properties.id || fiber.id) && port.endpoint === endpointKey);
        });
    }

    function defaultPropertiesFor(componentType) {
        const sequence = nextSequence(componentType);

        if (componentType === 'fiber_cable') {
            return {
                fiber_code: `FIBER-${sequence}`,
                core_count: '4F',
                cable_type: 'Overhead',
            };
        }

        if (componentType === 'tj_box') {
            return {
                box_name: `TJ-BOX-${sequence}`,
            };
        }

        if (componentType === 'splitter') {
            return {
                name: `SPLITTER-${sequence}`,
                splitter_type: '1:8',
            };
        }

        if (componentType === 'onu') {
            return {
                client_name: `ONU-${sequence}`,
            };
        }

        const prefixes = {
            router: 'ROUTER',
            switch: 'SWITCH',
            olt: 'OLT',
        };

        return {
            name: `${prefixes[componentType] || componentType.toUpperCase()}-${sequence}`,
        };
    }

    function nextSequence(componentType) {
        const count = [...state.features.values()].filter((feature) => {
            return feature.properties.component_type === componentType;
        }).length + 1;

        return String(count).padStart(3, '0');
    }

    function startNodeDrag(event) {
        if (!event.features?.length || state.activeTool || state.pendingEndpointLink) return;
        const featureId = event.features[0].properties.id;
        const feature = state.features.get(featureId);
        if (!feature || feature.geometry.type !== 'Point') return;

        event.preventDefault();
        state.draggingNode = {
            featureId,
            startPoint: { x: event.point.x, y: event.point.y },
            moved: false,
        };
        state.map.dragPan.disable();
        state.map.getCanvas().style.cursor = 'grabbing';
    }

    function handleMapMouseMove(event) {
        dragNode(event);
        updatePlacementPreview(event);
    }

    function visibleMapCenterPoint(bounds) {
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;
        const header = document.querySelector('.app-header');
        const headerBottom = header
            ? Math.min(viewportHeight, Math.max(0, header.getBoundingClientRect().bottom))
            : 0;
        const visibleLeft = Math.max(0, bounds.left);
        const visibleRight = Math.min(viewportWidth, bounds.right);
        const visibleTop = Math.max(headerBottom, bounds.top);
        const visibleBottom = Math.min(viewportHeight, bounds.bottom);

        if (visibleRight <= visibleLeft || visibleBottom <= visibleTop) {
            return { x: bounds.width / 2, y: bounds.height / 2 };
        }

        return {
            x: ((visibleLeft + visibleRight) / 2) - bounds.left,
            y: ((visibleTop + visibleBottom) / 2) - bounds.top,
        };
    }

    function updatePlacementCursor() {
        if (!state.map) return;
        state.map.getCanvas().classList.toggle('placing-map-item', Boolean(state.activeTool));
        state.map.getCanvas().style.cursor = state.activeTool ? 'copy' : '';
    }

    function updatePlacementPreview(event) {
        if (!state.activeTool || state.draggingNode || state.pendingEndpointLink) {
            clearPlacementPreview();
            return;
        }

        const label = state.activeTool === 'fiber'
            ? `Click to add Fiber point ${state.draftLine.length + 1}`
            : `Click to add ${componentLabels[state.activeNodeType]}`;
        const color = state.activeTool === 'fiber' ? '#12b76a' : (nodeColors[state.activeNodeType] || '#116149');
        const point = event.point;

        if (!state.placementPreview) {
            state.placementPreview = document.createElement('div');
            state.placementPreview.className = 'placement-preview';
            state.map.getContainer().appendChild(state.placementPreview);
        }

        state.placementPreview.style.left = `${point.x}px`;
        state.placementPreview.style.top = `${point.y}px`;
        state.placementPreview.style.setProperty('--placement-color', color);
        state.placementPreview.innerHTML = `<span></span><strong>${escapeHtml(label)}</strong>`;
    }

    function clearPlacementPreview() {
        if (state.placementPreview) {
            state.placementPreview.remove();
            state.placementPreview = null;
        }
    }

    function dragNode(event) {
        if (!state.draggingNode) return;

        if (!state.draggingNode.moved) {
            const distance = Math.hypot(
                event.point.x - state.draggingNode.startPoint.x,
                event.point.y - state.draggingNode.startPoint.y
            );
            if (distance < 3) return;
            state.draggingNode.moved = true;
        }

        const feature = state.features.get(state.draggingNode.featureId);
        if (!feature) return;

        feature.geometry.coordinates = [event.lngLat.lng, event.lngLat.lat];
        moveLinkedFiberEndpoints(feature);
        refreshSources();
    }

    function finishNodeDrag() {
        if (!state.draggingNode) return;

        const nodeMoved = state.draggingNode.moved;
        state.draggingNode = null;
        state.map.dragPan.enable();
        state.map.getCanvas().style.cursor = '';

        if (nodeMoved) {
            state.dirty = true;
            state.nodeDragJustFinished = true;
            persistTopology();
            setStatus('Node moved. Linked fiber endpoints updated.');
        }
    }

    function moveLinkedFiberEndpoints(nodeFeature) {
        const nodeId = nodeFeature.properties.id;
        state.features.forEach((feature) => {
            if (nodeFeature.properties.component_type === 'tj_box'
                && feature.geometry.type === 'Point'
                && feature.properties.component_type === 'splitter'
                && feature.properties.splitter_parent_tj_box_id === nodeId) {
                feature.geometry.coordinates = [...nodeFeature.geometry.coordinates];
            }

            if (feature.geometry.type !== 'LineString') return;

            const links = feature.properties.endpoint_links || {};
            if (links.a?.node_id === nodeId) {
                feature.geometry.coordinates[0] = [...nodeFeature.geometry.coordinates];
            }
            if (links.z?.node_id === nodeId) {
                feature.geometry.coordinates[feature.geometry.coordinates.length - 1] = [...nodeFeature.geometry.coordinates];
            }
            if (links.a?.node_id === nodeId || links.z?.node_id === nodeId) {
                feature.properties.length_meters = Number(lineLengthMeters(feature.geometry.coordinates).toFixed(2));
            }
        });
    }

    function syncSplitterParent(feature) {
        if (feature.properties.component_type !== 'splitter') return;

        const parentId = feature.properties.splitter_parent_tj_box_id;
        const parent = parentId ? state.features.get(parentId) : null;
        if (!parent) {
            feature.properties.splitter_parent_tj_box_name = '';
            return;
        }

        feature.properties.splitter_parent_tj_box_name = parent.properties.box_name || parent.properties.name || 'TJ Box';
        feature.geometry.coordinates = [...parent.geometry.coordinates];
    }

    function setSourceData(sourceName, data) {
        const source = state.map?.getSource(sourceName);
        if (source) source.setData(data);
    }

    function renderStats(features) {
        const nodes = features.filter((feature) => feature.geometry.type === 'Point');
        const links = features.filter((feature) => feature.geometry.type === 'LineString');
        const byType = features.reduce((carry, feature) => {
            const type = featureVisibilityType(feature);
            carry[type] = (carry[type] || 0) + 1;
            return carry;
        }, {});
        byType.party_locations = countPartyLocations();
        byType.party_usernames = byType.party_locations;
        const fiberKm = links.reduce((sum, feature) => sum + Number(feature.properties.length_meters || 0), 0) / 1000;

        renderVisibilityControls(byType, features);
        document.getElementById('networkStats').innerHTML = [
            stat('Nodes', nodes.length),
            stat('Fiber Links', links.length),
            stat('Routers', byType.router || 0),
            stat('OLTs', byType.olt || 0),
            stat('Splitters', byType.splitter || 0),
            stat('Party locations', byType.party_locations || 0),
            stat('Fiber km', fiberKm.toFixed(2)),
        ].join('');
    }

    function stat(label, value) {
        return `<div class="stat-tile"><span>${escapeHtml(label)}</span><strong>${escapeHtml(String(value))}</strong></div>`;
    }

    function toFeatureCollection() {
        return {
            type: 'FeatureCollection',
            features: [...state.features.values()].map((feature) => ({
                type: 'Feature',
                id: feature.properties.id || feature.id,
                geometry: feature.geometry,
                properties: { ...feature.properties, id: feature.properties.id || feature.id },
            })),
        };
    }

    function toggleGeoJsonPreview() {
        const preview = document.getElementById('geojsonPreview');
        preview.hidden = !preview.hidden;
        updateGeoJsonPreview();
    }

    function updateGeoJsonPreview() {
        const preview = document.getElementById('geojsonPreview');
        if (!preview.hidden) {
            preview.textContent = JSON.stringify(toFeatureCollection(), null, 2);
        }
    }

    function fitToFeatures() {
        const allCoordinates = [...state.features.values()].flatMap((feature) => {
            return feature.geometry.type === 'Point'
                ? [feature.geometry.coordinates]
                : feature.geometry.coordinates;
        });

        if (!allCoordinates.length) return;

        const bounds = allCoordinates.reduce((current, coordinate) => current.extend(coordinate), new maplibregl.LngLatBounds(allCoordinates[0], allCoordinates[0]));
        state.map.fitBounds(bounds, { padding: 80, maxZoom: 15, duration: 600 });
    }

    function popupCoordinate(feature) {
        if (feature.geometry.type === 'Point') return feature.geometry.coordinates;
        return feature.geometry.coordinates[Math.floor(feature.geometry.coordinates.length / 2)];
    }

    function popupHtml(feature) {
        const props = feature.properties;
        const title = props.name || props.box_name || props.client_name || props.fiber_code || componentLabels[props.component_type] || 'Network Feature';
        const rows = [
            ...popupRows(props),
            ...(Array.isArray(props.photos) && props.photos.length ? [['Photos', `${props.photos.length} saved`]] : []),
        ];

        return [
            `<p class="popup-title">${escapeHtml(title)}</p>`,
            `<p class="popup-meta">${escapeHtml(componentLabels[props.component_type] || props.component_type)}</p>`,
            rows.length ? `<dl class="popup-details">${rows.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(String(value))}</dd></div>`).join('')}</dl>` : '',
            `<div class="popup-actions"><button type="button" class="search-result-action search-result-action--primary" data-edit-feature="${escapeHtml(String(props.id || feature.id))}">Edit details</button>${feature.geometry.type === 'Point' ? `<button type="button" class="search-result-action" data-relocate-feature="${escapeHtml(String(props.id || feature.id))}">Relocate</button>` : ''}</div>`,
        ].join('');
    }

    /** Arm "click the map to move this node". */
    function startFeatureRelocate(id) {
        const feature = state.features.get(id);
        if (!feature || feature.geometry.type !== 'Point') return;
        state.featurePopup?.remove();
        state.featurePopup = null;
        closeFeatureForm();
        state.relocatingFeatureId = id;
        state.map.getCanvas().style.cursor = 'crosshair';
        setStatus(`Relocating ${featureDisplayName(feature)} — click the map to set the new location (Esc to cancel).`);
    }

    /** Info popup for any node/link with a one-click Edit button. */
    function showFeaturePopup(id) {
        const feature = state.features.get(id);
        if (!feature) return;

        state.featurePopup?.remove();
        state.featurePopup = new maplibregl.Popup({ closeButton: true, offset: 12 })
            .setLngLat(popupCoordinate(feature))
            .setHTML(popupHtml(feature))
            .addTo(state.map);

        const popupEl = state.featurePopup.getElement();
        popupEl.querySelector('[data-edit-feature]')?.addEventListener('click', () => {
            state.featurePopup?.remove();
            state.featurePopup = null;
            openExistingFeature(id);
        });
        popupEl.querySelector('[data-relocate-feature]')?.addEventListener('click', () => {
            startFeatureRelocate(id);
        });
    }

    function popupRows(props) {
        if (props.component_type === 'fiber_cable') {
            return [
                ['Core/Type', `${props.core_count || 'Fiber'} ${props.cable_type || ''}`.trim()],
                ['Length', `${Number(props.length_meters || 0).toFixed(2)} m`],
                ['A-End', compactJoin([props.a_end_device_port, props.a_end_tube_color, props.a_end_core_color])],
                ['Z-End', compactJoin([props.z_end_device_port, props.z_end_tube_color, props.z_end_core_color])],
                ['Splitter IN', props.splitter_input_port],
                ['Splitter OUT', compactJoin([props.splitter_output_port, props.splitter_output_core_color])],
                ['Connected Fiber', compactJoin([props.connected_fiber_code, props.connected_fiber_core_color])],
                ['A-End Link', formatEndpointLink(props.endpoint_links?.a)],
                ['Z-End Link', formatEndpointLink(props.endpoint_links?.z)],
                ['Core Map', formatFiberCoreMap(props.core_mappings)],
            ].filter((row) => row[1]);
        }

        if (props.component_type === 'splitter') {
            return [
                ['Type', props.splitter_type],
                ['Inside TJ', props.splitter_parent_tj_box_name],
                ['Parent OLT/Port', props.parent_olt_port],
                ['Input Fiber', compactJoin([props.splitter_input_fiber_code, props.splitter_input_tube_color, props.splitter_input_core_color])],
                ['Port Map', formatSplitterPortMap(props.splitter_ports)],
                ['Outputs', props.splitter_output_map],
            ].filter((row) => row[1]);
        }

        if (props.component_type === 'olt') {
            return [
                ['IP', props.ip_address],
                ['Ports', `${props.available_ports || 0}/${props.total_ports || 0} available`],
                ['Linked PONs', formatOltPortLinks({ ...(props.olt_port_links || {}), ...(props.port_links || {}) })],
                ['Note', props.note],
            ].filter((row) => row[1]);
        }

        if (['router', 'switch'].includes(props.component_type)) {
            return [
                ['IP', props.ip_address],
                ['Ports', `${props.available_ports || 0}/${props.total_ports || 0} available`],
                ['Linked Ports', formatOltPortLinks(props.port_links)],
                ['Note', props.note],
            ].filter((row) => row[1]);
        }

        return [];
    }

    function formatOltPortLinks(links) {
        return Object.entries(links || {})
            .map(([port, link]) => `${port}: ${compactJoin([link.fiber_code, link.color_name || `Core ${link.core}`])}`)
            .join('\n');
    }

    function compactJoin(values) {
        return values.filter((value) => value !== undefined && value !== null && String(value).trim() !== '').join(' / ');
    }

    function setPartySearchInput(value) {
        const input = document.getElementById('customerIdQuery');
        if (!input) {
            return;
        }

        const normalized = String(value || '').trim();
        input.value = normalized;
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
        return normalized === 'active' ? 'active' : normalized === 'inactive' || normalized === 'deactivated' ? 'inactive' : 'inactive';
    }

    function formatFiberCoreMap(rows) {
        if (!Array.isArray(rows) || rows.length === 0) return '';

        return rows
            .filter((row) => row.in_point || row.out_point || row.note)
            .map((row) => `${row.tube > 1 ? `T${row.tube} ` : ''}C${row.core} ${row.color_name}: ${row.in_point || '-'} -> ${row.out_point || '-'}${row.note ? ` (${row.note})` : ''}`)
            .join('\n');
    }

    function formatSplitterPortMap(rows) {
        if (!Array.isArray(rows) || rows.length === 0) return '';

        return rows
            .filter((row) => row.connected_fiber || row.connected_core || row.note)
            .map((row) => `${row.port} ${row.color_name}: ${compactJoin([row.connected_fiber, row.connected_core]) || '-'}${row.note ? ` (${row.note})` : ''}`)
            .join('\n');
    }

    function formatEndpointLink(link) {
        if (!link) return '';

        return `${link.node_name || 'TJ Box'} / Port ${link.port || '-'}`;
    }

    function fieldHtml(field, value) {
        const required = field.required ? 'required' : '';
        const disabled = field.disabled ? 'readonly' : '';
        const full = field.type === 'textarea' || field.type === 'fiber_core_map' || field.type === 'splitter_port_map' || field.type === 'photos' || field.type === 'link_endpoints' ? ' full' : '';
        const safeValue = value ?? '';
        const step = field.step ? `step="${escapeHtml(field.step)}"` : '';
        const min = field.min !== undefined ? `min="${escapeHtml(String(field.min))}"` : '';
        const max = field.max !== undefined ? `max="${escapeHtml(String(field.max))}"` : '';

        if (field.type === 'fiber_core_map' || field.type === 'splitter_port_map') {
            return `<section class="dynamic-map ${full}" data-map-type="${escapeHtml(field.type)}" data-property="${escapeHtml(field.name)}"><div class="dynamic-map-title">${escapeHtml(field.label)}</div><div class="dynamic-map-body"></div></section>`;
        }

        if (field.type === 'photos') {
            const photos = Array.isArray(value) ? value : [];
            return `<section class="photo-field full">
                <div class="dynamic-map-title">${escapeHtml(field.label)}</div>
                <div class="photo-grid">${photos.map((photo, index) => photoHtml(photo, index)).join('')}</div>
                <label class="photo-upload">Add Photos<input type="file" name="photo_files" accept="image/*" multiple></label>
            </section>`;
        }

        if (field.type === 'link_endpoints') {
            return linkedEndpointHtml(safeValue);
        }

        if (field.type === 'tj_box_select') {
            return `<label class="${full}">${escapeHtml(field.label)}${tjBoxSelectHtml(field.name, safeValue)}</label>`;
        }

        if (field.type === 'select' || field.type === 'color') {
            return `<label class="${full}">${escapeHtml(field.label)}${searchableDropdownHtml(
                field.name,
                field.type === 'color'
                    ? `Type or pick ${field.label.toLowerCase()}`
                    : `Type or choose ${field.label.toLowerCase()}`,
                String(safeValue),
                String(safeValue),
                field.required
            )}</label>`;
        }

        if (field.type === 'textarea') {
            return `<label class="${full}">${escapeHtml(field.label)}<textarea name="${escapeHtml(field.name)}" placeholder="${escapeHtml(field.placeholder || '')}" ${required}>${escapeHtml(String(safeValue))}</textarea></label>`;
        }

        return `<label class="${full}">${escapeHtml(field.label)}<input name="${escapeHtml(field.name)}" type="${escapeHtml(field.type || 'text')}" value="${escapeHtml(String(safeValue))}" placeholder="${escapeHtml(field.placeholder || '')}" ${required} ${disabled} ${step} ${min} ${max}></label>`;
    }

    function input(name, label, placeholder, required = false, type = 'text', disabled = false, options = {}) {
        return { type, name, label, placeholder, required, disabled, ...options };
    }

    function select(name, label, options, required = false) {
        return { type: 'select', name, label, options, required };
    }

    // Fibre tube / core colour picker: the 12 standard colours as a swatched,
    // typeahead list. allowCustom keeps any legacy free-text value intact.
    function color(name, label, required = false) {
        return { type: 'color', name, label, required };
    }

    function textarea(name, label, placeholder, required = false) {
        return { type: 'textarea', name, label, placeholder, required };
    }

    function nodeMetaFields() {
        return [
            input('latitude', 'Latitude', '23.901300', true, 'number', false, { step: '0.000001', min: -90, max: 90 }),
            input('longitude', 'Longitude', '89.122000', true, 'number', false, { step: '0.000001', min: -180, max: 180 }),
            textarea('note', 'Note', 'Add installation notes, splice notes, or field remarks'),
        ];
    }

    function dynamicMap(name, label, type) {
        return { type, name, label };
    }

    function photos(name, label) {
        return { type: 'photos', name, label };
    }

    function linkEndpoints(name, label) {
        return { type: 'link_endpoints', name, label };
    }

    function tjBoxSelect(name, label) {
        return { type: 'tj_box_select', name, label };
    }

    function tjBoxDropdownOptions() {
        return [{ value: '', label: 'Not inside TJ Box', search: 'none not inside' }].concat([...state.features.values()]
            .filter((feature) => feature.geometry.type === 'Point' && feature.properties.component_type === 'tj_box')
            .map((box) => ({
                value: String(box.properties.id || box.id || ''),
                label: String(box.properties.box_name || box.properties.name || 'TJ Box'),
                search: compactJoin([
                    box.properties.box_name,
                    box.properties.name,
                    box.properties.id || box.id,
                ]),
            })));
    }

    function tjBoxSelectHtml(name, selectedValue) {
        const selected = String(selectedValue || '');
        const options = tjBoxDropdownOptions();
        const selectedOption = options.find((option) => option.value === selected);

        return searchableDropdownHtml(
            name,
            'Type TJ Box name',
            selectedOption?.label || selected,
            selected
        );
    }

    function hydrateSearchableFormLists(container, schema) {
        schema.forEach((field) => {
            if (!['select', 'tj_box_select', 'color'].includes(field.type)) {
                return;
            }

            const dropdown = container.querySelector(`[data-searchable-dropdown="${field.name}"]`);
            let options;
            let placeholder;
            if (field.type === 'select') {
                options = field.options.map((option) => ({ value: String(option), label: String(option), search: String(option) }));
                placeholder = `Type or choose ${field.label.toLowerCase()}`;
            } else if (field.type === 'color') {
                options = corePalette.map(([label, hex]) => ({ value: label, label, search: label, color_hex: hex }));
                placeholder = `Type or pick ${field.label.toLowerCase()}`;
            } else {
                options = tjBoxDropdownOptions();
                placeholder = 'Type TJ Box name';
            }

            setupSearchableDropdown(
                dropdown,
                options,
                placeholder,
                null,
                { allowCustom: field.type === 'select' || field.type === 'color' }
            );
        });
    }

    function linkedEndpointHtml(value) {
        const links = value && typeof value === 'object' ? value : {};
        const rows = ['a', 'z'].map((key) => {
            const link = links[key];
            const label = key === 'a' ? 'A-End' : 'Z-End';

            return `<div class="endpoint-row">
                <strong>${label}</strong>
                <span>${link ? escapeHtml(`${link.node_name || 'TJ Box'} / Port ${link.port || '-'}`) : 'Not linked'}</span>
                ${link ? `<button type="button" class="endpoint-unlink" data-endpoint="${key}">Unlink</button>` : ''}
            </div>`;
        }).join('');

        return `<section class="linked-endpoints full"><div class="dynamic-map-title">Linked Endpoints</div>${rows}</section>`;
    }

    function bindEndpointUnlinkButtons(container) {
        container.querySelectorAll('.endpoint-unlink').forEach((button) => {
            button.addEventListener('click', () => {
                unlinkFiberEndpoint(state.editingFeatureId, button.dataset.endpoint);
                renderFields('fiber_cable', state.features.get(state.editingFeatureId)?.properties || {});
            });
        });
    }

    function photoHtml(photo, index) {
        const url = typeof photo === 'string' ? photo : photo.url;
        const name = typeof photo === 'string' ? `Photo ${index + 1}` : (photo.name || `Photo ${index + 1}`);

        return `<figure class="photo-thumb">
            <img src="${escapeHtml(url)}" alt="${escapeHtml(name)}">
            <figcaption>${escapeHtml(name)}</figcaption>
            <label><input type="checkbox" data-photo-remove value="${escapeHtml(url)}"> Remove</label>
            <input type="hidden" data-photo-url value="${escapeHtml(url)}" data-photo-name="${escapeHtml(name)}">
        </figure>`;
    }

    function serializeExistingPhotos(form) {
        const removed = new Set([...form.querySelectorAll('[data-photo-remove]:checked')].map((inputElement) => inputElement.value));

        return [...form.querySelectorAll('[data-photo-url]')]
            .filter((inputElement) => !removed.has(inputElement.value))
            .map((inputElement) => ({
                url: inputElement.value,
                name: inputElement.dataset.photoName || 'Photo',
            }));
    }

    async function uploadSelectedPhotos(form) {
        const inputElement = form.querySelector('input[name="photo_files"]');
        if (!inputElement || inputElement.files.length === 0) {
            return [];
        }

        const body = new FormData();
        [...inputElement.files].forEach((file) => body.append('photos[]', file));
        setStatus('Uploading photos...');

        const response = await fetch(config.photoUploadUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body,
        });

        if (!response.ok) {
            throw new Error(await responseErrorMessage(response, 'Photos could not be uploaded.'));
        }

        const payload = await response.json();
        return payload.photos || [];
    }

    function hydrateDynamicMaps(container, properties) {
        container.querySelectorAll('.dynamic-map').forEach((section) => {
            renderDynamicMap(section, properties[section.dataset.property] || []);
        });

        const coreCount = container.querySelector('input[name="core_count"]');
        if (coreCount) {
            coreCount.addEventListener('change', () => {
                const section = container.querySelector('.dynamic-map[data-map-type="fiber_core_map"]');
                if (section) renderDynamicMap(section, collectMapRows(section));
            });
        }

        const splitterType = container.querySelector('input[name="splitter_type"]');
        if (splitterType) {
            splitterType.addEventListener('change', () => {
                const section = container.querySelector('.dynamic-map[data-map-type="splitter_port_map"]');
                if (section) renderDynamicMap(section, collectMapRows(section));
            });
        }

        bindCoreMapActions(container);
    }

    function renderDynamicMap(section, existingRows) {
        const rows = section.dataset.mapType === 'fiber_core_map'
            ? buildFiberCoreRows(section, existingRows)
            : buildSplitterPortRows(section, existingRows);

        section.querySelector('.dynamic-map-body').innerHTML = dynamicMapTable(section.dataset.mapType, rows);
        bindCoreMapActions(section);
    }

    function buildFiberCoreRows(section, existingRows) {
        const count = Number(section.closest('form').querySelector('input[name="core_count"]')?.value?.replace('F', '') || 4);
        const existing = mapByKey(existingRows, 'key');

        return Array.from({ length: count }, (_, index) => {
            const palette = corePalette[index % 12];
            const tube = Math.floor(index / 12) + 1;
            const core = index + 1;
            const key = `core-${core}`;
            const saved = existing.get(key) || {};

            return {
                key,
                tube,
                core,
                color_name: saved.color_name || palette[0],
                color_hex: saved.color_hex || palette[1],
                in_point: saved.in_point || '',
                out_point: saved.out_point || '',
                note: saved.note || '',
            };
        });
    }

    function buildSplitterPortRows(section, existingRows) {
        const splitterType = section.closest('form').querySelector('input[name="splitter_type"]')?.value || '1:8';
        const outputCount = Number(splitterType.split(':')[1] || 8);
        const existing = mapByKey(existingRows, 'port');
        const input = existing.get('IN') || {};
        const rows = [{
            port: 'IN',
            color_name: input.color_name || 'Blue',
            color_hex: input.color_hex || '#1d4ed8',
            medium: input.medium === 'Copper' ? 'Copper' : 'Fiber',
            connected_fiber: input.connected_fiber || '',
            connected_core: input.connected_core || '',
            note: input.note || '',
        }];

        for (let index = 0; index < outputCount; index++) {
            const palette = corePalette[index % 12];
            const port = `OUT-${String(index + 1).padStart(2, '0')}`;
            const saved = existing.get(port) || {};
            rows.push({
                port,
                color_name: saved.color_name || palette[0],
                color_hex: saved.color_hex || palette[1],
                medium: saved.medium === 'Copper' ? 'Copper' : 'Fiber',
                connected_fiber: saved.connected_fiber || '',
                connected_core: saved.connected_core || '',
                note: saved.note || '',
            });
        }

        return rows;
    }

    function dynamicMapTable(type, rows) {
        if (type === 'fiber_core_map') {
            const options = endpointOptions();
            return `<div class="core-map-table"><div class="core-map-head"><span>Core</span><span>Color</span><span>IN</span><span>OUT</span><span>Note</span><span>Action</span></div>${rows.map((row) => `
                <div class="core-map-row" data-key="${escapeHtml(row.key)}">
                    <span>${escapeHtml(row.tube > 1 ? `T${row.tube} / C${row.core}` : `C${row.core}`)}</span>
                    <span class="color-cell" draggable="true" data-drag-value="${escapeHtml(row.color_name)}"><i style="background:${escapeHtml(row.color_hex)}"></i>${escapeHtml(row.color_name)}</span>
                    ${endpointSelect('in_point', row.in_point, options, 'Select IN')}
                    ${endpointSelect('out_point', row.out_point, options, 'Select OUT')}
                    <input data-map-field="note" value="${escapeHtml(row.note)}" placeholder="splice note">
                    <button type="button" class="core-unlink" data-clear-row>Unlink</button>
                    <input type="hidden" data-map-field="tube" value="${escapeHtml(String(row.tube))}">
                    <input type="hidden" data-map-field="core" value="${escapeHtml(String(row.core))}">
                    <input type="hidden" data-map-field="color_name" value="${escapeHtml(row.color_name)}">
                    <input type="hidden" data-map-field="color_hex" value="${escapeHtml(row.color_hex)}">
                </div>`).join('')}</div>`;
        }

        return `<div class="core-map-table splitter-map-table"><div class="core-map-head"><span>Port</span><span>Color / Medium</span><span>Target</span><span>Core</span><span>Note</span><span>Action</span></div>${rows.map((row) => `
            <div class="core-map-row" data-port="${escapeHtml(row.port)}">
                <span>${escapeHtml(row.port)}</span>
                ${splitterLinkStyleControls(row)}
                ${endpointSelect('connected_fiber', row.connected_fiber, endpointOptions(), 'Type or select any endpoint')}
                ${coreColorSelect('connected_core', row.connected_core)}
                <input data-map-field="note" value="${escapeHtml(row.note)}" placeholder="customer/splice note">
                <button type="button" class="core-unlink" data-clear-row>Unlink</button>
            </div>`).join('')}</div>`;
    }

    function splitterLinkStyleControls(row) {
        return `<span class="splitter-link-style">
            ${endpointSelect('color_name', row.color_name, corePalette.map(([name]) => name), 'Type or choose color', 'data-link-color-name')}
            <input type="color" data-link-color-picker value="${escapeHtml(row.color_hex)}" title="Custom link color">
            ${endpointSelect('medium', row.medium, ['Fiber', 'Copper'], 'Type or choose medium')}
            <input type="hidden" data-map-field="color_hex" data-link-color-hex value="${escapeHtml(row.color_hex)}">
        </span>`;
    }

    function serializeDynamicMaps(form) {
        const maps = {};
        form.querySelectorAll('.dynamic-map').forEach((section) => {
            maps[section.dataset.property] = collectMapRows(section);
        });

        return maps;
    }

    function collectMapRows(section) {
        return [...section.querySelectorAll('.core-map-row')].map((row) => {
            const data = {};
            if (row.dataset.key) data.key = row.dataset.key;
            if (row.dataset.port) data.port = row.dataset.port;

            row.querySelectorAll('[data-map-field]').forEach((inputElement) => {
                const value = inputElement.value.trim();
                data[inputElement.dataset.mapField] = value;
            });

            if (data.tube) data.tube = Number(data.tube);
            if (data.core) data.core = Number(data.core);

            return data;
        });
    }

    function endpointOptions() {
        const options = [];
        state.features.forEach((feature) => {
            const props = feature.properties || {};
            if (feature.geometry.type === 'Point') {
                const name = props.box_name || props.client_name || props.name || componentLabels[props.component_type] || 'Node';
                options.push(`${name}`);
                if (props.component_type === 'tj_box') {
                    for (let port = 1; port <= 16; port++) {
                        options.push(`${name} Port ${port}`);
                    }
                }
                if (props.component_type === 'splitter') {
                    options.push(`${name} IN`);
                    const count = Number(String(props.splitter_type || '1:8').split(':')[1] || 8);
                    for (let port = 1; port <= count; port++) {
                        options.push(`${name} OUT-${String(port).padStart(2, '0')}`);
                    }
                }
                if (props.component_type === 'olt') {
                    for (let port = 1; port <= Number(props.total_ports || 16); port++) {
                        options.push(`${name} PON ${port}`);
                    }
                }
            }

            if (feature.geometry.type === 'LineString' && props.fiber_code) {
                options.push(props.fiber_code);
                corePalette.forEach(([color]) => options.push(`${props.fiber_code} ${color}`));
            }
        });

        // Every mapped customer is a valid fiber endpoint too.
        state.customerFeatures.forEach((feature) => {
            if (Array.isArray(feature.geometry?.coordinates)) {
                options.push(customerEndpointLabel(feature.properties || {}));
            }
        });

        return [...new Set(options)].sort((first, second) => first.localeCompare(second, undefined, {
            numeric: true,
            sensitivity: 'base',
        }));
    }

    function endpointSelect(name, value, options, placeholder, inputAttributes = '') {
        const normalizedValue = value || '';
        const listId = `network-map-endpoint-options-${++endpointDropdownSequence}`;
        const optionHtml = options
            .map((option) => `<option value="${escapeHtml(option)}"></option>`)
            .join('');

        return `<span class="map-search-select">
            <input type="search" data-map-field="${escapeHtml(name)}" value="${escapeHtml(normalizedValue)}" list="${listId}" autocomplete="off" placeholder="${escapeHtml(placeholder)}" ${inputAttributes}>
            <datalist id="${listId}">${optionHtml}</datalist>
        </span>`;
    }

    function coreColorSelect(name, value) {
        return endpointSelect(name, value, corePalette.map(([color]) => color), 'Type or select core');
    }

    function bindCoreMapActions(container) {
        container.querySelectorAll('.splitter-link-style').forEach((styleControl) => {
            const nameSelect = styleControl.querySelector('[data-link-color-name]');
            const colorPicker = styleControl.querySelector('[data-link-color-picker]');
            const colorHex = styleControl.querySelector('[data-link-color-hex]');
            nameSelect?.addEventListener('change', () => {
                const nextColor = coreColorHex(nameSelect.value) || colorPicker.value;
                colorPicker.value = nextColor;
                colorHex.value = nextColor;
            });
            colorPicker?.addEventListener('input', () => {
                colorHex.value = colorPicker.value;
            });
        });

        container.querySelectorAll('[data-clear-row]').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('.core-map-row').querySelectorAll('input[data-map-field]').forEach((field) => {
                    if (!['tube', 'core', 'color_name', 'color_hex'].includes(field.dataset.mapField)) {
                        field.value = '';
                    }
                });
            });
        });

        container.querySelectorAll('[draggable="true"][data-drag-value]').forEach((chip) => {
            chip.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('text/plain', chip.dataset.dragValue);
            });
        });

        container.querySelectorAll('.core-map-row input[data-map-field]').forEach((field) => {
            field.addEventListener('dragover', (event) => event.preventDefault());
            field.addEventListener('drop', (event) => {
                event.preventDefault();
                const value = event.dataTransfer.getData('text/plain');
                if (value) {
                    field.value = value;
                }
            });
        });
    }

    function mapByKey(rows, key) {
        if (!Array.isArray(rows)) return new Map();

        return new Map(rows.map((row) => [row[key], row]));
    }

    function lineLengthMeters(coordinates) {
        return coordinates.slice(1).reduce((total, coordinate, index) => {
            return total + distanceMeters(coordinates[index], coordinate);
        }, 0);
    }

    function distanceMeters(from, to) {
        const earthRadius = 6371008.8;
        const fromLat = radians(from[1]);
        const toLat = radians(to[1]);
        const deltaLat = radians(to[1] - from[1]);
        const deltaLng = radians(to[0] - from[0]);
        const a = Math.sin(deltaLat / 2) ** 2
            + Math.cos(fromLat) * Math.cos(toLat) * Math.sin(deltaLng / 2) ** 2;

        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function radians(value) {
        return value * Math.PI / 180;
    }

    function setStatus(message) {
        document.getElementById('mapStatus').textContent = message;
    }

    function isTypingTarget(target) {
        return Boolean(target?.closest?.('input, textarea, select, [contenteditable="true"], .network-modal'));
    }

    async function responseErrorMessage(response, fallback) {
        const payload = await response.json().catch(() => ({}));
        if (response.status === 401) return 'Please sign in again to load the network topology.';
        if (response.status === 403) return 'Your user does not have permission to manage the network map.';
        if (response.status === 500) return `${fallback} Check Laravel logs and confirm migrations are applied.`;
        return payload.message || `${fallback} HTTP ${response.status}.`;
    }

    function uuid() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return 'feature-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
