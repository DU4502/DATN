@extends('layouts.shipper')

@section('title', 'Dẫn đường giao hàng')
@section('mobile-title', 'Dẫn đường')

@section('content')
@php
    $normalizedStatus = \App\Support\OrderStatus::normalize((string) $order->status);
    $statusLabel = \App\Support\OrderStatus::label((string) $order->status);
    $handoverContext = is_array($handoverContext ?? null) ? $handoverContext : null;
    $handoverPending = !empty($handoverContext);
    $pendingIssue = is_array($pendingIssue ?? null) ? $pendingIssue : null;
    $isGoingToStore = !$handoverPending && in_array($normalizedStatus, [
        \App\Support\OrderStatus::CONFIRMED,
        \App\Support\OrderStatus::PREPARING,
        \App\Support\OrderStatus::READY_FOR_DELIVERY,
    ], true);
    $branch = $order->branch;
    $targetLabel = $handoverPending
        ? ($handoverContext['label'] ?? 'Điểm bàn giao với shipper cũ')
        : ($isGoingToStore ? ($branch?->name ?: 'Cửa hàng') : ($order->customerName() ?: 'Khách hàng'));
    $targetAddress = $handoverPending
        ? ($handoverContext['address'] ?? 'Vị trí bàn giao')
        : ($isGoingToStore ? ($branch?->address ?: 'Chưa cập nhật địa chỉ cửa hàng') : $order->getShippingAddress());
    $customerPhone = (string) ($order->customerPhone() ?? '');
    $customerTel = preg_replace('/[^0-9+]/', '', $customerPhone);
    $customerArrivalConfirmed = (bool) ($customerArrivalConfirmed ?? false);
    $arrivalEvidence = is_array($arrivalEvidence ?? null) ? $arrivalEvidence : ['verified' => false];
    $arrivalVerified = (bool) ($arrivalEvidence['verified'] ?? false);
    $requiresCodCollection = (bool) ($requiresCodCollection ?? false);
    $amountToCollect = (int) ($amountToCollect ?? 0);
    $deliveryConfirmMessage = $requiresCodCollection
        ? 'Xác nhận đã giao hàng và đã thu '.number_format($amountToCollect).'đ từ khách?'
        : 'Xác nhận đã giao hàng cho khách?';
    $order->loadMissing(['orderItems.product', 'orderItems.productSize.size']);
    $orderItems = $order->orderItems ?? collect();
    $totalItemQty = (int) $orderItems->sum(fn ($item) => max(0, (int) ($item->quantity ?? 0)));
    $issueEligibleStatuses = [
        \App\Support\OrderStatus::CONFIRMED,
        \App\Support\OrderStatus::PREPARING,
        \App\Support\OrderStatus::READY_FOR_DELIVERY,
        \App\Support\OrderStatus::SHIPPER_PICKED_UP,
        \App\Support\OrderStatus::DELIVERING,
    ];
    $showIssueModal = !empty($isAccepted) && ($handoverPending || in_array($normalizedStatus, $issueEligibleStatuses, true));
    $showIssueButton = $showIssueModal && empty($pendingIssue);
@endphp

@section('mobile-subtitle', 'Mã đơn: '.$order->displayCode())

<div class="container-fluid shipper-navigation-page">
    @if($pendingIssue)
        <div class="alert alert-warning border-0 shadow-sm">
            <div class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Sự cố đã gửi · đang chờ cửa hàng/admin xử lý</div>
            <div class="small mt-1">{{ $pendingIssue['description'] ?? 'Shipper đã báo sự cố.' }}</div>
            <div class="small text-muted mt-1">Đơn vẫn thuộc chuyến hiện tại. Bạn không cần báo lại nhiều lần.</div>
        </div>
    @endif

    <div class="shipper-map-layout">
        <div class="shipper-map-primary">
            <div class="card border-0 shadow-sm overflow-hidden nav-map-card">
                <div class="navigation-summary" id="navigationSummary">
                    <div class="navigation-turn-icon" id="nextTurnIcon"><i class="fa-solid fa-location-arrow"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="small opacity-75" id="navigationStage">Đi thẳng</div>
                        <div class="fw-bold text-truncate" id="nextInstruction">Còn -- m nữa</div>
                    </div>
                    <div class="navigation-metrics text-end">
                        <strong id="routeDistance">-- km</strong>
                        <small id="routeEta">-- phút</small>
                    </div>
                </div>

                <div id="shipperMap"
                     class="shipper-map-canvas"
                     data-order-id="{{ $order->id }}"
                     data-order-code="{{ $order->displayCode() }}"
                     data-status="{{ $normalizedStatus }}"
                     data-route-url="{{ route('shipper.map.route', $order->id) }}"
                     data-location-url="{{ route('shipper.location.update') }}"
                     data-tts-url="{{ route('shipper.navigation.voice') }}">
                </div>

                <div class="map-tools-wrap">
                    <button type="button" class="map-follow-button" id="followMeButton" aria-label="Bám vị trí">
                        <i class="fa-solid fa-crosshairs"></i>
                    </button>
                    <button type="button" class="map-tools-toggle" id="mapToolsToggle" aria-expanded="false" aria-controls="mapToolsMenu" aria-label="Mở công cụ bản đồ">
                        <i class="fa-solid fa-sliders"></i>
                    </button>
                    <div class="map-tools-menu" id="mapToolsMenu" hidden>
                        <button type="button" id="voiceGuideButton" aria-pressed="true"><i class="fa-solid fa-volume-high"></i><span>Giọng nói: Bật</span></button>
                        <button type="button" id="repeatGuideButton"><i class="fa-solid fa-repeat"></i><span>Đọc lại chỉ dẫn</span></button>
                        <button type="button" class="d-none" id="testGpsButton"><i class="fa-solid fa-flask"></i><span>Test GPS</span></button>
                        <button type="button" class="d-none" id="testDriveButton"><i class="fa-solid fa-play"></i><span>Chạy thử tuyến</span></button>
                        <button type="button" class="d-none" id="testStopButton"><i class="fa-solid fa-stop"></i><span>Dừng test</span></button>
                        <div class="test-speed-control d-none" id="testSpeedControl">
                            <label for="testSpeedRange">
                                <span>Tốc độ test</span>
                                <strong><span id="testSpeedValue">50</span> km/h</strong>
                            </label>
                            <div class="test-speed-inputs">
                                <input type="range" id="testSpeedRange" min="5" max="80" step="1" value="50" aria-label="Tốc độ test GPS">
                                <input type="number" id="testSpeedNumber" min="5" max="80" step="1" value="50" aria-label="Nhập tốc độ test GPS">
                            </div>
                        </div>
                        <div class="map-tools-source" id="routeSourcePill">Đang tính tuyến...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="shipper-side-panel">
            <div class="card border-0 shadow-sm mb-3 map-destination-card">
                <div class="card-body p-3">
                    <div class="small text-muted mb-1 d-flex align-items-center gap-2">
                        <span>Điểm đến hiện tại</span>
                        <span class="badge bg-light text-dark border d-none" id="currentStopBadge"></span>
                    </div>
                    <div class="fw-bold mb-1">
                        <i class="fa-solid {{ $handoverPending ? 'fa-people-carry-box' : ($isGoingToStore ? 'fa-store' : 'fa-house') }} text-primary me-2"></i>
                        {{ $targetLabel }}
                    </div>
                    <div class="small text-muted">{{ $targetAddress }}</div>
                </div>
            </div>

            @if(!empty($isAccepted))
                <div class="card border-0 shadow-sm mb-3 order-items-mobile-card">
                    <div class="card-body p-3">
                        <details class="order-items-details">
                            <summary class="order-items-toggle">
                                <span class="order-items-summary-copy min-w-0 text-start">
                                    <span class="small text-muted d-block">Chi tiết đơn</span>
                                    <span class="fw-bold text-truncate d-block">{{ $totalItemQty }} món · {{ number_format((int) ($order->total ?? $order->total_price ?? 0), 0, ',', '.') }}đ</span>
                                </span>
                                <span class="order-items-toggle-meta">
                                    <span class="badge bg-light text-dark border">{{ $orderItems->count() }} dòng</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="order-mini-list">
                                @forelse($orderItems as $item)
                                    @php
                                        $lineTotal = (int) ($item->total_price ?? (($item->price ?? 0) * ($item->quantity ?? 1)));
                                        $meta = [];
                                        $sizeName = $item->size_name ?? $item->productSize?->size_name ?? $item->productSize?->name ?? optional(optional($item->productSize)->size)->size_name ?? '';
                                        if ($sizeName !== '') $meta[] = 'Size '.$sizeName;
                                        if ($item->sugar_level !== null && $item->sugar_level !== '') $meta[] = 'Đường '.$item->sugar_level.'%';
                                        if ($item->ice_level !== null && $item->ice_level !== '') $meta[] = 'Đá '.$item->ice_level.'%';
                                    @endphp
                                    <div class="order-mini-row">
                                        <div class="qty-badge">{{ (int) ($item->quantity ?? 1) }}x</div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-break">{{ $item->product->name ?? $item->product_name ?? 'Sản phẩm' }}</div>
                                            @if(!empty($meta))
                                                <div class="small text-muted">{{ implode(' · ', $meta) }}</div>
                                            @endif
                                        </div>
                                        <div class="order-mini-price">{{ number_format($lineTotal, 0, ',', '.') }}đ</div>
                                    </div>
                                @empty
                                    <div class="small text-muted pt-2">Chưa có chi tiết món trong đơn.</div>
                                @endforelse
                            </div>
                        </details>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3 trip-contact-card">
                    <div class="card-body p-3">
                        <div class="trip-contact-head mb-2">
                            <div class="min-w-0">
                                <div class="small text-muted">Liên hệ trong chuyến</div>
                                <div class="fw-bold text-truncate">{{ $order->customerName() ?: 'Khách hàng' }}</div>
                            </div>
                            <div class="trip-contact-actions">
                                <a href="{{ route('shipper.chats.index', ['order' => $order->id]) }}" class="trip-mini-action" title="Chat với khách" aria-label="Chat với khách">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </a>
                                @if($customerTel !== '')
                                    <a href="{{ $customerArrivalConfirmed ? 'tel:'.$customerTel : 'javascript:void(0)' }}"
                                       class="trip-mini-action is-call {{ $customerArrivalConfirmed ? '' : 'is-disabled' }}"
                                       title="{{ $customerArrivalConfirmed ? 'Gọi khách' : 'Tới nơi để mở gọi khách' }}"
                                       aria-label="{{ $customerArrivalConfirmed ? 'Gọi khách' : 'Tới nơi để mở gọi khách' }}"
                                       @unless($customerArrivalConfirmed) aria-disabled="true" @endunless>
                                        <i class="fa-solid {{ $customerArrivalConfirmed ? 'fa-phone' : 'fa-phone-slash' }}"></i>
                                    </a>
                                @else
                                    <button type="button" class="trip-mini-action is-disabled" disabled title="Chưa có số điện thoại" aria-label="Chưa có số điện thoại">
                                        <i class="fa-solid fa-phone-slash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-3 shipper-current-action-card">
                <div class="card-body p-3">
                    @if($handoverPending)
                        <div class="status-action-shell">
                            <div class="status-action-head">
                                <div class="status-action-copy min-w-0">
                                    <div class="status-action-pill tone-blue">{{ $arrivalVerified ? 'Đã tới điểm bàn giao' : 'Điểm bàn giao' }}</div>
                                    <div class="status-action-title">{{ $arrivalVerified ? 'Bạn đã tới điểm bàn giao' : 'Đang tới điểm bàn giao' }}</div>
                                    <div class="status-action-desc">{{ $arrivalVerified ? 'GPS đã xác minh điểm bàn giao. Bạn có thể vuốt để nhận bàn giao.' : 'Hãy tới vị trí shipper cũ để nhận lại hàng rồi mới tiếp tục giao khách.' }}</div>
                                </div>
                                @if($showIssueButton)
                                    <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                @endif
                            </div>
                            <form action="{{ route('shipper.orders.handover', $order->id) }}" method="POST" id="handoverForm" class="mt-2">
                                @csrf
                                <input type="hidden" name="latitude" id="handoverLatitude">
                                <input type="hidden" name="longitude" id="handoverLongitude">
                                <input type="hidden" name="accuracy" id="handoverAccuracy">
                                <x-shipper-swipe-submit
                                    button-id="handoverButton"
                                    tone="blue"
                                    :disabled="!($arrivalVerified && !empty($isAccepted))"
                                    :label="$arrivalVerified ? 'Vuốt để nhận bàn giao' : 'Tới điểm bàn giao để mở'" />
                            </form>
                        </div>
                    @elseif(in_array($normalizedStatus, [\App\Support\OrderStatus::CONFIRMED, \App\Support\OrderStatus::PREPARING], true))
                        <div class="status-action-shell">
                            <div class="status-action-head">
                                <div class="status-action-copy min-w-0">
                                    <div class="status-action-pill tone-amber">Quán đang pha</div>
                                    <div class="status-action-title">{{ $arrivalVerified ? 'Đã tới quán' : 'Tới quán chờ hàng' }}</div>
                                    <div class="status-action-desc">{{ $arrivalVerified ? 'Quán đang pha. Chờ quán làm xong để mở nút lấy hàng.' : 'Quán đang pha. Bạn có thể tới quán trước.' }}</div>
                                </div>
                                @if($showIssueButton)
                                    <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                @endif
                            </div>
                        </div>
                    @elseif($normalizedStatus === \App\Support\OrderStatus::READY_FOR_DELIVERY)
                        <div class="status-action-shell">
                            <div class="status-action-head">
                                <div class="status-action-copy min-w-0">
                                    <div class="status-action-pill tone-green">Quán xong</div>
                                    <div class="status-action-title">{{ $arrivalVerified ? 'Đã tới quán' : 'Tới quán lấy hàng' }}</div>
                                    <div class="status-action-desc">{{ $arrivalVerified ? 'Quán đã chuẩn bị xong. Vuốt để xác nhận đã lấy hàng.' : 'Quán đã xong. Tới quán để mở nút lấy hàng.' }}</div>
                                </div>
                                @if($showIssueButton)
                                    <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                @endif
                            </div>
                            <form action="{{ route('shipper.orders.picked-up', $order->id) }}" method="POST" id="pickedUpForm" class="mt-2">
                                @csrf
                                <input type="hidden" name="latitude" id="pickupLatitude">
                                <input type="hidden" name="longitude" id="pickupLongitude">
                                <input type="hidden" name="accuracy" id="pickupAccuracy">
                                <x-shipper-swipe-submit
                                    button-id="pickedUpButton"
                                    :disabled="!($arrivalVerified && !empty($isAccepted))"
                                    :label="$arrivalVerified ? 'Vuốt lấy hàng' : 'Tới quán để mở'" />
                            </form>
                        </div>
                    @elseif($normalizedStatus === \App\Support\OrderStatus::SHIPPER_PICKED_UP)
                        <div class="status-action-shell">
                            <div class="status-action-head">
                                <div class="status-action-copy min-w-0">
                                    <div class="status-action-pill tone-blue">Đã lấy hàng</div>
                                    <div class="status-action-title">Đã lấy hàng xong</div>
                                    <div class="status-action-desc">Hệ thống sẽ tự chuyển sang chặng giao khi đơn này tới lượt. Bạn không cần vuốt Bắt đầu giao nữa.</div>
                                </div>
                                @if($showIssueButton)
                                    <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                @endif
                            </div>
                        </div>
                    @elseif($normalizedStatus === \App\Support\OrderStatus::DELIVERING)
                        @if(!$customerArrivalConfirmed)
                            <div class="status-action-shell">
                                <div class="status-action-head">
                                    <div class="status-action-copy min-w-0">
                                        <div class="status-action-pill tone-blue">Đang giao</div>
                                        <div class="status-action-title">Đang tới điểm giao</div>
                                        <div class="status-action-desc">Khi tới đúng điểm giao, bạn có thể vuốt để xác nhận đã đến nơi.</div>
                                    </div>
                                    @if($showIssueButton)
                                        <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                    @endif
                                </div>
                                <form action="{{ route('shipper.orders.arrived', $order->id) }}" method="POST" id="arrivedAtCustomerForm" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="latitude" id="arrivalLatitude">
                                    <input type="hidden" name="longitude" id="arrivalLongitude">
                                    <input type="hidden" name="accuracy" id="arrivalAccuracy">
                                    <x-shipper-swipe-submit
                                        button-id="arrivedAtCustomerButton"
                                        tone="blue"
                                        :disabled="!$arrivalVerified"
                                        :label="$arrivalVerified ? 'Vuốt để xác nhận đã đến nơi' : 'Đến điểm giao để mở'" />
                                </form>
                            </div>
                        @else
                            <div class="status-action-shell">
                                <div class="status-action-head">
                                    <div class="status-action-copy min-w-0">
                                        <div class="status-action-pill tone-green">Đã tới khách</div>
                                        <div class="status-action-title">Xác nhận giao đơn</div>
                                        <div class="status-action-desc">{{ $requiresCodCollection ? 'Thu đủ tiền trước khi vuốt xác nhận giao xong.' : 'Khách đã thanh toán trước. Vuốt để xác nhận giao xong.' }}</div>
                                    </div>
                                    @if($showIssueButton)
                                        <button type="button" class="issue-mini-button status-inline-issue" data-bs-toggle="modal" data-bs-target="#issueModal" title="Báo sự cố" aria-label="Báo sự cố"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                    @endif
                                </div>
                                @if($requiresCodCollection)
                                    <div class="status-money-box mt-2">
                                        <small>Tiền cần thu</small>
                                        <strong>{{ number_format($amountToCollect) }}đ</strong>
                                        <span>COD · Thu đúng số tiền trước khi vuốt xác nhận giao xong.</span>
                                    </div>
                                @else
                                    <div class="status-paid-box mt-2"><i class="fa-solid fa-circle-check"></i><span>Đã thanh toán · Không cần thu tiền</span></div>
                                @endif
                                <form action="{{ route('shipper.orders.complete', $order->id) }}" method="POST" id="deliveredForm" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="latitude" id="deliveryLatitude">
                                    <input type="hidden" name="longitude" id="deliveryLongitude">
                                    <input type="hidden" name="accuracy" id="deliveryAccuracy">
                                    <x-shipper-swipe-submit
                                        button-id="deliveredButton"
                                        :label="$requiresCodCollection
                                            ? 'Vuốt để giao xong & xác nhận thu '.number_format($amountToCollect).'đ'
                                            : 'Vuốt để xác nhận giao xong'" />
                                </form>
                            </div>
                        @endif
                    @elseif($normalizedStatus === \App\Support\OrderStatus::DELIVERED)
                        <div class="status-action-shell">
                            <div class="status-action-head">
                                <div class="status-action-copy min-w-0">
                                    <div class="status-action-pill tone-green">Đã giao hàng</div>
                                    <div class="status-action-title">Đơn đang chờ khách xác nhận</div>
                                    <div class="status-action-desc">Bạn đã giao xong. Hệ thống sẽ tự hoàn tất khi khách xác nhận.</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm map-learning-card">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="fa-solid fa-database me-2 text-primary"></i>Dữ liệu map</h6>
                    <p class="small text-muted mb-0">
                        GPS trong chuyến được lưu vào tracking. Khi giao thành công, điểm giao thực tế được bổ sung vào kho địa chỉ đã có để các lần tìm địa chỉ sau chính xác hơn.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@if($showIssueModal)
<div class="modal fade" id="issueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('shipper.orders.issue', $order->id) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Báo sự cố giao hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small"><strong>Shipper Chill Drink không có hủy/từ chối chuyến.</strong> Báo sự cố chỉ gửi yêu cầu hỗ trợ; đơn vẫn giữ nguyên người giao cho tới khi cửa hàng/admin xử lý.</div>
                <label class="form-label fw-semibold">Sự cố <span class="text-danger">*</span></label>
                <select class="form-select mb-3" name="reason" required>
                    <option value="">Chọn sự cố</option>
                    @foreach($issueReasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label class="form-label fw-semibold">Mô tả thêm</label>
                <textarea class="form-control" name="reason_detail" rows="3" maxlength="1000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-warning">Gửi báo cáo</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    .nav-map-card { position: relative; min-height: 650px; background:#eef4f8; }
    .shipper-map-canvas { width:100%; height:650px; min-height:520px; z-index:1; cursor:grab; }
    .shipper-map-canvas:active { cursor:grabbing; }
    .shipper-map-canvas.is-test-gps { outline:3px dashed rgba(245,158,11,.9); outline-offset:-3px; }
    .shipper-map-canvas.is-test-gps::after {
        content:'TEST GPS: đang giữ vị trí giả';
        position:absolute; z-index:650; top:92px; left:50%; transform:translateX(-50%);
        background:rgba(17,24,39,.88); color:#fff; border-radius:999px; padding:7px 12px;
        font-size:12px; font-weight:700; pointer-events:none; white-space:nowrap;
    }
    .navigation-summary {
        position:absolute; z-index:500; top:14px; left:14px; right:14px; max-width:760px;
        display:flex; align-items:center; gap:12px; padding:12px 14px; color:#fff;
        background:rgba(18, 91, 71, .94); border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.18);
        backdrop-filter: blur(8px);
    }
    .navigation-turn-icon { width:46px; height:46px; border-radius:14px; background:rgba(255,255,255,.16); display:flex; align-items:center; justify-content:center; font-size:21px; flex:none; }
    .navigation-metrics strong, .navigation-metrics small { display:block; white-space:nowrap; }
    .navigation-metrics strong { font-size:18px; }
    .map-bottom-controls { position:absolute; z-index:500; bottom:16px; left:16px; right:16px; display:flex; justify-content:space-between; align-items:flex-end; gap:10px; pointer-events:none; }
    .map-bottom-controls > * { pointer-events:auto; }
    .route-source-pill { background:rgba(255,255,255,.95); border:1px solid #e5e7eb; border-radius:999px; padding:7px 12px; font-size:12px; box-shadow:0 4px 18px rgba(0,0,0,.1); }
    .bundle-stop-marker { width:32px; height:32px; border-radius:999px; border:3px solid #fff; box-shadow:0 6px 16px rgba(15,23,42,.22); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; line-height:1; }
    .bundle-stop-marker.is-pickup { background:#f59e0b; }
    .bundle-stop-marker.is-delivery { background:#2563eb; }
    .bundle-stop-marker.is-current { opacity:1; }
    .bundle-stop-marker.is-future { opacity:.52; }
    .shipper-current-marker {
        width:32px;
        height:32px;
        background:#ffffff;
        border:2px solid rgba(37,99,235,.14);
        border-radius:999px;
        box-shadow:0 10px 24px rgba(37,99,235,.24);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#2f6fed;
        line-height:1;
    }
    .shipper-current-marker svg {
        width:18px;
        height:18px;
        display:block;
        transform-origin:50% 50%;
        filter:drop-shadow(0 1px 1px rgba(47,111,237,.18));
    }
    .shipper-target-marker { width:34px; height:34px; background:#fff; border:3px solid #198754; border-radius:50% 50% 50% 0; transform:rotate(-45deg); box-shadow:0 3px 12px rgba(0,0,0,.2); }
    .shipper-target-marker > span { display:flex; width:100%; height:100%; align-items:center; justify-content:center; transform:rotate(45deg); color:#198754; }
    .arrival-guard { border:1px solid #dbe7ff; background:#f6f9ff; border-radius:12px; padding:10px 11px; }
    .arrival-guard.is-near { border-color:#fde68a; background:#fffbeb; }
    .arrival-guard.is-verified { border-color:#a7f3d0; background:#ecfdf5; }
    .arrival-guard.is-error { border-color:#fecaca; background:#fff1f2; }
    .arrival-guard-badge {
        display:inline-flex;
        align-items:center;
        gap:5px;
        margin-bottom:6px;
        padding:3px 7px;
        background:#fff3cd;
        border:1px solid #fde68a;
        color:#9a6700;
        font-size:10px;
        font-weight:800;
        line-height:1;
    }
    .arrival-guard-badge.is-ready {
        background:#ecfdf5;
        border-color:#a7f3d0;
        color:#047857;
    }
    .customer-arrival-panel { border:1px solid #dbe7ff; background:#fff; border-radius:14px; padding:12px; }
    .cod-collect-box { border-radius:12px; padding:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
    .cod-amount { font-size:1.7rem; line-height:1.1; font-weight:800; margin:.2rem 0 .35rem; color:#c2410c; }
    .paid-box { border-radius:12px; padding:12px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; }
    /* ===== Navigation screen layout ===== */
    .shipper-navigation-page {
        margin:0;
        padding:0 !important;
        width:100%;
        max-width:100%;
        display:flex;
        flex-direction:column;
        gap:8px;
        overflow-x:clip;
    }
    .shipper-navigation-page > .alert {
        margin:0;
        font-size:11px;
        line-height:1.45;
    }
    .shipper-map-layout {
        display:flex;
        flex-direction:column;
        gap:8px;
    }
    .shipper-map-primary,
    .shipper-side-panel {
        min-width:0;
    }
    .shipper-side-panel {
        display:flex;
        flex-direction:column;
        gap:8px;
        width:100%;
        max-width:100%;
    }
    .shipper-side-panel > .card {
        margin:0 !important;
    }
    .map-learning-card {
        display:none !important;
    }

    .nav-map-card {
        min-height:0 !important;
        height:auto !important;
        margin:0 !important;
        width:100%;
        max-width:100%;
        background:#eef4f8;
        border:1px solid #d8e4df !important;
        overflow:hidden;
    }
    .shipper-map-canvas {
        width:100% !important;
        height:clamp(430px, 58dvh, 620px) !important;
        min-height:430px !important;
        max-height:620px !important;
        background:#dbe5e2;
        position:relative;
    }
    .gl-navigation-map{position:absolute;inset:0;z-index:2;background:#dbe5e2}
    .shipper-map-canvas.has-gl-nav .leaflet-pane,
    .shipper-map-canvas.has-gl-nav .leaflet-control-container{display:none!important}
    .shipper-map-canvas.has-gl-nav .maplibregl-canvas{outline:0}
    .shipper-map-canvas.has-gl-nav .maplibregl-ctrl-bottom-left,
    .shipper-map-canvas.has-gl-nav .maplibregl-ctrl-bottom-right{z-index:3}
    .shipper-map-canvas .leaflet-tile,
    .shipper-map-canvas .leaflet-marker-icon,
    .shipper-map-canvas .leaflet-overlay-pane svg{
        will-change:transform;
        backface-visibility:hidden;
    }
    .shipper-map-canvas.is-test-gps::after {
        top:76px;
        font-size:11px;
        padding:6px 10px;
    }

    .navigation-summary {
        left:8px !important;
        right:auto !important;
        top:8px !important;
        width:min(calc(100% - 16px), 408px) !important;
        max-width:calc(100% - 16px) !important;
        margin:0 !important;
        padding:8px 10px !important;
        gap:8px !important;
        display:grid !important;
        grid-template-columns:38px minmax(0, 1fr) auto;
        align-items:center;
        background:linear-gradient(135deg, rgba(18, 91, 71, .97), rgba(16, 78, 62, .95)) !important;
        border:1px solid rgba(255,255,255,.12);
        box-shadow:0 10px 22px rgba(0,0,0,.18);
    }
    .navigation-turn-icon {
        width:38px !important;
        height:38px !important;
        font-size:16px !important;
        background:rgba(255,255,255,.14) !important;
    }
    .navigation-summary > .min-w-0,
    .navigation-metrics {
        align-self:center;
    }
    .navigation-summary .small {
        font-size:9px !important;
        line-height:1.15 !important;
    }
    .navigation-summary .fw-bold {
        font-size:18px !important;
        line-height:1.05;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .navigation-metrics strong {
        font-size:15px !important;
        line-height:1.1;
    }
    .navigation-metrics small {
        font-size:10px !important;
        line-height:1.1;
    }

    .map-tools-wrap {
        position:absolute;
        z-index:710;
        right:8px;
        bottom:8px;
        display:flex;
        flex-direction:column;
        align-items:flex-end;
        gap:6px;
    }
    .map-follow-button,
    .map-tools-toggle {
        width:38px;
        height:38px;
        border:0;
        background:rgba(255,255,255,.98);
        color:#17322a;
        display:grid;
        place-items:center;
        box-shadow:0 8px 18px rgba(15,23,42,.14);
        font-size:14px;
    }
    .map-follow-button {
        color:var(--ship-green-dark);
    }
    .map-follow-button:active,
    .map-follow-button.is-active {
        background:var(--ship-green);
        color:#fff;
    }
    .map-tools-toggle[aria-expanded="true"] {
        background:var(--ship-green);
        color:#fff;
    }
    .map-tools-menu {
        width:168px;
        background:rgba(255,255,255,.98);
        border:1px solid #dce7e3;
        padding:5px;
        box-shadow:0 14px 28px rgba(15,23,42,.16);
    }
    .map-tools-menu[hidden] {
        display:none !important;
    }
    .map-tools-menu button {
        width:100%;
        border:0;
        background:transparent;
        padding:7px;
        display:grid;
        grid-template-columns:20px 1fr;
        align-items:center;
        gap:5px;
        text-align:left;
        color:#31443e;
        font-size:10px;
        font-weight:750;
    }
    .map-tools-menu button:active {
        background:#eef6f2;
    }
    .map-tools-menu button i {
        text-align:center;
        color:var(--ship-green-dark);
    }
    .map-tools-source {
        margin-top:4px;
        padding:5px 7px;
        background:#f1f5f3;
        color:#65736e;
        font-size:8px;
        line-height:1.3;
        overflow-wrap:anywhere;
    }
    .test-speed-control {
        margin:3px 0 4px;
        padding:7px;
        background:#f8fbfa;
        border:1px solid #e1ebe7;
        color:#31443e;
        font-size:10px;
    }
    .test-speed-control label {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:6px;
        margin:0 0 6px;
        font-weight:800;
    }
    .test-speed-control strong {
        color:var(--ship-green-dark);
        white-space:nowrap;
    }
    .test-speed-inputs {
        display:grid;
        grid-template-columns:minmax(0,1fr) 44px;
        gap:6px;
        align-items:center;
    }
    .test-speed-inputs input[type="range"] {
        width:100%;
        accent-color:var(--ship-green);
    }
    .test-speed-inputs input[type="number"] {
        width:44px;
        height:24px;
        border:1px solid #d7e3df;
        background:#fff;
        color:#17322a;
        font-size:10px;
        font-weight:800;
        text-align:center;
        padding:2px;
    }

    .map-destination-card .card-body,
    .trip-contact-card .card-body,
    .order-items-mobile-card .card-body,
    .shipper-current-action-card .card-body {
        padding:10px !important;
    }
    .map-destination-card .small,
    .trip-contact-card .small,
    .order-items-mobile-card .small,
    .shipper-current-action-card .small {
        font-size:10px;
        line-height:1.35;
    }
    .map-destination-card .fw-bold,
    .trip-contact-card .fw-bold,
    .order-items-mobile-card .fw-bold,
    .shipper-current-action-card .fw-bold {
        font-size:14px;
        line-height:1.2;
    }
    .map-destination-card .small.text-muted:last-child,
    .shipper-current-action-card .arrival-guard .small,
    .shipper-current-action-card .customer-arrival-panel .small {
        display:-webkit-box;
        -webkit-box-orient:vertical;
        -webkit-line-clamp:2;
        overflow:hidden;
    }

    .trip-contact-card .card-body {
        display:flex;
        align-items:center;
    }
    .trip-contact-head {
        width:100%;
        max-width:100%;
        margin:0 !important;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        min-width:0;
    }
    .trip-contact-actions {
        display:flex;
        align-items:center;
        gap:6px;
        flex-shrink:0;
    }
    .trip-mini-action {
        width:36px;
        height:36px;
        border:1px solid #d9e6ff;
        background:#fff;
        color:#2563eb;
        display:grid;
        place-items:center;
        text-decoration:none;
        box-shadow:0 4px 10px rgba(37,99,235,.08);
    }
    .trip-mini-action i {
        font-size:14px;
    }
    .trip-mini-action.is-call {
        border-color:#c7f2e1;
        color:#0f766e;
        box-shadow:0 4px 10px rgba(15,118,110,.08);
    }
    .trip-mini-action.is-disabled {
        background:#f3f4f6 !important;
        border-color:#e5e7eb !important;
        color:#94a3b8 !important;
        box-shadow:none !important;
        pointer-events:none;
    }

    .order-items-details {
        display:block;
    }
    .order-items-details summary {
        list-style:none;
        cursor:pointer;
    }
    .order-items-details summary::-webkit-details-marker {
        display:none;
    }
    .order-items-toggle {
        width:100%;
        max-width:100%;
        border:0;
        background:transparent;
        padding:0;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        color:inherit;
    }
    .order-items-summary-copy {
        display:block;
    }
    .order-items-toggle-meta {
        display:flex;
        align-items:center;
        gap:6px;
        flex-shrink:0;
    }
    .order-items-toggle-meta i {
        font-size:10px;
        color:#8ca09a;
        transition:transform .18s ease;
    }
    .order-items-details[open] .order-items-toggle-meta i {
        transform:rotate(180deg);
    }
    .order-items-mobile-card .badge {
        font-size:8px;
    }
    .order-mini-list {
        display:none;
        gap:0;
        padding-top:8px;
    }
    .order-items-details[open] .order-mini-list {
        display:grid;
    }
    .order-mini-row {
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:9px 0;
        border-top:1px dashed #e5ebe8;
    }
    .order-mini-row:first-child {
        padding-top:9px;
    }
    .qty-badge {
        min-width:32px;
        height:26px;
        background:#ecfdf5;
        color:#047857;
        font-size:11px;
        font-weight:800;
        display:grid;
        place-items:center;
        flex-shrink:0;
        margin-top:2px;
    }
    .order-mini-price {
        font-size:11px;
        font-weight:800;
        color:#0f766e;
        white-space:nowrap;
        padding-top:2px;
        text-align:right;
        max-width:82px;
    }
    .order-items-mobile-card .fw-semibold {
        text-wrap:balance;
    }

    .shipper-current-action-card {
        border-color:#d5eee5 !important;
        background:#fff !important;
        position:relative;
        width:100%;
        max-width:100%;
    }
    .map-learning-card{display:none!important}
    .shipper-current-action-card>.card-body{padding:12px!important}
    .status-action-shell{display:flex;flex-direction:column;gap:10px}
    .status-action-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
    .status-action-copy{min-width:0;flex:1}
    .status-action-pill{display:inline-flex;align-items:center;min-height:24px;padding:0 10px;border-radius:999px;font-size:10px;font-weight:800;margin-bottom:6px}
    .status-action-pill.tone-green{background:#e9fbf3;color:#0f7a53;border:1px solid #bce6d1}
    .status-action-pill.tone-blue{background:#eff6ff;color:#1d4ed8;border:1px solid #cfe0ff}
    .status-action-pill.tone-amber{background:#fff7e6;color:#b76a00;border:1px solid #f4d79a}
    .status-action-title{font-size:18px;line-height:1.2;font-weight:800;color:#17342c;margin-bottom:4px}
    .status-action-desc{font-size:12px;line-height:1.45;color:#65756f}
    .status-inline-issue{position:static!important;flex:none;width:40px!important;height:40px!important;min-height:40px!important;border-radius:14px!important;background:#fff8eb!important;border:1px solid #ffd08a!important;color:#d97a00!important}
    .status-money-box{border:1px solid #f5c27c;background:#fff8ef;border-radius:16px;padding:12px 14px;display:flex;flex-direction:column;gap:2px}
    .status-money-box small{font-size:10px;line-height:1.35;color:#bf6a00;text-transform:uppercase;font-weight:700}
    .status-money-box strong{font-size:18px;line-height:1.15;color:#d15f00;font-weight:900}
    .status-money-box span{font-size:11px;line-height:1.45;color:#a76114}
    .status-paid-box{display:flex;align-items:center;gap:8px;border:1px solid #bfe4d3;background:#effcf5;border-radius:16px;padding:12px 14px;font-size:12px;font-weight:700;color:#0f7a53}
    .status-paid-box i{font-size:14px}
    .shipper-current-action-card.has-inline-ready-row > .card-body > .issue-mini-button {
        display:none !important;
    }
    .pickup-inline-row {
        position:relative;
        display:grid;
        grid-template-columns:minmax(0, 1fr) minmax(0, 1.08fr);
        align-items:center;
        gap:10px;
        padding:10px 42px 10px 10px;
        border:1px solid #cfe5dc;
        background:linear-gradient(180deg,#f5fbf7 0%, #edf6f1 100%);
    }
    .pickup-inline-status {
        min-width:0;
        margin:0 !important;
        border:0 !important;
        background:transparent !important;
        padding:0 !important;
        display:flex;
        flex-direction:column;
        justify-content:center;
    }
    .pickup-inline-status .arrival-guard-badge {
        width:max-content;
        margin-bottom:5px;
    }
    .pickup-inline-status .fw-semibold {
        font-size:15px;
        line-height:1.15;
        color:#14392e;
    }
    .pickup-inline-status .small {
        color:#587065 !important;
    }
    .pickup-inline-form {
        min-width:0;
        margin:0;
        display:flex;
        align-items:stretch;
    }
    .pickup-inline-form .ship-swipe-confirm {
        width:100%;
        height:100%;
        min-height:56px;
        background:#ffffff;
        border:1px solid #bfdccd;
        box-shadow:0 6px 16px rgba(18,52,42,.06);
    }
    .pickup-inline-form .ship-swipe-label {
        inset:0 10px 0 48px;
        font-size:10px;
    }
    .pickup-inline-form .ship-swipe-knob {
        top:4px;
        left:4px;
    }
    .shipper-current-action-card .btn {
        min-height:40px;
        font-size:11px;
        padding:8px 10px;
    }
    .issue-mini-button {
        position:absolute;
        top:10px;
        right:10px;
        width:30px;
        height:30px;
        border:1px solid #facc15;
        background:#fff7db;
        color:#ca8a04;
        display:grid;
        place-items:center;
        z-index:2;
        box-shadow:none;
    }
    .issue-mini-button i {
        font-size:12px;
    }
    .issue-mini-button.is-inline-overlay {
        position:absolute;
        top:10px;
        right:10px;
        width:24px;
        height:24px;
        min-height:24px;
        background:#fff7db;
        border-color:#f3d46e;
        color:#c79100;
        z-index:3;
    }
    .arrival-guard,
    .customer-arrival-panel,
    .cod-collect-box,
    .paid-box {
        padding:9px 40px 9px 10px !important;
    }
    .cod-amount {
        font-size:1.3rem !important;
        margin:.15rem 0 .25rem !important;
    }

    .shipper-navigation-page .card,
    .shipper-navigation-page .card-body,
    .shipper-navigation-page .btn,
    .shipper-navigation-page .badge,
    .shipper-navigation-page .alert,
    .shipper-navigation-page .arrival-guard,
    .shipper-navigation-page .customer-arrival-panel,
    .shipper-navigation-page .cod-collect-box,
    .shipper-navigation-page .paid-box,
    .shipper-navigation-page .trip-mini-action,
    .shipper-navigation-page .qty-badge,
    .shipper-navigation-page .order-items-toggle,
    .shipper-navigation-page .order-items-toggle-meta .badge,
    .shipper-navigation-page .nav-map-card,
    .shipper-navigation-page .shipper-map-canvas,
    .shipper-navigation-page .navigation-summary,
    .shipper-navigation-page .navigation-turn-icon,
    .shipper-navigation-page .route-source-pill,
    .shipper-navigation-page .map-tools-toggle,
    .shipper-navigation-page .map-tools-menu,
    .shipper-navigation-page .map-tools-menu button,
    .shipper-navigation-page .test-speed-control,
    .shipper-navigation-page .test-speed-inputs input,
    .shipper-navigation-page .map-tools-source,
    .shipper-navigation-page .modal-content,
    .shipper-navigation-page .btn-close {
        border-radius:0 !important;
    }

    @media (max-width: 430px) {
        .shipper-navigation-page {
            padding:0 !important;
            gap:6px;
        }
        .shipper-map-layout,
        .shipper-side-panel {
            gap:6px;
        }
        .shipper-map-canvas {
            height:clamp(430px, 56dvh, 560px) !important;
            min-height:430px !important;
            max-height:560px !important;
        }
        .navigation-summary {
            width:min(calc(100% - 12px), 352px) !important;
            padding:9px 10px !important;
            gap:8px !important;
        }
        .navigation-summary .small {
            font-size:9.5px !important;
        }
        .navigation-summary .fw-bold,
        .navigation-metrics strong {
            font-size:13px !important;
        }
        .navigation-metrics small {
            font-size:9px !important;
        }
        .map-destination-card .card-body,
        .trip-contact-card .card-body,
        .order-items-mobile-card .card-body,
        .shipper-current-action-card .card-body {
            padding:9px !important;
        }
        .map-destination-card .small,
        .trip-contact-card .small,
        .order-items-mobile-card .small,
        .shipper-current-action-card .small {
            font-size:9px;
        }
        .map-destination-card .fw-bold,
        .trip-contact-card .fw-bold,
        .order-items-mobile-card .fw-bold,
        .shipper-current-action-card .fw-bold {
            font-size:13px;
        }
        .shipper-current-action-card .btn {
            min-height:38px;
            font-size:10.5px;
        }
        .issue-mini-button {
            top:9px;
            right:9px;
            width:28px;
            height:28px;
        }
    }

    @media (max-width: 430px) and (max-height: 940px) {
        .shipper-navigation-page {
            gap:4px;
        }
        .shipper-map-layout,
        .shipper-side-panel {
            gap:4px;
        }
        .shipper-map-canvas {
            height:clamp(390px, 52dvh, 500px) !important;
            min-height:390px !important;
            max-height:500px !important;
        }
        .navigation-summary {
            top:6px !important;
            left:6px !important;
            right:auto !important;
            width:min(calc(100% - 12px), 330px) !important;
            padding:8px 9px !important;
            gap:7px !important;
        }
        .navigation-turn-icon {
            width:32px !important;
            height:32px !important;
            font-size:14px !important;
        }
        .navigation-summary .fw-bold,
        .navigation-metrics strong {
            font-size:12px !important;
        }
        .navigation-summary .small,
        .navigation-metrics small {
            font-size:8.5px !important;
            line-height:1.15 !important;
        }
        .navigation-summary .small:last-child {
            display:none !important;
        }
        .map-destination-card .card-body,
        .trip-contact-card .card-body,
        .order-items-mobile-card .card-body,
        .shipper-current-action-card .card-body {
            padding:7px 8px !important;
        }
        .map-destination-card .small,
        .trip-contact-card .small,
        .order-items-mobile-card .small,
        .shipper-current-action-card .small {
            font-size:8.75px !important;
            line-height:1.2 !important;
        }
        .map-destination-card .fw-bold,
        .trip-contact-card .fw-bold,
        .order-items-mobile-card .fw-bold,
        .shipper-current-action-card .fw-bold {
            font-size:12px !important;
            line-height:1.15 !important;
        }
        .map-destination-card .small.text-muted:last-child {
            -webkit-line-clamp:1;
        }
        .trip-mini-action {
            width:32px;
            height:32px;
        }
        .trip-mini-action i {
            font-size:12px;
        }
        .order-items-toggle-meta {
            gap:4px;
        }
        .order-items-mobile-card .badge {
            font-size:7.5px;
        }
        .arrival-guard,
        .customer-arrival-panel,
        .cod-collect-box,
        .paid-box {
            padding:7px 30px 7px 8px !important;
        }
        .arrival-guard.mb-2,
        .customer-arrival-panel.mb-2,
        .shipper-current-action-card form.mb-2,
        .shipper-current-action-card .small.mb-2 {
            margin-bottom:4px !important;
        }
        .arrival-guard .fw-semibold,
        .customer-arrival-panel .fw-bold {
            display:block;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            font-size:12px !important;
            line-height:1.15 !important;
        }
        .arrival-guard .small,
        .customer-arrival-panel .small {
            display:block !important;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            font-size:8.5px !important;
            line-height:1.15 !important;
        }
        .shipper-current-action-card .btn {
            min-height:32px;
            font-size:10px;
            padding:5px 8px;
        }
        .shipper-current-action-card .ship-swipe-confirm {
            height:38px;
        }
        .shipper-current-action-card .ship-swipe-knob {
            width:30px;
            height:30px;
            top:3px;
            left:3px;
        }
        .shipper-current-action-card .ship-swipe-label {
            inset:0 8px 0 42px;
            font-size:9px;
        }
        .pickup-inline-row {
            grid-template-columns:minmax(0, .92fr) minmax(0, 1.08fr);
            gap:6px;
            padding:8px 34px 8px 8px;
        }
        .pickup-inline-form .ship-swipe-confirm {
            min-height:38px;
        }
        .pickup-inline-form .ship-swipe-label {
            inset:0 6px 0 36px;
            font-size:8.5px;
        }
        .pickup-inline-form .ship-swipe-knob {
            width:28px;
            height:28px;
        }
        .issue-mini-button {
            top:7px;
            right:7px;
            width:24px;
            height:24px;
        }
        .issue-mini-button i {
            font-size:10px;
        }
        .issue-mini-button.is-inline-overlay {
            top:8px;
            right:8px;
            width:24px;
            height:24px;
            min-height:24px;
        }
        .arrival-guard-badge {
            font-size:8px;
            margin-bottom:4px;
            padding:2px 5px;
        }
    }

    .modal-dialog { margin:10px!important; }
    .modal-content { border:0; border-radius:0 !important; overflow:hidden; }
    .modal-footer .btn { flex:1; }

    @media (max-width: 380px) {
        .navigation-metrics { display:none!important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(() => {
    const mapEl = document.getElementById('shipperMap');
    if (!mapEl || !window.L) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const orderId = Number(mapEl.dataset.orderId);
    const currentOrderCode = String(mapEl.dataset.orderCode || `#${orderId}`);
    const routeUrl = mapEl.dataset.routeUrl;
    const locationUrl = mapEl.dataset.locationUrl;
    const ttsUrl = mapEl.dataset.ttsUrl;
    const orderStatus = mapEl.dataset.status;
    const customerArrivalConfirmed = {{ $customerArrivalConfirmed ? 'true' : 'false' }};
    const assignmentAccepted = {{ !empty($isAccepted) ? 'true' : 'false' }};
    const summaryInstruction = document.getElementById('nextInstruction');
    const stageEl = document.getElementById('navigationStage');
    const distanceEl = document.getElementById('routeDistance');
    const etaEl = document.getElementById('routeEta');
    const sourceEl = document.getElementById('routeSourcePill');
    const mapToolsToggle = document.getElementById('mapToolsToggle');
    const mapToolsMenu = document.getElementById('mapToolsMenu');
    const currentStopBadge = document.getElementById('currentStopBadge');
    const followButton = document.getElementById('followMeButton');
    const voiceButton = document.getElementById('voiceGuideButton');
    const repeatButton = document.getElementById('repeatGuideButton');
    const testGpsButton = document.getElementById('testGpsButton');
    const testDriveButton = document.getElementById('testDriveButton');
    const testStopButton = document.getElementById('testStopButton');
    const testSpeedControl = document.getElementById('testSpeedControl');
    const testSpeedRange = document.getElementById('testSpeedRange');
    const testSpeedNumber = document.getElementById('testSpeedNumber');
    const testSpeedValue = document.getElementById('testSpeedValue');
    const arrivalGuard = document.getElementById('arrivalGuard');
    const arrivalGuardBadge = document.getElementById('arrivalGuardBadge');
    const arrivalGuardTitle = document.getElementById('arrivalGuardTitle');
    const arrivalGuardText = document.getElementById('arrivalGuardText');
    const pickedUpButton = document.getElementById('pickedUpButton');
    const pickedUpButtonText = document.getElementById('pickedUpButtonText');
    const pickedUpButtonIcon = document.getElementById('pickedUpButtonIcon');
    const deliveredButton = document.getElementById('deliveredButton');
    const deliveredButtonText = document.getElementById('deliveredButtonText');
    const deliveredButtonIcon = document.getElementById('deliveredButtonIcon');
    const arrivedAtCustomerButton = document.getElementById('arrivedAtCustomerButton');
    const arrivedAtCustomerButtonText = document.getElementById('arrivedAtCustomerButtonText');
    const arrivedAtCustomerButtonIcon = document.getElementById('arrivedAtCustomerButtonIcon');
    const handoverButton = document.getElementById('handoverButton');
    const handoverButtonText = document.getElementById('handoverButtonText');
    const handoverButtonIcon = document.getElementById('handoverButtonIcon');

    const map = L.map(mapEl, {
        scrollWheelZoom:true,
        dragging:true,
        touchZoom:true,
        doubleClickZoom:true,
        zoomControl:false,
        boxZoom:false,
        keyboard:true,
        preferCanvas:true,
        fadeAnimation:false,
        markerZoomAnimation:false,
        zoomAnimation:true,
        wheelDebounceTime:28,
        wheelPxPerZoomLevel:96
    }).setView([19.8067, 105.7852], 13);

    // Desktop: lăn chuột = zoom, giữ chuột trái = kéo map.
    // Mobile: pinch 2 ngón = zoom.
    map.scrollWheelZoom.enable();
    map.dragging.enable();
    map.touchZoom.enable();
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom:19,
        updateWhenIdle:false,
        updateWhenZooming:true,
        keepBuffer:6,
        attribution:'&copy; OpenStreetMap contributors'
    }).addTo(map);

    let glMap = null;
    let glReady = false;
    let glCurrentMarker = null;
    let glCurrentMarkerPoint = null;
    let glCurrentMarkerFrameId = null;
    let glTargetMarker = null;
    let glCameraBearing = 0;
    let glLastCameraAt = 0;
    let glLastCameraCenter = null;
    const glRouteSourceId = 'active-route';
    const glRouteCasingLayerId = 'active-route-casing';
    const glRouteLayerId = 'active-route-line';

    function createGlMarkerElement(className, html) {
        const element = document.createElement('div');
        element.className = className;
        element.innerHTML = html;
        return element;
    }

    function geometryToGeoJson(geometry) {
        const coordinates = (Array.isArray(geometry) ? geometry : [])
            .filter(point => Array.isArray(point) && Number.isFinite(Number(point[0])) && Number.isFinite(Number(point[1])))
            .map(point => [Number(point[1]), Number(point[0])]);

        return {
            type:'Feature',
            properties:{},
            geometry:{
                type:'LineString',
                coordinates,
            },
        };
    }

    function ensureGlRouteLayer() {
        if (!glMap || !glReady) return false;
        if (!glMap.getSource(glRouteSourceId)) {
            glMap.addSource(glRouteSourceId, {
                type:'geojson',
                data:geometryToGeoJson([]),
            });
        }
        if (!glMap.getLayer(glRouteCasingLayerId)) {
            glMap.addLayer({
                id:glRouteCasingLayerId,
                type:'line',
                source:glRouteSourceId,
                layout:{'line-cap':'round','line-join':'round'},
                paint:{
                    'line-color':'rgba(255,255,255,.88)',
                    'line-width':8,
                    'line-opacity':.92,
                },
            });
        }
        if (!glMap.getLayer(glRouteLayerId)) {
            glMap.addLayer({
                id:glRouteLayerId,
                type:'line',
                source:glRouteSourceId,
                layout:{'line-cap':'round','line-join':'round'},
                paint:{
                    'line-color':'#1677ff',
                    'line-width':7.2,
                    'line-opacity':.96,
                },
            });
        }
        return true;
    }

    function renderGlRoute(geometry) {
        if (!ensureGlRouteLayer()) return false;
        glMap.getSource(glRouteSourceId).setData(geometryToGeoJson(geometry));
        return true;
    }

    function firstUsableGeometry(...candidates) {
        for (const candidate of candidates) {
            if (!Array.isArray(candidate)) continue;
            const geometry = candidate
                .filter(point => Array.isArray(point) && Number.isFinite(Number(point[0])) && Number.isFinite(Number(point[1])))
                .map(point => [Number(point[0]), Number(point[1])]);
            if (geometry.length >= 2) return geometry;
        }
        return [];
    }

    function renderGlCurrentMarker(point, heading = 0) {
        if (!glMap || !glReady || !point) return;
        const lngLat = [Number(point.longitude), Number(point.latitude)];
        if (!lngLat.every(Number.isFinite)) return;
        if (!glCurrentMarker) {
            glCurrentMarker = new maplibregl.Marker({
                element:createGlMarkerElement('gl-current-marker', '<div class="shipper-current-marker"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 19 21 12 17 5 21 12 3z"/></svg></div>'),
                anchor:'center',
                rotationAlignment:'viewport',
            }).setLngLat(lngLat).addTo(glMap);
            glCurrentMarkerPoint = lngLat;
        } else {
            if (glCurrentMarkerFrameId) {
                cancelAnimationFrame(glCurrentMarkerFrameId);
                glCurrentMarkerFrameId = null;
            }
            const from = Array.isArray(glCurrentMarkerPoint) ? glCurrentMarkerPoint : glCurrentMarker.getLngLat().toArray();
            const duration = testDriveActive ? 0 : 420;
            if (!duration) {
                glCurrentMarker.setLngLat(lngLat);
                glCurrentMarkerPoint = lngLat;
            } else {
                const startedAt = performance.now();
                const ease = t => 1 - Math.pow(1 - t, 3);
                const step = now => {
                    const ratio = Math.min(1, (now - startedAt) / duration);
                    const eased = ease(ratio);
                    const next = [
                        from[0] + (lngLat[0] - from[0]) * eased,
                        from[1] + (lngLat[1] - from[1]) * eased,
                    ];
                    glCurrentMarker.setLngLat(next);
                    glCurrentMarkerPoint = next;
                    if (ratio < 1) {
                        glCurrentMarkerFrameId = requestAnimationFrame(step);
                    } else {
                        glCurrentMarkerFrameId = null;
                        glCurrentMarkerPoint = lngLat;
                    }
                };
                glCurrentMarkerFrameId = requestAnimationFrame(step);
            }
        }
        const visualHeading = following
            ? bearingDelta(glCameraBearing, heading)
            : normalizeBearing(heading);
        glCurrentMarker.getElement().querySelector('svg')?.style.setProperty('transform', `rotate(${visualHeading}deg)`);
    }

    function renderGlTargetMarker(target) {
        if (!glMap || !glReady || !target) return;
        const lngLat = [Number(target.longitude ?? target.lng), Number(target.latitude ?? target.lat)];
        if (!lngLat.every(Number.isFinite)) return;
        if (!glTargetMarker) {
            glTargetMarker = new maplibregl.Marker({
                element:createGlMarkerElement('gl-target-marker', '<div class="shipper-target-marker"><span><i class="fa-solid fa-location-dot"></i></span></div>'),
                anchor:'bottom',
            }).setLngLat(lngLat).addTo(glMap);
        } else {
            glTargetMarker.setLngLat(lngLat);
        }
    }

    function setGlNavigationCamera(latLng, animate = true) {
        if (!glMap || !glReady || !latLng) return false;
        const cameraPoint = { latitude:Number(latLng.lat), longitude:Number(latLng.lng) };
        const cameraSnap = snapPointToRoute(cameraPoint, routeGeometry);
        const lookAhead = pointAheadOnRoute(cameraSnap, routeGeometry, 55);
        const centerPoint = lookAhead || cameraPoint;
        const center = [Number(centerPoint.longitude), Number(centerPoint.latitude)];
        if (!center.every(Number.isFinite)) return false;
        const now = performance.now();
        const moved = glLastCameraCenter
            ? haversine(
                {latitude:glLastCameraCenter[1], longitude:glLastCameraCenter[0]},
                {latitude:center[1], longitude:center[0]}
            )
            : Infinity;
        const targetBearing = normalizeBearing(headingAheadOnRoute(cameraSnap, routeGeometry) ?? currentHeading ?? 0);
        const turnDelta = Math.abs(bearingDelta(glCameraBearing, targetBearing));

        const minCameraGap = turnDelta > 80 ? 180 : 340;
        if (animate && moved < 2 && turnDelta < 3) return true;
        if (animate && now - glLastCameraAt < minCameraGap) return true;

        glLastCameraAt = now;
        glLastCameraCenter = center;
        const bearingFactor = turnDelta > 120 ? .54 : (turnDelta > 55 ? .38 : .22);
        glCameraBearing = animate ? smoothBearing(glCameraBearing, targetBearing, bearingFactor) : targetBearing;
        const zoom = Math.max(glMap.getZoom(), turnDelta > 70 ? 17.35 : 17.75);
        const options = {
            center,
            zoom,
            bearing:glCameraBearing,
            pitch:42,
            offset:[0, Math.round(mapEl.clientHeight * .13)],
            duration:animate ? (turnDelta > 80 ? 360 : 540) : 0,
            essential:true,
        };
        if (animate) glMap.easeTo(options);
        else glMap.jumpTo(options);
        return true;
    }

    function initGlNavigationMap() {
        if (!window.maplibregl || glMap) return;
        const glEl = document.createElement('div');
        glEl.className = 'gl-navigation-map';
        mapEl.appendChild(glEl);
        mapEl.classList.add('has-gl-nav');
        map.scrollWheelZoom.disable();
        map.dragging.disable();
        map.touchZoom.disable();

        glMap = new maplibregl.Map({
            container:glEl,
            style:{
                version:8,
                sources:{
                    osm:{
                        type:'raster',
                        tiles:[
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
                            'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
                            'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        ],
                        tileSize:256,
                        attribution:'&copy; OpenStreetMap contributors',
                    },
                },
                layers:[{
                    id:'osm',
                    type:'raster',
                    source:'osm',
                    paint:{
                        'raster-contrast':0.18,
                        'raster-saturation':-0.06,
                    },
                }],
            },
            center:[105.7852, 19.8067],
            zoom:13,
            pitch:46,
            bearing:0,
            attributionControl:false,
            antialias:true,
            maxPitch:70,
            renderWorldCopies:false,
        });
        glMap.addControl(new maplibregl.AttributionControl({compact:true}));
        glMap.scrollZoom.enable();
        glMap.dragPan.enable();
        glMap.touchZoomRotate.enable();
        glMap.keyboard.enable();
        setTimeout(() => glMap?.resize(), 80);
        window.addEventListener('resize', () => glMap?.resize());

        glMap.on('load', () => {
            glReady = true;
            glMap.resize();
            if (renderGlRoute(routeGeometry)) {
                lastRenderedGlRouteKey = geometryRouteKey(routeGeometry);
            }
            if (targetMarker) {
                const targetLatLng = targetMarker.getLatLng();
                renderGlTargetMarker({latitude:targetLatLng.lat, longitude:targetLatLng.lng});
            }
            if (currentMarker) {
                const currentLatLng = currentMarker.getLatLng();
                renderGlCurrentMarker({latitude:currentLatLng.lat, longitude:currentLatLng.lng}, currentHeading);
                setGlNavigationCamera(currentLatLng, false);
            }
        });
        glMap.on('error', () => {
            if (glReady) return;
            mapEl.classList.remove('has-gl-nav');
            glEl.remove();
            glMap = null;
            map.scrollWheelZoom.enable();
            map.dragging.enable();
            map.touchZoom.enable();
        });
        glMap.on('dragstart', () => {
            following = false;
            followButton?.classList.remove('is-active');
        });
        glMap.on('click', event => {
            if (!testMode) return;
            following = true;
            const {lat, lng} = event.lngLat;
            sourceEl.textContent = `TEST GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            persistTestGpsPoint({ latitude:lat, longitude:lng, accuracy:8 });
            applyPosition(fakeGeoPosition(lat, lng, 8), true);
        });
    }

    initGlNavigationMap();

    const closeMapTools = () => {
        if (!mapToolsToggle || !mapToolsMenu) return;
        mapToolsMenu.hidden = true;
        mapToolsToggle.setAttribute('aria-expanded', 'false');
    };

    if (mapToolsToggle && mapToolsMenu) {
        mapToolsToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const opening = mapToolsMenu.hidden;
            mapToolsMenu.hidden = !opening;
            mapToolsToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
        });
        mapToolsMenu.addEventListener('click', event => event.stopPropagation());
        map.on('click', closeMapTools);
        document.addEventListener('click', event => {
            if (!mapToolsMenu.hidden && !mapToolsMenu.contains(event.target) && !mapToolsToggle.contains(event.target)) closeMapTools();
        });
    }

    function currentIconForHeading(heading = 0) {
        const normalized = ((Number(heading) % 360) + 360) % 360;
        return L.divIcon({
            className:'',
            html:`<div class="shipper-current-marker"><svg viewBox="0 0 24 24" aria-hidden="true" style="transform:rotate(${normalized}deg)"><path fill="currentColor" d="M12 3 19 21 12 17 5 21 12 3z"/></svg></div>`,
            iconSize:[32,32],
            iconAnchor:[16,16]
        });
    }
    const targetIcon = L.divIcon({ className:'', html:'<div class="shipper-target-marker"><span><i class="fa-solid fa-location-dot"></i></span></div>', iconSize:[34,34], iconAnchor:[17,34] });

    let currentMarker = null;
    let targetMarker = null;
    let routeLine = null;
    let routeAltLine = null;
    let bundleGroupLines = [];
    let bundleStopMarkers = [];
    let routeGeometry = [];
    let currentPosition = null;
    let currentRoute = null;
    let currentHeading = 0;
    let displayedHeading = 0;
    let lastCameraAt = 0;
    let lastCameraCenter = null;
    let following = true;
    let routePending = false;
    let lastRouteAt = 0;
    let lastRoutePosition = null;
    let lastLocationSentAt = 0;
    let lastSentPosition = null;
    let lastNavigationSideEffectsAt = 0;
    let firstFit = true;
    let voiceEnabled = true;
    let lastSpeakAt = 0;
    let lastGuideText = '';
    let guidanceState = { key:null, warned:false, turn:false, intro:false, arrived:false };
    let arrivalVoiceLock = { targetKey:null, event:null, muted:false, announced:false };
    let latestArrivalSnapshot = null;
    let latestArrivalSnapshotTargetKey = '';
    let currentAudio = null;
    let voiceRequestId = 0;
    let voiceRequestController = null;
    const voiceCache = new Map();
    let ttsWarningShown = false;
    let ttsUnavailable = false;
    let testMode = false;
    let testDriveFrameId = null;
    let testDriveLastFrameAt = 0;
    let testDriveSegmentIndex = 0;
    let testDriveSegmentOffsetM = 0;
    let testDriveGeometry = [];
    let testDriveActive = false;
    let displayedRouteGeometry = [];
    let lastRenderedRouteKey = '';
    let lastRenderedGlRouteKey = '';
    let currentSpeedKmh = 0;
    let lastSpeedSample = null;
    const TEST_DRIVE_SPEED_MIN_KMH = 5;
    const TEST_DRIVE_SPEED_MAX_KMH = 80;
    const TEST_DRIVE_SPEED_DEFAULT_KMH = 50;
    const TEST_DRIVE_SPEED_KEY = 'shipper_map_test_drive_speed_kmh_v1';
    let testDriveSpeedKmh = TEST_DRIVE_SPEED_DEFAULT_KMH;
    let geoWatchId = null;
    const isLocalHost = ['127.0.0.1', 'localhost'].includes(window.location.hostname);
    const initialOrderStatus = String(mapEl.dataset.status || '');
    let statusReloading = false;
    const NAV_VOICE_RATE = 1.00;
    const NAV_VOICE_VOLUME = 0.92;
    const NAV_VOICE_INTRO_GAP_MS = 170;
    const NAV_GUIDE_PREPARE_ALERT_M = 100;
    const NAV_GUIDE_TURN_ALERT_M = 32;
    const NAV_GUIDE_ARRIVE_ALERT_M = 120;
    const NAV_GUIDE_ARRIVE_NOW_M = 35;
    const NAV_GUIDE_SPEED_SAMPLE_MAX_MS = 12000;
    const TEST_GPS_SESSION_KEY = 'shipper_map_test_gps_v1';
    const TEST_GPS_POINT_KEY = 'shipper_map_test_gps_point_v1';

    // File chuông thật thay cho WebAudio oscillator:
    // nghe rõ và ổn định hơn trên Chrome/điện thoại.
    const navigationIntroAudio = new Audio('{{ asset('audio/navigation_intro.wav') }}?v=2');
    navigationIntroAudio.preload = 'auto';
    navigationIntroAudio.volume = 0.55;
    navigationIntroAudio.load();

    // Chrome chỉ cho phát audio sau tương tác đầu tiên của người dùng.
    let audioUnlocked = false;
    const unlockAudio = () => {
        if (audioUnlocked) return;
        audioUnlocked = true;
        navigationIntroAudio.play()
            .then(() => {
                navigationIntroAudio.pause();
                navigationIntroAudio.currentTime = 0;
            })
            .catch(() => {});
    };
    window.addEventListener('pointerdown', unlockAudio, { once:true, passive:true });
    window.addEventListener('keydown', unlockAudio, { once:true });

    if (isLocalHost) {
        testGpsButton?.classList.remove('d-none');
    }

    map.on('dragstart', () => {
        following = false;
        followButton?.classList.remove('is-active');
        clearHeadingUpMap();
    });
    map.on('zoomstart', () => {
        clearHeadingUpMap();
    });
    followButton?.addEventListener('click', () => {
        following = true;
        followButton.classList.add('is-active');
        if (currentMarker) setNavigationCamera(currentMarker.getLatLng(), true);
        else if (currentPosition) setNavigationCamera(L.latLng(currentPosition.latitude, currentPosition.longitude), true);
        closeMapTools();
    });
    map.on('moveend zoomend', () => {
        if (following) scheduleHeadingUpMap();
    });

    voiceButton?.addEventListener('click', () => {
        voiceEnabled = !voiceEnabled;
        voiceButton.setAttribute('aria-pressed', voiceEnabled ? 'true' : 'false');
        voiceButton.innerHTML = voiceEnabled
            ? '<i class="fa-solid fa-volume-high me-1"></i> Giọng nói: Bật'
            : '<i class="fa-solid fa-volume-xmark me-1"></i> Giọng nói: Tắt';
        repeatButton?.classList.toggle('d-none', !voiceEnabled);

        if (voiceEnabled) {
            ttsUnavailable = false;
            speak('Đã bật hướng dẫn bằng giọng nói.', true);
            setTimeout(() => refreshGuidance(true), 450);
        } else {
            voiceRequestController?.abort();
            voiceRequestController = null;
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
            }
        }
        closeMapTools();
    });

    repeatButton?.addEventListener('click', () => {
        if (!voiceEnabled) return;
        speak(lastGuideText || summaryInstruction.textContent || 'Tiếp tục đi theo tuyến đường trên bản đồ.', true);
        closeMapTools();
    });

    function stopCurrentAudioPlayback() {
        if (!currentAudio) return;
        currentAudio.pause();
        currentAudio.currentTime = 0;
    }

    const distanceText = meters => {
        const n = Number(meters);
        if (!Number.isFinite(n)) return '--';
        return n >= 1000 ? `${(n / 1000).toFixed(n >= 10000 ? 0 : 1)} km` : `${Math.max(1, Math.round(n))} mét`;
    };
    const compactDistance = meters => {
        const n = Number(meters);
        if (!Number.isFinite(n)) return '--';
        return n >= 1000 ? `${(n / 1000).toFixed(n >= 10000 ? 0 : 1)} km` : `${Math.max(1, Math.round(n))} m`;
    };
    const minutes = seconds => `${Math.max(1, Math.round(Number(seconds || 0) / 60))} phút`;
    const speedKmhFromDistanceDuration = (meters, seconds) => {
        const distance = Number(meters);
        const duration = Number(seconds);
        if (!Number.isFinite(distance) || !Number.isFinite(duration) || distance <= 0 || duration <= 0) return null;
        return (distance / duration) * 3.6;
    };
    const compactSpeed = speedKmh => {
        const speed = Number(speedKmh);
        if (!Number.isFinite(speed) || speed <= 0) return '';
        return `~${Math.max(1, Math.round(speed))} km/h`;
    };

    function haversine(a, b) {
        if (!a || !b) return Infinity;
        const R = 6371000;
        const toRad = x => x * Math.PI / 180;
        const dLat = toRad(Number(b.latitude) - Number(a.latitude));
        const dLng = toRad(Number(b.longitude) - Number(a.longitude));
        const lat1 = toRad(Number(a.latitude));
        const lat2 = toRad(Number(b.latitude));
        const h = Math.sin(dLat/2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng/2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(Math.max(0, 1-h)));
    }

    function routeAverageSpeedKmh(route = currentRoute) {
        if (!route) return null;
        return speedKmhFromDistanceDuration(
            route.display_distance_m ?? route.distance_m,
            route.display_duration_s ?? route.duration_s
        );
    }

    function updateCurrentSpeed(point, sampledAtMs = Date.now()) {
        if (!point || !Number.isFinite(Number(point.latitude)) || !Number.isFinite(Number(point.longitude))) return currentSpeedKmh;

        const nextSample = {
            point: {
                latitude:Number(point.latitude),
                longitude:Number(point.longitude)
            },
            at:Number(sampledAtMs)
        };

        if (!lastSpeedSample) {
            lastSpeedSample = nextSample;
            return currentSpeedKmh;
        }

        const elapsedMs = nextSample.at - Number(lastSpeedSample.at || 0);
        if (!Number.isFinite(elapsedMs) || elapsedMs < 1800) return currentSpeedKmh;

        const movedMeters = haversine(lastSpeedSample.point, nextSample.point);
        lastSpeedSample = nextSample;

        if (!Number.isFinite(movedMeters) || elapsedMs <= 0 || elapsedMs > NAV_GUIDE_SPEED_SAMPLE_MAX_MS) return currentSpeedKmh;

        const normalizedMeters = movedMeters < 2 ? 0 : movedMeters;
        const rawSpeedKmh = (normalizedMeters / (elapsedMs / 1000)) * 3.6;
        if (!Number.isFinite(rawSpeedKmh) || rawSpeedKmh > 110) return currentSpeedKmh;

        currentSpeedKmh = currentSpeedKmh > 0
            ? (currentSpeedKmh * 0.65) + (rawSpeedKmh * 0.35)
            : rawSpeedKmh;

        return currentSpeedKmh;
    }

    function effectiveTravelSpeedKmh() {
        if (Number.isFinite(currentSpeedKmh) && currentSpeedKmh >= 4) return currentSpeedKmh;
        const routeSpeed = routeAverageSpeedKmh(currentRoute);
        return Number.isFinite(routeSpeed) ? routeSpeed : 0;
    }

    function toRad(value) {
        return Number(value) * Math.PI / 180;
    }

    function toDeg(value) {
        return Number(value) * 180 / Math.PI;
    }

    function normalizeBearing(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return 0;
        return ((n % 360) + 360) % 360;
    }

    function bearingDelta(from, to) {
        return ((normalizeBearing(to) - normalizeBearing(from) + 540) % 360) - 180;
    }

    function smoothBearing(from, to, factor = .38) {
        return normalizeBearing(normalizeBearing(from) + bearingDelta(from, to) * factor);
    }

    function clearHeadingUpMap() {
        displayedHeading = normalizeBearing(currentHeading || 0);
    }

    function applyHeadingUpMap() {
        const bearing = normalizeBearing(currentHeading || 0);
        displayedHeading = smoothBearing(displayedHeading, bearing);
    }

    function scheduleHeadingUpMap() {
        requestAnimationFrame(() => applyHeadingUpMap());
    }

    function navigationCameraCenter(latLng, zoom) {
        return latLng;
    }

    function setNavigationCamera(latLng, animate = true) {
        if (!latLng) return;
        if (setGlNavigationCamera(latLng, animate)) return;
        const now = performance.now();
        const center = navigationCameraCenter(latLng, map.getZoom());
        const moved = lastCameraCenter
            ? haversine(
                { latitude:lastCameraCenter.lat, longitude:lastCameraCenter.lng },
                { latitude:center.lat, longitude:center.lng }
            )
            : Infinity;

        if (animate && now - lastCameraAt < 650) {
            scheduleHeadingUpMap();
            return;
        }
        if (animate && moved < 5) {
            scheduleHeadingUpMap();
            return;
        }

        lastCameraAt = now;
        lastCameraCenter = center;
        const zoom = map.getZoom() < 16 ? 16 : map.getZoom();
        if (Math.abs(map.getZoom() - zoom) > .01) {
            map.setView(center, zoom, { animate:false });
        } else {
            map.panTo(center, { animate, duration:.45, easeLinearity:.25 });
        }
        scheduleHeadingUpMap();
    }

    function bearingBetween(a, b) {
        if (!a || !b) return 0;
        const lat1 = toRad(a.latitude);
        const lat2 = toRad(b.latitude);
        const dLng = toRad(Number(b.longitude) - Number(a.longitude));
        const y = Math.sin(dLng) * Math.cos(lat2);
        const x = Math.cos(lat1) * Math.sin(lat2)
            - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
        return normalizeBearing(toDeg(Math.atan2(y, x)));
    }

    function destinationPoint(point, bearingDeg, distanceM) {
        const R = 6371000;
        const brng = toRad(bearingDeg);
        const lat1 = toRad(point.latitude);
        const lng1 = toRad(point.longitude);
        const dr = Number(distanceM) / R;
        const lat2 = Math.asin(Math.sin(lat1) * Math.cos(dr) + Math.cos(lat1) * Math.sin(dr) * Math.cos(brng));
        const lng2 = lng1 + Math.atan2(
            Math.sin(brng) * Math.sin(dr) * Math.cos(lat1),
            Math.cos(dr) - Math.sin(lat1) * Math.sin(lat2)
        );
        return { latitude: toDeg(lat2), longitude: toDeg(lng2) };
    }

    function projectPointToSegment(point, a, b) {
        const refLat = toRad((Number(point.latitude) + Number(a.latitude) + Number(b.latitude)) / 3);
        const scale = Math.cos(refLat);
        const R = 6371000;
        const p = {
            x: R * toRad(point.longitude) * scale,
            y: R * toRad(point.latitude)
        };
        const start = {
            x: R * toRad(a.longitude) * scale,
            y: R * toRad(a.latitude)
        };
        const end = {
            x: R * toRad(b.longitude) * scale,
            y: R * toRad(b.latitude)
        };

        const dx = end.x - start.x;
        const dy = end.y - start.y;
        const len2 = dx * dx + dy * dy;
        const t = len2 > 0 ? Math.max(0, Math.min(1, ((p.x - start.x) * dx + (p.y - start.y) * dy) / len2)) : 0;
        const proj = {
            x: start.x + dx * t,
            y: start.y + dy * t
        };
        const distance = Math.hypot(p.x - proj.x, p.y - proj.y);
        const cross = dx * (p.y - start.y) - dy * (p.x - start.x);
        const side = cross >= 0 ? 1 : -1;
        return {
            point: {
                latitude: toDeg(proj.y / R),
                longitude: toDeg(proj.x / (R * scale || 1))
            },
            distance,
            side,
            heading: bearingBetween(a, b),
            t
        };
    }

    function snapPointToRoute(point, geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (path.length < 2 || !point) return null;

        let best = null;
        for (let i = 0; i < path.length - 1; i++) {
            const a = path[i];
            const b = path[i + 1];
            if (!Array.isArray(a) || !Array.isArray(b)) continue;
            const ax = Number(a[0]);
            const ay = Number(a[1]);
            const bx = Number(b[0]);
            const by = Number(b[1]);
            if (![ax, ay, bx, by].every(Number.isFinite)) continue;

            const candidate = projectPointToSegment(
                point,
                { latitude: ax, longitude: ay },
                { latitude: bx, longitude: by }
            );
            if (!best || candidate.distance < best.distance) {
                best = Object.assign({ index:i }, candidate);
            }
        }

        if (!best) return null;

        return {
            latitude: best.point.latitude,
            longitude: best.point.longitude,
            snapped: best.point,
            heading: best.heading,
            side: best.side,
            distance: best.distance,
            segmentIndex: best.index
        };
    }

    function geometryRouteKey(geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (path.length < 2) return '';
        const first = path[0];
        const mid = path[Math.floor(path.length / 2)];
        const last = path[path.length - 1];
        return [
            path.length,
            Number(first?.[0]).toFixed(5),
            Number(first?.[1]).toFixed(5),
            Number(mid?.[0]).toFixed(5),
            Number(mid?.[1]).toFixed(5),
            Number(last?.[0]).toFixed(5),
            Number(last?.[1]).toFixed(5),
        ].join('|');
    }

    function currentVisibleGeometry() {
        const active = Array.isArray(displayedRouteGeometry) && displayedRouteGeometry.length >= 2
            ? displayedRouteGeometry
            : Array.isArray(routeGeometry) && routeGeometry.length >= 2
            ? routeGeometry
            : (Array.isArray(currentRoute?.geometry) ? currentRoute.geometry : []);
        return active
            .map(point => [Number(point?.[0]), Number(point?.[1])])
            .filter(point => point.every(Number.isFinite));
    }

    function remainingGeometryFromSnap(snapped, geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (!snapped || path.length < 2 || !Number.isInteger(snapped.segmentIndex)) return path;

        const start = [
            Number(snapped.latitude ?? snapped.snapped?.latitude),
            Number(snapped.longitude ?? snapped.snapped?.longitude)
        ];
        if (!start.every(Number.isFinite)) return path;

        const remaining = [start];
        for (let i = snapped.segmentIndex + 1; i < path.length; i++) {
            const point = [Number(path[i]?.[0]), Number(path[i]?.[1])];
            if (point.every(Number.isFinite)) remaining.push(point);
        }

        return remaining.length >= 2 ? remaining : path.slice(-2);
    }

    function renderDisplayedRoute(geometry) {
        const path = firstUsableGeometry(geometry);
        displayedRouteGeometry = path;
        if (path.length < 2) return;

        if (glReady) {
            if (renderGlRoute(path)) {
                lastRenderedGlRouteKey = geometryRouteKey(path);
            }
            return;
        }

        if (routeLine) {
            routeLine.setLatLngs(path);
        } else {
            routeLine = L.polyline(path, {
                color:'#1677ff',
                weight:8,
                opacity:.96,
                lineJoin:'round',
                lineCap:'round'
            }).addTo(map);
        }
        lastRenderedRouteKey = geometryRouteKey(path);
    }

    function routeProgressForPoint(point, geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (!point || path.length < 2) return null;
        const projection = projectPointToRoute(point, path);
        if (!projection || !Number.isFinite(Number(projection.index))) return null;

        let beforeMeters = 0;
        for (let i = 0; i < projection.index; i++) {
            beforeMeters += haversine(testPointObject(path[i]), testPointObject(path[i + 1]));
        }

        const segmentMeters = haversine(testPointObject(path[projection.index]), testPointObject(path[projection.index + 1]));
        const offsetM = Math.max(0, Math.min(segmentMeters, segmentMeters * Number(projection.t || 0)));

        return {
            segmentIndex: Math.min(projection.index, path.length - 2),
            segmentOffsetM: offsetM,
            meters: beforeMeters + offsetM,
            totalMeters: routeLengthMeters(path),
            snapped: projection.point,
            heading: projection.heading,
        };
    }

    function routeLengthMeters(geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        let meters = 0;
        for (let i = 0; i < path.length - 1; i++) {
            meters += haversine(testPointObject(path[i]), testPointObject(path[i + 1]));
        }
        return meters;
    }

    function pointAheadOnRoute(snapped, geometry, lookAheadMeters = 45) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (!snapped || path.length < 2 || !Number.isInteger(snapped.segmentIndex)) return null;

        let remaining = Math.max(8, Number(lookAheadMeters) || 45);
        let from = snapped.snapped || { latitude:snapped.latitude, longitude:snapped.longitude };

        for (let i = snapped.segmentIndex; i < path.length - 1; i++) {
            const end = { latitude:Number(path[i + 1][0]), longitude:Number(path[i + 1][1]) };
            if (![from.latitude, from.longitude, end.latitude, end.longitude].every(Number.isFinite)) continue;

            const segmentMeters = haversine(from, end);
            if (!Number.isFinite(segmentMeters) || segmentMeters <= 0) continue;

            if (remaining <= segmentMeters) {
                const ratio = remaining / segmentMeters;
                return {
                    latitude: from.latitude + ((end.latitude - from.latitude) * ratio),
                    longitude: from.longitude + ((end.longitude - from.longitude) * ratio),
                };
            }

            remaining -= segmentMeters;
            from = end;
        }

        return from;
    }

    function headingAheadOnRoute(snapped, geometry) {
        const from = snapped?.snapped || snapped;
        const ahead = pointAheadOnRoute(snapped, geometry, 45);
        if (!from || !ahead || haversine(from, ahead) < 3) return null;
        return bearingBetween(from, ahead);
    }

    function projectPointToRoute(point, geometry) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (path.length < 2 || !point) return null;

        let best = null;
        for (let i = 0; i < path.length - 1; i++) {
            const a = path[i];
            const b = path[i + 1];
            if (!Array.isArray(a) || !Array.isArray(b)) continue;
            const ax = Number(a[0]);
            const ay = Number(a[1]);
            const bx = Number(b[0]);
            const by = Number(b[1]);
            if (![ax, ay, bx, by].every(Number.isFinite)) continue;

            const candidate = projectPointToSegment(
                point,
                { latitude: ax, longitude: ay },
                { latitude: bx, longitude: by }
            );
            if (!best || candidate.distance < best.distance) {
                best = Object.assign({ index:i }, candidate);
            }
        }

        return best;
    }

    function stageAnchorOnRoute(stage) {
        const geometry = Array.isArray(stage?.route?.geometry) ? stage.route.geometry : [];
        if (geometry.length >= 2) {
            const last = geometry[geometry.length - 1];
            const prev = geometry[geometry.length - 2];
            if (Array.isArray(last) && Array.isArray(prev)) {
                const end = { latitude:Number(last[0]), longitude:Number(last[1]) };
                const beforeEnd = { latitude:Number(prev[0]), longitude:Number(prev[1]) };
                if ([end.latitude, end.longitude, beforeEnd.latitude, beforeEnd.longitude].every(Number.isFinite)) {
                    return {
                        point: end,
                        heading: bearingBetween(beforeEnd, end),
                    };
                }
            }
        }

        const latitude = Number(stage?.point?.latitude);
        const longitude = Number(stage?.point?.longitude);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;

        return {
            point: { latitude, longitude },
            heading: 0,
        };
    }

    function pointAlongGeometryFromEnd(geometry, distanceBackMeters = 18) {
        const path = Array.isArray(geometry) ? geometry : [];
        if (path.length < 2) return null;

        let remaining = Math.max(0, Number(distanceBackMeters) || 0);
        for (let i = path.length - 1; i > 0; i--) {
            const end = { latitude:Number(path[i][0]), longitude:Number(path[i][1]) };
            const start = { latitude:Number(path[i - 1][0]), longitude:Number(path[i - 1][1]) };
            if (![start.latitude, start.longitude, end.latitude, end.longitude].every(Number.isFinite)) continue;

            const segmentMeters = haversine(start, end);
            if (!Number.isFinite(segmentMeters) || segmentMeters <= 0) continue;

            if (remaining <= segmentMeters) {
                const ratio = (segmentMeters - remaining) / segmentMeters;
                return {
                    point: {
                        latitude: start.latitude + ((end.latitude - start.latitude) * ratio),
                        longitude: start.longitude + ((end.longitude - start.longitude) * ratio),
                    },
                    heading: bearingBetween(start, end),
                };
            }

            remaining -= segmentMeters;
        }

        const fallbackStart = { latitude:Number(path[0][0]), longitude:Number(path[0][1]) };
        const fallbackEnd = { latitude:Number(path[1][0]), longitude:Number(path[1][1]) };
        if (![fallbackStart.latitude, fallbackStart.longitude, fallbackEnd.latitude, fallbackEnd.longitude].every(Number.isFinite)) {
            return null;
        }

        return {
            point: fallbackStart,
            heading: bearingBetween(fallbackStart, fallbackEnd),
        };
    }

    function placeStopOnStage(stop, stage, stopIndex = 0, totalStops = 1) {
        const latitude = Number(stop?.latitude);
        const longitude = Number(stop?.longitude);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;

        const anchor = stageAnchorOnRoute(stage);
        if (!anchor) {
            return { latitude, longitude };
        }

        let point = { ...anchor.point };
        const lateralOffset = totalStops > 1 ? (stopIndex - ((totalStops - 1) / 2)) * 13 : 0;
        const backOffset = stage?.state === 'current' ? 10 : 0;

        if (backOffset > 0 && Number.isFinite(anchor.heading)) {
            point = destinationPoint(point, normalizeBearing(anchor.heading + 180), backOffset);
        }

        if (Math.abs(lateralOffset) > 0) {
            point = destinationPoint(
                point,
                normalizeBearing(anchor.heading + (lateralOffset >= 0 ? 90 : -90)),
                Math.abs(lateralOffset)
            );
        }

        return point;
    }

    function maneuverLabel(step) {
        if (!step) return 'tiếp tục đi thẳng';
        if (step.type === 'arrive') return `đến ${routeTargetNoun()}`;
        if (step.type === 'roundabout' || step.type === 'rotary') return 'đi vào vòng xuyến';

        const map = {
            left:'rẽ trái', right:'rẽ phải', 'slight left':'chếch trái', 'slight right':'chếch phải',
            straight:'đi thẳng', uturn:'quay đầu', 'sharp left':'rẽ gắt sang trái', 'sharp right':'rẽ gắt sang phải'
        };
        return map[step.modifier] || (step.type === 'turn' ? 'rẽ theo tuyến đường' : 'tiếp tục');
    }

    function maneuverText(step) {
        const action = maneuverLabel(step);
        if (!step) return 'Đi theo tuyến đường được tô trên bản đồ';
        if (step.type === 'arrive') return `Bạn sắp đến ${routeTargetNoun()}`;
        return step.name ? `${capitalize(action)} vào ${spokenRoadName(step.name)}` : capitalize(action);
    }

    function capitalize(value) {
        const text = String(value || '');
        return text ? text.charAt(0).toUpperCase() + text.slice(1) : text;
    }

    function spokenRoadName(name) {
        const text = String(name || '').trim();
        if (!text) return '';
        if (/^(đường|phố|quốc lộ|tỉnh lộ|đại lộ|cao tốc|cầu|hầm|ngõ|hẻm)\b/i.test(text)) return text;
        return `đường ${text}`;
    }

    function stepRoadText(step) {
        return [step?.name, step?.ref, step?.pronunciation].filter(Boolean).join(' ').trim();
    }

    function isStraightContinuationStep(step) {
        if (!step) return true;
        const type = String(step.type || '').trim().toLowerCase();
        const modifier = String(step.modifier || '').trim().toLowerCase();
        if (type === 'arrive') return false;
        if (type === 'new name' || type === 'continue' || type === 'depart') return true;
        if (type === 'turn') return !modifier || modifier === 'straight';
        return modifier === 'straight';
    }

    function isMeaningfulGuidanceStep(step) {
        if (!step) return false;
        const type = String(step.type || '').trim().toLowerCase();
        const modifier = String(step.modifier || '').trim().toLowerCase();
        if (type === 'arrive') return true;
        if (['roundabout', 'rotary', 'fork', 'merge', 'end of road'].includes(type)) return true;
        if (type === 'turn') return Boolean(modifier && modifier !== 'straight');
        if (modifier && modifier !== 'straight') return true;
        return !isStraightContinuationStep(step) && Boolean(type);
    }

    function isNarrowRoadStep(step) {
        const road = stepRoadText(step);
        return /(ngõ|ngo|ngách|ngach|hẻm|hem|kiệt|kiet|xóm|xom)/i.test(road);
    }

    function guidanceThresholds(step, speedKmh = 0, nextSegmentDistance = Infinity) {
        const speed = Number.isFinite(Number(speedKmh)) ? Number(speedKmh) : 0;
        const tightRoad = isNarrowRoadStep(step)
            || (Number.isFinite(Number(nextSegmentDistance)) && Number(nextSegmentDistance) <= 40);

        if (tightRoad) {
            if (speed >= 28) return { prepare:20, turn:10, arrive:40, arriveNow:16 };
            if (speed >= 16) return { prepare:12, turn:6, arrive:28, arriveNow:12 };
            return { prepare:8, turn:5, arrive:20, arriveNow:8 };
        }

        if (speed >= 42) return { prepare:100, turn:42, arrive:140, arriveNow:30 };
        if (speed >= 28) return { prepare:70, turn:28, arrive:110, arriveNow:26 };
        if (speed >= 16) return { prepare:45, turn:16, arrive:82, arriveNow:22 };
        return { prepare:24, turn:10, arrive:56, arriveNow:18 };
    }

    function routeTargetKind() {
        const kind = String(currentRoute?.target_type || '').trim();
        return kind || 'customer';
    }

    function routeTargetNoun() {
        switch (routeTargetKind()) {
            case 'branch':
                return 'quán lấy hàng';
            case 'handover':
                return 'điểm bàn giao';
            default:
                return 'điểm giao hàng';
        }
    }

    function routeTargetActionLabel() {
        switch (routeTargetKind()) {
            case 'branch':
                return 'Tới quán lấy hàng';
            case 'handover':
                return 'Tới điểm bàn giao';
            default:
                return 'Tới khách giao hàng';
        }
    }

    function routeTargetShortLabel() {
        switch (routeTargetKind()) {
            case 'branch':
                return 'quán';
            case 'handover':
                return 'điểm bàn giao';
            default:
                return 'khách';
        }
    }

    function compactInstructionDistance(distanceMeters) {
        const meters = Number(distanceMeters);
        if (!Number.isFinite(meters) || meters <= 0) return 'Ngay phía trước';
        return `Còn ${compactDistance(meters)} nữa`;
    }

    function compactGuidanceStage(step, isApproaching = false) {
        if (!step) return 'Đi thẳng';
        if (step.type === 'arrive') return `Tới ${routeTargetShortLabel()}`;
        if (!isApproaching) return 'Đi thẳng';
        return capitalize(maneuverLabel(step));
    }

    function routeTargetKey(route = currentRoute) {
        if (!route) return '';
        const type = String(route?.target_type || '').trim() || 'customer';
        const label = String(route?.target_label || '').trim();
        const latitude = Number(route?.target_latitude);
        const longitude = Number(route?.target_longitude);
        const locationKey = Number.isFinite(latitude) && Number.isFinite(longitude)
            ? `${latitude.toFixed(5)}|${longitude.toFixed(5)}`
            : label;
        return `${type}|${locationKey}`;
    }

    function arrivalEventNoun(event) {
        switch (String(event || '')) {
            case 'arrived_store':
                return 'chi nhánh';
            case 'arrived_handover':
                return 'điểm bàn giao';
            default:
                return 'điểm giao hàng';
        }
    }

    function resetArrivalVoiceLock() {
        arrivalVoiceLock = { targetKey:null, event:null, muted:false, announced:false };
    }

    function syncArrivalVoiceLock(nextRoute = currentRoute) {
        const nextKey = routeTargetKey(nextRoute);
        if (!nextKey) return;
        if (arrivalVoiceLock.targetKey && arrivalVoiceLock.targetKey !== nextKey) {
            resetArrivalVoiceLock();
        }
    }

    function isGuidanceMutedByArrival() {
        const currentKey = routeTargetKey();
        return Boolean(arrivalVoiceLock.muted && arrivalVoiceLock.targetKey && currentKey && arrivalVoiceLock.targetKey === currentKey);
    }

    function pausedGuidanceText() {
        switch (String(arrivalVoiceLock.event || '')) {
            case 'arrived_store':
                return 'Bạn đã tới quán. Chờ xác nhận đã lấy hàng để chuyển sang chặng tiếp theo.';
            case 'arrived_handover':
                return 'Bạn đã tới điểm bàn giao. Chờ xác nhận nhận bàn giao để chuyển sang chặng tiếp theo.';
            case 'arrived_customer':
                return 'Bạn đã tới khách. Chờ xác nhận đã đến nơi hoặc giao hàng để chuyển sang bước tiếp theo.';
            default:
                return 'Bạn đã tới điểm đến. Chờ xác nhận trạng thái để tiếp tục.';
        }
    }

    function lockVoiceForVerifiedArrival(arrival) {
        if (!arrival || !arrival.required || !arrival.verified) return;

        const targetKey = routeTargetKey();
        if (!targetKey) return;

        if (arrivalVoiceLock.targetKey !== targetKey) {
            arrivalVoiceLock = {
                targetKey,
                event: arrival.event || null,
                muted: false,
                announced: false,
            };
        } else {
            arrivalVoiceLock.event = arrival.event || arrivalVoiceLock.event;
        }

        const alreadyAnnounced = guidanceState.arrived === true;
        guidanceState.arrived = true;

        if (!arrivalVoiceLock.announced) {
            arrivalVoiceLock.announced = true;
            if (!alreadyAnnounced && voiceEnabled) {
                speak(`Bạn đã đến ${arrivalEventNoun(arrival.event)}.`, true);
            }
        }

        arrivalVoiceLock.muted = true;
    }

    function bundleStages(bundleRoute) {
        return Array.isArray(bundleRoute?.stages)
            ? bundleRoute.stages
            : (Array.isArray(bundleRoute?.groups) ? bundleRoute.groups : []);
    }

    function bundleStageNumber(stage, fallbackIndex = 0) {
        const value = Number(stage?.sequence);
        return Number.isFinite(value) && value > 0 ? value : (fallbackIndex + 1);
    }

    function bundleStagePrimaryStop(stage) {
        const stops = Array.isArray(stage?.stops) ? stage.stops : [];
        return stops[0] || null;
    }

    function bundleStageAction(stage) {
        const stop = bundleStagePrimaryStop(stage);
        if (!stop) return 'Điểm trên tuyến';
        return stop.type === 'pickup' ? 'Tới quán' : 'Tới khách';
    }

    function bundleStageStateText(stage) {
        if (stage?.state === 'current') return 'Đang đi';
        return 'Tiếp theo';
    }

    function renderBundleRouteQueue(bundleRoute) {
        const stages = bundleStages(bundleRoute);
        if (!stages.length) {
            if (currentStopBadge) currentStopBadge.classList.add('d-none');
            return;
        }

        const currentStageIndex = Math.max(0, stages.findIndex(stage => stage?.state === 'current'));
        const currentStage = stages[currentStageIndex] || stages[0];
        const currentPointNumber = bundleStageNumber(currentStage, currentStageIndex);
        const totalDestinations = Number(bundleRoute?.total_destinations || stages.length || 0);
        const pointProgress = totalDestinations > 0 ? `${currentPointNumber}/${totalDestinations}` : `${currentPointNumber}`;
        if (currentStopBadge && currentPointNumber) {
            currentStopBadge.textContent = `Điểm ${pointProgress}`;
            currentStopBadge.classList.remove('d-none');
        } else if (currentStopBadge) {
            currentStopBadge.classList.add('d-none');
        }

        if (currentStage) {
            const action = bundleStageAction(currentStage);
            stageEl.textContent = `Điểm ${pointProgress} · ${action}`;
        }
    }

    function maneuverVoiceText(step, action) {
        const phrase = action || maneuverLabel(step);
        return step?.name ? `${phrase} vào ${spokenRoadName(step.name)}` : phrase;
    }

    function guidanceFromRoute(route) {
        const steps = Array.isArray(route?.steps) ? route.steps : [];
        if (!steps.length) return null;

        let nextIndex = -1;
        for (let i = 1; i < steps.length; i++) {
            const step = steps[i] || {};
            if (isMeaningfulGuidanceStep(step)) {
                nextIndex = i;
                break;
            }
        }

        if (nextIndex < 0) {
            return {
                current:steps[0],
                next:null,
                distance:Number((route?.display_distance_m ?? route?.distance_m ?? steps[0]?.distance_m) || 0),
                key:'continue'
            };
        }

        let distance = 0;
        for (let i = 0; i < nextIndex; i++) distance += Number(steps[i]?.distance_m || 0);
        const next = steps[nextIndex];
        const loc = Array.isArray(next?.location) && next.location.length >= 2
            ? { latitude:Number(next.location[0]), longitude:Number(next.location[1]) }
            : null;
        const key = loc
            ? `${next.type}|${next.modifier}|${loc.latitude.toFixed(5)}|${loc.longitude.toFixed(5)}`
            : `${next.type}|${next.modifier}|${next.name}|${nextIndex}`;

        return {
            current:steps[0],
            next,
            distance,
            location:loc,
            key,
            nextSegmentDistance:Number.isFinite(Number(next?.distance_m)) ? Number(next.distance_m) : Infinity,
            nextIndex
        };
    }

    function wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function playVoiceIntroCue(requestId) {
        // "Tinh-reng" ngắn trước mỗi câu dẫn đường.
        // Dùng file WAV thật để không phụ thuộc AudioContext/WebAudio.
        try {
            if (requestId !== voiceRequestId || !voiceEnabled) return;
            if (!audioUnlocked) {
                await wait(220);
                return;
            }

            navigationIntroAudio.pause();
            navigationIntroAudio.currentTime = 0;

            const ended = new Promise(resolve => {
                let done = false;
                const finish = () => {
                    if (done) return;
                    done = true;
                    navigationIntroAudio.removeEventListener('ended', finish);
                    navigationIntroAudio.removeEventListener('error', finish);
                    resolve();
                };
                navigationIntroAudio.addEventListener('ended', finish, { once:true });
                navigationIntroAudio.addEventListener('error', finish, { once:true });
                setTimeout(finish, 850);
            });

            await navigationIntroAudio.play();
            await ended;
        } catch (_) {
            // Nếu trình duyệt chưa cho phép phát âm báo, vẫn giữ một nhịp
            // trước khi Piper đọc để câu thoại không bật quá đột ngột.
            await wait(220);
        }
    }

    async function speak(text, force = false) {
        if (!voiceEnabled || ttsUnavailable || !text || !ttsUrl) return;
        const now = Date.now();
        if (!force && now - lastSpeakAt < 3500) return;
        lastSpeakAt = now;
        lastGuideText = text;
        const requestId = ++voiceRequestId;

        try {
            let objectUrl = voiceCache.get(text);
            if (!objectUrl) {
                voiceRequestController?.abort();
                voiceRequestController = new AbortController();
                const response = await fetch(ttsUrl, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'audio/*','X-CSRF-TOKEN':csrf},
                    body:JSON.stringify({text}),
                    signal:voiceRequestController.signal
                });
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    const requestError = new Error(error.message || 'Chưa tải được giọng Piper cố định.');
                    requestError.ttsUnavailable = response.status === 503;
                    throw requestError;
                }
                const blob = await response.blob();
                objectUrl = URL.createObjectURL(blob);
                voiceCache.set(text, objectUrl);
            }

            if (requestId !== voiceRequestId || !voiceEnabled) return;
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
            }

            // Intro mềm: ting-tung nhỏ -> nghỉ một nhịp -> mới đọc hướng dẫn.
            await playVoiceIntroCue(requestId);
            if (requestId !== voiceRequestId || !voiceEnabled) return;
            await wait(NAV_VOICE_INTRO_GAP_MS);
            if (requestId !== voiceRequestId || !voiceEnabled) return;

            currentAudio = new Audio(objectUrl);
            currentAudio.volume = NAV_VOICE_VOLUME;
            currentAudio.defaultPlaybackRate = NAV_VOICE_RATE;
            currentAudio.playbackRate = NAV_VOICE_RATE;
            if ('preservesPitch' in currentAudio) currentAudio.preservesPitch = true;
            if ('webkitPreservesPitch' in currentAudio) currentAudio.webkitPreservesPitch = true;
            await currentAudio.play();
        } catch (error) {
            if (error?.name === 'AbortError') return;
            if (error?.ttsUnavailable) ttsUnavailable = true;
            if (!ttsWarningShown) {
                ttsWarningShown = true;
                sourceEl.textContent = error.message || 'Chưa cài Piper TTS local';
            }
        } finally {
            if (requestId === voiceRequestId) {
                voiceRequestController = null;
            }
        }
    }

    window.addEventListener('beforeunload', () => {
        voiceRequestController?.abort();
        voiceCache.forEach(objectUrl => URL.revokeObjectURL(objectUrl));
    });

    function updateArrivalUi(arrival) {
        if (!arrival || !arrival.required) return;
        latestArrivalSnapshot = arrival;
        latestArrivalSnapshotTargetKey = routeTargetKey() || '';

        const verified = Boolean(arrival.verified);
        const eligible = Boolean(arrival.eligible);
        const isStore = arrival.event === 'arrived_store';
        const isReadyStorePickup = isStore && initialOrderStatus === 'ready_for_delivery';
        const isHandover = arrival.event === 'arrived_handover';
        const isCustomer = arrival.event === 'arrived_customer';
        const distance = Number(arrival.distance_m);
        const accuracy = Number(arrival.accuracy_m);

        if (verified) lockVoiceForVerifiedArrival(arrival);

        arrivalGuard?.classList.toggle('is-verified', verified);
        arrivalGuard?.classList.toggle('is-near', !verified && eligible);
        arrivalGuard?.classList.toggle('is-error', !verified && !eligible && accuracy > 120);

        if (arrivalGuardBadge) {
            if (isStore) {
                arrivalGuardBadge.textContent = isReadyStorePickup ? 'Quán xong' : 'Quán đang pha';
                arrivalGuardBadge.classList.toggle('is-ready', isReadyStorePickup);
            }
        }

        if (arrivalGuardTitle) {
            if (isCustomer && customerArrivalConfirmed) {
                arrivalGuardTitle.textContent = 'Đã đến nơi';
            } else if (verified) {
                arrivalGuardTitle.textContent = isStore
                    ? 'Đã tới quán'
                    : (isHandover ? 'Bạn đã tới điểm bàn giao' : 'Bạn đã tới điểm giao');
            } else {
                arrivalGuardTitle.textContent = isReadyStorePickup
                    ? 'Tới quán lấy hàng'
                    : isStore
                    ? 'Tới quán chờ hàng'
                    : (isHandover ? 'Đang tới điểm bàn giao' : 'Đang tới điểm giao');
            }
        }

        if (arrivalGuardText) {
            if (isCustomer && customerArrivalConfirmed) {
                arrivalGuardText.textContent = 'Hãy gọi khách, thu tiền nếu cần rồi xác nhận giao hàng.';
            } else if (verified) {
                arrivalGuardText.textContent = isStore
                    ? (isReadyStorePickup
                        ? 'Mở nút lấy hàng'
                        : 'Quán đang pha. Chờ quán làm xong để mở nút lấy hàng.')
                    : (isHandover
                        ? 'Đã tới đúng điểm bàn giao. Nút Đã nhận bàn giao đã được mở.'
                        : 'Đã tới đúng vị trí. Nút Đã đến nơi đã được mở.');
            } else if (Number.isFinite(accuracy) && accuracy > 120) {
                arrivalGuardText.textContent = 'GPS đang yếu. Hãy chờ tín hiệu vị trí chính xác hơn.';
            } else if (eligible) {
                arrivalGuardText.textContent = isStore
                    ? (isReadyStorePickup ? 'Quán xong. Đang kiểm tra vị trí để mở nút lấy hàng...' : 'Quán đang pha. Đang kiểm tra vị trí...')
                    : 'Bạn đã ở rất gần điểm đến. Đang kiểm tra vị trí...';
            } else if (Number.isFinite(distance)) {
                arrivalGuardText.textContent = isReadyStorePickup
                    ? `Quán xong. Còn khoảng ${Math.max(1, Math.round(distance))} m tới quán.`
                    : isStore
                    ? `Quán đang pha. Còn khoảng ${Math.max(1, Math.round(distance))} m tới quán.`
                    : `Còn khoảng ${Math.max(1, Math.round(distance))} m tới điểm đến.`;
            } else {
                arrivalGuardText.textContent = isReadyStorePickup
                    ? 'Quán xong. Tới quán để mở nút lấy hàng.'
                    : isStore
                    ? 'Quán đang pha. Bạn có thể tới quán trước.'
                    : (isHandover
                        ? 'Khi tới điểm bàn giao với shipper cũ, nút Đã nhận bàn giao sẽ tự mở.'
                        : 'Khi tới đúng vị trí khách, nút Đã đến nơi sẽ tự mở.');
            }
        }

        if (isStore && pickedUpButton) {
            const pickupEnabled = verified && assignmentAccepted;
            pickedUpButton.disabled = !pickupEnabled;
            const pickupActionText = verified ? 'Vuốt lấy hàng' : 'Tới quán để mở';
            if (pickedUpButtonText) pickedUpButtonText.textContent = pickupActionText;
            else pickedUpButton.textContent = pickupActionText;
            if (pickedUpButtonIcon) {
                pickedUpButtonIcon.className = pickupEnabled ? 'fa-solid fa-box-open me-2' : 'fa-solid fa-lock me-2';
            }
        }

        if (isHandover && handoverButton) {
            const handoverEnabled = verified && assignmentAccepted;
            handoverButton.disabled = !handoverEnabled;
            const handoverActionText = verified ? 'Vuốt để nhận bàn giao' : 'Tới điểm bàn giao để mở';
            if (handoverButtonText) handoverButtonText.textContent = handoverActionText;
            else handoverButton.textContent = handoverActionText;
            if (handoverButtonIcon) {
                handoverButtonIcon.className = handoverEnabled ? 'fa-solid fa-people-carry-box me-2' : 'fa-solid fa-lock me-2';
            }
        }

        if (isCustomer && arrivedAtCustomerButton) {
            arrivedAtCustomerButton.disabled = !verified;
            const arrivedActionText = verified ? 'Vuốt để xác nhận đã đến nơi' : 'Đến điểm giao để mở';
            if (arrivedAtCustomerButtonText) arrivedAtCustomerButtonText.textContent = arrivedActionText;
            else arrivedAtCustomerButton.textContent = arrivedActionText;
            if (arrivedAtCustomerButtonIcon) {
                arrivedAtCustomerButtonIcon.className = verified ? 'fa-solid fa-location-dot me-2' : 'fa-solid fa-lock me-2';
            }
        }
    }

    function refreshGuidance(forceIntro = false) {
        if (isGuidanceMutedByArrival()) {
            stageEl.textContent = `Đã tới ${routeTargetShortLabel()}`;
            summaryInstruction.textContent = 'Chờ xác nhận tiếp theo';
            return;
        }

        const guide = guidanceFromRoute(currentRoute);
        const targetNoun = routeTargetNoun();
        if (!guide) {
            if (currentRoute && Number.isFinite(Number(currentRoute.distance_m))) {
                const text = compactInstructionDistance(Number(currentRoute.distance_m));
                stageEl.textContent = 'Đi thẳng';
                summaryInstruction.textContent = text;
                if (voiceEnabled && forceIntro) speak(text);
            }
            return;
        }

        if (guidanceState.key !== guide.key) {
            guidanceState = { key:guide.key, warned:false, turn:false, intro:false, arrived:false };
        }

        let distanceToManeuver = guide.distance;
        const progress = currentPosition ? routeProgressForPoint(currentPosition, routeGeometry) : null;
        const progressedMeters = progress && Number.isFinite(Number(progress.meters)) ? Number(progress.meters || 0) : 0;
        const routeRemaining = Number(currentRoute?.display_distance_m ?? currentRoute?.distance_m ?? 0);
        const remainingRouteMeters = Number.isFinite(routeRemaining)
            ? Math.max(0, routeRemaining - progressedMeters)
            : Math.max(0, Number(guide.distance || 0));
        if (progress && Number.isFinite(Number(progress.meters))) {
            distanceToManeuver = Math.max(0, Number(guide.distance || 0) - Number(progress.meters || 0));
        }
        if (guide.location && currentPosition) {
            const direct = haversine(currentPosition, guide.location);
            if (Number.isFinite(direct) && direct < 250) distanceToManeuver = Math.min(distanceToManeuver, direct);
        }
        const speedKmh = effectiveTravelSpeedKmh();
        const thresholds = guidanceThresholds(guide.next, speedKmh, guide.nextSegmentDistance);

        if (!guide.next) {
            const text = compactInstructionDistance(remainingRouteMeters);
            stageEl.textContent = 'Đi thẳng';
            summaryInstruction.textContent = text;
            return;
        }

        const action = maneuverLabel(guide.next);
        const voiceAction = maneuverVoiceText(guide.next, action);
        if (guide.next.type !== 'arrive' && distanceToManeuver > thresholds.prepare) {
            stageEl.textContent = 'Đi thẳng';
            summaryInstruction.textContent = compactInstructionDistance(distanceToManeuver);
            return;
        }

        stageEl.textContent = compactGuidanceStage(guide.next, true);
        let display = compactInstructionDistance(distanceToManeuver);
        if (guide.next.type === 'arrive') display = compactInstructionDistance(distanceToManeuver);
        summaryInstruction.textContent = display;

        if (!voiceEnabled) return;

        if (guide.next.type === 'arrive') {
            if (distanceToManeuver <= thresholds.arriveNow && !guidanceState.arrived) {
                guidanceState.arrived = true;
                speak(`Bạn đã đến ${targetNoun}.`, true);
            } else if (distanceToManeuver <= thresholds.arrive && !guidanceState.warned) {
                guidanceState.warned = true;
                speak(`Còn ${distanceText(distanceToManeuver)}, bạn sắp đến ${targetNoun}.`);
            }
            return;
        }

        if (distanceToManeuver <= thresholds.turn && !guidanceState.turn) {
            guidanceState.turn = true;
            speak(`${capitalize(voiceAction)}.`, true);
            return;
        }

        if (distanceToManeuver <= thresholds.prepare && !guidanceState.warned) {
            guidanceState.warned = true;
            speak(`Còn ${distanceText(distanceToManeuver)}, chuẩn bị ${voiceAction}.`);
            return;
        }
    }

    async function sendLocation(position) {
        const now = Date.now();
        const elapsed = now - lastLocationSentAt;
        const moved = lastSentPosition ? haversine(lastSentPosition, position) : Infinity;

        // GPS gửi gần realtime để khách thấy xe mượt hơn, nhưng vẫn lọc nhiễu khi đứng yên.
        if (lastSentPosition && elapsed < 1500) return;
        if (lastSentPosition && moved < 4 && elapsed < 5000) return;
        if (Number.isFinite(position.accuracy) && position.accuracy > 250 && elapsed < 7000) return;

        lastLocationSentAt = now;
        lastSentPosition = { ...position };
        try {
            const response = await fetch(locationUrl, {
                method:'POST',
                headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf },
                body:JSON.stringify({
                    latitude:position.latitude,
                    longitude:position.longitude,
                    accuracy:position.accuracy,
                    order_id:orderId,
                    test_mode:testMode ? 1 : 0
                })
            });
            const payload = await response.json().catch(() => null);
            if (payload?.arrival) updateArrivalUi(payload.arrival);
            if (payload && payload.accepted === false && payload.message) sourceEl.textContent = payload.message;
        } catch (_) {}
    }

    async function updateRoute(force = false) {
        if (!currentPosition || routePending) return;
        if (testDriveActive && !force) {
            refreshGuidance(false);
            return;
        }
        const now = Date.now();
        const moved = lastRoutePosition ? haversine(lastRoutePosition, currentPosition) : Infinity;
        if (!force && now - lastRouteAt < 8000 && moved < 15) {
            refreshGuidance(false);
            return;
        }

        routePending = true;
        lastRouteAt = now;
        lastRoutePosition = { ...currentPosition };
        sourceEl.textContent = 'Đang tính tuyến gần nhất...';

        const url = new URL(routeUrl, window.location.origin);
        url.searchParams.set('latitude', currentPosition.latitude);
        url.searchParams.set('longitude', currentPosition.longitude);
        if (Number.isFinite(currentPosition.accuracy)) url.searchParams.set('accuracy', currentPosition.accuracy);

        try {
            const response = await fetch(url, { headers:{ 'Accept':'application/json' }, cache:'no-store' });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Không tính được tuyến');

            if (payload.redirect_url) {
                sourceEl.textContent = payload.message || 'Chuyến ghép vừa chuyển sang điểm tiếp theo...';
                window.location.href = payload.redirect_url;
                return;
            }

            const serverStatus = String(payload.status || '');
            const uiChangingStatuses = new Set(['ready_for_delivery', 'shipper_picked_up', 'delivering', 'delivered']);
            if (!statusReloading && serverStatus && serverStatus !== initialOrderStatus && uiChangingStatuses.has(serverStatus)) {
                statusReloading = true;
                sourceEl.textContent = 'Trạng thái đơn vừa thay đổi · đang cập nhật thao tác...';
                setTimeout(() => window.location.reload(), 250);
                return;
            }

            const target = payload.target;
            const route = payload.route;
            const bundleRoute = payload.bundle_route;
            const nextRoute = Object.assign({}, route, {
                target_type: target?.type || null,
                target_label: target?.label || null,
                target_latitude: Number(target?.latitude),
                target_longitude: Number(target?.longitude),
            });
            syncArrivalVoiceLock(nextRoute);
            currentRoute = nextRoute;
            if (payload.arrival) {
                updateArrivalUi(payload.arrival);
            } else if (
                latestArrivalSnapshot?.required
                && latestArrivalSnapshot?.verified
                && (!latestArrivalSnapshotTargetKey || latestArrivalSnapshotTargetKey === routeTargetKey(currentRoute))
            ) {
                lockVoiceForVerifiedArrival(latestArrivalSnapshot);
                latestArrivalSnapshotTargetKey = routeTargetKey(currentRoute);
            }
            const geometry = firstUsableGeometry(route.geometry);
            const bundleRendered = drawBundleRoute(bundleRoute, geometry);
            routeGeometry = bundleRendered
                ? firstUsableGeometry(routeGeometry, bundleRoute?.main_route?.geometry, geometry, bundleRoute?.alt_route?.geometry)
                : geometry;

            const targetLatLng = [target.latitude, target.longitude];
            const targetPopup = `<strong>${escapeHtml(target.label)}</strong><br>${escapeHtml(target.address || '')}`;
            if (glReady) {
                // MapLibre là renderer chính; Leaflet chỉ giữ fallback khi GL lỗi.
            } else if (targetMarker) {
                targetMarker.setLatLng(targetLatLng).setPopupContent(targetPopup);
            } else {
                targetMarker = L.marker(targetLatLng, { icon:targetIcon })
                    .addTo(map)
                    .bindPopup(targetPopup);
            }
            renderGlTargetMarker({latitude:target.latitude, longitude:target.longitude});

            if (!bundleRendered) {
                if (geometry.length >= 2) {
                    const nextKey = geometryRouteKey(geometry);
                    if (glReady) {
                        if (nextKey !== lastRenderedGlRouteKey && renderGlRoute(geometry)) {
                            lastRenderedGlRouteKey = nextKey;
                        }
                    } else if (routeLine && nextKey !== lastRenderedRouteKey) routeLine.setLatLngs(geometry);
                    else if (!routeLine) routeLine = L.polyline(geometry, { color:'#1677ff', weight:8, opacity:.96, lineJoin:'round', lineCap:'round' }).addTo(map);
                    if (!glReady && nextKey !== lastRenderedRouteKey) {
                        lastRenderedRouteKey = nextKey;
                    }
                } else if (!glReady && routeLine) {
                    routeLine.remove();
                    routeLine = null;
                    lastRenderedRouteKey = '';
                } else if (glReady) {
                    renderGlRoute([]);
                    lastRenderedGlRouteKey = '';
                }
            } else {
                const nextKey = geometryRouteKey(routeGeometry);
                if (glReady && nextKey !== lastRenderedGlRouteKey) {
                    if (renderGlRoute(routeGeometry)) {
                        lastRenderedGlRouteKey = nextKey;
                    }
                } else if (!glReady && nextKey !== lastRenderedRouteKey) {
                    lastRenderedRouteKey = nextKey;
                }
            }

            const displayDistance = bundleRoute?.display_distance_m ?? route.distance_m ?? 0;
            const displayDuration = bundleRoute?.display_duration_s ?? route.duration_s ?? 0;
            currentRoute.display_distance_m = Number(displayDistance || 0);
            currentRoute.display_duration_s = Number(displayDuration || 0);
            distanceEl.textContent = compactDistance(Number(displayDistance || 0));
            etaEl.textContent = minutes(Number(displayDuration || 0));
            stageEl.textContent = routeTargetActionLabel();
            const sourceLabel = bundleRoute?.main_route?.preference_label
                || route.preference_label
                || 'Tuyến ngắn nhất';
            sourceEl.textContent = bundleRoute
                ? `Chuyến ghép ${bundleStages(bundleRoute).length || 0} điểm · đang dẫn tới điểm tiếp theo`
                : route.fallback
                ? 'Tuyến tạm theo tọa độ · server đường đi chưa phản hồi'
                : `${sourceLabel} · ${route.alternatives_count || 1} phương án`;

            if (bundleRoute) {
                renderBundleRouteQueue(bundleRoute);
            } else {
                if (currentStopBadge) {
                    currentStopBadge.classList.add('d-none');
                }
            }

            refreshGuidance(false);

            if (firstFit && (routeLine || routeAltLine)) {
                if (following && currentMarker) {
                    setNavigationCamera(currentMarker.getLatLng(), false);
                } else {
                    map.fitBounds((routeLine || routeAltLine).getBounds(), { padding:[55,55] });
                    scheduleHeadingUpMap();
                }
                firstFit = false;
            } else if (following && currentMarker) {
                setNavigationCamera(currentMarker.getLatLng(), true);
            }
        } catch (error) {
            summaryInstruction.textContent = error.message || 'Không thể tải tuyến đường';
            sourceEl.textContent = 'Chưa có tuyến';
        } finally {
            routePending = false;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    function clearBundleOverlay() {
        if (routeLine) {
            routeLine.remove();
            routeLine = null;
        }
        if (routeAltLine) {
            routeAltLine.remove();
            routeAltLine = null;
        }
        bundleGroupLines.forEach(line => line.remove());
        bundleGroupLines = [];
        bundleStopMarkers.forEach(marker => marker.remove());
        bundleStopMarkers = [];
    }

    function bundleStageIcon(stage, stageIndex = 0) {
        const stop = bundleStagePrimaryStop(stage);
        const toneClass = stop?.type === 'pickup' ? 'is-pickup' : 'is-delivery';
        const number = bundleStageNumber(stage, stageIndex);
        return L.divIcon({
            className:'',
            html:`<div class="bundle-stop-marker ${toneClass} ${stage?.state || 'future'}">${number}</div>`,
            iconSize:[32, 32],
            iconAnchor:[16, 16]
        });
    }

    function drawBundleRoute(bundleRoute, fallbackGeometry = []) {
        if (!bundleRoute) {
            if (routeAltLine) {
                routeAltLine.remove();
                routeAltLine = null;
            }
            bundleGroupLines.forEach(line => line.remove());
            bundleGroupLines = [];
            bundleStopMarkers.forEach(marker => marker.remove());
            bundleStopMarkers = [];
            return false;
        }

        clearBundleOverlay();

        const stages = Array.isArray(bundleRoute.stages)
            ? bundleRoute.stages
            : (Array.isArray(bundleRoute.groups) ? bundleRoute.groups : []);
        const currentStage = stages.find(stage => stage?.state === 'current') || stages[0] || null;
        const currentGeometry = firstUsableGeometry(
            currentStage?.route?.geometry,
            bundleRoute?.main_route?.geometry,
            fallbackGeometry,
            bundleRoute?.alt_route?.geometry
        );

        routeGeometry = currentGeometry;
        if (glReady) {
            clearBundleOverlay();
            return true;
        }

        stages.forEach(stage => {
            const geometry = Array.isArray(stage?.route?.geometry) ? stage.route.geometry : [];
            if (geometry.length < 2) return;

            const isCurrent = stage.state === 'current';
            if (!isCurrent) return;
            const line = L.polyline(geometry, {
                color:'#1677ff',
                weight:8,
                opacity:.96,
                lineJoin:'round',
                lineCap:'round',
                dashArray:null
            }).addTo(map);

            routeLine = line;
        });

        if (!routeLine && currentGeometry.length >= 2) {
            routeLine = L.polyline(currentGeometry, {
                color:'#1677ff',
                weight:8,
                opacity:.96,
                lineJoin:'round',
                lineCap:'round'
            }).addTo(map);
        }

        if (!routeGeometry.length && currentGeometry.length >= 2) {
            routeGeometry = currentGeometry;
        }

        // Một marker = một điểm đến vật lý. Không vẽ 2 marker chồng nhau cho 2 đơn cùng quán.
        stages.forEach((stage, stageIndex) => {
            const stop = bundleStagePrimaryStop(stage);
            const point = stage?.point;
            if (!stop || !point || !Number.isFinite(Number(point.latitude)) || !Number.isFinite(Number(point.longitude))) return;
            const pointNumber = bundleStageNumber(stage, stageIndex);
            const orderCount = Number(stage?.order_count || 0);
            const sameBranchNote = orderCount > 1 && stop?.type === 'pickup'
                ? `<br><span class="text-success fw-semibold">${orderCount} đơn lấy tại cùng chi nhánh</span>`
                : '';
            const marker = L.marker([Number(point.latitude), Number(point.longitude)], {
                icon: bundleStageIcon(stage, stageIndex),
                zIndexOffset: stage.state === 'current' ? 700 : 600
            }).addTo(map).bindPopup(`
                <strong>${escapeHtml(`Điểm ${pointNumber}. ${stage?.label || stop.label}`)}</strong><br>
                <span class="text-secondary">${escapeHtml(stage?.address || stop.address || '')}</span>${sameBranchNote}
            `);
            bundleStopMarkers.push(marker);
        });

        return true;
    }

    function applyPosition(geo, simulated = false) {
        if (testMode && !simulated) return;

        const { latitude, longitude, accuracy } = geo.coords;
        currentPosition = { latitude, longitude, accuracy };
        updateCurrentSpeed(currentPosition, Date.now());
        if (testMode && simulated) {
            persistTestGpsPoint(currentPosition);
        }

        const snapped = snapPointToRoute(currentPosition, routeGeometry);
        const snapDistance = Number(snapped?.distance);
        const accuracyMeters = Number(accuracy);
        const snapLimit = Math.max(35, Math.min(85, (Number.isFinite(accuracyMeters) ? accuracyMeters : 20) * 1.8));
        const shouldSnapToRoute = Boolean(snapped && (testMode || simulated || !Number.isFinite(snapDistance) || snapDistance <= snapLimit));
        const renderPoint = shouldSnapToRoute ? snapped : currentPosition;
        const nextHeading = shouldSnapToRoute
            ? (headingAheadOnRoute(snapped, routeGeometry) ?? snapped?.heading ?? currentHeading ?? 0)
            : currentHeading;
        const hasRenderedPosition = Boolean(currentMarker || glCurrentMarker);
        const turnDelta = Math.abs(bearingDelta(currentHeading, nextHeading));
        const headingFactor = turnDelta > 120 ? .52 : (turnDelta > 55 ? .38 : .24);
        currentHeading = hasRenderedPosition ? smoothBearing(currentHeading, nextHeading, headingFactor) : normalizeBearing(nextHeading);
        displayedHeading = currentHeading;
        const markerIcon = glReady ? null : currentIconForHeading(currentHeading);

        if (!glReady && !currentMarker) currentMarker = L.marker([renderPoint.latitude, renderPoint.longitude], { icon:markerIcon, zIndexOffset:1000 }).addTo(map).bindPopup('Vị trí của bạn');
        else if (!glReady) {
            currentMarker.setLatLng([renderPoint.latitude, renderPoint.longitude]);
            currentMarker.setIcon(markerIcon);
        }
        renderGlCurrentMarker(renderPoint, currentHeading);

        if (shouldSnapToRoute && routeGeometry.length >= 2) {
            renderDisplayedRoute(remainingGeometryFromSnap(snapped, routeGeometry));
        } else if (!displayedRouteGeometry.length && routeGeometry.length >= 2) {
            renderDisplayedRoute(routeGeometry);
        }

        const latField = document.getElementById('deliveryLatitude');
        const lngField = document.getElementById('deliveryLongitude');
        const accuracyField = document.getElementById('deliveryAccuracy');
        const pickupLatField = document.getElementById('pickupLatitude');
        const pickupLngField = document.getElementById('pickupLongitude');
        const pickupAccuracyField = document.getElementById('pickupAccuracy');
        const arrivalLatField = document.getElementById('arrivalLatitude');
        const arrivalLngField = document.getElementById('arrivalLongitude');
        const arrivalAccuracyField = document.getElementById('arrivalAccuracy');
        const handoverLatField = document.getElementById('handoverLatitude');
        const handoverLngField = document.getElementById('handoverLongitude');
        const handoverAccuracyField = document.getElementById('handoverAccuracy');
        if (latField) latField.value = latitude;
        if (lngField) lngField.value = longitude;
        if (accuracyField) accuracyField.value = accuracy || '';
        if (pickupLatField) pickupLatField.value = latitude;
        if (pickupLngField) pickupLngField.value = longitude;
        if (pickupAccuracyField) pickupAccuracyField.value = accuracy || '';
        if (arrivalLatField) arrivalLatField.value = latitude;
        if (arrivalLngField) arrivalLngField.value = longitude;
        if (arrivalAccuracyField) arrivalAccuracyField.value = accuracy || '';
        if (handoverLatField) handoverLatField.value = latitude;
        if (handoverLngField) handoverLngField.value = longitude;
        if (handoverAccuracyField) handoverAccuracyField.value = accuracy || '';

        if (following && (glReady || !routeLine)) {
            setNavigationCamera(L.latLng(renderPoint.latitude, renderPoint.longitude), true);
        } else if (following && currentMarker) {
            setNavigationCamera(currentMarker.getLatLng(), true);
        }

        const now = performance.now();
        if (now - lastNavigationSideEffectsAt > 550) {
            lastNavigationSideEffectsAt = now;
            sendLocation(currentPosition);
            refreshGuidance(false);
            if (snapped && Number.isFinite(snapDistance) && snapDistance > Math.max(95, snapLimit * 1.7) && Date.now() - lastRouteAt > 4500) {
                updateRoute(true);
                return;
            }
            updateRoute(false);
        }
    }

    function persistTestGpsPoint(position) {
        if (!position) return;
        try {
            sessionStorage.setItem(TEST_GPS_POINT_KEY, JSON.stringify({
                latitude: Number(position.latitude),
                longitude: Number(position.longitude),
                accuracy: Number(position.accuracy || 8),
            }));
        } catch (_) {}
    }

    function loadTestGpsPoint() {
        try {
            const raw = sessionStorage.getItem(TEST_GPS_POINT_KEY);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (!Number.isFinite(Number(parsed?.latitude)) || !Number.isFinite(Number(parsed?.longitude))) {
                return null;
            }
            return {
                latitude: Number(parsed.latitude),
                longitude: Number(parsed.longitude),
                accuracy: Number(parsed.accuracy || 8),
            };
        } catch (_) {
            return null;
        }
    }

    function geoError(error) {
        summaryInstruction.textContent = error.code === 1
            ? 'Bạn cần cấp quyền vị trí để dẫn đường'
            : 'Chưa lấy được GPS. Hệ thống sẽ thử lại.';
        sourceEl.textContent = 'GPS chưa sẵn sàng';
    }

    function fakeGeoPosition(lat, lng, accuracy = 8) {
        return {
            coords: {
                latitude:Number(lat),
                longitude:Number(lng),
                accuracy:Number(accuracy)
            }
        };
    }

    function normalizeTestDriveSpeed(value) {
        const speed = Math.round(Number(value));
        if (!Number.isFinite(speed)) return TEST_DRIVE_SPEED_DEFAULT_KMH;
        return Math.min(TEST_DRIVE_SPEED_MAX_KMH, Math.max(TEST_DRIVE_SPEED_MIN_KMH, speed));
    }

    function setTestDriveSpeed(value, persist = true) {
        testDriveSpeedKmh = normalizeTestDriveSpeed(value);
        const nextValue = String(testDriveSpeedKmh);
        if (testSpeedRange && testSpeedRange.value !== nextValue) testSpeedRange.value = nextValue;
        if (testSpeedNumber && testSpeedNumber.value !== nextValue) testSpeedNumber.value = nextValue;
        if (testSpeedValue) testSpeedValue.textContent = nextValue;

        if (persist) {
            try {
                localStorage.setItem(TEST_DRIVE_SPEED_KEY, nextValue);
            } catch (_) {}
        }
    }

    function restoreTestDriveSpeed() {
        let saved = null;
        try {
            saved = localStorage.getItem(TEST_DRIVE_SPEED_KEY);
        } catch (_) {}

        setTestDriveSpeed(saved ?? TEST_DRIVE_SPEED_DEFAULT_KMH, false);
    }

    testSpeedRange?.addEventListener('input', event => {
        setTestDriveSpeed(event.target.value);
    });

    testSpeedNumber?.addEventListener('input', event => {
        setTestDriveSpeed(event.target.value);
    });

    testSpeedNumber?.addEventListener('blur', () => {
        setTestDriveSpeed(testSpeedNumber.value);
    });

    restoreTestDriveSpeed();

    function stopTestDrive() {
        if (testDriveFrameId) {
            cancelAnimationFrame(testDriveFrameId);
            testDriveFrameId = null;
        }
        testDriveActive = false;
        testDriveLastFrameAt = 0;
        testStopButton?.classList.add('d-none');
        if (testMode) {
            testDriveButton?.classList.remove('d-none');
        }
    }

    function setTestMode(enabled) {
        testMode = Boolean(enabled);
        stopTestDrive();
        mapEl.classList.toggle('is-test-gps', testMode);

        if (testGpsButton) {
            testGpsButton.classList.toggle('btn-warning', !testMode);
            testGpsButton.classList.toggle('btn-dark', testMode);
            testGpsButton.innerHTML = testMode
                ? '<i class="fa-solid fa-location-crosshairs me-1"></i> Đang test GPS'
                : '<i class="fa-solid fa-flask me-1"></i> Test GPS';
        }

        testDriveButton?.classList.toggle('d-none', !testMode);
        testStopButton?.classList.add('d-none');
        testSpeedControl?.classList.toggle('d-none', !testMode);

        if (testMode) {
            following = true;
            sourceEl.textContent = 'TEST GPS: bấm một điểm trên bản đồ để giả lập vị trí';
            try {
                sessionStorage.setItem(TEST_GPS_SESSION_KEY, '1');
            } catch (_) {}
        } else {
            sourceEl.textContent = 'Đã tắt Test GPS · quay lại GPS thật';
            try {
                sessionStorage.removeItem(TEST_GPS_SESSION_KEY);
                sessionStorage.removeItem(TEST_GPS_POINT_KEY);
            } catch (_) {}
        }
    }

    testGpsButton?.addEventListener('click', () => {
        unlockAudio();
        setTestMode(!testMode);
        closeMapTools();
    });

    map.on('click', event => {
        if (glMap) return;
        if (!testMode) return;
        following = true;
        const { lat, lng } = event.latlng;
        sourceEl.textContent = `TEST GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        const fakePoint = fakeGeoPosition(lat, lng, 8);
        persistTestGpsPoint({ latitude: lat, longitude: lng, accuracy: 8 });
        applyPosition(fakePoint, true);
    });

    function testPointObject(point) {
        return { latitude:Number(point[0]), longitude:Number(point[1]) };
    }

    function interpolateTestPoint(a, b, ratio) {
        const t = Math.max(0, Math.min(1, Number(ratio) || 0));
        return [
            Number(a[0]) + (Number(b[0]) - Number(a[0])) * t,
            Number(a[1]) + (Number(b[1]) - Number(a[1])) * t,
        ];
    }

    function advanceTestDrive(distanceMeters) {
        let remaining = Math.max(0, Number(distanceMeters) || 0);

        while (remaining > 0 && testDriveSegmentIndex < testDriveGeometry.length - 1) {
            const from = testDriveGeometry[testDriveSegmentIndex];
            const to = testDriveGeometry[testDriveSegmentIndex + 1];
            const segmentMeters = haversine(testPointObject(from), testPointObject(to));

            if (!Number.isFinite(segmentMeters) || segmentMeters < 0.25) {
                testDriveSegmentIndex += 1;
                testDriveSegmentOffsetM = 0;
                continue;
            }

            const available = Math.max(0, segmentMeters - testDriveSegmentOffsetM);
            if (remaining >= available) {
                remaining -= available;
                testDriveSegmentIndex += 1;
                testDriveSegmentOffsetM = 0;
                continue;
            }

            testDriveSegmentOffsetM += remaining;
            remaining = 0;
        }

        if (testDriveSegmentIndex >= testDriveGeometry.length - 1) {
            return testDriveGeometry[testDriveGeometry.length - 1] || null;
        }

        const from = testDriveGeometry[testDriveSegmentIndex];
        const to = testDriveGeometry[testDriveSegmentIndex + 1];
        const segmentMeters = haversine(testPointObject(from), testPointObject(to));
        const ratio = segmentMeters > 0 ? testDriveSegmentOffsetM / segmentMeters : 1;
        return interpolateTestPoint(from, to, ratio);
    }

    testDriveButton?.addEventListener('click', () => {
        unlockAudio();
        closeMapTools();
        const geometry = currentVisibleGeometry();
        if (geometry.length < 2) {
            sourceEl.textContent = 'TEST GPS: bấm vào bản đồ trước để tạo tuyến rồi thử lại';
            return;
        }

        stopTestDrive();
        testDriveActive = true;
        testDriveGeometry = geometry;

        const progress = routeProgressForPoint(currentPosition, testDriveGeometry);
        testDriveSegmentIndex = Math.min(progress?.segmentIndex ?? 0, testDriveGeometry.length - 2);
        testDriveSegmentOffsetM = Math.max(0, Number(progress?.segmentOffsetM || 0));
        testDriveButton.classList.add('d-none');
        testStopButton?.classList.remove('d-none');

        const step = (frameAt = performance.now()) => {
            if (!testMode || testDriveSegmentIndex >= testDriveGeometry.length - 1) {
                const lastPoint = testDriveGeometry[testDriveGeometry.length - 1];
                if (lastPoint) applyPosition(fakeGeoPosition(lastPoint[0], lastPoint[1], 6), true);
                stopTestDrive();
                sourceEl.textContent = 'TEST GPS: đã chạy tới cuối tuyến';
                return;
            }

            const speedKmh = testDriveSpeedKmh;
            const metersPerSecond = speedKmh / 3.6;
            const elapsedMs = testDriveLastFrameAt ? Math.min(220, frameAt - testDriveLastFrameAt) : 0;
            testDriveLastFrameAt = frameAt;
            const metersThisTick = metersPerSecond * (elapsedMs / 1000);
            const point = advanceTestDrive(metersThisTick);

            if (!point) {
                stopTestDrive();
                return;
            }

            applyPosition(fakeGeoPosition(point[0], point[1], 6), true);
            const progress = routeProgressForPoint({latitude:point[0], longitude:point[1]}, testDriveGeometry);
            const remaining = Math.max(0, Number(progress?.totalMeters || 0) - Number(progress?.meters || 0));
            sourceEl.textContent = `TEST GPS: ${Math.round(speedKmh)} km/h · còn ${compactDistance(remaining)} trên tuyến`;
            testDriveFrameId = requestAnimationFrame(step);
        };

        const speedKmh = testDriveSpeedKmh;
        sourceEl.textContent = `TEST GPS: bắt đầu mô phỏng ${Math.round(speedKmh)} km/h...`;
        testDriveLastFrameAt = 0;
        testDriveFrameId = requestAnimationFrame(step);
    });

    testStopButton?.addEventListener('click', () => {
        stopTestDrive();
        sourceEl.textContent = 'TEST GPS: đã dừng mô phỏng';
        closeMapTools();
    });

    const shouldRestoreTestGps = (() => {
        if (!isLocalHost) return false;

        try {
            return sessionStorage.getItem(TEST_GPS_SESSION_KEY) === '1'
                || new URLSearchParams(window.location.search).get('testgps') === '1';
        } catch (_) {
            return new URLSearchParams(window.location.search).get('testgps') === '1';
        }
    })();

    if (shouldRestoreTestGps) {
        setTestMode(true);
        const persistedPoint = loadTestGpsPoint();
        if (persistedPoint) {
            applyPosition(fakeGeoPosition(persistedPoint.latitude, persistedPoint.longitude, persistedPoint.accuracy), true);
        } else {
            sourceEl.textContent = 'TEST GPS: đang giữ chế độ test cho toàn bộ chuyến';
        }
    }

    if (!navigator.geolocation) {
        geoError({code:0});
    } else {
        geoWatchId = navigator.geolocation.watchPosition(
            geo => applyPosition(geo, false),
            geoError,
            {
                enableHighAccuracy:true,
                maximumAge:0,
                timeout:12000
            }
        );
    }

    setInterval(() => updateRoute(false), 10000);
})();
</script>
@endpush
