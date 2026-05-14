@extends('layouts.client')

@section('title', 'Thanh Toán')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="h2 fw-bold mb-4">Thanh Toán</h1>
        @php $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']); @endphp

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Thông tin thanh toán</h2>
                        <form method="POST" action="{{ route('checkout.process') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="cod">Thanh toán khi nhận hàng</option>
                                    <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                    <option value="momo">Momo</option>
                                    <option value="vnpay">VNPay</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea id="note" name="note" rows="4" class="form-control @error('note') is-invalid @enderror" placeholder="Ghi chú cho đơn hàng...">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">Đặt Hàng</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Đơn hàng của bạn</h2>
                        @foreach($cart as $item)
                            <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                <span>{{ $item['name'] }} x {{ $item['quantity'] }}</span>
                                <strong>{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</strong>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between h5 mt-3">
                            <span>Tổng cộng</span>
                            <strong class="text-primary">{{ number_format($total, 0, ',', '.') }}đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
