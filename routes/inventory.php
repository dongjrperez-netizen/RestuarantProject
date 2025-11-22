<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'verified'])->prefix('inventory')->group(function () {

    Route::get('/', [InventoryController::class, 'index'])
        ->name('inventory.index');

    Route::get('/ingredients', [InventoryController::class, 'index'])
        ->name('inventory.ingredients.index');

    Route::get('/ingredients/create', [InventoryController::class, 'create'])
        ->name('inventory.ingredients.create');

    Route::post('/ingredients', [InventoryController::class, 'store'])
        ->name('inventory.ingredients.store');

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
