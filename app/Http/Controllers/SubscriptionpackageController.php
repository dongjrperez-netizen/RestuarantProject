<?php

namespace App\Http\Controllers;

use App\Models\Paymentinfo;
use App\Models\Restaurant_Data;
use App\Models\Subpayment;
use App\Models\Subscriptionpackage;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
            // Set free trial duration to 3 minutes so users can explore the system
            $endDate = now()->addMinutes(3);

            $userSubscription = Usersubscription::create([
                'subscription_startDate' => $startDate,
                'subscription_endDate' => $endDate,
                // remaining_days is derived from subscription_endDate by the model; store 0 explicitly
                'remaining_days' => 30,
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

    /**
     * Create PayMongo subscription checkout for GCash
     */
    public function createPayMongoSubscription(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscriptionpackages,plan_id',
            'payment_method' => 'required|string|in:gcash,grab_pay,card',
        ]);

        $user = auth()->user();
        $plan = Subscriptionpackage::findOrFail($request->plan_id);

        // Check if PayMongo is configured
        if (empty(config('services.paymongo.secret_key'))) {
            return response()->json([
                'message' => 'PayMongo is not configured. Please contact the administrator.'
            ], 500);
        }

        // Construct full name from user fields
        $fullName = trim($user->first_name . ' ' . ($user->middle_name ? $user->middle_name . ' ' : '') . $user->last_name);

        // Validate user has required information
        if (empty($fullName) || empty($user->email)) {
            return response()->json([
                'message' => 'Your account is missing required information (name or email). Please update your profile first.'
            ], 400);
        }

        try {
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'line_items' => [
                                [
                                    'currency' => 'PHP',
                                    'amount' => (int) round($plan->plan_price * 100), // Convert to centavos
                                    'name' => 'ServeWise - ' . $plan->plan_name . ' Subscription',
                                    'quantity' => 1,
                                ],
                            ],
                            'payment_method_types' => [$request->payment_method],
                            'success_url' => route('subscriptions.paymongo.success') . '?plan_id=' . $plan->plan_id,
                            'cancel_url' => route('subscriptions.paymongo.cancel'),
                            'customer' => [
                                'name' => $fullName,
                                'email' => $user->email,
                            ],
                            'metadata' => [
                                'plan_id' => $plan->plan_id,
                                'user_id' => $user->id,
                                'payment_method' => $request->payment_method,
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $errorResponse = $response->json();
                $errorMessage = 'Payment processing failed';

                // Extract specific error message from PayMongo response
                if (isset($errorResponse['errors']) && is_array($errorResponse['errors']) && count($errorResponse['errors']) > 0) {
                    $errorMessage = $errorResponse['errors'][0]['detail'] ?? $errorMessage;
                } elseif (isset($errorResponse['message'])) {
                    $errorMessage = $errorResponse['message'];
                }

                Log::error('PayMongo Subscription Checkout Error', [
                    'response' => $errorResponse,
                    'plan_id' => $request->plan_id,
                    'user_id' => $user->id,
                    'status_code' => $response->status(),
                ]);

                return response()->json(['message' => $errorMessage], 400);
            }

            $checkoutData = $response->json();

            // Store checkout session ID in session for verification later
            session(['paymongo_checkout_session_id' => $checkoutData['data']['id']]);
            session(['paymongo_plan_id' => $plan->plan_id]);

            Log::info('PayMongo Subscription Checkout Created', [
                'plan_id' => $plan->plan_id,
                'user_id' => $user->id,
                'checkout_session_id' => $checkoutData['data']['id'],
                'amount' => $plan->plan_price,
                'payment_method' => $request->payment_method,
            ]);

            return response()->json([
                'checkout_url' => $checkoutData['data']['attributes']['checkout_url'],
                'checkout_session_id' => $checkoutData['data']['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayMongo Subscription Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'plan_id' => $request->plan_id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Payment processing error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle PayMongo payment success
     */
    public function payMongoSuccess(Request $request)
    {
        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return redirect()->route('subscriptions.index')
                ->withErrors('No restaurant data found for this user.');
        }

        $restaurantId = $restaurant->id;
        $planId = $request->get('plan_id') ?? session('paymongo_plan_id');
        $checkoutSessionId = $request->get('checkout_session_id') ?? session('paymongo_checkout_session_id');

        if (!$planId || !$checkoutSessionId) {
            return redirect()->route('subscriptions.index')
                ->withErrors('Missing payment information.');
        }

        $plan = Subscriptionpackage::findOrFail($planId);

        $startDate = now();
        $endDate = now()->addDays((int) $plan->plan_duration);

        try {
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
                'payment_name' => 'PayMongo - GCash',
                'payment_status' => 'completed',
            ]);

            $subPayment = Subpayment::create([
                'amount' => $plan->plan_price,
                'currency' => 'PHP',
                'paypal_transaction_id' => $checkoutSessionId, // Reusing this field for PayMongo
                'payment_id' => $paymentInfo->payment_id,
                'restaurant_id' => $restaurantId,
            ]);

            $user->save();

            DB::commit();

            // Clear session data
            session()->forget(['paymongo_checkout_session_id', 'paymongo_plan_id']);

            Log::info('PayMongo Subscription Activated', [
                'user_id' => $user->id,
                'plan_id' => $plan->plan_id,
                'checkout_session_id' => $checkoutSessionId,
                'subscription_id' => $userSubscription->userSubscription_id,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Subscription activated successfully via GCash!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('PayMongo Subscription Activation Error: ' . $e->getMessage());

            return redirect()->route('subscriptions.index')
                ->withErrors(['error' => 'Subscription activation failed. Please contact support.']);
        }
    }

    /**
     * Handle PayMongo payment cancellation
     */
    public function payMongoCancel()
    {
        // Clear session data
        session()->forget(['paymongo_checkout_session_id', 'paymongo_plan_id']);

        return redirect()->route('subscriptions.index')
            ->withErrors('Payment was cancelled.');
    }
}
