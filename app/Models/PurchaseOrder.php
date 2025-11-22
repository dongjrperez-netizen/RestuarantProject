<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $primaryKey = 'purchase_order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'po_number',
        'restaurant_id',
        'supplier_id',
        'supplier_name',
        'supplier_contact',
        'supplier_email',
        'supplier_phone',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'delivery_instructions',
        'delivery_condition',
        'received_by',
        'receiving_notes',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'supplier_response',
        'supplier_responded_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'supplier_responded_at' => 'datetime',
    ];

    protected $appends = [
        'order_number',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant_Data::class, 'restaurant_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function bill()
    {
        return $this->hasOne(SupplierBill::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // Accessor for order_number to maintain compatibility with frontend
    public function getOrderNumberAttribute()
    {
        return $this->po_number;
    }

    // Accessor to get supplier display name (from relationship or manual entry)
    public function getSupplierDisplayNameAttribute()
    {
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->supplier_name;
        }
        return $this->supplier_name ?? 'N/A';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->po_number)) {
                $model->po_number = 'PO-'.date('Y').'-'.str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
