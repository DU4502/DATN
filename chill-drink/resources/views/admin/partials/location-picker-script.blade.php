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

        .location-picker-search {
            position: relative;
        }

        .location-picker-search-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .location-picker-search-box {
            position: relative;
        }

        .location-picker-search-box i {
            position: absolute;
            top: 50%;
            left: 0.85rem;
            transform: translateY(-50%);
            color: #0d9373;
            z-index: 2;
        }

        .location-picker-search-box .form-control {
            border-radius: 14px;
            padding-left: 2.35rem;
            border-color: rgba(13, 147, 115, 0.22);
            min-height: 44px;
            font-weight: 600;
        }

        .location-picker-suggestions {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            z-index: 1080;
            overflow: hidden;
            border: 1px solid rgba(13, 147, 115, 0.18);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
        }

        .location-picker-suggestion {
            width: 100%;
            border: 0;
            background: #fff;
            padding: 0.75rem 0.9rem;
            text-align: left;
            display: block;
            border-bottom: 1px solid #eef2f7;
        }

        .location-picker-suggestion:last-child {
            border-bottom: 0;
        }

        .location-picker-suggestion:hover,
        .location-picker-suggestion:focus-visible {
            background: #ecfaf6;
            outline: none;
        }

        .location-picker-suggestion-title {
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 800;
        }

        .location-picker-suggestion-subtitle {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 600;
            margin-top: 0.15rem;
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endonce

<script>
(() => {
    if (!window.L || window.ChillDrinkLocationPicker) {
        return;
    }

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

    function compactAddress(parts) {
        return parts.map((part) => String(part || '').trim()).filter(Boolean).join(', ');
    }

    function debounce(callback, delay = 450) {
        let timer = null;

        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => callback(...args), delay);
        };
    }

    function uniqueParts(parts) {
        const seen = new Set();

        return parts.filter((part) => {
            const value = String(part || '').trim();
            const key = value.toLocaleLowerCase('vi-VN');

            if (!value || seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizePhotonFeature(feature) {
        const properties = feature?.properties || {};
        const coordinates = feature?.geometry?.coordinates || [];
        const lng = Number.parseFloat(coordinates[0]);
        const lat = Number.parseFloat(coordinates[1]);
        const street = compactAddress(uniqueParts([
            properties.housenumber,
            properties.street,
            properties.name,
        ]));
        const area = compactAddress(uniqueParts([
            properties.district,
            properties.city,
            properties.county,
            properties.state,
            properties.country,
        ]));
        const displayName = compactAddress(uniqueParts([
            street,
            area,
        ]));

        return {
            lat,
            lng,
            street,
            area,
            title: street || properties.name || displayName || 'Địa chỉ được gợi ý',
            subtitle: area || displayName || '',
            displayName,
        };
    }

    function normalizeInternalAddressSuggestion(item) {
        const lat = Number.parseFloat(item?.latitude);
        const lng = Number.parseFloat(item?.longitude);
        const displayName = item?.full_address || item?.name || '';

        return {
            lat,
            lng,
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
        const searchInput = container.querySelector('[data-location-search]');
        const suggestionsEl = container.querySelector('[data-location-suggestions]');

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

        const addressTargetSelector = container.dataset.addressTarget;
        const getAddressBtn = container.querySelector('[data-location-get-address]');

        const getAddressTargets = () => {
            const selectors = addressTargetSelector ? addressTargetSelector.split(',') : [];

            return {
                streetInput: selectors[0] ? document.querySelector(selectors[0].trim()) : null,
                areaInput: selectors[1] ? document.querySelector(selectors[1].trim()) : null,
            };
        };

        const fillAddressTargets = (streetLine, areaLine, displayName = '') => {
            const { streetInput, areaInput } = getAddressTargets();

            if (streetInput && areaInput) {
                streetInput.value = streetLine || displayName;
                areaInput.value = areaLine || displayName;
                streetInput.dispatchEvent(new Event('input', { bubbles: true }));
                areaInput.dispatchEvent(new Event('input', { bubbles: true }));
                return;
            }

            if (streetInput) {
                streetInput.value = displayName || streetLine || areaLine;
                streetInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

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

        const hideSuggestions = () => {
            if (!suggestionsEl) {
                return;
            }

            suggestionsEl.classList.add('d-none');
            suggestionsEl.innerHTML = '';
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
                <button type="button" class="location-picker-suggestion" data-suggestion-index="${index}">
                    <div class="location-picker-suggestion-title">${escapeHtml(item.title)}</div>
                    <div class="location-picker-suggestion-subtitle">${escapeHtml(item.subtitle || item.displayName)}</div>
                </button>
            `).join('');
            suggestionsEl.classList.remove('d-none');

            suggestionsEl.querySelectorAll('[data-suggestion-index]').forEach((button) => {
                button.addEventListener('click', () => {
                    const item = items[Number(button.dataset.suggestionIndex)];

                    if (!item || !Number.isFinite(item.lat) || !Number.isFinite(item.lng)) {
                        return;
                    }

                    if (searchInput) {
                        searchInput.value = item.displayName || item.title;
                    }

                    setCoordinates(item.lat, item.lng, 'Đã chọn địa chỉ từ gợi ý. Bạn có thể kéo pin để chỉnh lại.', 'search');
                    fillAddressTargets(item.street, item.area, item.displayName);
                    hideSuggestions();
                });
            });
        };

        const searchAddress = debounce(async () => {
            const query = String(searchInput?.value || '').trim();

            if (query.length < 3) {
                hideSuggestions();
                return;
            }

            if (statusEl) {
                statusEl.textContent = 'Đang tìm gợi ý địa chỉ...';
            }

            try {
                const internalUrl = new URL('/api/address-lookup', window.location.origin);
                internalUrl.searchParams.set('q', query);
                internalUrl.searchParams.set('limit', '8');
                const currentLat = parseNumber(latInput.value);
                const currentLng = parseNumber(lngInput.value);
                if (Number.isFinite(currentLat) && Number.isFinite(currentLng)) {
                    internalUrl.searchParams.set('latitude', String(currentLat));
                    internalUrl.searchParams.set('longitude', String(currentLng));
                }

                const photonUrl = new URL('https://photon.komoot.io/api/');
                photonUrl.searchParams.set('q', query);
                // Photon currently rejects the `lang` parameter with HTTP 400.
                // Its default response already contains Vietnamese OSM names.
                photonUrl.searchParams.set('limit', '10');

                const [internalResult, photonResult] = await Promise.allSettled([
                    fetch(internalUrl.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then((response) => response.ok ? response.json() : { data: [] }),
                    fetch(photonUrl.toString()).then((response) => response.ok ? response.json() : { features: [] }),
                ]);

                const internalItems = internalResult.status === 'fulfilled'
                    ? (internalResult.value.data || [])
                        .map(normalizeInternalAddressSuggestion)
                        .filter((item) => item.canAutofillCoordinates && Number.isFinite(item.lat) && Number.isFinite(item.lng))
                    : [];
                const photonItems = photonResult.status === 'fulfilled'
                    ? (photonResult.value.features || [])
                    .map(normalizePhotonFeature)
                    .filter((item) => Number.isFinite(item.lat) && Number.isFinite(item.lng))
                    : [];
                const seen = new Set();
                const items = internalItems.concat(photonItems).filter((item) => {
                    const key = `${item.title}|${item.lat.toFixed(4)}|${item.lng.toFixed(4)}`.toLocaleLowerCase('vi-VN');
                    if (seen.has(key)) {
                        return false;
                    }

                    seen.add(key);
                    return true;
                }).slice(0, 16);

                renderSuggestions(items);

                if (statusEl) {
                    statusEl.textContent = items.length
                        ? 'Chọn một gợi ý để tự điền tọa độ và địa chỉ.'
                        : 'Không tìm thấy gợi ý. Bạn có thể nhập rõ hơn hoặc chọn trên bản đồ.';
                }
            } catch (error) {
                console.error('Lỗi khi tìm địa chỉ:', error);
                hideSuggestions();

                if (statusEl) {
                    statusEl.textContent = 'Không tìm được gợi ý lúc này. Bạn vẫn có thể lấy vị trí hiện tại hoặc chọn trên bản đồ.';
                }
            }
        }, 500);

        searchInput?.addEventListener('input', searchAddress);
        searchInput?.addEventListener('focus', searchAddress);
        document.addEventListener('click', (event) => {
            if (!container.contains(event.target)) {
                hideSuggestions();
            }
        });

        getAddressBtn?.addEventListener('click', async () => {
            const lat = latInput.value;
            const lng = lngInput.value;

            if (!lat || !lng) {
                if (statusEl) {
                    statusEl.textContent = 'Vui lòng chọn vị trí trên bản đồ trước.';
                }
                return;
            }

            const originalText = getAddressBtn.innerHTML;
            getAddressBtn.disabled = true;
            getAddressBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lấy...';

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                const data = await response.json();
                
                if (data && data.display_name) {
                    const address = data.address || {};
                    const streetLine = compactAddress([
                        address.house_number,
                        address.road || address.pedestrian || address.footway,
                        address.neighbourhood || address.suburb
                    ]) || data.display_name || `${lat}, ${lng}`;

                    const areaLine = compactAddress([
                        address.quarter || address.ward || address.suburb || address.village,
                        address.city_district || address.district || address.town,
                        address.city || address.state
                    ]) || data.display_name || `${lat}, ${lng}`;

                    fillAddressTargets(streetLine, areaLine, data.display_name);

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
