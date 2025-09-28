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
        // Determine which guard is currently authenticated and logout
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        } elseif (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
