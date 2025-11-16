<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierBill extends Model
{
    protected $table = 'supplier_bills';

    protected $primaryKey = 'bill_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'bill_number',
        'purchase_order_id',
        'restaurant_id',
        'supplier_id',
        'supplier_invoice_number',
        'bill_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'status',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'bill_id', 'bill_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Generate a unique bill number after the bill has an auto-incremented ID
        static::created(function ($model) {
            // If a bill_number was explicitly set, respect it
            if (!empty($model->bill_number)) {
                return;
            }

            $model->bill_number = 'BILL-' . date('Y') . '-' . str_pad($model->bill_id, 6, '0', STR_PAD_LEFT);

            // Save quietly to avoid firing events again
            $model->saveQuietly();
        });
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') {
            return 0;
        }

        if (!$this->due_date || now()->startOfDay() <= $this->due_date->startOfDay()) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay());
    }

    public function getIsOverdueAttribute()
    {
        if (!$this->due_date || $this->outstanding_amount <= 0) {
            return false;
        }

        // Only overdue if current date is AFTER the due date (not equal to)
        return now()->startOfDay() > $this->due_date->startOfDay();
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return ($this->paid_amount / $this->total_amount) * 100;
    }

    public function getFormattedStatusAttribute()
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'partially_paid' => 'Partially Paid',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    public function canBeEdited()
    {
        return ! in_array($this->status, ['paid', 'cancelled']);
    }

    public function canReceivePayment()
    {
        return $this->outstanding_amount > 0 && ! in_array($this->status, ['cancelled']);
    }

    public function markAsPaid()
    {
        $this->update([
            'paid_amount' => $this->total_amount,
            'outstanding_amount' => 0,
            'status' => 'paid',
        ]);
    }

    public function calculateLateFee($feePercentage = 2)
    {
        if (! $this->is_overdue) {
            return 0;
        }

        $daysOverdue = $this->days_overdue;
        if ($daysOverdue <= 0) {
            return 0;
        }

        return ($this->outstanding_amount * $feePercentage / 100) * ($daysOverdue / 30);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->startOfDay())
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['cancelled']);
    }
}
