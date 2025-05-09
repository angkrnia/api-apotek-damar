<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// AUTH ROUTE
Route::post('login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::put('refresh-token', [App\Http\Controllers\Auth\AuthController::class, 'refreshToken']);

// KHUSUS YANG SUDAH LOGIN
Route::middleware(['auth:api'])->group(function () {
    // Change Password
    Route::post('change-password', [App\Http\Controllers\Auth\AuthController::class, 'changePassword']);
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
    // STOCK OPNAME HEADER
    Route::put('opname/{opname}/commit', [App\Http\Controllers\Opname\OpnameHeaderController::class, 'committed']);
    Route::apiResource('opname', App\Http\Controllers\Opname\OpnameHeaderController::class);
    // STOCK OPNAME DETAIL
    Route::apiResource('opname/{opname}/lines', App\Http\Controllers\Opname\OpnameDetailController::class);
    // SELECT LIST HELPER
    Route::get('categories-list', [App\Http\Controllers\HelperController::class, 'categoryList']);
    Route::get('groups-list', [App\Http\Controllers\HelperController::class, 'groupList']);
    Route::get('units-list', [App\Http\Controllers\HelperController::class, 'unitList']);
    Route::get('medicines-list', [App\Http\Controllers\HelperController::class, 'medicineList']);
    // KERANJANG
    Route::apiResource('carts', App\Http\Controllers\Sales\CartController::class);
    // CHECKOUT
    Route::post('checkout', [App\Http\Controllers\Sales\CheckoutController::class, 'checkout']);
    Route::post('checkout/{sale}/success', [App\Http\Controllers\Sales\CheckoutController::class, 'success']);
    // DETAIL RECEIPT NUMBER UNTUK STRUK
    Route::get('receipt-number/{trx}', [App\Http\Controllers\HelperController::class, 'receiptNumber']);
    // REMOVE ALL CART
    Route::delete('remove-carts', [App\Http\Controllers\Sales\CartController::class, 'removeCarts']);
    // Sales transaction history
    Route::get('sales-transaction', [App\Http\Controllers\Sales\SalesController::class, 'index']);
    // DASHBOARD GRAFIK
    Route::get('chart/summary-transaction', [App\Http\Controllers\ChartController::class, 'getTransactionSummary']);
    Route::get('chart/summary-sales', [App\Http\Controllers\ChartController::class, 'getSalesSummary']);
    Route::get('chart/summary-product', [App\Http\Controllers\ChartController::class, 'getProductSummary']);
    Route::get('chart/transaction-date-by-date', [App\Http\Controllers\ChartController::class, 'getTransactionDateByDate']);
    // CANCEL TRANSACTION
    Route::middleware(['admin'])->group(function () {
        Route::put('sales/{sale}/cancel', [App\Http\Controllers\Sales\SalesController::class, 'cancel']);
        Route::put('stock-entries/{stock}/cancel-stock', [App\Http\Controllers\StockIn\StockInHeaderController::class, 'cancel']);
    });
});
