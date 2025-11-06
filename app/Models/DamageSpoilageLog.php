<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageSpoilageLog extends Model
{
    protected $fillable = [
        'restaurant_id',
        'ingredient_id',
        'user_id',
        'type',
        'quantity',
        'unit',
        'reason',
        'notes',
        'incident_date',
        'estimated_cost',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'quantity' => 'decimal:3',
        'estimated_cost' => 'decimal:2',
    ];

    // Type constants
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_SPOILAGE = 'spoilage';

    public static function getTypes(): array
    {
        return [
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_SPOILAGE => 'Spoilage',
        ];
    }

    // Relationships
    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'ingredient_id');
    }

    public function user()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'employee_id');
    }

    // Scopes
    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('incident_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('incident_date', '>=', now()->subDays($days));
    }

    // Accessors
    public function getTypeNameAttribute()
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getFormattedQuantityAttribute()
    {
        return $this->quantity . ' ' . $this->unit;
    }

    public function getFormattedCostAttribute()
    {
        return $this->estimated_cost ? '$' . number_format($this->estimated_cost, 2) : 'N/A';
    }
}
