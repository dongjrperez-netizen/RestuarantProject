<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant_Order extends Model
{
    protected $table = 'restaurant_orders';

    protected $primaryKey = 'order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'supplier_id',
        'restaurant_id',
        'reference_no',
    ];

    public function items()
    {
        return $this->hasMany(Restaurant_Order_Items::class, 'order_id', 'order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    protected $casts = [
        'order_date' => 'datetime',  // 👈 this ensures you can call ->format()
    ];
}
