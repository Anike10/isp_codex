@extends('layouts.app')

@section('main_class', 'network-map-main')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/maplibre-gl.css') }}?v=4.7.1">
    <link rel="stylesheet" href="{{ asset('css/network-map-7993e11add8f.css') }}?v=20260903-12">

    <section class="network-map-page">
        <aside class="network-sidebar">
            <div class="tool-section map-picker-section">
                <h2>Network Map</h2>
                <label class="sr-only" for="mapPicker">Choose network map</label>
                <select id="mapPicker" class="map-picker-select">
                    @foreach ($networkMaps as $map)
                        <option value="{{ route('network-map.index', ['map' => $map->slug]) }}" @selected($map->id === $activeMap->id)>
                            {{ $map->name }}@if ($map->is_test) &nbsp;(test)@endif @if ($map->customer) &mdash; {{ $map->customer->name }}@endif
                        </option>
                    @endforeach
                </select>

                <details class="map-picker-new">
                    <summary class="btn light">+ New map</summary>
                    <form method="post" action="{{ route('network-map.maps.store') }}" class="map-picker-new-form" autocomplete="off">
                        @csrf
                        <label>Map name
                            <input type="text" name="name" maxlength="120" required placeholder="e.g. Kibria Bazar Zone">
                        </label>
                        <label>Link to customer (optional)
                            <input type="search" id="newMapCustomerSearch" placeholder="Search party by name or ID">
                        </label>
                        <input type="hidden" name="customer_id" id="newMapCustomerId">
                        <div class="search-results" id="newMapCustomerResults" hidden></div>
                        <p class="hint" id="newMapCustomerPicked" hidden></p>
                        <label class="map-picker-check">
                            <input type="checkbox" name="is_test" value="1"> Test map (scratch / experiments)
                        </label>
                        <button type="submit" class="btn secondary">Create map</button>
                    </form>
                </details>

                @if (! $activeMap->is_default)
                    <form method="post" action="{{ route('network-map.maps.destroy', $activeMap) }}"
                          onsubmit="return confirm('Delete the map &quot;{{ $activeMap->name }}&quot; and everything drawn on it? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn light danger map-picker-delete">Delete this map</button>
                    </form>
                @endif
            </div>

            <div class="tool-grid" id="historyTools">
                <button type="button" class="btn light" id="undoTopology" disabled title="Undo (Ctrl+Z)">Undo</button>
                <button type="button" class="btn light" id="redoTopology" disabled title="Redo (Ctrl+Y)">Redo</button>
            </div>

            <div class="map-party-search" role="search" aria-label="Search parties on the network map">
                <form class="map-party-search-form" id="customerSearch">
                    <label class="sr-only" for="customerIdQuery">Search party by name</label>
                    <input type="search" id="customerIdQuery" placeholder="পার্টির নামের অংশ লিখে সার্চ করুন" value="{{ $initialCustomerId }}" autocomplete="off">
                    <button type="submit" class="btn secondary">Search</button>
                </form>
                <div class="search-results map-party-search-results" id="customerSearchResult" hidden></div>
            </div>

            <section class="unmapped-party-panel" aria-labelledby="unmappedPartyHeading">
                <div class="unmapped-party-head">
                    <h2 id="unmappedPartyHeading">Unmapped Parties</h2>
                    <span id="unmappedPartyCount">0</span>
                </div>
                <p class="unmapped-party-summary" id="unmappedPartySummary">Loading parties&hellip;</p>
                <input type="search" id="unmappedPartyFilter" placeholder="Filter by ID, name, user or mobile">
                <div class="unmapped-party-list" id="unmappedPartyList">
                    <div class="unmapped-party-empty">Loading parties...</div>
                </div>
            </section>

            <div class="party-placement-panel" id="partyPlacementPanel" hidden>
                <p class="party-placement-title" id="partyPlacementTitle">Place party location</p>
                <div class="party-placement-info" id="partyPlacementInfo"></div>
                <div class="party-placement-actions">
                    <button type="button" class="btn secondary" id="startPartyPlacementBtn">Add on map</button>
                    <button type="button" class="btn light" id="cancelPartyPlacementBtn">Cancel</button>
                </div>
            </div>

            <div class="tool-section">
                <h2>Map Style</h2>
                <div class="basemap-grid" id="basemapTools">
                    <button type="button" class="basemap-tool" data-basemap="voyager">Voyager</button>
                    <button type="button" class="basemap-tool" data-basemap="osm">OSM</button>
                    <button type="button" class="basemap-tool" data-basemap="light">Light</button>
                    <button type="button" class="basemap-tool" data-basemap="dark">Dark</button>
                    <button type="button" class="basemap-tool" data-basemap="satellite">Satellite</button>
                    <button type="button" class="basemap-tool active" data-basemap="google_road">Google Road</button>
                    <button type="button" class="basemap-tool" data-basemap="google_satellite">Google Sat</button>
                </div>
                <form class="default-view-form" id="defaultViewForm">
                    <label>Latitude
                        <input type="number" id="defaultLat" step="0.000001" min="-90" max="90">
                    </label>
                    <label>Longitude
                        <input type="number" id="defaultLng" step="0.000001" min="-180" max="180">
                    </label>
                    <label>Zoom
                        <input type="number" id="defaultZoom" step="0.1" min="1" max="22">
                    </label>
                    <div class="default-view-actions">
                        <button type="button" class="btn light" id="useCurrentView">Set Current</button>
                        <button type="submit" class="btn secondary">Go</button>
                    </div>
                </form>
            </div>

            <div class="tool-section">
                <h2>Location Search</h2>
                <form class="location-search" id="locationSearch">
                    <input type="search" id="locationQuery" placeholder="Search location, road, village">
                    <button type="submit" class="btn secondary">Search</button>
                </form>
                <div class="search-results" id="searchResults" hidden></div>
            </div>

            <div class="tool-section">
                <h2>Nodes</h2>
                <div class="tool-grid" id="nodeTools">
                    <button type="button" class="map-tool" data-tool="node" data-node-type="router">Router</button>
                    <button type="button" class="map-tool" data-tool="node" data-node-type="switch">Switch</button>
                    <button type="button" class="map-tool" data-tool="node" data-node-type="olt">OLT</button>
                    <button type="button" class="map-tool" data-tool="node" data-node-type="splitter">Splitter</button>
                    <button type="button" class="map-tool" data-tool="node" data-node-type="tj_box">TJ Box</button>
                    <button type="button" class="map-tool" data-tool="node" data-node-type="onu">ONU</button>
                </div>
            </div>

            <div class="tool-section">
                <h2>Links</h2>
                <button type="button" class="map-tool wide" data-tool="fiber">Fiber Cable</button>
                <p class="hint">Click points to trace a route. Double-click or press Enter to finish.</p>
            </div>

            <div class="map-actions">
                <button type="button" class="btn" id="saveTopology">Save Network</button>
                <button type="button" class="btn light" id="exportGeojson">Show GeoJSON</button>
                <button type="button" class="btn light" id="cancelDraft">Cancel Draw</button>
            </div>

            <div class="tool-section">
                <h2>Map Visibility</h2>
                <details class="visibility-dropdown" id="visibilityDropdown">
                    <summary>Show / Hide Items</summary>
                    <div class="visibility-menu" id="visibilityControls"></div>
                </details>
                <div class="visibility-actions">
                    <button type="button" class="btn light" id="showAllMapItems">Show All</button>
                    <button type="button" class="btn light" id="hideAllMapItems">Hide All</button>
                </div>
            </div>

            <div class="network-stats" id="networkStats"></div>
            <pre class="geojson-preview" id="geojsonPreview" hidden></pre>
        </aside>

        <div class="map-stage">
            <div id="networkMap"></div>
            <div class="map-status" id="mapStatus">Loading topology...</div>
        </div>
    </section>

    <div class="network-modal" id="featureModal" hidden>
        <form class="network-form" id="featureForm">
            <div class="form-head">
                <div>
                    <p class="eyebrow" id="formMode">New feature</p>
                    <h2 id="formTitle">Infrastructure Details</h2>
                </div>
                <button type="button" class="icon-button" id="closeFeatureForm" aria-label="Close">x</button>
            </div>
            <div class="form-fields" id="featureFields"></div>
            <div class="form-actions">
                <button type="button" class="btn light danger" id="deleteFeature">Delete</button>
                <button type="submit" class="btn">Save Feature</button>
            </div>
        </form>
    </div>

    <script>
        window.NETWORK_MAP_CONFIG = {
            indexUrl: @json(route('network-map.features.index', ['map' => $activeMap->slug])),
            customersUrl: @json(route('network-map.customers.index')),
            storeUrl: @json(route('network-map.features.store', ['map' => $activeMap->slug])),
            photoUploadUrl: @json(route('network-map.photos.store')),
            initialCustomerId: @json($initialCustomerId),
            activeMapId: @json($activeMap->id),
            activeMapName: @json($activeMap->name),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/maplibre-gl.js') }}?v=4.7.1"></script>
    <script src="{{ asset('js/network-map-d194101.js') }}?v=20260904-1"></script>
    <script>
        (function () {
            // Switch the active network map by reloading with ?map=<slug>.
            const picker = document.getElementById('mapPicker');
            if (picker) {
                picker.addEventListener('change', function () {
                    if (this.value) window.location.href = this.value;
                });
            }

            // Lightweight typeahead to link a new map to a customer.
            const search = document.getElementById('newMapCustomerSearch');
            const hiddenId = document.getElementById('newMapCustomerId');
            const results = document.getElementById('newMapCustomerResults');
            const picked = document.getElementById('newMapCustomerPicked');
            if (!search || !hiddenId || !results) return;

            const customersUrl = window.NETWORK_MAP_CONFIG.customersUrl;
            let timer = null;

            function clearPick() {
                hiddenId.value = '';
                picked.hidden = true;
                picked.textContent = '';
            }

            function renderResults(features) {
                results.innerHTML = '';
                if (!features.length) {
                    results.hidden = true;
                    return;
                }
                features.slice(0, 20).forEach(function (feature) {
                    const props = feature.properties || {};
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'search-result';
                    item.textContent = '#' + props.customer_id + ' — ' + (props.customer_name || 'Unnamed');
                    item.addEventListener('click', function () {
                        hiddenId.value = props.customer_id;
                        search.value = props.customer_name || ('#' + props.customer_id);
                        picked.hidden = false;
                        picked.textContent = 'Linked to ' + item.textContent;
                        results.hidden = true;
                    });
                    results.appendChild(item);
                });
                results.hidden = false;
            }

            search.addEventListener('input', function () {
                clearPick();
                const q = search.value.trim();
                window.clearTimeout(timer);
                if (q.length < 2) {
                    results.hidden = true;
                    return;
                }
                timer = window.setTimeout(async function () {
                    try {
                        const url = new URL(customersUrl, window.location.origin);
                        url.searchParams.set('q', q);
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!response.ok) return;
                        const collection = await response.json();
                        renderResults((collection.features || []).filter(function (f) {
                            return f.properties && f.properties.customer_id;
                        }));
                    } catch (error) {
                        results.hidden = true;
                    }
                }, 250);
            });

            document.addEventListener('click', function (event) {
                if (!results.contains(event.target) && event.target !== search) {
                    results.hidden = true;
                }
            });
        })();
    </script>
@endsection
