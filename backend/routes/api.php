<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

// Public endpoints for the website
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/menu-items/lookup', [MenuItemController::class, 'lookup']);
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:15,1');

Route::middleware(['web', 'auth', 'active', 'role:admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::post('/menu-items/{menuItem}/toggle-sold-out', [MenuItemController::class, 'toggleSoldOut']);
    Route::post('/menu-items/{menuItem}/regenerate-barcode', [MenuItemController::class, 'regenerateBarcode']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

    Route::get('/orders/summary', [OrderController::class, 'summary']);
    Route::get('/orders/export', [OrderController::class, 'export']);
    Route::post('/orders/purge', [OrderController::class, 'purge']);

    Route::get('/users', [UserAdminController::class, 'index']);
    Route::put('/users/{user}', [UserAdminController::class, 'update']);
    Route::delete('/users/{user}', [UserAdminController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'active', 'role:admin|pos|kitchen|staff'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/send-to-kitchen', [OrderController::class, 'sendToKitchen']);
    Route::post('/orders/{order}/kitchen-status', [OrderController::class, 'updateKitchenStatus']);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);
});
