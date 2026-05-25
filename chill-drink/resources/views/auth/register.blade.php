@extends('layouts.client')

@section('title', 'Đăng Ký')

@section('content')
<style>
    .auth-brand-mark {
        display: flex;
        margin-left: auto;
        margin-right: auto;
    }

    .auth-brand-mark i {
        line-height: 1;
    }
</style>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="drink-card card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="brand-mark auth-brand-mark mb-3" aria-label="Chill Drink">
                            <i class="bi bi-cup-straw" aria-hidden="true"></i>
                        </div>
                        <h1 class="h3 fw-bold text-center mb-2">Tạo Tài Khoản</h1>
                        <p class="text-secondary text-center mb-4">Đăng ký để đặt đồ uống nhanh hơn.</p>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Họ tên</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">Đăng Ký</button>
                        </form>

                        <p class="text-center text-secondary mt-4 mb-0">
                            Đã có tài khoản?
                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Đăng nhập</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
