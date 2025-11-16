<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Administrator;
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
     * Check if supplier account is active
     */
    public function validateSupplierStatus(Supplier $supplier): ?array
    {
        if (!$supplier->is_active) {
            return [
                'email' => 'Your supplier account is not active. Please contact the restaurant.'
            ];
        }

        return null;
    }
    public function validateAdminEmail(Administrator $admin): ?array
    {
        if (!$admin->is_active) {
            return [
                'email' => 'Your admin account is not active'
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

        \Log::info('🔍 USER SUBSCRIPTION REDIRECT', [
            'user_id' => $user->user_id,
            'subscription_status' => $subscription ? $subscription->subscription_status : 'none'
        ]);

        if ($subscription && strtolower($subscription->subscription_status) === 'active') {
            \Log::info('🔍 REDIRECTING TO DASHBOARD');
            return redirect()->route('dashboard');
        } elseif ($subscription && strtolower($subscription->subscription_status) === 'archive') {
            \Log::info('🔍 REDIRECTING TO RENEW SUBSCRIPTION');
            return redirect()->route('subscriptions.renew');
        } else {
            \Log::info('🔍 REDIRECTING TO SUBSCRIPTIONS INDEX');
            return redirect()->route('subscriptions.index');
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
           // Special handling for kitchen
        if(strtolower($role->role_name) === 'kitchen') {
            \Log::info('🔍 KITCHEN DETECTED - CALLING CASHIER REDIRECT');
            return $this->handleKitchenRedirect($employee);
        }

        // For other roles, redirect to dashboard
        \Log::info('🔍 NON-WAITER-CASHIER REDIRECT', [
            'role_name' => $role->role_name
        ]);
        return redirect()->route('dashboard');
    }

    /**
     * Handle supplier redirect
     */
    public function handleSupplierRedirect(Supplier $supplier): RedirectResponse
    {
        \Log::info('🔍 SUPPLIER REDIRECT START', [
            'supplier_id' => $supplier->supplier_id,
            'email' => $supplier->email,
        ]);

      return redirect()->route('supplier.dashboard');
    }
     /**
     * Handle kitchen-specific redirect logic
     */
    private function handleKitchenRedirect(Employee $employee): RedirectResponse
    {
        \Log::info('🔍  KITCHEN REDIRECT START');

        // Direct redirect to waiter dashboard
        \Log::info('🔍 KITCHEN REDIRECT TO DASHBOARD');
        return redirect()->route('kitchen.dashboard');
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
        return redirect()->route('cashier.bills');
    }

      private function handleAdminRedirect(Administrator $admin): RedirectResponse
    {
        \Log::info('🔍 ADMIN REDIRECT START');

        // Direct redirect to admin dashboard
        \Log::info('🔍 ADMIN REDIRECT TO ADMIN DASHBOARD');
        return redirect()->route('admin.dashboard');
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
        // Check all possible user types first to handle email conflicts
        $user = User::where('email', $email)->first();
        $employee = Employee::where('email', $email)->with('role')->first();
        $supplier = Supplier::where('email', $email)->first();
        $admin = Administrator::where('email', $email)->first();

        // If both user and supplier exist with same email, prioritize based on active status
        if ($user && $supplier) {
            // Prioritize active supplier over pending/rejected user
            if ($supplier->is_active && in_array($user->status, ['Pending', 'Rejected'])) {
                return [
                    'type' => 'supplier',
                    'model' => $supplier,
                    'guard' => 'supplier'
                ];
            } elseif (!$supplier->is_active && !in_array($user->status, ['Pending', 'Rejected'])) {
                return [
                    'type' => 'user',
                    'model' => $user,
                    'guard' => 'web'
                ];
            } else {
                // Both active or both inactive - prioritize supplier for supplier portal access
                if ($supplier->is_active) {
                    return [
                        'type' => 'supplier',
                        'model' => $supplier,
                        'guard' => 'supplier'
                    ];
                } else {
                    // If supplier is not active, default to user
                    return [
                        'type' => 'user',
                        'model' => $user,
                        'guard' => 'web'
                    ];
                }
            }
        }

        // If both user and employee exist with same email
        if ($user && $employee) {
            // Prioritize active user (restaurant owner) over employee
            if (!in_array($user->status, ['Pending', 'Rejected']) && $employee->status === 'active') {
                // Both are active - prioritize user (restaurant owner)
                \Log::info('🔍 EMAIL CONFLICT: User and Employee both active, prioritizing User', [
                    'email' => $email,
                    'user_id' => $user->user_id,
                    'employee_id' => $employee->employee_id
                ]);
                return [
                    'type' => 'user',
                    'model' => $user,
                    'guard' => 'web'
                ];
            } elseif (!in_array($user->status, ['Pending', 'Rejected']) && $employee->status !== 'active') {
                // User active, employee inactive
                return [
                    'type' => 'user',
                    'model' => $user,
                    'guard' => 'web'
                ];
            } elseif (in_array($user->status, ['Pending', 'Rejected']) && $employee->status === 'active') {
                // User pending/rejected, employee active - use employee
                $guard = 'employee';
                if ($employee->role) {
                    $roleName = strtolower($employee->role->role_name);
                    if ($roleName === 'waiter') {
                        $guard = 'waiter';
                    } elseif ($roleName === 'cashier') {
                        $guard = 'cashier';
                    } elseif ($roleName === 'kitchen') {
                        $guard = 'kitchen';
                    }
                }
                return [
                    'type' => 'employee',
                    'model' => $employee,
                    'guard' => $guard
                ];
            } else {
                // Both pending/inactive - default to user
                return [
                    'type' => 'user',
                    'model' => $user,
                    'guard' => 'web'
                ];
            }
        }

        // If only user exists
        if ($user) {
            return [
                'type' => 'user',
                'model' => $user,
                'guard' => 'web'
            ];
        }

        // If both employee and supplier exist with same email, prioritize based on active status
        if ($employee && $supplier) {
            // Prioritize active supplier over inactive employee, or vice versa
            if ($supplier->is_active && $employee->status !== 'active') {
                return [
                    'type' => 'supplier',
                    'model' => $supplier,
                    'guard' => 'supplier'
                ];
            } elseif (!$supplier->is_active && $employee->status === 'active') {
                // Determine guard based on role
                $guard = 'employee'; // default
                if ($employee->role) {
                    $roleName = strtolower($employee->role->role_name);
                    if ($roleName === 'waiter') {
                        $guard = 'waiter';
                    } elseif ($roleName === 'cashier') {
                        $guard = 'cashier';
                    }
                    elseif ($roleName === 'kitchen') {
                        $guard = 'kitchen';
                    }
                }

                return [
                    'type' => 'employee',
                    'model' => $employee,
                    'guard' => $guard
                ];
            } else {
                // Both active or both inactive - prioritize supplier for unified login
                if ($supplier->is_active) {
                    return [
                        'type' => 'supplier',
                        'model' => $supplier,
                        'guard' => 'supplier'
                    ];
                } else {
                    // If supplier is not active, default to employee
                    $guard = 'employee'; // default
                    if ($employee->role) {
                        $roleName = strtolower($employee->role->role_name);
                        if ($roleName === 'waiter') {
                            $guard = 'waiter';
                        } elseif ($roleName === 'cashier') {
                            $guard = 'cashier';
                        }
                        elseif ($roleName === 'kitchen') {
                            $guard = 'kitchen';
                        }
                    }

                    return [
                        'type' => 'employee',
                        'model' => $employee,
                        'guard' => $guard
                    ];
                }
            }
        }

        // If only employee exists
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
                elseif ($roleName === 'kitchen') {
                    $guard = 'kitchen';
                }
            }

            return [
                'type' => 'employee',
                'model' => $employee,
                'guard' => $guard
            ];
        }

        // If only supplier exists
        if ($supplier) {
            return [
                'type' => 'supplier',
                'model' => $supplier,
                'guard' => 'supplier'
            ];
        }

        if ($admin) {
            return [
                'type' => 'admin',
                'model' => $admin,
                'guard' => 'admin'
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

        // Determine employee role (if applicable) for later guard behavior
        $employeeRoleName = null;
        if ($userInfo['type'] === 'employee' && $userInfo['model'] instanceof Employee) {
            $userInfo['model']->loadMissing('role');
            $employeeRoleName = strtolower($userInfo['model']->role->role_name ?? '');
        }

        // Perform type-specific validations
        if ($userInfo['type'] === 'user') {
            // Check if email is verified (restaurant owners must verify email)
            if (!$userInfo['model']->hasVerifiedEmail()) {
                return [
                    'success' => false,
                    'errors' => ['email' => 'Please verify your email address before logging in. Check your inbox for the verification link.'],
                    'user_type' => 'user'
                ];
            }

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
        } elseif ($userInfo['type'] === 'supplier') {
            $statusErrors = $this->validateSupplierStatus($userInfo['model']);
            if ($statusErrors) {
                return [
                    'success' => false,
                    'errors' => $statusErrors,
                    'user_type' => 'supplier'
                ];
            }
        }elseif ($userInfo['type'] === 'admin') {
            $emailErrors = $this->validateAdminEmail($userInfo['model']);
            if ($emailErrors) {
                return [
                    'success' => false,
                    'errors' => $emailErrors,
                    'user_type' => 'admin'
                ];
            }
        }

        // Attempt authentication with appropriate guard
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        // Special case: manager/supervisor employees should also be logged in as the owner on the web guard
        if ($userInfo['type'] === 'employee' && in_array($employeeRoleName, ['manager', 'supervisor'], true)) {
            /** @var Employee $employeeModel */
            $employeeModel = $userInfo['model'];

            // We already verified the password above, so we can log in directly
            Auth::guard($userInfo['guard'])->login($employeeModel, $remember);

            // Also log in the underlying restaurant owner on the web guard so all owner routes work
            $owner = $employeeModel->restaurant;
            if ($owner) {
                Auth::guard('web')->login($owner, $remember);
            }

            return [
                'success' => true,
                'user_type' => 'employee',
                'guard' => $userInfo['guard'],
                'model' => $employeeModel
            ];
        }

        // Default behavior for all other user types
        if (Auth::guard($userInfo['guard'])->attempt($credentials, $remember)) {
            $model = Auth::guard($userInfo['guard'])->user();

            if ($userInfo['type'] === 'employee') {
                $model->load('role');
            }

            return [
                'success' => true,
                'user_type' => $userInfo['type'],
                'guard' => $userInfo['guard'],
                'model' => $model
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
        \Log::info('🔍 UNIFIED REDIRECT HANDLER', [
            'user_type' => $userType,
            'model_class' => get_class($userModel),
            'model_id' => $userModel->id ?? $userModel->user_id ?? $userModel->employee_id ?? 'unknown'
        ]);

        if ($userType === 'user') {
            \Log::info('🔍 HANDLING USER TYPE REDIRECT');
            // Handle rejected status - redirect to account update
            if ($userModel->status === 'Rejected') {
                \Log::info('🔍 USER STATUS: REJECTED - REDIRECTING TO ACCOUNT UPDATE');
                return redirect()->route('account.update');
            }

            return $this->handleUserSubscriptionRedirect($userModel);
        }

        if ($userType === 'employee') {
            \Log::info('🔍 HANDLING EMPLOYEE TYPE REDIRECT');
            return $this->handleEmployeeRoleRedirect($userModel);
        }


        if ($userType === 'supplier') {
            \Log::info('🔍 HANDLING SUPPLIER TYPE REDIRECT');
            return $this->handleSupplierRedirect($userModel);
        }
        if ($userType === 'admin') {
            \Log::info('🔍 HANDLING ADMIN TYPE REDIRECT');
            return $this->handleAdminRedirect($userModel);
        }

        // Fallback
        \Log::warning('🔍 NO USER TYPE MATCHED - FALLBACK TO DASHBOARD');
        return redirect()->route('dashboard');
    }
}