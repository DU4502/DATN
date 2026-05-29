@extends('layouts.admin')

@section('page-title', 'Chi tiết khách hàng')
@section('search-placeholder', 'Tìm tên hoặc email...')

@section('content')
@php
    $avatar = $user->avatar;
    $avatarIsImage = $avatar && ! str_starts_with($avatar, 'preset-');
    $avatarUrl = $avatarIsImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatar) : null;
@endphp

<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Hồ sơ khách hàng</p>
        <h2 class="h2 fw-bold mb-1">{{ $user->name }}</h2>
        <p class="text-secondary mb-0">Xem nhanh thông tin tài khoản và trạng thái đăng nhập.</p>
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
            <span class="badge {{ $user->is_active ? 'badge-soft-primary' : 'badge-soft-danger' }}">
                {{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}
            </span>

            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="mt-4" onsubmit="return confirm('{{ $user->is_active ? 'Bạn chắc chắn muốn khóa tài khoản này? Người dùng sẽ không đăng nhập được nữa.' : 'Bạn chắc chắn muốn mở khóa tài khoản này?' }}');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn {{ $user->is_active ? 'btn-outline-primary' : 'btn-primary' }} w-100">
                    <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }} me-1"></i>
                    {{ $user->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                </button>
            </form>
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
        </div>
    </div>
</section>
@endsection
