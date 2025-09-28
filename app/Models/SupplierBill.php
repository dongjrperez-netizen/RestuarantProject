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

        static::creating(function ($model) {
            if (empty($model->bill_number)) {
                $model->bill_number = 'BILL-'.date('Y').'-'.str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') {
            return 0;
        }

        return $this->due_date < now() ? now()->diffInDays($this->due_date) : 0;
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && $this->outstanding_amount > 0;
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
        return $query->where('due_date', '<', now())
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['cancelled']);
    }
}
