<?php

namespace App\Http\Controllers;

use App\Models\Ingredients;
use App\Models\IngredientSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderApproved;
use App\Notifications\PurchaseOrderSubmitted;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        // Get filter parameters with defaults
        $search = $request->input('search', '');
        // "default" means: draft/pending/sent/confirmed + any supplier-delivered orders awaiting owner receive
        $statusFilter = $request->input('status', 'default');

        // Build query
        $query = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('restaurant_id', $restaurantId);

        // Apply search filter (PO number or supplier name)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($subQ) use ($search) {
                        $subQ->where('supplier_name', 'like', "%{$search}%");
                    })
                    ->orWhere(function ($notesQ) use ($search) {
                        // Search in notes for manual receive supplier names
                        $notesQ->whereNull('supplier_id')
                            ->where('notes', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'default') {
                // Default: show early workflow states (draft/pending/sent/confirmed)
                // plus any supplier-delivered orders awaiting owner receive
                $query->where(function ($q) {
                    $q->whereIn('status', ['draft', 'pending', 'sent', 'confirmed'])
                        ->orWhere(function ($q2) {
                            $q2->whereIn('status', ['partially_delivered', 'delivered'])
                                ->whereNull('received_by');
                        });
                });
            } else {
                $statuses = explode(',', $statusFilter);
                $query->whereIn('status', $statuses);
            }
        }

        $purchaseOrders = $query->orderBy('created_at', 'desc')->get();

        // Get pending approvals count for managers/owners
        $pendingApprovalsCount = 0;
        if ($user->role_id <= 2) { // Owner or Manager
            $pendingApprovalsCount = PurchaseOrder::where('restaurant_id', $restaurantId)
                ->where('status', 'pending')
                ->count();
        }

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'userRole' => $user->role_id,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function pendingApprovals()
    {
        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        // Only allow managers and owners to access this
        if ($user->role_id > 2) {
            return redirect()->back()->with('error', 'Access denied. Only managers can approve purchase orders.');
        }

        $restaurantId = $user->restaurantData->id;

        $pendingOrders = PurchaseOrder::with(['supplier', 'items.ingredient', 'createdBy'])
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('PurchaseOrders/PendingApprovals', [
            'pendingOrders' => $pendingOrders,
        ]);
    }

    public function manualReceive()
    {
        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        // Get all ingredients for the restaurant
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit']);

        return Inertia::render('PurchaseOrders/ManualReceive', [
            'ingredients' => $ingredients,
        ]);
    }

    public function storeManualReceive(Request $request)
    {
        \Log::info('=== MANUAL RECEIVE STARTED ===');
        \Log::info('Request data:', $request->all());

        $user = auth()->user();
        if (! $user->restaurantData) {
            \Log::error('No restaurant data found for user:', ['user_id' => $user->id]);
            return redirect()->back()->with('error', 'No restaurant data found.');
        }

        $restaurantId = $user->restaurantData->id;
        \Log::info('Restaurant ID:', ['restaurant_id' => $restaurantId]);

        try {
            $validated = $request->validate([
                'supplier_name' => 'required|string|max:255',
                'supplier_contact' => 'nullable|string|max:50',
                'supplier_email' => 'nullable|email|max:255',
                'reference_number' => 'nullable|string|max:100',
                'delivery_date' => 'required|date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
                'items.*.ingredient_name' => 'required_if:items.*.is_new,true|string|max:150',
                'items.*.base_unit' => 'required_if:items.*.is_new,true|string|max:50',
                'items.*.packages' => 'required|numeric|min:0.01',
                'items.*.contents_quantity' => 'required|numeric|min:0.01',
                'items.*.package_price' => 'required|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:500',
                'items.*.is_new' => 'required|boolean',
            ]);

            \Log::info('Validation passed:', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', [
                'errors' => $e->errors(),
                'messages' => $e->getMessage()
            ]);
            throw $e;
        }

        try {
            DB::beginTransaction();
            \Log::info('Transaction started');

            // Create a purchase order record for tracking (mark as manual/external supplier)
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return $item['packages'] * $item['package_price'];
            });

            \Log::info('Total amount calculated:', ['total' => $totalAmount]);

            // Generate PO number
            $lastPO = PurchaseOrder::where('restaurant_id', $restaurantId)
                ->orderBy('purchase_order_id', 'desc')
                ->first();
            $poNumber = 'PO-MANUAL-' . str_pad(($lastPO ? $lastPO->purchase_order_id + 1 : 1), 6, '0', STR_PAD_LEFT);

            // Create purchase order (without supplier_id since it's external)
            $purchaseOrderData = [
                'restaurant_id' => $restaurantId,
                'supplier_id' => null, // No supplier ID for manual receives
                'po_number' => $poNumber,
                'status' => 'delivered', // Mark as delivered immediately
                'order_date' => $validated['delivery_date'],
                'expected_delivery_date' => $validated['delivery_date'],
                'actual_delivery_date' => $validated['delivery_date'],
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'notes' => 'Manual Receive - Supplier: ' . $validated['supplier_name'] .
                          ($validated['supplier_contact'] ? ' | Contact: ' . $validated['supplier_contact'] : '') .
                          ($validated['reference_number'] ? ' | Ref: ' . $validated['reference_number'] : '') .
                          ($validated['notes'] ? ' | Notes: ' . $validated['notes'] : ''),
                'received_by' => $user->name ?? 'System',
                'created_by_user_id' => $user->id,
            ];

            \Log::info('Creating purchase order:', $purchaseOrderData);
            // If a manager/supervisor employee is performing manual receive, capture them as creator
            if (\Auth::guard('employee')->check()) {
                /** @var \App\Models\Employee $employee */
                $employee = \Auth::guard('employee')->user();
                $employee->loadMissing('role');
                $roleName = strtolower($employee->role->role_name ?? '');
                if (in_array($roleName, ['manager', 'supervisor'], true)) {
                    $purchaseOrderData['created_by_employee_id'] = $employee->employee_id;
                }
            }
 
            $purchaseOrder = PurchaseOrder::create($purchaseOrderData);
            \Log::info('Purchase order created:', ['po_id' => $purchaseOrder->purchase_order_id]);

            // Create purchase order items and update inventory
            foreach ($validated['items'] as $index => $item) {
                \Log::info("Processing item {$index}:", $item);
                $ingredientId = $item['ingredient_id'];

                // If it's a new ingredient, create it first
                if ($item['is_new']) {
                    \Log::info("Item is new, checking for existing ingredient");
                    // Check if ingredient already exists by name
                    $existingIngredient = Ingredients::where('restaurant_id', $restaurantId)
                        ->whereRaw('LOWER(ingredient_name) = ?', [strtolower($item['ingredient_name'])])
                        ->first();

                    if ($existingIngredient) {
                        \Log::info("Existing ingredient found:", ['id' => $existingIngredient->ingredient_id]);
                        $ingredientId = $existingIngredient->ingredient_id;
                    } else {
                        \Log::info("Creating new ingredient");
                        // Create new ingredient (reorder_level will be set in Inventory management)
                        // Calculate cost per unit from package price and contents quantity
                        $costPerUnit = $item['contents_quantity'] > 0
                            ? $item['package_price'] / $item['contents_quantity']
                            : 0;

                        $newIngredient = Ingredients::create([
                            'restaurant_id' => $restaurantId,
                            'ingredient_name' => $item['ingredient_name'],
                            'base_unit' => $item['base_unit'],
                            'cost_per_unit' => $costPerUnit,
                            'current_stock' => 0, // Will be updated below
                            'packages' => 0, // Will be updated below
                            'reorder_level' => 0, // Default to 0, can be set later in Inventory
                        ]);
                        $ingredientId = $newIngredient->ingredient_id;
                        \Log::info("New ingredient created:", ['id' => $ingredientId]);
                    }
                } else {
                    \Log::info("Using existing ingredient:", ['id' => $ingredientId]);
                }

                // Calculate total stock to add (packages × contents_quantity)
                $totalStock = $item['packages'] * $item['contents_quantity'];
                \Log::info("Total stock calculated:", ['total_stock' => $totalStock]);

                // Calculate total price (packages × package_price)
                $totalPrice = $item['packages'] * $item['package_price'];

                $poItemData = [
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'ingredient_id' => $ingredientId,
                    'ordered_quantity' => $item['packages'], // Store number of packages
                    'received_quantity' => $item['packages'], // Mark as fully received
                    'unit_price' => $item['package_price'], // Store package price
                    'total_price' => $totalPrice, // Required field
                    'unit_of_measure' => 'packages', // Unit is always packages
                    'condition_notes' => $item['notes'] ?? null,
                ];

                \Log::info("Creating PO item:", $poItemData);
                PurchaseOrderItem::create($poItemData);
                \Log::info("PO item created");

                // Update ingredient stock
                $ingredient = Ingredients::find($ingredientId);
                if ($ingredient) {
                    \Log::info("Updating ingredient stock:", [
                        'ingredient_id' => $ingredientId,
                        'old_stock' => $ingredient->current_stock,
                        'old_packages' => $ingredient->packages,
                        'adding_stock' => $totalStock,
                        'adding_packages' => $item['packages']
                    ]);

                    $oldStock = $ingredient->current_stock;
                    $ingredient->current_stock += $totalStock; // Add total stock (packages × contents)
                    $ingredient->packages += $item['packages']; // Add number of packages

                    // Update cost per unit (weighted average)
                    // Cost per unit = package_price / contents_quantity
                    $newCostPerUnit = $item['contents_quantity'] > 0
                        ? $item['package_price'] / $item['contents_quantity']
                        : 0;

                    if ($oldStock > 0) {
                        $totalValue = ($ingredient->cost_per_unit * $oldStock) +
                                     ($newCostPerUnit * $totalStock);
                        $ingredient->cost_per_unit = $totalValue / $ingredient->current_stock;
                    } else {
                        $ingredient->cost_per_unit = $newCostPerUnit;
                    }

                    $ingredient->save();
                    \Log::info("Ingredient updated:", [
                        'new_stock' => $ingredient->current_stock,
                        'new_packages' => $ingredient->packages,
                        'new_cost_per_unit' => $ingredient->cost_per_unit
                    ]);
                } else {
                    \Log::error("Ingredient not found:", ['ingredient_id' => $ingredientId]);
                }
            }

            // 🚀 AUTO-GENERATE BILL for manual receive
            $billMessage = '';
            try {
                $billResult = $this->billingService->generateBillFromPurchaseOrder(
                    $purchaseOrder->purchase_order_id,
                    [
                        'bill_date' => $validated['delivery_date'],
                        'auto_calculate_due_date' => true,
                        'notes' => 'Auto-generated from manual receive - ' . $validated['supplier_name'] .
                                  ($validated['reference_number'] ? ' | Ref: ' . $validated['reference_number'] : ''),
                    ]
                );

                if ($billResult['success']) {
                    $billMessage = " Bill {$billResult['bill_number']} has been automatically generated.";
                    \Log::info("Auto-generated bill {$billResult['bill_number']} for manual receive PO {$purchaseOrder->po_number}");
                }
            } catch (\Exception $billException) {
                \Log::error("Failed to auto-generate bill for manual receive PO {$purchaseOrder->po_number}: ".$billException->getMessage());
                $billMessage = ' Note: Bill generation failed, please create manually.';
            }

            DB::commit();
            \Log::info('=== MANUAL RECEIVE COMPLETED SUCCESSFULLY ===');

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Manual receive completed successfully. Inventory has been updated.'.$billMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('=== MANUAL RECEIVE FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to process manual receive: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
'supplier',
            'items.ingredient',
            'bill.payments',
            'createdBy',
            'createdByEmployee',
            'approvedBy',
        ])->findOrFail($id);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        // Get all ingredients from the restaurant's inventory
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit', 'cost_per_unit', 'current_stock']);

        // Get all suppliers for the restaurant
        $suppliers = Supplier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('supplier_name')
            ->get(['supplier_id', 'supplier_name']);

        return Inertia::render('PurchaseOrders/Create', [
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('=== PURCHASE ORDER STORE STARTED ===', [
            'user_id' => optional(auth()->user())->id,
            'request_data' => $request->all(),
        ]);

        // Basic validation only; do not enforce a supplier-side "maximum" here.
        // This avoids hidden failures if there is any mismatch between frontend limits
        // and the ingredient_suppliers.minimum_order_quantity value in the database.
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,supplier_id',
                'items' => 'required|array|min:1',
                'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
                'items.*.ordered_quantity' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],
                'items.*.unit_price' => 'nullable|numeric|min:0', // Price is entered when receiving, not when creating
                'items.*.unit_of_measure' => 'required|string|max:50',
                'items.*.notes' => 'nullable|string',
            ]);

            \Log::info('PURCHASE ORDER VALIDATION PASSED', [
                'supplier_id' => $validated['supplier_id'] ?? null,
                'items_count' => count($validated['items'] ?? []),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('PURCHASE ORDER VALIDATION FAILED', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e; // Let Inertia / validation handling behave normally
        }

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * ($item['unit_price'] ?? 0);
            });

            $user = auth()->user();
            if (! $user->restaurantData) {
                \Log::error('PURCHASE ORDER STORE FAILED: no restaurantData for user', [
                    'user_id' => $user?->id,
                ]);

                return redirect()->back()->with('error', 'No restaurant data found.');
            }

            // If a manager/supervisor employee is creating this PO, capture their employee_id
            $createdByEmployeeId = null;
            if (\Auth::guard('employee')->check()) {
                /** @var \App\Models\Employee $employee */
                $employee = \Auth::guard('employee')->user();
                $employee->loadMissing('role');
                $roleName = strtolower($employee->role->role_name ?? '');
                if (in_array($roleName, ['manager', 'supervisor'], true)) {
                    $createdByEmployeeId = $employee->employee_id;
                }
            }

            \Log::info('CREATING PURCHASE ORDER', [
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $validated['supplier_id'],
                'subtotal' => $subtotal,
                'items' => $validated['items'],
                'created_by_user_id' => $user->id,
                'created_by_employee_id' => $createdByEmployeeId,
            ]);

            $purchaseOrder = PurchaseOrder::create([
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $validated['supplier_id'],
                'status' => 'draft',
                'order_date' => now(),
                'expected_delivery_date' => null,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => null,
                'delivery_instructions' => null,
                'created_by_user_id' => $user->id,
                'created_by_employee_id' => $createdByEmployeeId,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $unitPrice = $item['unit_price'] ?? 0;
                $totalPrice = $item['ordered_quantity'] * $unitPrice;

                $itemData = [
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'ingredient_id' => $item['ingredient_id'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'notes' => $item['notes'],
                ];

                \Log::info('CREATING PURCHASE ORDER ITEM', [
                    'index' => $index,
                    'item' => $itemData,
                ]);

                PurchaseOrderItem::create($itemData);
            }

            DB::commit();

            \Log::info('=== PURCHASE ORDER STORE COMPLETED ===', [
                'purchase_order_id' => $purchaseOrder->purchase_order_id,
            ]);

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('PURCHASE ORDER STORE FAILED WITH EXCEPTION', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating purchase order: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with(['items.ingredient', 'supplier'])
            ->findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'Cannot edit purchase order with status: '.$purchaseOrder->status);
        }

        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        // Get all ingredients from the restaurant's inventory
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit', 'cost_per_unit', 'current_stock']);

        // Get all suppliers for the restaurant
        $suppliers = Supplier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('supplier_name')
            ->get(['supplier_id', 'supplier_name']);

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder,
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Cannot update purchase order with status: '.$purchaseOrder->status);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * $item['unit_price'];
            });

            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'expected_delivery_date' => null,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'notes' => null,
                'delivery_instructions' => null,
            ]);

            $purchaseOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                $totalPrice = $item['ordered_quantity'] * $item['unit_price'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'ingredient_id' => $item['ingredient_id'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'notes' => $item['notes'],
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Purchase Order updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating purchase order: '.$e->getMessage());
        }
    }

    public function approve($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Can only approve purchase orders with pending status.');
        }

        $purchaseOrder->update([
            'status' => 'sent',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Send notification to supplier about the new order
        $this->notifySupplierOfNewOrder($purchaseOrder);

        return redirect()->back()
            ->with('success', 'Purchase Order approved and sent to supplier.');
    }

    public function submit($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Can only create draft purchase orders.');
        }

        // Directly create the PO and set to confirmed status
        $purchaseOrder->update([
            'status' => 'confirmed',
        ]);

        return redirect()->back()
            ->with('success', 'Purchase Order created successfully.');
    }

    public function cancel($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (!in_array($purchaseOrder->status, ['draft', 'pending', 'sent'])) {
            return redirect()->back()
                ->with('error', 'Cannot cancel purchase order once confirmed by supplier. Current status: '.$purchaseOrder->status);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->back()
            ->with('success', 'Purchase Order cancelled successfully.');
    }

    public function receive($id)
    {
        $purchaseOrder = PurchaseOrder::with('items.ingredient', 'supplier')->findOrFail($id);

        if (! in_array($purchaseOrder->status, ['confirmed', 'partially_delivered', 'delivered'])) {
            return redirect()->back()
                ->with('error', 'Can only receive confirmed or delivered purchase orders.');
        }

        // Prevent re-receiving already processed orders
        if ($purchaseOrder->status === 'delivered' && $purchaseOrder->received_by) {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'This order has already been received and processed.');
        }

        return Inertia::render('PurchaseOrders/Receive', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function processReceive(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with(['items.ingredient'])->findOrFail($id);

        // Prevent processing already received orders
        if ($purchaseOrder->status === 'delivered' && $purchaseOrder->received_by) {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'This order has already been received and processed.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,purchase_order_item_id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'received_by' => 'required|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $allItemsFullyReceived = true;

            foreach ($validated['items'] as $itemData) {
                $item = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                $newReceivedQuantity = $item->received_quantity + $itemData['received_quantity'];

                $item->update([
                    'received_quantity' => $newReceivedQuantity,
                    'unit_price' => $itemData['unit_price'],
                ]);

                if ($newReceivedQuantity < $item->ordered_quantity) {
                    $allItemsFullyReceived = false;
                }

                if ($itemData['received_quantity'] > 0) {
                    $ingredient = Ingredients::find($item->ingredient_id);
                    if ($ingredient) {
                        $oldStock = $ingredient->current_stock;
                        $oldCost = $ingredient->cost_per_unit;

                        // Directly add received quantity to current stock (per-unit system)
                        $ingredient->current_stock += $itemData['received_quantity'];

                        // Update cost per unit with the price entered during receiving
                        $ingredient->cost_per_unit = $itemData['unit_price'];

                        $ingredient->save();

                        \Log::info("Stock updated for ingredient {$ingredient->ingredient_name}: Stock: {$oldStock} -> {$ingredient->current_stock} (+{$itemData['received_quantity']} {$ingredient->base_unit}), Cost per unit: {$oldCost} -> {$ingredient->cost_per_unit}");
                    } else {
                        \Log::error('Ingredient not found with ID: '.$item->ingredient_id);
                    }
                }
            }

            $newStatus = $allItemsFullyReceived ? 'delivered' : 'partially_delivered';

            $purchaseOrder->update([
                'status' => $newStatus,
                'actual_delivery_date' => now(),
                'received_by' => $validated['received_by'],
            ]);

            // 🚀 AUTO-GENERATE BILL when PO is fully delivered
            $billMessage = '';
            if ($newStatus === 'delivered') {
                try {
                    $billResult = $this->billingService->generateBillFromPurchaseOrder(
                        $purchaseOrder->purchase_order_id,
                        [
                            'bill_date' => now()->toDateString(),
                            'auto_calculate_due_date' => true,
                            'notes' => 'Auto-generated from delivered purchase order',
                        ]
                    );

                    if ($billResult['success']) {
                        $billMessage = " Bill {$billResult['bill_number']} has been automatically generated.";
                        \Log::info("Auto-generated bill {$billResult['bill_number']} for PO {$purchaseOrder->po_number}");
                    }
                } catch (\Exception $billException) {
                    \Log::error("Failed to auto-generate bill for PO {$purchaseOrder->po_number}: ".$billException->getMessage());
                    $billMessage = ' Note: Bill generation failed, please create manually.';
                }
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Delivery received and stock updated successfully.'.$billMessage);

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing delivery: '.$e->getMessage());
        }
    }

    /**
     * Calculate expected delivery date based on supplier lead times
     */
    private function calculateExpectedDeliveryDate($supplierId, $items)
    {
        $maxLeadTime = 0;

        foreach ($items as $item) {
            $offering = IngredientSupplier::where('supplier_id', $supplierId)
                ->where('ingredient_id', $item['ingredient_id'])
                ->where('is_active', true)
                ->first();

            if ($offering && $offering->lead_time_days > $maxLeadTime) {
                $maxLeadTime = $offering->lead_time_days;
            }
        }

        if ($maxLeadTime === 0) {
            return null;
        }

        // Calculate delivery date: today + max lead time
        $deliveryDate = now()->addDays(ceil($maxLeadTime));

        return $deliveryDate->format('Y-m-d');
    }

    /**
     * Send notification to restaurant managers for purchase order approval
     */
    private function notifyManagersForApproval(PurchaseOrder $purchaseOrder)
    {
        // Load required relationships
        $purchaseOrder->load(['supplier', 'createdBy', 'restaurant']);

        // Find restaurant managers/owners (role_id 1 or 2) for this restaurant
        $managers = User::where('role_id', '<=', 2) // Assuming 1 = Owner, 2 = Manager
            ->whereHas('restaurantData', function ($query) use ($purchaseOrder) {
                $query->where('id', $purchaseOrder->restaurant_id);
            })
            ->get();

        // Send notification to all managers
        foreach ($managers as $manager) {
            $manager->notify(new PurchaseOrderSubmitted($purchaseOrder));
        }
    }

    /**
     * Send notification to supplier about new purchase order
     */
    private function notifySupplierOfNewOrder(PurchaseOrder $purchaseOrder)
    {
        // Load required relationships
        $purchaseOrder->load(['supplier', 'restaurant']);

        // Send notification to supplier
        $purchaseOrder->supplier->notify(new PurchaseOrderApproved($purchaseOrder));
    }
}