@extends('layouts.admin')

@section('page-title', 'Voucher')
@section('search-placeholder', 'Tìm mã voucher...')

@section('content')
<section class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Quản lý voucher</h2>
        <p class="text-secondary mb-0">Giao diện quản lý mã giảm giá và chương trình khuyến mãi.</p>
    </div>
    <button type="button" class="btn btn-primary align-self-start align-self-xl-auto">
        <i class="bi bi-plus-circle me-1"></i>Thêm voucher
    </button>
</section>

<section class="row g-3 align-items-end mb-4">
    <div class="col-lg-6">
        <label class="admin-kicker mb-2 d-block">Tìm kiếm</label>
        <div class="admin-search w-100">
            <span class="admin-search-icon"><i class="bi bi-search"></i></span>
            <input type="search" placeholder="Nhập mã voucher hoặc tên chương trình">
        </div>
    </div>
    <div class="col-md-3">
        <label class="admin-kicker mb-2 d-block">Trạng thái</label>
        <select class="admin-filter">
            <option>Tất cả trạng thái</option>
            <option>Đang hoạt động</option>
            <option>Đã lên lịch</option>
            <option>Đã hết hạn</option>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="button" class="btn btn-outline-primary flex-grow-1">
            <i class="bi bi-funnel me-1"></i>Lọc
        </button>
        <button type="button" class="btn btn-outline-primary">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-ticket-perforated"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Voucher đang có</p>
                <p class="admin-value mb-0">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-clock-history"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Đã lên lịch</p>
                <p class="admin-value mb-0">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-check2-circle"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Đã sử dụng</p>
                <p class="admin-value mb-0">0</p>
            </div>
        </div>
    </div>
</section>

<section class="admin-card p-4">
    <div class="admin-empty-state py-5">
        <span class="admin-icon-dot mx-auto mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-ticket-perforated"></i>
        </span>
        <h3 class="h4 fw-bold text-dark mb-2">Chưa có voucher</h3>
        <p class="mb-3">Hiện hệ thống chưa có dữ liệu voucher thật, nên trang này không hiển thị mã giảm giá mẫu.</p>
        <button type="button" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Tạo voucher đầu tiên
        </button>
    </div>
</section>
@endsection
