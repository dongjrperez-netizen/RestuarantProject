<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Employee;
use App\Models\MenuPlan;
use App\Models\MenuPreparationItem;
use App\Models\MenuPreparationOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MenuPreparationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        // Temporary workaround for table creation issue
        try {
            $preparationOrders = MenuPreparationOrder::with(['items.dish', 'menuPlan', 'creator', 'preparedBy'])
                ->forRestaurant($restaurantId)
                ->orderBy('preparation_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } catch (\Exception $e) {
            // If table doesn't exist, show empty state with setup message
            $preparationOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                15,
                1,
                ['path' => request()->url()]
            );

            return Inertia::render('MenuPreparation/Index', [
                'preparationOrders' => $preparationOrders,
                'setup_required' => true,
                'setup_message' => 'Database tables need to be created. Please run: php artisan migrate',
            ]);
        }

        return Inertia::render('MenuPreparation/Index', [
            'preparationOrders' => $preparationOrders,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dishes = Dish::where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->with(['dishIngredients.ingredient'])
            ->orderBy('dish_name')
            ->get();

        $menuPlans = MenuPlan::forRestaurant($restaurantId)
            ->active()
            ->with(['menuPlanDishes.dish'])
            ->orderBy('start_date', 'desc')
            ->get();

        $employees = Employee::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('MenuPreparation/Create', [
            'dishes' => $dishes,
            'menuPlans' => $menuPlans,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $request->validate([
            'menu_plan_id' => 'nullable|exists:menu_plans,menu_plan_id',
            'preparation_type' => ['required', Rule::in(['daily', 'weekly', 'special_event'])],
            'preparation_date' => 'required|date',
            'meal_type' => ['nullable', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
            'notes' => 'nullable|string',
            'prepared_by' => 'nullable|exists:employees,employee_id',
            'dishes' => 'required|array|min:1',
            'dishes.*.dish_id' => 'required|exists:dishes,dish_id',
            'dishes.*.planned_quantity' => 'required|integer|min:1',
            'dishes.*.notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $preparationOrder = MenuPreparationOrder::create([
                'restaurant_id' => $restaurantId,
                'menu_plan_id' => $request->menu_plan_id,
                'preparation_type' => $request->preparation_type,
                'preparation_date' => $request->preparation_date,
                'meal_type' => $request->meal_type,
                'notes' => $request->notes,
                'created_by' => $user->id,
                'prepared_by' => $request->prepared_by,
            ]);

            $preparationOrder->update([
                'order_reference' => $preparationOrder->generateOrderReference(),
            ]);

            foreach ($request->dishes as $dishData) {
                MenuPreparationItem::create([
                    'preparation_order_id' => $preparationOrder->preparation_order_id,
                    'dish_id' => $dishData['dish_id'],
                    'planned_quantity' => $dishData['planned_quantity'],
                    'notes' => $dishData['notes'] ?? null,
                ]);
            }

            $inventoryCheck = $preparationOrder->checkInventoryAvailability();

            DB::commit();

            if ($inventoryCheck['has_shortages']) {
                return redirect()->route('menu-preparation.show', $preparationOrder->preparation_order_id)
                    ->with('warning', 'Preparation order created but there are ingredient shortages. Please check inventory.')
                    ->with('inventory_shortages', $inventoryCheck['shortages']);
            }

            $this->sendNotifications($preparationOrder);

            return redirect()->route('menu-preparation.show', $preparationOrder->preparation_order_id)
                ->with('success', 'Menu preparation order created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create preparation order. Please try again.']);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::with([
            'items.dish.dishIngredients.ingredient',
            'menuPlan',
            'creator',
            'preparedBy',
            'restaurant',
        ])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        $inventoryCheck = $preparationOrder->checkInventoryAvailability();

        return Inertia::render('MenuPreparation/Show', [
            'preparationOrder' => $preparationOrder,
            'inventoryCheck' => $inventoryCheck,
        ]);
    }

    public function startPreparation($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($id);

        if (! $preparationOrder->startPreparation()) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot start preparation. Order must be in pending status.']);
        }

        $this->sendNotifications($preparationOrder, 'started');

        return redirect()->back()
            ->with('success', 'Preparation started successfully.');
    }

    public function completePreparation($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($id);

        if (! $preparationOrder->completePreparation()) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot complete preparation. All items must be completed first.']);
        }

        $this->sendNotifications($preparationOrder, 'completed');

        return redirect()->back()
            ->with('success', 'Preparation completed successfully.');
    }

    public function startItem($orderId, $itemId)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($orderId);
        $preparationItem = $preparationOrder->items()->findOrFail($itemId);

        if (! $preparationItem->startPreparation()) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot start item preparation. Item must be in pending status.']);
        }

        return redirect()->back()
            ->with('success', 'Item preparation started successfully.');
    }

    public function completeItem(Request $request, $orderId, $itemId)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $request->validate([
            'prepared_quantity' => 'required|integer|min:0',
        ]);

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($orderId);
        $preparationItem = $preparationOrder->items()->findOrFail($itemId);

        $inventoryCheck = $preparationItem->checkIngredientAvailability();
        if ($inventoryCheck['has_shortages']) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot complete item. Insufficient ingredients in inventory.'])
                ->with('inventory_shortages', $inventoryCheck['shortages']);
        }

        if (! $preparationItem->completePreparation($request->prepared_quantity)) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot complete item preparation. Item must be in progress.']);
        }

        return redirect()->back()
            ->with('success', 'Item preparation completed successfully. Inventory has been updated.');
    }

    public function getInventoryStatus($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($id);
        $inventoryCheck = $preparationOrder->checkInventoryAvailability();

        return response()->json($inventoryCheck);
    }

    public function duplicate($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $originalOrder = MenuPreparationOrder::with(['items'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        return Inertia::render('MenuPreparation/Create', [
            'duplicateFrom' => $originalOrder,
            'dishes' => Dish::where('restaurant_id', $restaurantId)
                ->where('is_available', true)
                ->with(['dishIngredients.ingredient'])
                ->orderBy('dish_name')
                ->get(),
            'menuPlans' => MenuPlan::forRestaurant($restaurantId)
                ->active()
                ->with(['menuPlanDishes.dish'])
                ->orderBy('start_date', 'desc')
                ->get(),
            'employees' => Employee::where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($id);

        if ($preparationOrder->status === 'completed') {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete completed preparation orders.']);
        }

        $preparationOrder->delete();

        return redirect()->route('menu-preparation.index')
            ->with('success', 'Preparation order deleted successfully.');
    }

    private function sendNotifications(MenuPreparationOrder $preparationOrder, $event = 'created')
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $kitchenStaff = Employee::where('restaurant_id', $restaurantId)
            ->where('department', 'kitchen')
            ->where('is_active', true)
            ->get();

        $stockroomStaff = Employee::where('restaurant_id', $restaurantId)
            ->where('department', 'stockroom')
            ->where('is_active', true)
            ->get();

        $notificationData = [
            'preparation_order_id' => $preparationOrder->preparation_order_id,
            'order_reference' => $preparationOrder->order_reference,
            'preparation_date' => $preparationOrder->preparation_date->format('Y-m-d'),
            'meal_type' => $preparationOrder->meal_type,
            'event' => $event,
            'total_dishes' => $preparationOrder->items->count(),
        ];

        // Send notifications to kitchen staff
        foreach ($kitchenStaff as $staff) {
            if ($staff->user) {
                $staff->user->notify(new \App\Notifications\MenuPreparationNotification($notificationData, 'kitchen'));
            }
        }

        // Send notifications to stockroom staff for inventory updates
        if ($event === 'completed') {
            foreach ($stockroomStaff as $staff) {
                if ($staff->user) {
                    $staff->user->notify(new \App\Notifications\MenuPreparationNotification($notificationData, 'stockroom'));
                }
            }
        }
    }

    public function createPurchaseOrderFromShortages($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $preparationOrder = MenuPreparationOrder::forRestaurant($restaurantId)->findOrFail($id);
        $inventoryCheck = $preparationOrder->checkInventoryAvailability();

        if (! $inventoryCheck['has_shortages']) {
            return redirect()->back()->with('info', 'No shortages detected. Purchase order not needed.');
        }

        try {
            DB::beginTransaction();

            // Group shortages by supplier
            $shortagesBySupplier = $this->groupShortagesBySupplier($inventoryCheck['shortages'], $restaurantId);

            $createdOrders = [];

            foreach ($shortagesBySupplier as $supplierId => $ingredients) {
                $supplier = Supplier::find($supplierId);
                if (! $supplier) {
                    continue;
                }

                // Create purchase order
                $purchaseOrder = PurchaseOrder::create([
                    'restaurant_id' => $restaurantId,
                    'supplier_id' => $supplierId,
                    'status' => 'draft',
                    'order_date' => now(),
                    'expected_delivery_date' => now()->addDays($supplier->average_lead_time ?? 3),
                    'notes' => "Auto-generated from Menu Preparation Order: {$preparationOrder->order_reference}",
                    'created_by_user_id' => $user->id,
                ]);

                $subtotal = 0;

                // Add items to purchase order
                foreach ($ingredients as $ingredientData) {
                    $ingredient = $ingredientData['ingredient'];
                    $shortageQuantity = $ingredientData['shortage'];

                    // Get supplier pricing info
                    $supplierPivot = $ingredient->suppliers()
                        ->where('suppliers.supplier_id', $supplierId)
                        ->first();

                    if ($supplierPivot) {
                        $packageQuantity = $supplierPivot->pivot->package_contents_quantity;
                        $packagePrice = $supplierPivot->pivot->package_price;
                        $minimumOrder = $supplierPivot->pivot->minimum_order_quantity ?? 1;

                        // Calculate packages needed
                        $packagesNeeded = max(
                            ceil($shortageQuantity / $packageQuantity),
                            $minimumOrder
                        );

                        $itemTotal = $packagesNeeded * $packagePrice;
                        $subtotal += $itemTotal;

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $purchaseOrder->purchase_order_id,
                            'ingredient_id' => $ingredient->ingredient_id,
                            'quantity_ordered' => $packagesNeeded,
                            'unit_of_measure' => $supplierPivot->pivot->package_unit,
                            'unit_price' => $packagePrice,
                            'total_price' => $itemTotal,
                            'notes' => "Shortage: {$shortageQuantity} {$ingredient->base_unit}",
                        ]);
                    }
                }

                // Update totals
                $taxAmount = $subtotal * 0.1; // 10% tax (adjust as needed)
                $totalAmount = $subtotal + $taxAmount;

                $purchaseOrder->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);

                $createdOrders[] = $purchaseOrder;
            }

            DB::commit();

            $message = count($createdOrders) === 1
                ? 'Purchase order created successfully.'
                : count($createdOrders).' purchase orders created successfully.';

            return redirect()->back()->with('success', $message)
                ->with('purchase_orders', $createdOrders->pluck('purchase_order_id'));

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Failed to create purchase orders. Please try again.']);
        }
    }

    private function groupShortagesBySupplier(array $shortages, int $restaurantId): array
    {
        $groupedShortages = [];

        foreach ($shortages as $shortage) {
            // Find the ingredient
            $ingredient = \App\Models\Ingredients::where('ingredient_name', $shortage['ingredient'])
                ->where('restaurant_id', $restaurantId)
                ->with('suppliers')
                ->first();

            if (! $ingredient || $ingredient->suppliers->isEmpty()) {
                continue;
            }

            // Find the best supplier (lowest price or preferred)
            $bestSupplier = $ingredient->suppliers()
                ->where('ingredient_suppliers.is_active', true)
                ->orderBy('ingredient_suppliers.package_price', 'asc')
                ->first();

            if ($bestSupplier) {
                $supplierId = $bestSupplier->supplier_id;

                if (! isset($groupedShortages[$supplierId])) {
                    $groupedShortages[$supplierId] = [];
                }

                $groupedShortages[$supplierId][] = [
                    'ingredient' => $ingredient,
                    'shortage' => $shortage['shortage'],
                    'dish' => $shortage['dish'],
                ];
            }
        }

        return $groupedShortages;
    }
}
