@php
    $activeTab = $activeTab ?? 'orders';
@endphp

<nav class="profile-tabs mb-4" aria-label="Điều hướng tài khoản">
    <a href="{{ route('profile.edit') }}" class="profile-tab {{ $activeTab === 'profile' ? 'active' : '' }}">
        <i class="bi bi-person me-1"></i>Hồ sơ cá nhân
    </a>
    <a href="{{ route('profile.orders') }}" class="profile-tab {{ $activeTab === 'orders' ? 'active' : '' }}">
        <i class="bi bi-receipt me-1"></i>Đơn hàng của tôi
    </a>
    <a href="{{ route('profile.addresses.index') }}" class="profile-tab {{ $activeTab === 'addresses' ? 'active' : '' }}">
        <i class="bi bi-geo-alt me-1"></i>Địa chỉ của bạn
    </a>
    <a href="{{ route('favorites.index') }}" class="profile-tab {{ $activeTab === 'favorites' ? 'active' : '' }}">
        <i class="bi bi-heart me-1"></i>Món yêu thích
    </a>
    <a href="{{ route('loyalty.index') }}" class="profile-tab {{ $activeTab === 'loyalty' ? 'active' : '' }}">
        <i class="bi bi-star me-1"></i>Điểm thưởng
    </a>
    <a href="{{ route('group-orders.index') }}" class="profile-tab {{ $activeTab === 'groups' ? 'active' : '' }}">
        <i class="bi bi-people me-1"></i>Đơn nhóm
    </a>
</nav>
