@extends('layouts.client')

@section('title', 'Đăng Ký')

@section('content')
<section class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="auth-card auth-card-register card border-0">
                    <div class="card-body p-4 p-md-5">
                        <img src="{{ asset('images/logo.png') }}" alt="Chill Drink Logo" class="brand-mark auth-brand-mark mb-3" style="background: white; object-fit: contain; padding: 2px;">
                        <h1 class="h3 fw-bold text-center mb-2">Tạo Tài Khoản</h1>
                        <p class="text-secondary text-center mb-4">Lưu thông tin liên hệ để đặt đồ uống và theo dõi đơn hàng thuận tiện hơn.</p>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="mb-4">
                                <span class="auth-section-label mb-3"><i class="bi bi-person-badge"></i>Thông tin tài khoản</span>
                                <div class="auth-form-grid">
                                    <div>
                                        <label for="name" class="form-label">Họ tên</label>
                                        <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required autocomplete="tel">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="full-span">
                                        <label for="email" class="form-label">Email</label>
                                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <span class="auth-section-label mb-3"><i class="bi bi-geo-alt"></i>Thông tin nhận hàng</span>
                                <div class="auth-form-grid">
                                    <div>
                                        <label for="area" class="form-label">Khu vực</label>
                                        <select id="area" name="area" class="form-select @error('area') is-invalid @enderror">
                                            <option value="">Chọn khu vực</option>
                                            @foreach(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ', 'Khác'] as $area)
                                                <option value="{{ $area }}" @selected(old('area') === $area)>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                        @error('area')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="address" class="form-label">Địa chỉ nhận hàng</label>
                                        <input id="address" type="text" name="address" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror" autocomplete="street-address">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <span class="auth-section-label mb-3"><i class="bi bi-shield-lock"></i>Bảo mật</span>
                                <div class="auth-form-grid">
                                    <div>
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
                                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            <div class="auth-note mb-4">
                                <i class="bi bi-check-circle me-1"></i>
                                Tài khoản mới sẽ dùng để xem lịch sử đơn hàng, cập nhật địa chỉ và nhận thông báo giao hàng.
                            </div>

                            <div class="form-check mb-4">
                                <input id="agree_terms" type="checkbox" class="form-check-input" required>
                                <label class="form-check-label" for="agree_terms">
                                    Tôi đồng ý với điều khoản mua hàng và chính sách bảo mật của Chill Drink.
                                </label>
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
