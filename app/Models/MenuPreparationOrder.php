<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class MenuPreparationOrder extends Model
{
    use Notifiable;

    protected $table = 'menu_preparation_orders';

    protected $primaryKey = 'preparation_order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'menu_plan_id',
        'order_reference',
        'preparation_type',
        'preparation_date',
        'meal_type',
        'status',
        'notes',
        'created_by',
        'prepared_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'preparation_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function menuPlan(): BelongsTo
    {
        return $this->belongsTo(MenuPlan::class, 'menu_plan_id', 'menu_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'prepared_by', 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuPreparationItem::class, 'preparation_order_id', 'preparation_order_id');
    }

    public function generateOrderReference(): string
    {
        $date = $this->preparation_date->format('Ymd');
        $type = strtoupper(substr($this->preparation_type, 0, 1));
        $meal = $this->meal_type ? strtoupper(substr($this->meal_type, 0, 1)) : 'G';
        $sequence = static::where('preparation_date', $this->preparation_date)
            ->where('restaurant_id', $this->restaurant_id)
            ->count() + 1;

        return "PREP-{$date}-{$type}{$meal}-".str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    public function checkInventoryAvailability(): array
    {
        $shortages = [];
        $warnings = [];

        foreach ($this->items as $item) {
            $dish = $item->dish;
            $requiredQuantity = $item->planned_quantity;

            foreach ($dish->dishIngredients as $dishIngredient) {
                $ingredient = $dishIngredient->ingredient;
                $totalNeeded = $dishIngredient->quantity_needed * $requiredQuantity;
                $available = $ingredient->current_stock;

                if ($available < $totalNeeded) {
                    $shortages[] = [
                        'ingredient' => $ingredient->ingredient_name,
                        'dish' => $dish->dish_name,
                        'needed' => $totalNeeded,
                        'available' => $available,
                        'shortage' => $totalNeeded - $available,
                    ];
                } elseif ($available < ($totalNeeded * 1.2)) {
                    $warnings[] = [
                        'ingredient' => $ingredient->ingredient_name,
                        'dish' => $dish->dish_name,
                        'needed' => $totalNeeded,
                        'available' => $available,
                        'buffer' => $available - $totalNeeded,
                    ];
                }
            }
        }

        return [
            'has_shortages' => ! empty($shortages),
            'has_warnings' => ! empty($warnings),
            'shortages' => $shortages,
            'warnings' => $warnings,
        ];
    }

    public function startPreparation(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    public function completePreparation(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $allItemsCompleted = $this->items()->where('status', '!=', 'completed')->count() === 0;

        if (! $allItemsCompleted) {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return true;
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('preparation_date', $date);
    }

    public function scopeByMealType($query, $mealType)
    {
        return $query->where('meal_type', $mealType);
    }
}
