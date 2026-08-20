@extends('layouts.shipper')

@section('title', 'Thông báo')
@section('mobile-title', 'Thông báo')
@section('mobile-subtitle', $unreadCount > 0 ? $unreadCount.' thông báo chưa đọc' : 'Bạn đã đọc hết thông báo')

@section('content')
@php
    $activeFilter = request('filter', 'all');
    $iconMap = [
        'shipper_incident_reported' => 'fa-triangle-exclamation',
        'order_issue_created' => 'fa-comment-dots',
        'order_issue_status' => 'fa-comment-medical',
        'group_order_completed' => 'fa-people-group',
        'order_status_updated' => 'fa-receipt',
        'shipper_order_assigned' => 'fa-bell',
    ];
@endphp

<div class="ship-page-head">
    <div>
        <h1>Thông báo</h1>
        <p>Đơn mới, điều phối, sự cố và các cập nhật quan trọng đều nằm ở đây.</p>
    </div>
    <span class="ship-head-icon"><i class="fa-solid fa-bell"></i></span>
</div>

<div class="notify-summary">
    <div><span>Chưa đọc</span><strong>{{ (int)$unreadCount }}</strong></div>
    <div><span>Tổng</span><strong>{{ (int)$totalCount }}</strong></div>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('shipper.notifications.mark-all-read') }}">@csrf
            <button class="btn btn-dark btn-sm"><i class="fa-solid fa-check-double me-1"></i>Đọc tất cả</button>
        </form>
    @endif
</div>

<div class="notify-tabs" role="tablist">
    <a href="{{ route('shipper.notifications.index', ['filter'=>'all']) }}" class="{{ $activeFilter === 'all' ? 'active' : '' }}">Tất cả</a>
    <a href="{{ route('shipper.notifications.index', ['filter'=>'unread']) }}" class="{{ $activeFilter === 'unread' ? 'active' : '' }}">Chưa đọc @if($unreadCount > 0)<span>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif</a>
</div>

<div class="notify-list">
    @forelse($notifications as $notification)
        @php
            $data = $notification->data ?? [];
            $type = $data['type'] ?? null;
            $icon = $iconMap[$type] ?? 'fa-bell';
            $orderId = data_get($data, 'order_id');
            $link = data_get($data, 'link');
            $url = $link ?: (is_numeric($orderId) ? route('shipper.orders.show', (int)$orderId) : null);
            $isUnread = is_null($notification->read_at);
        @endphp
        <article class="notify-card {{ $isUnread ? 'is-unread' : '' }}">
            <div class="notify-icon"><i class="fa-solid {{ $icon }}"></i></div>
            <div class="notify-body">
                <div class="notify-top">
                    <b>{{ $data['title'] ?? 'Thông báo hệ thống' }}</b>
                    @if($isUnread)<span class="notify-dot" aria-label="Chưa đọc"></span>@endif
                </div>
                <p>{{ $data['message'] ?? 'Không có nội dung chi tiết.' }}</p>
                <div class="notify-meta">
                    <span>{{ $notification->created_at?->diffForHumans() }}</span>
                    @if(!empty($data['order_code']))<span>• {{ $data['order_code'] }}</span>@elseif(is_numeric($orderId))<span>• Đơn #{{ $orderId }}</span>@endif
                    @if(!empty($data['status_label']))<span>• {{ $data['status_label'] }}</span>@endif
                </div>
                @if($url)
                    <a href="{{ $url }}" class="notify-open">Mở chi tiết <i class="fa-solid fa-chevron-right"></i></a>
                @endif
            </div>
        </article>
    @empty
        <div class="ship-empty"><i class="fa-solid fa-bell-slash"></i><b>{{ $activeFilter === 'unread' ? 'Không còn thông báo chưa đọc' : 'Chưa có thông báo' }}</b><p>Khi có nhiệm vụ hoặc thay đổi quan trọng, hệ thống sẽ hiển thị tại đây và báo số đỏ trên thanh điều hướng.</p></div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div class="ship-pagination">{{ $notifications->withQueryString()->links('pagination::bootstrap-5') }}</div>
@endif

<style>
.notify-summary{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}.notify-summary>div{background:#fff;border:1px solid var(--ship-line);border-radius:17px;padding:11px 12px}.notify-summary span{display:block;font-size:10px;color:var(--ship-muted)}.notify-summary strong{font-size:20px;line-height:1;display:block;margin-top:5px}.notify-summary form{grid-column:1/-1}.notify-summary form .btn{width:100%}
.notify-tabs{display:flex;gap:7px;padding:4px;background:#e8eeeb;border-radius:14px;margin-bottom:10px}.notify-tabs a{flex:1;text-align:center;text-decoration:none;color:#71807a;font-size:10.5px;font-weight:800;padding:8px;border-radius:11px;position:relative}.notify-tabs a.active{background:#fff;color:var(--ship-green-dark);box-shadow:0 3px 9px rgba(0,0,0,.05)}.notify-tabs a span{display:inline-grid;place-items:center;min-width:16px;height:16px;border-radius:99px;background:#ef4444;color:#fff;font-size:8px;margin-left:3px}
.notify-list{display:grid;gap:8px}.notify-card{display:flex;gap:10px;background:#fff;border:1px solid var(--ship-line);border-radius:18px;padding:12px;box-shadow:0 4px 12px rgba(16,55,44,.035)}.notify-card.is-unread{border-color:#9ddfc3;background:linear-gradient(135deg,#effbf6,#fff)}.notify-icon{width:40px;height:40px;border-radius:14px;background:var(--ship-green-soft);color:var(--ship-green-dark);display:grid;place-items:center;flex:none}.notify-card.is-unread .notify-icon{background:#d9f5e9}.notify-body{min-width:0;flex:1}.notify-top{display:flex;align-items:flex-start;gap:7px}.notify-top b{font-size:12px;line-height:1.35;flex:1}.notify-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;flex:none;margin-top:4px}.notify-body p{font-size:10.5px;line-height:1.45;color:#5f706a;margin:4px 0 6px}.notify-meta{display:flex;flex-wrap:wrap;gap:3px;font-size:9px;color:#8a9893}.notify-open{display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:var(--ship-green-dark);font-size:10.5px;font-weight:850;margin-top:8px;padding-top:8px;border-top:1px solid #eef2f0}
</style>
@endsection
