@extends('layouts.client')

@section('title', 'Địa Chỉ Của Bạn - Chill Drink')

@section('content')
<style>
    .address-modal .modal-dialog {
        max-width: 620px;
        width: min(620px, calc(100vw - 2rem));
        margin: 1.5rem auto;
    }

    .address-modal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
        max-height: min(840px, calc(100dvh - 2rem));
    }

    .address-modal .modal-header {
        padding: 1.1rem 1.25rem 0.4rem 1.25rem;
    }

    .address-modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .address-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 0.75rem 1.25rem;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .address-modal .modal-footer {
        padding: 0.75rem 1.25rem 1rem 1.25rem;
        border-top: 1px solid #eef2f1 !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .address-modal-field {
        border-radius: 4px;
        border: 1px solid #d8d8d8;
        background: #ffffff;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        color: #212529;
    }

    .address-modal-field:focus {
        border-color: #0d9373;
        box-shadow: 0 0 0 0.18rem rgba(13, 147, 115, 0.12);
    }

    .btn-address-primary {
        border-radius: 4px;
        background: #0d9373 !important;
        border-color: #0d9373 !important;
        color: #ffffff !important;
        min-width: 150px;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 8px 20px rgba(13, 147, 115, 0.18);
        transition: all 0.2s ease;
    }

    .btn-address-primary:hover,
    .btn-address-primary:focus,
    .btn-address-primary:active {
        background: #0b7a60 !important;
        border-color: #0b7a60 !important;
        color: #ffffff !important;
        box-shadow: 0 10px 24px rgba(11, 122, 96, 0.24) !important;
    }

    .address-item {
        transition: background-color 0.15s ease;
    }

    @media (max-width: 575.98px) {
        .address-modal .modal-dialog {
            width: calc(100vw - 1rem);
            margin: 0.5rem auto;
        }

        .address-modal .modal-content {
            max-height: calc(100dvh - 1rem);
            border-radius: 16px;
        }

        .address-modal .modal-header,
        .address-modal .modal-body,
        .address-modal .modal-footer {
            padding: 0.75rem 1rem;
        }

        .address-modal .modal-footer {
            flex-direction: column-reverse;
            gap: 0.5rem;
            align-items: stretch;
        }

        .address-modal .modal-footer .btn,
        .address-modal .modal-footer .btn-address-primary {
            width: 100%;
            margin-left: 0;
            text-align: center;
        }
    }
</style>

<section class="profile-page py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Tài khoản</p>
                <h1 class="h2 fw-bold mb-1">Địa chỉ của bạn</h1>
                <p class="text-secondary mb-0">Quản lý danh sách địa chỉ nhận hàng để đặt món nhanh chóng.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createAddressModal">
                    <i class="bi bi-plus-lg me-1"></i>Thêm địa chỉ mới
                </button>
            </div>
        </div>

        @include('profile.partials.account-navigation', ['activeTab' => 'addresses'])

        @if(session('success'))
            <div class="alert alert-success rounded-4 border-0 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-warning rounded-4 border-0 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger rounded-4 border-0 mb-4">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($addresses->isEmpty())
            <div class="card border-0 rounded-4 shadow-sm text-center py-5 px-3">
                <div class="mx-auto mb-3 text-primary" style="font-size: 3.5rem;">
                    <i class="bi bi-geo-alt"></i>
                </div>
                <h2 class="h4 fw-bold mb-2">Chưa có địa chỉ nào</h2>
                <p class="text-secondary mb-4">Lưu địa chỉ giao hàng để không phải nhập lại mỗi lần đặt trà sữa & đồ uống!</p>
                <div>
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createAddressModal">
                        <i class="bi bi-plus-lg me-1"></i>Thêm địa chỉ ngay
                    </button>
                </div>
            </div>
        @else
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Danh sách địa chỉ nhận hàng</h5>
                        <p class="text-secondary small mb-0">Địa chỉ mặc định sẽ tự động được chọn khi bạn đặt hàng.</p>
                    </div>
                </div>

                @foreach($addresses as $address)
                    <div class="address-item py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div class="flex-grow-1 pe-md-3">
                                <div class="d-flex align-items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="fw-bold text-dark fs-6">{{ $address->receiver_name }}</span>
                                    <span class="text-muted small">|</span>
                                    <span class="text-secondary fw-semibold small">{{ $address->phone }}</span>
                                    @if($address->is_default)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-1 px-2 py-0.5 small fw-semibold">
                                            <i class="bi bi-check2 me-1"></i>Mặc định
                                        </span>
                                    @endif
                                </div>
                                <div class="text-dark mb-1 small fw-medium">
                                    <i class="bi bi-geo-alt text-primary me-1"></i>{{ $address->detail }}
                                </div>
                                @if($address->ward || $address->district || $address->province)
                                    <div class="text-secondary small" style="padding-left: 1.25rem;">
                                        {{ collect([$address->ward, $address->district, $address->province])->filter()->implode(', ') }}
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-shrink-0 align-self-md-center">
                                @if($address->latitude && $address->longitude)
                                    <a href="https://www.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}" target="_blank" rel="noopener" class="btn btn-sm btn-light text-secondary border rounded-pill px-3 py-1.5 fw-medium d-inline-flex align-items-center gap-1" title="Xem trên Google Maps">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> Bản đồ
                                    </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-medium" data-bs-toggle="modal" data-bs-target="#editAddressModal-{{ $address->id }}">
                                    <i class="bi bi-pencil me-1"></i>Cập nhật
                                </button>
                                @if(! $address->is_default)
                                    <form method="POST" action="{{ route('profile.addresses.set-default', $address) }}" class="d-inline m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-medium">
                                            Thiết lập mặc định
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('profile.addresses.destroy', $address) }}" class="d-inline m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1.5" title="Xóa địa chỉ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>


                    @php
                        $houseNumber = '';
                        $street = $address->detail ?? '';
                        if (preg_match('/^(?:số\s*)?(\d+[a-zA-Z]?(?:\/\d+[a-zA-Z]?)*)\s*[, \-\/]+\s*(.+)$/iu', (string) $address->detail, $m)) {
                            $houseNumber = trim($m[1]);
                            $street = trim($m[2]);
                        }
                        $areaText = collect([$address->ward, $address->district, $address->province])->filter()->implode(', ');
                        if (empty($areaText) && !empty($address->province)) {
                            $areaText = $address->province;
                        }
                        $currentLabel = $address->label ?: 'Nhà Riêng';
                        $isOffice = in_array(mb_strtolower(trim($currentLabel)), ['văn phòng', 'công ty', 'van phong', 'cong ty'], true);
                    @endphp

                    <!-- Modal Sửa Địa Chỉ (Chuẩn UI Checkout - Kích thước nhỏ gọn) -->
                    <div class="modal fade address-modal address-form-modal" id="editAddressModal-{{ $address->id }}" tabindex="-1" aria-labelledby="editAddressTitle-{{ $address->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h2 class="address-modal-title mb-0" id="editAddressTitle-{{ $address->id }}">Chỉnh sửa địa chỉ</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                </div>
                                <form method="POST" action="{{ route('profile.addresses.update', $address) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body pt-0">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small text-secondary mb-1" for="edit_name_{{ $address->id }}">Họ và tên</label>
                                                <input id="edit_name_{{ $address->id }}" type="text" name="receiver_name" class="form-control address-modal-field" value="{{ old('receiver_name', $address->receiver_name) }}" required placeholder="Họ và tên">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-secondary mb-1" for="edit_phone_{{ $address->id }}">Số điện thoại</label>
                                                <input id="edit_phone_{{ $address->id }}" type="tel" name="phone" class="form-control address-modal-field" value="{{ old('phone', $address->phone) }}" required placeholder="Số điện thoại" autocomplete="tel" minlength="10" inputmode="numeric">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small text-secondary mb-1" for="edit_area_{{ $address->id }}">Tỉnh/Thành phố, Quận/Huyện</label>
                                                <input id="edit_area_{{ $address->id }}" type="text" name="area" class="form-control address-modal-field" value="{{ old('area', $areaText) }}" placeholder="Tỉnh/Thành phố, Quận/Huyện">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-secondary mb-1" for="edit_house_number_{{ $address->id }}">Số nhà</label>
                                                <input id="edit_house_number_{{ $address->id }}" type="text" name="house_number" class="form-control address-modal-field" value="{{ old('house_number', $houseNumber) }}" placeholder="Ví dụ: 12/3">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label small text-secondary mb-1" for="edit_street_{{ $address->id }}">Đường, thôn, hẻm...</label>
                                                <input id="edit_street_{{ $address->id }}" type="text" name="street" class="form-control address-modal-field" value="{{ old('street', $street) }}" placeholder="Tên đường, thôn/xóm...">
                                            </div>
                                            <div class="col-12">
                                                @include('admin.partials.location-picker', [
                                                    'pickerId' => 'address-edit-picker-' . $address->id,
                                                    'label' => 'Vị trí đã xác nhận',
                                                    'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                                                    'latValue' => old('latitude', $address->latitude),
                                                    'lngValue' => old('longitude', $address->longitude),
                                                    'defaultLat' => $address->latitude ?: 16.047079,
                                                    'defaultLng' => $address->longitude ?: 108.206230,
                                                    'defaultZoom' => $address->latitude ? 16 : 5,
                                                    'showTerritoryLabels' => true,
                                                    'autoFillHouseTarget' => '#edit_house_number_' . $address->id,
                                                    'autoFillStreetTarget' => '#edit_street_' . $address->id,
                                                    'autoFillAreaTarget' => '#edit_area_' . $address->id,
                                                    'showSearch' => true,
                                                    'searchPlaceholder' => 'Tìm số nhà, tên đường, phường/xã...',
                                                ])
                                            </div>
                                            <input type="hidden" name="label" value="{{ $address->label ?: 'Địa chỉ' }}">
                                            <div class="col-12">
                                                <label class="form-check text-secondary small">
                                                    <input id="edit_default_{{ $address->id }}" class="form-check-input" type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default) ? 'checked' : '' }}>
                                                    <span class="form-check-label">Đặt làm địa chỉ mặc định</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-link text-dark text-decoration-none px-0" data-bs-dismiss="modal">Trở lại</button>
                                        <button type="submit" class="btn btn-address-primary">Lưu địa chỉ</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- Modal Thêm Địa Chỉ Mới (Chuẩn UI Checkout - Kích thước nhỏ gọn) -->
<div class="modal fade address-modal address-form-modal" id="createAddressModal" tabindex="-1" aria-labelledby="createAddressTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="address-modal-title mb-0" id="createAddressTitle">Địa chỉ mới</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form method="POST" action="{{ route('profile.addresses.store') }}">
                @csrf
                <div class="modal-body pt-0">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary mb-1" for="newAddressName">Họ và tên</label>
                            <input id="newAddressName" type="text" name="receiver_name" class="form-control address-modal-field" placeholder="Họ và tên" value="{{ old('receiver_name', auth()->user()->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary mb-1" for="newAddressPhone">Số điện thoại</label>
                            <input id="newAddressPhone" type="tel" name="phone" class="form-control address-modal-field" placeholder="Số điện thoại" value="{{ old('phone', auth()->user()->phone) }}" required autocomplete="tel" minlength="10" inputmode="numeric">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-secondary mb-1" for="newAddressArea">Tỉnh/Thành phố, Quận/Huyện</label>
                            <input id="newAddressArea" type="text" name="area" class="form-control address-modal-field" placeholder="Tỉnh/Thành phố, Quận/Huyện" value="{{ old('area', auth()->user()->area) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary mb-1" for="newAddressHouseNumber">Số nhà</label>
                            <input id="newAddressHouseNumber" type="text" name="house_number" class="form-control address-modal-field" placeholder="Ví dụ: 12/3" value="{{ old('house_number') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small text-secondary mb-1" for="newAddressStreet">Đường, thôn, hẻm...</label>
                            <input id="newAddressStreet" type="text" name="street" class="form-control address-modal-field" placeholder="Tên đường, thôn/xóm..." value="{{ old('street') }}">
                        </div>
                        <div class="col-12">
                            @include('admin.partials.location-picker', [
                                'pickerId' => 'address-create-picker',
                                'label' => 'Vị trí đã xác nhận',
                                'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                                'latValue' => old('latitude', auth()->user()->latitude),
                                'lngValue' => old('longitude', auth()->user()->longitude),
                                'defaultLat' => auth()->user()->latitude ?: 16.047079,
                                'defaultLng' => auth()->user()->longitude ?: 108.206230,
                                'defaultZoom' => auth()->user()->latitude ? 16 : 5,
                                'showTerritoryLabels' => true,
                                'autoFillHouseTarget' => '#newAddressHouseNumber',
                                'autoFillStreetTarget' => '#newAddressStreet',
                                'autoFillAreaTarget' => '#newAddressArea',
                                'showSearch' => true,
                                'searchPlaceholder' => 'Tìm số nhà, tên đường, phường/xã...',
                            ])
                        </div>
                        <input type="hidden" name="label" value="Địa chỉ">
                        <div class="col-12">
                            <label class="form-check text-secondary small">
                                <input id="newAddressDefault" class="form-check-input" type="checkbox" name="is_default" value="1" {{ old('is_default', $addresses->isEmpty()) ? 'checked' : '' }}>
                                <span class="form-check-label">Đặt làm địa chỉ mặc định</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-link text-dark text-decoration-none px-0" data-bs-dismiss="modal">Trở lại</button>
                    <button type="submit" class="btn btn-address-primary">Lưu địa chỉ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.partials.location-picker-script')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Trigger map invalidate size on modal open
        document.addEventListener('shown.bs.modal', function (event) {
            if (window.ChillDrinkLocationPicker) {
                window.ChillDrinkLocationPicker.refresh(event.target);
            }
        });
    });
</script>
@endsection
