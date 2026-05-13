@extends('layouts.client')

@section('title', 'Giỏ Hàng')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Giỏ Hàng</h1>

    @if(session('cart') && count(session('cart')) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    @php $total = 0; @endphp
                    
                    @foreach(session('cart') as $id => $item)
                        @php $subtotal = $item['price'] * $item['quantity']; @endphp
                        @php $total += $subtotal; @endphp
                        
                        <div class="flex items-center gap-4 p-4 border-b border-gray-200">
                            <img src="{{ $item['image'] }}" 
                                 alt="{{ $item['name'] }}" 
                                 class="w-20 h-20 object-cover rounded">
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">{{ $item['name'] }}</h3>
                                <p class="text-blue-600 font-semibold mt-1">
                                    {{ number_format($item['price'], 0, ',', '.') }}đ
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <form action="{{ route('cart.update', $id) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" 
                                           name="quantity" 
                                           value="{{ $item['quantity'] }}" 
                                           min="1" 
                                           max="99"
                                           class="w-16 px-2 py-1 border border-gray-300 rounded text-center">
                                    <button type="submit" 
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">
                                        Cập Nhật
                                    </button>
                                </form>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold text-gray-800">
                                    {{ number_format($subtotal, 0, ',', '.') }}đ
                                </p>
                                <form action="{{ route('cart.remove', $id) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="text-red-500 hover:text-red-700">
                            Xóa Tất Cả
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tổng Đơn Hàng</h3>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span>{{ number_format($total, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between font-semibold text-lg">
                            <span>Tổng cộng:</span>
                            <span class="text-blue-600">{{ number_format($total, 0, ',', '.') }}đ</span>
                        </div>
                    </div>

                    @auth
                        <a href="{{ route('checkout.index') }}" 
                           class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Thanh Toán
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 font-semibold">
                            Đăng Nhập Để Thanh Toán
                        </a>
                    @endauth

                    <a href="{{ route('products.index') }}" 
                       class="block w-full text-center py-3 mt-3 text-blue-600 hover:text-blue-700">
                        Tiếp Tục Mua Hàng
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-4 text-xl font-medium text-gray-900">Giỏ hàng trống</h3>
            <p class="mt-2 text-gray-500">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="{{ route('products.index') }}" 
               class="inline-block mt-6 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                Mua Sắm Ngay
            </a>
        </div>
    @endif
</div>
@endsection
