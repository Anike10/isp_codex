(function () {
    const fallbackCenter = [89.1219, 23.9013];
    const basemaps = {
        voyager: {
            tiles: ['https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', 'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', 'https://c.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png'],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
        osm: {
            tiles: ['https://a.tile.openstreetmap.org/{z}/{x}/{y}.png', 'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png', 'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png'],
            attribution: '&copy; OpenStreetMap contributors',
        },
        light: {
            tiles: ['https://a.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', 'https://b.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', 'https://c.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png'],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
        dark: {
            tiles: ['https://a.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', 'https://b.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', 'https://c.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png'],
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        },
        satellite: {
            tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
            attribution: 'Tiles &copy; Esri',
        },
        google_road: {
            tiles: ['https://mt0.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', 'https://mt2.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'],
            attribution: '&copy; Google',
        },
        google_satellite: {
            tiles: ['https://mt0.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', 'https://mt2.google.com/vt/lyrs=s&x={x}&y={y}&z={z}'],
            attribution: '&copy; Google',
        },
    };

    function initialize(root) {
        if (root.dataset.mapInitialized === '1' || typeof window.maplibregl === 'undefined') return;
        root.dataset.mapInitialized = '1';

        const editable = root.dataset.editable === '1';
        const latitudeInput = root.querySelector('[data-map-latitude]');
        const longitudeInput = root.querySelector('[data-map-longitude]');
        const stateLabel = root.querySelector('[data-location-state]');
        const shareInput = root.querySelector('[data-share-url]');
        const copyButton = root.querySelector('[data-copy-location]');
        const whatsappButton = root.querySelector('[data-whatsapp-share]');
        const mapCanvas = root.querySelector('[data-map-canvas]');
        let marker = null;

        const coordinates = () => {
            const latitude = Number(latitudeInput.value);
            const longitude = Number(longitudeInput.value);
            return latitudeInput.value !== '' && longitudeInput.value !== '' && Number.isFinite(latitude) && Number.isFinite(longitude)
                && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180
                ? { latitude, longitude }
                : null;
        };

        const saved = coordinates();
        const map = new maplibregl.Map({
            container: mapCanvas,
            style: {
                version: 8,
                sources: Object.fromEntries(Object.entries(basemaps).map(([key, item]) => [`party-basemap-${key}`, {
                    type: 'raster',
                    tiles: item.tiles,
                    tileSize: 256,
                    attribution: item.attribution,
                }])),
                layers: Object.keys(basemaps).map((key) => ({
                    id: `party-basemap-${key}`,
                    type: 'raster',
                    source: `party-basemap-${key}`,
                    layout: { visibility: key === 'voyager' ? 'visible' : 'none' },
                })),
            },
            center: saved ? [saved.longitude, saved.latitude] : fallbackCenter,
            zoom: saved ? 17 : 14,
            maxZoom: 22,
        });

        map.addControl(new maplibregl.NavigationControl(), 'top-right');

        const updateShare = (point) => {
            const mapUrl = point ? `https://maps.google.com/?q=${point.latitude.toFixed(8)},${point.longitude.toFixed(8)}` : '';
            const shareText = point ? [
                `Party ID: #${root.dataset.partyId}`,
                `Name: ${root.dataset.partyName || 'Not provided'}`,
                `Mobile: ${root.dataset.partyPhone || 'Not provided'}`,
                `Address: ${root.dataset.partyAddress || 'Not provided'}`,
                `Connection/User ID: ${root.dataset.partyUserId || 'Not assigned'}`,
                `Map location: ${mapUrl}`,
            ].join('\n') : '';
            shareInput.value = shareText;
            copyButton.disabled = !point;
            whatsappButton.classList.toggle('disabled', !point);
            whatsappButton.href = point ? `https://wa.me/?text=${encodeURIComponent(shareText)}` : '#';
        };

        const setLocation = (latitude, longitude, centerMap = false) => {
            latitudeInput.value = Number(latitude).toFixed(8);
            longitudeInput.value = Number(longitude).toFixed(8);

            if (!marker) {
                marker = new maplibregl.Marker({ color: '#0f766e', draggable: editable })
                    .setLngLat([longitude, latitude])
                    .addTo(map);
                if (editable) {
                    marker.on('dragend', () => {
                        const point = marker.getLngLat();
                        setLocation(point.lat, point.lng);
                    });
                }
            } else {
                marker.setLngLat([longitude, latitude]);
            }

            if (centerMap) map.flyTo({ center: [longitude, latitude], zoom: Math.max(map.getZoom(), 17) });
            stateLabel.textContent = `Selected: ${Number(latitude).toFixed(6)}, ${Number(longitude).toFixed(6)}`;
            stateLabel.classList.add('selected');
            updateShare({ latitude: Number(latitude), longitude: Number(longitude) });
        };

        map.on('load', () => {
            if (saved) setLocation(saved.latitude, saved.longitude);
        });

        if (editable) {
            map.on('click', (event) => setLocation(event.lngLat.lat, event.lngLat.lng));
            [latitudeInput, longitudeInput].forEach((input) => input.addEventListener('change', () => {
                const point = coordinates();
                if (point) setLocation(point.latitude, point.longitude, true);
            }));

            root.querySelector('[data-clear-location]').addEventListener('click', () => {
                latitudeInput.value = '';
                longitudeInput.value = '';
                marker?.remove();
                marker = null;
                stateLabel.textContent = 'No location selected';
                stateLabel.classList.remove('selected');
                updateShare(null);
            });
        }

        root.querySelectorAll('[data-map-style]').forEach((button) => {
            button.addEventListener('click', () => {
                const selected = button.dataset.mapStyle;
                Object.keys(basemaps).forEach((key) => map.setLayoutProperty(`party-basemap-${key}`, 'visibility', key === selected ? 'visible' : 'none'));
                root.querySelectorAll('[data-map-style]').forEach((item) => item.classList.toggle('active', item === button));
            });
        });

        copyButton.addEventListener('click', async () => {
            if (!shareInput.value) return;
            try {
                await navigator.clipboard.writeText(shareInput.value);
            } catch (error) {
                shareInput.select();
                document.execCommand('copy');
            }
            const original = copyButton.textContent;
            copyButton.textContent = 'Copied';
            window.setTimeout(() => { copyButton.textContent = original; }, 1400);
        });

        whatsappButton.addEventListener('click', (event) => {
            if (whatsappButton.classList.contains('disabled')) event.preventDefault();
        });

        updateShare(saved);
    }

    const initializeAll = () => document.querySelectorAll('[data-customer-location-map]').forEach(initialize);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAll);
    else initializeAll();
})();
