@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('title', 'Quản lý Topping')
@section('page-title', 'Quản lý Topping')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: #10241e;">Quản lý Topping</h1>
        <p class="text-secondary small mb-0">Thêm mới và thiết lập giá các loại Topping đi kèm đồ uống.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createToppingModal" style="background:#0D9373; border-color:#0D9373;">
        <i class="bi bi-plus-circle"></i>Thêm Topping mới
    </button>
</div>

<!-- Thống kê -->
<section class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-egg-fried"></i></span>
                <span class="badge badge-soft-muted">Toàn hệ thống</span>
            </div>
            <div class="mt-3">
                <p class="admin-kicker mb-1">TỔNG SỐ TOPPING</p>
                <p class="admin-value mb-0">{{ number_format($totalToppings) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot text-success"><i class="bi bi-check-circle"></i></span>
                <span class="badge badge-soft-success">Hoạt động</span>
            </div>
            <div class="mt-3">
                <p class="admin-kicker mb-1">ĐANG KINH DOANH</p>
                <p class="admin-value mb-0">{{ number_format($activeToppings) }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Bộ lọc -->
<div class="admin-card mb-4">
    <form method="GET" action="{{ route('admin.toppings.index') }}" class="row g-3">
        <div class="col-md-6 col-lg-5">
            <input type="text" name="search" class="form-control rounded-3" placeholder="Tìm theo tên Topping..." value="{{ request('search') }}">
        </div>
        <div class="col-md-4 col-lg-4">
            <select name="status" class="form-select rounded-3">
                <option value="">Tất cả trạng thái</option>
                <option value="1" @selected(request('status') === '1')>Hoạt động</option>
                <option value="0" @selected(request('status') === '0')>Ngừng kinh doanh</option>
            </select>
        </div>
        <div class="col-md-2 col-lg-3">
            <button type="submit" class="btn btn-secondary w-100 rounded-3 fw-semibold">
                <i class="bi bi-funnel me-1"></i>Lọc
            </button>
        </div>
    </form>
</div>

<!-- Bảng danh sách -->
<div class="admin-card p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4" style="width: 80px;">STT</th>
                    <th>TÊN TOPPING</th>
                    <th>GIÁ THÊM</th>
                    <th>TRẠNG THÁI</th>
                    <th class="text-end pe-4">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($toppings as $index => $topping)
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">{{ $toppings->firstItem() + $index }}</td>
                        <td>
                            <span class="fw-bold text-dark fs-6">{{ $topping->name }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-success" style="font-size: 1rem;">+{{ number_format($topping->price, 0, ',', '.') }}đ</span>
                        </td>
                        <td>
                            @if($topping->status)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5">
                                    <i class="bi bi-check-circle me-1"></i>Hoạt động
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1.5">
                                    <i class="bi bi-slash-circle me-1"></i>Tạm ngưng
                                </span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap pe-4">
                            <button type="button" class="admin-action" data-bs-toggle="modal" data-bs-target="#editToppingModal{{ $topping->id }}" title="Chỉnh sửa">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <form method="POST" action="{{ route('admin.toppings.destroy', $topping->id) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa Topping này?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-action" title="Xóa" style="color: var(--a-danger);">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                            Chưa có Topping nào. Hãy bấm <strong>"Thêm Topping mới"</strong> để bắt đầu.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($toppings->hasPages())
        <div class="p-3 bg-white border-top">
            {{ $toppings->links() }}
        </div>
    @endif
</div>

<!-- Modal Thêm Topping mới -->
<div class="modal fade" id="createToppingModal" tabindex="-1" aria-labelledby="createToppingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="createToppingModalLabel">Thêm Topping mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.toppings.store') }}">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="create_name" class="form-label small fw-bold">Tên Topping <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="create_name" class="form-control rounded-3" placeholder="Ví dụ: Kem cheese, Pudding trứng..." required>
                    </div>

                    <div class="mb-4">
                        <label for="create_price" class="form-label small fw-bold">Giá cộng thêm (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="create_price" class="form-control rounded-3" placeholder="Ví dụ: 7000" min="0" step="1000" required>
                    </div>

                    <div class="form-check form-switch p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="status" id="create_status" value="1" checked>
                        <label class="form-check-label fw-bold text-dark" for="create_status">
                            Bật kinh doanh Topping này
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:#0D9373; border-color:#0D9373;">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa Topping -->
@foreach($toppings as $topping)
    <div class="modal fade" id="editToppingModal{{ $topping->id }}" tabindex="-1" aria-labelledby="editToppingModalLabel{{ $topping->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editToppingModalLabel{{ $topping->id }}">Chỉnh sửa Topping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.toppings.update', $topping->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label for="edit_name_{{ $topping->id }}" class="form-label small fw-bold">Tên Topping <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name_{{ $topping->id }}" class="form-control rounded-3" value="{{ $topping->name }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="edit_price_{{ $topping->id }}" class="form-label small fw-bold">Giá cộng thêm (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="edit_price_{{ $topping->id }}" class="form-control rounded-3" value="{{ (int)$topping->price }}" min="0" step="1000" required>
                        </div>

                        <div class="form-check form-switch p-3 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="status" id="edit_status_{{ $topping->id }}" value="1" @checked($topping->status)>
                            <label class="form-check-label fw-bold text-dark" for="edit_status_{{ $topping->id }}">
                                Bật kinh doanh Topping này
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:#0D9373; border-color:#0D9373;">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection
