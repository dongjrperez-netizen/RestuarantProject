<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployeeLoginRequest;
use App\Models\Employee;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLoginController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * Show the employee login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/EmployeeLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'ownerLoginUrl' => route('login'),
        ]);
    }

    /**
     * Handle an incoming emoployee authentication request.
     */
    public function store(EmployeeLoginRequest $request): RedirectResponse
    {
        // Get employee by email
        $employee = Employee::where('email', $request->email)->first();

        if (!$employee) {
            return back()->withErrors([
                'email' => 'Employee not found with this email address.',
            ]);
        }

        // Validate employee status
        $statusErrors = $this->authService->validateEmployeeStatus($employee);
        if ($statusErrors) {
            return back()->withErrors($statusErrors);
        }

        // Validate subscription through restaurant
        $subscriptionErrors = $this->authService->validateEmployeeSubscription($employee);
        if ($subscriptionErrors) {
            return back()->withErrors($subscriptionErrors);
        }

        // Authenticate the employee
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        // Get authenticated employee
        $employee = Auth::guard('employee')->user();

        // Handle role-based redirect
        return $this->authService->handleEmployeeRoleRedirect($employee);
    }

    /**
     * Destroy an employee authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }

    /**
     * Show employee password reset request form.
     */
    public function showForgotPasswordForm(): Response
    {
        return Inertia::render('auth/EmployeeForgotPassword');
    }
}