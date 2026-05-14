@extends('layouts.admin')

@section('page-title', 'Quản lý người dùng')

@section('content')
<div class="admin-card card border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-1">Người dùng</h2>
        <p class="text-secondary mb-0">Danh sách tài khoản khách hàng và quản trị viên.</p>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Người dùng</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Điểm</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-brand-mark text-white" style="width: 42px; height: 42px;">{{ mb_substr($user->name, 0, 1) }}</div>
                                <div class="fw-bold">{{ $user->name }}</div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->role === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                {{ $user->role === 'admin' ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td>{{ $user->points ?? 0 }}</td>
                        <td class="text-secondary">{{ $user->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">Chưa có người dùng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $users->links() }}
    </div>
</div>
@endsection
