<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishVariant extends Model
{
    protected $table = 'dish_variants';

    protected $primaryKey = 'variant_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'dish_id',
        'size_name',
        'price_modifier',
        'quantity_multiplier',
        'is_default',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'quantity_multiplier' => 'decimal:2',
        'is_default' => 'boolean',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_available' => true,
        'sort_order' => 0,
        'quantity_multiplier' => 1.00,
    ];

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeByDish($query, $dishId)
    {
        return $query->where('dish_id', $dishId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
