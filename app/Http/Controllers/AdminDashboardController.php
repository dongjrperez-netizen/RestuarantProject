<?php

namespace App\Http\Controllers;

use App\Models\Restaurant_Data;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        $analytics = $this->getDashboardAnalytics();

        return Inertia::render('Admin/Dashboard', [
            'analytics' => $analytics,
            'recentActivities' => $this->getRecentActivities(),
        ]);
    }

    private function getDashboardAnalytics(): array
    {
        return [
            'totalRestaurants' => Restaurant_Data::count(),
            'pendingApprovals' => User::where('status', 'Pending')->count(),
            'approvedRestaurants' => User::where('status', 'Approved')->count(),
            'rejectedApplications' => User::where('status', 'Rejected')->count(),
            'activeSubscriptions' => UserSubscription::where('subscription_status', 'active')->count(),
            'expiredSubscriptions' => UserSubscription::where('subscription_status', 'archive')->count(),
            'totalRevenue' => $this->calculateTotalRevenue(),
            'monthlyStats' => $this->getMonthlyStats(),
        ];
    }

    private function calculateTotalRevenue(): float
    {
        // This would calculate based on your subscription payments
        // For now, returning a placeholder calculation
        $activeSubscriptions = UserSubscription::where('subscription_status', 'active')->count();

        return $activeSubscriptions * 29.99; // Assuming base price
    }

    private function getMonthlyStats(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();

        return [
            'newRegistrations' => User::where('created_at', '>=', $currentMonth)->count(),
            'newSubscriptions' => UserSubscription::where('created_at', '>=', $currentMonth)->count(),
            'approvalsThisMonth' => User::where('status', 'Approved')
                ->where('updated_at', '>=', $currentMonth)->count(),
        ];
    }

    private function getRecentActivities(): array
    {
        // Get recent user registrations
        $recentUsers = User::with('restaurantData')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'registration',
                    'message' => "New restaurant registration: {$user->first_name} {$user->last_name}",
                    'timestamp' => $user->created_at,
                    'status' => $user->status,
                ];
            });

        // Get recent subscription activities
        $recentSubscriptions = UserSubscription::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($subscription) {
                $user = User::find($subscription->user_id);

                return [
                    'type' => 'subscription',
                    'message' => "Subscription {$subscription->subscription_status}: {$user->first_name} {$user->last_name}",
                    'timestamp' => $subscription->created_at,
                    'status' => $subscription->subscription_status,
                ];
            });

        return $recentUsers->concat($recentSubscriptions)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }
}
