<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Supplier;
use App\Models\User;

class SubscriptionLimitService
{
    /**
     * Check if user can add more employees based on subscription limits
     */
    public function canAddEmployee(User $user): array
    {
        $subscription = $user->subscription()
            ->with('subscriptionPackage')
            ->where('subscription_status', 'active')
            ->where('subscription_endDate', '>=', now())
            ->first();

        if (! $subscription || ! $subscription->subscriptionPackage) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found. Please subscribe to a plan to add employees.',
            ];
        }

        $employeeLimit = $subscription->subscriptionPackage->employee_limit;

        // NULL means unlimited
        if ($employeeLimit === null) {
            return [
                'allowed' => true,
                'message' => 'Unlimited employees allowed',
                'current' => $this->getCurrentEmployeeCount($user),
                'limit' => null,
            ];
        }

        $currentCount = $this->getCurrentEmployeeCount($user);

        if ($currentCount >= $employeeLimit) {
            return [
                'allowed' => false,
                'message' => "Employee limit reached. Your {$subscription->subscriptionPackage->plan_name} plan allows {$employeeLimit} employee accounts. Upgrade your plan to add more employees.",
                'current' => $currentCount,
                'limit' => $employeeLimit,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Employee can be added',
            'current' => $currentCount,
            'limit' => $employeeLimit,
        ];
    }

    /**
     * Check if user can add more suppliers based on subscription limits
     */
    public function canAddSupplier(User $user): array
    {
        $subscription = $user->subscription()
            ->with('subscriptionPackage')
            ->where('subscription_status', 'active')
            ->where('subscription_endDate', '>=', now())
            ->first();

        if (! $subscription || ! $subscription->subscriptionPackage) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found. Please subscribe to a plan to add suppliers.',
            ];
        }

        $supplierLimit = $subscription->subscriptionPackage->supplier_limit;

        // NULL means unlimited
        if ($supplierLimit === null) {
            return [
                'allowed' => true,
                'message' => 'Unlimited suppliers allowed',
                'current' => $this->getCurrentSupplierCount($user),
                'limit' => null,
            ];
        }

        $currentCount = $this->getCurrentSupplierCount($user);

        if ($currentCount >= $supplierLimit) {
            return [
                'allowed' => false,
                'message' => "Supplier limit reached. Your {$subscription->subscriptionPackage->plan_name} plan allows {$supplierLimit} supplier accounts. Upgrade your plan to add more suppliers.",
                'current' => $currentCount,
                'limit' => $supplierLimit,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Supplier can be added',
            'current' => $currentCount,
            'limit' => $supplierLimit,
        ];
    }

    /**
     * Get current employee count for a user
     */
    public function getCurrentEmployeeCount(User $user): int
    {
        return Employee::where('user_id', $user->id)->count();
    }

    /**
     * Get current supplier count for a user
     */
    public function getCurrentSupplierCount(User $user): int
    {
        if (! $user->restaurantData) {
            return 0;
        }

        return Supplier::where('restaurant_id', $user->restaurantData->id)->count();
    }

    /**
     * Get subscription limits for a user
     */
    public function getSubscriptionLimits(User $user): array
    {
        $subscription = $user->subscription()
            ->with('subscriptionPackage')
            ->where('subscription_status', 'active')
            ->where('subscription_endDate', '>=', now())
            ->first();

        if (! $subscription || ! $subscription->subscriptionPackage) {
            return [
                'has_subscription' => false,
                'plan_name' => null,
                'employee_limit' => 0,
                'employee_current' => 0,
                'supplier_limit' => 0,
                'supplier_current' => 0,
            ];
        }

        return [
            'has_subscription' => true,
            'plan_name' => $subscription->subscriptionPackage->plan_name,
            'employee_limit' => $subscription->subscriptionPackage->employee_limit,
            'employee_current' => $this->getCurrentEmployeeCount($user),
            'supplier_limit' => $subscription->subscriptionPackage->supplier_limit,
            'supplier_current' => $this->getCurrentSupplierCount($user),
        ];
    }
}
