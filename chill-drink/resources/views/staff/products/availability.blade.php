@extends('layouts.staff')

@section('page-title', 'Tình trạng sản phẩm')
@section('hide-topbar-search', true)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Tình trạng sản phẩm</h2>
        <p class="text-secondary mb-0">
            Chỉ thay đổi khả năng bán tại <strong>{{ $branch->name }}</strong>. Thông tin và giá sản phẩm không bị thay đổi.
        </p>
    </div>
    <span class="badge bg-light text-dark border px-3 py-2">
        <i class="bi bi-shop me-1"></i>{{ $branch->name }}
    </span>
</div>

<form method="GET" action="{{ route('staff.products.availability.index') }}" class="admin-card p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-7">
            <label for="staff-product-search" class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input id="staff-product-search" class="admin-input" type="search" name="q" value="{{ $search }}"
                   placeholder="Tên sản phẩm, SKU hoặc danh mục">
        </div>
        <div class="col-lg-3">
            <label for="staff-product-availability" class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select id="staff-product-availability" class="admin-filter" name="availability">
                <option value="">Tất cả</option>
                <option value="1" @selected($availability === '1')>Đang bán</option>
                <option value="0" @selected($availability === '0')>Tạm hết hàng</option>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">Lọc</button>
            <a class="btn btn-outline-secondary" href="{{ route('staff.products.availability.index') }}" title="Xóa bộ lọc">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </div>
</form>

<div class="alert d-none" role="alert" data-availability-feedback></div>

<section class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>SKU</th>
                    <th class="text-center">Trạng thái tại chi nhánh</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $branchStatus = $product->branchStatuses->first();
                        $isAvailable = (bool) $branchStatus?->is_available;
                    @endphp
                    <tr data-product-availability="{{ $product->id }}" data-branch-id="{{ $branch->id }}">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-thumb">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                </div>
                                <strong>{{ $product->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td><code>{{ $product->sku }}</code></td>
                        <td class="text-center">
                            <span class="badge {{ $isAvailable ? 'text-bg-success' : 'text-bg-danger' }}"
                                  data-availability-badge data-availability-compact>
                                {{ $isAvailable ? 'Còn hàng' : 'Hết hàng' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <input type="hidden" value="{{ $isAvailable ? 1 : 0 }}" data-availability-input>
                            <button type="button"
                                    class="btn btn-sm {{ $isAvailable ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    data-staff-availability-toggle
                                    data-availability-button
                                    data-update-url="{{ route('staff.products.availability.update', $product) }}">
                                {{ $isAvailable ? 'Tạm hết hàng' : 'Mở bán lại' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">
                            Không có sản phẩm phù hợp tại chi nhánh này.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
        <div class="p-3 border-top">{{ $products->links('pagination::bootstrap-5') }}</div>
    @endif
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pendingProducts = new Set();
    const feedback = document.querySelector('[data-availability-feedback]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showFeedback(message, type) {
        if (typeof window.showRealtimeToast === 'function') {
            window.showRealtimeToast(message, type === 'danger' ? 'warning' : 'success');
            return;
        }

        if (!feedback) return;
        feedback.className = `alert alert-${type}`;
        feedback.textContent = message;
    }

    function applyStatus(row, isAvailable) {
        const input = row.querySelector('[data-availability-input]');
        const badge = row.querySelector('[data-availability-badge]');
        const button = row.querySelector('[data-staff-availability-toggle]');

        if (input) input.value = isAvailable ? '1' : '0';
        if (badge) {
            badge.classList.toggle('text-bg-success', isAvailable);
            badge.classList.toggle('text-bg-danger', !isAvailable);
            badge.textContent = isAvailable ? 'Còn hàng' : 'Hết hàng';
        }
        if (button) {
            button.classList.toggle('btn-outline-danger', isAvailable);
            button.classList.toggle('btn-outline-success', !isAvailable);
            button.textContent = isAvailable ? 'Tạm hết hàng' : 'Mở bán lại';
        }
    }

    document.querySelectorAll('[data-staff-availability-toggle]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const row = button.closest('[data-product-availability]');
            const productId = Number(row?.dataset.productAvailability || 0);
            const currentInput = row?.querySelector('[data-availability-input]');
            if (!row || !productId || !currentInput || pendingProducts.has(productId)) return;

            const previousValue = currentInput.value === '1';
            const nextValue = !previousValue;
            pendingProducts.add(productId);
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            const previousText = button.textContent;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Đang cập nhật';

            try {
                const response = await fetch(button.dataset.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ is_available: nextValue }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'Không thể cập nhật tình trạng sản phẩm.');
                }

                applyStatus(row, Boolean(payload.is_available));
                document.dispatchEvent(new CustomEvent('product:availability-updated', { detail: payload }));
                showFeedback(payload.message, 'success');
            } catch (error) {
                applyStatus(row, previousValue);
                showFeedback(error.message || 'Không thể cập nhật tình trạng sản phẩm.', 'danger');
            } finally {
                pendingProducts.delete(productId);
                button.disabled = false;
                button.removeAttribute('aria-busy');
                if (button.textContent.includes('Đang cập nhật')) button.textContent = previousText;
            }
        });
    });
});
</script>
@endsection
