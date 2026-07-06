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
@endphp

<div class="location-picker" data-location-picker="{{ $pickerId }}" data-default-lat="{{ $defaultLat }}" data-default-lng="{{ $defaultLng }}" data-default-zoom="{{ $defaultZoom }}" data-initial-lat="{{ $latValue }}" data-initial-lng="{{ $lngValue }}">
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
        <button type="button" class="btn btn-sm btn-outline-primary location-picker-btn" data-location-use-geolocation>
            <i class="bi bi-crosshair me-1"></i>Lấy vị trí hiện tại
        </button>
    </div>

    <div class="location-picker-map" data-location-map></div>

    <div class="location-picker-status form-text mt-2" data-location-status>{{ $hint }}</div>

    <input type="hidden" name="{{ $latName }}" value="{{ $latValue }}" data-location-lat>
    <input type="hidden" name="{{ $lngName }}" value="{{ $lngValue }}" data-location-lng>
</div>
