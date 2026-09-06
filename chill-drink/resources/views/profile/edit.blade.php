@extends('layouts.client')

@section('title', 'Tài Khoản')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
@php
    $storedAvatar = $user->avatar;
    $selectedAvatar = old('avatar', $storedAvatar ?: 'preset-mint');
    $avatarIsPreset = is_string($selectedAvatar) && str_starts_with($selectedAvatar, 'preset-');
    $avatarIsRemoteImage = is_string($selectedAvatar) && preg_match('/^https?:\/\//i', $selectedAvatar);
    $avatarIsLocalImage = is_string($selectedAvatar)
        && $selectedAvatar !== ''
        && ! $avatarIsPreset
        && ! $avatarIsRemoteImage
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($selectedAvatar);
    $avatarIsImage = $avatarIsRemoteImage || $avatarIsLocalImage;
    $avatarValue = $avatarIsImage || $avatarIsPreset ? $selectedAvatar : 'preset-mint';
    $avatarUrl = $avatarIsRemoteImage
        ? $selectedAvatar
        : ($avatarIsLocalImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($selectedAvatar) : null);
    $avatarOptions = [
        'preset-mint' => ['label' => 'Mint', 'class' => 'avatar-preset-mint'],
        'preset-sky' => ['label' => 'Sky', 'class' => 'avatar-preset-sky'],
        'preset-berry' => ['label' => 'Berry', 'class' => 'avatar-preset-berry'],
        'preset-orange' => ['label' => 'Cam', 'class' => 'avatar-preset-orange'],
    ];
@endphp

<style>
    .profile-page {
        padding: 2rem 0 4rem; background: var(--c-bg); min-height: calc(100vh - 80px);
    }

    .profile-heading {
        margin-bottom: 1.75rem;
    }

    .profile-heading h1 {
        font-size: clamp(1.55rem, 2.4vw, 2.05rem);
    }

    .profile-card {
        background: var(--c-surface); border-radius: var(--radius-2xl);
        border: 1px solid var(--c-border); box-shadow: var(--shadow-sm);
        padding: 2.5rem; height: 100%;
    }

    .profile-preview {
        display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;
        border: 1px solid var(--c-border); border-radius: var(--radius-xl);
        background: linear-gradient(135deg, #ffffff, var(--c-primary-light));
    }

    .profile-avatar-large {
        width: 84px; height: 84px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        color: #ffffff; font-size: 2rem; font-weight: 800;
        box-shadow: 0 16px 34px rgba(13, 147, 115, 0.24);
        overflow: hidden; flex: 0 0 auto; border: 3px solid #fff;
    }

    .profile-avatar-large img { width: 100%; height: 100%; object-fit: cover; }

    .avatar-choice {
        width: 52px; height: 52px; border-radius: 50%; border: 3px solid #ffffff;
        box-shadow: 0 0 0 1px var(--c-border), var(--shadow-sm);
        cursor: pointer; transition: all 0.2s ease;
    }

    .avatar-choice:hover, .avatar-choice.active {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 0 0 3px var(--c-primary-glow), var(--shadow-md);
    }
    
    .form-floating > .form-control { border-radius: var(--radius-md); border-color: var(--c-border); }
    .form-floating > .form-control:focus { border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-glow); }
    
    .section-title-icon {
        display: inline-flex; width: 36px; height: 36px; border-radius: 10px;
        background: var(--c-primary-light); color: var(--c-primary);
        align-items: center; justify-content: center; font-size: 1.1rem;
    }

    .profile-location-card {
        border: 1px solid var(--c-border); border-radius: var(--radius-lg); background: var(--c-bg-subtle);
    }

    .profile-address-search {
        position: relative;
    }

    .profile-address-search .form-control {
        padding-right: 3rem;
    }

    .profile-address-search-icon {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        color: var(--c-primary);
        z-index: 4;
    }

    .profile-address-suggestions {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 30;
        overflow: hidden;
        border: 1px solid rgba(13, 147, 115, 0.18);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
    }

    .profile-address-suggestion {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        padding: 0.8rem 1rem;
        text-align: left;
    }

    .profile-address-suggestion:last-child {
        border-bottom: 0;
    }

    .profile-address-suggestion:hover,
    .profile-address-suggestion:focus-visible {
        background: var(--c-primary-light);
        outline: none;
    }

    .profile-address-suggestion-title {
        color: var(--c-text);
        font-size: 0.9rem;
        font-weight: 800;
    }

    .profile-address-suggestion-subtitle {
        color: var(--c-muted);
        font-size: 0.78rem;
        font-weight: 600;
        margin-top: 0.15rem;
    }

    .password-field {
        position: relative;
    }

    .password-field .password-toggle {
        position: absolute;
        top: 50%;
        right: 0.9rem;
        transform: translateY(-50%);
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: var(--c-muted);
        z-index: 5;
        transition: color 0.2s ease, background-color 0.2s ease;
    }

    .password-field .password-toggle:hover,
    .password-field .password-toggle:focus-visible {
        color: var(--c-primary);
        background: rgba(13, 147, 115, 0.08);
        outline: none;
    }

    .password-strength-track {
        height: 8px;
        border-radius: 999px;
        background: #eef2f7;
        overflow: hidden;
    }

    .password-strength-bar {
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #ef4444;
        transition: width 0.2s ease, background-color 0.2s ease;
    }

    .password-requirements {
        display: grid;
        gap: 0.35rem;
        margin: 0.75rem 0 1rem;
        padding: 0;
        list-style: none;
        color: var(--c-muted);
        font-size: 0.88rem;
    }

    .password-requirements li {
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .password-requirements i {
        color: #94a3b8;
    }

    .password-requirements li.is-valid {
        color: var(--c-primary);
        font-weight: 700;
    }

    .password-requirements li.is-valid i {
        color: var(--c-primary);
    }
</style>

<section class="profile-page">
    <div class="container">
        <div class="profile-heading text-center mb-4">
            <h1 class="fw-bold mb-1">Cập nhật hồ sơ</h1>
            <p class="text-secondary mb-0">Quản lý thông tin cá nhân và bảo mật tài khoản.</p>
        </div>

        @include('profile.partials.account-navigation', ['activeTab' => 'profile'])

        <div id="profile-info" class="row g-4 justify-content-center">
            <div class="col-lg-6">
                <div class="profile-card">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="section-title-icon"><i class="bi bi-person-vcard"></i></span>
                        <h2 class="h4 fw-bold mb-0">Hồ sơ cá nhân</h2>
                    </div>
                    
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <input type="hidden" id="avatar" name="avatar" value="{{ $avatarValue }}">

                        <div class="profile-preview mb-4">
                            <div id="avatarPreview" class="profile-avatar-large {{ $avatarIsImage ? '' : ($avatarOptions[$avatarValue]['class'] ?? 'avatar-preset-mint') }}" data-avatar-initial="{{ mb_substr($user->name, 0, 1) }}">
                                @if($avatarIsImage)
                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                                @else
                                    <span>{{ mb_substr($user->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <div>
                                <div class="fw-bold fs-5">Ảnh đại diện</div>
                                <div class="text-secondary small">Tải ảnh lên hoặc chọn mẫu để hiển thị trên menu.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="avatar_file" class="form-label fw-semibold">Tải ảnh lên</label>
                            <input id="avatar_file" name="avatar_file" type="file" class="form-control form-control-sm @error('avatar_file') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text mt-1">Hỗ trợ JPG, PNG hoặc WEBP, tối đa 2MB.</div>
                            @error('avatar_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Gợi ý ảnh đại diện</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($avatarOptions as $value => $option)
                                    <button type="button"
                                        class="avatar-choice {{ $option['class'] }} {{ $avatarValue === $value ? 'active' : '' }}"
                                        data-avatar-value="{{ $value }}"
                                        data-avatar-class="{{ $option['class'] }}"
                                        title="Avatar {{ $option['label'] }}">
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name" placeholder="Họ tên">
                                    <label for="name">Họ và tên</label>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="Số điện thoại" autocomplete="tel">
                                    <label for="phone">Số điện thoại</label>
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username" placeholder="Email">
                                    <label for="email">Địa chỉ email</label>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="section-title-icon" style="width: 28px; height: 28px; font-size: 0.9rem;"><i class="bi bi-geo-alt"></i></span>
                                    <h3 class="h6 fw-bold mb-0">Địa chỉ giao hàng</h3>
                                </div>
                                <div class="profile-address-search form-floating mb-3">
                                    <input id="profileAddressSearch" type="search" class="form-control" value="{{ \App\Http\Controllers\ProfileController::cleanAddressString(trim(old('address', $user->address) . ', ' . old('area', $user->area))) }}" placeholder="Tìm số nhà, đường, phường/xã..." autocomplete="off">
                                    <label for="profileAddressSearch">Tìm địa chỉ giao hàng</label>
                                    <i class="bi bi-search profile-address-search-icon"></i>
                                    <div id="profileAddressSuggestions" class="profile-address-suggestions d-none"></div>
                                </div>
                                <div class="form-floating mb-3">
                                    <input id="area" name="area" type="text" class="form-control @error('area') is-invalid @enderror" value="{{ old('area', \App\Http\Controllers\ProfileController::cleanAddressString($user->area)) }}" placeholder="Bấm định vị để lấy khu vực">
                                    <label for="area">Khu vực (Phường/Xã, Tỉnh/TP)</label>
                                    <button id="profileLocationBtn" class="btn btn-primary position-absolute top-50 translate-middle-y end-0 me-2 py-1 px-3 rounded-pill" type="button" style="z-index: 10;">
                                        <i class="bi bi-crosshair me-1"></i>Định vị
                                    </button>
                                    @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div id="profileLocationStatus" class="form-text small mb-2 text-primary"></div>
                                
                                <div class="form-floating mb-3">
                                    <input id="address" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', \App\Http\Controllers\ProfileController::cleanAddressString($user->address)) }}" placeholder="Số nhà, tên đường..." autocomplete="street-address">
                                    <label for="address">Địa chỉ chi tiết (Số nhà, đường...)</label>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <input id="profileLatitude" type="hidden" name="latitude" value="{{ old('latitude', $user->latitude) }}">
                                <input id="profileLongitude" type="hidden" name="longitude" value="{{ old('longitude', $user->longitude) }}">

                                <div class="d-none profile-location-card p-3 mt-3" id="profileMapPreviewWrap">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold fs-6">Vị trí Map</div>
                                            <div class="text-secondary small" id="profileMapText">{{ \App\Http\Controllers\ProfileController::cleanAddressString(trim(($user->address ?? '') . ', ' . ($user->area ?? ''))) ?: 'Chưa có vị trí' }}</div>
                                        </div>
                                        <a id="profileMapLink" href="https://www.google.com/maps/search/{{ urlencode(\App\Http\Controllers\ProfileController::cleanAddressString(trim(($user->address ?? '') . ', ' . ($user->area ?? '')))) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm rounded-pill">
                                            Xem trên Map
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold">Lưu hồ sơ</button>
                            @if (session('status') === 'profile-updated')
                                <span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>{{ __('profile-updated') }}</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="profile-card">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="section-title-icon"><i class="bi bi-shield-lock"></i></span>
                        <h2 class="h4 fw-bold mb-0">Đổi mật khẩu</h2>
                    </div>
                    
                    <p class="text-secondary mb-4 text-sm">Mật khẩu cần trên 6 ký tự, có chữ in hoa, số và ký tự đặc biệt.</p>

                    <form method="POST" action="{{ route('password.update') }}" data-password-update-form novalidate>
                        @csrf
                        @method('PUT')

                        <div class="form-floating mb-3 password-field">
                            <input id="update_password_current_password" name="current_password" type="password" class="form-control pe-5 @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" placeholder="Mật khẩu hiện tại" data-password-input>
                            <label for="update_password_current_password">Mật khẩu hiện tại</label>
                            <button type="button" class="password-toggle" data-password-toggle data-target="update_password_current_password" data-show-label="Hiện mật khẩu hiện tại" data-hide-label="Ẩn mật khẩu hiện tại" aria-label="Hiện mật khẩu hiện tại" title="Hiện mật khẩu hiện tại">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating mb-3 password-field">
                            <input id="update_password_password" name="password" type="password" class="form-control pe-5 @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" placeholder="Mật khẩu mới" data-password-input>
                            <label for="update_password_password">Mật khẩu mới</label>
                            <button type="button" class="password-toggle" data-password-toggle data-target="update_password_password" data-show-label="Hiện mật khẩu mới" data-hide-label="Ẩn mật khẩu mới" aria-label="Hiện mật khẩu mới" title="Hiện mật khẩu mới">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="password-strength-track" aria-hidden="true">
                            <div class="password-strength-bar" data-password-strength-bar></div>
                        </div>
                        <ul class="password-requirements" data-password-requirements>
                            <li data-password-rule="length"><i class="bi bi-circle"></i>Trên 6 ký tự</li>
                            <li data-password-rule="uppercase"><i class="bi bi-circle"></i>Có chữ in hoa</li>
                            <li data-password-rule="number"><i class="bi bi-circle"></i>Có chữ số</li>
                            <li data-password-rule="special"><i class="bi bi-circle"></i>Có ký tự đặc biệt</li>
                            <li data-password-rule="match"><i class="bi bi-circle"></i>Xác nhận mật khẩu khớp</li>
                        </ul>

                        <div class="form-floating mb-4 password-field">
                            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control pe-5 @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới" data-password-input>
                            <label for="update_password_password_confirmation">Xác nhận mật khẩu mới</label>
                            <button type="button" class="password-toggle" data-password-toggle data-target="update_password_password_confirmation" data-show-label="Hiện xác nhận mật khẩu mới" data-hide-label="Ẩn xác nhận mật khẩu mới" aria-label="Hiện xác nhận mật khẩu mới" title="Hiện xác nhận mật khẩu mới">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold" data-password-submit>Lưu mật khẩu mới</button>
                            <span class="text-danger small fw-semibold d-none" data-password-form-message></span>
                            @if (session('status') === 'password-updated')
                                <span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>Đã đổi mật khẩu thành công.</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarInitial = avatarPreview?.dataset.avatarInitial || @json(mb_substr($user->name, 0, 1));
    const presetClasses = ['avatar-preset-mint', 'avatar-preset-sky', 'avatar-preset-berry', 'avatar-preset-orange'];

    const renderAvatarFallback = () => {
        if (!avatarPreview) {
            return;
        }

        avatarPreview.classList.remove(...presetClasses);
        avatarPreview.classList.add('avatar-preset-mint');
        avatarPreview.innerHTML = `<span>${avatarInitial}</span>`;
    };

    avatarPreview?.querySelector('img')?.addEventListener('error', renderAvatarFallback, { once: true });

    document.querySelectorAll('[data-avatar-value]').forEach((button) => {
        button.addEventListener('click', () => {
            const avatarInput = document.getElementById('avatar');
            const avatarFile = document.getElementById('avatar_file');

            document.querySelectorAll('[data-avatar-value]').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            avatarInput.value = button.dataset.avatarValue;
            avatarFile.value = '';
            avatarPreview.classList.remove(...presetClasses);
            avatarPreview.classList.add(button.dataset.avatarClass);
            avatarPreview.innerHTML = `<span>${avatarInitial}</span>`;
        });
    });

    document.getElementById('avatar_file').addEventListener('change', (event) => {
        const file = event.target.files[0];
        const avatarInput = document.getElementById('avatar');

        if (!file) {
            return;
        }

        document.querySelectorAll('[data-avatar-value]').forEach((item) => item.classList.remove('active'));
        avatarInput.value = '';
        avatarPreview.classList.remove(...presetClasses);
        avatarPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Avatar mới">`;
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }

            const showLabel = button.dataset.showLabel || 'Hiện mật khẩu';
            const hideLabel = button.dataset.hideLabel || 'Ẩn mật khẩu';
            const nextLabel = isHidden ? hideLabel : showLabel;

            button.setAttribute('aria-label', nextLabel);
            button.title = nextLabel;
        });
    });

    const passwordUpdateForm = document.querySelector('[data-password-update-form]');
    const currentPasswordInput = document.getElementById('update_password_current_password');
    const newPasswordInput = document.getElementById('update_password_password');
    const confirmPasswordInput = document.getElementById('update_password_password_confirmation');
    const passwordSubmit = document.querySelector('[data-password-submit]');
    const passwordStrengthBar = document.querySelector('[data-password-strength-bar]');
    const passwordFormMessage = document.querySelector('[data-password-form-message]');

    function setPasswordFormMessage(message = '') {
        if (!passwordFormMessage) {
            return;
        }

        passwordFormMessage.textContent = message;
        passwordFormMessage.classList.toggle('d-none', !message);
    }

    function setPasswordRuleState(rule, isValid) {
        const item = document.querySelector(`[data-password-rule="${rule}"]`);
        const icon = item?.querySelector('i');

        if (!item || !icon) {
            return;
        }

        item.classList.toggle('is-valid', isValid);
        icon.classList.toggle('bi-circle', !isValid);
        icon.classList.toggle('bi-check-circle-fill', isValid);
    }

    function syncPasswordFormState() {
        const currentPassword = currentPasswordInput?.value || '';
        const password = newPasswordInput?.value || '';
        const confirmation = confirmPasswordInput?.value || '';
        const checks = {
            length: password.length > 6,
            uppercase: /[A-Z]/u.test(password),
            number: /\d/.test(password),
            special: /[^A-Za-z0-9]/u.test(password),
            match: password !== '' && password === confirmation,
        };
        const score = Object.values(checks).filter(Boolean).length;

        Object.entries(checks).forEach(([rule, isValid]) => setPasswordRuleState(rule, isValid));

        if (passwordStrengthBar) {
            passwordStrengthBar.style.width = `${score * 20}%`;
            passwordStrengthBar.style.backgroundColor = score <= 1 ? '#ef4444' : (score <= 4 ? '#f59e0b' : '#0d9373');
        }

        if (confirmPasswordInput) {
            const shouldValidateConfirm = confirmation.length > 0 || password.length > 0;
            confirmPasswordInput.classList.toggle('is-invalid', shouldValidateConfirm && !checks.match);
            confirmPasswordInput.setCustomValidity(checks.match || !shouldValidateConfirm ? '' : 'Xác nhận mật khẩu mới không khớp.');
        }

        if (newPasswordInput) {
            const basicPasswordValid = checks.length && checks.uppercase && checks.number && checks.special;
            newPasswordInput.classList.toggle('is-invalid', password.length > 0 && !basicPasswordValid);
            newPasswordInput.setCustomValidity(basicPasswordValid || password.length === 0 ? '' : 'Mật khẩu mới cần trên 6 ký tự, có chữ in hoa, số và ký tự đặc biệt.');
        }

        if (passwordSubmit) {
            passwordSubmit.dataset.ready = currentPassword && checks.length && checks.uppercase && checks.number && checks.special && checks.match ? '1' : '0';
        }

        if (!currentPassword && (password || confirmation)) {
            setPasswordFormMessage('Vui lòng nhập mật khẩu hiện tại để xác minh.');
        } else if (password && (!checks.length || !checks.uppercase || !checks.number || !checks.special)) {
            setPasswordFormMessage('Mật khẩu mới cần trên 6 ký tự, có chữ in hoa, số và ký tự đặc biệt.');
        } else if (confirmation && !checks.match) {
            setPasswordFormMessage('Xác nhận mật khẩu mới chưa khớp.');
        } else {
            setPasswordFormMessage('');
        }
    }

    [currentPasswordInput, newPasswordInput, confirmPasswordInput].forEach((input) => {
        input?.addEventListener('input', syncPasswordFormState);
        input?.addEventListener('blur', syncPasswordFormState);
    });

    passwordUpdateForm?.addEventListener('submit', (event) => {
        syncPasswordFormState();

        if (passwordSubmit?.dataset.ready !== '1') {
            event.preventDefault();
            if (!currentPasswordInput?.value) {
                setPasswordFormMessage('Vui lòng nhập mật khẩu hiện tại để xác minh.');
            } else if (!newPasswordInput?.value) {
                setPasswordFormMessage('Vui lòng nhập mật khẩu mới.');
            } else if (!confirmPasswordInput?.value) {
                setPasswordFormMessage('Vui lòng xác nhận mật khẩu mới.');
            }
            (currentPasswordInput?.value ? newPasswordInput : currentPasswordInput)?.focus();
            return;
        }

        if (passwordSubmit) {
            passwordSubmit.disabled = true;
            passwordSubmit.textContent = 'Đang lưu...';
        }
        setPasswordFormMessage('');
    });

    syncPasswordFormState();

    const profileLocationBtn = document.getElementById('profileLocationBtn');
    const profileAddressInput = document.getElementById('address');
    const profileAreaInput = document.getElementById('area');
    const profileLocationStatus = document.getElementById('profileLocationStatus');
    const profileMapPreviewWrap = document.getElementById('profileMapPreviewWrap');
    const profileMapText = document.getElementById('profileMapText');
    const profileMapLink = document.getElementById('profileMapLink');
    const profileLatitudeInput = document.getElementById('profileLatitude');
    const profileLongitudeInput = document.getElementById('profileLongitude');
    const profileAddressSearch = document.getElementById('profileAddressSearch');
    const profileAddressSuggestions = document.getElementById('profileAddressSuggestions');

    function compactProfileAddress(parts) {
        return parts.map((part) => String(part || '').trim()).filter(Boolean).join(', ');
    }

    function uniqueProfileAddressParts(parts) {
        const seen = new Set();
        const result = [];

        for (const part of parts) {
            let raw = String(part || '').trim();
            if (!raw) continue;
            // Collapse consecutive duplicate words/tokens like "254 254" -> "254"
            raw = raw.replace(/\b(\S+)(?:\s+\1\b)+/gu, '$1');
            const tokens = raw.split(',').map((t) => t.trim().replace(/\b(\S+)(?:\s+\1\b)+/gu, '$1')).filter(Boolean);
            for (const token of tokens) {
                const key = token.toLocaleLowerCase('vi-VN');
                if (!seen.has(key)) {
                    seen.add(key);
                    result.push(token);
                }
            }
        }

        return result;
    }

    function escapeProfileHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function debounceProfile(callback, delay = 500) {
        let timer = null;

        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => callback(...args), delay);
        };
    }

    function normalizeProfileSuggestion(feature) {
        const properties = feature?.properties || {};
        const coordinates = feature?.geometry?.coordinates || [];
        const lng = Number.parseFloat(coordinates[0]);
        const lat = Number.parseFloat(coordinates[1]);
        const street = compactProfileAddress(uniqueProfileAddressParts([
            properties.housenumber,
            properties.street,
            properties.name,
        ]));
        const area = compactProfileAddress(uniqueProfileAddressParts([
            properties.district,
            properties.city,
            properties.county,
            properties.state,
            properties.country,
        ]));
        const displayName = compactProfileAddress(uniqueProfileAddressParts([
            street,
            area,
        ]));

        return {
            lat,
            lng,
            street,
            area,
            title: street || properties.name || displayName || 'Địa chỉ được gợi ý',
            subtitle: area || displayName || '',
            displayName,
        };
    }

    function normalizeInternalProfileSuggestion(item) {
        const lat = Number.parseFloat(item?.latitude);
        const lng = Number.parseFloat(item?.longitude);
        const displayName = item?.full_address || item?.name || '';

        return {
            lat,
            lng,
            street: displayName,
            area: '',
            title: item?.name || displayName || 'Địa chỉ Chill Drink đã ghi nhận',
            subtitle: item?.full_address || 'Dữ liệu địa chỉ đã lưu trong hệ thống',
            displayName,
            canAutofillCoordinates: item?.can_autofill_coordinates !== false,
        };
    }

    function setProfileMapLocation(lat, lng, text = '') {
        if (!Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) {
            return;
        }

        const nextLat = Number(lat).toFixed(6);
        const nextLng = Number(lng).toFixed(6);

        if (profileLatitudeInput) {
            profileLatitudeInput.value = nextLat;
        }

        if (profileLongitudeInput) {
            profileLongitudeInput.value = nextLng;
        }

        if (profileMapText) {
            profileMapText.textContent = text || `${nextLat}, ${nextLng}`;
        }

        if (profileMapLink) {
            profileMapLink.href = `https://www.google.com/maps?q=${nextLat},${nextLng}`;
        }

        profileMapPreviewWrap?.classList.remove('d-none');
    }

    function hideProfileAddressSuggestions() {
        if (!profileAddressSuggestions) {
            return;
        }

        profileAddressSuggestions.classList.add('d-none');
        profileAddressSuggestions.innerHTML = '';
    }

    function renderProfileAddressSuggestions(items) {
        if (!profileAddressSuggestions) {
            return;
        }

        if (!items.length) {
            profileAddressSuggestions.innerHTML = '<div class="profile-address-suggestion-title px-3 py-2">Không tìm thấy gợi ý phù hợp.</div>';
            profileAddressSuggestions.classList.remove('d-none');
            return;
        }

        profileAddressSuggestions.innerHTML = items.map((item, index) => `
            <button type="button" class="profile-address-suggestion" data-profile-address-suggestion="${index}">
                <div class="profile-address-suggestion-title">${escapeProfileHtml(item.title)}</div>
                <div class="profile-address-suggestion-subtitle">${escapeProfileHtml(item.subtitle || item.displayName)}</div>
            </button>
        `).join('');
        profileAddressSuggestions.classList.remove('d-none');

        profileAddressSuggestions.querySelectorAll('[data-profile-address-suggestion]').forEach((button) => {
            button.addEventListener('click', () => {
                const item = items[Number(button.dataset.profileAddressSuggestion)];

                if (!item || !Number.isFinite(item.lat) || !Number.isFinite(item.lng)) {
                    return;
                }

                profileAddressSearch.value = item.displayName || item.title;
                profileAddressInput.value = item.street || item.displayName;
                profileAreaInput.value = item.area || item.displayName;
                profileAddressInput.dispatchEvent(new Event('input', { bubbles: true }));
                profileAreaInput.dispatchEvent(new Event('input', { bubbles: true }));
                setProfileMapLocation(item.lat, item.lng, item.displayName);

                if (profileLocationStatus) {
                    profileLocationStatus.textContent = 'Đã chọn địa chỉ từ gợi ý. Vui lòng lưu hồ sơ.';
                    profileLocationStatus.classList.remove('text-danger');
                    profileLocationStatus.classList.add('text-primary');
                }

                hideProfileAddressSuggestions();
            });
        });
    }

    function pickVietnamWardName(candidates) {
        let fallback = '';
        let prefixedWard = '';

        for (const candidate of candidates) {
            const value = String(candidate || '').trim();
            if (!value) {
                continue;
            }

            if (!fallback) {
                fallback = value;
            }

            if (/^(Phường|Xã|Thị trấn)\b/ui.test(value)) {
                return value;
            }

            if (!prefixedWard && /^(P\.|P:|X\.|X:|TT\.|TT:)\s*/ui.test(value)) {
                prefixedWard = value;
            }
        }

        return prefixedWard || fallback;
    }

    if (profileAddressSearch && profileAddressSuggestions && profileAddressInput && profileAreaInput) {
        const searchProfileAddress = debounceProfile(async () => {
            const query = String(profileAddressSearch.value || '').trim();

            if (query.length < 3) {
                hideProfileAddressSuggestions();
                return;
            }

            if (profileLocationStatus) {
                profileLocationStatus.textContent = 'Đang tìm gợi ý địa chỉ...';
                profileLocationStatus.classList.remove('text-danger');
                profileLocationStatus.classList.add('text-primary');
            }

            try {
                const internalUrl = new URL('/api/address-lookup', window.location.origin);
                internalUrl.searchParams.set('q', query);
                internalUrl.searchParams.set('limit', '8');
                const currentLat = Number.parseFloat(profileLatitudeInput?.value || '');
                const currentLng = Number.parseFloat(profileLongitudeInput?.value || '');
                if (Number.isFinite(currentLat) && Number.isFinite(currentLng)) {
                    internalUrl.searchParams.set('latitude', String(currentLat));
                    internalUrl.searchParams.set('longitude', String(currentLng));
                }

                const photonUrl = new URL('https://photon.komoot.io/api/');
                photonUrl.searchParams.set('q', query);
                // Photon currently rejects the `lang` parameter with HTTP 400.
                // Its default response already contains Vietnamese OSM names.
                photonUrl.searchParams.set('limit', '10');

                const [internalResult, photonResult] = await Promise.allSettled([
                    fetch(internalUrl.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then((response) => response.ok ? response.json() : { data: [] }),
                    fetch(photonUrl.toString()).then((response) => response.ok ? response.json() : { features: [] }),
                ]);

                const internalItems = internalResult.status === 'fulfilled'
                    ? (internalResult.value.data || [])
                        .map(normalizeInternalProfileSuggestion)
                        .filter((item) => item.canAutofillCoordinates && Number.isFinite(item.lat) && Number.isFinite(item.lng))
                    : [];
                const photonItems = photonResult.status === 'fulfilled'
                    ? (photonResult.value.features || [])
                    .map(normalizeProfileSuggestion)
                    .filter((item) => Number.isFinite(item.lat) && Number.isFinite(item.lng))
                    : [];
                const seen = new Set();
                const items = internalItems.concat(photonItems).filter((item) => {
                    const key = `${item.title}|${item.lat.toFixed(4)}|${item.lng.toFixed(4)}`.toLocaleLowerCase('vi-VN');
                    if (seen.has(key)) {
                        return false;
                    }

                    seen.add(key);
                    return true;
                }).slice(0, 16);

                renderProfileAddressSuggestions(items);

                if (profileLocationStatus) {
                    profileLocationStatus.textContent = items.length
                        ? 'Chọn một gợi ý để tự điền tọa độ và địa chỉ.'
                        : 'Không tìm thấy gợi ý. Bạn có thể nhập rõ hơn hoặc dùng nút định vị.';
                }
            } catch (error) {
                console.error('Lỗi khi tìm địa chỉ:', error);
                hideProfileAddressSuggestions();

                if (profileLocationStatus) {
                    profileLocationStatus.textContent = 'Không tìm được gợi ý lúc này. Bạn vẫn có thể dùng nút định vị.';
                    profileLocationStatus.classList.add('text-danger');
                }
            }
        }, 500);

        profileAddressSearch.addEventListener('input', searchProfileAddress);
        profileAddressSearch.addEventListener('focus', searchProfileAddress);
        document.addEventListener('click', (event) => {
            if (!profileAddressSearch.parentElement?.contains(event.target)) {
                hideProfileAddressSuggestions();
            }
        });
    }

    if (profileLocationBtn && profileAddressInput && profileAreaInput && profileLocationStatus && profileMapPreviewWrap && profileMapText && profileMapLink) {
        profileLocationBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                profileLocationStatus.textContent = 'Trình duyệt của bạn không hỗ trợ định vị.';
                return;
            }

            profileLocationStatus.textContent = 'Đang xin quyền vị trí... Bạn hãy cho phép nhé.';
            profileLocationBtn.disabled = true;

            navigator.geolocation.getCurrentPosition(async function (position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);

                setProfileMapLocation(lat, lng);
                profileLocationStatus.textContent = 'Đã lấy vị trí, đang tải địa chỉ...';

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                    const data = await response.json();
                    const address = data.address || {};
                    const streetLine = compactProfileAddress(uniqueProfileAddressParts([
                        address.house_number,
                        address.road || address.pedestrian || address.footway,
                    ]));
                    const wardLine = pickVietnamWardName([
                        address.city,
                        address.suburb,
                        address.village,
                        address.neighbourhood,
                        address.quarter,
                        address.city_district,
                        address.district,
                        address.town,
                    ]);
                    const provinceLine = String(address.state || address.region || '').trim();
                    const areaLine = compactProfileAddress(uniqueProfileAddressParts([
                        wardLine,
                        provinceLine,
                    ]));

                    profileAddressInput.value = streetLine || data.display_name || `${lat}, ${lng}`;
                    profileAreaInput.value = areaLine || data.display_name || `${lat}, ${lng}`;
                    setProfileMapLocation(lat, lng, compactProfileAddress(uniqueProfileAddressParts([profileAddressInput.value, profileAreaInput.value])));
                    profileLocationStatus.textContent = 'Đã tự điền địa chỉ! Vui lòng lưu lại.';
                } catch (error) {
                    profileAddressInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileAreaInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileLocationStatus.textContent = 'Lấy vị trí thành công nhưng chưa đổi được sang địa chỉ chữ.';
                    setProfileMapLocation(lat, lng);
                } finally {
                    profileLocationBtn.disabled = false;
                }
            }, function () {
                profileLocationStatus.textContent = 'Lỗi định vị. Vui lòng cấp quyền hoặc kiểm tra kết nối mạng.';
                profileLocationStatus.classList.replace('text-primary', 'text-danger');
                profileLocationBtn.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
        
        // Show map initially if there's data
        if(profileAreaInput.value.trim() !== '') {
            profileMapPreviewWrap.classList.remove('d-none');
        }
    }
</script>
@endsection
