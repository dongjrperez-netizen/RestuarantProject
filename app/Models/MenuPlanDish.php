<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuPlanDish extends Model
{
    protected $table = 'menu_plan_dishes';

    protected $fillable = [
        'menu_plan_id',
        'dish_id',
        'planned_quantity',
        'meal_type',
        'planned_date',
        'day_of_week',
        'notes',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'planned_quantity' => 'integer',
        'day_of_week' => 'integer',
    ];

    public function menuPlan()
    {
        return $this->belongsTo(MenuPlan::class, 'menu_plan_id', 'menu_plan_id');
    }

    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id', 'dish_id');
    }
}
