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
            height: 250px;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(13, 147, 115, 0.08), rgba(255, 255, 255, 0.92));
        }

        .location-picker-status {
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 500;
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

    function uniqueAddressParts(parts) {
        const seen = new Set();

        return parts
            .map((part) => String(part || '').trim())
            .filter((part) => {
                if (!part) {
                    return false;
                }

                const key = part.toLocaleLowerCase('vi-VN');
                if (seen.has(key)) {
                    return false;
                }

                seen.add(key);
                return true;
            });
    }

    function debounce(callback, delay = 450) {
        let timer = null;

        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => callback(...args), delay);
        };
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
        const longitude = Number.parseFloat(coordinates[0]);
        const latitude = Number.parseFloat(coordinates[1]);
        const street = compactAddress(uniqueAddressParts([
            properties.housenumber,
            properties.street,
            properties.name,
        ]));
        const area = compactAddress(uniqueAddressParts([
            properties.district,
            properties.city,
            properties.county,
            properties.state,
            properties.country,
        ]));
        const displayName = compactAddress(uniqueAddressParts([street, area]));

        return {
            latitude,
            longitude,
            street: street || displayName,
            area,
            title: street || properties.name || displayName || 'Địa chỉ được gợi ý',
            subtitle: area || displayName || '',
            displayName,
            canAutofillCoordinates: true,
        };
    }

    function normalizeInternalSuggestion(item) {
        const latitude = Number.parseFloat(item?.latitude);
        const longitude = Number.parseFloat(item?.longitude);
        const displayName = item?.full_address || item?.name || '';

        return {
            latitude,
            longitude,
            street: displayName,
            area: '',
            title: item?.name || displayName || 'Địa chỉ Chill Drink đã ghi nhận',
            subtitle: item?.full_address || 'Dữ liệu địa chỉ đã lưu trong hệ thống',
            displayName,
            canAutofillCoordinates: item?.can_autofill_coordinates !== false,
        };
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

        if (!mapEl || !latInput || !lngInput) {
            return null;
        }

        const initialLat = parseNumber(latInput.value, parseNumber(container.dataset.initialLat));
        const initialLng = parseNumber(lngInput.value, parseNumber(container.dataset.initialLng));
        const defaultLat = parseNumber(container.dataset.defaultLat, DEFAULT_CENTER.lat);
        const defaultLng = parseNumber(container.dataset.defaultLng, DEFAULT_CENTER.lng);
        const defaultZoom = parseNumber(container.dataset.defaultZoom, DEFAULT_CENTER.zoom);

        const map = L.map(mapEl, {
            zoomControl: true,
            scrollWheelZoom: false,
            center: Number.isFinite(initialLat) && Number.isFinite(initialLng)
                ? [initialLat, initialLng]
                : [defaultLat, defaultLng],
            zoom: Number.isFinite(initialLat) && Number.isFinite(initialLng) ? 15 : defaultZoom,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const marker = L.marker(
            Number.isFinite(initialLat) && Number.isFinite(initialLng)
                ? [initialLat, initialLng]
                : [defaultLat, defaultLng],
            { draggable: true }
        ).addTo(map);

        const setCoordinates = (lat, lng, message = '', source = 'manual') => {
            const nextLat = Number.parseFloat(lat);
            const nextLng = Number.parseFloat(lng);

            if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
                return;
            }

            latInput.value = nextLat.toFixed(6);
            lngInput.value = nextLng.toFixed(6);
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

        const getAddressTargets = () => {
            const selectors = addressTargetSelector ? addressTargetSelector.split(',') : [];

            return {
                streetInput: selectors[0] ? document.querySelector(selectors[0].trim()) : null,
                areaInput: selectors[1] ? document.querySelector(selectors[1].trim()) : null,
            };
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

            const { streetInput, areaInput } = getAddressTargets();

            if (searchInput) {
                searchInput.value = item.displayName || item.title || '';
            }

            if (streetInput) {
                streetInput.value = item.street || item.displayName || item.title || '';
                streetInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (areaInput) {
                areaInput.value = item.area || '';
                areaInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

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
                const currentLat = parseNumber(latInput.value);
                const currentLng = parseNumber(lngInput.value);
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
            const lat = latInput.value;
            const lng = lngInput.value;

            if (!lat || !lng) {
                if (statusEl) {
                    statusEl.textContent = 'Vui lòng chọn vị trí trên bản đồ trước.';
                }
                return;
            }

            const { streetInput, areaInput } = getAddressTargets();

            const originalText = getAddressBtn.innerHTML;
            getAddressBtn.disabled = true;
            getAddressBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lấy...';

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                const data = await response.json();
                
                if (data && data.display_name) {
                    if (streetInput && areaInput) {
                        const address = data.address || {};
                        const compactHelper = (parts) => parts.filter(Boolean).join(', ');
                        
                        const streetLine = compactHelper([
                            address.house_number,
                            address.road || address.pedestrian || address.footway,
                            address.neighbourhood || address.suburb
                        ]) || data.display_name || `${lat}, ${lng}`;

                        const areaLine = compactHelper([
                            address.quarter || address.ward || address.suburb || address.village,
                            address.city_district || address.district || address.town,
                            address.city || address.state
                        ]) || data.display_name || `${lat}, ${lng}`;

                        streetInput.value = streetLine;
                        areaInput.value = areaLine;

                        streetInput.dispatchEvent(new Event('input', { bubbles: true }));
                        areaInput.dispatchEvent(new Event('input', { bubbles: true }));
                    } else if (streetInput) {
                        streetInput.value = data.display_name;
                        streetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    if (statusEl) {
                        statusEl.textContent = 'Đã tự động lấy và điền địa chỉ thành công.';
                    }
                } else {
                    if (statusEl) {
                        statusEl.textContent = 'Không tìm thấy địa chỉ cho tọa độ này.';
                    }
                }
            } catch (error) {
                console.error('Lỗi khi gọi Nominatim reverse geocode:', error);
                if (statusEl) {
                    statusEl.textContent = 'Có lỗi xảy ra khi lấy địa chỉ.';
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
            clearSelection(message = 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.') {
                latInput.value = '';
                lngInput.value = '';
                marker.setLatLng([defaultLat, defaultLng]);
                map.setView([defaultLat, defaultLng], defaultZoom, { animate: true });

                if (previewEl) {
                    previewEl.textContent = 'Chưa chọn vị trí';
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
            const picker = mountPicker(container);
            if (picker) {
                setTimeout(() => picker.refresh(), 60);
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
        clear(container, message = 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.') {
            const picker = mountPicker(container);
            if (picker) {
                picker.clearSelection(message);
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
