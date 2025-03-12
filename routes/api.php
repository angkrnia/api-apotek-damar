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
});
