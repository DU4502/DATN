<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Shipper') - Chill Drink</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/bootstrap-local.css', 'resources/js/app.js'])

    <style>
        :root{
            --ship-green:#159a67;
            --ship-green-dark:#0d7650;
            --ship-green-deep:#0a5e42;
            --ship-green-soft:#eaf8f2;
            --ship-orange:#ff8a2a;
            --ship-orange-soft:#fff2e7;
            --ship-blue:#277cff;
            --ship-red:#e65555;
            --ship-ink:#17322a;
            --ship-muted:#6f7f79;
            --ship-line:#e3eae7;
            --ship-bg:#f3f7f5;
            --ship-card:#fff;
            --ship-radius:20px;
            --ship-shadow:0 8px 24px rgba(18,52,42,.07);
        }

        *{box-sizing:border-box}
        html,body{margin:0;min-height:100%;background:#dfe8e4;color:var(--ship-ink)}
        body{
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            overflow-x:hidden;
            -webkit-font-smoothing:antialiased;
        }
        button,input,select,textarea{font:inherit}

        /* PC cũng chỉ render đúng một app mobile, không có layout desktop riêng. */
        .shipper-mobile-stage{
            width:100%;min-height:100vh;display:flex;justify-content:center;align-items:flex-start;
            background:
                radial-gradient(circle at 10% 0%,rgba(21,154,103,.09),transparent 28%),
                radial-gradient(circle at 100% 100%,rgba(255,138,42,.08),transparent 28%),
                #e7eeeb;
        }
        .shipper-mobile-app{
            width:min(100%,480px);min-height:100vh;position:relative;background:var(--ship-bg);
            box-shadow:0 0 40px rgba(11,54,42,.14);overflow-x:clip;container-type:inline-size;
        }

        .shipper-mobile-topbar{
            position:sticky;top:0;z-index:1040;min-height:64px;display:flex;align-items:center;gap:10px;
            padding:calc(8px + env(safe-area-inset-top)) 12px 8px;background:rgba(243,247,245,.96);
            backdrop-filter:blur(18px);border-bottom:1px solid rgba(227,234,231,.9)
        }
        .shipper-mobile-brand{display:flex;align-items:center;gap:9px;min-width:0;flex:1;text-decoration:none;color:var(--ship-ink)}
        .shipper-mobile-logo{
            width:40px;height:40px;border-radius:14px;background:linear-gradient(145deg,var(--ship-green),var(--ship-green-dark));
            color:#fff;display:grid;place-items:center;box-shadow:0 7px 17px rgba(21,154,103,.23);flex:none
        }
        .shipper-mobile-brand strong{display:block;font-size:13px;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .shipper-mobile-brand small{display:block;color:var(--ship-muted);font-size:10px;margin-top:3px}
        .shipper-mobile-actions{display:flex;align-items:center;gap:7px}
        .shipper-mobile-actions form{margin:0}
        .shipper-mobile-icon{
            width:40px;height:40px;border-radius:14px;border:1px solid var(--ship-line);background:#fff;color:var(--ship-ink);
            display:inline-flex;align-items:center;justify-content:center;text-decoration:none;position:relative;box-shadow:0 4px 12px rgba(0,0,0,.03);cursor:pointer
        }
        .shipper-mobile-logout{color:var(--ship-red)}
        .shipper-mobile-logout.is-blocked{color:#b56a13;background:var(--ship-orange-soft);border-color:#ffd6b3}
        .shipper-mobile-icon:active{transform:scale(.97)}
        .shipper-mobile-badge{
            position:absolute;right:-4px;top:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#ef4444;color:#fff;
            font-size:9px;font-weight:850;display:flex;align-items:center;justify-content:center;border:2px solid #fff
        }

        .shipper-mobile-content{padding:12px 12px calc(94px + env(safe-area-inset-bottom));min-height:calc(100vh - 64px)}
        .shipper-mobile-content > .container,.shipper-mobile-content > .container-fluid{padding:0!important;max-width:none!important}
        .shipper-mobile-content .card{border-radius:var(--ship-radius)!important;border:1px solid var(--ship-line)!important;box-shadow:var(--ship-shadow)!important}
        .shipper-mobile-content .leaflet-control-zoom{display:none!important}
        .shipper-mobile-content .btn{border-radius:14px;font-weight:750;min-height:42px}
        .shipper-mobile-content .btn-sm{min-height:36px}
        .shipper-mobile-content .form-control,.shipper-mobile-content .form-select{border-radius:14px;min-height:46px;border-color:var(--ship-line)}
        .shipper-mobile-content textarea.form-control{min-height:110px}
        .shipper-mobile-content .alert{border-radius:18px;font-size:12px;line-height:1.5}
        .shipper-mobile-content h1,.shipper-mobile-content h2,.shipper-mobile-content h3,.shipper-mobile-content h4,.shipper-mobile-content h5{color:var(--ship-ink)}

        /* Những trang cũ còn grid Bootstrap vẫn phải xếp theo app 480px dù viewport PC rất rộng. */
        .shipper-mobile-content .row > [class*="col-"]{width:100%!important;max-width:100%!important;flex:0 0 100%!important}
        .shipper-mobile-content .d-none.d-md-block,.shipper-mobile-content .d-none.d-lg-block,.shipper-mobile-content .d-none.d-xl-block{display:none!important}

        /* Mobile primitives dùng cho toàn bộ trang shipper. */
        .ship-page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin:2px 2px 12px}
        .ship-page-head h1{margin:0;font-size:20px;font-weight:850;letter-spacing:-.02em}
        .ship-page-head p{margin:4px 0 0;font-size:11.5px;line-height:1.45;color:var(--ship-muted)}
        .ship-page-head .ship-head-icon{width:42px;height:42px;flex:none;border-radius:15px;background:#fff;border:1px solid var(--ship-line);display:grid;place-items:center;color:var(--ship-green-dark);box-shadow:0 4px 12px rgba(0,0,0,.03)}

        .ship-section-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:18px 2px 9px}
        .ship-section-head h2{font-size:15px;font-weight:850;margin:0}
        .ship-section-head a,.ship-section-head button{font-size:11px;font-weight:800;color:var(--ship-green-dark);text-decoration:none;border:0;background:transparent;padding:4px}

        .ship-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
        .ship-stat-card{background:#fff;border:1px solid var(--ship-line);border-radius:18px;padding:13px;box-shadow:0 5px 15px rgba(16,55,44,.04);min-width:0}
        .ship-stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .ship-stat-icon{width:34px;height:34px;border-radius:12px;display:grid;place-items:center;background:var(--ship-green-soft);color:var(--ship-green-dark);font-size:14px;flex:none}
        .ship-stat-icon.orange{background:var(--ship-orange-soft);color:#c86815}.ship-stat-icon.blue{background:#edf4ff;color:#277cff}.ship-stat-icon.red{background:#fff0f0;color:var(--ship-red)}
        .ship-stat-label{font-size:10.5px;color:var(--ship-muted);line-height:1.2}
        .ship-stat-value{font-size:clamp(17px,5.5cqw,20px);font-weight:900;line-height:1.08;margin-top:8px;letter-spacing:-.03em;white-space:normal;overflow-wrap:anywhere}
        .ship-stat-note{font-size:9.5px;color:var(--ship-muted);margin-top:5px;white-space:normal;overflow-wrap:anywhere;line-height:1.35}

        .ship-profile-card{background:#fff;border:1px solid var(--ship-line);border-radius:22px;padding:15px;box-shadow:var(--ship-shadow)}
        .ship-profile-row{display:flex;align-items:center;gap:12px}.ship-profile-main{flex:1;min-width:0}
        .ship-avatar{width:48px;height:48px;border-radius:17px;background:#eaf2ff;color:#277cff;display:grid;place-items:center;font-size:20px;flex:none;overflow:hidden}
        .ship-avatar img{width:100%;height:100%;object-fit:cover}.ship-profile-main b{display:block;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ship-profile-main span{display:block;font-size:10.5px;color:var(--ship-muted);margin-top:3px}
        .ship-status-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border-radius:999px;font-size:10px;font-weight:850;white-space:nowrap;background:var(--ship-green-soft);color:var(--ship-green-dark)}
        .ship-status-pill.busy{background:#fff1c9;color:#9a6500}.ship-status-pill.offline{background:#eef1f0;color:#6e7b76}.ship-status-pill.returning{background:#eaf2ff;color:#246fd5}
        .ship-status-dot{width:7px;height:7px;border-radius:50%;background:currentColor}

        .ship-quick-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
        .ship-quick-link{min-width:0;background:#fff;border:1px solid var(--ship-line);border-radius:17px;padding:11px 5px;text-align:center;text-decoration:none;color:var(--ship-ink);box-shadow:0 4px 12px rgba(16,55,44,.035)}
        .ship-quick-link i{width:36px;height:36px;border-radius:12px;background:var(--ship-green-soft);color:var(--ship-green-dark);display:grid;place-items:center;margin:0 auto 7px;font-size:14px}
        .ship-quick-link:nth-child(even) i{background:var(--ship-orange-soft);color:#c86815}
        .ship-quick-link span{display:block;font-size:9.5px;font-weight:800;line-height:1.2}

        .ship-info-strip{background:linear-gradient(135deg,#dff7ef,#e4f5ff);border:1px solid #cfe9e2;border-radius:18px;padding:12px 13px;display:flex;gap:10px;align-items:flex-start}
        .ship-info-strip .strip-icon{width:36px;height:36px;border-radius:12px;background:#fff;color:var(--ship-green-dark);display:grid;place-items:center;flex:none}
        .ship-info-strip b{display:block;font-size:12px;margin-bottom:3px}.ship-info-strip p{margin:0;font-size:10.5px;line-height:1.45;color:#526a62}

        .ship-order-list{display:grid;gap:9px}
        .ship-order-card{background:#fff;border:1px solid var(--ship-line);border-radius:20px;padding:13px;box-shadow:0 5px 15px rgba(16,55,44,.04);position:relative;overflow:hidden}
        .ship-order-card.is-new{border-color:#9ddfc3;background:linear-gradient(180deg,#f1fcf7,#fff)}
        .ship-order-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.ship-order-code{font-size:12px;font-weight:900;color:var(--ship-blue)}
        .ship-order-time{font-size:9.5px;color:var(--ship-muted);margin-top:3px}
        .ship-badge{display:inline-flex;align-items:center;padding:6px 8px;border-radius:999px;font-size:9.5px;font-weight:850;background:#eef2f1;color:#5f6d68;white-space:nowrap}
        .ship-badge.info{background:#eaf2ff;color:#246fd5}.ship-badge.warn{background:#fff1d6;color:#9f6500}.ship-badge.success{background:var(--ship-green-soft);color:var(--ship-green-dark)}.ship-badge.danger{background:#fff0f0;color:#c74646}
        .ship-order-customer{display:flex;align-items:center;gap:8px;margin-top:11px}.ship-order-customer .mini-avatar{width:34px;height:34px;border-radius:12px;background:#edf4ff;color:#277cff;display:grid;place-items:center;flex:none}
        .ship-order-customer b{display:block;font-size:12px}.ship-order-customer span{display:block;font-size:10px;color:var(--ship-muted);margin-top:2px}
        .ship-address{display:flex;gap:8px;align-items:flex-start;margin-top:10px;padding:9px 10px;border-radius:14px;background:#f7f9f8;color:#596963;font-size:10.5px;line-height:1.42}.ship-address i{color:var(--ship-orange);margin-top:2px}
        .ship-order-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:11px}.ship-order-actions.one{grid-template-columns:1fr}.ship-order-actions .btn{font-size:11px;min-height:40px}
        .ship-order-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:9px}.ship-meta-chip{font-size:9.5px;padding:5px 7px;border-radius:999px;background:#f2f5f4;color:#60706a;font-weight:700}

        .ship-empty{background:#fff;border:1px dashed #ccd8d3;border-radius:22px;padding:30px 16px;text-align:center}.ship-empty i{width:58px;height:58px;border-radius:19px;background:var(--ship-green-soft);color:var(--ship-green-dark);display:grid;place-items:center;margin:0 auto 10px;font-size:22px}.ship-empty b{display:block;font-size:14px}.ship-empty p{margin:5px 0 0;font-size:11px;color:var(--ship-muted)}

        .ship-pagination{margin-top:10px;overflow:visible}.ship-pagination .pagination{margin:0;flex-wrap:wrap;gap:4px}.ship-pagination .page-link{border-radius:10px!important;margin:0;font-size:11px;min-width:34px;text-align:center}

        /* Không dùng bảng ngang cho trang shipper mới. Nếu một màn cũ còn table thì thu gọn tối đa. */
        .shipper-mobile-content .table-responsive{border-radius:18px;border:1px solid var(--ship-line);background:#fff;overflow:hidden;max-width:100%}.shipper-mobile-content table{font-size:.74rem;width:100%;table-layout:fixed}.shipper-mobile-content th,.shipper-mobile-content td{overflow-wrap:anywhere;word-break:break-word}

        /* Map/navigation: ưu tiên bản đồ, các panel desktop tự rơi xuống dưới. */
        .shipper-navigation-page{padding:0!important}.shipper-navigation-page .nav-map-card{border-radius:22px!important;overflow:hidden!important}.shipper-navigation-page .navigation-summary{border-radius:17px!important;margin:9px!important;left:0!important;right:0!important;top:0!important}.shipper-navigation-page .shipper-map-canvas{min-height:410px!important;height:52vh!important;max-height:570px!important}.shipper-navigation-page .map-bottom-controls{padding:8px!important}.shipper-navigation-page .map-bottom-controls>div{flex-wrap:wrap!important;overflow:visible}.shipper-navigation-page .map-bottom-controls .btn{font-size:10px;min-height:36px}.shipper-navigation-page .route-source-pill{font-size:9px!important;white-space:normal}.shipper-navigation-page .card-body{padding:13px!important}

        /* Profile cũ vẫn thuần mobile. */
        .profile-page .page-header{margin-bottom:12px!important}.profile-page .page-header .back-btn{display:none!important}.profile-page .profile-main-card,.profile-page .info-card,.profile-page .form-card{border-radius:20px!important}.profile-page .profile-cover{height:72px!important}.profile-page .shipper-avatar,.profile-page .avatar-default{width:88px!important;height:88px!important}.profile-page .profile-content{margin-top:-42px!important}.profile-page .form-body{padding:16px 16px 112px!important}.profile-page .form-footer{padding:12px 14px calc(12px + env(safe-area-inset-bottom))!important;position:sticky;bottom:74px;background:rgba(255,255,255,.97);backdrop-filter:blur(14px);z-index:8;box-shadow:0 -10px 24px rgba(18,52,42,.08)}

        .shipper-bottom-nav{
            position:fixed;left:50%;transform:translateX(-50%);bottom:0;width:min(100vw,480px);z-index:1050;
            display:grid;grid-template-columns:repeat(5,1fr);padding:7px 7px calc(7px + env(safe-area-inset-bottom));
            background:rgba(255,255,255,.97);backdrop-filter:blur(18px);border-top:1px solid var(--ship-line);box-shadow:0 -8px 24px rgba(18,52,42,.06)
        }
        .shipper-bottom-nav a{min-width:0;text-decoration:none;color:#8d9c96;border-radius:14px;padding:7px 2px 6px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;font-size:9px;font-weight:800;line-height:1.1;position:relative}
        .shipper-bottom-nav a i{font-size:18px}.shipper-bottom-nav a.active{background:var(--ship-green-soft);color:var(--ship-green-dark)}
        .shipper-nav-badge{position:absolute;top:2px;left:calc(50% + 7px);min-width:16px;height:16px;padding:0 3px;border-radius:999px;background:#ef4444;color:#fff;border:2px solid #fff;font-size:8px;display:grid;place-items:center;font-style:normal}


        /* Vuốt xác nhận: tránh chạm nhầm khi shipper đang di chuyển. */
        .ship-swipe-confirm{position:relative;width:100%;height:52px;border-radius:18px;background:#e9f4ef;border:1px solid #cfe5dc;overflow:hidden;user-select:none;-webkit-user-select:none;touch-action:pan-y;isolation:isolate}
        .ship-swipe-confirm[data-tone="orange"]{background:#fff1e5;border-color:#ffd6b3}.ship-swipe-confirm[data-tone="blue"]{background:#eaf2ff;border-color:#cddfff}
        .ship-swipe-fill{position:absolute;inset:0 auto 0 0;width:0;background:linear-gradient(90deg,rgba(21,154,103,.18),rgba(21,154,103,.32));z-index:0;pointer-events:none}
        .ship-swipe-confirm[data-tone="orange"] .ship-swipe-fill{background:linear-gradient(90deg,rgba(255,138,42,.16),rgba(255,138,42,.3))}.ship-swipe-confirm[data-tone="blue"] .ship-swipe-fill{background:linear-gradient(90deg,rgba(39,124,255,.14),rgba(39,124,255,.26))}
        .ship-swipe-label{position:absolute;inset:0 12px 0 62px;display:flex;align-items:center;justify-content:center;text-align:center;font-size:11px;font-weight:900;color:var(--ship-green-dark);line-height:1.2;pointer-events:none;z-index:1}
        .ship-swipe-confirm[data-tone="orange"] .ship-swipe-label{color:#ad5b17}.ship-swipe-confirm[data-tone="blue"] .ship-swipe-label{color:#2369c7}
        .ship-swipe-knob{position:absolute;left:4px;top:4px;width:44px;height:44px;border-radius:15px;background:linear-gradient(145deg,var(--ship-green),var(--ship-green-dark));color:#fff;display:grid;place-items:center;z-index:3;box-shadow:0 7px 16px rgba(21,154,103,.24);will-change:transform;transition:transform .18s ease}
        .ship-swipe-confirm[data-tone="orange"] .ship-swipe-knob{background:linear-gradient(145deg,#ff9c45,#ef7411);box-shadow:0 7px 16px rgba(255,138,42,.24)}.ship-swipe-confirm[data-tone="blue"] .ship-swipe-knob{background:linear-gradient(145deg,#438cff,#246bd6);box-shadow:0 7px 16px rgba(39,124,255,.24)}
        .ship-swipe-confirm.is-dragging .ship-swipe-knob{transition:none}.ship-swipe-confirm.is-complete .ship-swipe-knob{transition:transform .16s ease}
        .ship-swipe-confirm.is-disabled{opacity:.48;filter:grayscale(.25)}.ship-swipe-confirm.is-disabled .ship-swipe-knob{background:#9aa8a3;box-shadow:none}.ship-swipe-confirm.is-disabled .ship-swipe-label{color:#74817d}
        .ship-swipe-native-submit{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important;opacity:0!important;pointer-events:none!important}
        .toast-container.shipper-toast-mobile{position:fixed!important;top:68px!important;left:50%!important;right:auto!important;transform:translateX(-50%);padding:0 10px!important;width:min(100vw,480px)!important}.shipper-toast-mobile .toast{width:100%;border-radius:18px;overflow:hidden}

        /* Mọi lớp nổi của shipper đều bị khóa trong đúng khung điện thoại, kể cả khi chạy trên PC. */
        body .modal{left:50%;right:auto;width:min(100vw,480px);transform:translateX(-50%)}
        body .modal-backdrop{left:50%;right:auto;width:min(100vw,480px);transform:translateX(-50%)}
        body .modal-dialog{max-width:calc(100% - 20px);margin:1rem auto}
        body .modal-content{border-radius:22px;overflow:hidden;border:1px solid var(--ship-line)}
        body .offcanvas{max-width:min(92vw,440px)}

        @media(max-width:480px){.shipper-mobile-stage{background:var(--ship-bg)}.shipper-mobile-app{width:100%;box-shadow:none}.shipper-bottom-nav{left:0;transform:none;width:100%}.toast-container.shipper-toast-mobile{left:0!important;transform:none;width:100%!important}body .modal,body .modal-backdrop{left:0;right:0;width:100%;transform:none}}

        /* V8: khung mobile thật sự - không tràn ngang trên mọi cỡ điện thoại */
        .shipper-mobile-app,.shipper-mobile-content,.shipper-mobile-content *{min-width:0}
        .shipper-mobile-content{width:100%;max-width:100%;overflow-x:hidden}
        .shipper-mobile-content img,.shipper-mobile-content svg,.shipper-mobile-content video,.shipper-mobile-content canvas{max-width:100%}
        .shipper-mobile-content .table-responsive{max-width:100%;overflow-x:hidden!important}
        .shipper-mobile-content table{max-width:100%}
        .shipper-mobile-content .text-nowrap{white-space:normal!important}
        .shipper-mobile-content .card,.shipper-mobile-content .alert,.shipper-mobile-content .modal-content{max-width:100%}
        .shipper-mobile-content .btn,.shipper-mobile-content a,.shipper-mobile-content p,.shipper-mobile-content span,.shipper-mobile-content div{overflow-wrap:anywhere}
        .shipper-bottom-nav{padding-left:max(7px,env(safe-area-inset-left));padding-right:max(7px,env(safe-area-inset-right))}
        @media(max-width:390px){
            .shipper-mobile-content{padding-left:9px;padding-right:9px}
            .shipper-mobile-topbar{padding-left:10px;padding-right:10px}
            .shipper-mobile-logo{width:38px;height:38px;border-radius:13px}
            .shipper-mobile-icon{width:38px;height:38px;border-radius:13px}
            .shipper-bottom-nav a{font-size:8.5px;padding-left:0;padding-right:0}
            .shipper-bottom-nav a i{font-size:17px}
        }
        @media(max-width:340px){
            .shipper-mobile-content{padding-left:7px;padding-right:7px}
            .shipper-mobile-brand small{display:none}
            .shipper-bottom-nav a{font-size:8px}
            .shipper-bottom-nav a i{font-size:16px}
        }
    </style>

    @stack('styles')
</head>
<body>
@php
    $shipperLayoutUser = Auth::user();
    $shipperUnreadNotifications = $shipperLayoutUser->unreadNotifications()->count();
    $shipperLayoutProfile = $shipperLayoutUser->shipper;
    $shipperLayoutId = $shipperLayoutProfile?->id;
    $shipperHasActiveOrders = $shipperLayoutProfile?->hasActiveDeliveryOrders() ?? false;
    $shipperUnreadChats = 0;
    if ($shipperLayoutId && \Illuminate\Support\Facades\Schema::hasTable('delivery_order_messages')) {
        $shipperUnreadChats = \Illuminate\Support\Facades\DB::table('delivery_order_messages as messages')
            ->join('orders', 'orders.id', '=', 'messages.order_id')
            ->where('orders.shipper_id', $shipperLayoutId)
            ->where('messages.sender_type', 'customer')
            ->whereNull('messages.read_at')
            ->where('messages.created_at', '>=', now()->subHours(24))
            ->count();
    }
@endphp
<div class="shipper-mobile-stage">
    <div class="shipper-mobile-app">
        <header class="shipper-mobile-topbar">
            <a href="{{ route('shipper.dashboard') }}" class="shipper-mobile-brand">
                <span class="shipper-mobile-logo"><i class="fa-solid fa-motorcycle"></i></span>
                <span class="text-truncate">
                    <strong class="text-truncate">@yield('mobile-title', Auth::user()->name)</strong>
                    <small>@yield('mobile-subtitle', 'Shipper Chill Drink')</small>
                </span>
            </a>
            <div class="shipper-mobile-actions">
                <a href="{{ route('shipper.notifications.index') }}" class="shipper-mobile-icon" aria-label="Thông báo">
                    <i class="fa-solid fa-bell"></i>
                    @if($shipperUnreadNotifications > 0)
                        <span class="shipper-mobile-badge">{{ $shipperUnreadNotifications > 99 ? '99+' : $shipperUnreadNotifications }}</span>
                    @endif
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="shipper-mobile-icon shipper-mobile-logout {{ $shipperHasActiveOrders ? 'is-blocked' : '' }}"
                            aria-label="Đăng xuất và ngưng nhận đơn"
                            title="{{ $shipperHasActiveOrders ? 'Phải hoàn thành đơn hàng trước khi đăng xuất' : 'Đăng xuất và ngưng nhận đơn' }}"
                            data-shipper-logout
                            @if($shipperHasActiveOrders) data-shipper-logout-blocked @endif>
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="shipper-mobile-content">
            @if(session('error'))
                <div class="alert alert-danger mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>

        <nav class="shipper-bottom-nav" aria-label="Điều hướng shipper">
            <a href="{{ route('shipper.dashboard') }}" class="{{ request()->routeIs('shipper.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i><span>Trang chủ</span>
            </a>
            <a href="{{ route('shipper.orders') }}" class="{{ request()->routeIs('shipper.orders*') && !request()->routeIs('shipper.orders.show') ? 'active' : '' }}">
                <i class="fa-solid fa-box"></i><span>Đơn hàng</span>
            </a>
            <a href="{{ route('shipper.map') }}" class="{{ request()->routeIs('shipper.map*') || request()->routeIs('shipper.returning*') ? 'active' : '' }}">
                <i class="fa-solid fa-location-arrow"></i><span>Dẫn đường</span>
            </a>
            <a href="{{ route('shipper.chats.index') }}" class="{{ request()->routeIs('shipper.chats.*') ? 'active' : '' }}">
                <i class="fa-solid fa-comments"></i><span>Chat</span>
                @if($shipperUnreadChats > 0)<em class="shipper-nav-badge">{{ $shipperUnreadChats > 9 ? '9+' : $shipperUnreadChats }}</em>@endif
            </a>
            <a href="{{ route('shipper.profile') }}" class="{{ request()->routeIs('shipper.profile') || request()->routeIs('shipper.history') ? 'active' : '' }}">
                <i class="fa-solid fa-user"></i><span>Cá nhân</span>
            </a>
        </nav>

    </div>
</div>

<script>
(() => {
    const pulseUrl = @json(route('shipper.assignments.pulse'));
    const storageKey = 'chilldrink_shipper_last_assignment_notice';
    const initializedKey = 'chilldrink_shipper_assignment_pulse_initialized';
    const assignmentTsKey = 'chilldrink_shipper_last_assignment_ts';
    const pendingSoundKey = 'chilldrink_shipper_pending_assignment_sound';
    let audioContext = null;
    let lastPendingSoundAttemptAt = 0;
    if (!pulseUrl) return;

    function getAudioContext() {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return null;
        audioContext ??= new AudioCtx();
        return audioContext;
    }

    async function playAssignmentBell() {
        const context = getAudioContext();
        if (!context) return false;
        if (context.state === 'suspended') {
            await context.resume();
        }
        if (context.state !== 'running') {
            return false;
        }

        const compressor = context.createDynamicsCompressor();
        compressor.threshold.setValueAtTime(-22, context.currentTime);
        compressor.knee.setValueAtTime(18, context.currentTime);
        compressor.ratio.setValueAtTime(8, context.currentTime);
        compressor.attack.setValueAtTime(0.004, context.currentTime);
        compressor.release.setValueAtTime(0.18, context.currentTime);
        compressor.connect(context.destination);

        const playBell = (start, frequency) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'square';
            oscillator.frequency.setValueAtTime(frequency, start);
            oscillator.frequency.exponentialRampToValueAtTime(frequency * 1.18, start + 0.12);
            gain.gain.setValueAtTime(0.001, start);
            gain.gain.exponentialRampToValueAtTime(1.0, start + 0.014);
            gain.gain.exponentialRampToValueAtTime(0.001, start + 0.42);
            oscillator.connect(gain);
            gain.connect(compressor);
            oscillator.start(start);
            oscillator.stop(start + 0.44);
        };

        const now = context.currentTime + 0.03;
        [
            1046.5, 1318.5, 1568.0, 1318.5,
            1046.5, 1568.0, 1760.0, 1568.0,
            1318.5, 1046.5
        ].forEach((frequency, index) => {
            playBell(now + index * 0.32, frequency);
        });

        return true;
    }

    async function announceNewOrder(orderId = '', keepPending = false) {
        if (navigator.vibrate) navigator.vibrate([180, 90, 180, 90, 180, 120, 220]);

        try {
            if (orderId) sessionStorage.setItem(pendingSoundKey, orderId);
            const played = await playAssignmentBell();
            if (played && !keepPending) sessionStorage.removeItem(pendingSoundKey);
            return played;
        } catch (_) {
            return false;
        }
    }

    async function unlockAudioAndReplayPending() {
        const pendingOrderId = sessionStorage.getItem(pendingSoundKey);
        try {
            const context = getAudioContext();
            if (context?.state === 'suspended') await context.resume();
            if (pendingOrderId) await announceNewOrder(pendingOrderId);
        } catch (_) {}
    }
    window.addEventListener('pointerdown', unlockAudioAndReplayPending, {passive:true});
    window.addEventListener('keydown', unlockAudioAndReplayPending);

    async function pollAssignment() {
        if (document.hidden) return;
        try {
            const response = await fetch(pulseUrl, {headers:{'Accept':'application/json'}, cache:'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success) return;
            if (!data.order) {
                sessionStorage.setItem(initializedKey, '1');
                return;
            }
            const legacyOrderId = String(data.order.id);
            const orderId = String(data.order.assignment_key || data.order.id);
            const assignmentTs = Number(data.order.assignment_ts || 0);
            if ([orderId, legacyOrderId].includes(sessionStorage.getItem(pendingSoundKey))) {
                const now = Date.now();
                if (now - lastPendingSoundAttemptAt > 8000) {
                    lastPendingSoundAttemptAt = now;
                    announceNewOrder(orderId);
                }
            }
            if ([orderId, legacyOrderId].includes(sessionStorage.getItem(storageKey))) return;

            if (!sessionStorage.getItem(initializedKey)) {
                sessionStorage.setItem(initializedKey, '1');
                sessionStorage.setItem(storageKey, orderId);
                if (assignmentTs) sessionStorage.setItem(assignmentTsKey, String(assignmentTs));
                return;
            }

            const lastAssignmentTs = Number(sessionStorage.getItem(assignmentTsKey) || 0);
            if (assignmentTs && lastAssignmentTs && assignmentTs < lastAssignmentTs) {
                sessionStorage.setItem(storageKey, orderId);
                return;
            }

            sessionStorage.setItem(storageKey, orderId);
            if (assignmentTs) sessionStorage.setItem(assignmentTsKey, String(assignmentTs));
            const targetUrl = data.order.map_url || data.order.show_url;
            let willRedirect = false;
            if (targetUrl) {
                const target = new URL(targetUrl, location.origin);
                willRedirect = location.pathname !== target.pathname || location.search !== target.search;
                announceNewOrder(orderId, willRedirect);
                if (willRedirect) setTimeout(() => { location.href = target.href; }, 1200);
            } else {
                announceNewOrder(orderId);
            }
        } catch (_) {}
    }

    pollAssignment();
    unlockAudioAndReplayPending();
    setInterval(pollAssignment, 4000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) pollAssignment(); });
})();
</script>
<script>
(() => {
    const sliders = document.querySelectorAll('[data-swipe-submit]');
    sliders.forEach(slider => {
        const knob = slider.querySelector('[data-swipe-knob]');
        const fill = slider.querySelector('[data-swipe-fill]');
        const label = slider.querySelector('[data-swipe-label]');
        const native = slider.querySelector('.ship-swipe-native-submit');
        if (!knob || !fill || !native) return;

        let dragging = false;
        let startX = 0;
        let travel = 0;
        let maxTravel = 0;
        let pointerId = null;

        const sync = () => {
            const disabled = !!native.disabled;
            slider.classList.toggle('is-disabled', disabled);
            slider.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            if (label && native.textContent.trim()) label.textContent = native.textContent.trim();
        };
        sync();
        new MutationObserver(sync).observe(native, {attributes:true, childList:true, subtree:true, characterData:true});

        const reset = () => {
            dragging = false;
            pointerId = null;
            slider.classList.remove('is-dragging','is-complete');
            knob.style.transform = 'translateX(0px)';
            fill.style.width = '0px';
            travel = 0;
        };

        slider.addEventListener('pointerdown', event => {
            sync();
            if (native.disabled || slider.classList.contains('is-submitting')) return;
            if (event.button !== undefined && event.button !== 0) return;
            const bounds = slider.getBoundingClientRect();
            // Bắt buộc bắt đầu vuốt ở vùng tay nắm bên trái; chạm/miết giữa nút không thể xác nhận nhầm.
            if ((event.clientX - bounds.left) > (knob.offsetWidth + 18)) return;
            dragging = true;
            pointerId = event.pointerId;
            startX = event.clientX;
            maxTravel = Math.max(0, slider.clientWidth - knob.offsetWidth - 8);
            slider.classList.add('is-dragging');
            slider.setPointerCapture?.(pointerId);
        });

        slider.addEventListener('pointermove', event => {
            if (!dragging || event.pointerId !== pointerId) return;
            travel = Math.max(0, Math.min(maxTravel, event.clientX - startX));
            knob.style.transform = `translateX(${travel}px)`;
            fill.style.width = `${Math.min(slider.clientWidth, travel + knob.offsetWidth + 4)}px`;
        });

        const finish = event => {
            if (!dragging || (event.pointerId !== undefined && event.pointerId !== pointerId)) return;
            const passed = maxTravel > 0 && travel >= maxTravel * .86;
            dragging = false;
            slider.classList.remove('is-dragging');
            if (!passed) { reset(); return; }

            slider.classList.add('is-complete','is-submitting');
            knob.style.transform = `translateX(${maxTravel}px)`;
            fill.style.width = '100%';
            if (label) label.textContent = 'Đang xác nhận...';
            if (navigator.vibrate) navigator.vibrate(25);
            setTimeout(() => {
                if (native.form?.requestSubmit) {
                    native.form.requestSubmit(native);
                } else {
                    native.click();
                }
            }, 90);
        };

        slider.addEventListener('pointerup', finish);
        slider.addEventListener('pointercancel', reset);
        slider.addEventListener('lostpointercapture', () => { if (dragging) reset(); });
    });
})();
</script>
@stack('scripts')
</body>
</html>
