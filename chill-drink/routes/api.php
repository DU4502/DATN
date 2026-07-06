<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\NearestBranchController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CategoryApiController::class, 'index'])->name('api.categories.index');
Route::get('/products', [ProductApiController::class, 'index'])->name('api.products.index');
Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('api.products.show');
Route::get('/branches/nearest', [NearestBranchController::class, 'nearest'])->name('api.branches.nearest');
Route::get('/branches', [NearestBranchController::class, 'list'])->name('api.branches.list');

// Voucher routes
Route::post('/vouchers/receive', [VoucherController::class, 'receive'])->name('api.vouchers.receive');
Route::get('/vouchers/received', [VoucherController::class, 'getReceived'])->name('api.vouchers.received');
Route::post('/vouchers/{id}/mark-as-used', [VoucherController::class, 'markAsUsed'])->name('api.vouchers.mark-used');

use App\Http\Controllers\Api\GuestCheckoutController;

Route::prefix('guest')->group(function () {
    Route::post('/checkout', [GuestCheckoutController::class, 'checkout'])->name('api.guest.checkout');
    Route::post('/convert-to-member', [GuestCheckoutController::class, 'convertToMember'])->name('api.guest.convert');
});
