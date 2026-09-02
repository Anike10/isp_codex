@php
    $editable = (bool) ($editable ?? false);
    $showEditAction = (bool) ($showEditAction ?? true);
    $latitude = $editable ? old('map_latitude', $customer->map_latitude) : $customer->map_latitude;
    $longitude = $editable ? old('map_longitude', $customer->map_longitude) : $customer->map_longitude;
    $hasLocation = is_numeric($latitude) && is_numeric($longitude);
    $mapUrl = $hasLocation ? 'https://maps.google.com/?q='.$latitude.','.$longitude : '';
    $partyUserId = $customer->mikrotik_username ?: $customer->connection_id ?: 'Not assigned';
    $shareText = $hasLocation ? implode("\n", [
        'Party ID: #'.$customer->id,
        'Name: '.$customer->name,
        'Mobile: '.($customer->phone ?: 'Not provided'),
        'Address: '.($customer->address ?: 'Not provided'),
        'Connection/User ID: '.$partyUserId,
        'Map location: '.$mapUrl,
    ]) : '';
@endphp

<link rel="stylesheet" href="{{ asset('css/maplibre-gl.css') }}?v=4.7.1">
<link rel="stylesheet" href="{{ asset('css/customer-location-picker.css') }}?v=20260813-3">

<section
    class="party-location-card {{ $editable ? 'full' : 'party-location-card--view' }}"
    data-customer-location-map
    data-editable="{{ $editable ? '1' : '0' }}"
    data-party-id="{{ $customer->id }}"
    data-party-name="{{ $customer->name }}"
    data-party-phone="{{ $customer->phone }}"
    data-party-address="{{ $customer->address }}"
    data-party-user-id="{{ $partyUserId }}"
    aria-labelledby="party-location-title-{{ $customer->id }}"
>
    <div class="party-location-head">
        <div>
            <span class="party-location-kicker">Party ID #{{ $customer->id }}</span>
            <h2 id="party-location-title-{{ $customer->id }}">Map Location</h2>
            <p>{{ $editable ? 'Click the map or drag the marker to set the exact location.' : 'Saved service location for this party.' }}</p>
        </div>
        <div class="party-location-actions">
            <a class="btn light" href="{{ route('network-map.index', ['customer_id' => $customer->id]) }}">Open Network Map</a>
            @if ($editable)
                <button class="btn light" data-clear-location type="button">Clear Location</button>
            @elseif ($showEditAction)
                <a class="btn secondary" href="{{ route('customers.edit', $customer) }}#party-location-title-{{ $customer->id }}">Edit Location</a>
            @endif
        </div>
    </div>

    <div class="party-map-style-panel" aria-label="Map Style">
        <strong>Map Style</strong>
        <div class="party-map-style-options">
            @foreach (['voyager' => 'Voyager', 'osm' => 'OSM', 'light' => 'Light', 'dark' => 'Dark', 'satellite' => 'Satellite', 'google_road' => 'Google Road', 'google_satellite' => 'Google Sat'] as $styleKey => $styleLabel)
                <button type="button" data-map-style="{{ $styleKey }}" class="{{ $styleKey === 'google_road' ? 'active' : '' }}">{{ $styleLabel }}</button>
            @endforeach
        </div>
    </div>

    <div id="partyLocationPicker" data-map-canvas aria-label="{{ $editable ? 'Select party map location' : 'Party map location' }}"></div>

    <div class="party-coordinate-grid">
        <label>Latitude
            <input data-map-latitude type="number" @if($editable) name="map_latitude" @else readonly @endif min="-90" max="90" step="0.00000001" value="{{ $latitude }}" placeholder="23.90130000">
        </label>
        <label>Longitude
            <input data-map-longitude type="number" @if($editable) name="map_longitude" @else readonly @endif min="-180" max="180" step="0.00000001" value="{{ $longitude }}" placeholder="89.12200000">
        </label>
        <div class="party-location-state {{ $hasLocation ? 'selected' : '' }}" data-location-state>
            {{ $hasLocation ? 'Saved map location' : 'No location selected' }}
        </div>
    </div>

    <div class="party-location-share">
        <label>Copy Map Location
            <textarea data-share-url rows="6" placeholder="Select and save a map location first" readonly>{{ $shareText }}</textarea>
        </label>
        <button class="btn light" data-copy-location type="button" @disabled(! $hasLocation)>Copy</button>
        <a class="btn party-whatsapp {{ $hasLocation ? '' : 'disabled' }}" data-whatsapp-share href="{{ $hasLocation ? 'https://wa.me/?text='.rawurlencode($shareText) : '#' }}" target="_blank" rel="noopener">WhatsApp</a>
    </div>
</section>

<script src="{{ asset('js/maplibre-gl.js') }}?v=4.7.1"></script>
<script src="{{ asset('js/customer-location-map.js') }}?v=20260827-6"></script>
