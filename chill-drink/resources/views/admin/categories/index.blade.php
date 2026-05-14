@extends('layouts.admin')

@section('page-title', 'Quản lý danh mục')

@section('content')
<div class="admin-card card border-0">
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
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th>Sản phẩm</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $category->name }}</div>
                            <small class="text-secondary">{{ $category->slug }}</small>
                        </td>
                        <td class="text-secondary">{{ $category->description ?? 'Không có mô tả' }}</td>
                        <td><span class="badge text-bg-info">{{ $category->products_count }}</span></td>
                        <td>
                            <span class="badge {{ $category->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $category->status ? 'Hiển thị' : 'Ẩn' }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ $category->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-5">Chưa có danh mục.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $categories->links() }}
    </div>
</div>
@endsection
