<?php

namespace App\Models;

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
}
