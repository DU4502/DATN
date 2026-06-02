@extends('layouts.admin')

@section('page-title', 'Danh mục')
@section('search-placeholder', 'Tìm danh mục đồ uống...')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Danh mục đồ uống</h2>
        <p class="text-secondary mb-0">Nhóm sản phẩm để khách hàng tìm kiếm nhanh hơn.</p>
    </div>
    <button type="button" class="btn btn-primary align-self-start align-self-lg-auto">
        <i class="bi bi-plus-circle me-1"></i>Thêm danh mục
    </button>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-grid"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng danh mục</p>
                <p class="admin-value mb-0">{{ $categories->total() ?? $categories->count() }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-cup-hot"></i></span>
                <span class="badge badge-soft-muted">Theo trang</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Đang hiển thị</p>
                <p class="admin-value mb-0">{{ $categories->count() }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-box-seam"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng sản phẩm trong nhóm</p>
                <p class="admin-value mb-0">{{ $categories->sum('products_count') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th class="text-center">Sản phẩm</th>
                    <th class="text-center">Trạng thái</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    @php($categoryImage = $uiCategoryImages[$category->name] ?? $uiDefaultImage)
                    <tr>
                        <td>
                            <img src="{{ $categoryImage }}" alt="{{ $category->name }}" class="admin-thumb" style="object-fit: cover; padding: 0;">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="admin-icon-dot" style="width: 42px; height: 42px;"><i class="bi bi-grid"></i></span>
                                <span>
                                    <span class="fw-bold d-block">{{ $category->name }}</span>
                                    <small class="text-secondary">{{ $category->slug }}</small>
                                </span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $category->description ?? 'Chưa có mô tả' }}</td>
                        <td class="text-center"><span class="badge badge-soft-primary">{{ $category->products_count }}</span></td>
                        <td class="text-center">
                            <span class="badge {{ $category->status ? 'badge-soft-primary' : 'badge-soft-muted' }}">
                                {{ $category->status ? 'Hiển thị' : 'Chưa bật' }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ optional($category->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có danh mục</div>
                            <div>Các nhóm đồ uống sẽ hiển thị tại đây khi có dữ liệu.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $categories->count() }} danh mục</p>
        {{ $categories->links('pagination::bootstrap-5') }}
    </div>
</section>
@endsection
