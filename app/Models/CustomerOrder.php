<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $table = 'customer_orders';

    protected $primaryKey = 'order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'table_id',
        'reservation_id',
        'employee_id',
        'restaurant_id',
        'order_number',
        'customer_name',
        'status',
        'subtotal',
        'tax_amount',
        'reservation_fee',
        'total_amount',
        'notes',
        'ordered_at',
        'prepared_at',
        'served_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'reservation_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'prepared_at' => 'datetime',
        'served_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'subtotal' => 0.00,
        'tax_amount' => 0.00,
        'reservation_fee' => 0.00,
        'total_amount' => 0.00,
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(TableReservation::class, 'reservation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restaurant_id', 'id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class, 'order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'order_id', 'order_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeServed($query)
    {
        return $query->where('status', 'served');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeByRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');
        $lastOrder = static::whereDate('created_at', now())
            ->where('restaurant_id', $this->restaurant_id)
            ->latest()
            ->first();

        $sequence = $lastOrder ? (int)substr($lastOrder->order_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals()
    {
        $subtotal = $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $taxRate = 0.12; // 12% tax rate, can be configurable
        $taxAmount = $subtotal * $taxRate;

        // Include reservation fee in total if exists
        $reservationFee = $this->reservation_fee ?? 0;
        $total = $subtotal + $taxAmount + $reservationFee;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'reservation_fee' => $reservationFee,
            'total_amount' => $total,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $model->order_number = $model->generateOrderNumber();
            }
        });
    }
}