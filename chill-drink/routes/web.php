<?php

use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Auth\GuestConvertController;
use App\Http\Controllers\Client\ChatController;
use App\Http\Controllers\Client\GuestCheckoutController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProductController as ClientProductController;
use App\Http\Controllers\Client\ProductReviewController;
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

// Products
Route::get('/products', [ClientProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ClientProductController::class, 'show'])->name('products.show');

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
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');
});

// Chat routes (client)
Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'getOrCreateConversation'])->name('index');
    Route::get('/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/send', [ChatController::class, 'send'])->name('send');
});

// Chat routes (admin/cskh)
Route::prefix('admin/chat')->name('admin.chat.')->middleware(['auth', 'cskh'])->group(function () {
    Route::get('/', [AdminChatController::class, 'index'])->name('index');
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

        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/orders', [ProfileController::class, 'orders'])->name('orders.index');
    Route::get('/notifications/feed', [ProfileController::class, 'notificationsFeed'])->name('notifications.feed');
    Route::redirect('/profile/orders', '/orders')->name('profile.orders');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------https://antigravity.google/support
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/super-admin', [SuperAdminController::class, 'index'])->name('super-admin');
    Route::post('/super-admin/admins', [SuperAdminController::class, 'storeAdmin'])->name('super-admin.admins.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // JSON endpoint for dashboard data (AJAX)
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('admin.dashboard.data');

    // Voucher Management
    Route::resource('vouchers', VoucherController::class)->except(['show']);

    // Product Management
    Route::resource('products', AdminProductController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Category Management
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Order Management
    Route::get('orders/recent', [OrderController::class, 'recent'])->name('orders.recent');
    Route::resource('orders', OrderController::class)->only(['index']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.updateStatus');

    // Review Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

    // User Management
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('users', UserController::class)->only(['index', 'show', 'edit', 'update']);
});

require __DIR__.'/auth.php';

