@extends('layouts.client')

@section('title', 'Đăng Nhập')

@section('content')
<style>
    .auth-page {
        min-height: calc(100dvh - 80px);
        display: flex;
        align-items: center;
        padding: 1rem 0;
        overflow: hidden;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0.34) 46%, rgba(255, 255, 255, 0.08) 100%),
            url('{{ asset("images/auth-login-matcha.png") }}') center/cover no-repeat;
        position: relative;
    }
    .auth-page::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(0, 139, 122, 0.10) 0%, rgba(255, 246, 225, 0.22) 100%);
    }
    
    .auth-container { position: relative; z-index: 1; padding: 0; width: 100%; }

    .auth-card {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: var(--radius-2xl);
        box-shadow: 0 24px 58px rgba(12, 54, 47, 0.22);
        overflow: hidden;
    }

    .auth-header {
        text-align: center; margin-bottom: 1rem;
    }
    .auth-logo {
        width: 56px; height: 56px; border-radius: var(--radius-lg);
        background: #fff; display: inline-flex; align-items: center; justify-content: center;
        box-shadow: var(--shadow-md); margin-bottom: .85rem; border: 1px solid var(--c-border);
    }
    .auth-logo img { width: 38px; height: 38px; object-fit: contain; }

    .form-floating > .form-control {
        border-radius: var(--radius-md); border-color: var(--c-border);
    }
    .form-floating > .form-control:focus {
        border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-glow);
    }

    .btn-auth-submit {
        height: 48px; border-radius: var(--radius-md); font-size: 0.98rem; font-weight: 700;
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        border: none; color: #fff; box-shadow: 0 8px 16px rgba(13,147,115,0.3); transition: all 0.3s ease;
    }
    .btn-auth-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 20px rgba(13,147,115,0.4); color: #fff; }

    .auth-divider { color: var(--c-subtle); margin: 1rem 0; font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; }
    
    .social-btn {
        height: 44px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.92rem;
        background: var(--c-bg); border: 1.5px solid var(--c-border); color: var(--c-ink-secondary);
        display: flex; align-items: center; justify-content: center; gap: 0.75rem; transition: all 0.2s;
    }
    .social-btn:hover { background: var(--c-surface); transform: translateY(-2px); box-shadow: var(--shadow-sm); }

    .auth-form-stack .form-floating.mb-3 { margin-bottom: 0.75rem !important; }
    .auth-form-stack .form-floating.mb-4 { margin-bottom: 0.9rem !important; }
    .auth-form-stack .d-flex.justify-content-between.align-items-center.mb-4 {
        margin-bottom: 0.9rem !important;
    }
    .auth-social-stack { gap: 0.75rem !important; margin-bottom: 0.85rem !important; }

    @media (min-width: 992px) {
        .auth-page { padding: 0.75rem 0; }
        .auth-card .p-4.p-md-5 { padding: 1.35rem 1.5rem !important; }
    }

    @media (max-width: 991.98px) {
        .auth-page { padding: 1rem 0; overflow-y: auto; }
        .auth-container { padding: 1rem 0; }
        .auth-card .p-4.p-md-5 { padding: 1.5rem !important; }
    }

    @media (max-width: 575.98px) {
        .auth-page { min-height: calc(100dvh - 64px); padding: 0.5rem 0; }
        .auth-container { padding: 0.35rem 0; }
        .auth-card { border-radius: 16px; }
        .auth-card .p-4.p-md-5 { padding: 1rem !important; }
        .auth-header { margin-bottom: 0.7rem; }
        .auth-logo { width: 44px; height: 44px; margin-bottom: 0.5rem; }
        .auth-logo img { width: 30px; height: 30px; }
        .auth-header p { margin-bottom: 0.4rem; font-size: 0.8rem; }
        .auth-form-stack .form-floating.mb-3,
        .auth-form-stack .form-floating.mb-4 { margin-bottom: 0.55rem !important; }
        .auth-form-stack .d-flex.justify-content-between.align-items-center.mb-4 { margin-bottom: 0.6rem !important; }
        .btn-auth-submit { height: 42px; }
        .auth-divider { margin: 0.65rem 0; }
        .auth-social-stack { gap: 0.45rem !important; margin-bottom: 0.55rem !important; }
        .social-btn { height: 40px; font-size: 0.82rem; }
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
                                <div class="auth-logo"><img src="{{ asset('images/logo.png') }}" alt="Chill Drink"></div>
                                <h1 class="h3 fw-bold mb-2">Đăng nhập tài khoản</h1>
                                <p class="text-secondary">Chào mừng bạn trở lại với Chill Drink</p>
                            </div>

                            @if(session('status'))
                                <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> {{ __((string) session('status')) }}</div>
                            @endif
                            @if(session('error'))
                                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}</div>
                            @endif

                            @if(session('oauth_error'))
                                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('oauth_error') }}</div>
                            @endif

                            <form method="POST" action="{{ route('login') }}" class="auth-form-stack">
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

                            <div class="d-flex flex-column auth-social-stack mb-4">
                                <a href="{{ route('auth.google.redirect') }}" class="btn social-btn"><i class="bi bi-google text-danger fs-5"></i> Tiếp tục với Google</a>
                                <button type="button" class="btn social-btn" data-bs-toggle="modal" data-bs-target="#phoneAuthModal">
                                    <i class="bi bi-phone-vibrate-fill text-success fs-5"></i> Đăng nhập bằng SMS OTP
                                </button>
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

<div class="modal fade" id="phoneAuthModal" tabindex="-1" aria-labelledby="phoneAuthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="phoneAuthModalLabel">
                    <i class="bi bi-phone text-success me-2"></i> Đăng nhập bằng SMS OTP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div id="phone-auth-error" class="alert alert-danger d-none mb-3"></div>
                <div id="phone-auth-success" class="alert alert-success d-none mb-3"></div>

                <div id="phone-auth-phone-section">
                    <div class="form-floating mb-3">
                        <input type="tel" id="phone-auth-phone" class="form-control form-control-lg" placeholder="0945606336" autocomplete="tel">
                        <label for="phone-auth-phone">Số điện thoại</label>
                    </div>
                    <div id="phone-auth-recaptcha"></div>
                    <button type="button" id="phone-auth-send" class="btn btn-auth-submit w-100">
                        Gửi mã OTP
                    </button>
                </div>

                <div id="phone-auth-otp-section" class="d-none">
                    <div class="form-floating mb-3">
                        <input type="text" id="phone-auth-otp" class="form-control form-control-lg text-center" placeholder="123456" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="letter-spacing: 0.3rem;">
                        <label for="phone-auth-otp">Mã OTP 6 chữ số</label>
                    </div>
                    <button type="button" id="phone-auth-verify" class="btn btn-auth-submit w-100">
                        Xác nhận và đăng nhập
                    </button>
                    <button type="button" id="phone-auth-reset" class="btn btn-link w-100 text-secondary text-decoration-none mt-2">
                        Đổi số điện thoại
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
    import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js";

    const firebaseConfig = @json(config('services.firebase.phone_auth.web_config'));
    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    auth.languageCode = 'vi';

    const phoneInput = document.getElementById('phone-auth-phone');
    const otpInput = document.getElementById('phone-auth-otp');
    const sendButton = document.getElementById('phone-auth-send');
    const verifyButton = document.getElementById('phone-auth-verify');
    const resetButton = document.getElementById('phone-auth-reset');
    const phoneSection = document.getElementById('phone-auth-phone-section');
    const otpSection = document.getElementById('phone-auth-otp-section');
    const errorBox = document.getElementById('phone-auth-error');
    const successBox = document.getElementById('phone-auth-success');
    const modal = document.getElementById('phoneAuthModal');
    let recaptchaVerifier = null;
    let confirmationResult = null;
    let verifiedPhone = '';

    function normalizeVietnamPhone(value) {
        const compact = value.trim().replace(/[\s().-]/g, '');

        if (compact.startsWith('+')) {
            return compact;
        }

        if (compact.startsWith('0')) {
            return '+84' + compact.slice(1);
        }

        if (compact.startsWith('84')) {
            return '+' + compact;
        }

        return '+84' + compact;
    }

    function showMessage(type, message) {
        errorBox.classList.toggle('d-none', type !== 'error');
        successBox.classList.toggle('d-none', type !== 'success');

        if (type === 'error') {
            errorBox.textContent = message;
        }

        if (type === 'success') {
            successBox.textContent = message;
        }
    }

    function firebasePhoneErrorMessage(error) {
        const code = error?.code || '';

        const messages = {
            'auth/billing-not-enabled': 'Firebase chưa bật billing nên chưa thể gửi SMS OTP. Vui lòng bật gói Blaze/billing cho Firebase project rồi thử lại.',
            'auth/operation-not-allowed': 'Chức năng đăng nhập bằng số điện thoại chưa được bật hoặc Việt Nam (+84) chưa nằm trong SMS Region Policy.',
            'auth/invalid-phone-number': 'Số điện thoại không hợp lệ. Vui lòng nhập dạng 0988832678 hoặc +84988832678.',
            'auth/too-many-requests': 'Bạn đã yêu cầu OTP quá nhiều lần. Vui lòng chờ vài phút rồi thử lại.',
            'auth/quota-exceeded': 'Firebase đã hết hạn mức gửi SMS trong thời điểm này. Vui lòng thử lại sau.',
            'auth/captcha-check-failed': 'Xác minh reCAPTCHA không thành công. Vui lòng tải lại trang và thử lại.',
            'auth/invalid-app-credential': 'Firebase không xác thực được website hiện tại. Hãy kiểm tra Authorized domains trong Firebase Console.',
            'auth/missing-app-credential': 'Thiếu xác minh reCAPTCHA. Vui lòng tải lại trang và thử lại.',
        };

        return messages[code] || 'Không thể gửi mã OTP lúc này. Vui lòng kiểm tra số điện thoại hoặc thử lại sau.';
    }

    function setBusy(button, busy) {
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function ensureRecaptcha() {
        if (!recaptchaVerifier) {
            recaptchaVerifier = new RecaptchaVerifier(auth, 'phone-auth-recaptcha', {
                size: 'invisible'
            });
        }

        return recaptchaVerifier;
    }

    function resetPhoneAuth() {
        confirmationResult = null;
        verifiedPhone = '';
        otpInput.value = '';
        phoneSection.classList.remove('d-none');
        otpSection.classList.add('d-none');
        errorBox.classList.add('d-none');
        successBox.classList.add('d-none');
    }

    sendButton.addEventListener('click', async function () {
        const phone = normalizeVietnamPhone(phoneInput.value);

        if (!/^\+\d{9,15}$/.test(phone)) {
            showMessage('error', 'Số điện thoại không hợp lệ. Ví dụ: 0945606336 hoặc +84945606336.');
            return;
        }

        setBusy(sendButton, true);
        showMessage('success', 'Đang gửi mã OTP...');

        try {
            confirmationResult = await signInWithPhoneNumber(auth, phone, ensureRecaptcha());
            verifiedPhone = phone;
            phoneSection.classList.add('d-none');
            otpSection.classList.remove('d-none');
            otpInput.focus();
            showMessage('success', 'Mã OTP đã được gửi. Vui lòng kiểm tra tin nhắn.');
        } catch (error) {
            if (recaptchaVerifier) {
                recaptchaVerifier.clear();
                recaptchaVerifier = null;
            }

            showMessage('error', firebasePhoneErrorMessage(error));
        } finally {
            setBusy(sendButton, false);
        }
    });

    verifyButton.addEventListener('click', async function () {
        const code = otpInput.value.trim();

        if (!confirmationResult || !/^\d{6}$/.test(code)) {
            showMessage('error', 'Vui lòng nhập đúng mã OTP 6 chữ số.');
            return;
        }

        setBusy(verifyButton, true);
        showMessage('success', 'Đang xác minh mã OTP...');

        try {
            const result = await confirmationResult.confirm(code);
            const idToken = await result.user.getIdToken();
            const response = await fetch("{{ route('auth.phone.verify') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    phone_number: verifiedPhone,
                    firebase_uid: result.user.uid,
                    firebase_id_token: idToken
                })
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Đăng nhập bằng SMS thất bại.');
            }

            showMessage('success', data.message || 'Đăng nhập thành công.');
            window.location.href = data.redirect || "{{ route('home') }}";
        } catch (error) {
            showMessage('error', error.message || 'Mã OTP không chính xác hoặc đã hết hạn.');
        } finally {
            setBusy(verifyButton, false);
        }
    });

    resetButton.addEventListener('click', resetPhoneAuth);
    modal.addEventListener('hidden.bs.modal', resetPhoneAuth);
</script>
@endsection
