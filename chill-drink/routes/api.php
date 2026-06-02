<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CategoryApiController::class, 'index'])->name('api.categories.index');
Route::get('/products', [ProductApiController::class, 'index'])->name('api.products.index');
Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('api.products.show');
