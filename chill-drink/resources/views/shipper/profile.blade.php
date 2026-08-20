@extends('layouts.shipper')

@section('title', 'Thông tin cá nhân')
@section('mobile-title', 'Cá nhân')
@section('mobile-subtitle', 'Tài khoản & phương tiện')

@section('content')

<div class="profile-page">

    <div class="ship-quick-grid mb-3">
        <a href="{{ route('shipper.history') }}" class="ship-quick-link"><i class="fa-solid fa-clock-rotate-left"></i><span>Lịch sử</span></a>
        <a href="{{ route('shipper.notifications.index') }}" class="ship-quick-link"><i class="fa-solid fa-bell"></i><span>Thông báo</span></a>
        <a href="{{ route('shipper.orders') }}" class="ship-quick-link"><i class="fa-solid fa-box"></i><span>Đơn hàng</span></a>
        <a href="{{ route('shipper.map') }}" class="ship-quick-link"><i class="fa-solid fa-location-arrow"></i><span>Dẫn đường</span></a>
    </div>

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div class="page-header mb-4">

        <div>
            <div class="d-flex align-items-center gap-2 mb-2">

                <div class="header-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <h2 class="fw-bold mb-0">
                        Thông tin cá nhân
                    </h2>

                    <small class="text-muted">
                        Quản lý thông tin tài khoản và phương tiện giao hàng
                    </small>
                </div>

            </div>
        </div>

        <a href="{{ route('shipper.dashboard') }}"
           class="btn btn-light border back-btn">

            <i class="fa-solid fa-arrow-left me-2"></i>
            Dashboard

        </a>

    </div>

    <div class="row g-4">

        {{-- =====================================================
            LEFT - PROFILE
        ====================================================== --}}
        <div class="col-xl-4 col-lg-5">

            {{-- PROFILE CARD --}}
            <div class="profile-main-card">

                <div class="profile-cover"></div>

                <div class="profile-content text-center">

                    {{-- Avatar --}}
                    <div class="avatar-container">

                        @if(!empty($shipperInfo->avatar))

                            <img src="{{ asset('storage/' . $shipperInfo->avatar) }}"
                                 class="shipper-avatar"
                                 alt="Ảnh đại diện">

                        @else

                            <div class="avatar-default">

                                <i class="fa-solid fa-user"></i>

                            </div>

                        @endif

                        {{-- Online dot --}}
                        @if(($shipperInfo->status ?? '') === 'online')

                            <span class="avatar-status online"></span>

                        @elseif(($shipperInfo->status ?? '') === 'busy')

                            <span class="avatar-status busy"></span>

                        @else

                            <span class="avatar-status offline"></span>

                        @endif

                    </div>


                    {{-- Name --}}
                    <h3 class="fw-bold mt-3 mb-1">

                        {{ $shipperUser->name ?? 'Shipper' }}

                    </h3>


                    {{-- Code --}}
                    <div class="shipper-code">

                        <i class="fa-solid fa-id-badge me-1"></i>

                        {{ $shipperInfo->code ?? '---' }}

                    </div>


                    {{-- Status --}}
                    <div class="mt-3">

                        @if(($shipperInfo->status ?? '') === 'online')

                            <span class="status-pill online-pill">
                                <span></span>
                                Đang online
                            </span>

                        @elseif(($shipperInfo->status ?? '') === 'busy')

                            <span class="status-pill busy-pill">
                                <span></span>
                                Đang bận
                            </span>

                        @else

                            <span class="status-pill offline-pill">
                                <span></span>
                                Offline
                            </span>

                        @endif

                    </div>

                </div>


                {{-- Quick information --}}
                <div class="profile-quick-info">

                    <div class="quick-item">

                        <div class="quick-icon phone-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>

                        <div>
                            <small>Số điện thoại</small>

                            <strong>
                                {{ $shipperInfo->phone ?? 'Chưa cập nhật' }}
                            </strong>
                        </div>

                    </div>


                    <div class="quick-item">

                        <div class="quick-icon vehicle-icon">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>

                        <div>
                            <small>Phương tiện</small>

                            <strong>

                                @if(($shipperInfo->vehicle_type ?? '') === 'bike')
                                    Xe máy
                                @elseif(($shipperInfo->vehicle_type ?? '') === 'car')
                                    Ô tô
                                @elseif(($shipperInfo->vehicle_type ?? '') === 'truck')
                                    Xe tải
                                @else
                                    Chưa cập nhật
                                @endif

                            </strong>

                        </div>

                    </div>


                    <div class="quick-item">

                        <div class="quick-icon plate-icon">
                            <i class="fa-solid fa-id-card"></i>
                        </div>

                        <div>

                            <small>Biển số xe</small>

                            <strong>
                                {{ $shipperInfo->license_plate ?? 'Chưa cập nhật' }}
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                SYSTEM INFORMATION
            ================================================== --}}
            <div class="info-card mt-4">

                <div class="info-card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div>
                        <h5 class="fw-bold mb-0">
                            Thông tin hệ thống
                        </h5>

                        <small class="text-muted">
                            Thông tin tài khoản
                        </small>
                    </div>

                </div>


                <div class="system-list">

                    {{-- ID Shipper --}}
                    <div class="system-row">

                        <div class="system-label">

                            <i class="fa-solid fa-hashtag"></i>

                            ID Shipper

                        </div>

                        <strong>
                            {{ $shipperInfo->id ?? '---' }}
                        </strong>

                    </div>


                    {{-- Tên --}}
                    <div class="system-row">

                        <div class="system-label">

                            <i class="fa-solid fa-user"></i>

                            Tên tài khoản

                        </div>

                        <strong>
                            {{ $shipperUser->name ?? '---' }}
                        </strong>

                    </div>


                    {{-- Ngày tạo --}}
                    <div class="system-row">

                        <div class="system-label">

                            <i class="fa-solid fa-calendar-plus"></i>

                            Ngày tạo

                        </div>

                        <strong>

                            @if($shipperInfo?->created_at)

                                {{ $shipperInfo->created_at->format('d/m/Y') }}

                            @else

                                ---

                            @endif

                        </strong>

                    </div>


                    {{-- Cập nhật --}}
                    <div class="system-row">

                        <div class="system-label">

                            <i class="fa-solid fa-clock-rotate-left"></i>

                            Cập nhật

                        </div>

                        <strong>

                            @if($shipperInfo?->updated_at)

                                {{ $shipperInfo->updated_at->format('d/m/Y H:i') }}

                            @else

                                ---

                            @endif

                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
            RIGHT - FORM
        ====================================================== --}}
        <div class="col-xl-8 col-lg-7">

            <div class="form-card">

                {{-- Form Header --}}
                <div class="form-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>

                    <div>

                        <h5 class="fw-bold mb-1">
                            Chỉnh sửa thông tin
                        </h5>

                        <small class="text-muted">
                            Cập nhật thông tin cá nhân và phương tiện
                        </small>

                    </div>

                </div>


                {{-- Form --}}
                <form action="{{ route('shipper.profile.update') }}"
                      method="POST"
                      enctype="multipart/form-data">

                    @csrf
                    @method('PUT')


                    <div class="form-body">

                        <div class="row g-4">

                            {{-- =================================================
                                MÃ SHIPPER
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-id-badge"></i>

                                    Mã shipper

                                </label>

                                <div class="input-wrapper">

                                    <i class="fa-solid fa-barcode input-icon"></i>

                                    <input type="text"
                                           class="form-control custom-input readonly-input"
                                           value="{{ $shipperInfo->code ?? '---' }}"
                                           readonly>

                                    <span class="readonly-badge">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                </div>

                            </div>


                            {{-- =================================================
                                HỌ TÊN
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-user"></i>

                                    Họ và tên

                                </label>

                                <div class="input-wrapper">

                                    <i class="fa-solid fa-user input-icon"></i>

                                    <input type="text"
                                           name="name"
                                           class="form-control custom-input"
                                           value="{{ old('name', $shipperUser->name ?? '') }}"
                                           placeholder="Nhập họ và tên"
                                           required>

                                </div>

                            </div>


                            {{-- =================================================
                                PHONE
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-phone"></i>

                                    Số điện thoại

                                </label>

                                <div class="input-wrapper">

                                    <i class="fa-solid fa-phone input-icon"></i>

                                    <input type="text"
                                           name="phone"
                                           class="form-control custom-input"
                                           value="{{ old('phone', $shipperInfo->phone ?? '') }}"
                                           placeholder="Nhập số điện thoại">

                                </div>

                            </div>


                            {{-- =================================================
                                VEHICLE
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-motorcycle"></i>

                                    Loại xe

                                </label>

                                <div class="input-wrapper">

                                    <i class="fa-solid fa-motorcycle input-icon"></i>

                                    <select name="vehicle_type"
                                            class="form-select custom-input">

                                        <option value="">
                                            -- Chọn loại xe --
                                        </option>

                                        <option value="bike"
                                            {{ old('vehicle_type', $shipperInfo->vehicle_type ?? '') === 'bike' ? 'selected' : '' }}>
                                            🏍️ Xe máy
                                        </option>

                                        <option value="car"
                                            {{ old('vehicle_type', $shipperInfo->vehicle_type ?? '') === 'car' ? 'selected' : '' }}>
                                            🚗 Ô tô
                                        </option>

                                        <option value="truck"
                                            {{ old('vehicle_type', $shipperInfo->vehicle_type ?? '') === 'truck' ? 'selected' : '' }}>
                                            🚚 Xe tải
                                        </option>

                                    </select>

                                </div>

                            </div>


                            {{-- =================================================
                                LICENSE PLATE
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-car"></i>

                                    Biển số xe

                                </label>

                                <div class="input-wrapper">

                                    <i class="fa-solid fa-id-card input-icon"></i>

                                    <input type="text"
                                           name="license_plate"
                                           class="form-control custom-input"
                                           value="{{ old('license_plate', $shipperInfo->license_plate ?? '') }}"
                                           placeholder="Ví dụ: 59A1-12345">

                                </div>

                            </div>


                            {{-- =================================================
                                AVATAR
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-image"></i>

                                    Ảnh đại diện

                                </label>

                                <input type="file"
                                       name="avatar"
                                       class="form-control file-input"
                                       accept=".jpg,.jpeg,.png">

                                <div class="form-help">

                                    <i class="fa-solid fa-circle-info me-1"></i>

                                    JPG, JPEG, PNG - tối đa 2MB

                                </div>

                            </div>


                            {{-- =================================================
                                STATUS
                            ================================================== --}}
                            <div class="col-md-6">

                                <label class="form-label-custom">

                                    <i class="fa-solid fa-circle"></i>

                                    Trạng thái

                                </label>

                                <div class="status-input">

                                    @if(($shipperInfo->status ?? '') === 'online')

                                        <span class="status-dot online"></span>

                                        <span>Đang online</span>

                                    @elseif(($shipperInfo->status ?? '') === 'busy')

                                        <span class="status-dot busy"></span>

                                        <span>Đang bận</span>

                                    @else

                                        <span class="status-dot offline"></span>

                                        <span>Offline</span>

                                    @endif

                                    <span class="ms-auto">
                                        <i class="fa-solid fa-lock text-muted"></i>
                                    </span>

                                </div>

                            </div>


                            {{-- =================================================
                                LOCATION
                            ================================================== --}}
                            <div class="col-12">

                                <div class="location-card">

                                    <div class="location-header">

                                        <div class="location-icon">

                                            <i class="fa-solid fa-location-dot"></i>

                                        </div>

                                        <div>

                                            <h6 class="fw-bold mb-0">
                                                Vị trí hiện tại
                                            </h6>

                                            <small class="text-muted">
                                                Vị trí GPS của shipper
                                            </small>

                                        </div>

                                    </div>


                                    <div class="row g-3 mt-1">

                                        {{-- Latitude --}}
                                        <div class="col-md-6">

                                            <label class="small fw-semibold text-muted mb-2">
                                                Vĩ độ (Latitude)
                                            </label>

                                            <div class="gps-value">

                                                <i class="fa-solid fa-location-arrow"></i>

                                                {{ $shipperInfo->current_latitude ?? 'Chưa có dữ liệu' }}

                                            </div>

                                        </div>


                                        {{-- Longitude --}}
                                        <div class="col-md-6">

                                            <label class="small fw-semibold text-muted mb-2">
                                                Kinh độ (Longitude)
                                            </label>

                                            <div class="gps-value">

                                                <i class="fa-solid fa-location-arrow"></i>

                                                {{ $shipperInfo->current_longitude ?? 'Chưa có dữ liệu' }}

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                        FORM FOOTER
                    ================================================== --}}
                    <div class="form-footer">

                        <a href="{{ route('shipper.dashboard') }}"
                           class="btn btn-light border px-4">

                            <i class="fa-solid fa-arrow-left me-2"></i>

                            Quay lại

                        </a>


                        <button type="submit"
                                class="btn btn-primary save-btn px-4">

                            <i class="fa-solid fa-floppy-disk me-2"></i>

                            Lưu thay đổi

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
    CSS
============================================================= --}}
<style>

    /* =========================
       PAGE
    ========================== */

    .profile-page {
        max-width: 1500px;
        margin: auto;
    }


    /* =========================
       HEADER
    ========================== */

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0d6efd, #4f8dfd);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 8px 20px rgba(13, 110, 253, .2);
    }

    .back-btn {
        border-radius: 10px;
        padding: 10px 16px;
    }

    /* =========================
       PROFILE CARD
    ========================== */

    .profile-main-card,
    .info-card,
    .form-card {
        background: white;
        border-radius: 18px;
        border: 1px solid #edf0f4;
        box-shadow: 0 8px 30px rgba(0,0,0,.05);
        overflow: hidden;
    }

    .profile-cover {
        height: 105px;
        background: linear-gradient(
            135deg,
            #0d6efd,
            #6ea8fe
        );
        position: relative;
    }

    .profile-cover::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
        right: -50px;
        top: -90px;
    }

    .profile-content {
        padding: 0 25px 25px;
        margin-top: -55px;
        position: relative;
    }


    /* =========================
       AVATAR
    ========================== */

    .avatar-container {
        position: relative;
        display: inline-block;
    }

    .shipper-avatar,
    .avatar-default {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid white;
        box-shadow: 0 8px 25px rgba(0,0,0,.15);
    }

    .avatar-default {
        background: #e9f2ff;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 45px;
    }

    .avatar-status {
        position: absolute;
        right: 7px;
        bottom: 10px;
        width: 19px;
        height: 19px;
        border-radius: 50%;
        border: 3px solid white;
    }

    .avatar-status.online {
        background: #198754;
    }

    .avatar-status.busy {
        background: #ffc107;
    }

    .avatar-status.offline {
        background: #6c757d;
    }

    .shipper-code {
        color: #6c757d;
        font-size: 14px;
    }


    /* =========================
       STATUS
    ========================== */

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }

    .status-pill span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }

    .online-pill {
        background: #d1e7dd;
        color: #198754;
    }

    .busy-pill {
        background: #fff3cd;
        color: #856404;
    }

    .offline-pill {
        background: #e9ecef;
        color: #6c757d;
    }


    /* =========================
       QUICK INFO
    ========================== */

    .profile-quick-info {
        border-top: 1px solid #edf0f4;
        padding: 18px 22px;
    }

    .quick-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
    }

    .quick-item + .quick-item {
        border-top: 1px solid #f1f3f5;
    }

    .quick-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .phone-icon {
        background: #e8f7ef;
        color: #198754;
    }

    .vehicle-icon {
        background: #fff4d6;
        color: #d99b00;
    }

    .plate-icon {
        background: #e9f2ff;
        color: #0d6efd;
    }

    .quick-item small {
        display: block;
        color: #8a94a6;
        font-size: 12px;
        margin-bottom: 2px;
    }

    .quick-item strong {
        display: block;
        font-size: 14px;
    }


    /* =========================
       SYSTEM INFO
    ========================== */

    .info-card-header,
    .form-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 22px;
        border-bottom: 1px solid #edf0f4;
    }

    .section-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #e9f2ff;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .system-list {
        padding: 5px 22px;
    }

    .system-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid #f1f3f5;
        gap: 15px;
    }

    .system-row:last-child {
        border-bottom: none;
    }

    .system-label {
        color: #6c757d;
        font-size: 14px;
    }

    .system-label i {
        width: 22px;
        color: #0d6efd;
    }

    .system-row strong {
        text-align: right;
        font-size: 14px;
    }


    /* =========================
       FORM
    ========================== */

    .form-header {
        padding: 22px 25px;
    }

    .form-body {
        padding: 25px;
    }

    .form-label-custom {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #343a40;
    }

    .form-label-custom i {
        color: #0d6efd;
        width: 18px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa3af;
        z-index: 2;
        pointer-events: none;
    }

    .custom-input {
        min-height: 48px;
        border-radius: 11px;
        padding-left: 42px !important;
        border: 1px solid #dee2e6;
        transition: .2s ease;
    }

    .custom-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 4px rgba(13,110,253,.08);
    }

    .readonly-input {
        background: #f7f8fa !important;
        color: #6c757d;
    }

    .readonly-badge {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .file-input {
        min-height: 48px;
        border-radius: 11px;
        padding: 12px;
    }

    .form-help {
        margin-top: 7px;
        color: #8a94a6;
        font-size: 12px;
    }


    /* =========================
       STATUS INPUT
    ========================== */

    .status-input {
        min-height: 48px;
        background: #f7f8fa;
        border: 1px solid #dee2e6;
        border-radius: 11px;
        padding: 0 15px;
        display: flex;
        align-items: center;
        gap: 9px;
        color: #495057;
        font-weight: 500;
    }

    .status-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
    }

    .status-dot.online {
        background: #198754;
    }

    .status-dot.busy {
        background: #ffc107;
    }

    .status-dot.offline {
        background: #6c757d;
    }


    /* =========================
       LOCATION
    ========================== */

    .location-card {
        background: #f8faff;
        border: 1px solid #dfeaff;
        border-radius: 14px;
        padding: 20px;
    }

    .location-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .location-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: #ffe8e8;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gps-value {
        min-height: 45px;
        display: flex;
        align-items: center;
        gap: 9px;
        background: white;
        border: 1px solid #e3e8ef;
        border-radius: 10px;
        padding: 10px 13px;
        color: #495057;
        font-size: 14px;
    }

    .gps-value i {
        color: #dc3545;
    }


    /* =========================
       FOOTER
    ========================== */

    .form-footer {
        border-top: 1px solid #edf0f4;
        padding: 20px 25px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-footer .btn {
        min-height: 44px;
        border-radius: 10px;
    }

    .save-btn {
        box-shadow: 0 5px 15px rgba(13,110,253,.2);
    }


    /* =========================
       MOBILE
    ========================== */

    @media (max-width: 991px) {

        .profile-page {
            padding-bottom: 20px;
        }

        .page-header {
            align-items: flex-start;
        }

    }


    @media (max-width: 768px) {

        .profile-page {
            padding: 5px;
        }

        .page-header {
            display: block;
        }

        .page-header .back-btn {
            display: none;
        }

        .header-icon {
            width: 42px;
            height: 42px;
        }

        .page-header h2 {
            font-size: 21px;
        }

        .profile-cover {
            height: 85px;
        }

        .profile-content {
            margin-top: -48px;
            padding: 0 18px 20px;
        }

        .shipper-avatar,
        .avatar-default {
            width: 105px;
            height: 105px;
        }

        .avatar-default {
            font-size: 38px;
        }

        .profile-quick-info {
            padding: 15px 18px;
        }

        .info-card-header,
        .form-header {
            padding: 18px;
        }

        .system-list {
            padding: 5px 18px;
        }

        .form-body {
            padding: 18px 18px 118px;
        }

        .form-footer {
            padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
            position: sticky;
            bottom: 74px;
            z-index: 8;
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(14px);
            box-shadow: 0 -10px 24px rgba(18,52,42,.08);
        }

        .form-footer .btn {
            flex: 1 1 0;
            min-width: 0;
            white-space: nowrap;
        }

        .location-card {
            padding: 15px;
        }

        .system-row {
            align-items: flex-start;
        }

        .system-row strong {
            max-width: 55%;
            word-break: break-word;
        }

    }


    @media (max-width: 480px) {

        .profile-page {
            padding: 0;
        }

        .profile-main-card,
        .info-card,
        .form-card {
            border-radius: 14px;
        }

        .page-header h2 {
            font-size: 19px;
        }

        .page-header small {
            font-size: 12px;
        }

        .quick-item {
            padding: 8px 0;
        }

        .quick-icon {
            width: 36px;
            height: 36px;
        }

        .form-label-custom {
            font-size: 13px;
        }

        .custom-input,
        .file-input,
        .status-input {
            min-height: 45px;
        }

        .location-header {
            align-items: flex-start;
        }

        .gps-value {
            font-size: 12px;
            word-break: break-all;
        }

        .form-body {
            padding-bottom: 128px;
        }

    }

</style>

@endsection
