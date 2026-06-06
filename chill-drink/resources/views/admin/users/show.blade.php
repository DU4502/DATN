@extends('layouts.admin')

@section('page-title', 'Chi tiết người dùng')
@section('hide-topbar-search', true)

@section('content')
@php
    $avatar = $user->avatar;
    $avatarIsImage = $avatar && ! str_starts_with($avatar, 'preset-');
    $avatarUrl = $avatarIsImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatar) : null;
    $roleName = $roleOptions[(int) $user->role_id] ?? 'Không rõ';
@endphp

<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Hồ sơ người dùng</p>
        <h2 class="h2 fw-bold mb-1">{{ $user->name }}</h2>
        <p class="text-secondary mb-0">Xem thông tin tài khoản, vai trò và lịch sử hoạt động cơ bản.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Sửa</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Quay lại</a>
    </div>
</section>

<section class="row g-4">
    <div class="col-lg-4">
        <div class="admin-card p-4 text-center h-100">
            <span class="admin-avatar mx-auto mb-3" style="width:86px;height:86px;font-size:2rem;" aria-label="Avatar {{ $user->name }}">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                @else
                    {{ mb_substr($user->name, 0, 1) }}
                @endif
            </span>
            <h3 class="h4 fw-bold mb-1">{{ $user->name }}</h3>
            <p class="text-secondary mb-3">{{ $user->email }}</p>
            <div class="d-flex justify-content-center flex-wrap gap-2">
                <span class="badge {{ $user->isAdmin() ? 'badge-soft-primary' : 'badge-soft-muted' }}">{{ $roleName }}</span>
                <span class="badge {{ $user->is_active ? 'badge-soft-primary' : 'badge-soft-danger' }}">
                    {{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}
                </span>
            </div>

            @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="mt-4" onsubmit="return confirm('{{ $user->is_active ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' }}');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $user->is_active ? 'btn-outline-primary' : 'btn-primary' }} w-100">
                        <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }} me-1"></i>
                        {{ $user->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <div class="admin-card p-4 h-100">
            <h3 class="h4 fw-bold mb-4">Thông tin liên hệ</h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="admin-kicker mb-1">Email</p>
                    <div class="fw-bold">{{ $user->email }}</div>
                </div>
                <div class="col-md-6">
                    <p class="admin-kicker mb-1">Số điện thoại</p>
                    <div class="fw-bold">{{ $user->phone ?: 'Chưa cập nhật' }}</div>
                </div>
                <div class="col-md-6">
                    <p class="admin-kicker mb-1">Khu vực</p>
                    <div class="fw-bold">{{ $user->area ?: 'Chưa cập nhật' }}</div>
                </div>
                <div class="col-md-6">
                    <p class="admin-kicker mb-1">Ngày tạo</p>
                    <div class="fw-bold">{{ optional($user->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                </div>
                <div class="col-12">
                    <p class="admin-kicker mb-1">Địa chỉ</p>
                    <div class="fw-bold">{{ $user->address ?: 'Chưa cập nhật' }}</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="admin-card p-3 shadow-none">
                        <p class="admin-kicker mb-1">Đơn hàng</p>
                        <div class="admin-value mb-0">{{ $user->orders_count ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="admin-card p-3 shadow-none">
                        <p class="admin-kicker mb-1">Đánh giá</p>
                        <div class="admin-value mb-0">{{ $user->reviews_count ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
