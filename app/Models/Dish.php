<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dish extends Model
{
    protected $table = 'dishes';

    protected $primaryKey = 'dish_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'dish_name',
        'description',
        'category_id',
        'image_url',
        'preparation_time',
        'serving_size',
        'serving_unit',
        'calories',
        'allergens',
        'dietary_tags',
        'status',
        'price',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'preparation_time' => 'integer',
        'serving_size' => 'decimal:2',
        'calories' => 'integer',
        'allergens' => 'array',
        'dietary_tags' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_available' => true,
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id', 'category_id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredients::class, 'dish_ingredients', 'dish_id', 'ingredient_id')
            ->withPivot(['quantity_needed', 'unit_of_measure', 'is_optional'])
            ->withTimestamps();
    }

    public function dishIngredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class, 'dish_id', 'dish_id');
    }



    public function calculateIngredientQuantities($dishQuantity = 1)
    {
        return $this->dishIngredients->mapWithKeys(function ($dishIngredient) use ($dishQuantity) {
            // Use the unit-converted quantity for accurate inventory calculation
            $quantityInBaseUnit = $dishIngredient->getQuantityInBaseUnit();
            return [
                $dishIngredient->ingredient_id => $quantityInBaseUnit * $dishQuantity,
            ];
        });
    }

    public function hasAvailableStock($dishQuantity = 1)
    {
        // If dish has no ingredients, consider it available
        if ($this->dishIngredients->isEmpty()) {
            return true;
        }

        foreach ($this->dishIngredients as $dishIngredient) {
            // Use the unit-converted method for accurate stock checking
            if (!$dishIngredient->hasSufficientStock($dishQuantity)) {
                return false;
            }
        }

        return true;
    }

    public function calculateIngredientCost()
    {
        return $this->dishIngredients->sum(function ($dishIngredient) {
            // Use the unit-converted cost calculation method
            return $dishIngredient->calculateCost();
        });
    }

    public function getDineInPriceAttribute()
    {
        return $this->price ?? 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('status', 'active');
    }
}
