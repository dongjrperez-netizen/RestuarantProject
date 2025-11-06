<?php

namespace App\Models;

use App\Events\InventoryUpdated;
use App\Utils\UnitConverter;
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
        'cost_per_unit',
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
        $previousStock = $this->current_stock;
        $this->increment('current_stock', $quantity);
        $this->refresh();

        // Broadcast inventory update
        broadcast(new InventoryUpdated($this, 'increased', $previousStock))->toOthers();
    }

    public function decreaseStock($quantity, $unit = null)
    {
        $originalQuantity = $quantity;
        $originalUnit = $unit;
        $previousStock = $this->current_stock;

        // Convert quantity to base unit if different unit is provided
        if ($unit && $unit !== $this->base_unit) {
            try {
                $quantity = UnitConverter::convert($quantity, $unit, $this->base_unit);

                \Log::info("Unit conversion in decreaseStock", [
                    'ingredient_name' => $this->ingredient_name,
                    'original_quantity' => $originalQuantity,
                    'original_unit' => $originalUnit,
                    'converted_quantity' => $quantity,
                    'base_unit' => $this->base_unit,
                    'current_stock_before' => $this->current_stock,
                ]);
            } catch (\InvalidArgumentException $e) {
                throw new \Exception("Cannot convert {$unit} to {$this->base_unit} for ingredient: {$this->ingredient_name}");
            }
        }

        if ($this->current_stock < $quantity) {
            throw new \Exception("Insufficient stock for ingredient: {$this->ingredient_name}. Current: {$this->current_stock} {$this->base_unit}, Required: {$quantity} {$this->base_unit}");
        }

        $this->decrement('current_stock', $quantity);
        $this->refresh();

        \Log::info("Stock decremented", [
            'ingredient_name' => $this->ingredient_name,
            'decremented_by' => $quantity,
            'current_stock_after' => $this->current_stock,
        ]);

        // Broadcast inventory update
        broadcast(new InventoryUpdated($this, 'decreased', $previousStock))->toOthers();
    }

    public function addPackages($packageCount, $contentsPerPackage)
    {
        $previousStock = $this->current_stock;
        $this->increment('packages', $packageCount);
        $totalUnits = $packageCount * $contentsPerPackage;
        $this->increment('current_stock', $totalUnits);
        $this->refresh();

        // Broadcast inventory update
        broadcast(new InventoryUpdated($this, 'increased', $previousStock))->toOthers();
    }

    public function removePackages($packageCount, $contentsPerPackage)
    {
        if ($this->packages < $packageCount) {
            throw new \Exception("Insufficient packages for ingredient: {$this->ingredient_name}. Current: {$this->packages}, Required: {$packageCount}");
        }
        $previousStock = $this->current_stock;
        $this->decrement('packages', $packageCount);
        $totalUnits = $packageCount * $contentsPerPackage;
        $this->decrement('current_stock', $totalUnits);
        $this->refresh();

        // Broadcast inventory update
        broadcast(new InventoryUpdated($this, 'decreased', $previousStock))->toOthers();
    }

    /**
     * Get stock quantity in a specific unit
     */
    public function getStockInUnit(string $targetUnit): float
    {
        try {
            return UnitConverter::convert($this->current_stock, $this->base_unit, $targetUnit);
        } catch (\InvalidArgumentException $e) {
            return $this->current_stock;
        }
    }

    /**
     * Check if sufficient stock exists for a given quantity and unit
     */
    public function hasSufficientStock(float $requiredQuantity, string $unit): bool
    {
        try {
            $requiredInBaseUnit = UnitConverter::convert($requiredQuantity, $unit, $this->base_unit);
            return $this->current_stock >= $requiredInBaseUnit;
        } catch (\InvalidArgumentException $e) {
            // If units can't be converted, compare directly
            return $this->current_stock >= $requiredQuantity;
        }
    }

    /**
     * Get compatible units for this ingredient
     */
    public function getCompatibleUnits(): array
    {
        $unitType = UnitConverter::getUnitType($this->base_unit);
        return UnitConverter::getSuggestedUnits($unitType ?: 'weight');
    }
}
