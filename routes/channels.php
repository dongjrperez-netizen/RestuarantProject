<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Kitchen channel - only kitchen staff and waiters can listen
Broadcast::channel('restaurant.{restaurantId}.kitchen', function ($user, $restaurantId) {
    // Check if user is authenticated as kitchen staff, waiter, or admin for this restaurant
    $employee = null;
    
    if (Auth::guard('kitchen')->check()) {
        $employee = Auth::guard('kitchen')->user();
    } elseif (Auth::guard('waiter')->check()) {
        $employee = Auth::guard('waiter')->user();
    } elseif (Auth::guard('web')->check()) {
        // Admin user
        return $user->id == $restaurantId;
    }
    
    return $employee && $employee->user_id == $restaurantId;
});

// Cashier channel - only cashier staff and admins can listen
Broadcast::channel('restaurant.{restaurantId}.cashier', function ($user, $restaurantId) {
    // Check if user is authenticated as cashier or admin for this restaurant
    $employee = null;
    
    if (Auth::guard('cashier')->check()) {
        $employee = Auth::guard('cashier')->user();
    } elseif (Auth::guard('web')->check()) {
        // Admin user
        return $user->id == $restaurantId;
    }
    
    return $employee && $employee->user_id == $restaurantId;
});

// Waiter channel - only waiter staff can listen
Broadcast::channel('restaurant.{restaurantId}.waiter', function ($user, $restaurantId) {
    // $user is automatically the authenticated user from whichever guard is active
    // For waiters: $user will be an Employee model with user_id field
    // For web users: $user will be a User model with id field

    \Log::info('Waiter channel authorization', [
        'user_class' => get_class($user),
        'user_id' => $user->employee_id ?? $user->id ?? 'unknown',
        'restaurant_id_field' => $user->user_id ?? $user->id ?? 'none',
        'requested_restaurant_id' => $restaurantId,
        'all_guards' => [
            'waiter' => Auth::guard('waiter')->check(),
            'web' => Auth::guard('web')->check(),
            'kitchen' => Auth::guard('kitchen')->check(),
            'cashier' => Auth::guard('cashier')->check(),
        ]
    ]);

    // Check if user is a waiter Employee
    if (isset($user->user_id)) {
        // This is an Employee model (waiter, kitchen, etc.)
        return (int) $user->user_id === (int) $restaurantId;
    }

    // Check if user is admin (web guard - User model)
    if (isset($user->id) && !isset($user->employee_id)) {
        return (int) $user->id === (int) $restaurantId;
    }

    return false;
});

// Inventory channel - restaurant owners and managers can listen
Broadcast::channel('restaurant.{restaurantId}.inventory', function ($user, $restaurantId) {
    // Check if user is admin (web guard - User model)
    if (Auth::guard('web')->check()) {
        return (int) $user->id === (int) $restaurantId;
    }

    // Check if user is a manager or other employee with inventory access
    $employee = null;

    if (Auth::guard('cashier')->check()) {
        $employee = Auth::guard('cashier')->user();
    } elseif (Auth::guard('kitchen')->check()) {
        $employee = Auth::guard('kitchen')->user();
    } elseif (Auth::guard('waiter')->check()) {
        $employee = Auth::guard('waiter')->user();
    }

    // Employees can listen to inventory updates for their restaurant
    return $employee && (int) $employee->user_id === (int) $restaurantId;
});