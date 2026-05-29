@extends('layouts.admin')

@section('page-title', 'Tổng quát hệ thống')
@section('search-placeholder', 'Tìm báo cáo, đơn hàng...')

@section('content')
<style>
    /* Admin Dashboard Specific Styles */
    .dashboard-header { margin-bottom: 2rem; }
    
    .period-segmented-control {
        display: inline-flex; background: var(--a-border-light);
        padding: 4px; border-radius: var(--radius-full);
    }
    
    .period-segment {
        padding: 6px 16px; font-size: 0.8125rem; font-weight: 600;
        color: var(--a-muted); border-radius: var(--radius-full);
        cursor: pointer; transition: all 0.2s ease;
    }
    
    .period-segment:hover { color: var(--a-ink); }
    
    .period-segment.active {
        background: var(--a-surface); color: var(--a-primary);
        box-shadow: var(--shadow-sm);
    }

    .stat-card {
        padding: 1.5rem; position: relative; overflow: hidden;
        border: 1px solid var(--a-border); border-radius: var(--radius-xl);
        background: var(--a-surface); transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(13, 147, 115, 0.3);
    }

    .stat-icon {
        width: 48px; height: 48px;
        display: flex; align-items: center; justify-content: center;
        border-radius: var(--radius-lg); font-size: 1.25rem;
    }
    .stat-icon.primary { background: var(--a-primary-light); color: var(--a-primary); }
    .stat-icon.success { background: #D1FAE5; color: #10B981; }
    .stat-icon.warning { background: #FEF3C7; color: #F59E0B; }
    .stat-icon.info { background: #DBEAFE; color: #3B82F6; }

    .stat-value { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.03em; margin: 0.5rem 0 0.25rem; }
    .stat-label { color: var(--a-muted); font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    
    .stat-trend { font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: var(--radius-full); }
    .stat-trend.up { background: #D1FAE5; color: #059669; }
    .stat-trend.down { background: #FEE2E2; color: #DC2626; }

    /* CSS Mini Chart / Sparkline */
    .sparkline {
        position: absolute; bottom: 0; left: 0; right: 0; height: 40px;
        display: flex; align-items: flex-end; gap: 4px; padding: 0 1rem; opacity: 0.2;
    }
    .spark-bar { flex: 1; background: var(--a-primary); border-radius: 4px 4px 0 0; }
    
    /* Animated Chart Mockup */
    .chart-mockup {
        height: 300px; width: 100%; border-radius: var(--radius-lg);
        background: linear-gradient(180deg, var(--a-bg-subtle) 0%, rgba(241, 245, 244, 0.3) 100%);
        position: relative; overflow: hidden; display: flex; align-items: flex-end; justify-content: space-between;
        padding: 2rem 1rem 0; border: 1px dashed var(--a-border);
    }
    .chart-col {
        width: calc(100% / 12 - 10px); background: linear-gradient(180deg, var(--a-primary-light) 0%, var(--a-primary) 100%);
        border-radius: 6px 6px 0 0; position: relative; opacity: 0.8;
        transform-origin: bottom; animation: growBar 1.5s cubic-bezier(0.1, 0.8, 0.2, 1) forwards;
    }
    
    @keyframes growBar {
        from { transform: scaleY(0); }
        to { transform: scaleY(1); }
    }

    /* Table avatars */
    .avatar-sm { width: 32px; height: 32px; font-size: 0.75rem; }
    
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .status-dot.pending { background: #F59E0B; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
    .status-dot.completed { background: #10B981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
    .status-dot.cancelled { background: #EF4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }
</style>

<div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
    <div>
        <h2 class="h3 fw-bold mb-1">Hi, Admin 👋</h2>
        <p class="text-secondary mb-0">Đây là hoạt động kinh doanh hôm nay của cửa hàng.</p>
    </div>
    <div class="period-segmented-control">
        <div class="period-segment">Hôm nay</div>
        <div class="period-segment active">Tuần này</div>
        <div class="period-segment">Tháng này</div>
        <div class="period-segment">Năm nay</div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon primary"><i class="bi bi-wallet2"></i></div>
                <span class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 12.5%</span>
            </div>
            <div class="stat-label">Tổng doanh thu</div>
            <div class="stat-value">{{ number_format($totalRevenue, 0, ',', '.') }}đ</div>
            <div class="text-secondary small">So với tuần trước</div>
            
            <div class="sparkline">
                <div class="spark-bar" style="height: 30%"></div>
                <div class="spark-bar" style="height: 50%"></div>
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 70%"></div>
                <div class="spark-bar" style="height: 60%"></div>
                <div class="spark-bar" style="height: 90%"></div>
                <div class="spark-bar" style="height: 100%"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon info"><i class="bi bi-bag-check"></i></div>
                <span class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 8.2%</span>
            </div>
            <div class="stat-label">Đơn hàng mới</div>
            <div class="stat-value">{{ $totalOrders }}</div>
            <div class="text-secondary small">Đơn hàng giao thành công</div>
            
            <div class="sparkline" style="opacity: 0.15; filter: hue-rotate(180deg);">
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 50%"></div>
                <div class="spark-bar" style="height: 80%"></div>
                <div class="spark-bar" style="height: 60%"></div>
                <div class="spark-bar" style="height: 70%"></div>
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 90%"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon warning"><i class="bi bi-people"></i></div>
                <span class="stat-trend up"><i class="bi bi-arrow-up-short"></i> 5.1%</span>
            </div>
            <div class="stat-label">Khách hàng</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="text-secondary small">Khách hàng đăng ký mới</div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon success"><i class="bi bi-cup-straw"></i></div>
                <span class="stat-trend down"><i class="bi bi-arrow-down-short"></i> 1.2%</span>
            </div>
            <div class="stat-label">Sản phẩm menu</div>
            <div class="stat-value">{{ $totalProducts }}</div>
            <div class="text-secondary small">Sản phẩm đang bán</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">Phân tích doanh thu</h3>
                    <p class="text-secondary small mb-0">Thống kê doanh thu theo 7 ngày gần nhất</p>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Tuần này
                    </button>
                </div>
            </div>
            
            <div class="chart-mockup d-none d-md-flex">
                <div class="chart-col" style="height: 30%"></div>
                <div class="chart-col" style="height: 50%"></div>
                <div class="chart-col" style="height: 40%"></div>
                <div class="chart-col" style="height: 80%"></div>
                <div class="chart-col" style="height: 60%"></div>
                <div class="chart-col" style="height: 75%"></div>
                <div class="chart-col" style="height: 100%"></div>
            </div>
            <div class="d-md-none text-center py-5 rounded text-secondary bg-light mt-3">
                Vui lòng xem biểu đồ trên màn hình lớn
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h5 fw-bold mb-0">Món bán chạy</h3>
                <a href="{{ route('admin.products.index') }}" class="btn btn-link text-primary p-0 text-decoration-none small">Xem tất</a>
            </div>
            
            <div class="d-flex flex-column gap-3">
                @for($i=1; $i<=4; $i++)
                <div class="d-flex align-items-center gap-3 p-2 rounded-3" style="transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--a-bg-subtle)'" onmouseout="this.style.background='transparent'">
                    <div class="admin-thumb" style="width: 50px; height: 50px; border-radius: var(--radius-md);">
                        <img src="https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=100&q=85" alt="Product">
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6">Trà Sữa Trân Châu</div>
                        <div class="text-secondary small">#CD-TS-00{{$i}}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">{{ 150 - ($i*20) }} <span class="fw-normal text-secondary small">ly</span></div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div>
            <h3 class="h5 fw-bold mb-1">Đơn hàng mới nhất</h3>
            <p class="text-secondary small mb-0">Quản lý và theo dõi các đơn hàng vửa được đặt.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-sm rounded-pill px-4">Tất cả đơn hàng</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Đơn hàng</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Tổng tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    @php 
                        $statusClass = 'pending';
                        $statusText = 'Đang xử lý';
                        if(isset($order->status)) {
                            if($order->status === 'completed') { $statusClass = 'completed'; $statusText = 'Hoàn tất'; }
                            if($order->status === 'cancelled') { $statusClass = 'cancelled'; $statusText = 'Đã hủy'; }
                        }
                    @endphp
                    <tr>
                        <td class="fw-bold">
                            <a href="#" class="text-primary text-decoration-none">#{{ $order->id }}</a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="admin-avatar avatar-sm">{{ mb_substr($order->user->name ?? 'K', 0, 1) }}</span>
                                <span class="fw-semibold">{{ $order->user->name ?? 'Khách hàng' }}</span>
                            </div>
                        </td>
                        <td class="text-secondary small">{{ optional($order->created_at)->format('d/m/Y H:i') ?? 'Vừa xong' }}</td>
                        <td>
                            <span class="badge badge-soft-muted"><i class="bi bi-wallet2 me-1"></i> {{ strtoupper(str_replace('_', ' ', $order->payment_method ?? 'COD')) }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold d-inline-flex align-items-center" style="font-size: 0.8125rem;">
                                <span class="status-dot {{ $statusClass }}"></span> {{ $statusText }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">{{ number_format($order->total_price ?? $order->total ?? 0, 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="admin-empty-state mx-auto" style="max-width: 400px; border: none; background: transparent;">
                                <span class="admin-icon-dot mx-auto mb-3"><i class="bi bi-receipt"></i></span>
                                <div class="fw-bold text-dark mb-1">Chưa có đơn hàng nào</div>
                                <p class="small text-secondary mb-0">Các đơn hàng mới nhất sẽ hiển thị tại đây.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
