<nav class="navbar navbar-expand-lg bg-white shadow-sm shipper-topbar">
    <div class="container-fluid">
        <div>
            <span class="navbar-brand mb-0">Dashboard Shipper</span>
            <div class="text-secondary small">Quản lý giao hàng theo ca làm việc</div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3">
            @php($shipperUnreadNotifications = Auth::user()->unreadNotifications()->count())
            <a href="{{ route('shipper.notifications.index') }}"
               class="shipper-notification-link position-relative text-decoration-none"
               aria-label="Xem thông báo">
                <i class="fa-solid fa-bell"></i>
                <span class="shipper-notification-badge {{ $shipperUnreadNotifications > 0 ? '' : 'd-none' }}">
                    {{ $shipperUnreadNotifications > 99 ? '99+' : $shipperUnreadNotifications }}
                </span>
            </a>

            <div class="shipper-user-chip">
                <div class="shipper-user-name">{{ Auth::user()->name }}</div>
                <div class="shipper-user-role">Shipper</div>
            </div>
        </div>
    </div>
</nav>

<style>
    .shipper-topbar {
        min-height: 86px;
    }

    .shipper-notification-link {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #f8fafc;
        color: #0f172a;
        transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
    }

    .shipper-notification-link:hover {
        background: #e0f2fe;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
        color: #0f172a;
    }

    .shipper-notification-link i {
        font-size: 1.05rem;
    }

    .shipper-notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ef4444;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid #fff;
    }

    .shipper-user-chip {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.1;
    }

    .shipper-user-name {
        font-weight: 700;
        color: #0f172a;
    }

    .shipper-user-role {
        font-size: 0.78rem;
        color: #6b7280;
    }
</style>
