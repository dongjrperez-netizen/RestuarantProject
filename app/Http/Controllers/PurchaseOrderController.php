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

        // Get suppliers that have ingredient offerings
        $suppliers = Supplier::where('is_active', true)
            ->whereHas('ingredients')
            ->with(['ingredients'])
            ->orderBy('supplier_name')
            ->get();

        // Get all ingredient offerings (supplier inventory) - not restaurant inventory
        $supplierOfferings = IngredientSupplier::with(['ingredient', 'supplier'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->groupBy('supplier_id');

        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'supplierOfferings' => $supplierOfferings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    $itemIndex = explode('.', $attribute)[1];
                    $ingredientId = $request->input("items.{$itemIndex}.ingredient_id");
                    $supplierId = $request->input('supplier_id');

                    $ingredientSupplier = IngredientSupplier::where('ingredient_id', $ingredientId)
                        ->where('supplier_id', $supplierId)
                        ->where('is_active', true)
                        ->first();

                    if ($ingredientSupplier && $value > $ingredientSupplier->minimum_order_quantity) {
                        $ingredientName = Ingredients::find($ingredientId)->ingredient_name ?? 'this ingredient';
                        $fail("The ordered quantity for {$ingredientName} cannot exceed the supplier's maximum order quantity of {$ingredientSupplier->minimum_order_quantity} {$ingredientSupplier->package_unit}.");
                    }
                },
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

            $user = auth()->user();
            if (! $user->restaurantData) {
                return redirect()->back()->with('error', 'No restaurant data found.');
            }

            // Calculate expected delivery date if not provided
            $expectedDeliveryDate = $validated['expected_delivery_date'];
            if (empty($expectedDeliveryDate)) {
                $expectedDeliveryDate = $this->calculateExpectedDeliveryDate($validated['supplier_id'], $validated['items']);
            }

            $purchaseOrder = PurchaseOrder::create([
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $validated['supplier_id'],
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
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Exception $e) {
            DB::rollback();

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

        // Get suppliers that have ingredient offerings
        $suppliers = Supplier::where('is_active', true)
            ->whereHas('ingredients')
            ->with(['ingredients'])
            ->orderBy('supplier_name')
            ->get();

        // Get all ingredient offerings (supplier inventory) - not restaurant inventory
        $supplierOfferings = IngredientSupplier::with(['ingredient', 'supplier'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->groupBy('supplier_id');

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => $suppliers,
            'supplierOfferings' => $supplierOfferings,
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (! in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->back()
                ->with('error', 'Cannot update purchase order with status: '.$purchaseOrder->status);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    $itemIndex = explode('.', $attribute)[1];
                    $ingredientId = $request->input("items.{$itemIndex}.ingredient_id");
                    $supplierId = $request->input('supplier_id');

                    $ingredientSupplier = IngredientSupplier::where('ingredient_id', $ingredientId)
                        ->where('supplier_id', $supplierId)
                        ->where('is_active', true)
                        ->first();

                    if ($ingredientSupplier && $value > $ingredientSupplier->minimum_order_quantity) {
                        $ingredientName = Ingredients::find($ingredientId)->ingredient_name ?? 'this ingredient';
                        $fail("The ordered quantity for {$ingredientName} cannot exceed the supplier's maximum order quantity of {$ingredientSupplier->minimum_order_quantity} {$ingredientSupplier->package_unit}.");
                    }
                },
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

            // Calculate expected delivery date if not provided
            $expectedDeliveryDate = $validated['expected_delivery_date'];
            if (empty($expectedDeliveryDate)) {
                $expectedDeliveryDate = $this->calculateExpectedDeliveryDate($validated['supplier_id'], $validated['items']);
            }

            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
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

        return redirect()->back()
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

        return redirect()->back()
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

                            $ingredient->save();

                            \Log::info("Stock updated for ingredient {$ingredient->ingredient_name}: Packages: {$oldPackages} -> {$ingredient->packages} (+{$itemData['received_quantity']}), Stock: {$oldStock} -> {$ingredient->current_stock} (+{$stockIncrease})");
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
        $purchaseOrder->load(['supplier', 'restaurant']);

        // Send notification to supplier
        $purchaseOrder->supplier->notify(new PurchaseOrderApproved($purchaseOrder));
    }
}
