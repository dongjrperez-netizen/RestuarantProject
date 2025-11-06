<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularEmployee extends Model
{
    use HasFactory;

    protected $table = 'regular_employees';

    protected $primaryKey = 'regular_employee_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'firstname',
        'lastname',
        'middle_initial',
        'age',
        'date_of_birth',
        'email',
        'address',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'age' => 'integer',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id', 'id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $name = $this->firstname;
        if ($this->middle_initial) {
            $name .= ' ' . $this->middle_initial . '.';
        }
        $name .= ' ' . $this->lastname;

        return trim($name);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
