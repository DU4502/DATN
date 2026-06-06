@extends('layouts.client')

@section('title', 'Tài Khoản')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
@php
    $avatarValue = old('avatar', $user->avatar ?: 'preset-mint');
    $avatarIsImage = $user->avatar && ! str_starts_with($user->avatar, 'preset-');
    $avatarUrl = $avatarIsImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) : null;
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
</style>

<section class="profile-page">
    <div class="container">
        <div class="profile-heading text-center">
            <h1 class="fw-bold mb-1">Cập nhật hồ sơ</h1>
            <p class="text-secondary mb-0">Quản lý thông tin cá nhân và bảo mật tài khoản.</p>
        </div>

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
                            <div id="avatarPreview" class="profile-avatar-large {{ $avatarIsImage ? '' : ($avatarOptions[$avatarValue]['class'] ?? 'avatar-preset-mint') }}">
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
                                    <label for="email">Email</label>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="section-title-icon" style="width: 28px; height: 28px; font-size: 0.9rem;"><i class="bi bi-geo-alt"></i></span>
                                    <h3 class="h6 fw-bold mb-0">Địa chỉ giao hàng</h3>
                                </div>
                                <div class="form-floating mb-3">
                                    <input id="area" name="area" type="text" class="form-control @error('area') is-invalid @enderror" value="{{ old('area', $user->area) }}" placeholder="Bấm định vị để lấy khu vực">
                                    <label for="area">Khu vực (Quận/Huyện, Tỉnh/TP)</label>
                                    <button id="profileLocationBtn" class="btn btn-primary position-absolute top-50 translate-middle-y end-0 me-2 py-1 px-3 rounded-pill" type="button" style="z-index: 10;">
                                        <i class="bi bi-crosshair me-1"></i>Định vị
                                    </button>
                                    @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div id="profileLocationStatus" class="form-text small mb-2 text-primary"></div>
                                
                                <div class="form-floating mb-3">
                                    <input id="address" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $user->address) }}" placeholder="Số nhà, tên đường..." autocomplete="street-address">
                                    <label for="address">Địa chỉ chi tiết (Số nhà, đường...)</label>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="d-none profile-location-card p-3 mt-3" id="profileMapPreviewWrap">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold fs-6">Vị trí Map</div>
                                            <div class="text-secondary small" id="profileMapText">{{ trim(($user->address ?? '') . ' ' . ($user->area ?? '')) ?: 'Chưa có vị trí' }}</div>
                                        </div>
                                        <a id="profileMapLink" href="https://www.google.com/maps/search/{{ urlencode(trim(($user->address ?? '') . ' ' . ($user->area ?? ''))) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm rounded-pill">
                                            Xem trên Map
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold">Lưu hồ sơ</button>
                            @if (session('status') === 'profile-updated')
                                <span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>Đã lưu thành công!</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="profile-card">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="section-title-icon text-warning" style="background: #FEF3C7;"><i class="bi bi-shield-lock"></i></span>
                        <h2 class="h4 fw-bold mb-0">Đổi mật khẩu</h2>
                    </div>
                    
                    <p class="text-secondary mb-4 text-sm">Để đảm bảo an toàn, hãy sử dụng mật khẩu dài, ngẫu nhiên chứa các chữ cái, số và ký tự đặc biệt.</p>
                    
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-floating mb-3">
                            <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" placeholder="Mật khẩu hiện tại">
                            <label for="update_password_current_password">Mật khẩu hiện tại</label>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" placeholder="Mật khẩu mới">
                            <label for="update_password_password">Mật khẩu mới</label>
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating mb-4">
                            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                            <label for="update_password_password_confirmation">Xác nhận mật khẩu mới</label>
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-warning px-4 rounded-pill fw-semibold text-dark">Lưu mật khẩu mới</button>
                            @if (session('status') === 'password-updated')
                                <span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>Đã cập nhật!</span>
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
    const avatarInitial = @json(mb_substr($user->name, 0, 1));
    const presetClasses = ['avatar-preset-mint', 'avatar-preset-sky', 'avatar-preset-berry', 'avatar-preset-orange'];

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

    const profileLocationBtn = document.getElementById('profileLocationBtn');
    const profileAddressInput = document.getElementById('address');
    const profileAreaInput = document.getElementById('area');
    const profileLocationStatus = document.getElementById('profileLocationStatus');
    const profileMapPreviewWrap = document.getElementById('profileMapPreviewWrap');
    const profileMapText = document.getElementById('profileMapText');
    const profileMapLink = document.getElementById('profileMapLink');

    function compactProfileAddress(parts) {
        return parts.filter(Boolean).join(', ');
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
                const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

                profileMapText.textContent = `${lat}, ${lng}`;
                profileMapLink.href = mapsUrl;
                profileLocationStatus.textContent = 'Đã lấy vị trí, đang tải địa chỉ...';

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                    const data = await response.json();
                    const address = data.address || {};
                    const streetLine = compactProfileAddress([
                        address.house_number,
                        address.road || address.pedestrian || address.footway,
                        address.neighbourhood || address.suburb
                    ]);
                    const areaLine = compactProfileAddress([
                        address.quarter || address.ward || address.suburb || address.village,
                        address.city_district || address.district || address.town,
                        address.city || address.state
                    ]);

                    profileAddressInput.value = streetLine || data.display_name || `${lat}, ${lng}`;
                    profileAreaInput.value = areaLine || data.display_name || `${lat}, ${lng}`;
                    profileMapText.textContent = compactProfileAddress([profileAddressInput.value, profileAreaInput.value]);
                    profileLocationStatus.textContent = 'Đã tự điền địa chỉ! Vui lòng lưu lại.';
                    profileMapPreviewWrap.classList.remove('d-none');
                } catch (error) {
                    profileAddressInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileAreaInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileLocationStatus.textContent = 'Lấy vị trí thành công nhưng chưa đổi được sang địa chỉ chữ.';
                    profileMapPreviewWrap.classList.remove('d-none');
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
