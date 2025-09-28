<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubscriptionController extends Controller
{
    public function index(): Response
    {
        $subscriptions = UserSubscription::with(['user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                $user = User::find($subscription->user_id);

                return [
                    'id' => $subscription->userSubscription_id,
                    'user_id' => $subscription->user_id,
                    'user_name' => $user ? $user->first_name.' '.$user->last_name : 'Unknown',
                    'user_email' => $user ? $user->email : 'Unknown',
                    'start_date' => $subscription->subscription_startDate,
                    'end_date' => $subscription->subscription_endDate,
                    'remaining_days' => $subscription->remaining_days,
                    'status' => $subscription->subscription_status,
                    'is_trial' => $subscription->is_trial,
                    'plan_id' => $subscription->plan_id,
                ];
            });

        $stats = [
            'total' => $subscriptions->count(),
            'active' => $subscriptions->where('status', 'active')->count(),
            'expired' => $subscriptions->where('status', 'archive')->count(),
            'trial' => $subscriptions->where('is_trial', true)->count(),
        ];

        return Inertia::render('Admin/Subscriptions', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
        ]);
    }

    public function extend(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $subscription = UserSubscription::findOrFail($id);

        $currentEndDate = Carbon::parse($subscription->subscription_endDate);
        $newEndDate = $currentEndDate->addDays($request->days);

        $subscription->update([
            'subscription_endDate' => $newEndDate,
            'remaining_days' => $subscription->remaining_days + $request->days,
            'subscription_status' => 'active',
        ]);

        return back()->with('success', "Subscription extended by {$request->days} days successfully.");
    }

    public function suspend($id): RedirectResponse
    {
        $subscription = UserSubscription::findOrFail($id);

        $subscription->update([
            'subscription_status' => 'archive',
        ]);

        return back()->with('success', 'Subscription suspended successfully.');
    }

    public function activate($id): RedirectResponse
    {
        $subscription = UserSubscription::findOrFail($id);

        $subscription->update([
            'subscription_status' => 'active',
        ]);

        return back()->with('success', 'Subscription activated successfully.');
    }

    public function show($id): Response
    {
        $subscription = UserSubscription::findOrFail($id);
        $user = User::find($subscription->user_id);

        $subscriptionData = [
            'id' => $subscription->userSubscription_id,
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'phone' => $user->phonenumber,
            ],
            'start_date' => $subscription->subscription_startDate,
            'end_date' => $subscription->subscription_endDate,
            'remaining_days' => $subscription->remaining_days,
            'status' => $subscription->subscription_status,
            'is_trial' => $subscription->is_trial,
            'plan_id' => $subscription->plan_id,
            'created_at' => $subscription->created_at,
            'updated_at' => $subscription->updated_at,
        ];

        return Inertia::render('Admin/SubscriptionDetail', [
            'subscription' => $subscriptionData,
        ]);
    }
}
