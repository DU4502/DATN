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
