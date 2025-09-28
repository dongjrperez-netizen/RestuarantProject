<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'user_id',
        'table_number',
        'table_name',
        'seats',
        'status',
        'description',
        'x_position',
        'y_position',
    ];

    protected $casts = [
        'seats' => 'integer',
        'x_position' => 'decimal:2',
        'y_position' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    // Relationships
    public function reservations(): HasMany
    {
        return $this->hasMany(TableReservation::class);
    }

    public function activeReservations(): HasMany
    {
        return $this->hasMany(TableReservation::class)->active();
    }

    public function todayReservations(): HasMany
    {
        return $this->hasMany(TableReservation::class)->today();
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class)->whereIn('status', ['pending', 'in_progress', 'ready']);
    }

    // Helper methods
    public function getCurrentReservation()
    {
        return $this->reservations()
            ->where('status', 'seated')
            ->first();
    }

    public function getNextReservation()
    {
        return $this->reservations()
            ->upcoming()
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->first();
    }

    public function hasActiveReservation(): bool
    {
        return $this->activeReservations()->exists();
    }

    public function canBeReserved(\DateTime $date, \DateTime $time, int $duration = 120): bool
    {
        // Allow reservations for available and reserved tables (just not occupied)
        if ($this->status === 'occupied' || $this->status === 'maintenance') {
            return false;
        }

        $startDateTime = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
        $endDateTime = $startDateTime->copy()->addMinutes($duration);

        // Check for conflicting reservations
        $conflictingReservations = $this->reservations()
            ->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->where(function ($q) use ($startDateTime, $endDateTime) {
                    // Reservation starts within our time window
                    $q->whereRaw("DATE(reservation_date) = ? AND reservation_time >= ? AND reservation_time < ?", [
                        $startDateTime->format('Y-m-d'),
                        $startDateTime->format('H:i:s'),
                        $endDateTime->format('H:i:s')
                    ]);
                })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                    // Reservation ends within our time window
                    $q->whereRaw("DATE(reservation_date) = ? AND ADDTIME(reservation_time, SEC_TO_TIME(duration_minutes * 60)) > ? AND ADDTIME(reservation_time, SEC_TO_TIME(duration_minutes * 60)) <= ?", [
                        $startDateTime->format('Y-m-d'),
                        $startDateTime->format('H:i:s'),
                        $endDateTime->format('H:i:s')
                    ]);
                })->orWhere(function ($q) use ($startDateTime, $endDateTime) {
                    // Our reservation is within existing reservation time window
                    $q->whereRaw("DATE(reservation_date) = ? AND reservation_time <= ? AND ADDTIME(reservation_time, SEC_TO_TIME(duration_minutes * 60)) >= ?", [
                        $startDateTime->format('Y-m-d'),
                        $startDateTime->format('H:i:s'),
                        $endDateTime->format('H:i:s')
                    ]);
                });
            });

        $hasConflict = $conflictingReservations->exists();


        return !$hasConflict;
    }

    public function updateStatusBasedOnReservations(): void
    {
        $currentReservation = $this->getCurrentReservation();

        if ($currentReservation) {
            $this->update(['status' => 'occupied']);
        } else {
            $nextReservation = $this->getNextReservation();
            if ($nextReservation) {
                $this->update(['status' => 'reserved']);
            } else {
                $this->update(['status' => 'available']);
            }
        }
    }
}
