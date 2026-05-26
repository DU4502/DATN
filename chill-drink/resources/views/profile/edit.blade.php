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
    .profile-preview {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--drink-border);
        border-radius: 18px;
        background: linear-gradient(135deg, #ffffff, var(--drink-primary-soft));
    }

    .profile-avatar-large {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.8rem;
        font-weight: 800;
        box-shadow: 0 16px 34px rgba(79, 183, 168, 0.24);
        overflow: hidden;
        flex: 0 0 auto;
    }

    .profile-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-choice {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 1px var(--drink-border), 0 10px 22px rgba(8, 42, 38, 0.10);
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease;
    }

    .avatar-choice:hover,
    .avatar-choice.active {
        transform: translateY(-2px);
        box-shadow: 0 0 0 3px rgba(0, 139, 122, 0.22), 0 12px 26px rgba(8, 42, 38, 0.14);
    }

    .profile-location-card {
        border: 1px solid var(--drink-border);
        border-radius: 16px;
        background: #f7fffd;
    }
</style>

<section class="py-5">
    <div class="container">
        <div class="mb-4">
            <p class="text-primary fw-semibold mb-1">Tài khoản</p>
            <h1 class="h2 fw-bold mb-0">Thông tin cá nhân</h1>
        </div>

        <nav class="profile-tabs" aria-label="Mục tài khoản">
            <a href="{{ route('profile.edit') }}" class="profile-tab active">Thông tin</a>
            <a href="{{ route('profile.orders') }}" class="profile-tab">Đơn hàng của tôi</a>
        </nav>

        <div id="profile-info" class="row g-4">
            <div class="col-lg-6">
                <div class="drink-card card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Cập nhật hồ sơ</h2>
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
                                    <div class="fw-bold">Avatar tài khoản</div>
                                    <div class="text-secondary small">Tải ảnh của bạn lên hoặc chọn avatar demo để hiển thị trên thanh menu.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="avatar_file" class="form-label">Ảnh avatar của bạn</label>
                                <input id="avatar_file" name="avatar_file" type="file" class="form-control @error('avatar_file') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                                <div class="form-text">Chọn ảnh JPG, PNG hoặc WEBP, tối đa 2MB.</div>
                                @error('avatar_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chọn avatar demo</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($avatarOptions as $value => $option)
                                        <button type="button"
                                            class="avatar-choice {{ $option['class'] }} {{ $avatarValue === $value ? 'active' : '' }}"
                                            data-avatar-value="{{ $value }}"
                                            data-avatar-class="{{ $option['class'] }}"
                                            aria-label="Avatar {{ $option['label'] }}">
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Họ tên</label>
                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="Nhập số điện thoại" autocomplete="tel">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ</label>
                                <input id="address" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $user->address) }}" placeholder="Số nhà, tên đường..." autocomplete="street-address">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="area" class="form-label">Khu vực</label>
                                <div class="input-group">
                                    <input id="area" name="area" type="text" class="form-control @error('area') is-invalid @enderror" value="{{ old('area', $user->area) }}" placeholder="Bấm định vị để lấy khu vực">
                                    <button id="profileLocationBtn" class="btn btn-outline-primary px-3" type="button">
                                        <i class="bi bi-geo-alt me-1"></i>Định vị
                                    </button>
                                </div>
                                <div id="profileLocationStatus" class="form-text">Trình duyệt sẽ hỏi quyền vị trí khi bạn bấm định vị.</div>
                                <div class="d-none profile-location-card p-3 mt-3" id="profileMapPreviewWrap">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold">Vị trí tài khoản</div>
                                            <div class="text-secondary small" id="profileMapText">{{ trim(($user->address ?? '') . ' ' . ($user->area ?? '')) ?: 'Chưa có vị trí' }}</div>
                                        </div>
                                        <a id="profileMapLink" href="https://www.google.com/maps/search/{{ urlencode(trim(($user->address ?? '') . ' ' . ($user->area ?? ''))) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                            Mở Google Maps
                                        </a>
                                    </div>
                                </div>
                                @error('area')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>

                            @if (session('status') === 'profile-updated')
                                <span class="text-success ms-3">Đã lưu.</span>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="drink-card card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Đổi mật khẩu</h2>
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="update_password_current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="update_password_password" class="form-label">Mật khẩu mới</label>
                                <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="update_password_password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                                @error('password_confirmation', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>

                            @if (session('status') === 'password-updated')
                                <span class="text-success ms-3">Đã cập nhật.</span>
                            @endif
                        </form>
                    </div>
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

            profileLocationStatus.textContent = 'Đang xin quyền vị trí...';
            profileLocationBtn.disabled = true;

            navigator.geolocation.getCurrentPosition(async function (position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

                profileMapText.textContent = `${lat}, ${lng}`;
                profileMapLink.href = mapsUrl;
                profileLocationStatus.textContent = 'Đã lấy vị trí, đang chuyển thành địa chỉ...';

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
                    profileLocationStatus.textContent = 'Đã tự điền địa chỉ từ vị trí hiện tại. Bạn nhớ bấm Lưu thay đổi.';
                } catch (error) {
                    profileAddressInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileAreaInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                    profileLocationStatus.textContent = 'Đã lấy vị trí nhưng chưa đổi được thành địa chỉ chữ. Bạn có thể mở Google Maps để kiểm tra.';
                } finally {
                    profileLocationBtn.disabled = false;
                }
            }, function () {
                profileLocationStatus.textContent = 'Bạn chưa cấp quyền vị trí hoặc trình duyệt không lấy được vị trí.';
                profileLocationBtn.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }
</script>
@endsection
