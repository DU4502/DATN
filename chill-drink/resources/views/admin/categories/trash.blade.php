@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Thùng Rác Danh Mục')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Thùng Rác Danh Mục</h2>
        <p class="text-secondary mb-0">Các danh mục đã bị xóa có thể khôi phục hoặc xóa vĩnh viễn.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>
</section>

<!-- Search Form -->
<form method="GET" action="{{ route('admin.categories.trash') }}" class="mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm danh mục đã xóa</label>
            <input class="admin-input" type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên danh mục...">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">
                <i class="bi bi-search me-1"></i>Tìm kiếm
            </button>
            @if(request('search'))
                <a href="{{ route('admin.categories.trash') }}" class="btn btn-outline-primary">
                    <i class="bi bi-x-circle"></i>
                </a>
            @endif
        </div>
    </div>
</form>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th class="text-center">Sản phẩm</th>
                    <th class="text-center">Ngày xóa</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    @php($categoryImage = $category->image ? asset('storage/' . $category->image) : ($uiCategoryImages[$category->name] ?? $uiDefaultImage))
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
                        <td class="text-center">
                            <span class="badge {{ $category->products_count > 0 ? 'badge-soft-warning' : 'badge-soft-muted' }}">
                                {{ $category->products_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <small class="text-muted">{{ $category->deleted_at ? $category->deleted_at->format('d/m/Y H:i') : '-' }}</small>
                        </td>
                        <td class="text-end text-nowrap">
                            {{-- Nút khôi phục --}}
                            <form action="{{ route('admin.categories.restore', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn khôi phục danh mục này?');">
                                @csrf
                                <button type="submit" class="admin-action text-decoration-none" title="Khôi phục" style="color: var(--admin-success);">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>

                            {{-- Form xóa vĩnh viễn --}}
                            @if((int) $category->products_count === 0)
                                <form action="{{ route('admin.categories.force-delete', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn danh mục này? Hành động này không thể hoàn tác!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-action" title="Xóa Vĩnh Viễn" style="color: var(--a-danger);">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @else
                                <button type="button" class="admin-action" title="Không thể xóa (có sản phẩm)" style="color: var(--admin-muted); cursor: not-allowed;" disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Thùng rác trống</div>
                            <div>Không có danh mục nào đã bị xóa.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $categories->count() }} danh mục đã xóa</p>
        {{ $categories->links('pagination::bootstrap-5') }}
    </div>
</section>
@endsection
