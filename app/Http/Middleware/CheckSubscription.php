<?php

namespace App\Http\Middleware;

use App\Models\UserSubscription;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            $activeSubscription = Usersubscription::where('user_id', $user->id)
                ->where('subscription_status', 'active')
                ->first();

            if ($activeSubscription) {
                $now = Carbon::now();
                $endDate = Carbon::parse($activeSubscription->subscription_endDate);

                // Check if subscription has expired
                if ($now->greaterThan($endDate)) {
                    // Update subscription status to archive
                    $activeSubscription->update([
                        'subscription_status' => 'archive',
                        'remaining_days' => 0,
                    ]);

                    // Check if this is an API request
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Your subscription has expired. Please renew your subscription to continue.',
                            'redirect' => route('subscriptions.renew')
                        ], 403);
                    }

                    return redirect()->route('subscriptions.renew')
                        ->withErrors(['error' => 'Your subscription has expired. Please renew your subscription to continue.']);
                }
            } else {
                // Check if this is an API request
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You need an active subscription to access this feature.',
                        'redirect' => route('subscriptions.index')
                    ], 403);
                }

                // No active subscription found, redirect to subscriptions page
                return redirect()->route('subscriptions.index')
                    ->withErrors(['error' => 'You need an active subscription to access this feature.']);
            }
        }

        return $next($request);
    }
}
