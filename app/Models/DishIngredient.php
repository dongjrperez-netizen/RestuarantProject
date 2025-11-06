<?php

namespace App\Models;

use App\Utils\UnitConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishIngredient extends Model
{
    protected $table = 'dish_ingredients';

    protected $fillable = [
        'dish_id',
        'ingredient_id',
        'quantity_needed',
        'unit_of_measure',
        'is_optional',
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:4',
        'is_optional' => 'boolean',
    ];

    protected $attributes = [
        'is_optional' => false,
    ];

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }

    public function getTotalCostAttribute()
    {
        $costPerUnit = $this->cost_per_unit ?? $this->ingredient->cost_per_unit ?? 0;
        return $this->quantity_needed * $costPerUnit;
    }

    public function getIngredientNameAttribute()
    {
        return $this->ingredient->ingredient_name ?? '';
    }

    public function getUnitAttribute()
    {
        return $this->unit_of_measure ?? $this->ingredient->base_unit ?? '';
    }

    /**
     * Get quantity needed converted to the ingredient's base unit for inventory calculation
     */
    public function getQuantityInBaseUnit(): float
    {
        if (!$this->ingredient) {
            return $this->quantity_needed;
        }

        $dishUnit = $this->unit_of_measure ?? 'g';
        $baseUnit = $this->ingredient->base_unit ?? 'g';

        try {
            return UnitConverter::convert($this->quantity_needed, $dishUnit, $baseUnit);
        } catch (\InvalidArgumentException $e) {
            // If units are incompatible, return original quantity
            return $this->quantity_needed;
        }
    }

    /**
     * Get the quantity needed in a specific unit
     */
    public function getQuantityInUnit(string $targetUnit): float
    {
        $currentUnit = $this->unit_of_measure ?? 'g';

        try {
            return UnitConverter::convert($this->quantity_needed, $currentUnit, $targetUnit);
        } catch (\InvalidArgumentException $e) {
            return $this->quantity_needed;
        }
    }

    /**
     * Check if this ingredient has sufficient stock considering unit conversion
     */
    public function hasSufficientStock(int $dishQuantity = 1): bool
    {
        if (!$this->ingredient || $this->is_optional) {
            return true;
        }

        $requiredQuantity = $this->getQuantityInBaseUnit() * $dishQuantity;
        return $this->ingredient->current_stock >= $requiredQuantity;
    }

    /**
     * Calculate the cost for this ingredient considering unit conversion
     */
    public function calculateCost(): float
    {
        if (!$this->ingredient) {
            return 0;
        }

        // Convert quantity to ingredient's base unit for cost calculation
        $quantityInBaseUnit = $this->getQuantityInBaseUnit();
        $costPerUnit = $this->ingredient->cost_per_unit ?? 0;

        return $quantityInBaseUnit * $costPerUnit;
    }
}
