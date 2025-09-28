<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredients extends Model
{
    protected $table = 'ingredients';

    protected $primaryKey = 'ingredient_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'ingredient_name',
        'base_unit',
        'current_stock',
        'packages',
        'reorder_level',
    ];

    public function orderItems()
    {
        return $this->hasMany(Restaurant_Order_Items::class, 'ingredient_id', 'ingredient_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'ingredient_suppliers', 'ingredient_id', 'supplier_id')
            ->withPivot(['package_unit', 'package_quantity', 'package_contents_quantity', 'package_contents_unit', 'package_price', 'lead_time_days', 'minimum_order_quantity', 'is_active'])
            ->withTimestamps();
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'ingredient_id', 'ingredient_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'dish_ingredients', 'ingredient_id', 'dish_id')
            ->withPivot(['quantity_needed', 'unit_of_measure'])
            ->withTimestamps();
    }

    public function dishIngredients()
    {
        return $this->hasMany(DishIngredient::class, 'ingredient_id', 'ingredient_id');
    }

    public function getPackageQuantityForSupplier($supplierId)
    {
        $pivot = $this->suppliers()->where('suppliers.supplier_id', $supplierId)->first();

        return $pivot ? $pivot->pivot->package_contents_quantity : null;
    }

    public function increaseStock($quantity)
    {
        $this->increment('current_stock', $quantity);
    }

    public function decreaseStock($quantity)
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception("Insufficient stock for ingredient: {$this->ingredient_name}. Current: {$this->current_stock}, Required: {$quantity}");
        }
        $this->decrement('current_stock', $quantity);
    }

    public function addPackages($packageCount, $contentsPerPackage)
    {
        $this->increment('packages', $packageCount);
        $totalUnits = $packageCount * $contentsPerPackage;
        $this->increment('current_stock', $totalUnits);
    }

    public function removePackages($packageCount, $contentsPerPackage)
    {
        if ($this->packages < $packageCount) {
            throw new \Exception("Insufficient packages for ingredient: {$this->ingredient_name}. Current: {$this->packages}, Required: {$packageCount}");
        }
        $this->decrement('packages', $packageCount);
        $totalUnits = $packageCount * $contentsPerPackage;
        $this->decrement('current_stock', $totalUnits);
    }
}
