@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Thùng Rác Sản Phẩm')

@section('content')
@php
    $filterParams = collect($filters ?? [])->filter(fn ($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))->all();
    $currentCategory = (string) ($filters['category'] ?? '');
    $currentSort = (string) ($filters['sort'] ?? 'latest');
    $hasAdvancedFilters = $currentCategory !== '' || $currentSort !== 'latest';
@endphp

<section class="admin-sticky-tools d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div class="d-flex flex-column gap-2 flex-grow-1">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('admin.products.trash') }}" class="btn {{ empty($filterParams) ? 'btn-primary' : 'btn-outline-primary' }}">Tất cả đã xóa</a>
            <button
                class="btn {{ $hasAdvancedFilters ? 'btn-primary' : 'btn-outline-primary' }}"
                type="button"
                data-admin-filter-toggle
                aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
                aria-controls="productFilterPanel"
            >
                <i class="bi bi-sliders me-1"></i>Bộ lọc
                @if($activeFiltersCount > 0)
                    <span class="badge text-bg-light text-primary ms-1">{{ $activeFiltersCount }}</span>
                @endif
            </button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Quay lại danh sách</a>
        </div>
        <div class="admin-category-scroller">
            @foreach($categories as $category)
                @php
                    $categoryParams = array_filter(array_merge(request()->except(['page', 'category']), ['category' => $category->id]), fn ($value) => $value !== null && $value !== '');
                @endphp
                <a href="{{ route('admin.products.trash', $categoryParams) }}" class="btn {{ $currentCategory === (string) $category->id ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="text-lg-end">
        <p class="admin-kicker mb-1">Thùng rác</p>
        <div class="d-flex align-items-center gap-3">
            <div><span class="admin-value text-primary">{{ $totalProducts }}</span><small class="d-block text-secondary fw-bold">Đã xóa</small></div>
        </div>
    </div>
</section>

<section class="admin-filter-panel {{ $hasAdvancedFilters ? '' : 'd-none' }}" id="productFilterPanel" data-admin-filter-panel>
    <form method="GET" action="{{ route('admin.products.trash') }}" class="admin-card p-4 mb-4">
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
            <div class="col-md-2">
                <label class="admin-kicker mb-2 d-block" for="sort">Sắp xếp</label>
                <select id="sort" name="sort" class="admin-filter">
                    <option value="latest" @selected($currentSort === 'latest')">Mới nhất</option>
                    <option value="name" @selected($currentSort === 'name')">Tên A-Z</option>
                    <option value="price_asc" @selected($currentSort === 'price_asc')">Giá tăng dần</option>
                    <option value="price_desc" @selected($currentSort === 'price_desc')">Giá giảm dần</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
            </div>
        </div>
    </form>
</section>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Tồn kho</th>
                    <th>Ngày xóa</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        </td>
                        <td>
                            <strong class="d-block text-dark">{{ $product->name }}</strong>
                            <small class="text-secondary">{{ $product->sku ?? 'Chưa có' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-secondary">{{ $product->category?->name ?? 'Chưa có' }}</span>
                        </td>
                        <td class="text-primary fw-bold">{{ number_format($product->price, 0, ',', '.') }}₫</td>
                        <td>
                            <span class="{{ $product->stock <= 0 ? 'text-danger' : ($product->stock <= 5 ? 'text-warning' : 'text-success') }} fw-bold">
                                {{ $product->stock }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $product->deleted_at ? $product->deleted_at->format('d/m/Y H:i') : '-' }}</small>
                        </td>
                        <td class="text-end text-nowrap">
                            {{-- Nút khôi phục --}}
                            <form action="{{ route('admin.products.restore', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn khôi phục sản phẩm này?');">
                                @csrf
                                <button type="submit" class="admin-action btn btn-link text-decoration-none text-success" title="Khôi phục">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>

                            {{-- Form xóa vĩnh viễn --}}
                            <form action="{{ route('admin.products.force-delete', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn sản phẩm này? Hành động này không thể hoàn tác!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-action btn btn-link text-decoration-none text-danger" title="Xóa Vĩnh Viễn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Thùng rác trống</div>
                            <div>Không có sản phẩm nào đã bị xóa.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-secondary">Hiển thị {{ $products->firstItem() }} - {{ $products->lastItem() }} của {{ $products->total() }} sản phẩm</small>
            {{ $products->appends(request()->except('page'))->links() }}
        </div>
    @endif
</div>
@endsection
