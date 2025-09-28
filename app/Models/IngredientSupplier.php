<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientSupplier extends Model
{
    protected $table = 'ingredient_suppliers';

    protected $fillable = [
        'ingredient_id',
        'supplier_id',
        'package_unit',
        'package_quantity',
        'package_price',
        'lead_time_days',
        'minimum_order_quantity',
        'is_active',
    ];

    protected $casts = [
        'package_quantity' => 'decimal:2',
        'package_price' => 'decimal:2',
        'lead_time_days' => 'decimal:2',
        'minimum_order_quantity' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}
