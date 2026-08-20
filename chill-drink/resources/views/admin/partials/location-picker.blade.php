@php
    $pickerId = $pickerId ?? 'location-picker-'.uniqid();
    $label = $label ?? 'Vị trí trên bản đồ';
    $hint = $hint ?? 'Nhấn vào bản đồ để đặt pin, hoặc kéo pin để chỉnh vị trí.';
    $latName = $latName ?? 'latitude';
    $lngName = $lngName ?? 'longitude';
    $latValue = old($latName, $latValue ?? null);
    $lngValue = old($lngName, $lngValue ?? null);
    $defaultLat = $defaultLat ?? 16.047079;
    $defaultLng = $defaultLng ?? 108.206230;
    $defaultZoom = $defaultZoom ?? 5;
    $autoFillHouseTarget = $autoFillHouseTarget ?? null;
    $autoFillAreaTarget = $autoFillAreaTarget ?? null;
    $autoFillStreetTarget = $autoFillStreetTarget ?? null;
    $showSearch = $showSearch ?? false;
    $searchValue = $searchValue ?? '';
    $searchPlaceholder = $searchPlaceholder ?? 'Tìm số nhà, tên đường, phường/xã...';
@endphp

<div class="location-picker" data-location-picker="{{ $pickerId }}" data-default-lat="{{ $defaultLat }}" data-default-lng="{{ $defaultLng }}" data-default-zoom="{{ $defaultZoom }}" data-initial-lat="{{ $latValue }}" data-initial-lng="{{ $lngValue }}" data-address-target="{{ $addressTarget ?? '' }}" @if($autoFillHouseTarget) data-auto-fill-house-target="{{ $autoFillHouseTarget }}" @endif @if($autoFillAreaTarget) data-auto-fill-area-target="{{ $autoFillAreaTarget }}" @endif @if($autoFillStreetTarget) data-auto-fill-street-target="{{ $autoFillStreetTarget }}" @endif @if($showSearch) data-location-search-enabled="1" @endif>
    <div class="location-picker-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
            <div class="location-picker-label">{{ $label }}</div>
            <div class="location-picker-preview text-secondary" data-location-preview>
                @if(is_numeric($latValue) && is_numeric($lngValue))
                    Tọa độ: {{ number_format((float) $latValue, 6, '.', '') }}, {{ number_format((float) $lngValue, 6, '.', '') }}
                @else
                    Chưa chọn vị trí
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary location-picker-btn" style="border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.35rem 0.8rem;" data-location-use-geolocation>
                <i class="bi bi-crosshair me-1"></i>Lấy vị trí hiện tại
            </button>
            <button type="button" class="btn btn-sm btn-outline-success location-picker-btn" style="border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.35rem 0.8rem;" data-location-get-address>
                <i class="bi bi-geo-alt me-1"></i>Lấy địa chỉ
            </button>
        </div>
    </div>

    @if($showSearch)
        <div class="location-picker-search mb-2">
            <input
                type="search"
                class="form-control form-control-sm"
                value="{{ $searchValue }}"
                placeholder="{{ $searchPlaceholder }}"
                autocomplete="off"
                data-location-search-input
            >
            <i class="bi bi-search location-picker-search-icon"></i>
            <div class="location-picker-suggestions d-none" data-location-search-suggestions></div>
        </div>
    @endif

    <div class="location-picker-map" data-location-map></div>

    <div class="location-picker-status form-text mt-2" data-location-status>{{ $hint }}</div>

    <input type="hidden" name="{{ $latName }}" value="{{ $latValue }}" data-location-lat>
    <input type="hidden" name="{{ $lngName }}" value="{{ $lngValue }}" data-location-lng>
</div>
