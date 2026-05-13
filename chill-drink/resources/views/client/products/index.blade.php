@extends('layouts.client')

@section('title', 'Sản Phẩm')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Sản Phẩm</h1>
        <p class="text-gray-600 mt-2">Khám phá các loại đồ uống thơm ngon</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filter -->
        <aside class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Danh Mục</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('products.index') }}" 
                           class="block px-3 py-2 rounded {{ !request('category') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                            Tất Cả
                        </a>
                    </li>
                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('products.index', ['category' => $category->id]) }}" 
                               class="block px-3 py-2 rounded {{ request('category') == $category->id ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1">
            <!-- Search & Sort -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form action="{{ route('products.index') }}" method="GET" class="flex gap-4">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Tìm kiếm sản phẩm..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Tìm Kiếm
                    </button>
                </form>
            </div>

            <!-- Products -->
            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                            <a href="{{ route('products.show', $product->slug) }}">
                                <img src="{{ $product->image }}" 
                                     alt="{{ $product->name }}" 
                                     class="w-full h-48 object-cover rounded-t-lg">
                            </a>
                            <div class="p-4">
                                <a href="{{ route('products.show', $product->slug) }}" 
                                   class="font-semibold text-gray-800 hover:text-blue-600 line-clamp-2">
                                    {{ $product->name }}
                                </a>
                                <p class="text-sm text-gray-600 mt-1">{{ $product->category->name }}</p>
                                
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-lg font-bold text-blue-600">
                                        {{ number_format($product->price, 0, ',', '.') }}đ
                                    </span>
                                    
                                    @if($product->stock > 0)
                                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" 
                                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                                                Thêm Vào Giỏ
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-red-500 text-sm">Hết hàng</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Không tìm thấy sản phẩm</h3>
                    <p class="mt-1 text-sm text-gray-500">Thử tìm kiếm với từ khóa khác</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
