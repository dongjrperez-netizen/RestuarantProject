<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRequest extends Model
{
    protected $table = 'customer_requests';
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'order_id',
        'dish_id',
        'ingredient_id',
        'restaurant_id',
        'request_type',
        'notes',
    ];

    protected $casts = [
        'request_type' => 'string',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id', 'order_id');
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id', 'id');
    }
}
