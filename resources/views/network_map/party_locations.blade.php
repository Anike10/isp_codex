@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/maplibre-gl.css') }}?v=4.7.1">
    <link rel="stylesheet" href="{{ asset('css/network-map.css') }}?v=20260813-8">

    <section class="network-map-page">
        <aside class="network-sidebar">
            <div>
                <p class="eyebrow">Party Map</p>
                <h1>Party Location Manager</h1>
                <p class="muted">Manage party coordinates with one click: add, move or remove map location.</p>
            </div>

            <div class="tool-section">
                <h2>Map Style</h2>
                <div class="basemap-grid" id="partyBasemapTools">
                    <button type="button" class="basemap-tool" data-basemap="voyager">Voyager</button>
                    <button type="button" class="basemap-tool" data-basemap="osm">OSM</button>
                    <button type="button" class="basemap-tool" data-basemap="light">Light</button>
                    <button type="button" class="basemap-tool" data-basemap="dark">Dark</button>
                    <button type="button" class="basemap-tool" data-basemap="satellite">Satellite</button>
                    <button type="button" class="basemap-tool active" data-basemap="google_road">Google Road</button>
                    <button type="button" class="basemap-tool" data-basemap="google_satellite">Google Sat</button>
                </div>
            </div>

            <div class="tool-section">
                <h2>Party Search</h2>
                <form class="location-search" id="partyLocationSearchForm">
                    <input type="search" id="partyLocationQuery" placeholder="Party #, name, mobile, user ID, address">
                    <button type="submit" class="btn secondary">Search Party</button>
                </form>
                <div class="party-location-search-status search-empty" id="partyLocationSearchStatus" hidden></div>
            </div>

            <div class="party-placement-panel" id="partyPlacementPanel" hidden>
                <p class="party-placement-title" id="partyPlacementTitle">Place party location</p>
                <div class="party-placement-info" id="partyPlacementInfo"></div>
                <div class="party-placement-actions">
                    <button type="button" class="btn light" id="cancelPartyPlacementBtn">Cancel</button>
                </div>
            </div>

            <div class="tool-section">
                <h2>Party List</h2>
                <p class="party-location-stats" id="partyLocationStats">Loading parties...</p>
                <div class="party-list" id="partyList">
                    <div class="search-empty">Loading parties...</div>
                </div>
            </div>
        </aside>

        <div class="map-stage">
            <div id="networkMap"></div>
            <div class="map-status" id="mapStatus">Loading party map...</div>
        </div>
    </section>

    <script>
        window.NETWORK_PARTY_LOCATIONS_CONFIG = {
            customersUrl: @json(route('network-map.customers.index')),
            csrfToken: @json(csrf_token()),
            initialCustomerId: @json($initialCustomerId),
        };
    </script>
    <script src="{{ asset('js/maplibre-gl.js') }}?v=4.7.1"></script>
    <script src="{{ asset('js/network-party-locations.js') }}?v=20260827-8"></script>
@endsection
