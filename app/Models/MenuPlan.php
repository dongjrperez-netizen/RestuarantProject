<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MenuPlan extends Model
{
    protected $table = 'menu_plans';

    protected $primaryKey = 'menu_plan_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'plan_name',
        'plan_type',
        'start_date',
        'end_date',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'menu_plan_dishes', 'menu_plan_id', 'dish_id')
            ->withPivot(['planned_quantity', 'meal_type', 'planned_date', 'day_of_week', 'notes'])
            ->withTimestamps();
    }

    public function menuPlanDishes()
    {
        return $this->hasMany(MenuPlanDish::class, 'menu_plan_id', 'menu_plan_id');
    }

    public function getDishesForDate($date, $mealType = null)
    {
        $query = $this->dishes()->wherePivot('planned_date', $date);

        if ($mealType) {
            $query->wherePivot('meal_type', $mealType);
        }

        return $query->get();
    }

    public function getDishesForWeekDay($dayOfWeek, $mealType = null)
    {
        $query = $this->dishes()->wherePivot('day_of_week', $dayOfWeek);

        if ($mealType) {
            $query->wherePivot('meal_type', $mealType);
        }

        return $query->get();
    }

    public function isActive()
    {
        return $this->is_active &&
               Carbon::parse($this->start_date)->lte(now()) &&
               Carbon::parse($this->end_date)->gte(now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now()->startOfDay());
    }

    public function scopeExpiredButActive($query)
    {
        return $query->where('is_active', true)
            ->where('end_date', '<', now()->startOfDay());
    }
}
