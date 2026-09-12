<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BusinessHoursController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StoreInventoryController;
use App\Http\Controllers\Api\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

// Public endpoints for the website
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/menu-items/lookup', [MenuItemController::class, 'lookup']);
Route::get('/order-availability', [BusinessHoursController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);

Route::middleware(['web', 'auth', 'active', 'role:admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
    Route::post('/menu-items/{menuItem}/regenerate-barcode', [MenuItemController::class, 'regenerateBarcode']);
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

    Route::get('/orders/summary', [OrderController::class, 'summary']);
    Route::get('/orders/export', [OrderController::class, 'export']);
    Route::post('/orders/purge', [OrderController::class, 'purge']);

    Route::get('/users', [UserAdminController::class, 'index']);
    Route::put('/users/{user}', [UserAdminController::class, 'update']);
    Route::delete('/users/{user}', [UserAdminController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'active', 'role:admin|manager'])->group(function () {
    Route::post('/menu-items/{menuItem}/toggle-sold-out', [MenuItemController::class, 'toggleSoldOut']);
    Route::put('/menu-items/{menuItem}/stock', [MenuItemController::class, 'updateStock']);
    Route::get('/inventory-adjustments', [MenuItemController::class, 'adjustments']);
    Route::put('/order-availability', [BusinessHoursController::class, 'update']);
    Route::get('/manager/orders', [OrderController::class, 'managerOrders']);
    Route::get('/manager/summary', [OrderController::class, 'managerSummary']);

    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'active', 'role:admin|manager|pos|kitchen|staff'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/send-to-kitchen', [OrderController::class, 'sendToKitchen']);
    Route::post('/orders/{order}/kitchen-status', [OrderController::class, 'updateKitchenStatus']);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});

Route::middleware(['web', 'auth', 'active', 'role:admin|staff'])->group(function () {
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'active', 'role:admin|inventory'])->group(function () {
    // Store categories
    Route::get('/store-categories', [StoreInventoryController::class, 'categories']);
    Route::post('/store-categories', [StoreInventoryController::class, 'storeCategory']);
    Route::put('/store-categories/{storeCategory}', [StoreInventoryController::class, 'updateCategory']);
    Route::delete('/store-categories/{storeCategory}', [StoreInventoryController::class, 'destroyCategory']);

    // Store items
    Route::get('/store-items', [StoreInventoryController::class, 'index']);
    Route::post('/store-items', [StoreInventoryController::class, 'store']);
    Route::put('/store-items/{storeItem}', [StoreInventoryController::class, 'update']);
    Route::delete('/store-items/{storeItem}', [StoreInventoryController::class, 'destroy']);
    Route::post('/store-items/{storeItem}/adjust', [StoreInventoryController::class, 'adjust']);
    Route::get('/store-items/{storeItem}/history', [StoreInventoryController::class, 'history']);
});
