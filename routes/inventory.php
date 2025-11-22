<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\IngredientsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'verified'])->prefix('inventory')->group(function () {

    Route::get('/', [InventoryController::class, 'index'])
        ->name('inventory.index');

    Route::put('/ingredients/{ingredientId}', [InventoryController::class, 'updateIngredient'])
        ->name('inventory.ingredients.update')
        ->where('ingredientId', '[0-9]+');

    Route::post('/purchase-order/receipt', [InventoryController::class, 'processPurchaseOrderReceipt'])
        ->name('inventory.purchase-order.receipt');

    Route::post('/dish/sale', [InventoryController::class, 'processDishSale'])
        ->name('inventory.dish.sale');

    Route::post('/dish/batch-sale', [InventoryController::class, 'processBatchDishSales'])
        ->name('inventory.dish.batch-sale');

    Route::get('/dish/stock-check', [InventoryController::class, 'checkStockAvailability'])
        ->name('inventory.dish.stock-check');

    Route::get('/ingredients/low-stock', [InventoryController::class, 'getLowStockIngredients'])
        ->name('inventory.ingredients.low-stock');

    Route::get('/dish/ingredient-cost', [InventoryController::class, 'calculateIngredientCost'])
        ->name('inventory.dish.ingredient-cost');
});

// Ingredients Library Management
Route::middleware(['auth:web', 'verified', 'check.subscription'])->prefix('ingredients-library')->group(function () {
    Route::get('/', [IngredientsController::class, 'index'])
        ->name('ingredients.index');

    Route::get('/create', [IngredientsController::class, 'create'])
        ->name('ingredients.create');

    Route::post('/', [IngredientsController::class, 'store'])
        ->name('ingredients.store');

    Route::get('/{ingredient}/edit', [IngredientsController::class, 'edit'])
        ->name('ingredients.edit');

    Route::put('/{ingredient}', [IngredientsController::class, 'update'])
        ->name('ingredients.update');

    Route::delete('/{ingredient}', [IngredientsController::class, 'destroy'])
        ->name('ingredients.destroy');
});
