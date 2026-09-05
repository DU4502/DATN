@props(['order', 'liveUrl'])

@php
    $status = \App\Support\OrderStatus::normalize((string) $order->status);
    $isDelivery = ($order->fulfillment_type ?? 'delivery') === 'delivery';
    $orderRealtimeChannel = null;
    $orderRealtimePrivate = false;
    if (auth()->check() && (int) auth()->id() === (int) $order->user_id) {
        $orderRealtimeChannel = \App\Support\OrderRealtimeChannel::authenticated($order);
        $orderRealtimePrivate = true;
    } elseif ($order->isGuest() && \App\Support\GuestOrderAccess::canView($order, request())) {
        $orderRealtimeChannel = \App\Support\OrderRealtimeChannel::guest($order);
    }
@endphp

@if($isDelivery)
<section class="delivery-live-card mt-3"
         data-delivery-live
         data-order-id="{{ $order->id }}"
         data-live-url="{{ $liveUrl }}"
         data-realtime-channel="{{ $orderRealtimeChannel }}"
         data-realtime-private="{{ $orderRealtimePrivate ? '1' : '0' }}"
         data-scooter-icon="{{ asset('images/tracking/shipper-scooter.png') }}?v=datn3-map2">
    <div class="delivery-live-head">
        <div class="min-w-0">
            <div class="delivery-live-eyebrow"><span class="delivery-live-dot"></span> Theo dõi đơn hàng</div>
            <div class="fw-bold text-truncate" data-live-stage>Đang tải trạng thái...</div>
            <div class="small text-secondary d-none" data-live-updated></div>
        </div>
        <div class="text-end flex-shrink-0">
            <div class="fw-bold text-primary" data-live-distance>--</div>
            <div class="small text-secondary" data-live-eta>Quán → bạn</div>
        </div>
    </div>

    <div class="delivery-live-map-wrap">
        <div class="delivery-live-map" data-live-map>
            <div class="delivery-live-placeholder" data-live-placeholder>
                <div class="delivery-live-placeholder-icon"><i class="bi bi-map"></i></div>
                <div class="delivery-live-placeholder-title" data-live-placeholder-title>Đang mở bản đồ...</div>
                <div class="delivery-live-placeholder-text" data-live-empty-message>
                    Đang tải tuyến đường từ quán tới địa chỉ giao hàng.
                </div>
            </div>
            <div class="delivery-live-compass" data-live-compass title="Hướng phía trước">
                <i class="bi bi-navigation-fill"></i>
                <span>Hướng đi</span>
            </div>
            <div class="delivery-live-mode" data-live-mode>Đang tải...</div>
        </div>

        <div class="delivery-live-map-hint">
            <i class="bi bi-mouse2 me-1"></i>
            Máy tính: <strong>lăn chuột</strong> để zoom, <strong>giữ chuột trái</strong> để kéo. Điện thoại: dùng <strong>2 ngón</strong> để phóng to/thu nhỏ.
        </div>
    </div>

    <div class="delivery-live-foot">
        <div class="small min-w-0">
            <strong data-live-shipper>Đang chờ tài xế</strong>
            <span class="text-secondary" data-live-vehicle></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-live-follow>
            <i class="bi bi-crosshair me-1"></i>Bám theo tài xế
        </button>
    </div>

</section>
@endif

@include('partials.vietnam-territory-labels')

@once
<style>
.delivery-live-card{border:1px solid #dcebe7;border-radius:18px;overflow:hidden;background:#fff;--delivery-bearing:0deg}
.delivery-live-head,.delivery-live-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px}
.delivery-live-head{background:#f5fbf9;border-bottom:1px solid #e3eeeb}
.delivery-live-foot{border-top:1px solid #e3eeeb;flex-wrap:wrap}
.delivery-live-eyebrow{font-size:.76rem;font-weight:800;text-transform:uppercase;color:#0d9373;display:flex;align-items:center;gap:7px}
.delivery-live-dot{width:9px;height:9px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 5px rgba(22,163,74,.12);animation:deliveryPulse 1.6s infinite}
.delivery-live-map-wrap{padding:12px;background:#fbfdfc}
.delivery-live-map{height:360px;background:#eaf1ef;position:relative;border-radius:16px;overflow:hidden;border:1px solid #e5efec;isolation:isolate}
.delivery-live-map.is-loading::after{
    content:'Đang tải bản đồ...';
    position:absolute;
    inset:0;
    z-index:1100;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#0d9373;
    font-size:.82rem;
    font-weight:800;
    background:linear-gradient(135deg, rgba(247,253,250,.95), rgba(237,245,242,.92));
    pointer-events:none;
}
.delivery-live-map-hint{font-size:.8rem;color:#68807a;margin-top:9px;line-height:1.45}
.delivery-live-placeholder{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:18px;background:radial-gradient(circle at top,#f7fdfa 0%,#edf5f2 68%,#e7efec 100%);z-index:1000}
.delivery-live-placeholder.is-hidden{display:none}
.delivery-live-placeholder-icon{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:#fff;color:#0d9373;font-size:1.65rem;box-shadow:0 14px 32px rgba(13,147,115,.13);margin-bottom:12px;animation:deliveryFloat 2.2s ease-in-out infinite}
.delivery-live-placeholder-title{font-weight:900;font-size:1.02rem;color:#17332d;margin-bottom:5px}
.delivery-live-placeholder-text{max-width:440px;font-size:.9rem;color:#61716d;line-height:1.5}
.delivery-live-map .leaflet-control-attribution{font-size:.68rem}
.delivery-live-map .leaflet-control-zoom{border:0!important;box-shadow:0 10px 24px rgba(31,60,53,.16)!important}
.delivery-live-map .leaflet-control-zoom a{border:0!important;color:#174f43!important}
.delivery-live-compass{position:absolute;right:10px;top:10px;z-index:700;display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;background:rgba(255,255,255,.94);box-shadow:0 8px 24px rgba(20,74,63,.16);font-size:.72rem;font-weight:800;color:#22594c;pointer-events:none}
.delivery-live-compass i{display:inline-block;color:#0d9373;transform:rotate(var(--delivery-bearing));transition:transform .35s ease}
.delivery-live-mode{position:absolute;left:10px;top:10px;z-index:700;padding:7px 10px;border-radius:999px;background:rgba(17,62,52,.9);color:#fff;font-size:.72rem;font-weight:800;box-shadow:0 8px 22px rgba(0,0,0,.14);pointer-events:none}
.delivery-live-route-shadow{stroke-linecap:round;stroke-linejoin:round}
.delivery-live-prep-marker{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#fff;border:3px solid #14a274;color:#0d9373;font-size:20px;box-shadow:0 10px 24px rgba(13,147,115,.25);transform:rotate(var(--delivery-bearing));transition:transform .3s ease}
.delivery-live-customer-marker{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#fff;border:3px solid #0d9373;color:#0d9373;font-size:16px;box-shadow:0 8px 20px rgba(0,0,0,.18);transform:rotate(var(--delivery-bearing));transition:transform .3s ease}
.delivery-live-scooter-marker{width:58px;height:58px;display:flex;align-items:center;justify-content:center;transform:rotate(var(--delivery-bearing));transition:transform .3s ease}
.delivery-live-scooter-marker img{width:58px;height:58px;object-fit:contain;filter:drop-shadow(0 8px 14px rgba(0,0,0,.24));user-select:none;-webkit-user-drag:none}
@keyframes deliveryPulse{0%,100%{opacity:1}50%{opacity:.45}}
@keyframes deliveryFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@media(max-width:767px){.delivery-live-map{height:300px}.delivery-live-head{align-items:flex-start}.delivery-live-placeholder-text{font-size:.84rem}.delivery-live-map-hint{font-size:.75rem}.delivery-live-scooter-marker,.delivery-live-scooter-marker img{width:50px;height:50px}.delivery-live-compass span{display:none}}
</style>

<script>
(() => {
    if (window.__chillDeliveryTrackingBooted) return;
    window.__chillDeliveryTrackingBooted = true;

    const leafletCss = [
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css'
    ];
    const leafletJs = [
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js'
    ];

    const addStylesheet = href => {
        if ([...document.styleSheets].some(sheet => sheet.href === href)) return;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    };

    const loadScript = src => new Promise((resolve, reject) => {
        const existing = [...document.scripts].find(s => s.src === src);
        if (existing) {
            if (window.L) return resolve();
            existing.addEventListener('load', resolve, {once:true});
            existing.addEventListener('error', reject, {once:true});
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const ensureLeaflet = async () => {
        leafletCss.forEach(addStylesheet);
        if (window.L) return;
        let lastError = null;
        for (const src of leafletJs) {
            try {
                await loadScript(src);
                if (window.L) return;
            } catch (error) {
                lastError = error;
            }
        }
        throw lastError || new Error('Không tải được Leaflet.');
    };

    const distanceText = meters => {
        const n = Number(meters);
        if (!Number.isFinite(n)) return '--';
        return n >= 1000 ? `${(n / 1000).toFixed(n >= 10000 ? 0 : 1)} km` : `${Math.max(1, Math.round(n))} m`;
    };
    const etaText = seconds => {
        const n = Number(seconds);
        if (!Number.isFinite(n)) return '--';
        return `Khoảng ${Math.max(1, Math.round(n / 60))} phút`;
    };
    const speedKmhFromDistanceDuration = (meters, seconds) => {
        const distance = Number(meters);
        const duration = Number(seconds);
        if (!Number.isFinite(distance) || !Number.isFinite(duration) || distance <= 0 || duration <= 0) return null;
        return (distance / duration) * 3.6;
    };
    const speedText = (meters, seconds) => {
        const speed = speedKmhFromDistanceDuration(meters, seconds);
        if (!Number.isFinite(speed) || speed <= 0) return '';
        return `~${Math.max(1, Math.round(speed))} km/h`;
    };
    const toPoint = value => Array.isArray(value)
        ? [Number(value[0]), Number(value[1])]
        : value ? [Number(value.latitude), Number(value.longitude)] : null;
    const validPoint = point => Array.isArray(point) && Number.isFinite(point[0]) && Number.isFinite(point[1]);
    const haversine = (a, b) => {
        if (!validPoint(a) || !validPoint(b)) return Infinity;
        const rad = v => v * Math.PI / 180;
        const R = 6371000;
        const dLat = rad(b[0] - a[0]);
        const dLng = rad(b[1] - a[1]);
        const q = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a[0])) * Math.cos(rad(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.atan2(Math.sqrt(q), Math.sqrt(Math.max(0, 1 - q)));
    };
    const bearingBetween = (a, b) => {
        if (!validPoint(a) || !validPoint(b)) return 0;
        const rad = v => v * Math.PI / 180;
        const lat1 = rad(a[0]), lat2 = rad(b[0]);
        const dLng = rad(b[1] - a[1]);
        const y = Math.sin(dLng) * Math.cos(lat2);
        const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    };
    const normalizeBearing = value => ((Number(value) % 360) + 360) % 360;
    const bearingDelta = (from, to) => {
        let diff = normalizeBearing(to) - normalizeBearing(from);
        while (diff > 180) diff -= 360;
        while (diff < -180) diff += 360;
        return diff;
    };
    const smoothBearing = (current, target, factor = .34) => normalizeBearing(
        normalizeBearing(current) + (bearingDelta(current, target) * factor)
    );
    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const snapPointToGeometry = (point, geometry, maxDistanceMeters = 18) => {
        if (!validPoint(point) || !Array.isArray(geometry) || geometry.length < 2 || !window.L) return null;
        const project = candidate => L.CRS.EPSG3857.project(L.latLng(candidate[0], candidate[1]));
        const unproject = candidate => {
            const latLng = L.CRS.EPSG3857.unproject(candidate);
            return [latLng.lat, latLng.lng];
        };

        const source = project(point);
        let best = null;

        for (let index = 0; index < geometry.length - 1; index += 1) {
            const a = geometry[index];
            const b = geometry[index + 1];
            if (!validPoint(a) || !validPoint(b)) continue;
            const pa = project(a);
            const pb = project(b);
            const dx = pb.x - pa.x;
            const dy = pb.y - pa.y;
            const lengthSquared = (dx * dx) + (dy * dy);
            if (!lengthSquared) continue;

            const t = clamp((((source.x - pa.x) * dx) + ((source.y - pa.y) * dy)) / lengthSquared, 0, 1);
            const snapped = {
                x: pa.x + (dx * t),
                y: pa.y + (dy * t),
            };
            const distance = Math.hypot(source.x - snapped.x, source.y - snapped.y);

            if (!best || distance < best.distance) {
                best = {
                    distance,
                    point: unproject(snapped),
                };
            }
        }

        return best && best.distance <= maxDistanceMeters ? best : null;
    };
    const nextAheadPoint = (current, geometry, minMeters = 45) => {
        if (!validPoint(current) || !Array.isArray(geometry)) return null;
        let nearestIndex = 0;
        let nearestDistance = Infinity;
        geometry.forEach((point, index) => {
            const d = haversine(current, point);
            if (d < nearestDistance) { nearestDistance = d; nearestIndex = index; }
        });
        for (let i = nearestIndex + 1; i < geometry.length; i++) {
            if (haversine(current, geometry[i]) >= minMeters) return geometry[i];
        }
        return geometry[geometry.length - 1] || null;
    };
    const parseTime = value => {
        if (!value) return null;
        const raw = String(value).includes('T') ? value : String(value).replace(' ', 'T') + 'Z';
        const d = new Date(raw);
        return Number.isNaN(d.getTime()) ? null : d;
    };
    const freshnessText = value => {
        const d = parseTime(value);
        if (!d) return 'Vị trí vừa cập nhật';
        const seconds = Math.max(0, Math.round((Date.now() - d.getTime()) / 1000));
        if (seconds < 8) return 'Vị trí vừa cập nhật';
        if (seconds < 60) return `Cập nhật ${seconds} giây trước`;
        return `Cập nhật ${Math.round(seconds / 60)} phút trước`;
    };

    const boot = async () => {
        const roots = [...document.querySelectorAll('[data-delivery-live]')];
        if (!roots.length) return;

        try {
            await ensureLeaflet();
        } catch (error) {
            roots.forEach(root => {
                const title = root.querySelector('[data-live-placeholder-title]');
                const text = root.querySelector('[data-live-empty-message]');
                if (title) title.textContent = 'Không mở được bản đồ';
                if (text) text.textContent = 'Không tải được thư viện bản đồ. Hãy kiểm tra Internet rồi Ctrl + F5 trang này.';
            });
            console.error('[ChillDrink Tracking] Leaflet load failed', error);
            return;
        }

        roots.forEach(root => {
            const mapEl = root.querySelector('[data-live-map]');
            if (!mapEl || mapEl.dataset.ready === '1') return;
            mapEl.dataset.ready = '1';

            const liveUrl = root.dataset.liveUrl;
            const orderId = Number(root.dataset.orderId || 0);
            const realtimeChannel = root.dataset.realtimeChannel || '';
            const realtimePrivate = root.dataset.realtimePrivate === '1';
            const scooterIconUrl = root.dataset.scooterIcon;
            const stageEl = root.querySelector('[data-live-stage]');
            const updatedEl = root.querySelector('[data-live-updated]');
            const distanceEl = root.querySelector('[data-live-distance]');
            const etaEl = root.querySelector('[data-live-eta]');
            const shipperEl = root.querySelector('[data-live-shipper]');
            const vehicleEl = root.querySelector('[data-live-vehicle]');
            const followBtn = root.querySelector('[data-live-follow]');
            const placeholderEl = root.querySelector('[data-live-placeholder]');
            const placeholderTitleEl = root.querySelector('[data-live-placeholder-title]');
            const placeholderTextEl = root.querySelector('[data-live-empty-message]');
            const modeEl = root.querySelector('[data-live-mode]');
            const map = L.map(mapEl, {
                scrollWheelZoom: true,
                dragging: true,
                touchZoom: true,
                doubleClickZoom: true,
                zoomControl: false,
                boxZoom: false,
                keyboard: true,
                preferCanvas: true,
                inertia: true,
                inertiaDeceleration: 2600,
                zoomSnap: .25,
                zoomDelta: .5,
                wheelDebounceTime: 30,
            }).setView([19.8067, 105.7852], 13);
            map.scrollWheelZoom.enable();
            map.dragging.enable();
            map.touchZoom.enable();
            mapEl.classList.add('is-loading');

            const clearMapLoading = () => {
                mapEl.classList.remove('is-loading');
            };

            const primaryTiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                keepBuffer: 6,
                detectRetina: false,
                updateWhenIdle: false,
                updateWhenZooming: true,
                subdomains: 'abc',
                attribution: '&copy; OpenStreetMap contributors'
            });
            const fallbackTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
                keepBuffer: 6,
                detectRetina: false,
                updateWhenIdle: false,
                updateWhenZooming: true,
                subdomains: 'abcd',
                r: window.devicePixelRatio > 1 ? '@2x' : '',
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            });
            let tileFallbackSwapped = false;
            let tilesSettled = false;
            let fallbackSwapTimer = null;

            const finishTileLoading = () => {
                if (tilesSettled) return;
                tilesSettled = true;
                if (fallbackSwapTimer) {
                    window.clearTimeout(fallbackSwapTimer);
                    fallbackSwapTimer = null;
                }
                clearMapLoading();
            };

            const swapToFallbackTiles = () => {
                if (tileFallbackSwapped) return;
                tileFallbackSwapped = true;
                if (fallbackSwapTimer) {
                    window.clearTimeout(fallbackSwapTimer);
                    fallbackSwapTimer = null;
                }
                if (map.hasLayer(primaryTiles)) {
                    map.removeLayer(primaryTiles);
                }
                fallbackTiles.addTo(map);
            };

            fallbackSwapTimer = window.setTimeout(swapToFallbackTiles, 1800);
            primaryTiles.once('load', finishTileLoading);
            primaryTiles.once('tileerror', swapToFallbackTiles);
            fallbackTiles.once('load', finishTileLoading);
            fallbackTiles.once('tileerror', finishTileLoading);
            primaryTiles.addTo(map);
            window.ChillDrinkVietnamTerritoryLabels?.addToLeaflet(map);
            window.setTimeout(() => map.invalidateSize({pan:false}), 120);

            const prepIcon = L.divIcon({
                className: '',
                html: '<div class="delivery-live-prep-marker"><i class="bi bi-cup-straw"></i></div>',
                iconSize: [44,44], iconAnchor: [22,22]
            });
            const customerIcon = L.divIcon({
                className: '',
                html: '<div class="delivery-live-customer-marker"><i class="bi bi-house-door-fill"></i></div>',
                iconSize: [38,38], iconAnchor: [19,19]
            });
            const shipperIcon = L.divIcon({
                className: '',
                html: `<div class="delivery-live-scooter-marker"><img src="${scooterIconUrl}" alt="Tài xế"></div>`,
                iconSize: [58,58], iconAnchor: [29,29]
            });

            let prepMarker = null;
            let customerMarker = null;
            let shipperMarker = null;
            let routeShadow = null;
            let routeLine = null;
            let lastGeometry = null;
            let lastBranchPoint = null;
            let lastCustomerPoint = null;
            let lastData = null;
            let lastMode = null;
            let pollBusy = false;
            let forcedRefreshQueued = false;
            let pollCount = 0;
            let timer = null;
            let stopped = false;
            let following = true;
            let headingMode = false;
            let currentBearing = 0;
            let markerAnimationFrame = null;
            let followAnimationFrame = null;
            let followMoveTimeout = null;
            let lastFollowAt = 0;
            let nextPollTimeout = null;
            let lastRoutePollAt = 0;
            let lastRouteRequestedFrom = null;
            let lastRenderedPoint = null;
            let lastRenderedUpdatedAt = null;
            let routeRefreshAfterMs = 12000;

            const setPlaceholder = (show, title = '', message = '') => {
                placeholderEl?.classList.toggle('is-hidden', !show);
                if (title && placeholderTitleEl) placeholderTitleEl.textContent = title;
                if (message && placeholderTextEl) placeholderTextEl.textContent = message;
            };
            const remove = layer => { if (layer && map.hasLayer(layer)) map.removeLayer(layer); };
            const clearPrep = () => { remove(prepMarker); prepMarker = null; };
            const clearShipper = () => { remove(shipperMarker); shipperMarker = null; };
            const clearRoute = () => { remove(routeShadow); remove(routeLine); routeShadow = null; routeLine = null; };
            const easeOutCubic = t => 1 - Math.pow(1 - t, 3);

            const setBearingVisual = (bearing, immediate = false) => {
                const target = Number.isFinite(bearing) ? normalizeBearing(bearing) : 0;
                currentBearing = immediate ? target : smoothBearing(currentBearing, target, .38);
                root.style.setProperty('--delivery-bearing', `${currentBearing.toFixed(2)}deg`);
            };

            const fitPreview = (a, b) => {
                if (!validPoint(a) || !validPoint(b)) return;
                headingMode = false;
                setBearingVisual(0, true);
                applyHeadingMap();
                map.fitBounds(L.latLngBounds([a,b]), {padding:[55,55], maxZoom:16, animate:false});
            };

            const refreshMapSize = () => {
                [0, 60, 180, 420, 900].forEach(delay => {
                    setTimeout(() => {
                        if (!stopped) map.invalidateSize({pan:false});
                    }, delay);
                });
            };

            const smoothMapTo = (point, zoom = null, duration = 1100) => {
                if (!validPoint(point)) return;
                if (followAnimationFrame) cancelAnimationFrame(followAnimationFrame);
                if (followMoveTimeout) clearTimeout(followMoveTimeout);

                const targetZoom = Number.isFinite(Number(zoom)) ? Number(zoom) : map.getZoom();
                const startCenter = map.getCenter();
                const startZoom = map.getZoom();
                const start = [startCenter.lat, startCenter.lng];
                const started = performance.now();

                if (Math.abs(targetZoom - startZoom) > .01) {
                    map.setZoom(targetZoom, {animate:true});
                }

                const frame = now => {
                    const t = Math.min(1, (now - started) / duration);
                    const eased = easeOutCubic(t);
                    const lat = start[0] + (point[0] - start[0]) * eased;
                    const lng = start[1] + (point[1] - start[1]) * eased;
                    map.panTo([lat, lng], {animate:false});
                    if (headingMode) applyHeadingMap();
                    if (t < 1) followAnimationFrame = requestAnimationFrame(frame);
                };

                followAnimationFrame = requestAnimationFrame(frame);
                followMoveTimeout = setTimeout(() => {
                    map.panTo(point, {animate:true, duration:.35});
                    if (headingMode) applyHeadingMap();
                }, duration + 40);
            };

            const applyHeadingMap = () => {
                const pane = map._mapPane;
                if (!pane) return;
                pane.style.transform = String(pane.style.transform || '').replace(/\srotate\([^)]*\)/g, '').trim();
                pane.style.transformOrigin = '';
            };

            const disableHeadingForManualUse = () => {
                if (!headingMode) return;
                headingMode = false;
                following = false;
                if (followAnimationFrame) cancelAnimationFrame(followAnimationFrame);
                if (followMoveTimeout) clearTimeout(followMoveTimeout);
                const pane = map._mapPane;
                if (pane) {
                    pane.style.transform = String(pane.style.transform || '').replace(/\srotate\([^)]*\)/g, '').trim();
                }
                setBearingVisual(0, true);
            };

            mapEl.addEventListener('wheel', disableHeadingForManualUse, {capture:true, passive:true});
            mapEl.addEventListener('pointerdown', disableHeadingForManualUse, true);
            mapEl.addEventListener('touchstart', disableHeadingForManualUse, {capture:true, passive:true});
            map.on('moveend zoomend', () => { if (headingMode) applyHeadingMap(); });

            const drawRoute = (geometry, isPreview = false) => {
                if (!Array.isArray(geometry) || geometry.length < 2) return;
                clearRoute();
                routeShadow = L.polyline(geometry, {
                    color: '#ffffff', weight: 10, opacity: .92,
                    lineJoin: 'round', className: 'delivery-live-route-shadow'
                }).addTo(map);
                routeLine = L.polyline(geometry, {
                    color: '#0d9373',
                    weight: 6,
                    opacity: isPreview ? .82 : .95,
                    lineJoin: 'round',
                    dashArray: isPreview ? '12 10' : null
                }).addTo(map);
            };

            const animateMarkerTo = (marker, to, duration = 1000) => {
                if (!marker || !validPoint(to)) return;
                if (markerAnimationFrame) cancelAnimationFrame(markerAnimationFrame);
                const fromLatLng = marker.getLatLng();
                const from = [fromLatLng.lat, fromLatLng.lng];
                const started = performance.now();
                const frame = now => {
                    const t = Math.min(1, (now - started) / duration);
                    const eased = easeOutCubic(t);
                    const lat = from[0] + (to[0] - from[0]) * eased;
                    const lng = from[1] + (to[1] - from[1]) * eased;
                    marker.setLatLng([lat,lng]);
                    if (t < 1) markerAnimationFrame = requestAnimationFrame(frame);
                };
                markerAnimationFrame = requestAnimationFrame(frame);
            };

            const placeCustomer = (point, data) => {
                if (!validPoint(point)) return;
                if (!customerMarker) {
                    customerMarker = L.marker(point, {icon: customerIcon, zIndexOffset: 500}).addTo(map)
                        .bindPopup(data?.customer?.address || 'Địa chỉ giao hàng');
                } else customerMarker.setLatLng(point);
            };

            const placePrep = (point, data) => {
                if (!validPoint(point)) return;
                clearShipper();
                if (!prepMarker) {
                    prepMarker = L.marker(point, {icon: prepIcon, zIndexOffset: 900}).addTo(map)
                        .bindPopup(`<strong>${data?.branch?.label || 'Chill Drink'}</strong><br>Quán đang pha chế`);
                } else prepMarker.setLatLng(point);
            };

            const placeShipper = (point, data, smooth = true) => {
                if (!validPoint(point)) return;
                clearPrep();
                const updatedAt = parseTime(data?.current?.updated_at);
                if (!shipperMarker) {
                    shipperMarker = L.marker(point, {icon: shipperIcon, zIndexOffset: 1200}).addTo(map)
                        .bindPopup(data?.shipper?.name || 'Tài xế');
                } else {
                    const currentLatLng = shipperMarker.getLatLng();
                    const from = [currentLatLng.lat, currentLatLng.lng];
                    const jumpDistance = haversine(from, point);
                    let duration = 0;

                    if (smooth && Number.isFinite(jumpDistance) && jumpDistance >= 2 && jumpDistance <= 220) {
                        const elapsedMs = updatedAt && lastRenderedUpdatedAt
                            ? Math.max(0, updatedAt.getTime() - lastRenderedUpdatedAt.getTime())
                            : 1000;
                        duration = clamp(elapsedMs || 1000, 700, 1600);
                    }

                    if (duration > 0) animateMarkerTo(shipperMarker, point, duration);
                    else shipperMarker.setLatLng(point);
                }

                lastRenderedPoint = point;
                if (updatedAt) lastRenderedUpdatedAt = updatedAt;
            };

            const updateHeadingAndFollow = (current, geometry, shipperMode, data = null) => {
                if (!shipperMode || !validPoint(current)) {
                    headingMode = false;
                    setBearingVisual(0, true);
                    applyHeadingMap();
                    return;
                }
                const ahead = nextAheadPoint(current, geometry, 45) || geometry?.[geometry.length - 1];
                const reportedBearing = Number(data?.current?.heading);
                const bearing = Number.isFinite(reportedBearing)
                    ? reportedBearing
                    : bearingBetween(current, ahead);
                setBearingVisual(bearing);

                if (following) {
                    headingMode = true;
                    const lookAhead = nextAheadPoint(current, geometry, 75) || current;
                    const now = performance.now();
                    const duration = now - lastFollowAt < 1600 ? 900 : 1200;
                    lastFollowAt = now;
                    smoothMapTo(lookAhead, Math.max(16, map.getZoom()), duration);
                }
            };

            const renderData = (data, forceFit = false) => {
                lastData = data;
                const branch = toPoint(data.branch);
                const customer = toPoint(data.customer);
                const currentRaw = toPoint(data.current) || branch;
                const shipperMode = typeof data.mode === 'string' && data.mode.startsWith('shipper');
                lastBranchPoint = branch;
                lastCustomerPoint = customer;
                const apiGeometry = Array.isArray(data.route?.geometry)
                    ? data.route.geometry.map(toPoint).filter(validPoint)
                    : null;

                if (apiGeometry && apiGeometry.length >= 2) {
                    lastGeometry = apiGeometry;
                    drawRoute(lastGeometry, !shipperMode);
                } else if (!lastGeometry || lastMode !== data.mode) {
                    lastGeometry = validPoint(currentRaw) && validPoint(customer) ? [currentRaw, customer] : null;
                    if (lastGeometry) drawRoute(lastGeometry, !shipperMode);
                }
                lastMode = data.mode;
                routeRefreshAfterMs = Number.isFinite(Number(data.route_refresh_after_ms))
                    ? Number(data.route_refresh_after_ms)
                    : 12000;

                let current = currentRaw;
                if (shipperMode && validPoint(currentRaw) && Array.isArray(lastGeometry) && lastGeometry.length >= 2) {
                    const snapThreshold = data?.current?.filtered ? 20 : 14;
                    const snapped = snapPointToGeometry(currentRaw, lastGeometry, snapThreshold);
                    if (snapped?.point) current = snapped.point;
                }

                if (modeEl) {
                    modeEl.textContent = shipperMode
                        ? (data.mode === 'shipper_live'
                            ? 'GPS tài xế trực tiếp'
                            : (data.mode === 'shipper_delayed' ? 'GPS cập nhật chậm' : 'Tài xế đã nhận đơn'))
                        : 'Xem trước quán → nhà';
                }
                if (stageEl) stageEl.textContent = data.stage || data.timeline_label || data.status_label || 'Theo dõi đơn hàng';
                if (updatedEl) {
                    const fresh = shipperMode && data.current?.updated_at ? ` • ${freshnessText(data.current.updated_at)}` : '';
                    updatedEl.textContent = `${data.message || 'Đang cập nhật hành trình.'}${fresh}`;
                }
                if (distanceEl && Number.isFinite(Number(data.distance_m))) distanceEl.textContent = distanceText(data.distance_m);
                if (etaEl) {
                    if (shipperMode && Number.isFinite(Number(data.duration_s))) {
                        const travelSpeed = speedText(data.distance_m, data.duration_s);
                        etaEl.textContent = travelSpeed
                            ? `${etaText(data.duration_s)} • ${travelSpeed}`
                            : etaText(data.duration_s);
                    }
                    else if (!shipperMode) etaEl.textContent = 'Quán → bạn';
                }
                if (shipperEl) shipperEl.textContent = data.shipper?.name || (shipperMode ? 'Tài xế Chill Drink' : 'Quán đang chuẩn bị');
                if (vehicleEl) vehicleEl.textContent = [data.shipper?.vehicle_type, data.shipper?.license_plate].filter(Boolean).join(' · ');
                if (followBtn) {
                    followBtn.innerHTML = shipperMode
                        ? '<i class="bi bi-crosshair me-1"></i>Bám theo tài xế'
                        : '<i class="bi bi-map me-1"></i>Bám theo lộ trình';
                }

                placeCustomer(customer, data);
                if (shipperMode) placeShipper(current, data, !forceFit);
                else placePrep(branch, data);

                setPlaceholder(false);

                if (forceFit || !mapEl.dataset.fitted) {
                    if (shipperMode && validPoint(current) && validPoint(customer)) {
                        fitPreview(current, customer);
                    } else if (validPoint(branch) && validPoint(customer)) {
                        fitPreview(branch, customer);
                    }
                    mapEl.dataset.fitted = '1';
                    refreshMapSize();
                }

                updateHeadingAndFollow(current, lastGeometry, shipperMode, data);

                root.dispatchEvent(new CustomEvent('delivery:tracking-updated', {detail:data}));

                if (data.status === 'delivered' || data.status === 'completed') {
                    stopped = true;
                    if (timer) clearInterval(timer);
                    if (nextPollTimeout) clearTimeout(nextPollTimeout);
                    headingMode = false;
                    following = false;
                    applyHeadingMap();
                }
            };

            const poll = async (forceRoute = false) => {
                if (stopped) return;
                if (pollBusy) {
                    forcedRefreshQueued = forcedRefreshQueued || forceRoute;
                    return;
                }
                if (document.hidden && !forceRoute) {
                    schedulePoll();
                    return;
                }
                pollBusy = true;
                pollCount += 1;
                try {
                    const url = new URL(liveUrl, window.location.origin);
                    const now = Date.now();
                    const movedSinceLastRoute = validPoint(lastRenderedPoint) && validPoint(lastRouteRequestedFrom)
                        ? haversine(lastRenderedPoint, lastRouteRequestedFrom)
                        : Infinity;
                    const needsRoute = forceRoute
                        || pollCount === 1
                        || !lastGeometry
                        || now - lastRoutePollAt >= routeRefreshAfterMs
                        || movedSinceLastRoute >= 32;
                    if (needsRoute) {
                        url.searchParams.set('route', '1');
                    }
                    const response = await fetch(url, {headers:{'Accept':'application/json'}, cache:'no-store'});
                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data?.success) throw new Error(data?.message || `Theo dõi đơn lỗi HTTP ${response.status}`);

                    if (!data.available) {
                        if (stageEl) stageEl.textContent = data.stage || data.timeline_label || 'Đang xử lý đơn hàng';
                        if (updatedEl) updatedEl.textContent = data.message || 'Chưa có dữ liệu bản đồ.';
                        if (modeEl) modeEl.textContent = 'Chưa đủ dữ liệu';
                        setPlaceholder(true, 'Chưa hiển thị được bản đồ', data.message || 'Đang chờ đủ tọa độ quán và khách hàng.');
                        root.dispatchEvent(new CustomEvent('delivery:tracking-updated', {detail:data}));
                        return;
                    }

                    if (lastData && lastData.mode !== data.mode) {
                        lastGeometry = null;
                        setTimeout(() => poll(true), 150);
                    }
                    renderData(data, pollCount === 1);
                    if (needsRoute) {
                        lastRoutePollAt = Date.now();
                        const routedFrom = toPoint(data.current);
                        if (validPoint(routedFrom)) lastRouteRequestedFrom = routedFrom;
                    }
                } catch (error) {
                    console.error('[ChillDrink Tracking] poll failed', error);
                    setPlaceholder(true, 'Không tải được hành trình', error.message || 'Có lỗi khi tải dữ liệu theo dõi.');
                    if (updatedEl) updatedEl.textContent = 'Không tải được dữ liệu. Trang sẽ tự thử lại.';
                } finally {
                    pollBusy = false;
                    if (forcedRefreshQueued) {
                        forcedRefreshQueued = false;
                        poll(true);
                    } else {
                        schedulePoll();
                    }
                }
            };

            const schedulePoll = () => {
                if (stopped) return;
                const liveMode = lastData && typeof lastData.mode === 'string' && lastData.mode.startsWith('shipper');
                // Order/status changes are event-driven. Periodic refresh remains only
                // while rendering shipper GPS, which has no location broadcast event.
                if (!liveMode) return;
                const delayedMode = lastData?.mode === 'shipper_delayed';
                const delay = document.hidden ? 6000 : (liveMode ? (delayedMode ? 2200 : 1600) : 4000);
                if (nextPollTimeout) clearTimeout(nextPollTimeout);
                nextPollTimeout = setTimeout(() => poll(false), delay);
            };

            followBtn?.addEventListener('click', () => {
                const shipperMode = !!(lastData && typeof lastData.mode === 'string' && lastData.mode.startsWith('shipper'));
                if (!shipperMode && validPoint(lastBranchPoint) && validPoint(lastCustomerPoint)) {
                    headingMode = false;
                    following = true;
                    fitPreview(lastBranchPoint, lastCustomerPoint);
                    return;
                }

                following = true;
                headingMode = true;
                if (lastData) renderData(lastData, false);
            });

            poll(true);
            if (window.Echo && realtimeChannel) {
                const channel = realtimePrivate
                    ? window.Echo.private(realtimeChannel)
                    : window.Echo.channel(realtimeChannel);

                channel.listen('.order.status.updated', payload => {
                    if (Number(payload?.order_id) !== orderId) return;
                    if (typeof window.dispatchOrderStatusUpdate === 'function') {
                        window.dispatchOrderStatusUpdate(payload);
                    }
                    // Fetch one authorized snapshot immediately so stage, journey,
                    // shipper and map all change atomically with the broadcast event.
                    poll(true);
                });
            }
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshMapSize();
                    poll(true);
                }
            });
            window.addEventListener('resize', refreshMapSize);
            if ('ResizeObserver' in window) {
                const resizeObserver = new ResizeObserver(refreshMapSize);
                resizeObserver.observe(mapEl);
                resizeObserver.observe(root);
            }
            refreshMapSize();
        });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, {once:true});
    else boot();
})();
</script>
@endonce
