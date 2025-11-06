<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'usersubscriptions'; // 👈 fix table name

    protected $primaryKey = 'userSubscription_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'subscription_startDate',
        'subscription_endDate',
        'subscription_status',
        'plan_id',
        'user_id',
        'is_trial',
    ];

    // Define the relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Define the relationship with Subscription Package
    public function subscriptionPackage()
    {
        return $this->belongsTo(Subscriptionpackage::class, 'plan_id', 'plan_id');
    }

    // Scope for active subscriptions
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active');
    }

    // Scope for current user subscriptions
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Get current active subscription for a user (excluding free trials)
    public function scopeCurrent($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('subscription_status', 'active')
            ->where('subscription_endDate', '>=', now())
            ->where('plan_id', '!=', 4) // Exclude Free Trial (plan_id = 4)
            ->orderBy('subscription_endDate', 'desc');
    }

    // Calculate remaining days based on actual subscription end date (can be negative if expired)
    public function getRemainingDaysAttribute()
    {
        if ($this->subscription_endDate) {
            $endDate = \Carbon\Carbon::parse($this->subscription_endDate);

            return now()->diffInDays($endDate, false);
        }

        return 0;
    }

    // Get remaining time with days, hours, and minutes
    public function getRemainingTimeAttribute()
    {
        if ($this->subscription_endDate) {
            $endDate = \Carbon\Carbon::parse($this->subscription_endDate);
            $now = now();

            if ($now->gt($endDate)) {
                return 'Expired';
            }

            $diff = $now->diff($endDate);
            $days = $diff->days;
            $hours = $diff->h;
            $minutes = $diff->i;

            if ($days > 0) {
                return "{$days} days, {$hours} hours, {$minutes} minutes";
            } elseif ($hours > 0) {
                return "{$hours} hours, {$minutes} minutes";
            } else {
                return "{$minutes} minutes";
            }
        }

        return 'No subscription';
    }

    // Check if subscription is expired based on actual end date
    public function getIsExpiredAttribute()
    {
        if ($this->subscription_endDate) {
            $endDate = \Carbon\Carbon::parse($this->subscription_endDate);

            return now()->gt($endDate);
        }

        return true;
    }

    // Check if subscription is currently active (not expired)
    public function getIsCurrentlyActiveAttribute()
    {
        return $this->subscription_status === 'active' && ! $this->is_expired;
    }
}
