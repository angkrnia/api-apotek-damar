<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// AUTH ROUTE
Route::post('login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::put('refresh-token', [App\Http\Controllers\Auth\AuthController::class, 'refreshToken']);

// KHUSUS YANG SUDAH LOGIN
Route::middleware(['auth:api'])->group(function () {
    // Upload photos
    Route::post('upload/photos', [App\Http\Controllers\UploadPhotosController::class, 'store']);
    // Units
    Route::apiResource('units', App\Http\Controllers\UnitController::class);
    // Groups
    Route::apiResource('groups', App\Http\Controllers\GroupController::class);
    // Kategori
    Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
    // Products
    Route::apiResource('products', App\Http\Controllers\ProductController::class);
    // Stock Movement
    Route::get('stock-movements', [App\Http\Controllers\StockMovementController::class, 'index']);
    Route::get('stock-movements/{product}', [App\Http\Controllers\StockMovementController::class, 'show']);
    // STOK MASUK HEADER
    Route::put('stock-entries/{stock}/commit', [App\Http\Controllers\StockIn\StockInHeaderController::class, 'committed']);
    Route::apiResource('stock-entries', App\Http\Controllers\StockIn\StockInHeaderController::class);
    // STOK MASUK DETAIL
    Route::apiResource('stock-entries/{stock}/lines', App\Http\Controllers\StockIn\StockInDetailController::class);
    // SELECT LIST HELPER
    Route::get('categories-list', [App\Http\Controllers\HelperController::class, 'categoryList']);
	Route::get('groups-list', [App\Http\Controllers\HelperController::class, 'groupList']);
	Route::get('units-list', [App\Http\Controllers\HelperController::class, 'unitList']);
});
