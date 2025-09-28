<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class usersubscription_demo extends Model
{
    use HasFactory;

    protected $table = 'usersubscription_demos';

    protected $primaryKey = 'userSubscription_id';

    protected $fillable = [
        'subscription_startDate',
        'subscription_endDate',
        'remaining_minutes',
        'subscription_status',
        'plan_id',
        'restaurant_id',
    ];
}
