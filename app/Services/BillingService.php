<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Automatically generate bills from received purchase orders
     */
    public function generateBillFromPurchaseOrder(int $purchaseOrderId, array $options = []): array
    {
        return DB::transaction(function () use ($purchaseOrderId, $options) {
            $purchaseOrder = PurchaseOrder::with(['supplier', 'items.ingredient'])
                ->findOrFail($purchaseOrderId);

            // Check if bill already exists
            if ($purchaseOrder->bill) {
                throw new \Exception("Bill already exists for Purchase Order {$purchaseOrder->po_number}");
            }

            // Validate purchase order status
            if (! in_array($purchaseOrder->status, ['delivered', 'partially_delivered'])) {
                throw new \Exception('Purchase Order must be delivered before generating a bill');
            }

            $supplier = $purchaseOrder->supplier;

            // Calculate due date based on supplier payment terms (or default for manual receives)
            $billDate = $options['bill_date'] ?? ($purchaseOrder->actual_delivery_date ?? now());
            $paymentTerms = $supplier ? $supplier->payment_terms : 'NET_30'; // Default to NET_30 for manual receives
            $dueDate = $this->calculateDueDate($billDate, $paymentTerms);

            // Calculate bill amounts (use received quantities, not ordered)
            $billAmounts = $this->calculateBillAmounts($purchaseOrder, $options);

            // Create the bill
            $bill = SupplierBill::create([
                'purchase_order_id' => $purchaseOrder->purchase_order_id,
                'restaurant_id' => $purchaseOrder->restaurant_id,
                'supplier_id' => $purchaseOrder->supplier_id, // Can be null for manual receives
                'supplier_invoice_number' => $options['supplier_invoice_number'] ?? null,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'subtotal' => $billAmounts['subtotal'],
                'tax_amount' => $billAmounts['tax_amount'],
                'discount_amount' => $billAmounts['discount_amount'],
                'total_amount' => $billAmounts['total_amount'],
                'outstanding_amount' => $billAmounts['total_amount'],
                'status' => 'pending',
                'notes' => $options['notes'] ?? "Auto-generated from PO {$purchaseOrder->po_number}",
            ]);

            $supplierName = $supplier ? $supplier->supplier_name : $this->extractSupplierNameFromNotes($purchaseOrder->notes);

            Log::info('Bill generated from purchase order', [
                'bill_id' => $bill->bill_id,
                'bill_number' => $bill->bill_number,
                'purchase_order_id' => $purchaseOrderId,
                'po_number' => $purchaseOrder->po_number,
                'supplier' => $supplierName ?? 'Unknown',
                'is_manual_receive' => is_null($purchaseOrder->supplier_id),
                'total_amount' => $bill->total_amount,
            ]);

            return [
                'success' => true,
                'bill' => $bill,
                'bill_number' => $bill->bill_number,
                'purchase_order' => $purchaseOrder,
                'message' => "Bill {$bill->bill_number} generated successfully",
            ];
        });
    }

    /**
     * Extract supplier name from purchase order notes (for manual receives)
     */
    private function extractSupplierNameFromNotes(?string $notes): ?string
    {
        if (!$notes) {
            return null;
        }

        // Notes format: "Manual Receive - Supplier: {name} | ..."
        if (preg_match('/Supplier:\s*([^|]+)/', $notes, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Process the complete workflow: Receive goods + Generate bill
     */
    public function processReceivedPurchaseOrder(int $purchaseOrderId, array $options = []): array
    {
        return DB::transaction(function () use ($purchaseOrderId, $options) {
            // Step 1: Process inventory updates
            $inventoryResult = $this->inventoryService->addStockFromPurchaseOrder($purchaseOrderId);

            // Step 2: Generate bill automatically
            $billingOptions = [
                'bill_date' => $options['bill_date'] ?? now(),
                'supplier_invoice_number' => $options['supplier_invoice_number'] ?? null,
                'notes' => $options['notes'] ?? null,
                'tax_rate' => $options['tax_rate'] ?? 12, // Default VAT in Philippines
                'discount_amount' => $options['discount_amount'] ?? 0,
            ];

            $billResult = $this->generateBillFromPurchaseOrder($purchaseOrderId, $billingOptions);

            return [
                'success' => true,
                'inventory_result' => $inventoryResult,
                'bill_result' => $billResult,
                'message' => 'Purchase order processed: inventory updated and bill generated',
            ];
        });
    }

    /**
     * Record a payment against a bill
     */
    public function recordPayment(int $billId, array $paymentData): array
    {
        return DB::transaction(function () use ($billId, $paymentData) {
            // Lock the bill row to prevent race conditions
            $bill = SupplierBill::with('supplier')->lockForUpdate()->findOrFail($billId);

            Log::info('Recording payment - Before payment', [
                'bill_id' => $billId,
                'original_status' => $bill->status,
                'original_outstanding' => $bill->outstanding_amount,
                'original_paid' => $bill->paid_amount,
                'payment_amount' => $paymentData['payment_amount'],
            ]);

            // Validate payment amount (prevent overpayments)
            if ($paymentData['payment_amount'] > $bill->outstanding_amount) {
                throw new \Exception('Payment amount (₱'.number_format($paymentData['payment_amount'], 2).') cannot exceed outstanding amount (₱'.number_format($bill->outstanding_amount, 2).')');
            }

            if ($paymentData['payment_amount'] <= 0) {
                throw new \Exception('Payment amount must be greater than zero');
            }

            if (! $bill->canReceivePayment()) {
                throw new \Exception('This bill cannot receive payments (Status: '.$bill->status.')');
            }

            // Create payment record
            $payment = SupplierPayment::create([
                'bill_id' => $billId,
                'restaurant_id' => $bill->restaurant_id,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'payment_amount' => $paymentData['payment_amount'],
                'payment_method' => $paymentData['payment_method'],
                'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'created_by_user_id' => $paymentData['created_by_user_id'] ?? auth()->id(),
                'status' => 'completed',
            ]);

            // Update bill status and amounts
            $this->updateBillAfterPayment($bill, $paymentData['payment_amount']);

            // Reload the bill to get updated values
            $updatedBill = $bill->fresh();

            Log::info('Payment recorded - After update', [
                'payment_id' => $payment->payment_id,
                'payment_reference' => $payment->payment_reference,
                'bill_id' => $billId,
                'amount' => $paymentData['payment_amount'],
                'method' => $paymentData['payment_method'],
                'new_status' => $updatedBill->status,
                'new_outstanding' => $updatedBill->outstanding_amount,
                'new_paid' => $updatedBill->paid_amount,
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'bill' => $updatedBill,
                'message' => "Payment {$payment->payment_reference} recorded successfully",
            ];
        });
    }

    /**
     * Get comprehensive billing analytics
     */
    public function getBillingAnalytics(int $restaurantId, array $options = []): array
    {
        $dateFrom = $options['date_from'] ?? now()->subDays(30);
        $dateTo = $options['date_to'] ?? now();

        $bills = SupplierBill::where('restaurant_id', $restaurantId)
            ->whereBetween('bill_date', [$dateFrom, $dateTo])
            ->with(['supplier', 'payments'])
            ->get();

        $payments = SupplierPayment::where('restaurant_id', $restaurantId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->with(['supplier', 'bill'])
            ->get();

        return [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'bills_summary' => [
                'total_bills' => $bills->count(),
                'total_amount' => $bills->sum('total_amount'),
                'total_outstanding' => $bills->sum('outstanding_amount'),
                'total_paid' => $bills->sum('paid_amount'),
                'overdue_count' => $bills->where('is_overdue', true)->count(),
                'overdue_amount' => $bills->where('is_overdue', true)->sum('outstanding_amount'),
            ],
            'payments_summary' => [
                'total_payments' => $payments->count(),
                'total_amount_paid' => $payments->where('status', 'completed')->sum('payment_amount'),
                'payment_methods' => $payments->groupBy('payment_method')
                    ->map(fn ($group) => [
                        'count' => $group->count(),
                        'total' => $group->sum('payment_amount'),
                    ]),
            ],
            'supplier_breakdown' => $bills->groupBy('supplier.supplier_name')
                ->map(function ($supplierBills) {
                    return [
                        'bills_count' => $supplierBills->count(),
                        'total_amount' => $supplierBills->sum('total_amount'),
                        'outstanding_amount' => $supplierBills->sum('outstanding_amount'),
                        'payment_rate' => $supplierBills->sum('total_amount') > 0
                            ? ($supplierBills->sum('paid_amount') / $supplierBills->sum('total_amount')) * 100
                            : 0,
                    ];
                }),
            'monthly_trends' => $this->getMonthlyBillingTrends($restaurantId, $dateFrom, $dateTo),
        ];
    }

    /**
     * Process bulk bills for multiple purchase orders
     */
    public function generateBulkBills(array $purchaseOrderIds, array $globalOptions = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($purchaseOrderIds as $purchaseOrderId) {
            try {
                $result = $this->generateBillFromPurchaseOrder($purchaseOrderId, $globalOptions);
                $results[] = $result;
                $successCount++;
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'purchase_order_id' => $purchaseOrderId,
                    'error' => $e->getMessage(),
                ];
                $errorCount++;
                Log::error('Bulk bill generation failed', [
                    'purchase_order_id' => $purchaseOrderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => $errorCount === 0,
            'processed_count' => count($purchaseOrderIds),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results,
        ];
    }

    /**
     * Auto-mark overdue bills
     */
    public function markOverdueBills(?int $restaurantId = null): array
    {
        $query = SupplierBill::overdue();

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $overdueBills = $query->get();
        $markedCount = 0;

        foreach ($overdueBills as $bill) {
            if ($bill->status !== 'overdue') {
                $bill->update(['status' => 'overdue']);
                $markedCount++;
            }
        }

        Log::info('Overdue bills marked', [
            'restaurant_id' => $restaurantId,
            'marked_count' => $markedCount,
            'total_overdue' => $overdueBills->count(),
        ]);

        return [
            'marked_count' => $markedCount,
            'total_overdue' => $overdueBills->count(),
            'overdue_bills' => $overdueBills,
        ];
    }

    /**
     * Calculate payment terms and due dates
     */
    private function calculateDueDate($billDate, $paymentTerms): Carbon
    {
        $date = Carbon::parse($billDate);

        // Normalize payment terms to handle different formats
        $normalizedTerms = strtoupper(str_replace(' ', '_', $paymentTerms));

        return match ($normalizedTerms) {
            'COD', 'NET_0', 'NET 0' => $date,
            'NET_7', 'NET 7' => $date->addDays(7),
            'NET_15', 'NET 15' => $date->addDays(15),
            'NET_30', 'NET 30' => $date->addDays(30),
            'NET_60', 'NET 60' => $date->addDays(60),
            'NET_90', 'NET 90' => $date->addDays(90),
            default => $date->addDays(30) // Default to 30 days
        };
    }

    /**
     * Calculate bill amounts based on received quantities
     */
    private function calculateBillAmounts(PurchaseOrder $purchaseOrder, array $options): array
    {
        $subtotal = 0;

        // Calculate based on received quantities, not ordered quantities
        foreach ($purchaseOrder->items as $item) {
            $subtotal += $item->received_quantity * $item->unit_price;
        }

        $discountAmount = $options['discount_amount'] ?? 0;
        $taxRate = $options['tax_rate'] ?? 12; // Default VAT in Philippines

        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = ($subtotalAfterDiscount * $taxRate) / 100;
        $totalAmount = $subtotalAfterDiscount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Update bill after payment
     */
    private function updateBillAfterPayment(SupplierBill $bill, float $paymentAmount): void
    {
        $originalPaidAmount = $bill->paid_amount;
        $originalOutstandingAmount = $bill->outstanding_amount;
        $originalStatus = $bill->status;

        $newPaidAmount = $bill->paid_amount + $paymentAmount;
        $newOutstandingAmount = $bill->total_amount - $newPaidAmount;

        // Ensure outstanding amount doesn't go negative (cap at 0)
        $newOutstandingAmount = max(0, $newOutstandingAmount);

        // If outstanding is 0 but paid exceeds total, adjust paid amount
        if ($newOutstandingAmount == 0 && $newPaidAmount > $bill->total_amount) {
            $newPaidAmount = $bill->total_amount;
        }

        $newStatus = match (true) {
            $newOutstandingAmount <= 0 => 'paid',
            $newPaidAmount > 0 => 'partially_paid',
            default => $bill->status
        };

        // Check if overdue
        if ($newOutstandingAmount > 0 && $bill->due_date < now()) {
            $newStatus = 'overdue';
        }

        Log::info('Updating bill after payment', [
            'bill_id' => $bill->bill_id,
            'payment_amount' => $paymentAmount,
            'original_paid' => $originalPaidAmount,
            'original_outstanding' => $originalOutstandingAmount,
            'original_status' => $originalStatus,
            'new_paid' => $newPaidAmount,
            'new_outstanding' => $newOutstandingAmount,
            'new_status' => $newStatus,
        ]);

        $updateResult = $bill->update([
            'paid_amount' => $newPaidAmount,
            'outstanding_amount' => $newOutstandingAmount,
            'status' => $newStatus,
        ]);

        Log::info('Bill update result', [
            'bill_id' => $bill->bill_id,
            'update_successful' => $updateResult,
            'actual_paid_after_update' => $bill->fresh()->paid_amount,
            'actual_outstanding_after_update' => $bill->fresh()->outstanding_amount,
            'actual_status_after_update' => $bill->fresh()->status,
        ]);
    }

    /**
     * Get monthly billing trends
     */
    private function getMonthlyBillingTrends(int $restaurantId, $dateFrom, $dateTo): array
    {
        $bills = SupplierBill::where('restaurant_id', $restaurantId)
            ->whereBetween('bill_date', [$dateFrom, $dateTo])
            ->selectRaw('YEAR(bill_date) as year, MONTH(bill_date) as month, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $bills->map(function ($item) {
            return [
                'period' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                'bills_count' => $item->count,
                'total_amount' => $item->total,
            ];
        })->toArray();
    }
}
