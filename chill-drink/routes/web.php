<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\GroupOrderController as AdminGroupOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\ToppingController;
use App\Http\Controllers\Admin\BranchSlideController;
use App\Http\Controllers\Auth\GuestConvertController;
use App\Http\Controllers\Client\OrderLookupController;
use App\Http\Controllers\Client\ChatController;
use App\Http\Controllers\Client\GuestCheckoutController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\GroupOrderController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProductController as ClientProductController;
use App\Http\Controllers\Client\ProductReviewController;
use App\Http\Controllers\Client\QuickOrderController;
use App\Http\Controllers\Client\VnpayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Broadcast;
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
});

Route::post('/register/guest-convert', [GuestConvertController::class, 'store'])
    ->middleware('guest')
    ->name('register.guest-convert');

Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/vnpay/payment/{order}', [VnpayController::class, 'payment'])->name('vnpay.payment');

Broadcast::routes(['middleware' => ['web', 'auth']]);

// Checkout (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::post('/checkout/addresses', [CheckoutController::class, 'storeAddress'])->name('checkout.addresses.store');
    Route::put('/checkout/addresses/{address}', [CheckoutController::class, 'updateAddress'])->name('checkout.addresses.update');
    Route::patch('/checkout/address/primary', [CheckoutController::class, 'updatePrimaryAddress'])->name('checkout.addresses.primary.update');
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');

    Route::get('/group-orders/join/{code}', [GroupOrderController::class, 'show'])->name('group-orders.show');
    Route::post('/group-orders/join/{code}/presence', [GroupOrderController::class, 'presence'])->name('group-orders.presence');
    Route::post('/group-orders/join/{code}/leave', [GroupOrderController::class, 'leave'])->name('group-orders.leave');
    Route::post('/group-orders/join/{code}/leave-room', [GroupOrderController::class, 'leaveRoom'])->name('group-orders.leave-room');
    Route::get('/group-orders/join/{code}/messages', [GroupOrderController::class, 'messages'])->name('group-orders.messages');
    Route::post('/group-orders/join/{code}/messages', [GroupOrderController::class, 'sendMessage'])->name('group-orders.messages.send');
    Route::post('/group-orders/join/{code}/messages/read', [GroupOrderController::class, 'readMessages'])->name('group-orders.messages.read');
    Route::post('/group-orders/join/{code}', [GroupOrderController::class, 'join'])->name('group-orders.join');
    Route::post('/group-orders/join/{code}/items', [GroupOrderController::class, 'addItem'])->name('group-orders.items.store');
    Route::patch('/group-orders/join/{code}/items/{item}/increment', [GroupOrderController::class, 'incrementItem'])->name('group-orders.items.increment');
    Route::delete('/group-orders/join/{code}/items/{item}', [GroupOrderController::class, 'removeItem'])->name('group-orders.items.destroy');
    Route::get('/group-orders', [GroupOrderController::class, 'index'])->name('group-orders.index');
    Route::get('/group-orders/create', [GroupOrderController::class, 'create'])->name('group-orders.create');
    Route::post('/group-orders', [GroupOrderController::class, 'store'])->name('group-orders.store');
    Route::post('/group-orders/{code}/close', [GroupOrderController::class, 'close'])->name('group-orders.close');
    Route::post('/group-orders/{code}/cancel', [GroupOrderController::class, 'cancel'])->name('group-orders.cancel');
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
    Route::post('/send', [ChatController::class, 'send'])->name('send');
    Route::post('/end-session', [ChatController::class, 'endSession'])->name('end-session');
});

// Chat routes (admin/cskh)
Route::prefix('admin/chat')->name('admin.chat.')->middleware(['auth', 'cskh'])->group(function () {
    Route::get('/', [AdminChatController::class, 'index'])->name('index');
    Route::get('/conversations', [AdminChatController::class, 'conversationList'])->name('conversations');
    Route::get('/unread-count', [AdminChatController::class, 'unreadCount'])->name('unread-count');
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

        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/orders', [ProfileController::class, 'orders'])->name('orders.index');
    Route::post('/orders/{order}/cancel', [ProfileController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{order}/confirm-received', [ProfileController::class, 'confirmReceived'])->name('orders.confirm-received');
    Route::get('/notifications/feed', [ProfileController::class, 'notificationsFeed'])->name('notifications.feed');
    Route::post('/notifications/mark-all-read', [ProfileController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
    Route::redirect('/profile/orders', '/orders')->name('profile.orders');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Loyalty Points
    Route::get('/loyalty-points', [\App\Http\Controllers\Client\LoyaltyPointController::class, 'index'])->name('loyalty.index');
    Route::post('/loyalty-points/redeem/{voucher}', [\App\Http\Controllers\Client\LoyaltyPointController::class, 'redeemVoucher'])->name('loyalty.redeem-voucher');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------https://antigravity.google/support
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/super-admin', [SuperAdminController::class, 'index'])->name('super-admin');
    Route::post('/super-admin/admins', [SuperAdminController::class, 'storeAdmin'])->name('super-admin.admins.store');
    Route::patch('/super-admin/admins/{user}/branch', [SuperAdminController::class, 'updateBranch'])->name('super-admin.update-branch');
    Route::patch('/super-admin/admins/{user}/role', [SuperAdminController::class, 'updateRole'])->name('super-admin.update-role');
    Route::get('/preview/admin', [SuperAdminController::class, 'enterAdminWorkspace'])->name('preview-admin');
    Route::get('/preview/admin/exit', [SuperAdminController::class, 'exitAdminWorkspace'])->name('preview-admin.exit');

    
    // Branch Management
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    Route::patch('/branches/{branch}/status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'exportTimeComparison'])->name('dashboard.export');

    // JSON endpoint for dashboard data (AJAX)
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('admin.dashboard.data');

    // Voucher Management
    Route::resource('vouchers', VoucherController::class)->except(['show']);

    // Topping Management
    Route::resource('toppings', ToppingController::class)->except(['show']);

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
    Route::resource('orders', OrderController::class)->only(['index']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.updateStatus');
    Route::resource('group-orders', AdminGroupOrderController::class)->only(['index', 'show']);

    // Review Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

    // User Management
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
});

require __DIR__.'/auth.php';
