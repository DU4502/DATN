@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Sản phẩm')

@section('search-placeholder', 'Tìm tên, mã hoặc giá...')
@php
    $productIndexRoute = request()->routeIs('admin.super-admin.manage.products.*')
        ? 'admin.super-admin.manage.products.index'
        : 'admin.products.index';
@endphp
@section('topbar-search-action', route($productIndexRoute))


@section('content')
@php
    $filterParams = collect($filters ?? [])->filter(fn ($value, $key) => $value !== '' && ! ($key === 'sort' && $value === 'latest'))->all();
    $currentCategory = (string) ($filters['category'] ?? '');
    $currentStatus = (string) ($filters['status'] ?? '');
    $currentAvailability = (string) ($filters['availability'] ?? '');
    $currentSort = (string) ($filters['sort'] ?? 'latest');
    $hasAdvancedFilters = $currentStatus !== '' || $currentAvailability !== '' || request('branch_id') || $currentSort !== 'latest';
    $returnParams = request()->only(['q', 'category', 'status', 'availability', 'branch_id', 'sort', 'page']);
@endphp

<style>
    .product-category-quick-filter { width: min(220px, 100%); }
    .product-category-quick-filter .admin-filter { min-height: 38px; padding-block: .4rem; }
    .product-availability-summary { display: flex; flex-direction: column; align-items: flex-start; gap: .2rem; min-width: 230px; }
    .product-availability-toggle { padding: 0; text-decoration: none; font-weight: 700; }
    .product-availability-toggle [data-toggle-chevron] { display: inline-block; transition: transform .2s ease; }
    .product-availability-toggle[aria-expanded="true"] [data-toggle-chevron] { transform: rotate(180deg); }
    .product-availability-details > td { border-top: 0; }
    .product-availability-panel { margin: 0 .75rem .75rem; padding: .75rem 1rem; background: var(--admin-soft-2); border: 1px solid var(--admin-border); border-radius: 12px; }
    .product-branch-table th { color: var(--admin-muted); font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; }
    .product-branch-table td, .product-branch-table th { padding: .65rem .75rem; }
    .product-branch-table tbody tr:last-child td { border-bottom: 0; }
    @media (max-width: 767.98px) {
        .product-category-quick-filter { width: 100%; }
        .product-availability-summary { min-width: 190px; }
        .product-availability-panel { margin-inline: .35rem; padding-inline: .5rem; }
    }
</style>

<section class="admin-sticky-tools d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div class="d-flex flex-column gap-2 flex-grow-1">

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route($productIndexRoute) }}" class="btn {{ empty($filterParams) ? 'btn-primary' : 'btn-outline-primary' }}">Tất cả sản phẩm</a>
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
            <a href="{{ request()->routeIs('admin.super-admin.manage.products.*') ? route('admin.super-admin.manage.products.create') : route('admin.products.create') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mới</a>
            <a href="{{ request()->routeIs('admin.super-admin.manage.products.*') ? route('admin.super-admin.manage.products.trash') : route('admin.products.trash') }}" class="btn btn-outline-secondary"><i class="bi bi-trash me-1"></i>Thùng rác</a>
            <form method="GET" action="{{ route($productIndexRoute) }}" class="product-category-quick-filter">
                @foreach(request()->except(['page', 'category']) as $key => $value)
                    @if(!is_array($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="visually-hidden" for="quickCategoryFilter">Danh mục</label>
                <select id="quickCategoryFilter" name="category" class="admin-filter" onchange="this.form.submit()">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($currentCategory === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div
        class="text-lg-end"
        data-product-summary
        data-current-branch-id="{{ $managedBranch?->id ?? request('branch_id') }}"
    >
        <p class="admin-kicker mb-1">Tình trạng bán</p>
        <div class="d-flex align-items-center gap-3">
            <div><span class="admin-value text-primary" data-product-total>{{ $totalProducts }}</span><small class="d-block text-secondary fw-bold">Tổng</small></div>
            <div style="width:1px;height:38px;background:var(--admin-border);"></div>
            <div><span class="admin-value" style="color:var(--admin-danger);" data-product-unavailable>{{ $unavailableProducts }}</span><small class="d-block text-secondary fw-bold">Hết hàng</small></div>
        </div>
    </div>
</section>



<section class="admin-filter-panel {{ $hasAdvancedFilters ? '' : 'd-none' }}" id="productFilterPanel" data-admin-filter-panel>
    <form method="GET" action="{{ route($productIndexRoute) }}" class="admin-card p-4 mb-4">
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
            @if(auth()->user()->isSuperAdmin())
                <div class="col-md-2">
                    <label class="admin-kicker mb-2 d-block" for="branch_id">Chi nhánh</label>
                    <select id="branch_id" name="branch_id" class="admin-filter">
                        <option value="">Tất cả</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <label class="admin-kicker mb-2 d-block" for="availability">Tình trạng</label>
                <select id="availability" name="availability" class="admin-filter">
                    <option value="">Tất cả</option>
                    <option value="available" @selected($currentAvailability === 'available')>Còn hàng</option>
                    <option value="out_of_stock" @selected($currentAvailability === 'out_of_stock')>Hết hàng</option>
                    <option value="unassigned" @selected($currentAvailability === 'unassigned')>Chưa áp dụng</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="admin-kicker mb-2 d-block" for="sort">Sắp xếp</label>
                <select id="sort" name="sort" class="admin-filter">
                    <option value="latest" @selected($currentSort === 'latest')>Mới nhất</option>
                    <option value="name" @selected($currentSort === 'name')>Tên A-Z</option>
                    <option value="price_asc" @selected($currentSort === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected($currentSort === 'price_desc')>Giá giảm dần</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="{{ route($productIndexRoute) }}" class="btn btn-outline-primary" aria-label="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>
</section>

<section
    class="admin-card"
    data-current-branch-id="{{ $managedBranch?->id ?? request('branch_id') }}"
>
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
                    @php
                        $productBranchStatuses = $product->branchStatuses->keyBy('branch_id');
                        $branchTotal = $branches->count();
                        $availableCount = $branches->filter(fn ($branch) => $productBranchStatuses->get($branch->id)?->is_available === true)->count();
                        $unavailableCount = $branches->filter(fn ($branch) => $productBranchStatuses->get($branch->id)?->is_available === false)->count();
                        $unassignedCount = max(0, $branchTotal - $availableCount - $unavailableCount);
                    @endphp
                    <tr data-product-row="{{ $product->id }}">
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
                            <div class="product-availability-summary" data-availability-summary="{{ $product->id }}" data-total-branches="{{ $branchTotal }}">
                                <div class="small fw-bold text-dark" data-availability-summary-text>
                                    {{ $availableCount }}/{{ $branchTotal }} chi nhánh còn hàng
                                    @if($unavailableCount > 0)
                                        <span class="text-secondary"> • {{ $unavailableCount }} chi nhánh hết hàng</span>
                                    @endif
                                    @if($unassignedCount > 0)
                                        <span class="text-secondary"> • {{ $unassignedCount }} chưa áp dụng</span>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link product-availability-toggle"
                                    data-availability-toggle="{{ $product->id }}"
                                    aria-expanded="false"
                                    aria-controls="productAvailabilityDetails{{ $product->id }}"
                                >
                                    Quản lý trạng thái <span data-toggle-chevron>▼</span>
                                </button>
                            </div>
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
                    <tr class="d-none product-availability-details" id="productAvailabilityDetails{{ $product->id }}" data-availability-details="{{ $product->id }}">
                        <td colspan="6" class="p-0">
                            <div class="product-availability-panel">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0 product-branch-table">
                                        <thead>
                                            <tr>
                                                <th>Chi nhánh</th>
                                                <th>Trạng thái</th>
                                                <th class="text-end">Chuyển trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($branches as $branch)
                                                @php($branchStatus = $productBranchStatuses->get($branch->id))
                                                <tr
                                                    data-product-availability="{{ $product->id }}"
                                                    data-branch-id="{{ $branch->id }}"
                                                    data-availability-state="{{ ! $branchStatus ? 'unassigned' : ($branchStatus->is_available ? 'available' : 'unavailable') }}"
                                                >
                                                    <td class="fw-semibold">{{ $branch->name }}</td>
                                                    <td>
                                                        <span class="badge {{ ! $branchStatus ? 'text-bg-secondary' : ($branchStatus->is_available ? 'text-bg-success' : 'text-bg-danger') }}" data-availability-badge data-availability-compact>
                                                            {{ ! $branchStatus ? 'Chưa áp dụng' : ($branchStatus->is_available ? 'Còn hàng' : 'Hết hàng') }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        @if($branchStatus || auth()->user()->isSuperAdmin())
                                                            <form method="POST" action="{{ route('admin.products.branches.availability.update', [$product, $branch]) }}" class="d-inline-block" data-availability-form>
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="is_available" value="{{ $branchStatus?->is_available ? 0 : 1 }}" data-availability-input>
                                                                <button type="submit" class="btn btn-sm btn-outline-primary" data-availability-button>
                                                                    {{ ! $branchStatus ? 'Áp dụng' : ($branchStatus->is_available ? 'Chuyển hết hàng' : 'Chuyển còn hàng') }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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

@push('scripts')
<script>
function showProductToast(message, type = 'success') {
    if (typeof window.showRealtimeToast === 'function') {
        window.showRealtimeToast(message, type);
        return;
    }

    let container = document.getElementById('productToastFallback');
    if (! container) {
        container = document.createElement('div');
        container.id = 'productToastFallback';
        container.style.cssText = 'position:fixed;top:84px;right:20px;z-index:10001;display:flex;flex-direction:column;gap:10px;max-width:calc(100vw - 40px);';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const palette = {
        success: { bg: '#ecfdf5', border: '#a7f3d0', color: '#065f46' },
        warning: { bg: '#fffbeb', border: '#fde68a', color: '#92400e' },
        info: { bg: '#eff6ff', border: '#bfdbfe', color: '#1d4ed8' },
        error: { bg: '#fef2f2', border: '#fecaca', color: '#b91c1c' },
    }[type] || { bg: '#ecfdf5', border: '#a7f3d0', color: '#065f46' };

    toast.textContent = message;
    toast.style.cssText = `padding:12px 14px;border:1px solid ${palette.border};background:${palette.bg};color:${palette.color};border-radius:12px;box-shadow:0 14px 30px rgba(15,23,42,.12);font-weight:600;font-size:.85rem;`;
    container.appendChild(toast);
    window.setTimeout(() => toast.remove(), 2400);
}

document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.querySelector('form.admin-search');
    const searchInput = searchForm?.querySelector('input[name="q"]');

    if (!searchForm || !searchInput) {
        return;
    }

    let searchTimer = null;
    let lastSubmittedValue = searchInput.value.trim();

    searchInput.addEventListener('input', function () {
        const currentValue = searchInput.value.trim();

        if (currentValue === lastSubmittedValue) {
            return;
        }

        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            lastSubmittedValue = searchInput.value.trim();

            if (typeof searchForm.requestSubmit === 'function') {
                searchForm.requestSubmit();
                return;
            }

            searchForm.submit();
        }, 300);
    });
});

function updateProductAvailabilitySummary(productId) {
    const details = document.querySelector(`[data-availability-details="${productId}"]`);
    const summary = document.querySelector(`[data-availability-summary="${productId}"]`);
    const summaryText = summary?.querySelector('[data-availability-summary-text]');
    if (! details || ! summaryText) return;

    const branchRows = Array.from(details.querySelectorAll('[data-availability-state]'));
    const total = branchRows.length;
    const available = branchRows.filter((row) => row.dataset.availabilityState === 'available').length;
    const unavailable = branchRows.filter((row) => row.dataset.availabilityState === 'unavailable').length;
    const unassigned = Math.max(0, total - available - unavailable);
    const parts = [`${available}/${total} chi nhánh còn hàng`];

    if (unavailable > 0) parts.push(`${unavailable} chi nhánh hết hàng`);
    if (unassigned > 0) parts.push(`${unassigned} chưa áp dụng`);
    summaryText.textContent = parts.join(' • ');
}

function updateProductAvailabilityCounter(delta) {
    if (! delta) return;

    const unavailableCounter = document.querySelector('[data-product-unavailable]');
    if (! unavailableCounter) return;

    const current = Number.parseInt(unavailableCounter.textContent || '0', 10) || 0;
    unavailableCounter.textContent = String(Math.max(0, current + delta));
}

function updateAvailabilityButtonState(button, isAvailable) {
    const input = button?.closest('form')?.querySelector('[data-availability-input]');
    if (input) {
        input.value = isAvailable ? '0' : '1';
    }

    button.textContent = isAvailable ? 'Chuyển hết hàng' : 'Chuyển còn hàng';
}

document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-availability-toggle]');
    if (! toggle) return;

    const productId = toggle.dataset.availabilityToggle;
    const details = document.querySelector(`[data-availability-details="${productId}"]`);
    if (! details) return;

    const willOpen = details.classList.contains('d-none');
    details.classList.toggle('d-none', ! willOpen);
    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
});

document.addEventListener('product:availability-updated', function (event) {
    const payload = event.detail;
    if (! payload) return;

    const branchRow = document.querySelector(
        `[data-availability-details="${payload.product_id}"] [data-branch-id="${payload.branch_id}"]`
    );
    if (! branchRow) return;

    branchRow.dataset.availabilityState = payload.is_available ? 'available' : 'unavailable';
    updateProductAvailabilitySummary(payload.product_id);
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-availability-form]');
    if (! form) return;
    event.preventDefault();

    const row = form.closest('[data-availability-state]');
    const button = form.querySelector('[data-availability-button]');
    const previousLabel = button?.textContent;
    const previousState = row?.dataset.availabilityState || null;
    if (button) {
        button.disabled = true;
        button.textContent = 'Đang cập nhật...';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: new FormData(form),
        });

        if (! response.ok) throw new Error('Không thể cập nhật trạng thái sản phẩm.');
        const payload = await response.json();
        document.dispatchEvent(new CustomEvent('product:availability-updated', {detail: payload}));

        if (row) {
            const wasAvailable = previousState === 'available';
            row.dataset.availabilityState = payload.is_available ? 'available' : 'unavailable';

            const badge = row.querySelector('[data-availability-badge]');
            if (badge) {
                badge.className = `badge ${payload.is_available ? 'text-bg-success' : 'text-bg-danger'}`;
                badge.textContent = payload.is_available ? 'Còn hàng' : 'Hết hàng';
            }

            updateAvailabilityButtonState(button, payload.is_available);
            updateProductAvailabilitySummary(payload.product_id);

            if (row.dataset.branchId && button?.closest('[data-current-branch-id]')) {
                const currentBranchId = button.closest('[data-current-branch-id]').dataset.currentBranchId;
                if (String(row.dataset.branchId) === String(currentBranchId) && previousState && previousState !== 'unassigned') {
                    const delta = payload.is_available ? -1 : 1;
                    updateProductAvailabilityCounter(delta);
                }
            }
        }

        showProductToast(payload.message || 'Cập nhật trạng thái thành công.', 'success');
    } catch (error) {
        if (button) button.textContent = previousLabel;
        showProductToast(error.message || 'Không thể cập nhật trạng thái sản phẩm.', 'error');
    } finally {
        if (button) button.disabled = false;
    }
});
</script>
@endpush
