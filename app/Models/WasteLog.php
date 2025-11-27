<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteLog extends Model
{
    protected $table = 'waste_logs';
    protected $primaryKey = 'waste_log_id';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'dish_id',
        'dish_name',
        'quantity',
        'unit',
        'unit_price',
        'total_cost',
        'reason',
        'reported_by',
        'reported_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'reported_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reported_by', 'employee_id');
    }
}
