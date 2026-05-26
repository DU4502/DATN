@extends('layouts.admin')

@section('page-title', 'Sửa khách hàng')
@section('search-placeholder', 'Tìm tên hoặc email...')

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Cập nhật tài khoản</p>
        <h2 class="h2 fw-bold mb-1">{{ $user->name }}</h2>
        <p class="text-secondary mb-0">Chỉnh thông tin liên hệ, trạng thái khóa/mở khóa dùng menu thao tác.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Quay lại</a>
</section>

<section class="admin-card p-4">
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-md-6">
                <label for="name" class="form-label">Họ tên</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="area" class="form-label">Khu vực</label>
                <input id="area" type="text" name="area" value="{{ old('area', $user->area) }}" class="form-control @error('area') is-invalid @enderror">
                @error('area')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="address" class="form-label">Địa chỉ</label>
                <input id="address" type="text" name="address" value="{{ old('address', $user->address) }}" class="form-control @error('address') is-invalid @enderror">
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-primary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
    </form>
</section>
@endsection
