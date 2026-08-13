@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/maplibre-gl.css') }}?v=4.7.1">
    <link rel="stylesheet" href="{{ asset('css/network-map.css') }}?v=20260813-7">

    <section class="network-map-page">
        <aside class="network-sidebar">
            <div>
                <p class="eyebrow">GIS Operations</p>
                <h1>FTTX Network Map</h1>
                <p class="muted">Draw nodes and fiber routes, add infrastructure attributes, then persist the full topology as GeoJSON.</p>
            </div>

            <div class="tool-section">
                <h2>Map Style</h2>
                <div class="basemap-grid" id="basemapTools">
                    <button type="button" class="basemap-tool active" data-basemap="voyager">Voyager</button>
                    <button type="button" class="basemap-tool" data-basemap="osm">OSM</button>
                    <button type="button" class="basemap-tool" data-basemap="light">Light</button>
                    <button type="button" class="basemap-tool" data-basemap="dark">Dark</button>
                    <button type="button" class="basemap-tool" data-basemap="satellite">Satellite</button>
                    <button type="button" class="basemap-tool" data-basemap="google_road">Google Road</button>
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
                <h2>Party Search</h2>
                <div class="party-placement-panel" id="partyPlacementPanel" hidden>
                    <p class="party-placement-title">Place party location</p>
                    <div class="party-placement-info" id="partyPlacementInfo"></div>
                    <div class="party-placement-actions">
                        <button type="button" class="btn secondary" id="startPartyPlacementBtn">Add on map</button>
                        <button type="button" class="btn light" id="cancelPartyPlacementBtn">Cancel</button>
                    </div>
                </div>
                <form class="location-search" id="customerSearch">
                    <input type="search" id="customerIdQuery" placeholder="Party name, mobile, or connection ID" value="{{ $initialCustomerId }}">
                    <button type="submit" class="btn secondary">Search Party</button>
                </form>
                <div class="search-results" id="customerSearchResult" hidden></div>
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
            indexUrl: @json(route('network-map.features.index')),
            customersUrl: @json(route('network-map.customers.index')),
            storeUrl: @json(route('network-map.features.store')),
            photoUploadUrl: @json(route('network-map.photos.store')),
            initialCustomerId: @json($initialCustomerId),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/maplibre-gl.js') }}?v=4.7.1"></script>
    <script src="{{ asset('js/network-map.js') }}?v=20260813-18"></script>
@endsection
