<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationService
{
    /**
     * Check if user account status allows login
     */
    public function validateUserStatus(User $user): ?array
    {
        return match ($user->status) {
            'Pending' => [
                'status' => 'Your account is pending approval. Please wait for administrator approval.'
            ],
            'Rejected' => null, // Allow login but redirect to account update
            default => null, // No errors
        };
    }

    /**
     * Check if employee account is active
     */
    public function validateEmployeeStatus(Employee $employee): ?array
    {
        if ($employee->status !== 'active') {
            return [
                'email' => 'Your employee account is not active. Please contact your manager.'
            ];
        }

        return null;
    }

    /**
     * Check if restaurant has active subscription through employee
     */
    public function validateEmployeeSubscription(Employee $employee): ?array
    {
        $restaurant = $employee->restaurant;

        if (!$restaurant) {
            return [
                'email' => 'No restaurant associated with this employee account.'
            ];
        }

        $subscription = $restaurant->subscription()
            ->orderByDesc('subscription_endDate')
            ->first();

        if (!$subscription || strtolower($subscription->subscription_status) !== 'active') {
            return [
                'email' => 'Restaurant subscription is not active. Please contact your manager.'
            ];
        }

        return null;
    }

    /**
     * Check user subscription and determine redirect
     */
    public function handleUserSubscriptionRedirect(User $user): RedirectResponse
    {
        $subscription = $user->subscription()
            ->orderByDesc('subscription_endDate')
            ->first();

        if ($subscription && strtolower($subscription->subscription_status) === 'active') {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($subscription && strtolower($subscription->subscription_status) === 'archive') {
            return redirect()->intended(route('subscriptions.renew', absolute: false));
        } else {
            return redirect()->intended(route('subscriptions.index', absolute: false));
        }
    }

    /**
     * Handle employee role-based redirect
     */
    public function handleEmployeeRoleRedirect(Employee $employee): RedirectResponse
    {
        // DEBUG: Log employee redirect attempt
        \Log::info('🔍 EMPLOYEE REDIRECT START', [
            'employee_id' => $employee->employee_id,
            'email' => $employee->email,
            'role_id_raw' => $employee->getAttribute('role_id'), // Get raw value
        ]);

        // Load the role relationship
        $employee->load('role');
        $role = $employee->role;

        // DEBUG: Log role information
        \Log::info('🔍 EMPLOYEE ROLE INFO', [
            'role_exists' => $role ? 'Yes' : 'No',
            'role_name' => $role->role_name ?? null,
            'role_id' => $role->id ?? null,
        ]);

        if (!$role) {
            \Log::warning('🔍 NO ROLE FOUND - DEFAULT REDIRECT');
            return redirect()->route('dashboard');
        }

        // Special handling for waiters
        if (strtolower($role->role_name) === 'waiter') {
            \Log::info('🔍 WAITER DETECTED - CALLING WAITER REDIRECT');
            return $this->handleWaiterRedirect($employee);
        }

        // Special handling for cashiers
        if (strtolower($role->role_name) === 'cashier') {
            \Log::info('🔍 CASHIER DETECTED - CALLING CASHIER REDIRECT');
            return $this->handleCashierRedirect($employee);
        }

        // For other roles, redirect to dashboard
        \Log::info('🔍 NON-WAITER-CASHIER REDIRECT', [
            'role_name' => $role->role_name
        ]);
        return redirect()->route('dashboard');
    }

    /**
     * Handle waiter-specific redirect logic
     */
    private function handleWaiterRedirect(Employee $employee): RedirectResponse
    {
        \Log::info('🔍 WAITER REDIRECT START');

        // Direct redirect to waiter dashboard
        \Log::info('🔍 WAITER REDIRECT TO DASHBOARD');
        return redirect()->route('waiter.dashboard');
    }

    /**
     * Handle cashier-specific redirect logic
     */
    private function handleCashierRedirect(Employee $employee): RedirectResponse
    {
        \Log::info('🔍 CASHIER REDIRECT START');

        // Direct redirect to cashier dashboard
        \Log::info('🔍 CASHIER REDIRECT TO CASHIER DASHBOARD');
        return redirect()->route('cashier.dashboard');
    }

    /**
     * Handle rate limiting for authentication requests
     */
    public function handleRateLimit(string $throttleKey): void
    {
        RateLimiter::hit($throttleKey);
    }

    /**
     * Clear rate limiting for successful authentication
     */
    public function clearRateLimit(string $throttleKey): void
    {
        RateLimiter::clear($throttleKey);
    }

    /**
     * Detect user type by email and return array with user info
     */
    public function detectUserType(string $email): array
    {
        // Check if it's a restaurant owner (User)
        $user = User::where('email', $email)->first();
        if ($user) {
            return [
                'type' => 'user',
                'model' => $user,
                'guard' => 'web'
            ];
        }

        // Check if it's an employee
        $employee = Employee::where('email', $email)->with('role')->first();
        if ($employee) {
            // Determine guard based on role
            $guard = 'employee'; // default
            if ($employee->role) {
                $roleName = strtolower($employee->role->role_name);
                if ($roleName === 'waiter') {
                    $guard = 'waiter';
                } elseif ($roleName === 'cashier') {
                    $guard = 'cashier';
                }
            }

            return [
                'type' => 'employee',
                'model' => $employee,
                'guard' => $guard
            ];
        }

        return [
            'type' => null,
            'model' => null,
            'guard' => null
        ];
    }

    /**
     * Authenticate user with appropriate guard and perform validations
     */
    public function attemptUnifiedLogin(string $email, string $password, bool $remember = false): array
    {
        // DEBUG: Log the detection process
        \Log::info('🔍 USER TYPE DETECTION', [
            'email' => $email,
            'password_length' => strlen($password)
        ]);

        $userInfo = $this->detectUserType($email);

        // DEBUG: Log the detection result
        \Log::info('🔍 USER TYPE DETECTED', [
            'type' => $userInfo['type'],
            'guard' => $userInfo['guard'],
            'model_found' => $userInfo['model'] ? 'Yes' : 'No'
        ]);

        if (!$userInfo['model']) {
            return [
                'success' => false,
                'errors' => ['email' => 'No account found with this email address.'],
                'user_type' => null
            ];
        }

        // Verify password
        $passwordCheck = Hash::check($password, $userInfo['model']->password);

        // DEBUG: Log password verification
        \Log::info('🔍 PASSWORD VERIFICATION', [
            'email' => $email,
            'password_provided' => $password,
            'password_hash' => $userInfo['model']->password,
            'password_check_result' => $passwordCheck ? 'MATCH' : 'NO MATCH'
        ]);

        if (!$passwordCheck) {
            return [
                'success' => false,
                'errors' => ['email' => 'The provided credentials do not match our records.'],
                'user_type' => $userInfo['type']
            ];
        }

        // Perform type-specific validations
        if ($userInfo['type'] === 'user') {
            $statusErrors = $this->validateUserStatus($userInfo['model']);
            if ($statusErrors) {
                // Allow login for 'Rejected' status but will redirect to account update
                if ($userInfo['model']->status !== 'Rejected') {
                    return [
                        'success' => false,
                        'errors' => $statusErrors,
                        'user_type' => 'user'
                    ];
                }
            }
        } elseif ($userInfo['type'] === 'employee') {
            $statusErrors = $this->validateEmployeeStatus($userInfo['model']);
            if ($statusErrors) {
                return [
                    'success' => false,
                    'errors' => $statusErrors,
                    'user_type' => 'employee'
                ];
            }

            $subscriptionErrors = $this->validateEmployeeSubscription($userInfo['model']);
            if ($subscriptionErrors) {
                return [
                    'success' => false,
                    'errors' => $subscriptionErrors,
                    'user_type' => 'employee'
                ];
            }
        }

        // Attempt authentication with appropriate guard
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        if (Auth::guard($userInfo['guard'])->attempt($credentials, $remember)) {
            return [
                'success' => true,
                'user_type' => $userInfo['type'],
                'guard' => $userInfo['guard'],
                'model' => $userInfo['model']
            ];
        }

        return [
            'success' => false,
            'errors' => ['email' => 'Authentication failed. Please try again.'],
            'user_type' => $userInfo['type']
        ];
    }

    /**
     * Handle unified login redirect based on user type
     */
    public function handleUnifiedLoginRedirect(string $userType, $userModel): RedirectResponse
    {
        if ($userType === 'user') {
            // Handle rejected status - redirect to account update
            if ($userModel->status === 'Rejected') {
                return redirect()->intended(route('account.update', absolute: false));
            }

            return $this->handleUserSubscriptionRedirect($userModel);
        }

        if ($userType === 'employee') {
            return $this->handleEmployeeRoleRedirect($userModel);
        }

        // Fallback
        return redirect()->route('dashboard');
    }
}