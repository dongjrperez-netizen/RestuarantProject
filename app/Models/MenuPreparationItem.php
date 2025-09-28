<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuPreparationItem extends Model
{
    protected $table = 'menu_preparation_items';

    protected $primaryKey = 'preparation_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'preparation_order_id',
        'dish_id',
        'planned_quantity',
        'prepared_quantity',
        'status',
        'notes',
        'inventory_deducted',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'prepared_quantity' => 'integer',
        'inventory_deducted' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function preparationOrder(): BelongsTo
    {
        return $this->belongsTo(MenuPreparationOrder::class, 'preparation_order_id', 'preparation_order_id');
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function checkIngredientAvailability(): array
    {
        $shortages = [];
        $dish = $this->dish;

        foreach ($dish->dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            $totalNeeded = $dishIngredient->quantity_needed * $this->planned_quantity;
            $available = $ingredient->current_stock;

            if ($available < $totalNeeded) {
                $shortages[] = [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'needed' => $totalNeeded,
                    'available' => $available,
                    'shortage' => $totalNeeded - $available,
                    'unit' => $dishIngredient->unit_of_measure,
                ];
            }
        }

        return [
            'has_shortages' => ! empty($shortages),
            'shortages' => $shortages,
        ];
    }

    public function deductInventory(): bool
    {
        if ($this->inventory_deducted) {
            return false;
        }

        $dish = $this->dish;
        $deductions = [];

        try {
            foreach ($dish->dishIngredients as $dishIngredient) {
                $ingredient = $dishIngredient->ingredient;
                $totalNeeded = $dishIngredient->quantity_needed * $this->prepared_quantity;

                if ($ingredient->current_stock < $totalNeeded) {
                    throw new Exception("Insufficient stock for ingredient: {$ingredient->ingredient_name}. Available: {$ingredient->current_stock}, Required: {$totalNeeded}");
                }

                $deductions[] = [
                    'ingredient' => $ingredient,
                    'quantity' => $totalNeeded,
                ];
            }

            foreach ($deductions as $deduction) {
                $deduction['ingredient']->decreaseStock($deduction['quantity']);
            }

            $this->update(['inventory_deducted' => true]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function startPreparation(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    public function completePreparation($preparedQuantity = null): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $finalQuantity = $preparedQuantity ?? $this->planned_quantity;

        $this->update([
            'status' => 'completed',
            'prepared_quantity' => $finalQuantity,
            'completed_at' => now(),
        ]);

        if (! $this->inventory_deducted) {
            $this->deductInventory();
        }

        return true;
    }

    public function cancelPreparation(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return true;
    }

    public function getProgressPercentage(): float
    {
        if ($this->planned_quantity == 0) {
            return 0;
        }

        return ($this->prepared_quantity / $this->planned_quantity) * 100;
    }

    public function getRemainingQuantity(): int
    {
        return max(0, $this->planned_quantity - $this->prepared_quantity);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDish($query, $dishId)
    {
        return $query->where('dish_id', $dishId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
