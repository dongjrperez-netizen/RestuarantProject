<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'lastname',
        'firstname',
        'middlename',
        'email',
        'password',
        'manager_access_code',
        'date_of_birth',
        'gender',
        'role_id',
        'user_id',
        'status',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'manager_access_code',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName(); // Use primary key (employee_id)
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier()
    {
        return $this->getKey(); // Return the primary key value
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscription()
    {
        // Employee inherits subscription from the restaurant owner (User)
        return $this->restaurant->subscription();
    }

    public function getFullNameAttribute()
    {
        $name = $this->firstname;
        if ($this->middlename) {
            $name .= ' '.$this->middlename;
        }
        $name .= ' '.$this->lastname;

        return trim($name);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeForRestaurant($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
