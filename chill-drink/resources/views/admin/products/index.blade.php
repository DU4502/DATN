@extends('layouts.admin')

@section('page-title', 'Sản phẩm')

@section('search-placeholder', 'Tìm đồ uống, mã sản phẩm...')
@section('topbar-search-action', route('admin.products.index'))


@section('content')
@php
    $filterParams = collect($filters ?? [])->filter(fn ($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))->all();
    $currentCategory = (string) ($filters['category'] ?? '');
    $currentStatus = (string) ($filters['status'] ?? '');
    $currentStock = (string) ($filters['stock'] ?? '');
    $currentSort = (string) ($filters['sort'] ?? 'latest');
    $hasAdvancedFilters = $currentStatus !== '' || $currentStock !== '' || $currentSort !== 'latest';
    $returnParams = request()->only(['q', 'category', 'status', 'stock', 'sort', 'page']);
@endphp

<section class="admin-sticky-tools d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div class="d-flex flex-column gap-2 flex-grow-1">

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('admin.products.index') }}" class="btn {{ empty($filterParams) ? 'btn-primary' : 'btn-outline-primary' }}">Tất cả sản phẩm</a>
            <button
                class="btn {{ $hasAdvancedFilters ? 'btn-primary' : 'btn-outline-primary' }}"
                type="button"
                data-admin-filter-toggle
                aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
                aria-controls="productFilterPanel"
            
                <i class="bi bi-sliders me-1"></i>Bộ lọc
                @if($activeFiltersCount > 0)
                    <span class="badge text-bg-light text-primary ms-1">{{ $activeFiltersCount }}</span>
                @endif
            </button>
            <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mới</a> 
         </div>
        <div class="admin-category-scroller">
            @foreach($categories as $category)
                @php
                    $categoryParams = array_filter(array_merge(request()->except(['page', 'category']), ['category' => $category->id]), fn ($value) => $value !== null && $value !== '');
                @endphp
                <a href="{{ route('admin.products.index', $categoryParams) }}" class="btn {{ $currentCategory === (string) $category->id ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

    </div>
    <div class="text-lg-end">
        <p class="admin-kicker mb-1">Tình trạng kho</p>
        <div class="d-flex align-items-center gap-3">
            <div><span class="admin-value text-primary">{{ $totalProducts }}</span><small class="d-block text-secondary fw-bold">Tổng</small></div>
            <div style="width:1px;height:38px;background:var(--admin-border);"></div>
            <div><span class="admin-value" style="color:var(--admin-danger);">{{ $lowStockProducts }}</span><small class="d-block text-secondary fw-bold">Sắp hết</small></div>
        </div>
    </div>
</section>



<section class="admin-filter-panel {{ $hasAdvancedFilters ? '' : 'd-none' }}" id="productFilterPanel" data-admin-filter-panel>
    <form method="GET" action="{{ route('admin.products.index') }}" class="admin-card p-4 mb-4">
        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="admin-kicker mb-2 d-block" for="category">Danh mục</label>
                <select id="category" name="category" class="admin-filter">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($currentCategory === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="admin-kicker mb-2 d-block" for="status">Trạng thái</label>
                <select id="status" name="status" class="admin-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected($currentStatus === 'active')>Đang bán</option>
                    <option value="hidden" @selected($currentStatus === 'hidden')>Đã ẩn</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="admin-kicker mb-2 d-block" for="stock">Tồn kho</label>
                <select id="stock" name="stock" class="admin-filter">
                    <option value="">Tất cả</option>
                    <option value="low" @selected($currentStock === 'low')>Sắp hết</option>
                    <option value="out" @selected($currentStock === 'out')>Hết hàng</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="admin-kicker mb-2 d-block" for="sort">Sắp xếp</label>
                <select id="sort" name="sort" class="admin-filter">
                    <option value="latest" @selected($currentSort === 'latest')>Mới nhất</option>
                    <option value="name" @selected($currentSort === 'name')>Tên A-Z</option>
                    <option value="price_asc" @selected($currentSort === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected($currentSort === 'price_desc')>Giá giảm dần</option>
                    <option value="stock_asc" @selected($currentSort === 'stock_asc')>Tồn kho thấp</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary" aria-label="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>
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
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-primary"><span style="width:8px;height:8px;border-radius:50%;background:var(--admin-primary);"></span> Đang bán</span>
                            @else
                                <span class="d-inline-flex align-items-center gap-2 fw-bold text-secondary"><span style="width:8px;height:8px;border-radius:50%;background:var(--admin-muted);"></span> Đã ẩn</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', array_merge(['product' => $product->id], $returnParams)) }}" class="admin-action text-decoration-none" title="Xem"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.products.edit', array_merge(['product' => $product->id], $returnParams)) }}" class="admin-action text-decoration-none" title="Sửa"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.products.destroy', array_merge(['product' => $product->id], $returnParams)) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">
                                @csrf
                                @method('DELETE')
                                <button class="admin-action" title="Xóa" style="color:var(--admin-danger);"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">{{ empty($filterParams) ? 'Chưa có sản phẩm' : 'Không tìm thấy sản phẩm phù hợp' }}</div>
                            <div>{{ empty($filterParams) ? 'Danh sách sản phẩm sẽ hiển thị tại đây khi có dữ liệu.' : 'Thử đổi từ khóa hoặc xóa bớt bộ lọc đang áp dụng.' }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $products->count() }} / {{ $products->total() }} sản phẩm</p>
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
</section>
@endsection
