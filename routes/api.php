<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Models\Branch;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);
Route::get('menu', [MenuController::class, 'index']);
Route::get('menu/{id}', [MenuController::class, 'show']);
Route::get('categories', [MenuController::class, 'categories']);
Route::get('branches', function () {
    return Branch::orderBy('name')->get();
});

// public member search may be protected if you want to keep private
Route::post('members', [MemberController::class, 'store']);
Route::get('members/search', [MemberController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders/{id}/pay', [OrderController::class, 'pay']);

    // payments admin endpoints
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);

    Route::apiResource('members', MemberController::class);
});

Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    Route::get('dashboard-summary', [DashboardController::class, 'summary']);

    Route::apiResource('branches', BranchController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('menu-items', MenuItemController::class);
    Route::apiResource('users', UserController::class);
});
