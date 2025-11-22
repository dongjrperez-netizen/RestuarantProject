<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Determine which user to share based on the route and authentication status
        $user = null;
        $userType = null;

        if ($request->is('admin/*') || $request->is('admin') || $request->is('request')) {
            // For admin routes, get the admin user
            $user = Auth::guard('admin')->user();
            $userType = 'admin';
        } elseif ($request->is('supplier/*') || $request->is('supplier')) {
            // For supplier routes, get the supplier user
            $user = Auth::guard('supplier')->user();
            $userType = 'supplier';
        } elseif ($request->is('cashier/*') || $request->is('cashier')) {
            // For cashier routes, get the cashier user
            $user = Auth::guard('cashier')->user();
            $userType = 'cashier';
        } elseif ($request->is('waiter/*') || $request->is('waiter')) {
            // For waiter routes, get the waiter user
            $user = Auth::guard('waiter')->user();
            $userType = 'waiter';
        }
        elseif ($request->is('kitchen/*') || $request->is('kitchen')) {
            // For waiter routes, get the waiter user
            $user = Auth::guard('kitchen')->user();
            $userType = 'kitchen';
        } else {
            // Check if authenticated as employee first
            if (Auth::guard('employee')->check()) {
                $user = Auth::guard('employee')->user();
                $userType = 'employee';
            } elseif (Auth::guard('web')->check()) {
                // For regular routes, get the web user (owner)
                $user = Auth::guard('web')->user();
                $userType = 'owner';
            }
        }

        // Get subscription data for restaurant owner
        $subscription = null;
        if ($userType === 'owner' && $user) {
            $subscription = $user->subscription()
                ->with('subscriptionPackage')
                ->where('subscription_status', 'active')
                ->first();
        }

        // Determine restaurant (restaurant_data) for current context
        $restaurant = null;
        if ($user) {
            // 1) Direct restaurantData relation (restaurant owner user)
            if (method_exists($user, 'restaurantData')) {
                $user->loadMissing('restaurantData');
                $restaurant = $user->restaurantData;
            }

            // 2) Supplier/Employee-style relation: check if restaurant() exists
            if (! $restaurant && method_exists($user, 'restaurant')) {
                $user->loadMissing('restaurant');
                $userRestaurant = $user->restaurant;

                // If restaurant is already Restaurant_Data instance (e.g., Supplier), use it directly
                if ($userRestaurant instanceof \App\Models\Restaurant_Data) {
                    $restaurant = $userRestaurant;
                }
                // If restaurant is a User instance (e.g., Employee), get its restaurantData
                elseif ($userRestaurant && method_exists($userRestaurant, 'restaurantData')) {
                    $userRestaurant->loadMissing('restaurantData');
                    $restaurant = $userRestaurant->restaurantData;
                }
            }
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'userType' => $userType,
                'restaurant' => $restaurant,
                'subscription' => $subscription ? [
                    'plan_name' => $subscription->subscriptionPackage->plan_name ?? null,
                    'plan_id' => $subscription->plan_id ?? null,
                    'status' => $subscription->subscription_status ?? null,
                    'end_date' => $subscription->subscription_endDate ?? null,
                ] : null,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'csrf_token' => csrf_token(), // Add CSRF token here
        ]);
    }
}