@extends('layouts.client')

@section('title', 'Đăng Ký')

@section('content')
<style>
    .register-page {
        min-height: calc(100vh - 80px);
        display: grid;
        place-items: center;
        padding: 1rem;
        overflow: hidden;
        background:
            linear-gradient(180deg, rgba(255, 251, 232, 0.32), rgba(255, 248, 212, 0.2)),
            url('{{ asset('images/auth-register-mango.png') }}') center / cover no-repeat;
    }

    .register-shell {
        width: min(1120px, calc(100vw - 2rem), calc((100dvh - 120px) * 16 / 9));
        aspect-ratio: 16 / 9;
        display: grid;
        grid-template-columns: 0.92fr 1fr;
        gap: 2.25rem;
        align-items: center;
    }

    .register-hero {
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: #0f766e;
    }

    .register-hero h1 {
        margin: 0 0 0.75rem;
        max-width: 24rem;
        color: #16b9ad;
        font-size: 2.05rem;
        line-height: 1.18;
        font-weight: 900;
        letter-spacing: 0;
    }

    .register-hero p {
        margin: 0;
        max-width: 23rem;
        color: #0f9488;
        font-size: 1rem;
        line-height: 1.48;
        font-weight: 700;
    }

    .register-hero__badge {
        width: fit-content;
        margin: 1rem 0 1.1rem;
        padding: 0.55rem 0.8rem;
        border-radius: 0.7rem;
        background: #22cbb9;
        color: #ffffff;
        font-size: 0.78rem;
        line-height: 1.3;
        font-weight: 800;
        box-shadow: 0 10px 22px rgba(17, 166, 150, 0.22);
    }

    .register-illustration {
        position: relative;
        width: min(390px, 92%);
        height: 250px;
        margin-top: 0.2rem;
        overflow: hidden;
        border-radius: 24px;
        background: url('{{ asset('images/auth-register-mango.png') }}') center / cover no-repeat;
        box-shadow: 0 22px 44px rgba(12, 132, 121, 0.22);
    }

    .register-illustration::before {
        display: none;
    }

    .drink-card {
        display: none;
        position: absolute;
        left: 78px;
        top: 12px;
        width: 205px;
        height: 205px;
        border-radius: 24px;
        background: linear-gradient(145deg, rgba(47, 220, 197, 0.96), rgba(189, 255, 238, 0.84));
        box-shadow: 0 22px 44px rgba(12, 132, 121, 0.22);
        transform: rotate(-4deg);
        overflow: hidden;
    }

    .drink-card::before {
        content: '';
        position: absolute;
        inset: 18px 24px 38px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.62);
    }

    .drink-card img {
        position: absolute;
        left: 35px;
        bottom: 5px;
        width: 135px;
        height: 160px;
        object-fit: contain;
        filter: drop-shadow(0 16px 18px rgba(9, 91, 84, 0.2));
    }

    .channel-dot {
        display: none;
        position: absolute;
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 12px 26px rgba(13, 121, 111, 0.15);
        color: #09a28f;
        font-weight: 900;
    }

    .channel-dot:nth-child(2) {
        left: 22px;
        top: 72px;
    }

    .channel-dot:nth-child(3) {
        right: 12px;
        top: 62px;
    }

    .channel-dot:nth-child(4) {
        left: 48px;
        bottom: 28px;
    }

    .channel-dot:nth-child(5) {
        right: 54px;
        bottom: 24px;
    }

    .register-card {
        width: 100%;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.82);
        box-shadow: 0 20px 45px rgba(16, 80, 74, 0.12);
    }

    .register-card__body {
        padding: 2rem 2.35rem;
    }

    .register-card h2 {
        margin: 0 0 1.35rem;
        color: #1f2937;
        font-size: 1.35rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .register-form-stack {
        display: grid;
        gap: 0.78rem;
    }

    .register-input {
        height: 42px;
        border-radius: 4px;
        border: 1px solid #dfe5ea;
        color: #1f2937;
        font-size: 0.92rem;
        box-shadow: none;
    }

    .register-input:focus {
        border-color: #0d9373;
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.12);
    }

    .email-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 72px;
        gap: 0.65rem;
    }

    .code-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 110px;
        gap: 0.78rem;
    }

    .btn-code,
    .btn-verify-code {
        height: 42px;
        border-radius: 4px;
        border: 1px solid #0d9373;
        background: #ffffff;
        color: #0d9373;
        font-size: 0.88rem;
        font-weight: 800;
        white-space: nowrap;
        padding: 0 0.55rem;
        transition: background 0.16s ease, color 0.16s ease, opacity 0.16s ease;
    }

    .btn-verify-code {
        background: #0d9373;
        color: #ffffff;
    }

    .btn-code:hover,
    .btn-code:focus,
    .btn-verify-code:hover,
    .btn-verify-code:focus {
        background: #0d9373;
        color: #ffffff;
    }

    .btn-verify-code:hover,
    .btn-verify-code:focus {
        background: #087560;
    }

    .btn-code:disabled,
    .btn-verify-code:disabled {
        opacity: 0.65;
    }

    .code-status {
        display: none;
        min-height: 1rem;
        color: #7a8794;
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .code-status.has-message {
        display: block;
    }

    .code-status.is-success {
        color: #0d9373;
        font-weight: 700;
    }

    .code-status.is-error {
        color: #dc3545;
        font-weight: 700;
    }

    .register-check {
        display: flex;
        align-items: flex-start;
        gap: 0.48rem;
        margin: 0.1rem 0;
        color: #667085;
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .register-check .form-check-input {
        margin: 0.08rem 0 0;
        flex: 0 0 auto;
    }

    .form-check-input:checked {
        background-color: #0d9373;
        border-color: #0d9373;
    }

    .register-check a,
    .register-login a {
        color: #0d9373;
        font-weight: 800;
        text-decoration: none;
    }

    .btn-register {
        height: 44px;
        border: 0;
        border-radius: 4px;
        background: #0d9373;
        color: #ffffff;
        font-size: 0.94rem;
        font-weight: 800;
        transition: transform 0.16s ease, background 0.16s ease;
    }

    .btn-register:hover,
    .btn-register:focus {
        background: #087560;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .register-login {
        margin: 0.65rem 0 0;
        color: #667085;
        font-size: 0.84rem;
    }

    .register-divider {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin: 1rem 0;
        color: #9aa4b2;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .register-divider::before,
    .register-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        background: #e5eaf0;
    }

    .google-btn {
        height: 42px;
        border-radius: 4px;
        border: 1px solid #dfe5ea;
        background: #ffffff;
        color: #344054;
        font-size: 0.9rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        width: 100%;
    }

    .invalid-feedback {
        font-size: 0.78rem;
    }

    @media (max-width: 991.98px) {
        .register-page {
            overflow: visible;
        }

        .register-shell {
            width: min(720px, calc(100vw - 1rem));
            aspect-ratio: auto;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            border-radius: 22px;
        }

        .register-hero {
            min-height: 330px;
            align-items: center;
            text-align: center;
        }

        .register-hero h1,
        .register-hero p {
            max-width: 28rem;
        }

    }

    @media (max-width: 575.98px) {
        .register-page {
            padding: 0.5rem;
        }

        .register-card__body {
            padding: 1.35rem;
        }

        .email-row,
        .code-row {
            grid-template-columns: 1fr;
        }

        .register-illustration {
            width: 100%;
            transform: scale(0.88);
        }

    }
</style>

<section class="register-page">
    <div class="register-shell">
        <aside class="register-hero" aria-label="Giới thiệu Chill Drink">
            <div>
                <h1>Đăng ký tài khoản Chill Drink thật nhanh</h1>
                <p>Xác minh bằng Gmail để đặt đồ uống, lưu ưu đãi và theo dõi đơn hàng của bạn.</p>
                <div class="register-hero__badge">
                    Mã xác minh gửi qua Gmail<br>
                    Không dùng xác minh số điện thoại
                </div>
            </div>

            <div class="register-illustration" aria-hidden="true">
                <div class="drink-card">
                    <img src="{{ asset('images/trasua.png') }}" alt="">
                </div>
                <div class="channel-dot"><i class="bi bi-cup-straw"></i></div>
                <div class="channel-dot"><i class="bi bi-envelope-check"></i></div>
                <div class="channel-dot"><i class="bi bi-bag-heart"></i></div>
                <div class="channel-dot"><i class="bi bi-shield-check"></i></div>
            </div>
        </aside>

        <section class="register-card">
            <div class="register-card__body">
                <h2>Đăng ký tài khoản</h2>

                @if(session('oauth_error'))
                    <div class="alert alert-danger d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ session('oauth_error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="register-form-stack" data-register-form>
                    @csrf

                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control register-input @error('name') is-invalid @enderror" placeholder="Vui lòng nhập họ và tên" required autofocus autocomplete="name">
                    @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control register-input @error('phone') is-invalid @enderror" placeholder="Vui lòng nhập số điện thoại" required autocomplete="tel">
                    @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <div class="email-row">
                        <div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control register-input @error('email') is-invalid @enderror" placeholder="Vui lòng nhập Gmail" required autocomplete="username">
                            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="button" class="btn btn-code" data-send-code-url="{{ route('register.email-code.send') }}">
                            Lấy mã
                        </button>
                    </div>

                    <div class="code-row">
                        <div>
                            <input id="email_verification_code" type="text" name="email_verification_code" value="{{ old('email_verification_code') }}" class="form-control register-input @error('email_verification_code') is-invalid @enderror" placeholder="Vui lòng nhập mã xác minh Gmail" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autocomplete="one-time-code">
                            @error('email_verification_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="button" class="btn btn-verify-code" data-verify-code-url="{{ route('register.email-code.verify') }}">
                            Xác minh
                        </button>
                    </div>

                    <div class="code-status" data-code-status></div>

                    <input id="password" type="password" name="password" class="form-control register-input @error('password') is-invalid @enderror" placeholder="Vui lòng nhập mật khẩu" required autocomplete="new-password">
                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control register-input" placeholder="Xác nhận mật khẩu" required autocomplete="new-password">

                    <label class="register-check" for="terms">
                        <input id="terms" name="terms" type="checkbox" value="1" class="form-check-input @error('terms') is-invalid @enderror" required @checked(old('terms'))>
                        <span>
                            Tôi đã đọc và đồng ý với <a href="#">Thỏa thuận sử dụng dịch vụ</a>, <a href="#">Chính sách quyền riêng tư</a>.
                            @error('terms') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                        </span>
                    </label>

                    <button type="submit" class="btn btn-register">
                        Đăng ký ngay
                    </button>
                </form>

                <p class="register-login">
                    Đã có tài khoản, ngay <a href="{{ route('login') }}">Đăng nhập</a>
                </p>

                <div class="register-divider">Hoặc</div>

                <a href="{{ route('auth.google.redirect') }}" class="btn google-btn">
                    <i class="bi bi-google text-danger fs-5"></i>
                    Tiếp tục với Google
                </a>
            </div>
        </section>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-register-form]');
        const emailInput = form?.querySelector('input[name="email"]');
        const sendButton = form?.querySelector('[data-send-code-url]');
        const verifyButton = form?.querySelector('[data-verify-code-url]');
        const codeInput = form?.querySelector('input[name="email_verification_code"]');
        const status = form?.querySelector('[data-code-status]');
        let countdownTimer = null;

        if (!form || !emailInput || !sendButton || !verifyButton || !codeInput || !status) {
            return;
        }

        const setStatus = (message, type = '') => {
            status.textContent = message;
            status.classList.toggle('has-message', Boolean(message));
            status.classList.toggle('is-success', type === 'success');
            status.classList.toggle('is-error', type === 'error');
        };

        const startCountdown = (seconds = 60) => {
            clearInterval(countdownTimer);
            let remaining = seconds;
            sendButton.disabled = true;
            sendButton.textContent = `${remaining}s`;

            countdownTimer = setInterval(() => {
                remaining -= 1;
                sendButton.textContent = remaining > 0 ? `${remaining}s` : 'Lấy mã';

                if (remaining <= 0) {
                    clearInterval(countdownTimer);
                    sendButton.disabled = false;
                }
            }, 1000);
        };

        const getErrorMessage = async (response) => {
            try {
                const data = await response.json();
                const errors = data.errors || {};
                const firstError = Object.values(errors)[0];
                return Array.isArray(firstError) ? firstError[0] : (data.message || 'Không thể gửi mã xác minh.');
            } catch (error) {
                return 'Không thể gửi mã xác minh.';
            }
        };

        sendButton.addEventListener('click', async () => {
            if (!emailInput.value.trim()) {
                emailInput.focus();
                setStatus('Vui lòng nhập Gmail trước khi lấy mã.', 'error');
                return;
            }

            sendButton.disabled = true;
            sendButton.textContent = 'Đang gửi...';
            setStatus('Đang gửi mã xác minh...');

            try {
                const response = await fetch(sendButton.dataset.sendCodeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({ email: emailInput.value.trim() }),
                });

                if (!response.ok) {
                    setStatus(await getErrorMessage(response), 'error');
                    sendButton.textContent = 'Lấy mã';
                    sendButton.disabled = false;
                    return;
                }

                const data = await response.json();
                setStatus(data.message || 'Mã xác minh đã được gửi. Vui lòng kiểm tra Gmail.', 'success');
                startCountdown(60);
                codeInput.focus();
            } catch (error) {
                setStatus('Không thể gửi mã. Kiểm tra kết nối hoặc cấu hình mail.', 'error');
                clearInterval(countdownTimer);
                sendButton.textContent = 'Lấy mã';
                sendButton.disabled = false;
            }
        });

        verifyButton.addEventListener('click', async () => {
            if (!emailInput.value.trim()) {
                emailInput.focus();
                setStatus('Vui lòng nhập Gmail trước khi xác minh.', 'error');
                return;
            }

            if (!codeInput.value.trim()) {
                codeInput.focus();
                setStatus('Vui lòng nhập mã xác minh.', 'error');
                return;
            }

            verifyButton.disabled = true;
            setStatus('Đang xác minh Gmail...');

            try {
                const response = await fetch(verifyButton.dataset.verifyCodeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        email: emailInput.value.trim(),
                        email_verification_code: codeInput.value.trim(),
                    }),
                });

                if (!response.ok) {
                    setStatus(await getErrorMessage(response), 'error');
                    return;
                }

                const data = await response.json();
                setStatus(data.message || 'Gmail đã được xác minh.', 'success');
            } catch (error) {
                setStatus('Không thể xác minh mã. Vui lòng thử lại.', 'error');
            } finally {
                verifyButton.disabled = false;
            }
        });
    });
</script>
@endsection
