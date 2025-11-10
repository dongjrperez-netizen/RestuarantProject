<?php

namespace App\Http\Controllers;

use App\Models\Paymentinfo;
use App\Models\Restaurant_Data;
use App\Models\Subpayment;
use App\Models\Subscriptionpackage;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class RenewSubscriptionController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        // Get current expired or expiring subscription
        $currentSubscription = Usersubscription::where('user_id', $user->id)
            ->whereIn('subscription_status', ['archive', 'active'])
            ->orderBy('subscription_endDate', 'desc')
            ->first();

        // Get all subscription packages except free trial (plan_id 4)
        $plans = Subscriptionpackage::where('plan_id', '!=', 4)->get();
        $subscriptions = Usersubscription::where('user_id', $user->id)
            ->join('subscriptionpackages', 'usersubscriptions.plan_id', '=', 'subscriptionpackages.plan_id')
            ->select('usersubscriptions.*', 'subscriptionpackages.plan_name')
            ->get();

        return Inertia::render('RenewSubscription', [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'currentSubscription' => $currentSubscription,
            'csrf_token' => csrf_token(),
            'success' => session('success'),
        ]);
    }

    public function renew(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:subscriptionpackages,plan_id']);

        $user = Auth::user();
        $plan = Subscriptionpackage::where('plan_id', $request->plan_id)->firstOrFail();

        // Check if user already has an active subscription
        $activeSubscription = Usersubscription::where('user_id', $user->id)
            ->where('subscription_status', 'active')
            ->first();

        if ($activeSubscription) {
            $now = Carbon::now();
            $endDate = Carbon::parse($activeSubscription->subscription_endDate);

            // Only allow renewal if subscription is expiring within 7 days or has expired
            if ($now->diffInDays($endDate, false) > 7) {
                return redirect()->back()->withErrors(['error' => 'You can only renew your subscription within 7 days of expiration.']);
            }
        }

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            if (! $plan->paypal_plan_id) {
                return redirect()->back()->withErrors(['error' => 'Subscription setup problem. Missing PayPal plan configuration.']);
            }

            $returnUrl = route('subscriptions.renew.success', ['plan_id' => $plan->plan_id]);
            $cancelUrl = route('subscriptions.renew.cancel');

            $payload = [
                'plan_id' => $plan->paypal_plan_id,
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
                return redirect()->back()->withErrors(['error' => 'Unable to initiate subscription renewal.']);
            }

            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }

            return redirect()->back()->withErrors(['error' => 'Approval URL not found.']);

        } catch (\Exception $e) {
            Log::error('PayPal Subscription Renewal Error: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Subscription renewal failed. Try again later.']);
        }
    }

    public function renewSuccess(Request $request)
    {
        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        if (! $restaurant) {
            return redirect()->route('subscriptions.renew')
                ->withErrors('No restaurant data found for this user.');
        }

        $restaurantId = $restaurant->id;
        $plan = Subscriptionpackage::findOrFail($request->plan_id);
        $subscriptionId = $request->get('subscription_id') ?? session('paypal_subscription_id');

        if (! $subscriptionId) {
            return redirect()->route('subscriptions.renew')->withErrors('Missing PayPal subscription ID.');
        }

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $subscriptionDetails = $provider->showSubscriptionDetails($subscriptionId);

        if (! isset($subscriptionDetails['status']) || $subscriptionDetails['status'] !== 'ACTIVE') {
            return redirect()->route('subscriptions.renew')->withErrors('Subscription not activated.');
        }

        try {
            DB::beginTransaction();

            // Archive any existing active subscription
            Usersubscription::where('user_id', $user->id)
                ->where('subscription_status', 'active')
                ->update(['subscription_status' => 'archive']);

            $startDate = now();
            $endDate = now()->addDays((int) $plan->plan_duration);

            $userSubscription = Usersubscription::create([
                'subscription_startDate' => $startDate,
                'subscription_endDate' => $endDate,
                'remaining_days' => $plan->plan_duration,
                'subscription_status' => 'active',
                'plan_id' => $plan->plan_id,
                'user_id' => $user->id,
                'is_trial' => false,
            ]);

            $paymentInfo = Paymentinfo::create([
                'payment_name' => 'PayPal',
                'payment_status' => 'completed',
            ]);

            $subPayment = Subpayment::create([
                'amount' => $plan->plan_price,
                'currency' => 'PHP',
                'paypal_transaction_id' => $subscriptionDetails['id'],
                'payment_id' => $paymentInfo->payment_id,
                'restaurant_id' => $restaurantId,
            ]);

            DB::commit();

            return redirect()->route('dashboard')->with('success', 'Subscription renewed successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Subscription Renewal Success Error: '.$e->getMessage());

            return redirect()->route('subscriptions.renew')->withErrors('Subscription renewal failed. Please try again.');
        }
    }

    public function renewCancel()
    {
        return redirect()->route('subscriptions.renew')->withErrors('Subscription renewal was cancelled.');
    }
}
