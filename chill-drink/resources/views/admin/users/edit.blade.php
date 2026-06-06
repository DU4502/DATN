@extends('layouts.admin')

@section('page-title', 'Thay đổi vai trò')
@section('hide-topbar-search', true)

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Quản lý vai trò</p>
        <h2 class="h2 fw-bold mb-1">Thay đổi vai trò</h2>
        <p class="text-secondary mb-0">Chỉ thay đổi quyền truy cập của tài khoản, không chỉnh sửa thông tin cá nhân.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Quay lại</a>
</section>

<section class="admin-card p-4" style="max-width: 560px;">
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <p class="admin-kicker mb-1">Người dùng</p>
        <h3 class="h4 fw-bold mb-4">{{ $user->name }}</h3>

        <label for="role_id" class="form-label fw-semibold">Vai trò mới</label>
        <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" @disabled($user->id === auth()->id()) required>
            @foreach($roleOptions as $roleId => $roleName)
                <option value="{{ $roleId }}" @selected((string) old('role_id', $user->role_id) === (string) $roleId)>{{ $roleName }}</option>
            @endforeach
        </select>

        @if($user->id === auth()->id())
            <input type="hidden" name="role_id" value="{{ $user->role_id }}">
            <small class="text-secondary d-block mt-2">Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.</small>
        @endif

        @error('role_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Hủy</a>
            <button type="submit" class="btn btn-primary" @disabled($user->id === auth()->id())>Lưu thay đổi</button>
        </div>
    </form>
</section>
@endsection
