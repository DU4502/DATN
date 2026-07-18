@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Thùng Rác Slideshow')

@section('content')
@php
    $viewer = auth()->user();
@endphp

<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Thùng Rác Slideshow Chi Nhánh</h2>
        @if($branch)
            <p class="text-secondary mb-0">Xem slide đã xóa của chi nhánh: <strong class="text-dark">{{ $branch->name }}</strong></p>
        @else
            <p class="text-secondary mb-0">Các slide đã bị xóa có thể khôi phục hoặc xóa vĩnh viễn.</p>
        @endif
    </div>
    
    <div class="d-flex align-items-center gap-3">
        @if($viewer->isSuperAdmin() && $branches->isNotEmpty())
            <div class="d-flex align-items-center gap-2">
                <span class="admin-kicker mb-0 text-nowrap">Chọn chi nhánh:</span>
                <form action="{{ route('admin.slides.trash') }}" method="GET" class="m-0">
                    <select name="branch_id" class="form-select" onchange="this.form.submit()" style="min-width: 220px;">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
        <a href="{{ route('admin.slides.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>
</section>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-12">
        <div class="admin-card">
            <h3 class="h5 fw-bold mb-3">Slide đã xóa ({{ $slides->count() }})</h3>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Món / Tiêu đề</th>
                            <th>Giá</th>
                            <th>Màu Nền / Mô Tả</th>
                            <th class="text-center">Thứ tự</th>
                            <th class="text-center">Ngày xóa</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slides as $slide)
                            @php
                                $imgUrl = str_starts_with($slide->image, '/') ? $slide->image : asset('storage/' . $slide->image);
                            @endphp
                            <tr>
                                <td>
                                    <div class="rounded p-1 text-center" style="background-color: {{ $slide->bg_color }}; width: 62px; height: 62px;">
                                        <img src="{{ $imgUrl }}" alt="{{ $slide->product_name }}" style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                </td>
                                <td>
                                    <strong class="d-block text-dark">{{ $slide->product_name }}</strong>
                                    <small class="text-secondary d-block text-truncate" style="max-width: 180px;">{{ $slide->title }}</small>
                                </td>
                                <td class="text-primary fw-bold">{{ $slide->price }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="d-inline-block rounded-circle border" style="width: 14px; height: 14px; background-color: {{ $slide->bg_color }};"></span>
                                        <small class="text-secondary" style="font-family: monospace;">{{ $slide->bg_color }}</small>
                                    </div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="{{ $slide->description }}">
                                        {{ $slide->description }}
                                    </small>
                                </td>
                                <td class="text-center fw-bold">{{ $slide->sort_order }}</td>
                                <td class="text-center">
                                    <small class="text-muted">{{ $slide->deleted_at ? $slide->deleted_at->format('d/m/Y H:i') : '-' }}</small>
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Nút khôi phục --}}
                                    <form action="{{ route('admin.slides.restore', $slide) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn khôi phục slide này?');">
                                        @csrf
                                        <button type="submit" class="admin-action btn btn-link text-decoration-none text-success" title="Khôi phục Slide">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>

                                    {{-- Form xóa vĩnh viễn --}}
                                    <form action="{{ route('admin.slides.force-delete', $slide) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn slide này? Hành động này không thể hoàn tác!');">
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
                                    <div>Không có slide nào đã bị xóa.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
