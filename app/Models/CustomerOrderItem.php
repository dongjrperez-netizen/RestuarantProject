<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderItem extends Model
{
    protected $table = 'customer_order_items';

    protected $primaryKey = 'item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'order_id',
        'dish_id',
        'variant_id',
        'quantity',
        'served_quantity',
        'unit_price',
        'total_price',
        'special_instructions',
        'status',
        'inventory_deducted',
        'inventory_deducted_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'served_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'inventory_deducted' => 'boolean',
        'inventory_deducted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'quantity' => 1,
        'served_quantity' => 0,
        'inventory_deducted' => false,
    ];

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(DishVariant::class, 'variant_id', 'variant_id');
    }

    public function excludedIngredients()
    {
        return $this->hasMany(CustomerRequest::class, 'order_id', 'order_id')
            ->where('dish_id', $this->dish_id)
            ->where('request_type', 'exclude')
            ->with('ingredient');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
        });

        static::saved(function ($model) {
            // Update order totals when item is saved
            $model->customerOrder->calculateTotals();

            // Deduct ingredients from inventory when order item is created (not updated)
            if (!$model->inventory_deducted && $model->wasRecentlyCreated) {
                $model->deductIngredientsFromInventory();
            }
        });

        static::deleted(function ($model) {
            // Update order totals when item is deleted
            $model->customerOrder->calculateTotals();
        });
    }

    public function deductIngredientsFromInventory()
    {
        // Get the dish and its ingredients
        $dish = $this->dish;
        if (!$dish) {
            return;
        }

        $dishIngredients = $dish->dishIngredients;
        if ($dishIngredients->isEmpty()) {
            return;
        }

        // Get excluded ingredients for this order item
        $excludedIngredientIds = CustomerRequest::where('order_id', $this->order_id)
            ->where('dish_id', $this->dish_id)
            ->where('request_type', 'exclude')
            ->pluck('ingredient_id')
            ->toArray();

        // Deduct each ingredient based on the order quantity
        foreach ($dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            if (!$ingredient) {
                \Log::warning("Ingredient not found for dish ingredient ID {$dishIngredient->id}");
                continue;
            }

            // Skip this ingredient if customer requested exclusion
            if (in_array($ingredient->ingredient_id, $excludedIngredientIds)) {
                \Log::info("Ingredient excluded from inventory deduction", [
                    'order_item_id' => $this->item_id,
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'reason' => 'Customer requested exclusion',
                ]);
                continue;
            }

            // Calculate required quantity using unit conversion and variant multiplier
            // Apply variant multiplier to dish quantity BEFORE converting units
            $variantMultiplier = 1.0;
            if ($this->variant_id && $this->variant) {
                $variantMultiplier = (float) $this->variant->quantity_multiplier;
            }

            // Calculate quantity needed in the dish's unit first
            $quantityInDishUnit = $dishIngredient->quantity_needed * $variantMultiplier * $this->quantity;

            try {
                // Let decreaseStock handle the unit conversion from dish unit to ingredient base unit
                $ingredient->decreaseStock($quantityInDishUnit, $dishIngredient->unit_of_measure);

                \Log::info("Inventory deducted successfully", [
                    'order_item_id' => $this->item_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'dish_ingredient_quantity' => $dishIngredient->quantity_needed,
                    'dish_ingredient_unit' => $dishIngredient->unit_of_measure,
                    'variant_multiplier' => $variantMultiplier,
                    'quantity_in_dish_unit' => $quantityInDishUnit,
                    'ingredient_base_unit' => $ingredient->base_unit,
                    'order_quantity' => $this->quantity,
                ]);
            } catch (\Exception $e) {
                // Log the error but don't fail the order
                \Log::warning("Failed to deduct ingredient {$ingredient->ingredient_name} for order item {$this->item_id}: " . $e->getMessage(), [
                    'dish_ingredient_quantity' => $dishIngredient->quantity_needed,
                    'dish_ingredient_unit' => $dishIngredient->unit_of_measure,
                    'quantity_in_dish_unit' => $quantityInDishUnit,
                    'ingredient_base_unit' => $ingredient->base_unit,
                    'current_stock' => $ingredient->current_stock,
                    'order_quantity' => $this->quantity,
                ]);
            }
        }

        // Mark as inventory deducted
        $this->update([
            'inventory_deducted' => true,
            'inventory_deducted_at' => now(),
        ]);
    }

    /**
     * Deduct inventory for a specific quantity (used when adding more items to existing order item)
     */
    public function deductIngredientsForQuantity(int $additionalQuantity)
    {
        // Get the dish and its ingredients
        $dish = $this->dish;
        if (!$dish) {
            return;
        }

        $dishIngredients = $dish->dishIngredients;
        if ($dishIngredients->isEmpty()) {
            return;
        }

        // Get excluded ingredients for this order item
        $excludedIngredientIds = CustomerRequest::where('order_id', $this->order_id)
            ->where('dish_id', $this->dish_id)
            ->where('request_type', 'exclude')
            ->pluck('ingredient_id')
            ->toArray();

        // Deduct each ingredient based on the additional quantity
        foreach ($dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            if (!$ingredient) {
                \Log::warning("Ingredient not found for dish ingredient ID {$dishIngredient->id}");
                continue;
            }

            // Skip this ingredient if customer requested exclusion
            if (in_array($ingredient->ingredient_id, $excludedIngredientIds)) {
                \Log::info("Ingredient excluded from inventory deduction", [
                    'order_item_id' => $this->item_id,
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'reason' => 'Customer requested exclusion',
                ]);
                continue;
            }

            // Calculate required quantity using unit conversion and variant multiplier
            // Apply variant multiplier to dish quantity BEFORE converting units
            $variantMultiplier = 1.0;
            if ($this->variant_id && $this->variant) {
                $variantMultiplier = (float) $this->variant->quantity_multiplier;
            }

            // Calculate quantity needed in the dish's unit first (for the additional quantity only)
            $quantityInDishUnit = $dishIngredient->quantity_needed * $variantMultiplier * $additionalQuantity;

            try {
                // Let decreaseStock handle the unit conversion from dish unit to ingredient base unit
                $ingredient->decreaseStock($quantityInDishUnit, $dishIngredient->unit_of_measure);

                \Log::info("Inventory deducted for additional quantity", [
                    'order_item_id' => $this->item_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'dish_ingredient_quantity' => $dishIngredient->quantity_needed,
                    'dish_ingredient_unit' => $dishIngredient->unit_of_measure,
                    'variant_multiplier' => $variantMultiplier,
                    'additional_quantity' => $additionalQuantity,
                    'quantity_in_dish_unit' => $quantityInDishUnit,
                    'ingredient_base_unit' => $ingredient->base_unit,
                ]);
            } catch (\Exception $e) {
                // Log the error but don't fail the order
                \Log::warning("Failed to deduct ingredient {$ingredient->ingredient_name} for additional quantity: " . $e->getMessage(), [
                    'dish_ingredient_quantity' => $dishIngredient->quantity_needed,
                    'dish_ingredient_unit' => $dishIngredient->unit_of_measure,
                    'additional_quantity' => $additionalQuantity,
                    'quantity_in_dish_unit' => $quantityInDishUnit,
                    'ingredient_base_unit' => $ingredient->base_unit,
                    'current_stock' => $ingredient->current_stock,
                ]);
            }
        }
    }
}