<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subpayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'subpayment_id';

    protected $fillable = [
        'amount',
        'currency',
        'paypal_transaction_id',
        'payment_id',
        'restaurant_id',
    ];
}
