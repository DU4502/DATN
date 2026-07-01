<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CategoryApiController::class, 'index'])->name('api.categories.index');
Route::get('/products', [ProductApiController::class, 'index'])->name('api.products.index');
Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('api.products.show');

use App\Http\Controllers\Api\GuestCheckoutController;

Route::prefix('guest')->group(function () {
    Route::post('/checkout', [GuestCheckoutController::class, 'checkout'])->name('api.guest.checkout');
    Route::post('/convert-to-member', [GuestCheckoutController::class, 'convertToMember'])->name('api.guest.convert');
});
