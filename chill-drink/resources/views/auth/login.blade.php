@extends('layouts.client')

@section('title', 'Đăng Nhập')

@section('content')
<style>
    .auth-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        background: url('https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=2000&q=80') center/cover no-repeat;
        position: relative;
    }
    .auth-page::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(13, 147, 115, 0.85) 0%, rgba(6, 122, 95, 0.95) 100%);
    }
    
    .auth-container { position: relative; z-index: 1; padding: 4rem 0; width: 100%; }

    .auth-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: var(--radius-2xl);
        box-shadow: 0 24px 48px rgba(0,0,0,0.2);
        overflow: hidden;
    }

    .auth-header {
        text-align: center; margin-bottom: 2rem;
    }
    .auth-logo {
        width: 64px; height: 64px; border-radius: var(--radius-lg);
        background: #fff; display: inline-flex; align-items: center; justify-content: center;
        box-shadow: var(--shadow-md); margin-bottom: 1.5rem; border: 1px solid var(--c-border);
    }
    .auth-logo img { width: 44px; height: 44px; object-fit: contain; }

    .form-floating > .form-control {
        border-radius: var(--radius-md); border-color: var(--c-border);
    }
    .form-floating > .form-control:focus {
        border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-glow);
    }

    .btn-auth-submit {
        height: 54px; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700;
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        border: none; color: #fff; box-shadow: 0 8px 16px rgba(13,147,115,0.3); transition: all 0.3s ease;
    }
    .btn-auth-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 20px rgba(13,147,115,0.4); color: #fff; }

    .auth-divider { color: var(--c-subtle); margin: 2rem 0; font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; }
    
    .social-btn {
        height: 48px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.95rem;
        background: var(--c-bg); border: 1.5px solid var(--c-border); color: var(--c-ink-secondary);
        display: flex; align-items: center; justify-content: center; gap: 0.75rem; transition: all 0.2s;
    }
    .social-btn:hover { background: var(--c-surface); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
</style>

<section class="auth-page">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <div class="p-4 p-md-5">
                            <div class="auth-header">
                                <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Chill Drink"></div>
                                <h1 class="h3 fw-bold mb-2">Đăng nhập tài khoản</h1>
                                <p class="text-secondary">Chào mừng bạn trở lại với Chill Drink</p>
                            </div>

                            @if(session('status'))
                                <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}</div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="form-floating mb-3">
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required autofocus autocomplete="username">
                                    <label for="email">Địa chỉ Email</label>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-floating mb-4">
                                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mật khẩu" required autocomplete="current-password">
                                    <label for="password">Mật khẩu</label>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                        <label class="form-check-label fw-medium text-secondary" for="remember_me" style="font-size: 0.9rem;">Ghi nhớ đăng nhập</label>
                                    </div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-primary fw-semibold text-decoration-none" style="font-size: 0.9rem;">Quên mật khẩu?</a>
                                    @endif
                                </div>

                                <button type="submit" class="btn btn-auth-submit w-100">Đăng Nhập</button>
                            </form>

                            <div class="auth-divider d-flex align-items-center gap-3">
                                <hr class="flex-grow-1 m-0"><span>Hoặc</span><hr class="flex-grow-1 m-0">
                            </div>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <button type="button" class="btn social-btn"><i class="bi bi-google text-danger fs-5"></i> Tiếp tục với Google</button>
                                <button type="button" class="btn social-btn"><i class="bi bi-facebook text-primary fs-5"></i> Tiếp tục với Facebook</button>
                            </div>

                            <p class="text-center text-secondary mb-0">
                                Chưa có tài khoản? <a href="{{ route('register') }}" class="text-primary fw-bold text-decoration-none ms-1">Đăng ký ngay</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
