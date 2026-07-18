@php
    $pickerId = $pickerId ?? 'branch-map-link-'.uniqid();
    $label = $label ?? 'Link Google Maps';
    $hint = $hint ?? 'Dán link Google Maps có chứa tọa độ rồi bấm lấy tọa độ.';
    $mapLinkName = $mapLinkName ?? 'map_link';
    $addressTarget = $addressTarget ?? null;
    $mapLinkValue = old($mapLinkName, $mapLinkValue ?? '');
    $latName = $latName ?? 'latitude';
    $lngName = $lngName ?? 'longitude';
    $latValue = old($latName, $latValue ?? null);
    $lngValue = old($lngName, $lngValue ?? null);
    $errorBag = $errorBag ?? null;
    $mapLinkError = $errorBag ? $errors->getBag($errorBag)->first($mapLinkName) : $errors->first($mapLinkName);
@endphp

<div class="branch-map-link-picker border rounded-3 p-3 bg-light" data-branch-map-link-picker="{{ $pickerId }}" @if($addressTarget) data-address-target="{{ $addressTarget }}" @endif>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
            <div class="fw-bold text-dark small">{{ $label }}</div>
            <div class="text-secondary small" data-branch-map-link-preview>
                @if(is_numeric($latValue) && is_numeric($lngValue))
                    Tọa độ: {{ number_format((float) $latValue, 6, '.', '') }}, {{ number_format((float) $lngValue, 6, '.', '') }}
                @else
                    Chưa có tọa độ
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" data-branch-map-link-reverse>
                <i class="bi bi-geo-alt me-1"></i>Lấy địa chỉ
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-branch-map-link-apply>
                <i class="bi bi-geo-alt me-1"></i>Lấy tọa độ
            </button>
        </div>
    </div>

    <div class="input-group">
        <input
            type="url"
            class="form-control"
            name="{{ $mapLinkName }}"
            value="{{ $mapLinkValue }}"
            placeholder="Dán link Google Maps ở đây"
            data-branch-map-link-input
        >
        <button type="button" class="btn btn-outline-secondary" data-branch-map-link-apply>
            Phân tích link
        </button>
    </div>

    <div class="form-text mt-2">{{ $hint }}</div>

    @if($mapLinkError)
        <div class="invalid-feedback d-block mt-1">{{ $mapLinkError }}</div>
    @endif

    <input type="hidden" name="{{ $latName }}" value="{{ $latValue }}" data-branch-map-link-lat>
    <input type="hidden" name="{{ $lngName }}" value="{{ $lngValue }}" data-branch-map-link-lng>
</div>
