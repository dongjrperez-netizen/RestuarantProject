<?php

namespace App\Examples;

use App\Models\SupplierBill;
use App\Services\BillingService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive examples of the Restaurant Billing Management System
 *
 * This demonstrates the complete workflow from Purchase Order → Inventory → Billing → Payments
 */
class BillingSystemExample
{
    private BillingService $billingService;

    private InventoryService $inventoryService;

    public function __construct(BillingService $billingService, InventoryService $inventoryService)
    {
        $this->billingService = $billingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Example 1: Complete Workflow - Receive Goods + Auto-Generate Bill
     * This is the most common scenario in restaurant operations
     */
    public function completeReceivingWorkflow()
    {
        try {
            // Scenario: Restaurant receives PO-2025-000001
            // - 5 sacks rice (125kg total)
            // - 10 trays chicken (10kg total)
            // - All quantities received as ordered

            $purchaseOrderId = 1;

            echo "🚚 Processing Complete Receiving Workflow\n";
            echo "===============================================\n";

            // Step 1: Process the complete workflow (inventory + billing)
            $result = $this->billingService->processReceivedPurchaseOrder($purchaseOrderId, [
                'supplier_invoice_number' => 'INV-METRO-2025-001',
                'tax_rate' => 12, // VAT in Philippines
                'notes' => 'All items received in good condition',
            ]);

            echo "✅ Inventory Result:\n";
            echo "   - Items processed: {$result['inventory_result']['items_processed']}\n";
            echo "   - Total items: {$result['inventory_result']['total_items']}\n";
            echo "   - PO Number: {$result['inventory_result']['po_number']}\n";
            echo "   - Supplier: {$result['inventory_result']['supplier']}\n";

            echo "\n✅ Bill Generated:\n";
            $bill = $result['bill_result']['bill'];
            echo "   - Bill Number: {$bill->bill_number}\n";
            echo '   - Total Amount: ₱'.number_format($bill->total_amount, 2)."\n";
            echo "   - Due Date: {$bill->due_date->format('M d, Y')}\n";
            echo "   - Status: {$bill->formatted_status}\n";

            return $result;

        } catch (\Exception $e) {
            echo "❌ Workflow failed: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Example 2: Manual Bill Generation (for existing received POs)
     */
    public function manualBillGeneration()
    {
        try {
            echo "\n📋 Manual Bill Generation\n";
            echo "==========================\n";

            $purchaseOrderId = 2;

            $result = $this->billingService->generateBillFromPurchaseOrder($purchaseOrderId, [
                'supplier_invoice_number' => 'INV-FRESH-2025-005',
                'bill_date' => now(),
                'tax_rate' => 12,
                'discount_amount' => 100.00,
                'notes' => 'Early payment discount applied',
            ]);

            $bill = $result['bill'];
            echo "✅ Bill created successfully:\n";
            echo "   - Bill ID: {$bill->bill_id}\n";
            echo "   - Bill Number: {$bill->bill_number}\n";
            echo '   - Subtotal: ₱'.number_format($bill->subtotal, 2)."\n";
            echo '   - Discount: ₱'.number_format($bill->discount_amount, 2)."\n";
            echo '   - Tax: ₱'.number_format($bill->tax_amount, 2)."\n";
            echo '   - Total: ₱'.number_format($bill->total_amount, 2)."\n";

            return $result;

        } catch (\Exception $e) {
            echo "❌ Manual bill generation failed: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Example 3: Payment Processing
     */
    public function paymentProcessing()
    {
        try {
            echo "\n💳 Payment Processing Examples\n";
            echo "===============================\n";

            // Example: Partial payment by bank transfer
            $billId = 1;
            $bill = SupplierBill::findOrFail($billId);

            echo "📄 Bill: {$bill->bill_number}\n";
            echo '   - Total Amount: ₱'.number_format($bill->total_amount, 2)."\n";
            echo '   - Outstanding: ₱'.number_format($bill->outstanding_amount, 2)."\n";

            // Scenario 1: Partial payment
            $partialPayment = $this->billingService->recordPayment($billId, [
                'payment_amount' => 5000.00,
                'payment_method' => 'bank_transfer',
                'payment_date' => now(),
                'transaction_reference' => 'TXN-001-2025',
                'notes' => 'First installment payment',
                'created_by_user_id' => 1,
            ]);

            $payment = $partialPayment['payment'];
            $updatedBill = $partialPayment['bill'];

            echo "\n✅ Partial Payment Recorded:\n";
            echo "   - Payment Reference: {$payment->payment_reference}\n";
            echo '   - Amount Paid: ₱'.number_format($payment->payment_amount, 2)."\n";
            echo "   - Method: {$payment->formatted_method}\n";
            echo "   - Bill Status: {$updatedBill->formatted_status}\n";
            echo '   - Remaining: ₱'.number_format($updatedBill->outstanding_amount, 2)."\n";

            // Scenario 2: Final payment
            if ($updatedBill->outstanding_amount > 0) {
                $finalPayment = $this->billingService->recordPayment($billId, [
                    'payment_amount' => $updatedBill->outstanding_amount,
                    'payment_method' => 'cash',
                    'payment_date' => now()->addDays(5),
                    'notes' => 'Final settlement',
                    'created_by_user_id' => 1,
                ]);

                echo "\n✅ Final Payment Recorded:\n";
                echo "   - Payment Reference: {$finalPayment['payment']->payment_reference}\n";
                echo '   - Amount: ₱'.number_format($finalPayment['payment']->payment_amount, 2)."\n";
                echo "   - Bill Status: {$finalPayment['bill']->formatted_status}\n";
                echo "   - Payment Progress: 100%\n";
            }

        } catch (\Exception $e) {
            echo "❌ Payment processing failed: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Example 4: Bulk Operations
     */
    public function bulkOperations()
    {
        try {
            echo "\n📦 Bulk Operations\n";
            echo "===================\n";

            // Bulk bill generation for multiple POs
            $purchaseOrderIds = [3, 4, 5];

            $bulkResult = $this->billingService->generateBulkBills($purchaseOrderIds, [
                'tax_rate' => 12,
                'notes' => 'Bulk generated bills - End of week processing',
            ]);

            echo "✅ Bulk Bill Generation:\n";
            echo "   - Total POs processed: {$bulkResult['processed_count']}\n";
            echo "   - Successful: {$bulkResult['success_count']}\n";
            echo "   - Failed: {$bulkResult['error_count']}\n";

            foreach ($bulkResult['results'] as $result) {
                if ($result['success']) {
                    echo "   - ✅ PO {$result['purchase_order']->po_number} → Bill {$result['bill']->bill_number}\n";
                } else {
                    echo "   - ❌ PO {$result['purchase_order_id']}: {$result['error']}\n";
                }
            }

            // Auto-mark overdue bills
            $overdueResult = $this->billingService->markOverdueBills(1); // Restaurant ID 1
            echo "\n📅 Overdue Bills Check:\n";
            echo "   - Bills marked overdue: {$overdueResult['marked_count']}\n";
            echo "   - Total overdue bills: {$overdueResult['total_overdue']}\n";

        } catch (\Exception $e) {
            echo "❌ Bulk operations failed: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Example 5: Analytics and Reporting
     */
    public function analyticsAndReporting()
    {
        try {
            echo "\n📊 Billing Analytics\n";
            echo "=====================\n";

            $restaurantId = 1;
            $analytics = $this->billingService->getBillingAnalytics($restaurantId, [
                'date_from' => now()->subDays(30),
                'date_to' => now(),
            ]);

            echo "📈 Last 30 Days Summary:\n";
            echo "   - Total Bills: {$analytics['bills_summary']['total_bills']}\n";
            echo '   - Total Amount: ₱'.number_format($analytics['bills_summary']['total_amount'], 2)."\n";
            echo '   - Outstanding: ₱'.number_format($analytics['bills_summary']['total_outstanding'], 2)."\n";
            echo '   - Paid Amount: ₱'.number_format($analytics['bills_summary']['total_paid'], 2)."\n";
            echo "   - Overdue Count: {$analytics['bills_summary']['overdue_count']}\n";
            echo '   - Overdue Amount: ₱'.number_format($analytics['bills_summary']['overdue_amount'], 2)."\n";

            echo "\n💳 Payment Methods:\n";
            foreach ($analytics['payments_summary']['payment_methods'] as $method => $data) {
                echo '   - '.ucfirst(str_replace('_', ' ', $method)).": {$data['count']} payments, ₱".number_format($data['total'], 2)."\n";
            }

            echo "\n🏪 Supplier Breakdown:\n";
            foreach ($analytics['supplier_breakdown'] as $supplierName => $data) {
                $paymentRate = round($data['payment_rate'], 1);
                echo "   - {$supplierName}: {$data['bills_count']} bills, {$paymentRate}% paid\n";
            }

            return $analytics;

        } catch (\Exception $e) {
            echo "❌ Analytics failed: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * Example 6: Complete Restaurant Day Workflow
     */
    public function completeRestaurantDay()
    {
        echo "\n🏪 Complete Restaurant Day Workflow\n";
        echo "====================================\n";
        echo "Simulating a typical day's billing operations...\n\n";

        try {
            // Morning: Receive deliveries and process bills
            echo "🌅 Morning Operations:\n";
            $this->completeReceivingWorkflow();

            // Afternoon: Process payments
            echo "\n🌞 Afternoon Operations:\n";
            $this->paymentProcessing();

            // Evening: Analytics and reporting
            echo "\n🌙 Evening Operations:\n";
            $analytics = $this->analyticsAndReporting();

            // End of day summary
            echo "\n📋 End of Day Summary:\n";
            echo "=====================================\n";
            echo "✅ All operations completed successfully!\n";
            echo "📊 Key Metrics:\n";
            echo "   - New bills generated today\n";
            echo "   - Payments processed\n";
            echo "   - Inventory updated automatically\n";
            echo "   - All transactions logged\n";

        } catch (\Exception $e) {
            echo "\n❌ Restaurant day workflow failed: {$e->getMessage()}\n";
            Log::error('Complete restaurant day workflow failed', ['error' => $e->getMessage()]);
        }
    }
}

/**
 * API USAGE EXAMPLES
 *
 * Below are examples of how to use the billing API endpoints:
 */

/*
// 1. Auto-generate bill from purchase order
POST /billing/bills/auto-generate
Content-Type: application/json

{
    "purchase_order_id": 1,
    "supplier_invoice_number": "INV-2025-001",
    "tax_rate": 12,
    "discount_amount": 50.00,
    "notes": "Early payment discount"
}

// Response:
{
    "success": true,
    "message": "Bill BILL-2025-000001 generated successfully",
    "bill": {
        "bill_id": 1,
        "bill_number": "BILL-2025-000001",
        "total_amount": "9520.00",
        "due_date": "2025-02-08",
        "status": "pending"
    }
}

// 2. Process complete receiving workflow (Inventory + Billing)
POST /billing/bills/process-received
Content-Type: application/json

{
    "purchase_order_id": 1,
    "supplier_invoice_number": "INV-SUPPLIER-123",
    "tax_rate": 12,
    "notes": "All items received in good condition"
}

// Response:
{
    "success": true,
    "message": "Purchase order processed: inventory updated and bill generated",
    "data": {
        "inventory_result": {
            "items_processed": 2,
            "total_items": 2,
            "po_number": "PO-2025-000001"
        },
        "bill": {
            "bill_id": 1,
            "bill_number": "BILL-2025-000001",
            "total_amount": "9520.00"
        },
        "redirect_url": "/bills/1"
    }
}

// 3. Record quick payment
POST /billing/bills/1/quick-payment
Content-Type: application/json

{
    "payment_amount": 5000.00,
    "payment_method": "bank_transfer",
    "transaction_reference": "TXN-001-2025",
    "notes": "Partial payment"
}

// Response:
{
    "success": true,
    "message": "Payment PAY-2025-000001 recorded successfully",
    "data": {
        "payment": {
            "payment_id": 1,
            "payment_reference": "PAY-2025-000001",
            "payment_amount": "5000.00"
        },
        "bill": {
            "bill_id": 1,
            "outstanding_amount": "4520.00",
            "status": "partially_paid"
        }
    }
}

// 4. Get billing analytics
GET /billing/bills/analytics?date_from=2025-01-01&date_to=2025-01-31

// Response:
{
    "success": true,
    "data": {
        "bills_summary": {
            "total_bills": 15,
            "total_amount": "125000.00",
            "total_outstanding": "35000.00",
            "overdue_count": 3
        },
        "payments_summary": {
            "total_payments": 12,
            "total_amount_paid": "90000.00"
        },
        "supplier_breakdown": {
            "Metro Food Supplies": {
                "bills_count": 8,
                "payment_rate": 85.5
            }
        }
    }
}

// 5. Bulk generate bills
POST /billing/bills/bulk-generate
Content-Type: application/json

{
    "purchase_order_ids": [1, 2, 3, 4],
    "global_options": {
        "tax_rate": 12,
        "notes": "Week-end bulk processing"
    }
}

// Response:
{
    "success": true,
    "message": "Successfully generated 4 bills",
    "data": {
        "processed_count": 4,
        "success_count": 4,
        "error_count": 0
    }
}
*/
