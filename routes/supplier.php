<?php

use App\Http\Controllers\Supplier\AuthController;
use App\Http\Controllers\Supplier\DashboardController;
use App\Http\Controllers\Supplier\IngredientOfferController;
use App\Http\Controllers\Supplier\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supplier Routes
|--------------------------------------------------------------------------
|
| Here is where you can register supplier routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "supplier" middleware group. Now create something great!
|
*/

// Guest Supplier Routes
Route::middleware('guest:supplier')->group(function () {
    // Registration routes only - no login routes needed (uses unified login)
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('supplier.register');
    Route::post('/register', [AuthController::class, 'register'])->name('supplier.register.post');
});

// Authenticated Supplier Routes
Route::middleware(['auth:supplier', 'supplier.auth'])->group(function () {
    // Supplier logout - handled by unified logout
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('supplier.logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('supplier.dashboard');

    // Ingredient Offers Management
    Route::prefix('ingredients')->name('supplier.ingredients.')->group(function () {
        Route::get('/', [IngredientOfferController::class, 'index'])->name('index');
        Route::get('/create', [IngredientOfferController::class, 'create'])->name('create');
        Route::post('/', [IngredientOfferController::class, 'store'])->name('store');
        Route::get('/{ingredient}/edit', [IngredientOfferController::class, 'edit'])->name('edit');
        Route::put('/{ingredient}', [IngredientOfferController::class, 'update'])->name('update');
        Route::delete('/{ingredient}', [IngredientOfferController::class, 'destroy'])->name('destroy');
    });

    // Purchase Orders Management
    Route::prefix('purchase-orders')->name('supplier.purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::post('/{id}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject'])->name('reject');
        Route::post('/{id}/mark-delivered', [PurchaseOrderController::class, 'markDelivered'])->name('mark-delivered');
    });
});
