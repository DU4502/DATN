@extends('layouts.admin')

@section('page-title', 'Quản lý danh mục')

@section('content')
<div class="container-fluid px-0">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="admin-card card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Danh mục đồ uống</h2>
                <p class="text-secondary mb-0">Nhóm sản phẩm để khách hàng tìm kiếm nhanh hơn.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Thêm danh mục</a>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">ID</th> 
                        <th>Tên danh mục</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-end" style="min-width: 120px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <span class="text-secondary fw-semibold">#{{ $category->id }}</span> {{-- Hiển thị ID danh mục --}}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $category->name }}</div>
                                <small class="text-secondary d-block">{{ $category->slug }}</small>
                            </td>
                            {{-- Đã xóa cột sản phẩm ($category->products_count) ở đây --}}
                            <td>
                                <span class="badge {{ $category->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $category->status ? 'Hiển thị' : 'Ẩn' }}
                                </span>
                            </td>
                            <td class="text-secondary">{{ $category->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    {{-- Nút Sửa --}}
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                        Sửa
                                    </a>
                                    
                                    {{-- Form Xóa --}}
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">Chưa có danh mục nào được tạo.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white pt-3">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection