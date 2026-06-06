@extends('layouts.client')

@section('title', 'Tài khoản')

@section('content')
<style>
    .user-dashboard { padding: 4rem 0; background: var(--c-bg); min-height: calc(100vh - 80px); }
    
    .dashboard-card {
        background: var(--c-surface);
        border-radius: var(--radius-2xl);
        border: 1px solid var(--c-border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        padding: 4rem 2rem;
        position: relative;
        overflow: hidden;
        color: #fff;
    }
    
    .dashboard-header::after {
        content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
        transform: rotate(30deg); pointer-events: none;
    }
    
    .dashboard-stats {
        padding: 2rem;
        display: flex; gap: 2rem; justify-content: space-around; flex-wrap: wrap;
        border-bottom: 1px solid var(--c-border); background: var(--c-surface);
    }
    
    .stat-item { text-align: center; flex: 1; min-width: 150px; }
    .stat-item .stat-value { font-size: 2rem; font-weight: 800; color: var(--c-ink); line-height: 1.2; }
    .stat-item .stat-label { font-size: 0.8125rem; font-weight: 600; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.5rem; }
    
    .dashboard-actions { padding: 2rem; }
    
    .action-card {
        display: flex; align-items: center; gap: 1rem; padding: 1.5rem;
        border: 1px solid var(--c-border); border-radius: var(--radius-xl);
        text-decoration: none; color: var(--c-ink); transition: all 0.3s ease;
        background: var(--c-bg-subtle);
    }
    
    .action-card:hover {
        background: var(--c-surface); border-color: var(--c-primary);
        box-shadow: var(--shadow-md); transform: translateY(-3px);
    }
    
    .action-icon {
        width: 56px; height: 56px; border-radius: var(--radius-lg);
        background: var(--c-primary-light); color: var(--c-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    
    .action-content h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.25rem; }
    .action-content p { color: var(--c-secondary); margin: 0; font-size: 0.875rem; }
</style>

<section class="user-dashboard">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="dashboard-card">
                    <div class="dashboard-header text-center">
                        <span class="badge rounded-pill bg-white text-primary mb-3 px-3 py-2 fw-semibold shadow-sm">Thành viên thân thiết</span>
                        <h1 class="display-6 fw-bold mb-2">Xin chào, {{ auth()->user()->name }}</h1>
                        <p class="mb-0 opacity-75 fs-5">Rất vui được gặp lại bạn tại Chill Drink</p>
                    </div>
                    
                    <div class="dashboard-stats">
                        <div class="stat-item">
                            <div class="stat-value text-primary">0</div>
                            <div class="stat-label">Đơn hàng đã đặt</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value text-primary">0</div>
                            <div class="stat-label">Điểm thưởng</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value text-primary">0</div>
                            <div class="stat-label">Voucher khả dụng</div>
                        </div>
                    </div>
                    
                    <div class="dashboard-actions row g-4">
                        <div class="col-md-6">
                            <a href="{{ route('profile.edit') }}" class="action-card">
                                <div class="action-icon"><i class="bi bi-person-gear"></i></div>
                                <div class="action-content">
                                    <h3>Thông tin cá nhân</h3>
                                    <p>Cập nhật tên, mật khẩu và địa chỉ</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('orders.index') }}" class="action-card">
                                <div class="action-icon"><i class="bi bi-receipt"></i></div>
                                <div class="action-content">
                                    <h3>Lịch sử đơn hàng</h3>
                                    <p>Xem lại các đồ uống đã đặt</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('products.index') }}" class="action-card">
                                <div class="action-icon"><i class="bi bi-cup-straw"></i></div>
                                <div class="action-content">
                                    <h3>Đặt món mới</h3>
                                    <p>Khám phá menu đồ uống tuyệt hảo</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="h-100">
                                @csrf
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="action-card h-100" style="background: #FEF2F2; border-color: #FEE2E2;">
                                    <div class="action-icon" style="background: #FEE2E2; color: #DC2626;"><i class="bi bi-box-arrow-right"></i></div>
                                    <div class="action-content">
                                        <h3 class="text-danger">Đăng xuất</h3>
                                        <p class="text-danger opacity-75">Kết thúc phiên làm việc</p>
                                    </div>
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
