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

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
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

        // Deduct each ingredient based on the order quantity
        foreach ($dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            if (!$ingredient) {
                \Log::warning("Ingredient not found for dish ingredient ID {$dishIngredient->id}");
                continue;
            }

            $requiredQuantity = $dishIngredient->quantity_needed * $this->quantity;

            try {
                // Use the existing decreaseStock method from Ingredients model
                $ingredient->decreaseStock($requiredQuantity);
            } catch (\Exception $e) {
                // Log the error but don't fail the order
                \Log::warning("Failed to deduct ingredient {$ingredient->ingredient_name} for order item {$this->item_id}: " . $e->getMessage());
            }
        }

        // Mark as inventory deducted
        $this->update([
            'inventory_deducted' => true,
            'inventory_deducted_at' => now(),
        ]);
    }
}