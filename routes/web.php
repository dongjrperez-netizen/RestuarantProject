<?php

use App\Http\Controllers\AccountUpdateController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DamageSpoilageController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RegularEmployeeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuPlanController;
use App\Http\Controllers\MenuPreparationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RenewSubscriptionController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SubscriptionpackageController;
use App\Http\Controllers\SupplierBillController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\UsersubscriptionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Custom broadcasting auth endpoint that supports multiple guards
Route::post('/broadcasting-custom-auth', function (Illuminate\Http\Request $request) {
    // Find authenticated user from any guard
    $authenticatedUser = null;
    $guardName = null;

    foreach (['waiter', 'kitchen', 'cashier', 'web'] as $guard) {
        if (Auth::guard($guard)->check()) {
            $authenticatedUser = Auth::guard($guard)->user();
            $guardName = $guard;
            break;
        }
    }

    if (!$authenticatedUser) {
        \Log::warning('Broadcasting auth failed - no authenticated user');
        return response()->json(['message' => 'Unauthenticated'], 403);
    }

    \Log::info('Broadcasting auth request', [
        'guard' => $guardName,
        'user_id' => $authenticatedUser->employee_id ?? $authenticatedUser->id,
        'channel' => $request->input('channel_name'),
    ]);

    // Set the default guard to use this authenticated user
    Auth::shouldUse($guardName);

    // Call Laravel's broadcasting authorization
    return Broadcast::auth($request);
})->middleware('web');

// Test route for broadcasting
Route::get('/test-broadcast/{restaurantId}', function($restaurantId) {
    $testOrder = (object) [
        'order_id' => 999,
        'order_number' => 'TEST-' . time(),
        'status' => 'pending',
        'restaurant_id' => $restaurantId,
        'customer_name' => 'Test Customer',
        'created_at' => now()->toISOString(),
        'table' => (object) ['table_number' => '99', 'table_name' => 'Test Table'],
        'order_items' => [(object) ['dish' => (object) ['dish_name' => 'Test Dish']]]
    ];

    broadcast(new \App\Events\OrderCreated((object) $testOrder))->toOthers();

    return response()->json(['message' => 'Test event broadcasted', 'order' => $testOrder]);
})->name('test.broadcast');

// Test route for void order notification
Route::get('/test-void-notification/{restaurantId}', function($restaurantId) {
    // Get a real table from the database for realistic testing
    $realTable = \App\Models\Table::where('user_id', $restaurantId)->first();

    // Create a mock order object with all necessary relationships
    $mockOrder = new \App\Models\CustomerOrder();
    $mockOrder->order_id = 'TEST-VOID-' . time();
    $mockOrder->order_number = 'ORD-TEST-' . time();
    $mockOrder->status = 'voided';
    $mockOrder->restaurant_id = $restaurantId;
    $mockOrder->customer_name = 'Test Customer';
    $mockOrder->total_amount = 1000;
    $mockOrder->created_at = now();
    $mockOrder->updated_at = now();

    // Create mock table relationship (use real table if available)
    if ($realTable) {
        $mockTable = new \stdClass();
        $mockTable->table_number = $realTable->table_number;
        $mockTable->table_name = $realTable->table_name;
        $mockTable->id = $realTable->id;
    } else {
        $mockTable = new \stdClass();
        $mockTable->table_number = '5';
        $mockTable->table_name = 'Table 5';
        $mockTable->id = 5;
    }

    // Create mock employee relationship
    $mockEmployee = new \stdClass();
    $mockEmployee->employee_id = 1;
    $mockEmployee->firstname = 'Test';
    $mockEmployee->lastname = 'Waiter';

    // Manually set relationships for the mock object
    $mockOrder->setRelation('table', $mockTable);
    $mockOrder->setRelation('employee', $mockEmployee);
    $mockOrder->setRelation('orderItems', collect([]));

    // Broadcast the void order event
    broadcast(new \App\Events\OrderStatusUpdated($mockOrder, 'completed'));

    \Log::info('Test void notification broadcasted', [
        'order_id' => $mockOrder->order_id,
        'order_number' => $mockOrder->order_number,
        'restaurant_id' => $restaurantId,
        'status' => $mockOrder->status,
        'table_id' => $mockTable->id,
    ]);

    return response()->json([
        'message' => 'Void order notification test broadcasted successfully!',
        'instructions' => [
            'Check the BELL ICON in the upper right corner of the waiter dashboard',
            'Expected notification: "Order ' . $mockOrder->order_number . ' has been voided by cashier"',
            'The notification should appear with a yellow warning icon',
            'Click the notification to be redirected to the order details',
            'The bell icon should show an unread count badge',
        ],
        'order' => [
            'order_id' => $mockOrder->order_id,
            'order_number' => $mockOrder->order_number,
            'status' => $mockOrder->status,
            'restaurant_id' => $restaurantId,
            'table' => [
                'id' => $mockTable->id,
                'table_number' => $mockTable->table_number,
                'table_name' => $mockTable->table_name,
            ],
        ]
    ]);
})->name('test.void.notification');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'check.subscription'])
    ->name('dashboard');

Route::get('/admin/login', [AdministratorController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdministratorController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdministratorController::class, 'logout'])->name('admin.logout');


// Employee-accessible routes (authenticated with employee guard)
Route::middleware('auth:employee')->group(function () {
    // Menu planning index - accessible by employees
    Route::get('/menu-planning', [MenuPlanController::class, 'index'])->name('menu-planning.index.employee');
});

// Waiter-specific routes (restricted to waiters only)
Route::middleware(['auth:waiter', 'role:waiter'])->prefix('waiter')->name('waiter.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\WaiterController::class, 'dashboard'])->name('dashboard');
    Route::get('/current-menu', [App\Http\Controllers\WaiterController::class, 'currentMenu'])->name('current-menu');
    Route::patch('/tables/{table}/status', [App\Http\Controllers\WaiterController::class, 'updateTableStatus'])->name('tables.update-status');
    Route::get('/take-order', [App\Http\Controllers\WaiterController::class, 'takeOrder'])->name('take-order');
    Route::get('/orders/create/{tableId}', [App\Http\Controllers\WaiterController::class, 'createOrder'])->name('orders.create');
    Route::post('/orders', [App\Http\Controllers\WaiterController::class, 'storeOrder'])->name('orders.store');
    Route::get('/tables/{table}/orders', [App\Http\Controllers\WaiterController::class, 'getTableOrders'])->name('tables.orders');
    Route::post('/orders/{orderId}/items/{itemId}/served', [App\Http\Controllers\WaiterController::class, 'updateItemServedStatus'])->name('orders.items.served');

    // Debug route to check recent orders
    Route::get('/debug/orders', function () {
        $orders = \App\Models\CustomerOrder::with(['table', 'employee', 'customerOrderItems.dish'])
            ->latest('ordered_at')
            ->take(5)
            ->get();

        return response()->json([
            'recent_orders' => $orders->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'table' => $order->table->table_name ?? 'N/A',
                    'customer_name' => $order->customer_name,
                    'waiter' => $order->employee ? $order->employee->firstname . ' ' . $order->employee->lastname : 'N/A',
                    'status' => $order->status,
                    'ordered_at' => $order->ordered_at->format('Y-m-d H:i:s'),
                    'total_items' => $order->customerOrderItems->count(),
                    'items' => $order->customerOrderItems->map(function ($item) {
                        return [
                            'dish' => $item->dish->dish_name ?? 'N/A',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'special_instructions' => $item->special_instructions ?? 'None'
                        ];
                    })
                ];
            })
        ]);
    })->name('debug.orders');
});

// Debug route to check employees
Route::get('/debug/employees', function () {
    $employees = \App\Models\Employee::with('role')->get();
    $roles = \App\Models\Role::all();

    return response()->json([
        'employees' => $employees->map(function ($emp) {
            return [
                'id' => $emp->employee_id,
                'email' => $emp->email,
                'status' => $emp->status,
                'role_name' => $emp->role ? $emp->role->role_name : 'No role',
                'role_id' => $emp->role_id,
                'user_id' => $emp->user_id,
            ];
        }),
        'roles' => $roles->pluck('role_name', 'id'),
    ]);
});

// Cashier-specific routes (restricted to cashiers only)
Route::middleware(['auth:cashier', 'role:cashier'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/successful-payments', [CashierController::class, 'successfulPayments'])->name('successful-payments');
    Route::get('/bills', [CashierController::class, 'bills'])->name('bills');
    Route::get('/api/orders', [App\Http\Controllers\OrderPollingController::class, 'cashierOrders'])->name('api.orders');
    Route::get('/bills/{orderId}', [CashierController::class, 'viewBill'])->name('bills.view');
    Route::get('/bills/{orderId}/print', [CashierController::class, 'printBill'])->name('bills.print');
    Route::post('/bills/{orderId}/discount', [CashierController::class, 'applyDiscount'])->name('bills.discount');
    Route::delete('/bills/{orderId}/discount', [CashierController::class, 'removeDiscount'])->name('bills.discount.remove');
    Route::post('/bills/{orderId}/void', [CashierController::class, 'voidOrder'])->name('bills.void');
    // PayPal routes must come before generic {orderId} routes
    Route::post('/payment/paypal', [CashierController::class, 'payWithPaypal'])->name('payment.paypal');
    Route::get('/payment/paypal/success', [CashierController::class, 'paypalSuccess'])->name('payment.paypal.success');
    Route::get('/payment/paypal/cancel', [CashierController::class, 'paypalCancel'])->name('payment.paypal.cancel');

    // Payment success page (for cash/card payments)
    Route::get('/payment-success', [CashierController::class, 'paymentSuccess'])->name('payment-success');

    // Generic payment routes with orderId parameter
    Route::get('/payment/{orderId}', [CashierController::class, 'processPayment'])->name('process-payment');
    Route::post('/payment/{orderId}', [CashierController::class, 'updatePaymentStatus'])->name('update-payment');
});

Route::middleware('admin.auth')->group(function () {
    // Admin Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // User Management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/admin/users/{id}/status', [AdminUserController::class, 'updateStatus'])->name('admin.users.status');
    Route::post('/admin/users/{id}/toggle-email', [AdminUserController::class, 'toggleEmailVerification'])->name('admin.users.toggle-email');
    Route::post('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    // Subscription Management
    Route::get('/admin/subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    Route::get('/admin/subscriptions/{id}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
    Route::post('/admin/subscriptions/{id}/extend', [AdminSubscriptionController::class, 'extend'])->name('admin.subscriptions.extend');
    Route::post('/admin/subscriptions/{id}/suspend', [AdminSubscriptionController::class, 'suspend'])->name('admin.subscriptions.suspend');
    Route::post('/admin/subscriptions/{id}/activate', [AdminSubscriptionController::class, 'activate'])->name('admin.subscriptions.activate');

    // System Settings
    Route::get('/admin/settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings/admins', [AdminSettingsController::class, 'createAdmin'])->name('admin.settings.admins.create');
    Route::delete('/admin/settings/admins/{id}', [AdminSettingsController::class, 'deleteAdmin'])->name('admin.settings.admins.delete');
    Route::post('/admin/settings/subscription-plans', [AdminSettingsController::class, 'createSubscriptionPlan'])->name('admin.settings.plans.create');
    Route::put('/admin/settings/subscription-plans/{id}', [AdminSettingsController::class, 'updateSubscriptionPlan'])->name('admin.settings.plans.update');
    Route::delete('/admin/settings/subscription-plans/{id}', [AdminSettingsController::class, 'deleteSubscriptionPlan'])->name('admin.settings.plans.delete');
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');

    // Regular employees routes (non-account employees)
    Route::resource('regular-employees', RegularEmployeeController::class);
    Route::post('/regular-employees/{regularEmployee}/toggle-status', [RegularEmployeeController::class, 'toggleStatus'])->name('regular-employees.toggle-status');
    Route::post('/regular-employees/{regularEmployee}/activate', [RegularEmployeeController::class, 'activate'])->name('regular-employees.activate');
    Route::post('/regular-employees/{regularEmployee}/deactivate', [RegularEmployeeController::class, 'deactivate'])->name('regular-employees.deactivate');
});

Route::get('/register/documents', [RegisteredUserController::class, 'showDocumentUpload'])->middleware('auth')->name('register.documents');
Route::post('/register/documents', [RegisteredUserController::class, 'store_doc'])->middleware('auth')->name('register.documents.store');

// Debug routes for registration testing
Route::get('/debug/registration', function () {
    return response()->json([
        'environment' => app()->environment(),
        'debug_mode' => config('app.debug'),
        'database_connection' => DB::connection()->getPdo() ? 'Connected' : 'Failed',
        'session_driver' => config('session.driver'),
        'auth_user' => Auth::user() ? [
            'id' => Auth::user()->id,
            'email' => Auth::user()->email,
            'restaurant_data' => Auth::user()->restaurantData,
        ] : null,
        'routes' => [
            'register' => route('register'),
            'register_documents' => route('register.documents'),
            'login' => route('login'),
        ],
        'latest_users' => DB::table('users')->latest()->limit(3)->get(),
        'latest_restaurants' => DB::table('restaurant_data')->latest()->limit(3)->get(),
    ]);
})->name('debug.registration');

// PayMongo Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/checkout', [App\Http\Controllers\PayMongoController::class, 'createCheckout'])->name('checkout');
    Route::post('/attach', [App\Http\Controllers\PayMongoController::class, 'attachPaymentMethod'])->name('attach');
    Route::get('/success', [App\Http\Controllers\PayMongoController::class, 'paymentSuccess'])->name('success');
    Route::get('/failed', [App\Http\Controllers\PayMongoController::class, 'paymentFailed'])->name('failed');
});

// Test registration with sample data
Route::get('/debug/test-registration', function () {
    $testData = [
        'first_name' => 'Test',
        'middle_name' => 'User',
        'last_name' => 'Demo',
        'date_of_birth' => '1990-01-01',
        'gender' => 'Male',
        'email' => 'test'.time().'@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'restaurant_name' => 'Test Restaurant '.time(),
        'address' => '123 Test Street, Test City, Test Country',
        'postal_code' => '12345',
        'contact_number' => '1234567890',
    ];

    return view('debug.test-registration', compact('testData'));
})->name('debug.test-registration');

// Debug route to test document page access
Route::get('/debug/documents', function () {
    return response()->json([
        'auth_check' => Auth::check(),
        'user' => Auth::user() ? [
            'id' => Auth::user()->id,
            'email' => Auth::user()->email,
            'restaurant_data' => Auth::user()->restaurantData,
        ] : null,
        'session_data' => [
            'session_id' => session()->getId(),
            'registration_success' => session('registration_success'),
            'all_session' => session()->all(),
        ],
        'routes' => [
            'documents_url' => route('register.documents'),
            'login_url' => route('login'),
            'register_url' => route('register'),
        ],
        'request_info' => [
            'url' => request()->url(),
            'method' => request()->method(),
            'headers' => request()->headers->all(),
            'ip' => request()->ip(),
        ],
    ]);
})->name('debug.documents');

// Debug middleware test
Route::get('/debug/middleware-test', function () {
    return 'Middleware test passed - you are authenticated';
})->middleware('auth')->name('debug.middleware-test');

// Debug route to view login logs
Route::get('/debug/login-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        // Get only the last 50 lines that contain LOGIN or AUTH
        $lines = explode("\n", $logs);
        $debugLines = array_filter($lines, function($line) {
            return strpos($line, '🔍') !== false || strpos($line, 'LOGIN') !== false || strpos($line, 'AUTH') !== false;
        });
        $recent = array_slice($debugLines, -50);

        return '<pre style="background: #1a1a1a; color: #00ff00; padding: 20px; font-family: monospace;">' .
               '<h2 style="color: #ffff00;">🔍 LOGIN DEBUG LOGS</h2>' .
               htmlspecialchars(implode("\n", $recent)) .
               '</pre>';
    }
    return 'No log file found';
})->name('debug.login-logs');

// Employee Login Test UI
Route::get('/debug/employee-test', function () {
    return Inertia::render('Debug/EmployeeTest');
})->name('debug.employee-test');

// API routes for auth status checking
Route::get('/api/auth-status/web', function () {
    return response()->json([
        'authenticated' => Auth::guard('web')->check(),
        'user' => Auth::guard('web')->user()
    ]);
});

Route::get('/api/auth-status/employee', function () {
    return response()->json([
        'authenticated' => Auth::guard('employee')->check(),
        'user' => Auth::guard('employee')->user()
    ]);
});

// Test route to simulate registration success and redirect
Route::get('/debug/test-redirect', function () {
    $testUser = DB::table('users')->latest()->first();

    if ($testUser) {
        Auth::loginUsingId($testUser->id);

        return response()->json([
            'message' => 'User logged in for testing',
            'user' => $testUser,
            'auth_check' => Auth::check(),
            'redirect_url' => route('register.documents'),
            'can_access_documents' => true,
        ]);
    }

    return response()->json([
        'message' => 'No users found in database',
        'users_count' => DB::table('users')->count(),
    ]);
})->name('debug.test-redirect');

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/product', [ProductController::class, 'index'])->name('product.index');
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/kitchen', [KitchenController::class, 'Index'])->name('kitchen.index');
});

// Kitchen Staff Routes
Route::middleware(['auth:kitchen', 'role:kitchen'])->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::get('/dashboard', [KitchenController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/orders', [App\Http\Controllers\OrderPollingController::class, 'kitchenOrders'])->name('api.orders');
    Route::post('/preparation-orders/{id}/start', [KitchenController::class, 'startPreparationOrder'])->name('preparation-orders.start');
    Route::post('/preparation-orders/{orderId}/items/{itemId}/start', [KitchenController::class, 'startPreparationItem'])->name('preparation-items.start');
    Route::post('/preparation-orders/{orderId}/items/{itemId}/complete', [KitchenController::class, 'completePreparationItem'])->name('preparation-items.complete');
    Route::post('/orders/{orderId}/status', [KitchenController::class, 'updateOrderStatus'])->name('orders.update-status');
});

// Damage and Spoilage Management Routes (accessible by kitchen staff via modal)
Route::middleware(['auth:kitchen', 'role:kitchen'])->group(function () {
    Route::post('/damage-spoilage', [DamageSpoilageController::class, 'store'])->name('damage-spoilage.store');
});

// Reports and Analytics Routes (accessible by restaurant owners/managers)
Route::middleware(['auth', 'verified', 'check.subscription'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('/sales', [ReportsController::class, 'sales'])->name('sales');
    Route::get('/inventory', [ReportsController::class, 'inventory'])->name('inventory');
    Route::get('/purchase-orders', [ReportsController::class, 'purchaseOrders'])->name('purchase-orders');
    Route::get('/wastage', [ReportsController::class, 'wastage'])->name('wastage');
    Route::get('/comprehensive', [ReportsController::class, 'comprehensiveReport'])->name('comprehensive');
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/pos/customer-order', function () {
        return Inertia::render('POS/CustomerOrder');
    })->name('pos.customer-order');

    // Table Management Routes
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::resource('tables', App\Http\Controllers\TableController::class);
        Route::post('/tables/{table}/status', [App\Http\Controllers\TableController::class, 'updateStatus'])->name('tables.update-status');

        // Table Reservation Routes
        Route::get('/reservations/available-slots', [App\Http\Controllers\TableReservationController::class, 'availableSlots'])->name('reservations.available-slots');
        Route::resource('reservations', App\Http\Controllers\TableReservationController::class);
        Route::post('/reservations/{reservation}/confirm', [App\Http\Controllers\TableReservationController::class, 'confirm'])->name('reservations.confirm');
        Route::post('/reservations/{reservation}/cancel', [App\Http\Controllers\TableReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::post('/reservations/{reservation}/arrived', [App\Http\Controllers\TableReservationController::class, 'markArrived'])->name('reservations.arrived');
        Route::post('/reservations/{reservation}/seat', [App\Http\Controllers\TableReservationController::class, 'seat'])->name('reservations.seat');
        Route::post('/reservations/{reservation}/complete', [App\Http\Controllers\TableReservationController::class, 'complete'])->name('reservations.complete');
        Route::post('/reservations/{reservation}/no-show', [App\Http\Controllers\TableReservationController::class, 'noShow'])->name('reservations.no-show');
        Route::post('/reservations/update-expired', [App\Http\Controllers\TableReservationController::class, 'updateExpiredReservations'])->name('reservations.update-expired');
    });
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/supplier', [SuppliersController::class, 'Index'])->name('supplier.index');
    Route::get('/reorder', [SuppliersController::class, 'Reorder'])->name('supplier.reorder');
    Route::get('/orderedRequested', [SuppliersController::class, 'OrderedRequested'])->name('supplier.OrderedRequested');

});

// Document approval routes (should be admin-only)
Route::middleware('admin.auth')->group(function () {
    Route::get('/request', [DocumentController::class, 'index'])->name('request');
    Route::post('/admin/accounts/{restaurant}/approve', [DocumentController::class, 'approve'])->name('admin.accounts.approve');
    Route::post('/admin/accounts/{restaurant}/reject', [DocumentController::class, 'reject'])->name('admin.accounts.reject');
});

Route::get('/subscriptions', [SubscriptionpackageController::class, 'index'])->name('subscriptions.index');
Route::post('/subscriptions/create', [SubscriptionpackageController::class, 'create'])->name('subscriptions.create');
Route::post('/subscriptions/free-trial', [SubscriptionpackageController::class, 'createFreeTrial'])->name('subscriptions.free-trial');
Route::get('/subscriptions/success', [SubscriptionpackageController::class, 'success'])->name('subscriptions.success');
Route::get('/subscriptions/cancel', [SubscriptionpackageController::class, 'cancel'])->name('subscriptions.cancel');

// inventory and stock in routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/stock-list', [StockInController::class, 'index'])->name('stock-in.index');
    Route::get('/inventory/stock-in', [StockInController::class, 'create'])->name('stock-in.create');
    Route::post('/inventory/stock-in', [StockInController::class, 'store'])->name('stock-in.store');
    Route::post('/ingredients/store-quick', [StockInController::class, 'storeIngredient'])->name('ingredients.store.quick');
    Route::get('/api/purchase-orders/{purchaseOrderId}/details', [StockInController::class, 'getPurchaseOrderDetails'])->name('purchase-orders.api-details');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/account/update', [AccountUpdateController::class, 'show'])->name('account.update.show');
    Route::post('/account/update', [AccountUpdateController::class, 'update'])->name('account.update');
    Route::get('/api/account/user', [AccountUpdateController::class, 'fetchUser']);

    // Image upload API
    Route::post('/api/images/upload', [ImageUploadController::class, 'upload'])->name('api.images.upload');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscriptions/renew', [RenewSubscriptionController::class, 'show'])->name('subscriptions.renew');
    Route::post('/subscriptions/renew', [RenewSubscriptionController::class, 'renew'])->name('subscriptions.renew.process');
    Route::get('/subscriptions/renew/success', [RenewSubscriptionController::class, 'renewSuccess'])->name('subscriptions.renew.success');
    Route::get('/subscriptions/renew/cancel', [RenewSubscriptionController::class, 'renewCancel'])->name('subscriptions.renew.cancel');
});

// User Management Routes
Route::middleware(['auth', 'verified'])->prefix('user-management')->group(function () {
    Route::get('/subscriptions', [UsersubscriptionController::class, 'index'])->name('user-management.subscriptions');
    Route::get('/subscriptions/data', [UsersubscriptionController::class, 'getSubscriptionData'])->name('user-management.subscriptions.data');
    Route::get('/subscriptions/renew', [UsersubscriptionController::class, 'showRenewal'])->name('user-management.subscriptions.renew');
    Route::get('/subscriptions/upgrade', [UsersubscriptionController::class, 'showUpgrade'])->name('user-management.subscriptions.upgrade');
    Route::post('/subscriptions/process', [UsersubscriptionController::class, 'processRenewalOrUpgrade'])->name('user-management.subscriptions.process');
    Route::get('/subscriptions/success', [UsersubscriptionController::class, 'paymentSuccess'])->name('user-management.subscriptions.success');
    Route::get('/subscriptions/cancel', [UsersubscriptionController::class, 'paymentCancel'])->name('user-management.subscriptions.cancel');
});

// Supplier Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{id}/details', [SupplierController::class, 'getSupplierDetails'])->name('suppliers.details');
    Route::post('/suppliers/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    Route::post('/suppliers/{id}/send-invitation', [SupplierController::class, 'sendInvitation'])->name('suppliers.send-invitation');
});

// Purchase Order Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/purchase-orders/pending-approvals', [PurchaseOrderController::class, 'pendingApprovals'])->name('purchase-orders.pending-approvals');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'processReceive'])->name('purchase-orders.process-receive');
});

// Supplier Bills Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('bills', SupplierBillController::class);
    Route::post('/bills/from-purchase-order/{purchaseOrderId}', [SupplierBillController::class, 'createFromPurchaseOrder'])->name('bills.from-purchase-order');
    Route::get('/bills/{id}/download', [SupplierBillController::class, 'downloadAttachment'])->name('bills.download');
    Route::post('/bills/mark-overdue', [SupplierBillController::class, 'markOverdue'])->name('bills.mark-overdue');

    // PayPal Payment Routes
    Route::post('/bills/{bill}/paypal', [SupplierBillController::class, 'payWithPaypal'])->name('bills.paypal.pay');
    Route::get('/bills/{bill}/paypal/success', [SupplierBillController::class, 'paypalSuccess'])->name('bills.paypal.success');
    Route::get('/bills/{bill}/paypal/cancel', [SupplierBillController::class, 'paypalCancel'])->name('bills.paypal.cancel');
     Route::post('/payments/cash', [SupplierPaymentController::class, 'handleCashPayment'])->name('payments.cash');

    // Quick Payment Route (for Bills/Show page)
    Route::post('/bills/{bill}/quick-payment', [SupplierBillController::class, 'quickPayment'])->name('bills.quick-payment');

    // PDF Download Route
    Route::get('/bills/{bill}/pdf', [SupplierBillController::class, 'downloadPdf'])->name('bills.download-pdf');
});

// Supplier Payments Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('payments', SupplierPaymentController::class);
    Route::get('/payments/create/{billId}', [SupplierPaymentController::class, 'create'])->name('payments.create-for-bill');
    Route::post('/payments/{id}/cancel', [SupplierPaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/api/bills/{id}/details', [SupplierPaymentController::class, 'getBillDetails'])->name('bills.api-details');
});

// Menu Preparation Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/menu-preparation', [MenuPreparationController::class, 'index'])->name('menu-preparation.index');
    Route::get('/menu-preparation/create', [MenuPreparationController::class, 'create'])->name('menu-preparation.create');
    Route::post('/menu-preparation', [MenuPreparationController::class, 'store'])->name('menu-preparation.store');
    Route::get('/menu-preparation/{id}', [MenuPreparationController::class, 'show'])->name('menu-preparation.show');
    Route::get('/menu-preparation/{id}/duplicate', [MenuPreparationController::class, 'duplicate'])->name('menu-preparation.duplicate');
    Route::delete('/menu-preparation/{id}', [MenuPreparationController::class, 'destroy'])->name('menu-preparation.destroy');

    // Preparation order actions
    Route::post('/menu-preparation/{id}/start', [MenuPreparationController::class, 'startPreparation'])->name('menu-preparation.start');
    Route::post('/menu-preparation/{id}/complete', [MenuPreparationController::class, 'completePreparation'])->name('menu-preparation.complete');

    // Individual item actions
    Route::post('/menu-preparation/{orderId}/items/{itemId}/start', [MenuPreparationController::class, 'startItem'])->name('menu-preparation.items.start');
    Route::post('/menu-preparation/{orderId}/items/{itemId}/complete', [MenuPreparationController::class, 'completeItem'])->name('menu-preparation.items.complete');

    // API routes for real-time inventory checking
    Route::get('/api/menu-preparation/{id}/inventory-status', [MenuPreparationController::class, 'getInventoryStatus'])->name('menu-preparation.inventory-status');

    // Purchase order creation from shortages
    Route::post('/menu-preparation/{id}/create-purchase-orders', [MenuPreparationController::class, 'createPurchaseOrderFromShortages'])->name('menu-preparation.create-purchase-orders');
});


// Menu Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menu/{dish}', [MenuController::class, 'show'])->name('menu.show');
    Route::get('/menu/{dish}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::put('/menu/{dish}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{dish}', [MenuController::class, 'destroy'])->name('menu.destroy');
    Route::post('/menu/{dish}/status', [MenuController::class, 'updateStatus'])->name('menu.update-status');
    Route::get('/menu-analytics', [MenuController::class, 'analytics'])->name('menu.analytics');

    // Menu Categories
    Route::get('/menu-categories', [MenuCategoryController::class, 'index'])->name('menu-categories.index');
    Route::post('/menu-categories', [MenuCategoryController::class, 'store'])->name('menu-categories.store');
    Route::put('/menu-categories/{category}', [MenuCategoryController::class, 'update'])->name('menu-categories.update');
    Route::delete('/menu-categories/{category}', [MenuCategoryController::class, 'destroy'])->name('menu-categories.destroy');
    Route::post('/menu-categories/{category}/status', [MenuCategoryController::class, 'updateStatus'])->name('menu-categories.update-status');
    Route::post('/menu-categories/reorder', [MenuCategoryController::class, 'reorder'])->name('menu-categories.reorder');

    // Menu Planning Routes
    Route::get('/menu-planning', [MenuPlanController::class, 'index'])->name('menu-planning.index');
    Route::get('/menu-planning/create', [MenuPlanController::class, 'create'])->name('menu-planning.create');
    Route::post('/menu-planning', [MenuPlanController::class, 'store'])->name('menu-planning.store');
    Route::get('/menu-planning/{menuPlan}', [MenuPlanController::class, 'show'])->name('menu-planning.show');
    Route::get('/menu-planning/{menuPlan}/edit', [MenuPlanController::class, 'edit'])->name('menu-planning.edit');
    Route::put('/menu-planning/{menuPlan}', [MenuPlanController::class, 'update'])->name('menu-planning.update');
    Route::delete('/menu-planning/{menuPlan}', [MenuPlanController::class, 'destroy'])->name('menu-planning.destroy');
    Route::post('/menu-planning/{menuPlan}/toggle-active', [MenuPlanController::class, 'toggleActive'])->name('menu-planning.toggle-active');
    Route::get('/api/menu-planning/active', [MenuPlanController::class, 'getActiveMenuPlan'])->name('menu-planning.active');
    // Mobile view route - accessible by restaurant owners
    Route::get('/menu-planning/{menuPlan}/mobile-view/{date}', [MenuPlanController::class, 'mobileView'])
        ->name('menu-planning.mobile-view');
});

// Customer Menu Routes (public access)
Route::get('/customer-menu', [App\Http\Controllers\CustomerMenuController::class, 'index'])->name('customer-menu.index');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/inventory.php';
require __DIR__.'/billing.php';

// Supplier Routes
Route::prefix('supplier')->name('supplier.')->group(function () {
    require __DIR__.'/supplier.php';
});
// Debug route to check authentication status
Route::get('/debug/auth-check', function() {
    return response()->json([
        'waiter_auth' => Auth::guard('waiter')->check(),
        'waiter_user' => Auth::guard('waiter')->user(),
        'web_auth' => Auth::guard('web')->check(),
        'web_user' => Auth::guard('web')->user(),
        'session_data' => session()->all(),
        'cookies' => request()->cookies->all(),
    ]);
})->middleware('web');
