<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'email',
        'phonenumber',
        'password',
        'manager_access_code',
        'status',
        'role_id',
    ];

    protected $attributes = [
        'role_id' => 1, // Default value
    ];

    // Define the relationship with Restaurant_Data
    public function restaurantData()
    {
        // A user has one restaurant
        return $this->hasOne(Restaurant_Data::class, 'user_id', 'id');
    }

    // Alias for backward compatibility
    public function restaurant_data()
    {
        return $this->restaurantData();
    }

    // Define the relationship with UserSubscription
    public function subscription()
    {
        return $this->hasOne(UserSubscription::class, 'user_id', 'id');
    }

    // Define the relationship with MenuPlans through Restaurant_Data
    public function menuPlans()
    {
        return $this->hasManyThrough(
            MenuPlan::class,
            Restaurant_Data::class,
            'user_id', // Foreign key on Restaurant_Data table
            'restaurant_id', // Foreign key on MenuPlan table
            'id', // Local key on User table
            'id' // Local key on Restaurant_Data table
        );
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'manager_access_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
