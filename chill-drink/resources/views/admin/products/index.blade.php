@extends('layouts.admin')

@section('page-title', 'Sản phẩm')
@section('search-placeholder', 'Tìm đồ uống, mã sản phẩm...')

@section('content')
<section class="admin-products-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div class="admin-products-filters d-flex flex-wrap gap-2">
        <button class="btn btn-primary">Tất cả sản phẩm</button>
        <button class="btn btn-outline-primary">Trà sữa</button>
        <button class="btn btn-outline-primary">Cà phê</button>
        <button class="btn btn-outline-primary">Nước ép</button>
        <button class="btn btn-outline-primary">Bộ lọc</button>
        <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mới</a>
    </div>
    <div class="admin-stock-summary text-lg-end">
        <p class="admin-kicker mb-1">Tình trạng kho</p>
        <div class="d-flex align-items-center gap-3">
            <div><span class="admin-value text-primary">{{ $products->total() ?? $products->count() }}</span><small class="d-block text-secondary fw-bold">Tổng</small></div>
            <div style="width:1px;height:38px;background:var(--a-border);"></div>
            <div><span class="admin-value" style="color:var(--a-danger);">0</span><small class="d-block text-secondary fw-bold">Sắp hết</small></div>
        </div>
    </div>
</section>

<section class="admin-card admin-table-card admin-products-card">
    <div class="table-responsive">
        <table class="table admin-table admin-products-table align-middle">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="admin-thumb d-flex align-items-center justify-content-center overflow-hidden">
                                <x-product-image
                                    :src="$product->image_url"
                                    :sku="$product->sku ?? null"
                                    :name="$product->name"
                                    :alt="$product->name"
                                    :category="$product->category?->name"
                                    class="w-100 h-100"
                                    style="object-fit: contain;"
                                    :width="180"
                                />
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $product->name }}</div>
                            @if(!empty($product->sku))
                                <small class="text-secondary font-monospace">{{ $product->sku }}</small>
                            @endif
                        </td>
                        <td><span class="badge badge-soft-primary">{{ $product->category->name ?? 'Chưa phân loại' }}</span></td>
                        <td class="fw-bold">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</td>
                        <td>
                            @if($product->status)
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-primary"><span style="width:8px;height:8px;border-radius:50%;background:var(--a-primary);"></span> Đang bán</span>
                            @else
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-secondary"><span style="width:8px;height:8px;border-radius:50%;background:var(--a-muted);"></span> Đã ẩn</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product->id) }}" class="admin-action text-decoration-none" title="Xem"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="admin-action text-decoration-none" title="Sửa"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">
                                @csrf
                                @method('DELETE')
                                <button class="admin-action" title="Xóa" style="color:var(--a-danger);"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có sản phẩm</div>
                            <div>Danh sách sản phẩm sẽ hiển thị tại đây khi có dữ liệu.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination-footer">
        <p class="text-secondary mb-0">Đang hiển thị {{ $products->count() }} sản phẩm</p>
        {{ $products->onEachSide(1)->links() }}
    </div>
</section>
@endsection
