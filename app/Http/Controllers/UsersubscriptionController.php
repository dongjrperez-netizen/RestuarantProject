<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use Illuminate\Http\Request;

class UsersubscriptionController extends Controller
{
    /**
     * Display user subscriptions page with current and history
     */
    public function index()
    {
        $userId = auth()->id();

        // Get current active subscription (only truly active ones, excluding free trials)
        $currentSubscription = UserSubscription::with(['user', 'subscriptionPackage'])
            ->where('user_id', $userId)
            ->where('subscription_status', 'active')
            ->where('subscription_endDate', '>=', now()->format('Y-m-d'))
            ->where('plan_id', '!=', 4) // Exclude Free Trial (plan_id = 4)
            ->orderBy('subscription_endDate', 'desc')
            ->first();

        // Get subscription history (all subscriptions for the user, excluding free trials)
        $subscriptionHistory = UserSubscription::with(['user', 'subscriptionPackage'])
            ->forUser($userId)
            ->where('plan_id', '!=', 4) // Exclude Free Trial (plan_id = 4)
            ->orderBy('subscription_startDate', 'desc')
            ->get()
            ->map(function ($subscription) {
                // Auto-update subscription status if expired
                if ($subscription->subscription_status === 'active' && $subscription->is_expired) {
                    $subscription->subscription_status = 'expired';
                    $subscription->save();
                }

                return [
                    'userSubscription_id' => $subscription->userSubscription_id,
                    'plan_name' => $subscription->subscriptionPackage->plan_name ?? 'Unknown Plan',
                    'plan_price' => $subscription->subscriptionPackage->plan_price ?? 0,
                    'plan_duration' => $subscription->subscriptionPackage->plan_duration ?? 0,
                    'subscription_status' => $subscription->subscription_status,
                    'subscription_startDate' => $subscription->subscription_startDate,
                    'subscription_endDate' => $subscription->subscription_endDate,
                    'remaining_days' => $subscription->remaining_days, // Auto-calculated
                    'remaining_time' => $subscription->remaining_time, // Auto-calculated with time
                    'is_expired' => $subscription->is_expired,
                    'is_trial' => $subscription->is_trial,
                    'is_currently_active' => $subscription->is_currently_active,
                    'created_at' => $subscription->created_at,
                ];
            });

        // Format current subscription data
        $currentSubscriptionData = null;
        if ($currentSubscription) {
            // Auto-update status if expired
            if ($currentSubscription->is_expired) {
                $currentSubscription->subscription_status = 'expired';
                $currentSubscription->save();
                $currentSubscription = null; // Clear current subscription if expired
            } else {
                $currentSubscriptionData = [
                    'userSubscription_id' => $currentSubscription->userSubscription_id,
                    'plan_name' => $currentSubscription->subscriptionPackage->plan_name ?? 'Unknown Plan',
                    'plan_price' => $currentSubscription->subscriptionPackage->plan_price ?? 0,
                    'plan_duration' => $currentSubscription->subscriptionPackage->plan_duration ?? 0,
                    'subscription_status' => $currentSubscription->subscription_status,
                    'subscription_startDate' => $currentSubscription->subscription_startDate,
                    'subscription_endDate' => $currentSubscription->subscription_endDate,
                    'remaining_days' => $currentSubscription->remaining_days, // Auto-calculated
                    'remaining_time' => $currentSubscription->remaining_time, // Auto-calculated with time
                    'is_expired' => $currentSubscription->is_expired,
                    'is_trial' => $currentSubscription->is_trial,
                    'is_currently_active' => $currentSubscription->is_currently_active,
                    'created_at' => $currentSubscription->created_at,
                ];
            }
        }

        // Calculate statistics based on current status (excluding trials)
        $activeSubscriptions = $subscriptionHistory->filter(function ($sub) {
            return $sub['subscription_status'] === 'active' && ! $sub['is_expired'];
        });

        $expiredSubscriptions = $subscriptionHistory->filter(function ($sub) {
            return $sub['subscription_status'] === 'expired' || $sub['is_expired'];
        });

        $stats = [
            'total_subscriptions' => $subscriptionHistory->count(),
            'active_subscriptions' => $activeSubscriptions->count(),
            'expired_subscriptions' => $expiredSubscriptions->count(),
            'trial_subscriptions' => 0, // Don't show trial count since we're excluding trials
            'total_spent' => $subscriptionHistory->sum('plan_price'),
        ];

        return inertia('UserManagement/UserSubscription', [
            'currentSubscription' => $currentSubscriptionData,
            'subscriptionHistory' => $subscriptionHistory,
            'stats' => $stats,
        ]);
    }

    /**
     * Get subscription data for API calls
     */
    public function getSubscriptionData()
    {
        $userId = auth()->id();

        $currentSubscription = UserSubscription::with(['user', 'subscriptionPackage'])
            ->current($userId)
            ->first();

        $subscriptionHistory = UserSubscription::with(['user', 'subscriptionPackage'])
            ->forUser($userId)
            ->orderBy('subscription_startDate', 'desc')
            ->get();

        return response()->json([
            'current' => $currentSubscription,
            'history' => $subscriptionHistory,
        ]);
    }

    /**
     * Show renewal page with available plans
     */
    public function showRenewal()
    {
        $userId = auth()->id();

        // Get current subscription
        $currentSubscription = UserSubscription::with(['subscriptionPackage'])
            ->current($userId)
            ->first();

        // Format current subscription data
        $currentSubscriptionData = null;
        if ($currentSubscription) {
            $currentSubscriptionData = [
                'userSubscription_id' => $currentSubscription->userSubscription_id,
                'plan_name' => $currentSubscription->subscriptionPackage->plan_name ?? 'Unknown Plan',
                'plan_price' => $currentSubscription->subscriptionPackage->plan_price ?? 0,
                'plan_duration' => $currentSubscription->subscriptionPackage->plan_duration ?? 0,
                'subscription_status' => $currentSubscription->subscription_status,
                'subscription_startDate' => $currentSubscription->subscription_startDate,
                'subscription_endDate' => $currentSubscription->subscription_endDate,
                'remaining_days' => $currentSubscription->remaining_days,
                'remaining_time' => $currentSubscription->remaining_time,
                'is_expired' => $currentSubscription->is_expired,
                'is_trial' => $currentSubscription->is_trial,
                'is_currently_active' => $currentSubscription->is_currently_active,
                'created_at' => $currentSubscription->created_at,
            ];
        }

        // Get only the current subscription plan for renewal
        $availablePlans = [];
        if ($currentSubscription && $currentSubscription->subscriptionPackage) {
            $availablePlans = [\App\Models\Subscriptionpackage::find($currentSubscription->subscriptionPackage->plan_id)];
        }

        return inertia('UserManagement/RenewSubscription', [
            'currentSubscription' => $currentSubscriptionData,
            'availablePlans' => $availablePlans,
            'isRenewal' => true,
        ]);
    }

    /**
     * Show upgrade page with available plans
     */
    public function showUpgrade()
    {
        $userId = auth()->id();

        // Get current subscription
        $currentSubscription = UserSubscription::with(['subscriptionPackage'])
            ->current($userId)
            ->first();

        // Format current subscription data
        $currentSubscriptionData = null;
        if ($currentSubscription) {
            $currentSubscriptionData = [
                'userSubscription_id' => $currentSubscription->userSubscription_id,
                'plan_name' => $currentSubscription->subscriptionPackage->plan_name ?? 'Unknown Plan',
                'plan_price' => $currentSubscription->subscriptionPackage->plan_price ?? 0,
                'plan_duration' => $currentSubscription->subscriptionPackage->plan_duration ?? 0,
                'subscription_status' => $currentSubscription->subscription_status,
                'subscription_startDate' => $currentSubscription->subscription_startDate,
                'subscription_endDate' => $currentSubscription->subscription_endDate,
                'remaining_days' => $currentSubscription->remaining_days,
                'remaining_time' => $currentSubscription->remaining_time,
                'is_expired' => $currentSubscription->is_expired,
                'is_trial' => $currentSubscription->is_trial,
                'is_currently_active' => $currentSubscription->is_currently_active,
                'created_at' => $currentSubscription->created_at,
            ];
        }

        // Get available subscription plans (only higher tier plans, excluding free trial)
        $availablePlans = \App\Models\Subscriptionpackage::where('plan_price', '>', $currentSubscription->subscriptionPackage->plan_price ?? 0)
            ->where('plan_id', '!=', 4)
            ->get();

        return inertia('UserManagement/UpgradeSubscription', [
            'currentSubscription' => $currentSubscriptionData,
            'availablePlans' => $availablePlans,
            'isUpgrade' => true,
        ]);
    }

    /**
     * Process renewal or upgrade
     */
    public function processRenewalOrUpgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscriptionpackages,plan_id',
            'type' => 'required|in:renewal,upgrade',
        ]);

        $userId = auth()->id();
        $planId = $request->plan_id;
        $type = $request->type;

        // Get the selected plan
        $selectedPlan = \App\Models\Subscriptionpackage::findOrFail($planId);

        if (! $selectedPlan->paypal_plan_id) {
            return redirect()->back()->withErrors(['error' => 'Subscription setup problem. Missing PayPal plan configuration.']);
        }

        try {
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            // Store the action type in session for success handling
            session(['renewal_action_type' => $type]);

            // Set return URLs with action type parameter
            $returnUrl = route('user-management.subscriptions.success', [
                'plan_id' => $selectedPlan->plan_id,
                'action_type' => $type,
            ]);
            $cancelUrl = route('user-management.subscriptions.cancel');

            $payload = [
                'plan_id' => $selectedPlan->paypal_plan_id,
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'locale' => 'en-US',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ];

            $response = $provider->createSubscription($payload);

            if (! isset($response['links'])) {
                return redirect()->back()->withErrors(['error' => 'Unable to initiate '.$type.'.']);
            }

            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }

            return redirect()->back()->withErrors(['error' => 'Approval URL not found.']);

        } catch (\Exception $e) {
            \Log::error('PayPal '.ucfirst($type).' Error: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => ucfirst($type).' initiation failed. Try again later.']);
        }
    }

    /**
     * Handle successful PayPal payment for renewal/upgrade
     */
    public function paymentSuccess(Request $request)
    {
        $user = auth()->user();
        $plan = \App\Models\Subscriptionpackage::findOrFail($request->plan_id);
        $actionType = $request->action_type ?? session('renewal_action_type', 'renewal');
        $subscriptionId = $request->get('subscription_id');

        if (! $subscriptionId) {
            return redirect()->route('user-management.subscriptions')->withErrors('Missing PayPal subscription ID.');
        }

        try {
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();
            $subscriptionDetails = $provider->showSubscriptionDetails($subscriptionId);

            if (! isset($subscriptionDetails['status']) || $subscriptionDetails['status'] !== 'ACTIVE') {
                return redirect()->route('user-management.subscriptions')->withErrors('Subscription not activated.');
            }

            \DB::beginTransaction();

            // Get current active subscription with its package
            $currentSubscription = UserSubscription::with('subscriptionPackage')
                ->where('user_id', $user->id)
                ->where('subscription_status', 'active')
                ->where('plan_id', '!=', 4) // Exclude free trials
                ->first();

            $startDate = now();
            $endDate = now()->addDays((int) $plan->plan_duration);
            $remainingDaysToAdd = 0;

            // Handle renewal logic - preserve remaining days
            if ($actionType === 'renewal' && $currentSubscription) {
                // Refresh the subscription with its package to ensure accurate calculation
                $currentSubscription->load('subscriptionPackage');

                // Calculate remaining days from current subscription
                $remainingDaysToAdd = max(0, $currentSubscription->remaining_days);

                // Add remaining days to the new subscription end date
                $endDate = $endDate->addDays($remainingDaysToAdd);

                // Archive the old subscription
                $currentSubscription->update(['subscription_status' => 'archive']);
            }

            // Handle upgrade logic - expire current subscription WITHOUT adding remaining days
            // The remaining time from the old subscription will NOT be transferred to the new subscription
            if ($actionType === 'upgrade' && $currentSubscription) {
                $currentSubscription->update(['subscription_status' => 'expired']);
                // Note: $remainingDaysToAdd stays at 0 for upgrades
            }

            // Create new subscription (remaining_days will be auto-calculated by the model)
            $userSubscription = UserSubscription::create([
                'subscription_startDate' => $startDate,
                'subscription_endDate' => $endDate,
                'subscription_status' => 'active',
                'plan_id' => $plan->plan_id,
                'user_id' => $user->id,
                'is_trial' => false,
            ]);

            // Create payment records
            $paymentInfo = \App\Models\Paymentinfo::create([
                'payment_name' => 'PayPal',
                'payment_status' => 'completed',
            ]);

            $restaurant = \App\Models\Restaurant_Data::where('user_id', $user->id)->first();
            $restaurantId = $restaurant ? $restaurant->id : null;

            \App\Models\Subpayment::create([
                'amount' => $plan->plan_price,
                'currency' => 'PHP',
                'paypal_transaction_id' => $subscriptionDetails['id'],
                'payment_id' => $paymentInfo->payment_id,
                'restaurant_id' => $restaurantId,
            ]);

            \DB::commit();

            // Clear session
            session()->forget('renewal_action_type');

            // Create success message based on action type
            $successMessage = ucfirst($actionType).' successful! Your '.$plan->plan_name.' subscription is now active.';

            if ($actionType === 'renewal' && $remainingDaysToAdd > 0) {
                $successMessage .= ' Your remaining '.$remainingDaysToAdd.' days from your previous subscription have been added.';
            }

            return redirect()->route('user-management.subscriptions')->with('success', $successMessage);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('PayPal '.ucfirst($actionType).' Success Error: '.$e->getMessage());

            return redirect()->route('user-management.subscriptions')->withErrors('Payment processing failed.');
        }
    }

    /**
     * Handle cancelled PayPal payment
     */
    public function paymentCancel()
    {
        session()->forget('renewal_action_type');

        return redirect()->route('user-management.subscriptions')->with('message', 'Payment cancelled.');
    }
}
