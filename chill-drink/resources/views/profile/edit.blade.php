@extends('layouts.client')

@section('title', 'Tài Khoản')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="mb-4">
            <p class="text-primary fw-semibold mb-1">Tài khoản</p>
            <h1 class="h2 fw-bold mb-0">Thông tin cá nhân</h1>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Cập nhật hồ sơ</h2>
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="name" class="form-label">Họ tên</label>
                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>

                            @if (session('status') === 'profile-updated')
                                <span class="text-success ms-3">Đã lưu.</span>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Đổi mật khẩu</h2>
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="update_password_current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="update_password_password" class="form-label">Mật khẩu mới</label>
                                <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="update_password_password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                @error('password_confirmation', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>

                            @if (session('status') === 'password-updated')
                                <span class="text-success ms-3">Đã cập nhật.</span>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-danger shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-danger mb-2">Xóa tài khoản</h2>
                        <p class="text-secondary">Sau khi xóa, toàn bộ dữ liệu tài khoản sẽ bị xóa vĩnh viễn.</p>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="row g-3 align-items-end">
                            @csrf
                            @method('DELETE')
                            <div class="col-md-6">
                                <label for="delete_password" class="form-label">Nhập mật khẩu để xác nhận</label>
                                <input id="delete_password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror">
                                @error('password', 'userDeletion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-danger">Xóa tài khoản</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
