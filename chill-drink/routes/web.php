<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\GroupOrderController as AdminGroupOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DashboardDrilldownController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\ToppingController;
use App\Http\Controllers\Admin\BranchSlideController;
use App\Http\Controllers\Admin\StaffManagementController;
use App\Http\Controllers\Admin\ShipperIncidentController;
use App\Http\Controllers\Admin\OrderIssueReportController as AdminOrderIssueReportController;
use App\Http\Middleware\KeepSuperAdminContext;
use App\Http\Controllers\Admin\ProductAvailabilityController;
use App\Http\Controllers\Auth\GuestConvertController;
use App\Http\Controllers\Client\OrderLookupController;
use App\Http\Controllers\Client\DeliveryTrackingController;
use App\Http\Controllers\Client\ChatController;
use App\Http\Controllers\Client\GuestCheckoutController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffOrderController;
use App\Http\Controllers\Staff\StaffGroupOrderController;
use App\Http\Controllers\Staff\StaffChatController;
use App\Http\Controllers\Staff\StaffProductAvailabilityController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\GroupOrderController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProductController as ClientProductController;
use App\Http\Controllers\Client\ProductReviewController;
use App\Http\Controllers\Shipper\ShipController;
use App\Http\Controllers\Client\QuickOrderController;
use App\Http\Controllers\Client\VnpayController;
use App\Http\Controllers\Client\OrderIssueReportController as ClientOrderIssueReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderDeliveryChatController;
use App\Http\Controllers\OrderShipmentIncidentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/select-nearest-branch', [HomeController::class, 'selectNearestBranch'])->name('select-nearest-branch');

// Products
Route::get('/products', [ClientProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ClientProductController::class, 'show'])->name('products.show');

// Order Lookup
Route::get('/tra-cuu-don-hang', [OrderLookupController::class, 'index'])->name('order-lookup.index');
Route::post('/tra-cuu-don-hang', [OrderLookupController::class, 'search'])->name('order-lookup.search');
Route::get('/tra-cuu-don-hang/{order}/status', [OrderLookupController::class, 'status'])->name('order-lookup.status');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/vnpay/ipn', [VnpayController::class, 'ipn'])->name('vnpay.ipn');
Route::get('/vnpay/return', [VnpayController::class, 'return'])->name('vnpay.return');

// Guest checkout (no authentication required)
Route::prefix('checkout/guest')->name('checkout.guest.')->group(function () {
    Route::get('/', [GuestCheckoutController::class, 'index'])->name('index');
    Route::post('/info', [GuestCheckoutController::class, 'storeInfo'])->name('info.store');
    Route::get('/payment', [GuestCheckoutController::class, 'payment'])->name('payment');
    Route::post('/process', [GuestCheckoutController::class, 'process'])->name('process');
    Route::get('/pending-confirmation/{order}', [GuestCheckoutController::class, 'pendingConfirmation'])->name('pending-confirmation');
    Route::get('/confirm-email/{order}', [GuestCheckoutController::class, 'confirmEmail'])->name('confirm-email');
    Route::get('/track/{order}', [GuestCheckoutController::class, 'track'])
        ->middleware('signed')
        ->name('track');
    Route::get('/track/{order}/live', [DeliveryTrackingController::class, 'guest'])
        ->name('live');
    Route::get('/track/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'guestMessages'])
        ->name('delivery-chat.messages');
    Route::post('/track/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'guestSend'])
        ->middleware('throttle:30,1')
        ->name('delivery-chat.send');
});

Route::middleware(['auth', 'shipper'])
    ->prefix('shipper')
    ->name('shipper.')
    ->group(function () {

        // ==============================
        // DASHBOARD
        // ==============================
        Route::get('/assignments/pulse', [
            ShipController::class,
            'assignmentPulse',
        ])->name('assignments.pulse');

        Route::get('/dashboard', [
            ShipController::class,
            'dashboard'
        ])->name('dashboard');

        Route::get('/notifications', [
            ShipController::class,
            'notifications'
        ])->name('notifications.index');
        Route::post('/notifications/mark-all-read', [
            ShipController::class,
            'markAllNotificationsRead'
        ])->name('notifications.mark-all-read');

        // Hộp chat theo chuyến của shipper. Chi tiết cuộc chat dùng ?order={id}
        // để vẫn giữ toàn bộ UI trong một trang mobile duy nhất.
        Route::get('/chats', [
            ShipController::class,
            'chats'
        ])->name('chats.index');


        // ==============================
        // ĐƠN HÀNG
        // ==============================

        // Danh sách đơn
        Route::get('/orders', [
            ShipController::class,
            'orders'
        ])->name('orders');

        // Chi tiết đơn
        Route::get('/orders/{id}', [
            ShipController::class,
            'showOrder'
        ])->name('orders.show');

        // Nhận đơn
        Route::post('/orders/{id}/accept', [
            ShipController::class,
            'acceptOrder'
        ])->name('orders.accept');

        // Xác nhận đã lấy hàng tại cửa hàng
        Route::post('/orders/{id}/picked-up', [
            ShipController::class,
            'pickedUpOrder'
        ])->name('orders.picked-up');

        // Bắt đầu chặng giao tới khách
        Route::post('/orders/{id}/start', [
            ShipController::class,
            'startDelivery'
        ])->name('orders.start');

        // Shipper xác nhận thủ công đã tới điểm giao sau khi GPS đã xác nhận lớp 1.
        Route::post('/orders/{id}/arrived', [
            ShipController::class,
            'confirmCustomerArrival'
        ])->name('orders.arrived');

        // Tài xế thay thế xác nhận đã nhận bàn giao hàng từ tài xế cũ.
        Route::post('/orders/{id}/handover', [
            ShipController::class,
            'confirmHandover'
        ])->name('orders.handover');

        // Shipper giao xong: hoàn tất đơn và ghi nhận doanh thu ngay.
        Route::post('/orders/{id}/complete', [
            ShipController::class,
            'completeOrder'
        ])->name('orders.complete');

        // Legacy guard: route cũ được giữ nhưng backend luôn chặn shipper hủy/từ chối chuyến.
        Route::post('/orders/{id}/cancel', [
            ShipController::class,
            'cancelOrder'
        ])->name('orders.cancel');

        // Báo sự cố sau khi đã lấy hàng; không tự nhả đơn
        Route::post('/orders/{id}/issue', [
            ShipController::class,
            'reportIssue'
        ])->name('orders.issue');

        // Chat ngắn theo đúng chuyến giao (khách <-> shipper), tự hết hạn sau 24h.
        Route::get('/orders/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'shipperMessages'])->name('orders.delivery-chat.messages');
        Route::post('/orders/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'shipperSend'])->middleware('throttle:30,1')->name('orders.delivery-chat.send');


        // ==============================
        // TRẠNG THÁI SHIPPER
        // ==============================
        Route::post('/status', [
            ShipController::class,
            'updateStatus'
        ])->name('status.update');


        // ==============================
        // QUAY VỀ CHI NHÁNH / CÂN BẰNG ĐỘI
        // ==============================
        Route::get('/returning', [
            ShipController::class,
            'returning'
        ])->name('returning');

        Route::get('/returning/route', [
            ShipController::class,
            'returningRoute'
        ])->name('returning.route');


        // ==============================
        // VỊ TRÍ
        // ==============================
        Route::post('/location', [
            ShipController::class,
            'updateLocation'
        ])->name('location.update');


        // ==============================
        // BẢN ĐỒ
        // ==============================
        Route::get('/map/{id?}', [
            ShipController::class,
            'map'
        ])->name('map');

        // Dữ liệu tuyến cho chính map hiện tại (server tự xác định điểm đến)
        Route::get('/map/{id}/route', [
            ShipController::class,
            'routeData'
        ])->name('map.route');

        // TTS dẫn đường cố định: mọi thiết bị dùng cùng một voice phía server.
        Route::post('/navigation/voice', [
            ShipController::class,
            'navigationVoice'
        ])->middleware('throttle:20,1')->name('navigation.voice');


        // ==============================
        // LỊCH SỬ GIAO HÀNG
        // ==============================
        Route::get('/history', function () {
            $shipper = \App\Models\Shipper::where(
                'user_id',
                auth()->id()
            )->firstOrFail();

            $completedStatuses = [
                \App\Support\OrderStatus::DELIVERED,
                \App\Support\OrderStatus::COMPLETED,
            ];
            $completedQuery = \App\Models\Order::where('shipper_id', $shipper->id)
                ->whereIn('status', $completedStatuses);

            $periods = [
                'day' => [
                    'label' => 'Hôm nay',
                    'from' => now()->startOfDay(),
                    'to' => now()->endOfDay(),
                ],
                'week' => [
                    'label' => 'Tuần này',
                    'from' => now()->startOfWeek(),
                    'to' => now()->endOfWeek(),
                ],
                'month' => [
                    'label' => 'Tháng này',
                    'from' => now()->startOfMonth(),
                    'to' => now()->endOfMonth(),
                ],
                'year' => [
                    'label' => 'Năm nay',
                    'from' => now()->startOfYear(),
                    'to' => now()->endOfYear(),
                ],
            ];
            $incomeSummary = collect($periods)
                ->map(function (array $period) use ($completedQuery) {
                    $query = (clone $completedQuery)
                        ->whereBetween('updated_at', [$period['from'], $period['to']]);

                    return [
                        'label' => $period['label'],
                        'orders' => (int) $query->count(),
                        'amount' => (int) (clone $query)->sum('shipping_fee'),
                    ];
                })
                ->all();

            $incomePeriod = request('income_period', 'day');
            if (! array_key_exists($incomePeriod, $periods)) {
                $incomePeriod = 'day';
            }

            $incomeDetailOrders = (clone $completedQuery)
                ->whereBetween('updated_at', [$periods[$incomePeriod]['from'], $periods[$incomePeriod]['to']])
                ->latest('updated_at')
                ->get(['id', 'order_code', 'shipping_fee', 'status', 'updated_at']);

            $incomeDetail = match ($incomePeriod) {
                'week' => $incomeDetailOrders
                    ->groupBy(fn ($order) => $order->updated_at?->format('Y-m-d') ?? '')
                    ->map(fn ($items, $key) => [
                        'label' => \Illuminate\Support\Carbon::parse($key)->format('d/m/Y'),
                        'orders' => $items->count(),
                        'amount' => (int) $items->sum('shipping_fee'),
                    ])
                    ->values(),
                'month' => $incomeDetailOrders
                    ->groupBy(fn ($order) => 'Tuần '.$order->updated_at?->weekOfMonth)
                    ->map(fn ($items, $key) => [
                        'label' => $key,
                        'orders' => $items->count(),
                        'amount' => (int) $items->sum('shipping_fee'),
                    ])
                    ->values(),
                'year' => $incomeDetailOrders
                    ->groupBy(fn ($order) => $order->updated_at?->format('m/Y') ?? '')
                    ->map(fn ($items, $key) => [
                        'label' => \Illuminate\Support\Carbon::createFromFormat('m/Y', $key)->format('m/Y'),
                        'orders' => $items->count(),
                        'amount' => (int) $items->sum('shipping_fee'),
                    ])
                    ->values(),
                default => $incomeDetailOrders
                    ->groupBy(fn ($order) => $order->updated_at?->format('H:00') ?? '')
                    ->map(fn ($items, $key) => [
                        'label' => $key,
                        'orders' => $items->count(),
                        'amount' => (int) $items->sum('shipping_fee'),
                    ])
                    ->values(),
            };

            $orders = \App\Models\Order::where('shipper_id', $shipper->id)
                ->whereIn('status', [
                    'delivered',
                    'completed'
                ])
                ->latest()
                ->paginate(10);

            return view(
                'shipper.history',
                compact('orders', 'shipper', 'incomeSummary', 'incomeDetail', 'incomePeriod')
            );
        })->name('history');


    // ==============================
    // PROFILE
    // ==============================
    // Trang cá nhân
    Route::get('/profile', [
        ShipController::class,
        'profile'
    ])->name('profile');

    // Cập nhật cá nhân
    Route::put('/profile', [
        ShipController::class,
        'updateProfile'
    ])->name('profile.update');
    });


Route::post('/register/guest-convert', [GuestConvertController::class, 'store'])
    ->middleware('guest')
    ->name('register.guest-convert');

Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/vnpay/payment/{order}', [VnpayController::class, 'payment'])->name('vnpay.payment');

// Checkout (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->middleware('verified')->name('checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->middleware('verified')->name('checkout.process');
    Route::post('/checkout/addresses', [CheckoutController::class, 'storeAddress'])->middleware('verified')->name('checkout.addresses.store');
    Route::put('/checkout/addresses/{address}', [CheckoutController::class, 'updateAddress'])->middleware('verified')->name('checkout.addresses.update');
    Route::patch('/checkout/address/primary', [CheckoutController::class, 'updatePrimaryAddress'])->middleware('verified')->name('checkout.addresses.primary.update');
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');

    Route::get('/group-orders/join/{code}', [GroupOrderController::class, 'show'])->name('group-orders.show');
    Route::post('/group-orders/join/{code}/presence', [GroupOrderController::class, 'presence'])->name('group-orders.presence');
    Route::get('/group-orders/join/{code}/state', [GroupOrderController::class, 'state'])->name('group-orders.state');
    Route::post('/group-orders/join/{code}/leave', [GroupOrderController::class, 'leave'])->name('group-orders.leave');
    Route::post('/group-orders/join/{code}/leave-room', [GroupOrderController::class, 'leaveRoom'])->name('group-orders.leave-room');
    Route::get('/group-orders/join/{code}/messages', [GroupOrderController::class, 'messages'])->name('group-orders.messages');
    Route::post('/group-orders/join/{code}/messages', [GroupOrderController::class, 'sendMessage'])->name('group-orders.messages.send');
    Route::post('/group-orders/join/{code}/messages/read', [GroupOrderController::class, 'readMessages'])->name('group-orders.messages.read');
    Route::post('/group-orders/join/{code}', [GroupOrderController::class, 'join'])->name('group-orders.join');
    Route::post('/group-orders/join/{code}/items', [GroupOrderController::class, 'addItem'])->name('group-orders.items.store');
    Route::patch('/group-orders/join/{code}/items/{item}/increment', [GroupOrderController::class, 'incrementItem'])->name('group-orders.items.increment');
    Route::patch('/group-orders/join/{code}/items/{item}/decrement', [GroupOrderController::class, 'decrementItem'])->name('group-orders.items.decrement');
    Route::delete('/group-orders/join/{code}/items/{item}', [GroupOrderController::class, 'removeItem'])->name('group-orders.items.destroy');
    Route::get('/group-orders', [GroupOrderController::class, 'index'])->name('group-orders.index');
    Route::get('/group-orders/create', [GroupOrderController::class, 'create'])->name('group-orders.create');
    Route::post('/group-orders', [GroupOrderController::class, 'store'])->name('group-orders.store');
    Route::post('/group-orders/{code}/close', [GroupOrderController::class, 'close'])->name('group-orders.close');
    Route::post('/group-orders/{code}/edit-checkout', [GroupOrderController::class, 'editCheckout'])->name('group-orders.edit-checkout');
    Route::post('/group-orders/{code}/cancel', [GroupOrderController::class, 'cancel'])->name('group-orders.cancel');
    Route::post('/group-orders/pending-checkout/resume', [GroupOrderController::class, 'resumePendingCheckout'])->name('group-orders.pending-checkout.resume');
    Route::post('/group-orders/{code}/resume', [GroupOrderController::class, 'resume'])->name('group-orders.resume');
    Route::get('/favorites', [QuickOrderController::class, 'favorites'])->name('favorites.index');
    Route::post('/favorites/{product}', [QuickOrderController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::post('/orders/{order}/reorder', [QuickOrderController::class, 'reorderOrder'])->name('orders.reorder');
    Route::post('/orders/{order}/items/{item}/reorder', [QuickOrderController::class, 'reorderItem'])->name('orders.items.reorder');
    Route::post('/products/{product}/taste-profile', [QuickOrderController::class, 'saveTaste'])->name('taste-profiles.store');
});

// Chat routes (client) — không yêu cầu auth, dùng guest_token để xác thực
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'getOrCreateConversation'])->name('index');
    Route::get('/nearest-branches', [ChatController::class, 'nearestBranches'])->name('nearest-branches');
    Route::post('/guest-init', [ChatController::class, 'guestInit'])->name('guest-init');
    Route::post('/select-branch', [ChatController::class, 'selectBranch'])->name('select-branch');
    Route::get('/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/send', [ChatController::class, 'send'])
        ->middleware('throttle:15,1')
        ->name('send');
    Route::post('/end-session', [ChatController::class, 'endSession'])->name('end-session');
});

// Chat routes (admin/cskh)
Route::prefix('admin/chat')->name('admin.chat.')->middleware(['auth', 'cskh'])->group(function () {
    Route::get('/', [AdminChatController::class, 'index'])->name('index');
    Route::get('/conversations', [AdminChatController::class, 'conversationList'])->name('conversations');
    Route::get('/unread-count', [AdminChatController::class, 'unreadCount'])->name('unread-count');
    Route::get('/order-issues', [AdminOrderIssueReportController::class, 'index'])->name('order-issues.index');
    Route::get('/order-issues-pending/count', [AdminOrderIssueReportController::class, 'pendingCount'])->name('order-issues.pending-count');
    Route::patch('/order-issues/{issue}', [AdminOrderIssueReportController::class, 'update'])->name('order-issues.update');
    Route::get('/order-issues/{issue}/evidence', [AdminOrderIssueReportController::class, 'evidence'])->name('order-issues.evidence');
    Route::get('/{conversation}/messages', [AdminChatController::class, 'messages'])->name('messages');
    Route::get('/{conversation}', [AdminChatController::class, 'show'])->name('show');
    Route::post('/{conversation}/reply', [AdminChatController::class, 'reply'])->name('reply');
    Route::patch('/{conversation}/close', [AdminChatController::class, 'close'])->name('close');
});

/*
|--------------------------------------------------------------------------
| User Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.super-admin');
        }

        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (auth()->user()->isCskh()) {
            return redirect()->route('admin.chat.index');
        }

        if (auth()->user()->isStaffOnly()) {
            return redirect()->route('staff.dashboard');
        }

        if (auth()->user()->isShipper()) {
            return redirect()->route('shipper.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/orders', [ProfileController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}/track', [DeliveryTrackingController::class, 'show'])->name('orders.track');
    Route::get('/orders/{order}/delivery-tracking', [DeliveryTrackingController::class, 'authenticated'])->name('orders.delivery-tracking');
    Route::get('/orders/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'customerMessages'])->name('orders.delivery-chat.messages');
    Route::post('/orders/{order}/delivery-chat/messages', [OrderDeliveryChatController::class, 'customerSend'])->middleware('throttle:30,1')->name('orders.delivery-chat.send');
    Route::get('/orders/{order}/issues', [ClientOrderIssueReportController::class, 'create'])->name('orders.issues.create');
    Route::post('/orders/{order}/issues', [ClientOrderIssueReportController::class, 'store'])->name('orders.issues.store');
    Route::get('/orders/{order}/issues/status', [ClientOrderIssueReportController::class, 'status'])->name('orders.issues.status');
    Route::post('/orders/{order}/issues/{issue}/confirm', [ClientOrderIssueReportController::class, 'confirmResolution'])->name('orders.issues.confirm');
    Route::post('/orders/{order}/cancel', [ProfileController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{order}/confirm-received', [ProfileController::class, 'confirmReceived'])->name('orders.confirm-received');
    Route::get('/notifications/feed', [ProfileController::class, 'notificationsFeed'])->name('notifications.feed');
    Route::post('/notifications/mark-all-read', [ProfileController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
    Route::redirect('/profile/orders', '/orders')->name('profile.orders');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/data-export', [ProfileController::class, 'exportData'])->name('profile.data-export');
    
    // Address Book Management
    Route::get('/profile/addresses', [\App\Http\Controllers\Client\ProfileAddressController::class, 'index'])->name('profile.addresses.index');
    Route::post('/profile/addresses', [\App\Http\Controllers\Client\ProfileAddressController::class, 'store'])->name('profile.addresses.store');
    Route::put('/profile/addresses/{address}', [\App\Http\Controllers\Client\ProfileAddressController::class, 'update'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [\App\Http\Controllers\Client\ProfileAddressController::class, 'destroy'])->name('profile.addresses.destroy');
    Route::patch('/profile/addresses/{address}/set-default', [\App\Http\Controllers\Client\ProfileAddressController::class, 'setDefault'])->name('profile.addresses.set-default');

    // Loyalty Points
    Route::middleware('verified')->group(function () {
        Route::get('/loyalty-points', [\App\Http\Controllers\Client\LoyaltyPointController::class, 'index'])->name('loyalty.index');
        Route::post('/loyalty-points/redeem/{voucher}', [\App\Http\Controllers\Client\LoyaltyPointController::class, 'redeemVoucher'])->name('loyalty.redeem-voucher');
    });
});

Route::view('/dieu-khoan', 'legal.page', ['page' => 'terms'])->name('legal.terms');
Route::view('/doi-tra', 'legal.page', ['page' => 'returns'])->name('legal.returns');
Route::view('/quyen-rieng-tu', 'legal.page', ['page' => 'privacy'])->name('legal.privacy');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------https://antigravity.google/support
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin', KeepSuperAdminContext::class])->group(function () {
    Route::get('/super-admin', [SuperAdminController::class, 'index'])->name('super-admin');
    Route::get('/super-admin/dashboard/drilldown', DashboardDrilldownController::class)->name('super-admin.dashboard.drilldown');
    Route::post('/super-admin/admins', [SuperAdminController::class, 'storeAdmin'])->name('super-admin.admins.store');
    Route::post('/super-admin/staff', [SuperAdminController::class, 'storeStaff'])->name('super-admin.staff.store');
    Route::patch('/super-admin/admins/{user}/branch', [SuperAdminController::class, 'updateBranch'])->name('super-admin.update-branch');
    Route::patch('/super-admin/admins/{user}/role', [SuperAdminController::class, 'updateRole'])->name('super-admin.update-role');
    Route::post('/super-admin/admins/{user}/reset-password', [SuperAdminController::class, 'resetAdminPassword'])->name('super-admin.reset-password');
    Route::post('/super-admin/impersonate/{user}', [SuperAdminController::class, 'impersonate'])->name('super-admin.impersonate');
    Route::post('/super-admin/leave-impersonation', [SuperAdminController::class, 'leaveImpersonation'])->name('super-admin.leave-impersonation');
    Route::patch('/super-admin/staff/{user}/branch', [SuperAdminController::class, 'updateStaffBranch'])->name('super-admin.staff.update-branch');
    Route::patch('/super-admin/staff/{user}/toggle-status', [SuperAdminController::class, 'toggleStaffStatus'])->name('super-admin.staff.toggle-status');
    Route::delete('/super-admin/staff/{user}', [SuperAdminController::class, 'destroyStaff'])->name('super-admin.staff.destroy');
    Route::get('/preview/admin', [SuperAdminController::class, 'enterAdminWorkspace'])->name('preview-admin');
    Route::get('/preview/admin/exit', [SuperAdminController::class, 'exitAdminWorkspace'])->name('preview-admin.exit');

    // Store-management pages opened from the Super Admin sidebar keep the
    // Super Admin URL/context instead of falling back to /admin/*.
    Route::prefix('super-admin')->name('super-admin.manage.')->group(function () {
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
        Route::get('/vouchers/{voucher}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');

        Route::get('/toppings', [ToppingController::class, 'index'])->name('toppings.index');

        Route::get('/products/trash', [AdminProductController::class, 'trash'])->name('products.trash');
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');

        Route::get('/categories/trash', [CategoryController::class, 'trash'])->name('categories.trash');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');

        Route::get('/slides/trash', [BranchSlideController::class, 'trash'])->name('slides.trash');
        Route::get('/slides', [BranchSlideController::class, 'index'])->name('slides.index');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/recent', [OrderController::class, 'recent'])->name('orders.recent');
        Route::get('/orders/pending-alerts', [OrderController::class, 'pendingAlerts'])->name('orders.pending-alerts');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('/orders/{order}/shipper-incident/resolve', [OrderShipmentIncidentController::class, 'resolve'])->name('orders.shipper-incident.resolve');
        Route::get('/shipper-incidents', [ShipperIncidentController::class, 'index'])->name('shipper-incidents.index');
        Route::get('/shipper-incidents/feed', [ShipperIncidentController::class, 'feed'])->name('shipper-incidents.feed');
        Route::get('/order-issues', [AdminOrderIssueReportController::class, 'index'])->name('order-issues.index');
        Route::get('/order-issues-pending/count', [AdminOrderIssueReportController::class, 'pendingCount'])->name('order-issues.pending-count');
        Route::patch('/order-issues/{issue}', [AdminOrderIssueReportController::class, 'update'])->name('order-issues.update');
        Route::get('/order-issues/{issue}/evidence', [AdminOrderIssueReportController::class, 'evidence'])->name('order-issues.evidence');
        Route::get('/group-orders', [AdminGroupOrderController::class, 'index'])->name('group-orders.index');
        Route::get('/group-orders/{groupOrder}', [AdminGroupOrderController::class, 'show'])->name('group-orders.show');

        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');

        Route::get('/staff', [StaffManagementController::class, 'index'])->name('staff.index');
        Route::put('/staff/delivery-fee-settings', [StaffManagementController::class, 'updateDeliveryFeeSettings'])
            ->name('staff.delivery-fee-settings.update');
    });

    // Branch Management
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    Route::patch('/branches/{branch}/status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', KeepSuperAdminContext::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'exportTimeComparison'])->name('dashboard.export');

    // JSON endpoint for dashboard data (AJAX)
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('admin.dashboard.data');
    Route::get('/dashboard/drilldown', DashboardDrilldownController::class)->name('dashboard.drilldown');

    // Voucher Management
    Route::resource('vouchers', VoucherController::class)->except(['show']);

    // Topping Management
    Route::resource('toppings', ToppingController::class)->only(['index', 'store', 'update', 'destroy']);

    // Product Trash Management
    Route::get('/products/trash', [AdminProductController::class, 'trash'])->name('products.trash');
    Route::post('/products/{id}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
    Route::delete('/products/{id}/force-delete', [AdminProductController::class, 'forceDelete'])->name('products.force-delete');

    // Product Management
    Route::resource('products', AdminProductController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Category Management
    Route::get('/categories/trash', [CategoryController::class, 'trash'])->name('categories.trash');
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::delete('/categories/{id}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.force-delete');
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Order Management
    Route::get('orders/recent', [OrderController::class, 'recent'])->name('orders.recent');
    Route::get('orders/pending-alerts', [OrderController::class, 'pendingAlerts'])->name('orders.pending-alerts');
    Route::resource('orders', OrderController::class)->only(['index']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.updateStatus');
    Route::post('orders/{order}/shipper-incident/resolve', [OrderShipmentIncidentController::class, 'resolve'])
        ->name('orders.shipper-incident.resolve');
    Route::get('shipper-incidents', [ShipperIncidentController::class, 'index'])->name('shipper-incidents.index');
    Route::get('shipper-incidents/feed', [ShipperIncidentController::class, 'feed'])->name('shipper-incidents.feed');
    Route::get('order-issues', [AdminOrderIssueReportController::class, 'index'])->name('order-issues.index');
    Route::get('order-issues-pending/count', [AdminOrderIssueReportController::class, 'pendingCount'])->name('order-issues.pending-count');
    Route::patch('order-issues/{issue}', [AdminOrderIssueReportController::class, 'update'])->name('order-issues.update');
    Route::get('order-issues/{issue}/evidence', [AdminOrderIssueReportController::class, 'evidence'])->name('order-issues.evidence');
    Route::resource('group-orders', AdminGroupOrderController::class)->only(['index', 'show']);
    Route::put('group-orders/{groupOrder}/status', [AdminGroupOrderController::class, 'updateStatus'])->name('group-orders.updateStatus');

    // Review Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::patch('/reviews/{review}/status', [ReviewController::class, 'toggleStatus'])->name('reviews.toggle-status');

    // User Management
    Route::patch('/users/bulk-toggle-status', [UserController::class, 'bulkToggleStatus'])->name('users.bulk-toggle-status');
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('users', UserController::class)->only(['index', 'show', 'edit', 'update']);

    // Slideshow Management
    Route::get('/slides', [BranchSlideController::class, 'index'])->name('slides.index');
    Route::post('/slides', [BranchSlideController::class, 'store'])->name('slides.store');
    Route::put('/slides/{slide}', [BranchSlideController::class, 'update'])->name('slides.update');
    Route::delete('/slides/{slide}', [BranchSlideController::class, 'destroy'])->name('slides.destroy');
    
    // Slide Trash Management
    Route::get('/slides/trash', [BranchSlideController::class, 'trash'])->name('slides.trash');
    Route::post('/slides/{id}/restore', [BranchSlideController::class, 'restore'])->name('slides.restore');
    Route::delete('/slides/{id}/force-delete', [BranchSlideController::class, 'forceDelete'])->name('slides.force-delete');

    Route::patch('/products/{productId}/branches/{branch}/availability', [ProductAvailabilityController::class, 'update'])
        ->name('products.branches.availability.update');

    // Staff Management (Admin và Super Admin quản lý nhân viên)
    Route::get('/staff', [StaffManagementController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffManagementController::class, 'store'])->name('staff.store');
    Route::put('/staff/{user}', [StaffManagementController::class, 'update'])->name('staff.update');
    Route::patch('/staff/{user}/toggle-status', [StaffManagementController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::patch('/staff/{user}/branch', [StaffManagementController::class, 'updateBranch'])->name('staff.update-branch');
    Route::delete('/staff/{user}', [StaffManagementController::class, 'destroy'])->name('staff.destroy');
});

/*
|--------------------------------------------------------------------------
| Staff Routes (role_id = 5) — Nhân viên
|--------------------------------------------------------------------------
*/

Route::prefix('staff')->name('staff.')->middleware(['auth', 'staff'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

    // Đơn hàng
    Route::get('/orders', [StaffOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/pending-alerts', [StaffOrderController::class, 'pendingAlerts'])->name('orders.pending-alerts');
    Route::put('/orders/{id}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.updateStatus');

    // Tình trạng bán sản phẩm tại đúng chi nhánh của Staff
    Route::get('/products/availability', [StaffProductAvailabilityController::class, 'index'])
        ->name('products.availability.index');
    Route::patch('/products/{product}/availability', [StaffProductAvailabilityController::class, 'update'])
        ->name('products.availability.update');

    // Đơn nhóm
    Route::get('/group-orders', [StaffGroupOrderController::class, 'index'])->name('group-orders.index');
    Route::get('/group-orders/{groupOrder}', [StaffGroupOrderController::class, 'show'])->name('group-orders.show');
    Route::put('/group-orders/{groupOrder}/status', [StaffGroupOrderController::class, 'updateStatus'])->name('group-orders.updateStatus');

    // Chat
    Route::get('/chat', [StaffChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/conversations', [StaffChatController::class, 'conversationList'])->name('chat.conversations');
    Route::get('/chat/unread-count', [StaffChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::get('/chat/{conversation}/messages', [StaffChatController::class, 'messages'])->name('chat.messages');
    Route::get('/chat/{conversation}', [StaffChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/reply', [StaffChatController::class, 'reply'])->name('chat.reply');
    Route::patch('/chat/{conversation}/close', [StaffChatController::class, 'close'])->name('chat.close');
});

require __DIR__.'/auth.php';


/* V27_BRANCH_SHIPPING_FEE_ROUTES */
\Illuminate\Support\Facades\Route::middleware(['auth', 'superadmin'])
    ->prefix('admin/super-admin/shipping-fees')
    ->name('admin.super-admin.shipping-fees.')
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/', [\App\Http\Controllers\Admin\BranchShippingFeeController::class, 'index'])->name('index');
        \Illuminate\Support\Facades\Route::get('/{branch}', [\App\Http\Controllers\Admin\BranchShippingFeeController::class, 'show'])->name('show');
        \Illuminate\Support\Facades\Route::put('/{branch}', [\App\Http\Controllers\Admin\BranchShippingFeeController::class, 'update'])->name('update');
        \Illuminate\Support\Facades\Route::post('/{branch}/preview', [\App\Http\Controllers\Admin\BranchShippingFeeController::class, 'preview'])->name('preview');
    });
/* /V27_BRANCH_SHIPPING_FEE_ROUTES */
