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

    public function index()
    {
        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('PurchaseOrderController@index returning count', ['count' => $purchaseOrders->count(), 'user_id' => $user->id ?? null, 'restaurant_id' => $restaurantId, 'request_url' => request()->fullUrl()]);

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

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'supplier',
            'items.ingredient',
            'bill.payments',
            'createdBy',
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

        // Get all suppliers (without ingredient relationships to avoid ingredient_suppliers table)
        $suppliers = Supplier::where('is_active', true)
            ->where('restaurant_id', $restaurantId)
            ->orderBy('supplier_name')
            ->get();

        // Get all ingredients from ingredient library for manual PO entry
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'unit' => $ingredient->base_unit,
                    'current_stock' => $ingredient->current_stock,
                    'reorder_level' => $ingredient->reorder_level,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                ];
            });

        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        // Diagnostic logging: record incoming request details to help debug why
        // the client may not be receiving the expected Inertia payload.
        try {
            $hdrs = [];
            foreach ($request->headers->all() as $k => $v) {
                $hdrs[$k] = is_array($v) ? $v : [$v];
            }

            // Mask the X-CSRF-TOKEN value to avoid leaking token into logs
            if (isset($hdrs['x-csrf-token'])) {
                $hdrs['x-csrf-token'] = ['[MASKED]'];
            }

            \Log::info('PurchaseOrderController@store called', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_authenticated' => auth()->check() ? auth()->id() : null,
                'headers' => $hdrs,
                'cookies' => $request->cookie(),
                'payload_keys' => array_keys($request->all()),
            ]);
        } catch (\Exception $e) {
            // swallow any logging error - diagnostics should not break the request
            \Log::warning('Failed to log diagnostic for PurchaseOrderController@store: '.$e->getMessage());
        }
        // Custom validation: must have either supplier_id OR supplier_name
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:100',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:50',
        ]);

        // Ensure at least one supplier identifier is provided
        if (!$request->filled('supplier_id') && !$request->filled('supplier_name')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['supplier' => 'Please either select an existing supplier or enter a supplier name.']);
        }

        $validated = $request->validate([
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * $item['unit_price'];
            });

            $user = auth()->user();
            if (! $user->restaurantData) {
                return redirect()->back()->with('error', 'No restaurant data found.');
            }

            // Calculate expected delivery date if not provided and supplier_id exists
            $expectedDeliveryDate = $validated['expected_delivery_date'];
            if (empty($expectedDeliveryDate) && $request->filled('supplier_id')) {
                $expectedDeliveryDate = $this->calculateExpectedDeliveryDate($request->input('supplier_id'), $validated['items']);
            }

            // Auto-save supplier information for future reuse
            $supplierIdToUse = $request->input('supplier_id');
            if (! $request->filled('supplier_id') && ($request->filled('supplier_email') || $request->filled('supplier_name'))) {
                // Try to find existing supplier by email first, then by name
                $supplierQuery = Supplier::where('restaurant_id', $user->restaurantData->id);

                if ($request->filled('supplier_email')) {
                    $supplierQuery->where('email', $request->input('supplier_email'));
                } elseif ($request->filled('supplier_name')) {
                    $supplierQuery->where('supplier_name', $request->input('supplier_name'));
                }

                $existingSupplier = $supplierQuery->first();

                if ($existingSupplier) {
                    $supplierIdToUse = $existingSupplier->supplier_id;
                    \Log::info('Found existing supplier for PO', ['supplier_id' => $supplierIdToUse]);
                } else {
                    // Create new supplier for future reuse (password is nullable for email-only suppliers)
                    $newSupplier = Supplier::create([
                        'restaurant_id' => $user->restaurantData->id,
                        'supplier_name' => $request->input('supplier_name') ?? 'Unnamed Supplier',
                        'contact_number' => $request->input('supplier_phone') ?? $request->input('supplier_contact'),
                        'email' => $request->input('supplier_email'),
                        'is_active' => true,
                        'payment_terms' => 'COD',
                        'credit_limit' => 0.00, // Default to 0 for new suppliers
                        'password' => null, // Email-only supplier, no login access
                    ]);

                    $supplierIdToUse = $newSupplier->supplier_id;
                    \Log::info('Auto-created supplier for future reuse', ['supplier_id' => $supplierIdToUse, 'name' => $newSupplier->supplier_name]);
                }
            }

            $purchaseOrder = PurchaseOrder::create([
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $supplierIdToUse,
                'supplier_name' => $request->input('supplier_name'),
                'supplier_contact' => $request->input('supplier_contact'),
                'supplier_email' => $request->input('supplier_email'),
                'supplier_phone' => $request->input('supplier_phone'),
                'status' => 'draft',
                'order_date' => now(),
                'expected_delivery_date' => $expectedDeliveryDate,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => $validated['notes'],
                'delivery_instructions' => $validated['delivery_instructions'],
                'created_by_user_id' => auth()->id(),
            ]);

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

                // If the ingredient doesn't have a cost recorded, and the user provided a unit_price,
                // save it to the ingredients table so future POs autofill the price.
                try {
                    $ingredientModel = \App\Models\Ingredients::find($item['ingredient_id']);
                    if ($ingredientModel && (empty($ingredientModel->cost_per_unit) || $ingredientModel->cost_per_unit == 0) && !empty($item['unit_price'])) {
                        $ingredientModel->cost_per_unit = $item['unit_price'];
                        $ingredientModel->save();
                        \Log::info('Updated ingredient cost_per_unit from PO', ['ingredient_id' => $ingredientModel->ingredient_id, 'new_cost' => $ingredientModel->cost_per_unit]);
                    }
                } catch (\Exception $ex) {
                    \Log::warning('Failed to update ingredient cost_per_unit: '.$ex->getMessage(), ['ingredient_id' => $item['ingredient_id']]);
                }
            }

            DB::commit();

            \Log::info('PurchaseOrderController@store created PO', ['purchase_order_id' => $purchaseOrder->purchase_order_id]);

            // Success message with PO number
            $successMessage = "Purchase Order {$purchaseOrder->po_number} created successfully! Total: ₱" . number_format($purchaseOrder->total_amount, 2);

            \Log::info('PurchaseOrderController@store returning purchase order data', [
                'purchase_order_id' => $purchaseOrder->purchase_order_id,
                'route' => route('purchase-orders.show', $purchaseOrder->purchase_order_id),
                'success_message' => $successMessage,
            ]);

            // Load relationships needed for the summary modal
            $purchaseOrder->load(['supplier', 'items.ingredient']);

            \Log::info('PurchaseOrder data being returned', [
                'po_id' => $purchaseOrder->purchase_order_id,
                'po_number' => $purchaseOrder->po_number,
                'has_supplier' => $purchaseOrder->supplier ? 'yes' : 'no',
                'items_count' => $purchaseOrder->items->count(),
            ]);

            // For Inertia requests, return the purchase order data so the modal can display
            if (request()->header('X-Inertia')) {
                // Get suppliers and ingredients for the Create page
                $restaurantId = $user->restaurantData->id;
                $suppliers = Supplier::where('is_active', true)
                    ->where('restaurant_id', $restaurantId)
                    ->orderBy('supplier_name')
                    ->get();

                $ingredients = Ingredients::where('restaurant_id', $restaurantId)
                    ->orderBy('ingredient_name')
                    ->get()
                    ->map(function ($ingredient) {
                        return [
                            'ingredient_id' => $ingredient->ingredient_id,
                            'ingredient_name' => $ingredient->ingredient_name,
                            'unit' => $ingredient->base_unit,
                            'current_stock' => $ingredient->current_stock,
                            'reorder_level' => $ingredient->reorder_level,
                            'cost_per_unit' => $ingredient->cost_per_unit,
                        ];
                    });

                \Log::info('Returning Inertia response with purchaseOrder', [
                    'page' => 'PurchaseOrders/Create',
                    'has_purchaseOrder' => isset($purchaseOrder),
                ]);

                return Inertia::render('PurchaseOrders/Create', [
                    'suppliers' => $suppliers,
                    'ingredients' => $ingredients,
                    'purchaseOrder' => $purchaseOrder,
                ])->with('success', $successMessage);
            }

            // For non-Inertia requests (shouldn't happen but fallback)
            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', $successMessage);


        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('PurchaseOrderController@store exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
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

        if (! in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'Cannot edit purchase order with status: '.$purchaseOrder->status);
        }

        $user = auth()->user();
        if (! $user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }

        $restaurantId = $user->restaurantData->id;

        // Get all suppliers (without ingredient relationships to avoid ingredient_suppliers table)
        $suppliers = Supplier::where('is_active', true)
            ->where('restaurant_id', $restaurantId)
            ->orderBy('supplier_name')
            ->get();

        // Get all ingredients from ingredient library for manual PO entry
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'unit' => $ingredient->base_unit,
                    'current_stock' => $ingredient->current_stock,
                    'reorder_level' => $ingredient->reorder_level,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                ];
            });

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => $suppliers,
            'ingredients' => $ingredients,
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (! in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->back()
                ->with('error', 'Cannot update purchase order with status: '.$purchaseOrder->status);
        }

        // Custom validation: must have either supplier_id OR supplier_name
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:100',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:50',
        ]);

        // Ensure at least one supplier identifier is provided
        if (!$request->filled('supplier_id') && !$request->filled('supplier_name')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['supplier' => 'Please either select an existing supplier or enter a supplier name.']);
        }

        $validated = $request->validate([
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * $item['unit_price'];
            });

            // Calculate expected delivery date if not provided and supplier_id exists
            $expectedDeliveryDate = $validated['expected_delivery_date'];
            if (empty($expectedDeliveryDate) && $request->filled('supplier_id')) {
                $expectedDeliveryDate = $this->calculateExpectedDeliveryDate($request->input('supplier_id'), $validated['items']);
            }

            // Try to find existing supplier if manual entry was used
            $supplierIdToUse = $request->input('supplier_id');
            if (! $request->filled('supplier_id') && ($request->filled('supplier_email') || $request->filled('supplier_name'))) {
                $supplierQuery = Supplier::where('restaurant_id', $purchaseOrder->restaurant_id);

                if ($request->filled('supplier_email')) {
                    $supplierQuery->where('email', $request->input('supplier_email'));
                } elseif ($request->filled('supplier_name')) {
                    $supplierQuery->where('supplier_name', $request->input('supplier_name'));
                }

                $existingSupplier = $supplierQuery->first();

                if ($existingSupplier) {
                    $supplierIdToUse = $existingSupplier->supplier_id;
                }
            }

            $purchaseOrder->update([
                'supplier_id' => $supplierIdToUse,
                'supplier_name' => $request->input('supplier_name'),
                'supplier_contact' => $request->input('supplier_contact'),
                'supplier_email' => $request->input('supplier_email'),
                'supplier_phone' => $request->input('supplier_phone'),
                'expected_delivery_date' => $expectedDeliveryDate,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'notes' => $validated['notes'],
                'delivery_instructions' => $validated['delivery_instructions'],
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

        return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
            ->with('success', 'Purchase Order approved and sent to supplier.');
    }

    public function submit($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Can only submit draft purchase orders.');
        }

        $purchaseOrder->update(['status' => 'pending']);

        // Send notification to restaurant owner/managers for approval
        $this->notifyManagersForApproval($purchaseOrder);

        return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
            ->with('success', 'Purchase Order submitted for approval.');
    }

    public function cancel($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (in_array($purchaseOrder->status, ['delivered', 'cancelled'])) {
            return redirect()->back()
                ->with('error', 'Cannot cancel purchase order with status: '.$purchaseOrder->status);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->back()
            ->with('success', 'Purchase Order cancelled successfully.');
    }

    public function receive($id)
    {
        $purchaseOrder = PurchaseOrder::with('items.ingredient', 'supplier')->findOrFail($id);

        if (! in_array($purchaseOrder->status, ['confirmed', 'partially_delivered'])) {
            return redirect()->back()
                ->with('error', 'Can only receive confirmed purchase orders.');
        }

        return Inertia::render('PurchaseOrders/Receive', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function processReceive(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with(['items.ingredient'])->findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,purchase_order_item_id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.quality_rating' => 'nullable|in:excellent,good,fair,poor',
            'items.*.condition_notes' => 'nullable|string|max:500',
            'items.*.has_discrepancy' => 'boolean',
            'items.*.discrepancy_reason' => 'required_if:items.*.has_discrepancy,true|nullable|string|max:500',
            'actual_delivery_date' => 'required|date',
            'delivery_condition' => 'required|in:excellent,good,fair,poor',
            'received_by' => 'required|string|max:100',
            'general_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $allItemsFullyReceived = true;

            foreach ($validated['items'] as $itemData) {
                $item = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                $newReceivedQuantity = $item->received_quantity + $itemData['received_quantity'];

                $item->update([
                    'received_quantity' => $newReceivedQuantity,
                    'quality_rating' => $itemData['quality_rating'] ?? null,
                    'condition_notes' => $itemData['condition_notes'] ?? null,
                    'has_discrepancy' => $itemData['has_discrepancy'] ?? false,
                    'discrepancy_reason' => $itemData['discrepancy_reason'] ?? null,
                ]);

                if ($newReceivedQuantity < $item->ordered_quantity) {
                    $allItemsFullyReceived = false;
                }

                if ($itemData['received_quantity'] > 0) {
                    $ingredient = Ingredients::find($item->ingredient_id);
                    if ($ingredient) {
                        $oldStock = $ingredient->current_stock;
                        $oldPackages = $ingredient->packages;

                        // Get the package quantity for this supplier
                        $packageQuantity = $ingredient->getPackageQuantityForSupplier($purchaseOrder->supplier_id);

                        if ($packageQuantity) {
                            // Add received quantity to packages
                            $ingredient->packages += $itemData['received_quantity'];

                            // Calculate total stock increase (received packages × package contents)
                            $stockIncrease = $itemData['received_quantity'] * $packageQuantity;
                            $ingredient->current_stock += $stockIncrease;

                            // Update cost_per_unit based on the package price
                            // unit_price is the price per package, packageQuantity is units per package
                            // Therefore: cost_per_unit = price_per_package / units_per_package
                            if ($packageQuantity > 0) {
                                $ingredient->cost_per_unit = $item->unit_price / $packageQuantity;
                            }

                            $ingredient->save();

                            \Log::info("Stock updated for ingredient {$ingredient->ingredient_name}: Packages: {$oldPackages} -> {$ingredient->packages} (+{$itemData['received_quantity']}), Stock: {$oldStock} -> {$ingredient->current_stock} (+{$stockIncrease}), Cost per unit: {$ingredient->cost_per_unit}");
                        } else {
                            \Log::error("Package quantity not found for ingredient {$ingredient->ingredient_name} with supplier {$purchaseOrder->supplier_id}");
                        }
                    } else {
                        \Log::error('Ingredient not found with ID: '.$item->ingredient_id);
                    }
                }
            }

            $newStatus = $allItemsFullyReceived ? 'delivered' : 'partially_delivered';

            $purchaseOrder->update([
                'status' => $newStatus,
                'actual_delivery_date' => $validated['actual_delivery_date'],
                'delivery_condition' => $validated['delivery_condition'],
                'received_by' => $validated['received_by'],
                'receiving_notes' => $validated['general_notes'],
            ]);

            // 🚀 AUTO-GENERATE BILL when PO is fully delivered
            $billMessage = '';
            if ($newStatus === 'delivered') {
                try {
                    $billResult = $this->billingService->generateBillFromPurchaseOrder(
                        $purchaseOrder->purchase_order_id,
                        [
                            'bill_date' => $validated['actual_delivery_date'],
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
        $purchaseOrder->load(['supplier', 'restaurant', 'items.ingredient']);

        // Send supplier an email with signed confirm/reject links when we have an email address
        try {
            if ($purchaseOrder->supplier && $purchaseOrder->supplier->email) {
                $purchaseOrder->supplier->notify(new \App\Notifications\SupplierPurchaseOrderAction($purchaseOrder));
                return;
            }

            // If supplier relation not present but we have supplier_email on PO, send mail directly
            if (! empty($purchaseOrder->supplier_email)) {
                // Create signed confirm/reject links for direct email
                $confirmUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'supplier.purchase-orders.respond',
                    now()->addDays(7),
                    ['id' => $purchaseOrder->purchase_order_id, 'action' => 'confirm']
                );

                $rejectUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'supplier.purchase-orders.respond',
                    now()->addDays(7),
                    ['id' => $purchaseOrder->purchase_order_id, 'action' => 'reject']
                );

                // Use the same nice email template
                \Mail::send('emails.supplier_purchase_order_action', [
                    'purchaseOrder' => $purchaseOrder,
                    'confirmUrl' => $confirmUrl,
                    'rejectUrl' => $rejectUrl,
                    'notifiableName' => $purchaseOrder->supplier_name ?? 'Supplier',
                ], function ($message) use ($purchaseOrder) {
                    $message->to($purchaseOrder->supplier_email)
                        ->subject('Purchase Order – Action Required: ' . $purchaseOrder->po_number);
                });

                \Log::info('Sent PO notification to non-registered supplier email', [
                    'po_id' => $purchaseOrder->purchase_order_id,
                    'supplier_email' => $purchaseOrder->supplier_email,
                ]);

                return;
            }

            \Log::info('No supplier contact found for PO '.$purchaseOrder->purchase_order_id);
        } catch (\Exception $e) {
            \Log::error('Failed to notify supplier for PO '.$purchaseOrder->purchase_order_id.': '.$e->getMessage());
        }
    }
}