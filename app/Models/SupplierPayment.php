<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $table = 'supplier_payments';

    protected $primaryKey = 'payment_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'payment_reference',
        'bill_id',
        'restaurant_id',
        'supplier_id',
        'payment_date',
        'payment_amount',
        'payment_method',
        'transaction_reference',
        'notes',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function bill()
    {
        return $this->belongsTo(SupplierBill::class, 'bill_id', 'bill_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_reference)) {
                $model->payment_reference = 'PAY-'.date('Y').'-'.str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getFormattedStatusAttribute()
    {
        return match ($this->status) {
            'completed' => 'Completed',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($this->status)
        };
    }

    public function getFormattedMethodAttribute()
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'credit_card' => 'Credit Card',
            'paypal' => 'PayPal',
            'online' => 'Online Payment',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->payment_method))
        };
    }

    public function canBeCancelled()
    {
        return $this->status !== 'cancelled' && $this->status !== 'refunded';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
