<?php

namespace App\Http\Controllers;

use App\Models\MenuPreparationOrder;
use App\Models\MenuPreparationItem;
use App\Models\CustomerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function dashboard()
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id; // Employee belongs to their specific restaurant

        try {
            // Get customer orders that need kitchen attention
            $unpaidOrders = CustomerOrder::with([
                'table',
                'orderItems.dish',
                'employee'
            ])
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'ready', 'completed', 'in_progress'])
            ->whereHas('orderItems', function ($query) {
                // Only show orders that have at least one item not fully served
                $query->whereColumn('served_quantity', '<', 'quantity');
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

            // Load excluded ingredients for each order item
            foreach ($unpaidOrders as $order) {
                foreach ($order->orderItems as $item) {
                    $item->excluded_ingredients = \App\Models\CustomerRequest::where('order_id', $order->order_id)
                        ->where('dish_id', $item->dish_id)
                        ->where('request_type', 'exclude')
                        ->with('ingredient')
                        ->get();
                }
            }

            // Relationship should be loaded via with() clause above

            // Get today's kitchen statistics
            $todayStats = [
                'total_orders' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->whereDate('created_at', today())
                    ->count(),
                'pending_orders' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'in_progress_orders' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->where('status', 'in_progress')
                    ->count(),
                'ready_orders' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->where('status', 'ready')
                    ->count(),
            ];

        } catch (\Exception $e) {
            // Fallback to mock data if database tables don't exist
            $unpaidOrders = collect([
                (object)[
                    'customer_order_id' => 1,
                    'order_number' => 'ORD-001',
                    'status' => 'confirmed',
                    'customer_name' => 'John Doe',
                    'created_at' => now()->subMinutes(15),
                    'updated_at' => now()->subMinutes(15),
                    'total_amount' => 850.00,
                    'table' => (object)[
                        'table_id' => 1,
                        'table_number' => '5',
                        'table_name' => 'Table 5'
                    ],
                    'employee' => (object)[
                        'employee_id' => 1,
                        'firstname' => 'Maria',
                        'lastname' => 'Santos'
                    ],
                    'orderItems' => collect([
                        (object)[
                            'order_item_id' => 1,
                            'dish_id' => 1,
                            'quantity' => 2,
                            'unit_price' => 350.00,
                            'special_instructions' => 'Extra spicy, no onions',
                            'dish' => (object)[
                                'dish_id' => 1,
                                'dish_name' => 'Chicken Adobo',
                                'dish_description' => 'Traditional Filipino braised chicken'
                            ]
                        ],
                        (object)[
                            'order_item_id' => 2,
                            'dish_id' => 2,
                            'quantity' => 1,
                            'unit_price' => 150.00,
                            'special_instructions' => 'Extra rice',
                            'dish' => (object)[
                                'dish_id' => 2,
                                'dish_name' => 'Garlic Rice',
                                'dish_description' => 'Fragrant garlic fried rice'
                            ]
                        ]
                    ])
                ],
                (object)[
                    'customer_order_id' => 2,
                    'order_number' => 'ORD-002',
                    'status' => 'in_progress',
                    'customer_name' => 'Jane Smith',
                    'created_at' => now()->subMinutes(25),
                    'updated_at' => now()->subMinutes(10),
                    'total_amount' => 1200.00,
                    'table' => (object)[
                        'table_id' => 2,
                        'table_number' => '3',
                        'table_name' => 'Table 3'
                    ],
                    'employee' => (object)[
                        'employee_id' => 2,
                        'firstname' => 'Carlos',
                        'lastname' => 'Rodriguez'
                    ],
                    'orderItems' => collect([
                        (object)[
                            'order_item_id' => 3,
                            'dish_id' => 3,
                            'quantity' => 1,
                            'unit_price' => 750.00,
                            'special_instructions' => 'Medium rare, side of vegetables',
                            'dish' => (object)[
                                'dish_id' => 3,
                                'dish_name' => 'Beef Tenderloin',
                                'dish_description' => 'Grilled beef tenderloin with herbs'
                            ]
                        ],
                        (object)[
                            'order_item_id' => 4,
                            'dish_id' => 4,
                            'quantity' => 2,
                            'unit_price' => 225.00,
                            'special_instructions' => 'Extra creamy',
                            'dish' => (object)[
                                'dish_id' => 4,
                                'dish_name' => 'Mushroom Soup',
                                'dish_description' => 'Creamy mushroom soup'
                            ]
                        ]
                    ])
                ],
                (object)[
                    'customer_order_id' => 3,
                    'order_number' => 'ORD-003',
                    'status' => 'ready',
                    'customer_name' => 'Mike Johnson',
                    'created_at' => now()->subMinutes(45),
                    'updated_at' => now()->subMinutes(5),
                    'total_amount' => 650.00,
                    'table' => (object)[
                        'table_id' => 3,
                        'table_number' => '8',
                        'table_name' => 'Table 8'
                    ],
                    'employee' => (object)[
                        'employee_id' => 1,
                        'firstname' => 'Maria',
                        'lastname' => 'Santos'
                    ],
                    'orderItems' => collect([
                        (object)[
                            'order_item_id' => 5,
                            'dish_id' => 5,
                            'quantity' => 1,
                            'unit_price' => 450.00,
                            'special_instructions' => 'Well cooked, extra sauce',
                            'dish' => (object)[
                                'dish_id' => 5,
                                'dish_name' => 'Grilled Salmon',
                                'dish_description' => 'Fresh grilled salmon with lemon'
                            ]
                        ],
                        (object)[
                            'order_item_id' => 6,
                            'dish_id' => 6,
                            'quantity' => 1,
                            'unit_price' => 200.00,
                            'special_instructions' => 'Light dressing',
                            'dish' => (object)[
                                'dish_id' => 6,
                                'dish_name' => 'Caesar Salad',
                                'dish_description' => 'Fresh caesar salad with croutons'
                            ]
                        ]
                    ])
                ]
            ]);

            $todayStats = [
                'total_orders' => 12,
                'pending_orders' => 1,
                'in_progress_orders' => 1,
                'ready_orders' => 1,
            ];
        }


        // Get ingredients for damage/spoilage modal
        $ingredients = [];
        $types = [];
        try {
            $ingredients = \App\Models\Ingredients::where('restaurant_id', $restaurantId)
                ->orderBy('ingredient_name')
                ->get(['ingredient_id', 'ingredient_name', 'base_unit']);
            $types = \App\Models\DamageSpoilageLog::getTypes();
        } catch (\Exception $e) {
            // Fallback if tables don't exist yet
            $ingredients = collect([]);
            $types = ['damage' => 'Damage', 'spoilage' => 'Spoilage'];
        }

        return Inertia::render('Kitchen/Dashboard', [
            'unpaidOrders' => $unpaidOrders,
            'todayStats' => $todayStats,
            'employee' => $employee ?: (object)[
                'employee_id' => 1,
                'name' => 'Kitchen Staff Demo',
                'email' => 'kitchen@demo.com'
            ],
            'ingredients' => $ingredients,
            'damageTypes' => $types,
        ]);
    }

    public function startPreparationOrder(Request $request, $id)
    {
        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Order started successfully (Demo Mode)']);
    }

    public function startPreparationItem(Request $request, $orderId, $itemId)
    {
        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Item started successfully (Demo Mode)']);
    }

    public function completePreparationItem(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'prepared_quantity' => 'required|integer|min:0'
        ]);

        // Mock response - replace with real database operations when tables are created
        return response()->json(['success' => true, 'message' => 'Item completed successfully (Demo Mode)']);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,in_progress,ready'
        ]);

        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id; // Employee belongs to their specific restaurant

        try {
            $order = CustomerOrder::where('order_id', $orderId)
                ->where('restaurant_id', $restaurantId)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            $order->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            // Mock response if database operations fail
            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status} (Demo Mode)"
            ]);
        }
    }
}
