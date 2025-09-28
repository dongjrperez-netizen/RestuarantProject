<?php

namespace App\Http\Controllers;

use App\Models\Paymentinfo;
use App\Models\Restaurant_Data;
use App\Models\Subpayment;
use App\Models\Subscriptionpackage;
use App\Models\Usersubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class SubscriptionpackageController extends Controller
{
    public function index(): Response
    {
        $plans = Subscriptionpackage::all();
        $subscriptions = UserSubscription::where('user_id', auth()->user()->id)->get();

        return Inertia::render('Subscriptions/Subscription', [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'csrf_token' => csrf_token(),
            'success' => session('success'),
        ]);
    }

    public function create(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:subscriptionpackages,plan_id']);
        $plan = subscriptionpackage::where('plan_id', $request->plan_id)->firstOrFail();
        $request->validate(['plan_id' => 'required|exists:subscriptionpackages,plan_id']);
        $plan = Subscriptionpackage::where('plan_id', $request->plan_id)->firstOrFail();

        // if (auth()->user()->hasActiveSubscription()) {
        //     return redirect()->back()->withErrors(['error' => 'You already have an active subscription.']);
        // }

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            if (! $plan->paypal_plan_id) {
                return redirect()->back()->withErrors(['error' => 'Subscription setup problem. Missing PayPal plan configuration.']);
            }

            $returnUrl = route('subscriptions.success', ['plan_id' => $plan->plan_id]);
            $cancelUrl = route('subscriptions.cancel');

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
                return redirect()->back()->withErrors(['error' => 'Unable to initiate subscription.']);
            }

            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }

            return redirect()->back()->withErrors(['error' => 'Approval URL not found.']);

        } catch (\Exception $e) {
            Log::error('PayPal Subscription Error: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Subscription initiation failed. Try again later.']);
        }
    }

    public function success(Request $request)
    {
        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        if (! $restaurant) {
            return redirect()->route('subscriptions.index')
                ->withErrors('No restaurant data found for this user.');
        }

        $restaurantId = $restaurant->id;

        $plan = Subscriptionpackage::findOrFail($request->plan_id);
        $subscriptionId = $request->get('subscription_id') ?? session('paypal_subscription_id');

        if (! $subscriptionId) {
            return redirect()->route('subscriptions.index')->withErrors('Missing PayPal subscription ID.');
        }

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $accessToken = $provider->getAccessToken();
        $subscriptionDetails = $provider->showSubscriptionDetails($subscriptionId);

        if (! isset($subscriptionDetails['status']) || $subscriptionDetails['status'] !== 'ACTIVE') {
            return redirect()->route('subscriptions.index')->withErrors('Subscription not activated.');
        }

        $startDate = now();
        $endDate = now()->addDays((int) $plan->plan_duration);

        DB::beginTransaction();

        $userSubscription = Usersubscription::create([
            'subscription_startDate' => $startDate,
            'subscription_endDate' => $endDate,
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
        $user->save();

        DB::commit();

        return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');

    }

    public function cancel()
    {
        return redirect()->route('subscriptions.index')->withErrors('Payment was cancelled.');
    }

    public function createFreeTrial(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:subscriptionpackages,plan_id']);

        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        if (! $restaurant) {
            return redirect()->route('subscriptions.index')
                ->withErrors('No restaurant data found for this user.');
        }

        $plan = Subscriptionpackage::where('plan_id', $request->plan_id)->firstOrFail();

        // Check if user already has an active subscription
        $existingSubscription = Usersubscription::where('user_id', $user->id)
            ->where('subscription_status', 'active')
            ->first();

        if ($existingSubscription) {
            return redirect()->route('subscriptions.index')
                ->withErrors('You already have an active subscription.');
        }

        try {
            DB::beginTransaction();

            $startDate = now();
            $endDate = now()->addSeconds(15);

            $userSubscription = Usersubscription::create([
                'subscription_startDate' => $startDate,
                'subscription_endDate' => $endDate,
                'remaining_days' => 20, // 10 seconds instead of plan duration
                'subscription_status' => 'active',
                'plan_id' => $plan->plan_id,
                'user_id' => $user->id,
            ]);

            $user->save();

            DB::commit();

            return redirect()->route('dashboard')->with('success', 'Free trial activated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Free Trial Creation Error: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Free trial activation failed. Try again later.']);
        }
    }
}
