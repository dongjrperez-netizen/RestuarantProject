<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriptionpackage extends Model
{
    protected $table = 'subscriptionpackages';

    protected $primaryKey = 'plan_id'; // 👈 tell Laravel your PK column

    public $incrementing = true;       // because plan_id is auto increment

    protected $keyType = 'int';

    protected $fillable = [
        'plan_name',
        'plan_price',
        'plan_duration',
        'plan_duration_display',
        'paypal_plan_id',
        'employee_limit',
        'supplier_limit',
    ];
}
