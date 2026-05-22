@extends('layouts.admin')

@section('page-title', 'Quản lý sản phẩm')

@section('content')
<div class="admin-card card border-0">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Sản phẩm</h2>
            <p class="text-secondary mb-0">Theo dõi hình ảnh, tồn kho, giá bán và trạng thái hiển thị.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Thêm sản phẩm</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="admin-thumb">
                                @else
                                    <div class="admin-thumb d-flex align-items-center justify-content-center bg-light">
                                        <span class="text-secondary" style="font-size: 12px;">No IMG</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <small class="text-secondary">{{ $product->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? 'Chưa phân loại' }}</td>
                        <td class="fw-bold text-primary">{{ number_format($product->price, 0, ',', '.') }}đ</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            <span class="badge {{ $product->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $product->status ? 'Đang bán' : 'Ẩn' }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ $product->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">
                                    Sửa
                                </a>
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" 
                                      onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">Chưa có sản phẩm.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $products->links() }}
    </div>
</div>
@endsection
