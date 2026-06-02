@extends('layouts.client')

@section('title', 'Đăng Ký')

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
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(0, 139, 122, 0.10) 0%, rgba(255, 246, 225, 0.22) 100%);
    }

    .auth-container { position: relative; z-index: 1; padding: 4rem 0; width: 100%; }

    .auth-card {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: var(--radius-2xl);
        box-shadow: 0 24px 58px rgba(12, 54, 47, 0.22);
        overflow: hidden;
    }

    .auth-header { text-align: center; margin-bottom: 2.5rem; }
    .auth-logo {
        width: 64px; height: 64px; border-radius: var(--radius-lg);
        background: #fff; display: inline-flex; align-items: center; justify-content: center;
        box-shadow: var(--shadow-md); margin-bottom: 1.5rem; border: 1px solid var(--c-border);
    }
    .auth-logo img { width: 44px; height: 44px; object-fit: contain; }

    .form-floating > .form-control, .form-floating > .form-select {
        border-radius: var(--radius-md); border-color: var(--c-border);
    }
    .form-floating > .form-control:focus, .form-floating > .form-select:focus {
        border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-glow);
    }

    .btn-auth-submit {
        height: 54px; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700;
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        border: none; color: #fff; box-shadow: 0 8px 16px rgba(13,147,115,0.3); transition: all 0.3s ease;
    }
    .btn-auth-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 20px rgba(13,147,115,0.4); color: #fff; }

    .auth-section-title {
        color: var(--c-primary); font-size: 0.8125rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .auth-section-title i { font-size: 1.1rem; }

    .form-check-input:checked { background-color: var(--c-primary); border-color: var(--c-primary); }
</style>

<section class="auth-page">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-9 col-lg-7 col-xl-6">
                    <div class="auth-card">
                        <div class="p-4 p-md-5">
                            <div class="auth-header">
                                <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Chill Drink"></div>
                                <h1 class="h3 fw-bold mb-2">Tạo tài khoản mới</h1>
                                <p class="text-secondary">Điền thông tin để bắt đầu đặt đồ uống siêu tốc</p>
                            </div>

                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                <div class="row g-3 mb-4">
                                    <div class="col-12"><div class="auth-section-title"><i class="bi bi-person-vcard"></i> Thông tin cá nhân</div></div>
                                    <div class="col-sm-6">
                                        <div class="form-floating">
                                            <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Nguyễn Văn A" required autofocus autocomplete="name">
                                            <label for="name">Họ và tên</label>
                                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-floating">
                                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0901234567" required autocomplete="tel">
                                            <label for="phone">Số điện thoại</label>
                                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required autocomplete="username">
                                            <label for="email">Địa chỉ Email</label>
                                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12"><div class="auth-section-title"><i class="bi bi-geo-alt"></i> Giao hàng</div></div>
                                    <div class="col-sm-5">
                                        <div class="form-floating">
                                            <select id="area" name="area" class="form-select @error('area') is-invalid @enderror">
                                                <option value="" disabled selected>Chọn khu vực...</option>
                                                @foreach(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ', 'Khác'] as $area)
                                                    <option value="{{ $area }}" @selected(old('area') === $area)>{{ $area }}</option>
                                                @endforeach
                                            </select>
                                            <label for="area">Khu vực</label>
                                            @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-7">
                                        <div class="form-floating">
                                            <input id="address" type="text" name="address" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror" placeholder="Tầng 10, Tòa nhà A..." autocomplete="street-address">
                                            <label for="address">Địa chỉ chi tiết (Không bắt buộc)</label>
                                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12"><div class="auth-section-title"><i class="bi bi-shield-lock"></i> Bảo mật</div></div>
                                    <div class="col-sm-6">
                                        <div class="form-floating">
                                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mật khẩu" required autocomplete="new-password">
                                            <label for="password">Mật khẩu</label>
                                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-floating">
                                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu" required autocomplete="new-password">
                                            <label for="password_confirmation">Xác nhận mật khẩu</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input id="agree_terms" type="checkbox" class="form-check-input" required>
                                    <label class="form-check-label text-secondary small" for="agree_terms">
                                        Tôi đã đọc và đồng ý với <a href="#" class="text-primary text-decoration-none fw-semibold">Điều khoản dịch vụ</a> và <a href="#" class="text-primary text-decoration-none fw-semibold">Chính sách bảo mật</a> của Chill Drink.
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-auth-submit w-100">Hoàn Tất Đăng Ký</button>
                            </form>

                            <p class="text-center text-secondary mb-0 mt-4">
                                Đã có tài khoản? <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none ms-1">Đăng nhập</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
