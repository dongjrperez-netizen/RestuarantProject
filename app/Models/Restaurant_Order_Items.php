<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant_Order_Items extends Model
{
    protected $table = 'restaurant_order_items';

    protected $primaryKey = 'order_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'order_id',
        'ingredient_id',
        'item_type',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function order()
    {
        return $this->belongsTo(Restaurant_Order::class, 'order_id', 'order_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }
}
