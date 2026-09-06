@extends('layouts.client')

@section('title', 'Tra cứu đơn hàng')

@section('content')
<style>
    .lookup-page {
        min-height: calc(100vh - 88px);
        padding: 64px 0 88px;
        background: linear-gradient(180deg, #effcf8 0%, #f8fbfa 52%, #ffffff 100%);
    }

    .lookup-card {
        max-width: 520px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #dcebe7;
        border-radius: 24px;
        padding: 48px;
        box-shadow: 0 24px 60px rgba(14, 72, 61, 0.1);
    }

    .lookup-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #effcf8;
        color: #0d9373;
        font-size: 2rem;
        display: grid;
        place-items: center;
        margin: 0 auto 24px;
    }

    @media (max-width: 576px) {
        .lookup-card { padding: 32px 20px; border-radius: 18px; }
        .lookup-page { padding: 32px 0 64px; }
    }
</style>

<main class="lookup-page">
    <div class="container">
        <div class="lookup-card">
            <div class="lookup-icon">
                <i class="bi bi-search"></i>
            </div>
            <h1 class="h4 fw-bold text-center mb-2">Tra cứu đơn hàng</h1>
            <p class="text-secondary text-center small mb-4">Nhập mã đơn hàng và email / số điện thoại để xem trạng thái.</p>

            @if(session('error'))
                <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('order-lookup.search') }}">
                @csrf
                <div class="mb-4">
                    <label for="order_code" class="form-label fw-semibold">Mã đơn hàng</label>
                    <input
                        type="text"
                        id="order_code"
                        name="order_code"
                        class="form-control @error('order_code') is-invalid @enderror"
                        placeholder="VD: TH1-ON-20260720-0001"
                        value="{{ old('order_code') }}"
                        autocomplete="off"
                    >
                    @error('order_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">
                    <i class="bi bi-search me-2"></i>Tra cứu
                </button>
            </form>
        </div>
    </div>
</main>
@endsection
