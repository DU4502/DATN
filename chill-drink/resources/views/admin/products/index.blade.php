@extends('layouts.admin')

@section('page-title', 'Sản phẩm')
@section('search-placeholder', 'Tìm đồ uống, mã sản phẩm...')

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary">Tất cả sản phẩm</button>
        <button class="btn btn-outline-primary">Trà sữa</button>
        <button class="btn btn-outline-primary">Cà phê</button>
        <button class="btn btn-outline-primary">Nước ép</button>
        <button class="btn btn-outline-primary">Bộ lọc</button>
    </div>
    <div class="text-lg-end">
        <p class="admin-kicker mb-1">Tình trạng kho</p>
        <div class="d-flex align-items-center gap-3">
            <div><span class="admin-value text-primary">{{ $products->total() ?? $products->count() }}</span><small class="d-block text-secondary fw-bold">Tổng</small></div>
            <div style="width:1px;height:38px;background:var(--admin-border);"></div>
            <div><span class="admin-value" style="color:var(--admin-danger);">0</span><small class="d-block text-secondary fw-bold">Sắp hết</small></div>
        </div>
    </div>
</section>

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
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
                                <img src="{{ $product->image ?: 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=75' }}" alt="{{ $product->name }}" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $product->name }}</div>
                            <small class="text-secondary">Mã: {{ $product->slug }}</small>
                        </td>
                        <td><span class="badge badge-soft-primary">{{ $product->category->name ?? 'Chưa phân loại' }}</span></td>
                        <td class="fw-bold">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</td>
                        <td>
                            @if($product->status)
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-primary"><span style="width:8px;height:8px;border-radius:50%;background:var(--admin-primary);"></span> Đang bán</span>
                            @else
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-secondary"><span style="width:8px;height:8px;border-radius:50%;background:var(--admin-muted);"></span> Đã ẩn</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="admin-action" title="Sửa">✎</button>
                            <button class="admin-action" title="Xóa" style="color:var(--admin-danger);">⌫</button>
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $products->count() }} sản phẩm</p>
        {{ $products->links() }}
    </div>
</section>
@endsection
