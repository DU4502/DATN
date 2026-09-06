@extends('layouts.shipper')

@section('title', 'Quay về chi nhánh')
@section('mobile-title', 'Về chi nhánh')
@section('mobile-subtitle', 'Đang quay về home branch')

@section('content')
<div class="ship-return-page">
    <section class="return-map-card">
        <div id="returnMap" class="return-map"></div>
        <div class="return-nav-banner">
            <span class="return-nav-icon"><i class="fa-solid fa-location-arrow"></i></span>
            <div class="min-w-0 flex-grow-1">
                <small>Quay về home branch</small>
                <strong id="returnStage">Đang lấy vị trí GPS...</strong>
                <span id="returnMeta">Hệ thống sẽ tự xác nhận khi bạn về tới chi nhánh.</span>
            </div>
            <div class="return-nav-metric">
                <b id="returnDistance">--</b>
                <small id="returnEta">--</small>
            </div>
        </div>
        <button class="return-test-btn d-none" type="button" id="toggleTestGps" aria-label="Test GPS">
            <i class="fa-solid fa-flask"></i><span>Test GPS</span>
        </button>
    </section>

    <section class="return-sheet-card">
        <div class="return-sheet-head">
            <div>
                <small class="return-eyebrow">Đích đến</small>
                <strong>Chi nhánh {{ $branch->name }}</strong>
                <span>{{ $branch->address ?: 'Home branch cố định của bạn.' }}</span>
            </div>
            <span class="return-branch-badge"><i class="fa-solid fa-shop"></i></span>
        </div>

        <div class="return-info-grid">
            <div class="return-info-card is-status">
                <span class="return-info-icon"><i class="fa-solid fa-route"></i></span>
                <div>
                    <small>Trạng thái</small>
                    <strong>Đang quay về chi nhánh</strong>
                    <span>Tới phạm vi chi nhánh, hệ thống tự chuyển sang Sẵn sàng.</span>
                </div>
            </div>

            <div class="return-info-card is-note">
                <span class="return-info-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <div>
                    <small>Tự động</small>
                    <strong>Không cần bấm xác nhận</strong>
                    <span>Bạn chỉ cần di chuyển, hệ thống sẽ tự kiểm tra GPS và hoàn tất bước quay về.</span>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@include('partials.vietnam-territory-labels')
<style>
.ship-return-page{display:flex;flex-direction:column;gap:12px;width:100%;min-width:0;overflow-x:hidden;padding-bottom:6px}
.return-map-card{position:relative;width:100%;overflow:hidden;border:1px solid var(--ship-line);border-radius:26px;background:#eaf1ee;box-shadow:var(--ship-shadow)}
.return-map{height:clamp(350px,54dvh,580px);min-height:350px;width:100%;background:#eef3f1}
.return-nav-banner{position:absolute;z-index:700;left:12px;right:12px;top:12px;display:flex;align-items:center;gap:10px;padding:12px;border-radius:18px;background:linear-gradient(135deg,rgba(15,118,80,.97),rgba(8,91,62,.97));color:#fff;box-shadow:0 12px 28px rgba(8,91,62,.24)}
.return-nav-icon{width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:inline-flex!important;align-items:center!important;justify-content:center!important;flex:none;font-size:16px;line-height:1}
.return-nav-icon i,.return-branch-badge i,.return-info-icon i{display:block;line-height:1}
.return-nav-banner small,.return-nav-banner span{display:block;font-size:10px;line-height:1.35;color:rgba(255,255,255,.80)}
.return-nav-banner strong{display:block;font-size:15px;line-height:1.22;margin:2px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.return-nav-metric{text-align:right;flex:none;min-width:54px}.return-nav-metric b{display:block;font-size:16px;line-height:1.1}.return-nav-metric small{white-space:nowrap}
.return-test-btn{position:absolute;z-index:710;right:12px;bottom:12px;height:40px;padding:0 12px;border:1px solid #ffd08b;border-radius:999px;background:rgba(255,250,238,.98);color:#b35d00;font-size:11px;font-weight:850;display:flex;align-items:center;gap:6px;box-shadow:0 8px 18px rgba(0,0,0,.12)}
.return-test-btn.btn-danger{background:#fff0f0!important;color:#c0392b!important;border-color:#ffc8c8!important}
.return-sheet-card{background:#fff;border:1px solid var(--ship-line);border-radius:24px;box-shadow:0 8px 24px rgba(16,55,44,.05);padding:14px}
.return-sheet-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid #edf1ef}
.return-sheet-head .return-eyebrow{display:block;font-size:10px;color:var(--ship-muted);line-height:1.3}
.return-sheet-head strong{display:block;font-size:15px;line-height:1.25;margin-top:2px;color:#16342b}
.return-sheet-head span{display:block;font-size:11px;color:var(--ship-muted);line-height:1.45;margin-top:4px}
.return-branch-badge{width:42px;height:42px;border-radius:14px;background:var(--ship-green-soft);color:var(--ship-green-dark);display:inline-flex!important;align-items:center!important;justify-content:center!important;flex:none;font-size:17px;line-height:1;margin-top:1px}
.return-info-grid{display:grid;grid-template-columns:1fr;gap:10px;padding-top:12px}
.return-info-card{display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:18px;border:1px solid #e4ece8;background:#fafcfb}
.return-info-card.is-status{border-color:#bfe4d3;background:#f1faf5}
.return-info-card.is-note{border-color:#e6edf5;background:#f8fbff}
.return-info-icon{width:40px;height:40px;border-radius:13px;background:#fff;display:inline-flex!important;align-items:center!important;justify-content:center!important;flex:none;box-shadow:0 4px 10px rgba(15,23,42,.05);font-size:14px;line-height:1}
.return-info-card.is-status .return-info-icon{color:var(--ship-green-dark)}
.return-info-card.is-note .return-info-icon{color:#2563eb}
.return-info-card small,.return-info-card span{display:block;font-size:10px;line-height:1.38;color:var(--ship-muted)}
.return-info-card strong{display:block;font-size:14px;line-height:1.25;margin:2px 0;color:#16342b}
#returnMap .leaflet-control-attribution{font-size:8px}
#returnMap .leaflet-control-zoom{display:none!important}
.return-shipper-marker{width:25px;height:25px;border-radius:50%;background:#277cff;border:4px solid #fff;box-shadow:0 0 0 6px rgba(39,124,255,.18)}
.return-branch-marker{width:40px;height:40px;border-radius:15px;background:#fff;border:3px solid var(--ship-green);display:grid;place-items:center;color:var(--ship-green);font-size:18px;box-shadow:0 8px 20px rgba(0,0,0,.18)}
@media(max-width:390px){.return-map{height:clamp(320px,50dvh,500px);min-height:320px}.return-nav-banner{padding:10px;left:10px;right:10px;top:10px}.return-nav-icon{width:38px;height:38px}.return-nav-banner strong{font-size:14px}.return-nav-metric b{font-size:14px}.return-sheet-card{padding:12px}}
@media(max-width:340px){.return-nav-metric{display:none}.return-map{min-height:300px;height:46dvh}.return-sheet-head strong{font-size:14px}.return-info-card strong{font-size:13px}}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(() => {
    if (!window.L) return;

    const routeUrl = @json(route('shipper.returning.route'));
    const locationUrl = @json(route('shipper.location.update'));
    const csrf = @json(csrf_token());
    const branch = {
        latitude: Number(@json((float) $branch->latitude)),
        longitude: Number(@json((float) $branch->longitude)),
        name: @json($branch->name),
    };
    const localTestAllowed = ['127.0.0.1', 'localhost'].includes(location.hostname);

    const map = L.map('returnMap', {
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true,
        zoomControl: false,
    }).setView([branch.latitude, branch.longitude], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    window.ChillDrinkVietnamTerritoryLabels?.addToLeaflet(map);

    const branchIcon = L.divIcon({className:'', html:'<div class="return-branch-marker"><i class="fa-solid fa-shop"></i></div>', iconSize:[40,40], iconAnchor:[20,20]});
    const shipperIcon = L.divIcon({className:'', html:'<div class="return-shipper-marker"></div>', iconSize:[25,25], iconAnchor:[13,13]});
    const branchMarker = L.marker([branch.latitude, branch.longitude], {icon:branchIcon}).addTo(map).bindPopup(branch.name);
    let shipperMarker = null;
    let routeLine = null;
    let testMode = false;
    let routeBusy = false;
    let lastSentAt = 0;

    const stageEl = document.getElementById('returnStage');
    const metaEl = document.getElementById('returnMeta');
    const distanceEl = document.getElementById('returnDistance');
    const etaEl = document.getElementById('returnEta');

    const distanceText = meters => {
        const n = Number(meters);
        if (!Number.isFinite(n)) return '--';
        return n >= 1000 ? `${(n / 1000).toFixed(1)} km` : `${Math.max(1, Math.round(n))} m`;
    };
    const etaText = seconds => {
        const n = Number(seconds);
        if (!Number.isFinite(n)) return '--';
        return `${Math.max(1, Math.round(n / 60))} phút`;
    };

    async function refreshRoute() {
        if (routeBusy) return;
        routeBusy = true;
        try {
            const response = await fetch(routeUrl, {headers:{Accept:'application/json'}, cache:'no-store'});
            const data = await response.json();
            if (!data.active) {
                stageEl.textContent = 'Đã về tới chi nhánh';
                metaEl.textContent = 'Bạn đang sẵn sàng nhận nhiệm vụ mới.';
                distanceEl.textContent = '0 m';
                etaEl.textContent = 'Đã tới';
                setTimeout(() => location.href = @json(route('shipper.dashboard')), 1300);
                return;
            }
            if (data.waiting_gps) {
                stageEl.textContent = 'Đang chờ GPS...';
                metaEl.textContent = data.message || 'Hãy cho phép trình duyệt truy cập vị trí.';
                return;
            }

            const current = [Number(data.current.latitude), Number(data.current.longitude)];
            if (!shipperMarker) {
                shipperMarker = L.marker(current, {icon:shipperIcon,zIndexOffset:1000}).addTo(map).bindPopup('Vị trí của bạn');
                map.fitBounds(L.latLngBounds([current,[branch.latitude,branch.longitude]]), {padding:[50,50],maxZoom:16});
            } else {
                shipperMarker.setLatLng(current);
            }

            const geometry = Array.isArray(data.route?.geometry) ? data.route.geometry : null;
            if (geometry && geometry.length > 1) {
                if (routeLine) routeLine.remove();
                routeLine = L.polyline(geometry, {color:'#198754',weight:6,opacity:.85,lineJoin:'round'}).addTo(map);
            }

            distanceEl.textContent = distanceText(data.distance_m);
            etaEl.textContent = etaText(data.duration_s);
            stageEl.textContent = `Đang quay về ${data.branch.name}`;
            metaEl.textContent = `${distanceText(data.distance_m)} · khoảng ${etaText(data.duration_s)}`;
        } catch (_) {
            metaEl.textContent = 'Tạm thời chưa cập nhật được tuyến đường.';
        } finally {
            routeBusy = false;
        }
    }

    async function sendLocation(lat, lng, accuracy = 15, isTest = false) {
        const now = Date.now();
        if (!isTest && now - lastSentAt < 2500) return;
        lastSentAt = now;

        try {
            const response = await fetch(locationUrl, {
                method:'POST',
                headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
                body: JSON.stringify({latitude:lat, longitude:lng, accuracy, test_mode:isTest ? 1 : 0})
            });
            const data = await response.json();
            if (data.return_state?.arrived) {
                stageEl.textContent = data.return_state.message;
                metaEl.textContent = data.new_assignment
                    ? 'Vừa có nhiệm vụ mới. Bấm Bắt đầu hoặc chờ đếm ngược để mở dẫn đường.'
                    : 'Đã về home branch.';
                distanceEl.textContent = '0 m';
                etaEl.textContent = 'Đã tới';
                if (data.new_assignment && typeof window.ChillShipperAssignmentPrompt === 'function') {
                    window.ChillShipperAssignmentPrompt(data.new_assignment);
                } else {
                    setTimeout(() => location.href = @json(route('shipper.dashboard')), 900);
                }
                return;
            }
            if (data.return_state?.message) metaEl.textContent = data.return_state.message;
            refreshRoute();
        } catch (_) {}
    }

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            pos => {
                if (testMode) return;
                sendLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy || 30, false);
            },
            () => { if (!testMode) metaEl.textContent = 'Không lấy được GPS. Hãy bật quyền vị trí.'; },
            {enableHighAccuracy:true, maximumAge:2500, timeout:12000}
        );
    }

    if (localTestAllowed) {
        const toggle = document.getElementById('toggleTestGps');
        toggle?.classList.remove('d-none');
        toggle?.addEventListener('click', () => {
            testMode = !testMode;
            toggle.textContent = testMode ? 'Tắt Test GPS' : 'Bật Test GPS';
            toggle.classList.toggle('btn-danger', testMode);
            toggle.classList.toggle('btn-warning', !testMode);
            metaEl.textContent = testMode ? 'Test GPS đang bật: bấm lên bản đồ để đặt vị trí giả.' : 'Đã quay lại GPS thật.';
        });
        map.on('click', e => {
            if (!testMode) return;
            sendLocation(e.latlng.lat, e.latlng.lng, 8, true);
        });
    }

    refreshRoute();
    setInterval(refreshRoute, 7000);
})();
</script>
@endpush
