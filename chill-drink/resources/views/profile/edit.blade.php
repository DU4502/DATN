@extends('layouts.client')

@section('title', 'Tài Khoản')

@section('content')
@php
    $avatarValue = old('avatar', $user->avatar ?: 'preset-mint');
    $avatarIsImage = $user->avatar && ! str_starts_with($user->avatar, 'preset-');
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
</style>

<section class="py-5">
    <div class="container">
        <div class="mb-4">
            <p class="text-primary fw-semibold mb-1">Tài khoản</p>
            <h1 class="h2 fw-bold mb-0">Thông tin cá nhân</h1>
        </div>

        <div class="row g-4">
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
                                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
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

        if (!file) {
            return;
        }

        document.querySelectorAll('[data-avatar-value]').forEach((item) => item.classList.remove('active'));
        avatarPreview.classList.remove(...presetClasses);
        avatarPreview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Avatar mới">`;
    });
</script>
@endsection
