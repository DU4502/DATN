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
            radial-gradient(circle at 16% 18%, rgba(11, 198, 180, 0.16), transparent 28%),
            linear-gradient(180deg, #e9fbff 0%, #effdfa 46%, #ffffff 100%);
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
        width: min(360px, 86%);
        height: 265px;
        margin-top: 0.2rem;
    }

    .register-illustration::before {
        content: '';
        position: absolute;
        inset: 35px 18px 8px;
        border-radius: 38px;
        background: linear-gradient(145deg, rgba(32, 204, 185, 0.18), rgba(255, 255, 255, 0.8));
        transform: perspective(440px) rotateX(58deg) rotateZ(-7deg);
        box-shadow: 0 26px 42px rgba(10, 117, 108, 0.16);
    }

    .drink-card {
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
        padding: 1.85rem 2.1rem;
    }

    .register-card h2 {
        margin: 0 0 1rem;
        color: #1f2937;
        font-size: 1.35rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .register-form-stack {
        display: grid;
        gap: 0.72rem;
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

    .contact-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 116px;
        gap: 0.65rem;
    }

    .code-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.35rem;
    }

    .btn-code {
        height: 42px;
        border-radius: 4px;
        border: 1px solid #0d9373;
        background: #ffffff;
        color: #0d9373;
        font-size: 0.88rem;
        font-weight: 800;
        white-space: nowrap;
        padding: 0 0.65rem;
        transition: background 0.16s ease, color 0.16s ease, opacity 0.16s ease;
    }

    .btn-code:hover,
    .btn-code:focus,
    .btn-code.is-active {
        background: #0d9373;
        color: #ffffff;
    }

    .btn-code.is-phone {
        background: #087560;
    }

    .btn-code:disabled,
    .btn-code:disabled {
        opacity: 0.65;
    }

    .contact-hint {
        color: #0f9488;
        font-size: 0.76rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .contact-mode-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
        background: #ecfeff;
        color: #0f766e;
        font-size: 0.72rem;
        font-weight: 800;
        white-space: nowrap;
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

        .contact-row,
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
                <p>Nhập một ô duy nhất cho Gmail hoặc số điện thoại, hệ thống sẽ tự nhận diện và gửi mã xác minh phù hợp.</p>
                <div class="register-hero__badge">
                    1 ô duy nhất cho Gmail hoặc SĐT<br>
                    Mã tự kiểm tra khi nhập đủ 6 số
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

                    <input type="hidden" name="contact_type" value="{{ old('contact_type') }}" data-contact-type>
                    <input type="hidden" name="firebase_id_token" value="{{ old('firebase_id_token') }}" data-firebase-token>
                    <input type="hidden" name="firebase_uid" value="{{ old('firebase_uid') }}" data-firebase-uid>

                    <div class="contact-row">
                        <div>
                            <input id="contact" type="text" name="contact" value="{{ old('contact', old('email', old('phone'))) }}" class="form-control register-input @error('contact') is-invalid @enderror" placeholder="Vui lòng nhập Gmail hoặc số điện thoại" required autocomplete="username" inputmode="email" data-contact-input>
                            @error('contact') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="button" class="btn btn-code" data-send-code-url="{{ route('register.email-code.send') }}" data-contact-send>
                            Lấy mã
                        </button>
                    </div>

                    <div class="code-row">
                        <div>
                            <input id="email_verification_code" type="text" name="email_verification_code" value="{{ old('email_verification_code') }}" class="form-control register-input @error('email_verification_code') is-invalid @enderror" placeholder="Vui lòng nhập mã xác minh" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autocomplete="one-time-code" data-verification-code>
                            @error('email_verification_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="code-status" data-code-status></div>
                    <div id="register-phone-recaptcha" class="d-none"></div>

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
        const contactInput = form?.querySelector('[data-contact-input]');
        const contactTypeInput = form?.querySelector('[data-contact-type]');
        const sendButton = form?.querySelector('[data-contact-send]');
        const codeInput = form?.querySelector('[data-verification-code]');
        const contactHint = form?.querySelector('[data-contact-hint]');
        const contactModePill = form?.querySelector('[data-contact-mode-pill]');
        const status = form?.querySelector('[data-code-status]');
        const firebaseTokenInput = form?.querySelector('[data-firebase-token]');
        const firebaseUidInput = form?.querySelector('[data-firebase-uid]');
        const phoneRecaptcha = document.getElementById('register-phone-recaptcha');
        const emailVerifyUrl = sendButton?.dataset.sendCodeUrl;
        const firebaseConfig = @json(config('services.firebase.phone_auth.web_config'));
        let countdownTimer = null;
        let currentMode = null;
        let lastContactValue = '';
        let verified = false;
        let verifying = false;
        let confirmationResult = null;
        let recaptchaVerifier = null;
        let firebaseApp = null;
        let firebaseAuth = null;
        let firebaseLoaded = false;
        let autoVerifiedCode = '';

        if (!form || !contactInput || !contactTypeInput || !sendButton || !codeInput || !status) {
            return;
        }

        const setStatus = (message, type = '') => {
            status.textContent = message;
            status.classList.toggle('has-message', Boolean(message));
            status.classList.toggle('is-success', type === 'success');
            status.classList.toggle('is-error', type === 'error');
        };

        const normalizeVietnamPhone = (value) => {
            const compact = String(value || '').trim().replace(/[\s().-]/g, '');

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
        };

        const detectContactMode = (value) => {
            const text = String(value || '').trim();

            if (!text) {
                return null;
            }

            if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) {
                return 'email';
            }

            const phone = normalizeVietnamPhone(text);
            if (/^\+\d{9,15}$/.test(phone)) {
                return 'phone';
            }

            return null;
        };

        const loadFirebase = async () => {
            if (firebaseLoaded) {
                return true;
            }

            if (!firebaseConfig || Object.keys(firebaseConfig || {}).length === 0) {
                return false;
            }

            const [{ initializeApp }, { getAuth, RecaptchaVerifier, signInWithPhoneNumber }] = await Promise.all([
                import('https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js'),
                import('https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js'),
            ]);

            if (!firebaseApp) {
                firebaseApp = initializeApp(firebaseConfig);
            }

            firebaseAuth = getAuth(firebaseApp);
            firebaseAuth.languageCode = 'vi';
            window.__registerPhoneFirebase = { RecaptchaVerifier, signInWithPhoneNumber };
            firebaseLoaded = true;
            return true;
        };

        const ensureRecaptcha = async () => {
            const loaded = await loadFirebase();
            if (!loaded) {
                return null;
            }

            const { RecaptchaVerifier } = window.__registerPhoneFirebase || {};
            if (!recaptchaVerifier && RecaptchaVerifier && phoneRecaptcha) {
                recaptchaVerifier = new RecaptchaVerifier(firebaseAuth, phoneRecaptcha, {
                    size: 'invisible',
                });
            }

            return recaptchaVerifier;
        };

        const setModeUI = (mode) => {
            currentMode = mode;
            contactTypeInput.value = mode || '';

            if (contactModePill) {
                contactModePill.textContent = mode === 'phone' ? 'Nhận diện: SĐT' : mode === 'email' ? 'Nhận diện: Gmail' : '';
                contactModePill.classList.toggle('d-none', !mode);
            }

            if (contactHint) {
                contactHint.textContent = mode === 'phone'
                    ? 'Hệ thống sẽ gửi mã SMS tới số điện thoại này.'
                    : mode === 'email'
                        ? 'Hệ thống sẽ gửi mã xác minh tới Gmail này.'
                        : 'Nhập Gmail hoặc số điện thoại. Hệ thống sẽ tự nhận diện ngay khi bạn gõ.';
            }

            if (sendButton) {
                sendButton.textContent = mode === 'phone' ? 'Gửi mã SMS' : 'Lấy mã';
                sendButton.classList.toggle('is-phone', mode === 'phone');
            }

            if (contactInput) {
                contactInput.setAttribute('inputmode', mode === 'phone' ? 'tel' : 'email');
                contactInput.setAttribute('autocomplete', mode === 'phone' ? 'tel' : 'username');
            }
        };

        const resetVerification = (keepContact = true) => {
            verified = false;
            verifying = false;
            confirmationResult = null;
            autoVerifiedCode = '';

            if (firebaseTokenInput) firebaseTokenInput.value = '';
            if (firebaseUidInput) firebaseUidInput.value = '';
            if (!keepContact && contactInput) contactInput.value = '';
            codeInput.value = '';
            clearInterval(countdownTimer);
            countdownTimer = null;
            if (sendButton) {
                sendButton.disabled = false;
                sendButton.textContent = currentMode === 'phone' ? 'Gửi mã SMS' : 'Lấy mã';
            }
            if (codeInput) {
                codeInput.removeAttribute('readonly');
            }
        };

        const startCountdown = (seconds = 60) => {
            clearInterval(countdownTimer);
            let remaining = seconds;
            sendButton.disabled = true;
            sendButton.textContent = `${remaining}s`;

            countdownTimer = setInterval(() => {
                remaining -= 1;
                sendButton.textContent = remaining > 0 ? `${remaining}s` : (currentMode === 'phone' ? 'Gửi mã SMS' : 'Lấy mã');

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

        const syncMode = () => {
            const nextMode = detectContactMode(contactInput.value);

            if (contactInput.value.trim() !== lastContactValue) {
                resetVerification(true);
                lastContactValue = contactInput.value.trim();
            }

            setModeUI(nextMode);
            return nextMode;
        };

        contactInput.addEventListener('input', () => {
            syncMode();
        });

        codeInput.addEventListener('input', () => {
            const digits = codeInput.value.replace(/\D/g, '').slice(0, 6);
            if (codeInput.value !== digits) {
                codeInput.value = digits;
            }

            if (digits.length < 6) {
                autoVerifiedCode = '';
                return;
            }

            if (verifying || verified || autoVerifiedCode === digits) {
                return;
            }

            autoVerifiedCode = digits;
            verifyCode(digits);
        });

        async function verifyCode(code) {
            const mode = syncMode();
            if (!mode) {
                setStatus('Vui lòng nhập Gmail hoặc số điện thoại hợp lệ.', 'error');
                return;
            }

            verifying = true;

            try {
                if (mode === 'email') {
                    const response = await fetch('{{ route('register.email-code.verify') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify({
                            email: contactInput.value.trim().toLowerCase(),
                            email_verification_code: code,
                        }),
                    });

                    if (!response.ok) {
                        autoVerifiedCode = '';
                        setStatus(await getErrorMessage(response), 'error');
                        return;
                    }

                    const data = await response.json();
                    verified = true;
                    setStatus(data.message || 'Gmail đã được xác minh.', 'success');
                    return;
                }

                if (mode === 'phone') {
                    if (!confirmationResult) {
                        autoVerifiedCode = '';
                        setStatus('Vui lòng lấy mã SMS trước khi xác minh.', 'error');
                        return;
                    }

                    const result = await confirmationResult.confirm(code);
                    const token = await result.user.getIdToken(true);

                    if (firebaseTokenInput) firebaseTokenInput.value = token;
                    if (firebaseUidInput) firebaseUidInput.value = result.user.uid || '';
                    verified = true;
                    setStatus('Số điện thoại đã được xác minh.', 'success');
                    return;
                }
            } catch (error) {
                autoVerifiedCode = '';
                setStatus(error?.message || 'Không thể xác minh mã. Vui lòng thử lại.', 'error');
            } finally {
                verifying = false;
            }
        }

        sendButton.addEventListener('click', async () => {
            const mode = syncMode();
            const contactValue = contactInput.value.trim();

            if (!mode) {
                setStatus('Vui lòng nhập Gmail hoặc số điện thoại hợp lệ trước khi lấy mã.', 'error');
                contactInput.focus();
                return;
            }

            if (mode === 'email') {
                sendButton.disabled = true;
                sendButton.textContent = 'Đang gửi...';
                setStatus('Đang gửi mã xác minh Gmail...');

                try {
                    const response = await fetch(emailVerifyUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify({ email: contactValue.toLowerCase() }),
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
                    return;
                } catch (error) {
                    setStatus('Không thể gửi mã. Kiểm tra kết nối hoặc cấu hình mail.', 'error');
                    clearInterval(countdownTimer);
                    sendButton.textContent = 'Lấy mã';
                    sendButton.disabled = false;
                    return;
                }
            }

            const normalizedPhone = normalizeVietnamPhone(contactValue);
            if (!/^\+\d{9,15}$/.test(normalizedPhone)) {
                setStatus('Số điện thoại không hợp lệ. Vui lòng nhập dạng 0988832678 hoặc +84988832678.', 'error');
                contactInput.focus();
                return;
            }

            setStatus('Đang gửi mã SMS...');

            try {
                const recaptcha = await ensureRecaptcha();
                if (!recaptcha || !firebaseAuth) {
                    setStatus('Chức năng SMS chưa được cấu hình đầy đủ. Vui lòng dùng Gmail.', 'error');
                    return;
                }

                const { signInWithPhoneNumber } = window.__registerPhoneFirebase || {};
                if (!signInWithPhoneNumber) {
                    setStatus('Không tải được Firebase SMS. Vui lòng thử lại sau.', 'error');
                    return;
                }

                const confirmation = await signInWithPhoneNumber(firebaseAuth, normalizedPhone, recaptcha);
                confirmationResult = confirmation;
                contactInput.value = normalizedPhone;
                lastContactValue = normalizedPhone;
                setModeUI('phone');
                setStatus(`Đã gửi mã SMS tới ${normalizedPhone}. Nhập đủ 6 số để xác minh tự động.`, 'success');
                startCountdown(60);
                codeInput.focus();
            } catch (error) {
                console.error('Phone verification send failed:', error);
                setStatus(error.message || 'Không thể gửi mã SMS. Vui lòng thử lại.', 'error');
                clearInterval(countdownTimer);
                sendButton.textContent = 'Gửi mã SMS';
                sendButton.disabled = false;
            }
        });

        form.addEventListener('submit', (event) => {
            const mode = syncMode();
            if (!mode) {
                event.preventDefault();
                setStatus('Vui lòng nhập Gmail hoặc số điện thoại hợp lệ.', 'error');
                contactInput.focus();
                return;
            }

            if (!verified) {
                event.preventDefault();
                setStatus('Vui lòng xác minh mã trước khi đăng ký.', 'error');
                codeInput.focus();
                return;
            }
        });

        syncMode();
    });
</script>
@endsection
