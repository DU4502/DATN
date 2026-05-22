@extends('layouts.client')

@section('title', 'Đăng Nhập')

@section('content')
<section class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="auth-card card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="brand-mark auth-brand-mark mb-3" aria-label="Chill Drink">
                            <i class="bi bi-cup-straw" aria-hidden="true"></i>
                        </div>
                        <h1 class="h3 fw-bold text-center mb-2">Đăng Nhập</h1>
                        <p class="text-secondary text-center mb-4">Chào mừng bạn quay lại Chill Drink.</p>

                        @if(session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                    <label class="form-check-label" for="remember_me">Ghi nhớ đăng nhập</label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-decoration-none">Quên mật khẩu?</a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">Đăng Nhập</button>
                        </form>

                        <p class="text-center text-secondary mt-4 mb-0">
                            Chưa có tài khoản?
                            <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Đăng ký ngay</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
