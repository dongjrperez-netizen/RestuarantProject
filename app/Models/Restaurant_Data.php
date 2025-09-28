<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant_Data extends Model
{
    protected $table = 'restaurant_data';

    protected $fillable = [
        'user_id',
        'restaurant_name',
        'address',

        'contact_number',
    ];

    // Define the relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Define the relationship with Documents
    public function documents()
    {
        return $this->hasMany(Document::class, 'restaurant_id');
    }

    // Define the relationship with Ingredients
    public function ingredients()
    {
        return $this->hasMany(Ingredients::class, 'restaurant_id');
    }

    // Define the relationship with Dishes
    public function dishes()
    {
        return $this->hasMany(Dish::class, 'restaurant_id');
    }

    // Define the relationship with Purchase Orders
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'restaurant_id');
    }
}
