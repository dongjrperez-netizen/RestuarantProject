<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TableReservation extends Model
{
    protected $fillable = [
        'table_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'party_size',
        'reservation_date',
        'reservation_time',
        'duration_minutes',
        'actual_arrival_time',
        'dining_start_time',
        'status',
        'special_requests',
        'notes',
        'cancelled_at',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'reservation_time' => 'datetime',
        'actual_arrival_time' => 'datetime',
        'dining_start_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'party_size' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected $dates = [
        'reservation_date',
        'reservation_time',
        'actual_arrival_time',
        'dining_start_time',
        'cancelled_at',
    ];

    // Relationships
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'seated']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', now()->startOfDay())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', today());
    }

    public function scopeForTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    // Accessor methods
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'seated']);
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->reservation_date >= now() && in_array($this->status, ['pending', 'confirmed']);
    }

    public function getIsOverdueAttribute(): bool
    {
        $reservationDateTime = Carbon::parse($this->reservation_date->format('Y-m-d') . ' ' . $this->reservation_time->format('H:i:s'));
        return $reservationDateTime->addMinutes(15)->isPast() && $this->status === 'pending';
    }

    public function getEndTimeAttribute()
    {
        $reservationDateTime = Carbon::parse($this->reservation_date->format('Y-m-d') . ' ' . $this->reservation_time->format('H:i:s'));
        return $reservationDateTime->addMinutes($this->duration_minutes);
    }

    // Helper methods
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeSeated(): bool
    {
        return $this->status === 'confirmed';
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function markArrived(): void
    {
        $this->update([
            'actual_arrival_time' => now(),
        ]);
    }

    public function seat(): void
    {
        $updateData = [
            'status' => 'seated',
            'dining_start_time' => now(),
        ];

        // If arrival time wasn't recorded yet, record it now
        if (!$this->actual_arrival_time) {
            $updateData['actual_arrival_time'] = now();
        }

        $this->update($updateData);

        // Update table status to occupied
        $this->table->update(['status' => 'occupied']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);

        // Update table status back to available
        $this->table->update(['status' => 'available']);
    }

    public function markNoShow(): void
    {
        $this->update(['status' => 'no_show']);

        // Update table status back to available
        $this->table->update(['status' => 'available']);
    }
}
