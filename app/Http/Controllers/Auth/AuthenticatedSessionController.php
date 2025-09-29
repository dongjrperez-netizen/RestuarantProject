<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * Show the unified login page for all user types.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'unified' => true, // Flag to indicate this supports unified login
        ]);
    }

    /**
     * Handle unified authentication request for all user types.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        \Log::info('🔍 UNIFIED LOGIN ATTEMPT', ['email' => $request->email]);

        // Use the unified authentication service
        $result = $this->authService->attemptUnifiedLogin(
            $request->email,
            $request->password,
            $request->boolean('remember')
        );

        \Log::info('🔍 UNIFIED LOGIN RESULT', [
            'success' => $result['success'],
            'user_type' => $result['user_type'] ?? null,
            'guard' => $result['guard'] ?? null
        ]);

        if (!$result['success']) {
            return back()->withErrors($result['errors']);
        }

        // Clean up any existing sessions from other guards before establishing new session
        // $this->cleanupAllGuardSessions($request);

        // Regenerate session
        $request->session()->regenerate();

        // Handle role-based redirect
        return $this->authService->handleUnifiedLoginRedirect(
            $result['user_type'],
            $result['model']
        );
    }

    /**
     * Destroy an authenticated session for any user type.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Determine which guard is currently authenticated and logout from all possible guards
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
        }

        if (Auth::guard('waiter')->check()) {
            Auth::guard('waiter')->logout();
        }

        if (Auth::guard('cashier')->check()) {
            Auth::guard('cashier')->logout();
        }

        if (Auth::guard('kitchen')->check()) {
            Auth::guard('kitchen')->logout();
        }

        if (Auth::guard('supplier')->check()) {
            Auth::guard('supplier')->logout();
        }

        // Clear all session data
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear any additional session data that might cause conflicts
        $request->session()->flush();

        // Clear any PayPal session data that might cause conflicts
        $request->session()->forget([
            'paypal_order_payment',
            'paypal_bill_payment',
            'gcash_payment_data',
            'payment_session'
        ]);

        return redirect('/');
    }

    /**
     * Clean up sessions from all authentication guards to prevent conflicts
     */
    private function cleanupAllGuardSessions(Request $request): void
    {
        // Logout from all guards without checking if they're active
        // This prevents session conflicts when switching between user types
        Auth::guard('web')->logout();
        Auth::guard('employee')->logout();
        Auth::guard('waiter')->logout();
        Auth::guard('cashier')->logout();
        Auth::guard('kitchen')->logout();
        Auth::guard('supplier')->logout();

        // Clear specific payment session data that might cause component conflicts
        $request->session()->forget([
            'paypal_order_payment',
            'paypal_bill_payment',
            'gcash_payment_data',
            'payment_session'
        ]);
    }
}