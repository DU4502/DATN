@extends('layouts.client')

@section('title', 'Xác Thực Email')

@section('content')
<style>
    .auth-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0.34) 46%, rgba(255, 255, 255, 0.08) 100%),
            url('https://png.pngtree.com/background/20250106/original/pngtree-bubble-tea-cup-with-splashing-milk-summer-drinks-background-picture-image_15464755.jpg') center/cover no-repeat;
        position: relative;
    }

    .auth-page::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(0, 139, 122, 0.10) 0%, rgba(255, 246, 225, 0.22) 100%);
    }

    .auth-container {
        position: relative;
        z-index: 1;
        padding: 4rem 0;
        width: 100%;
    }

    .auth-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: var(--radius-2xl);
        box-shadow: 0 24px 58px rgba(12, 54, 47, 0.22);
        overflow: hidden;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-logo {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-lg);
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-md);
        margin-bottom: 1.5rem;
        border: 1px solid var(--c-border);
    }

    .auth-logo img {
        width: 44px;
        height: 44px;
        object-fit: contain;
    }

    .verification-mail {
        padding: 0.85rem 1rem;
        border-radius: var(--radius-md);
        background: rgba(13, 147, 115, 0.08);
        border: 1px solid rgba(13, 147, 115, 0.16);
        color: var(--c-ink-secondary);
        font-size: 0.92rem;
        line-height: 1.5;
    }

    .verification-code {
        height: 58px;
        border-radius: var(--radius-md);
        border-color: var(--c-border);
        text-align: center;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: 0.26em;
    }

    .verification-code:focus {
        border-color: var(--c-primary);
        box-shadow: 0 0 0 4px var(--c-primary-glow);
    }

    .btn-auth-submit {
        height: 54px;
        border-radius: var(--radius-md);
        font-size: 1rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        border: none;
        color: #fff;
        box-shadow: 0 8px 16px rgba(13, 147, 115, 0.3);
        transition: all 0.25s ease;
    }

    .btn-auth-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 20px rgba(13, 147, 115, 0.4);
        color: #fff;
    }

    .btn-soft {
        height: 46px;
        border-radius: var(--radius-md);
        border: 1.5px solid var(--c-border);
        background: var(--c-bg);
        color: var(--c-ink-secondary);
        font-weight: 700;
    }

    .btn-soft:hover {
        background: var(--c-surface);
        color: var(--c-primary);
    }
</style>

<section class="auth-page">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <div class="p-4 p-md-5">
                            <div class="auth-header">
                                <div class="auth-logo">
                                    <img src="{{ asset('images/logo.png') }}" alt="Chill Drink">
                                </div>
                                <h1 class="h3 fw-bold mb-2">Xác minh email</h1>
                                <p class="text-secondary mb-0">Nhập mã 6 số đã gửi đến Gmail của bạn.</p>
                            </div>

                            <div class="verification-mail mb-4">
                                <i class="bi bi-envelope-check text-primary me-2"></i>
                                Mã xác minh đã được gửi tới <strong>{{ auth()->user()->email }}</strong>. Mã có hiệu lực trong 10 phút.
                            </div>

                            @if (session('status') === 'verification-code-sent')
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    Mã xác minh mới đã được gửi.
                                </div>
                            @elseif (session('status'))
                                <div class="alert alert-success mb-4">{{ __((string) session('status')) }}</div>
                            @endif

                            <form method="POST" action="{{ route('verification.code.verify') }}" class="mb-3">
                                @csrf
                                <label for="code" class="form-label fw-bold">Mã xác minh</label>
                                <input id="code" name="code" value="{{ old('code') }}" class="form-control verification-code @error('code') is-invalid @enderror" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autofocus autocomplete="one-time-code">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <button type="submit" class="btn btn-auth-submit w-100 mt-4">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Xác minh tài khoản
                                </button>
                            </form>

                            <div class="d-grid gap-3">
                                <form method="POST" action="{{ route('verification.code.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-soft w-100">
                                        <i class="bi bi-arrow-clockwise me-2"></i>
                                        Gửi lại mã
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-secondary text-decoration-none w-100">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
