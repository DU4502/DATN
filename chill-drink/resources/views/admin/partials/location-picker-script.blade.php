@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .location-picker {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            padding: 0.9rem;
            background: #f9fbfb;
        }

        .location-picker-label {
            color: #111827;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .location-picker-preview {
            font-size: 0.72rem;
            font-weight: 600;
        }

        .location-picker-btn {
            min-width: 172px;
            border-radius: 10px;
        }

        .location-picker-search {
            position: relative;
        }

        .location-picker-search .form-control {
            border-color: rgba(148, 163, 184, 0.36);
            border-radius: 10px;
            min-height: 40px;
            padding-right: 2.6rem;
            font-weight: 600;
        }

        .location-picker-search .form-control:focus {
            border-color: #0d9373;
            box-shadow: 0 0 0 0.16rem rgba(13, 147, 115, 0.12);
        }

        .location-picker-search-icon {
            position: absolute;
            top: 50%;
            right: 0.9rem;
            transform: translateY(-50%);
            color: #0d9373;
            pointer-events: none;
            z-index: 3;
        }

        .location-picker-suggestions {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            z-index: 1040;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid rgba(13, 147, 115, 0.18);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
        }

        .location-picker-suggestion {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
            padding: 0.75rem 0.9rem;
            text-align: left;
        }

        .location-picker-suggestion:last-child {
            border-bottom: 0;
        }

        .location-picker-suggestion:hover,
        .location-picker-suggestion:focus-visible {
            background: #eefbf7;
            outline: none;
        }

        .location-picker-suggestion-title {
            color: #111827;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .location-picker-suggestion-subtitle {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.12rem;
        }

        .location-picker-map {
            position: relative;
            height: 250px;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(13, 147, 115, 0.08), rgba(255, 255, 255, 0.92));
        }

        .location-picker-map.is-loading::after {
            content: 'Đang tải bản đồ...';
            position: absolute;
            inset: 0;
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d9373;
            font-size: 0.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, rgba(239, 253, 250, 0.96), rgba(255, 255, 255, 0.92));
            pointer-events: none;
        }

        .location-picker-status {
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .territory-map-label {
            background: transparent;
            border: 0;
        }

        .territory-map-label-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem 0.2rem 0.45rem;
            border: 1px solid rgba(13, 147, 115, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #0d6b5b;
            font-size: 0.68rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            transform: translate(-50%, -120%);
            pointer-events: none;
        }

        .territory-map-label-flag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 0.95rem;
            height: 0.7rem;
            flex: 0 0 auto;
        }

        .territory-map-label-flag svg {
            display: block;
            width: 100%;
            height: 100%;
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endonce

<script>
(() => {
    if (!window.L || window.ChillDrinkLocationPicker) {
        return;
    }

    const ADDRESS_LOOKUP_ENDPOINT = @json(\Illuminate\Support\Facades\Route::has('api.address-lookup') ? route('api.address-lookup') : null);
    const REVERSE_GEOCODE_ENDPOINT = @json(\Illuminate\Support\Facades\Route::has('api.reverse-geocode') ? route('api.reverse-geocode') : null);

    const DEFAULT_CENTER = {
        lat: 16.047079,
        lng: 108.206230,
        zoom: 5,
    };

    function parseNumber(value, fallback = null) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function formatCoordinates(lat, lng) {
        return `Tọa độ: ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        })[char]);
    }

    function compactAddress(parts) {
        return parts.map((part) => String(part || '').trim()).filter(Boolean).join(', ');
    }

    function readableAddressPart(value) {
        const text = String(value || '').trim();
        return isCoordinateLikeText(text) ? '' : text;
    }

    function uniqueAddressParts(parts) {
        const seen = new Set();
        const result = [];

        for (const part of parts) {
            let raw = String(part || '').trim();
            if (!raw) continue;
            raw = raw.replace(/\b(\S+)(?:\s+\1\b)+/gu, '$1');
            const tokens = raw.split(',').map((t) => t.trim().replace(/\b(\S+)(?:\s+\1\b)+/gu, '$1')).filter(Boolean);
            for (const token of tokens) {
                const key = token.toLocaleLowerCase('vi-VN');
                if (!seen.has(key)) {
                    seen.add(key);
                    result.push(token);
                }
            }
        }

        return result;
    }

    function isCoordinateLikeText(value) {
        const text = String(value || '').trim();
        return /^-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?$/.test(text);
    }

    function hasStructuredAddress(item = {}) {
        return Boolean(
            readableAddressPart(item?.house_number || item?.housenumber)
            || readableAddressPart(item?.road)
            || readableAddressPart(item?.road_name)
            || readableAddressPart(item?.street)
            || readableAddressPart(item?.area)
            || readableAddressPart(item?.ward)
            || readableAddressPart(item?.district)
            || readableAddressPart(item?.province)
        );
    }

    async function fetchJsonWithTimeout(url, options = {}, timeout = 8500) {
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
            });
            const payload = await response.json().catch(() => ({}));

            return { response, payload };
        } finally {
            window.clearTimeout(timer);
        }
    }

    async function reverseGeocodeFromBrowser(latitude, longitude) {
        const nominatimUrl = new URL('https://nominatim.openstreetmap.org/reverse');
        nominatimUrl.searchParams.set('format', 'jsonv2');
        nominatimUrl.searchParams.set('addressdetails', '1');
        nominatimUrl.searchParams.set('zoom', '19');
        nominatimUrl.searchParams.set('lat', String(latitude));
        nominatimUrl.searchParams.set('lon', String(longitude));
        nominatimUrl.searchParams.set('accept-language', 'vi');

        try {
            const { response, payload } = await fetchJsonWithTimeout(nominatimUrl.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'Accept-Language': 'vi',
                },
                cache: 'no-store',
            });

            if (response.ok && payload && (payload.address || payload.display_name)) {
                const address = payload.address || {};
                const road = address.road
                    || address.pedestrian
                    || address.footway
                    || address.path
                    || address.residential
                    || address.hamlet
                    || address.neighbourhood
                    || address.suburb
                    || address.village
                    || '';
                const ward = address.village
                    || address.suburb
                    || address.neighbourhood
                    || address.quarter
                    || address.hamlet
                    || address.residential
                    || '';
                const district = address.city_district
                    || address.district
                    || address.county
                    || address.town
                    || address.municipality
                    || address.city
                    || '';
                const province = address.state || address.region || address.province || '';

                return {
                    latitude,
                    longitude,
                    house_number: address.house_number || address.housenumber || '',
                    road,
                    ward,
                    district,
                    province,
                    street: compactAddress([address.house_number || address.housenumber, road]),
                    area: compactAddress([district, province, ward]),
                    display_name: payload.display_name || '',
                    source: 'nominatim-browser',
                };
            }
        } catch (error) {
            console.warn('Browser Nominatim reverse geocode failed:', error);
        }

        // Photon is already used by the address search and is a useful second
        // provider when the hosting server or Nominatim is unavailable.
        try {
            const photonUrl = new URL('https://photon.komoot.io/reverse');
            photonUrl.searchParams.set('lat', String(latitude));
            photonUrl.searchParams.set('lon', String(longitude));
            const { response, payload } = await fetchJsonWithTimeout(photonUrl.toString(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });
            const feature = payload?.features?.[0];

            if (response.ok && feature) {
                return normalizePhotonSuggestion(feature);
            }
        } catch (error) {
            console.warn('Browser Photon reverse geocode failed:', error);
        }

        return null;
    }

    function normalizeResolvedAddress(item = {}, fallback = {}) {
        const latitude = Number.parseFloat(item?.latitude ?? fallback.latitude);
        const longitude = Number.parseFloat(item?.longitude ?? fallback.longitude);
        const houseNumber = readableAddressPart(item?.house_number || item?.housenumber);
        const roadParts = [
            readableAddressPart(item?.road_name),
            readableAddressPart(item?.road),
            readableAddressPart(item?.street_name),
        ].filter(Boolean);
        if (!roadParts.length) {
            roadParts.push(readableAddressPart(item?.name));
        }
        const roadName = compactAddress(uniqueAddressParts(roadParts));
        const streetFallback = readableAddressPart(item?.street);
        const area = compactAddress(uniqueAddressParts([
            readableAddressPart(item?.area),
            readableAddressPart(item?.ward),
            readableAddressPart(item?.district),
            readableAddressPart(item?.province),
            readableAddressPart(item?.city),
            readableAddressPart(item?.county),
            readableAddressPart(item?.state),
            readableAddressPart(item?.country),
        ]));
        const street = compactAddress(uniqueAddressParts([
            houseNumber,
            roadName || streetFallback || readableAddressPart(item?.display_name) || readableAddressPart(fallback.displayName),
        ]));
        const displayName = compactAddress(uniqueAddressParts([
            street,
            area,
        ])) || readableAddressPart(item?.display_name || item?.displayName || item?.title || fallback.displayName);

        return {
            ...item,
            latitude,
            longitude,
            house_number: houseNumber,
            road_name: roadName || street,
            street,
            area,
            displayName,
            title: readableAddressPart(item?.title) || street || displayName || 'Địa chỉ được gợi ý',
            subtitle: readableAddressPart(item?.subtitle) || area || displayName || '',
            canAutofillCoordinates: item?.canAutofillCoordinates ?? item?.can_autofill_coordinates ?? true,
        };
    }

    function debounce(callback, delay = 450) {
        let timer = null;

        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => callback(...args), delay);
        };
    }

    function isContainerVisible(container) {
        if (!container) {
            return false;
        }

        const modal = container.closest('.modal');
        if (modal) {
            return modal.classList.contains('show');
        }

        return container.getClientRects().length > 0;
    }

    function distanceMeters(lat1, lng1, lat2, lng2) {
        const earthRadius = 6371000;
        const latDelta = (lat2 - lat1) * Math.PI / 180;
        const lngDelta = (lng2 - lng1) * Math.PI / 180;
        const startLat = lat1 * Math.PI / 180;
        const endLat = lat2 * Math.PI / 180;
        const a = Math.sin(latDelta / 2) ** 2
            + Math.cos(startLat) * Math.cos(endLat) * Math.sin(lngDelta / 2) ** 2;

        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function normalizePhotonSuggestion(feature) {
        const properties = feature?.properties || {};
        const coordinates = feature?.geometry?.coordinates || [];
        return normalizeResolvedAddress({
            latitude: coordinates[1],
            longitude: coordinates[0],
            house_number: properties.housenumber || properties.house_number || '',
            road_name: compactAddress(uniqueAddressParts([properties.street, properties.name])),
            street: properties.name || '',
            area: compactAddress(uniqueAddressParts([
                properties.district,
                properties.city,
                properties.county,
                properties.state,
                properties.country,
            ])),
            title: '',
            subtitle: '',
            canAutofillCoordinates: true,
        });
    }

    function normalizeInternalSuggestion(item) {
        const latitude = Number.parseFloat(item?.latitude);
        const longitude = Number.parseFloat(item?.longitude);
        const displayName = item?.full_address || item?.name || '';
        const match = displayName.match(/^(?:so\s*)?(\d+[a-z]?(?:\/\d+[a-z]?)*)(?:\s+|-|,)+(.*)$/iu);
        return normalizeResolvedAddress({
            latitude,
            longitude,
            house_number: match?.[1] || item?.house_number || '',
            road_name: item?.road_name || match?.[2]?.trim() || displayName,
            street: displayName,
            area: item?.area || '',
            title: item?.name || displayName || 'Địa chỉ Chill Drink đã ghi nhận',
            subtitle: item?.full_address || 'Dữ liệu địa chỉ đã lưu trong hệ thống',
            canAutofillCoordinates: item?.can_autofill_coordinates !== false,
        });
    }

    function mountPicker(container) {
        if (!container || container.dataset.locationPickerMounted === '1') {
            return container?.__locationPicker ?? null;
        }

        const mapEl = container.querySelector('[data-location-map]');
        const latInput = container.querySelector('[data-location-lat]');
        const lngInput = container.querySelector('[data-location-lng]');
        const previewEl = container.querySelector('[data-location-preview]');
        const statusEl = container.querySelector('[data-location-status]');
        const locateBtn = container.querySelector('[data-location-use-geolocation]');
        const searchInput = container.querySelector('[data-location-search-input]');
        const suggestionsEl = container.querySelector('[data-location-search-suggestions]');
        const addressTargetSelector = container.dataset.addressTarget;
        const houseTargetSelector = container.dataset.autoFillHouseTarget;
        const areaTargetSelector = container.dataset.autoFillAreaTarget;
        const streetTargetSelector = container.dataset.autoFillStreetTarget;
        const wardTargetSelector = container.dataset.autoFillWardTarget;
        const districtTargetSelector = container.dataset.autoFillDistrictTarget;
        const provinceTargetSelector = container.dataset.autoFillProvinceTarget;
        const showTerritoryLabels = container.dataset.showTerritoryLabels === '1';

        if (!mapEl || !latInput || !lngInput) {
            return null;
        }

        if (mapEl.offsetWidth < 48 || mapEl.offsetHeight < 48) {
            if (container.dataset.locationPickerMountPending !== '1') {
                container.dataset.locationPickerMountPending = '1';
                window.setTimeout(() => {
                    container.dataset.locationPickerMountPending = '0';
                    refreshPickers(container.closest('.modal') || container.parentElement || document);
                }, 80);
            }

            return null;
        }

        const initialLat = parseNumber(latInput.value, parseNumber(container.dataset.initialLat));
        const initialLng = parseNumber(lngInput.value, parseNumber(container.dataset.initialLng));
        const defaultLat = parseNumber(container.dataset.defaultLat, DEFAULT_CENTER.lat);
        const defaultLng = parseNumber(container.dataset.defaultLng, DEFAULT_CENTER.lng);
        const defaultZoom = parseNumber(container.dataset.defaultZoom, DEFAULT_CENTER.zoom);
        let previewLatitude = null;
        let previewLongitude = null;

        mapEl.classList.add('is-loading');
        const map = L.map(mapEl, {
            zoomControl: false,
            scrollWheelZoom: true,
            touchZoom: true,
            tap: true,
            center: Number.isFinite(initialLat) && Number.isFinite(initialLng)
                ? [initialLat, initialLng]
                : [defaultLat, defaultLng],
            zoom: Number.isFinite(initialLat) && Number.isFinite(initialLng) ? 15 : defaultZoom,
        });

        const clearLoadingState = () => {
            mapEl.classList.remove('is-loading');
        };

        const loadingTimeout = window.setTimeout(clearLoadingState, 12000);
        let fallbackSwapTimer = null;
        let tilesSettled = false;
        const finishLoading = () => {
            if (tilesSettled) {
                return;
            }

            tilesSettled = true;
            window.clearTimeout(loadingTimeout);
            if (fallbackSwapTimer) {
                window.clearTimeout(fallbackSwapTimer);
            }
            clearLoadingState();
        };

        const baseTileOptions = {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
            updateWhenIdle: false,
            updateWhenZooming: true,
            keepBuffer: 6,
            detectRetina: false,
        };

        const primaryTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            ...baseTileOptions,
            subdomains: 'abc',
        });
        const fallbackTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
            ...baseTileOptions,
            subdomains: 'abc',
            attribution: '&copy; OpenStreetMap contributors, Tiles style by HOT',
        });

        let tileFallbackSwapped = false;
        fallbackSwapTimer = window.setTimeout(() => {
            if (!tileFallbackSwapped) {
                tileFallbackSwapped = true;
                if (map.hasLayer(primaryTileLayer)) {
                    map.removeLayer(primaryTileLayer);
                }
                fallbackTileLayer.addTo(map);
            }
        }, 1800);

        const swapToFallback = () => {
            if (tileFallbackSwapped) {
                return;
            }

            tileFallbackSwapped = true;
            if (fallbackSwapTimer) {
                window.clearTimeout(fallbackSwapTimer);
            }
            if (map.hasLayer(primaryTileLayer)) {
                map.removeLayer(primaryTileLayer);
            }
            fallbackTileLayer.addTo(map);
        };

        primaryTileLayer.once('load', finishLoading);
        primaryTileLayer.once('tileerror', swapToFallback);
        fallbackTileLayer.once('load', finishLoading);
        fallbackTileLayer.once('tileerror', finishLoading);

        primaryTileLayer.addTo(map);

        const marker = L.marker(
            Number.isFinite(initialLat) && Number.isFinite(initialLng)
                ? [initialLat, initialLng]
                : [defaultLat, defaultLng],
            { draggable: true }
        ).addTo(map);

        if (showTerritoryLabels) {
            const territoryIcon = (label) => L.divIcon({
                className: 'territory-map-label',
                html: `
                    <span class="territory-map-label-chip">
                        <span class="territory-map-label-flag" aria-hidden="true">
                            <svg viewBox="0 0 30 20" role="img" focusable="false" aria-hidden="true">
                                <rect width="30" height="20" rx="3" fill="#da251d"></rect>
                                <path
                                    d="M15 4.2l1.74 3.53 3.9.57-2.82 2.75.67 3.89L15 13.9l-3.49 1.84.67-3.89-2.82-2.75 3.9-.57L15 4.2z"
                                    fill="#ffde00"
                                ></path>
                            </svg>
                        </span>
                        <span>${escapeHtml(label)}</span>
                    </span>`,
                iconSize: [1, 1],
                iconAnchor: [0, 0],
            });

            [
                { label: 'Hoàng Sa', lat: 16.5, lng: 112.0 },
                { label: 'Trường Sa', lat: 8.6402, lng: 111.9187 },
            ].forEach((territory) => {
                L.marker([territory.lat, territory.lng], {
                    icon: territoryIcon(territory.label),
                    interactive: false,
                    keyboard: false,
                    riseOnHover: false,
                }).addTo(map);
            });
        }

        const setCoordinates = (lat, lng, message = '', source = 'manual') => {
            const nextLat = Number.parseFloat(lat);
            const nextLng = Number.parseFloat(lng);

            if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
                return;
            }

            latInput.value = nextLat.toFixed(6);
            lngInput.value = nextLng.toFixed(6);
            previewLatitude = null;
            previewLongitude = null;
            marker.setLatLng([nextLat, nextLng]);
            map.setView([nextLat, nextLng], Math.max(map.getZoom(), 15), { animate: true });

            if (previewEl) {
                previewEl.textContent = formatCoordinates(nextLat, nextLng);
            }

            if (statusEl && message) {
                statusEl.textContent = message;
            }

            container.dispatchEvent(new CustomEvent('location-picker:change', {
                bubbles: true,
                detail: {
                    latitude: nextLat,
                    longitude: nextLng,
                    source,
                },
            }));

        };

        const previewCoordinates = (lat, lng, message = 'Đây là vị trí tham chiếu. Hãy xác nhận lại nếu muốn dùng cho đơn hàng.') => {
            const nextLat = Number.parseFloat(lat);
            const nextLng = Number.parseFloat(lng);

            if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
                return;
            }

            previewLatitude = nextLat;
            previewLongitude = nextLng;
            marker.setLatLng([nextLat, nextLng]);
            map.setView([nextLat, nextLng], Math.max(map.getZoom(), 15), { animate: true });

            if (previewEl) {
                previewEl.textContent = `Tham chiếu: ${formatCoordinates(nextLat, nextLng)}`;
            }

            if (statusEl && message) {
                statusEl.textContent = message;
            }
        };

        const getAddressTargets = () => {
            const selectors = addressTargetSelector ? addressTargetSelector.split(',') : [];

            return {
                houseInput: houseTargetSelector ? document.querySelector(houseTargetSelector.trim()) : null,
                streetInput: streetTargetSelector
                    ? document.querySelector(streetTargetSelector.trim())
                    : (selectors[0] ? document.querySelector(selectors[0].trim()) : null),
                areaInput: areaTargetSelector
                    ? document.querySelector(areaTargetSelector.trim())
                    : (selectors[1] ? document.querySelector(selectors[1].trim()) : null),
                wardInput: wardTargetSelector ? document.querySelector(wardTargetSelector.trim()) : null,
                districtInput: districtTargetSelector ? document.querySelector(districtTargetSelector.trim()) : null,
                provinceInput: provinceTargetSelector ? document.querySelector(provinceTargetSelector.trim()) : null,
            };
        };

        const fillAddressTargets = (item, preserveExisting = false) => {
            const normalized = normalizeResolvedAddress(item, {
                displayName: String(item?.displayName || item?.display_name || item?.title || '').trim(),
            });
            const { houseInput, streetInput, areaInput, wardInput, districtInput, provinceInput } = getAddressTargets();
            const currentHouseValue = String(houseInput?.value || '').trim();
            const currentStreetValue = String(streetInput?.value || '').trim();
            const currentAreaValue = String(areaInput?.value || '').trim();
            const displayText = String(normalized.displayName || normalized.title || '').trim();
            const displayTextIsCoordinate = isCoordinateLikeText(displayText);

            if (searchInput && displayText && !displayTextIsCoordinate
                && (!preserveExisting || !String(searchInput.value || '').trim())) {
                searchInput.value = displayText;
            }

            if (houseInput) {
                houseInput.value = normalized.house_number || (preserveExisting ? readableAddressPart(currentHouseValue) : '');
                houseInput.dispatchEvent(new Event('input', { bubbles: true }));
                houseInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (streetInput) {
                const nextStreetValue = compactAddress(uniqueAddressParts([
                    normalized.house_number,
                    normalized.road_name || normalized.street,
                ]));
                const nextStreet = readableAddressPart(nextStreetValue);
                streetInput.value = nextStreet || (preserveExisting ? readableAddressPart(currentStreetValue) : readableAddressPart(normalized.street));
                streetInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (areaInput) {
                areaInput.value = normalized.area || (preserveExisting ? readableAddressPart(currentAreaValue) : '');
                areaInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (wardInput && normalized.ward) {
                wardInput.value = normalized.ward;
                wardInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (districtInput && normalized.district) {
                districtInput.value = normalized.district;
                districtInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (provinceInput && normalized.province) {
                provinceInput.value = normalized.province;
                provinceInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            return normalized;
        };

        const lookupKnownAddressSuggestion = async (query, latitude, longitude) => {
            const normalizedQuery = String(query || '').trim();
            if (!ADDRESS_LOOKUP_ENDPOINT || normalizedQuery.length < 3) {
                return null;
            }

            const url = new URL(ADDRESS_LOOKUP_ENDPOINT, window.location.origin);
            url.searchParams.set('q', normalizedQuery);
            url.searchParams.set('limit', '1');

            if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
                url.searchParams.set('latitude', String(latitude));
                url.searchParams.set('longitude', String(longitude));
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !Array.isArray(payload?.data) || payload.data.length === 0) {
                return null;
            }

            const match = payload.data[0];
            return normalizeResolvedAddress(match, {
                latitude,
                longitude,
                displayName: normalizedQuery,
            });
        };

        const hideSuggestions = () => {
            if (!suggestionsEl) {
                return;
            }

            suggestionsEl.classList.add('d-none');
            suggestionsEl.innerHTML = '';
        };

        const applySuggestion = (item) => {
            if (!item || !Number.isFinite(item.latitude) || !Number.isFinite(item.longitude)) {
                return;
            }

            if (searchInput) {
                searchInput.value = item.displayName || item.title || '';
            }

            fillAddressTargets(item);

            setCoordinates(item.latitude, item.longitude, 'Đã chọn địa chỉ từ gợi ý.', 'search');
            hideSuggestions();
        };

        const renderSuggestions = (items) => {
            if (!suggestionsEl) {
                return;
            }

            if (!items.length) {
                suggestionsEl.innerHTML = '<div class="location-picker-suggestion-title px-3 py-2">Không tìm thấy gợi ý phù hợp.</div>';
                suggestionsEl.classList.remove('d-none');
                return;
            }

            suggestionsEl.innerHTML = items.map((item, index) => `
                <button type="button" class="location-picker-suggestion" data-location-search-suggestion="${index}">
                    <div class="location-picker-suggestion-title">${escapeHtml(item.title)}</div>
                    <div class="location-picker-suggestion-subtitle">${escapeHtml(item.subtitle || item.displayName)}</div>
                </button>
            `).join('');
            suggestionsEl.classList.remove('d-none');

            suggestionsEl.querySelectorAll('[data-location-search-suggestion]').forEach((button) => {
                button.addEventListener('click', () => {
                    applySuggestion(items[Number(button.dataset.locationSearchSuggestion)]);
                });
            });
        };

        const searchAddress = async () => {
            const query = String(searchInput?.value || '').trim();
            if (query.length < 3) {
                hideSuggestions();
                return;
            }

            try {
                const currentLat = parseNumber(latInput.value, previewLatitude);
                const currentLng = parseNumber(lngInput.value, previewLongitude);
                const requests = [];

                if (ADDRESS_LOOKUP_ENDPOINT) {
                    const internalUrl = new URL(ADDRESS_LOOKUP_ENDPOINT, window.location.origin);
                    internalUrl.searchParams.set('q', query);
                    internalUrl.searchParams.set('limit', '8');
                    if (Number.isFinite(currentLat) && Number.isFinite(currentLng)) {
                        internalUrl.searchParams.set('latitude', String(currentLat));
                        internalUrl.searchParams.set('longitude', String(currentLng));
                    }

                    requests.push(
                        fetch(internalUrl.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        }).then((response) => response.ok ? response.json() : { data: [] })
                    );
                } else {
                    requests.push(Promise.resolve({ data: [] }));
                }

                const photonUrl = new URL('https://photon.komoot.io/api/');
                photonUrl.searchParams.set('q', query);
                photonUrl.searchParams.set('limit', '10');
                requests.push(fetch(photonUrl.toString()).then((response) => response.ok ? response.json() : { features: [] }));

                const [internalResult, photonResult] = await Promise.allSettled(requests);
                const internalItems = internalResult.status === 'fulfilled'
                    ? (internalResult.value.data || [])
                        .map(normalizeInternalSuggestion)
                        .filter((item) => item.canAutofillCoordinates && Number.isFinite(item.latitude) && Number.isFinite(item.longitude))
                    : [];
                const photonItems = photonResult.status === 'fulfilled'
                    ? (photonResult.value.features || [])
                        .map(normalizePhotonSuggestion)
                        .filter((item) => Number.isFinite(item.latitude) && Number.isFinite(item.longitude))
                        .filter((item) => !internalItems.some((internalItem) => (
                            distanceMeters(item.latitude, item.longitude, internalItem.latitude, internalItem.longitude) <= 80
                        )))
                    : [];
                const seen = new Set();
                const items = internalItems.concat(photonItems).filter((item) => {
                    const key = `${item.title}|${item.latitude.toFixed(4)}|${item.longitude.toFixed(4)}`.toLocaleLowerCase('vi-VN');
                    if (seen.has(key)) {
                        return false;
                    }

                    seen.add(key);
                    return true;
                }).slice(0, 16);

                renderSuggestions(items);
            } catch (error) {
                console.error('Lỗi khi tìm địa chỉ:', error);
                hideSuggestions();
            }
        };

        if (searchInput && suggestionsEl) {
            const debouncedSearch = debounce(searchAddress);
            searchInput.addEventListener('input', debouncedSearch);
            searchInput.addEventListener('focus', debouncedSearch);
            document.addEventListener('click', (event) => {
                if (!container.contains(event.target)) {
                    hideSuggestions();
                }
            });
        }

        marker.on('dragend', () => {
            const position = marker.getLatLng();
            setCoordinates(position.lat, position.lng, 'Đã cập nhật vị trí đã chọn.');
        });

        map.on('click', (event) => {
            setCoordinates(event.latlng.lat, event.latlng.lng, 'Đã chọn vị trí từ bản đồ.');
        });

        locateBtn?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                if (statusEl) {
                    statusEl.textContent = 'Trình duyệt không hỗ trợ định vị.';
                }
                return;
            }

            if (statusEl) {
                statusEl.textContent = 'Đang lấy vị trí hiện tại...';
            }

            navigator.geolocation.getCurrentPosition((position) => {
                setCoordinates(position.coords.latitude, position.coords.longitude, 'Đã lấy vị trí hiện tại. Bạn có thể kéo pin để chỉnh lại.', 'geolocation');
            }, () => {
                if (statusEl) {
                    statusEl.textContent = 'Không thể lấy vị trí hiện tại. Hãy chọn trực tiếp trên bản đồ.';
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        });

        const getAddressBtn = container.querySelector('[data-location-get-address]');

        getAddressBtn?.addEventListener('click', async () => {
            const selectedLat = parseNumber(latInput.value);
            const selectedLng = parseNumber(lngInput.value);
            const lat = Number.isFinite(selectedLat) ? selectedLat : previewLatitude;
            const lng = Number.isFinite(selectedLng) ? selectedLng : previewLongitude;

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                if (statusEl) {
                    statusEl.textContent = 'Vui lòng chọn vị trí trên bản đồ trước.';
                }
                return;
            }

            if (!Number.isFinite(selectedLat) || !Number.isFinite(selectedLng)) {
                setCoordinates(lat, lng, 'Đã xác nhận vị trí hiện tại để lấy địa chỉ.', 'preview-confirmed');
            }

            const originalText = getAddressBtn.innerHTML;
            getAddressBtn.disabled = true;
            getAddressBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lấy...';

            try {
                if (!REVERSE_GEOCODE_ENDPOINT) {
                    throw new Error('Hệ thống chưa bật dịch vụ lấy địa chỉ.');
                }

                const reverseUrl = new URL(REVERSE_GEOCODE_ENDPOINT, window.location.origin);
                reverseUrl.searchParams.set('latitude', lat);
                reverseUrl.searchParams.set('longitude', lng);

                const response = await fetch(reverseUrl.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });
                let data = await response.json().catch(() => ({}));
                const compactHelper = (parts) => parts.map((part) => String(part || '').trim()).filter(Boolean).join(', ');

                if (!response.ok || !hasStructuredAddress(data)) {
                    const browserAddress = await reverseGeocodeFromBrowser(lat, lng);
                    if (browserAddress) {
                        data = {
                            ...data,
                            ...browserAddress,
                        };
                    }
                }

                if (!response.ok && !hasStructuredAddress(data)) {
                    throw new Error(data.message || 'Không thể lấy địa chỉ lúc này.');
                }

                const houseNumber = String(data.house_number || '').trim();
                const roadLine = compactHelper([
                    data.road || data.street,
                ]);
                const areaLine = compactHelper([
                    data.area || '',
                    data.ward,
                    data.district,
                    data.province,
                ]);
                const displayText = data.display_name
                    || compactHelper([roadLine, areaLine]);
                const hasResolvedAddress = Boolean(
                    houseNumber
                    || roadLine
                    || areaLine
                    || (data.area && !isCoordinateLikeText(data.area))
                    || (data.display_name && !isCoordinateLikeText(data.display_name))
                );
                const resolvedAddress = fillAddressTargets({
                    latitude: Number.parseFloat(lat),
                    longitude: Number.parseFloat(lng),
                    house_number: houseNumber,
                    road_name: roadLine,
                    street: roadLine || '',
                    area: data.area || areaLine || '',
                    ward: data.ward,
                    district: data.district,
                    province: data.province,
                    display_name: readableAddressPart(displayText),
                    title: readableAddressPart(data.display_name),
                    subtitle: areaLine || displayText,
                    canAutofillCoordinates: true,
                }, true);

                const lookupQuery = compactAddress(uniqueAddressParts([
                    resolvedAddress.house_number,
                    resolvedAddress.road_name,
                    resolvedAddress.area,
                ]));

                if (lookupQuery.length >= 3) {
                    const learnedAddress = await lookupKnownAddressSuggestion(lookupQuery, Number.parseFloat(lat), Number.parseFloat(lng));
                    if (learnedAddress) {
                        // Address lookup results may only contain street data;
                        // do not erase the area already returned by geocoding.
                        fillAddressTargets(learnedAddress, true);
                    }
                }

                if (statusEl) {
                    statusEl.textContent = !hasResolvedAddress
                        ? 'Chưa tìm thấy địa chỉ chi tiết tại vị trí này.'
                        : 'Đã tự động lấy và điền địa chỉ thành công.';
                }
            } catch (error) {
                console.error('Lỗi khi gọi reverse geocode:', error);
                if (statusEl) {
                    statusEl.textContent = error?.message || 'Có lỗi xảy ra khi lấy địa chỉ.';
                }
            } finally {
                getAddressBtn.disabled = false;
                getAddressBtn.innerHTML = originalText;
            }
        });

        if (Number.isFinite(initialLat) && Number.isFinite(initialLng)) {
            setCoordinates(initialLat, initialLng, 'Đã tải vị trí đã lưu.');
        } else if (statusEl) {
            statusEl.textContent = 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.';
        }

        container.dataset.locationPickerMounted = '1';
        container.dataset.locationPickerMountPending = '0';
        container.__locationPicker = {
            map,
            marker,
            container,
            latInput,
            lngInput,
            previewEl,
            statusEl,
            defaultLat,
            defaultLng,
            defaultZoom,
            setCoordinates,
            previewCoordinates,
            clearSelection(message = 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.') {
                latInput.value = '';
                lngInput.value = '';
                previewLatitude = null;
                previewLongitude = null;
                marker.setLatLng([defaultLat, defaultLng]);
                map.setView([defaultLat, defaultLng], defaultZoom, { animate: true });

                if (previewEl) {
                    previewEl.textContent = 'Chưa chọn vị trí';
                }

                if (statusEl) {
                    statusEl.textContent = message;
                }
            },
            invalidateSelection(message = 'Địa chỉ vừa thay đổi. Vui lòng kiểm tra lại vị trí trên bản đồ.') {
                const latitude = Number.parseFloat(latInput.value);
                const longitude = Number.parseFloat(lngInput.value);

                if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                    this.clearSelection(message);
                    return;
                }

                if (statusEl) {
                    statusEl.textContent = message;
                }
            },
            refresh() {
                map.invalidateSize();
            },
        };

        return container.__locationPicker;
    }

    function refreshPickers(root = document) {
        root.querySelectorAll('[data-location-picker]').forEach((container) => {
            if (!isContainerVisible(container)) {
                return;
            }

            const picker = mountPicker(container);
            if (picker) {
                [0, 80, 240, 520].forEach((delay) => {
                    window.setTimeout(() => picker.refresh(), delay);
                });
            }
        });
    }

    window.ChillDrinkLocationPicker = {
        mount: mountPicker,
        refresh: refreshPickers,
        set(container, lat, lng, message = '') {
            const picker = mountPicker(container);
            if (picker) {
                picker.setCoordinates(lat, lng, message);
            }
        },
        preview(container, lat, lng, message = 'Đây là vị trí tham chiếu. Hãy xác nhận lại nếu muốn dùng cho đơn hàng.') {
            const picker = mountPicker(container);
            if (picker) {
                picker.previewCoordinates(lat, lng, message);
            }
        },
        clear(container, message = 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.') {
            const picker = mountPicker(container);
            if (picker) {
                picker.clearSelection(message);
            }
        },
        invalidate(container, message = 'Địa chỉ vừa thay đổi. Vui lòng kiểm tra lại vị trí trên bản đồ.') {
            const picker = mountPicker(container);
            if (picker) {
                picker.invalidateSelection(message);
            }
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        refreshPickers();
    });

    document.addEventListener('shown.bs.modal', (event) => {
        refreshPickers(event.target);
    });
})();
</script>
