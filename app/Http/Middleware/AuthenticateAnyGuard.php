<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAnyGuard
{
    /**
     * Handle an incoming request.
     * Authenticate using any available guard (web, waiter, kitchen, cashier)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guards = ['waiter', 'kitchen', 'cashier', 'web'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Set the authenticated user for broadcasting
                Auth::shouldUse($guard);
                \Log::info('AuthenticateAnyGuard: Authenticated', [
                    'guard' => $guard,
                    'user_id' => Auth::guard($guard)->user()->employee_id ?? Auth::guard($guard)->user()->id ?? null
                ]);
                return $next($request);
            }
        }

        \Log::warning('AuthenticateAnyGuard: No authentication found');
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}