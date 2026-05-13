@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl font-bold mb-4">Chào Mừng Đến Với Chill Drink</h1>
        <p class="text-xl mb-8">Đồ uống ngon - Giá cả phải chăng - Giao hàng nhanh chóng</p>
        <a href="{{ route('products.index') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 inline-block">
            Xem Sản Phẩm
        </a>
    </div>
</section>

<!-- Categories Section -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold text-gray-800 mb-8">Danh Mục Sản Phẩm</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($categories as $category)
            <a href="{{ route('products.index', ['category' => $category->id]) }}" 
               class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                <div class="text-4xl mb-2">🥤</div>
                <h3 class="font-semibold text-gray-800">{{ $category->name }}</h3>
            </a>
        @endforeach
    </div>
</section>

<!-- Featured Products Section -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold text-gray-800 mb-8">Sản Phẩm Nổi Bật</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($featuredProducts as $product)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded-t-lg">
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-600 mb-2">{{ $product->category->name }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-blue-600">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Thêm
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<!-- Features Section -->
<section class="bg-gray-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="text-5xl mb-4">🚚</div>
                <h3 class="text-xl font-semibold mb-2">Giao Hàng Nhanh</h3>
                <p class="text-gray-600">Giao hàng trong vòng 30 phút</p>
            </div>
            <div class="text-center">
                <div class="text-5xl mb-4">💯</div>
                <h3 class="text-xl font-semibold mb-2">Chất Lượng Đảm Bảo</h3>
                <p class="text-gray-600">Nguyên liệu tươi ngon, an toàn</p>
            </div>
            <div class="text-center">
                <div class="text-5xl mb-4">💰</div>
                <h3 class="text-xl font-semibold mb-2">Giá Cả Hợp Lý</h3>
                <p class="text-gray-600">Nhiều ưu đãi hấp dẫn</p>
            </div>
        </div>
    </div>
</section>
@endsection
