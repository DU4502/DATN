@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

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
        <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror js-user-role-select" data-branch-roles="{{ implode(',', $branchRoleIds) }}" @disabled($user->id === auth()->id()) required>
            @if(! array_key_exists((int) old('role_id', $user->role_id), $roleOptions))
                <option value="" selected disabled>Chọn vai trò thay thế</option>
            @endif
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

        @if($branches->isNotEmpty() || ($user->branch && ! $user->branch->status))
            <div id="staff_branch" class="mt-3">
                <label for="branch_id" class="form-label fw-semibold">Chi nhánh làm việc</label>
                <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror js-shipper-branch-select">
                    <option value="">Chọn chi nhánh</option>
                    @if($user->branch && ! $user->branch->status && ! $branches->contains('id', $user->branch->id))
                        <option value="{{ $user->branch->id }}" selected>{{ $user->branch->name }} (Ngừng hoạt động - hiện tại)</option>
                    @endif
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <small class="text-secondary d-block mt-2">Nhân viên và Shipper chỉ thao tác trong phạm vi chi nhánh này.</small>
                @error('branch_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Hủy</a>
            <button type="submit" class="btn btn-primary" @disabled($user->id === auth()->id())>Lưu thay đổi</button>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.querySelector('.js-user-role-select');
    const branchWrapper = document.getElementById('staff_branch');
    if (!roleSelect || !branchWrapper) return;

    const branchSelect = branchWrapper.querySelector('.js-shipper-branch-select');
    const syncBranchField = function () {
        const needsBranch = roleSelect.dataset.branchRoles.split(',').includes(roleSelect.value);
        branchWrapper.hidden = !needsBranch;
        branchSelect.required = needsBranch;
        branchSelect.disabled = !needsBranch;
    };

    roleSelect.addEventListener('change', syncBranchField);
    syncBranchField();
});
</script>
@endpush
