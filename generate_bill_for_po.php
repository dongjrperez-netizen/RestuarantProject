<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PurchaseOrder;
use App\Services\BillingService;

echo "🚀 GENERATING BILL FOR PO-2025-000001\n";
echo "====================================\n\n";

// Find the PO
$po = PurchaseOrder::with(['supplier', 'items.ingredient'])
    ->where('po_number', 'PO-2025-000001')
    ->first();

if (! $po) {
    echo "❌ Purchase order PO-2025-000001 not found!\n";
    exit(1);
}

echo "📋 PURCHASE ORDER DETAILS:\n";
echo "--------------------------\n";
echo "• PO Number: {$po->po_number}\n";
echo "• Status: {$po->status}\n";
echo "• Restaurant ID: {$po->restaurant_id}\n";
echo "• Supplier: {$po->supplier->supplier_name}\n";
echo '• Total Amount: ₱'.number_format($po->total_amount, 2)."\n";
echo '• Items Count: '.$po->items->count()."\n";

if ($po->status !== 'delivered') {
    echo "\n⚠️  WARNING: PO status is '{$po->status}' not 'delivered'\n";
    echo "   Bills are typically generated when PO status = 'delivered'\n";
}

// Check if bill already exists
if ($po->bill) {
    echo "\n⚠️  Bill already exists: {$po->bill->bill_number}\n";
    echo "   Skipping bill generation.\n";
    exit(0);
}

echo "\n🎯 GENERATING BILL:\n";
echo "-------------------\n";

try {
    // Create the billing service with dependencies
    $inventoryService = app(\App\Services\InventoryService::class);
    $billingService = new BillingService($inventoryService);

    $result = $billingService->generateBillFromPurchaseOrder(
        $po->purchase_order_id,
        [
            'bill_date' => now()->format('Y-m-d'),
            'auto_calculate_due_date' => true,
            'notes' => 'Generated for existing delivered purchase order',
        ]
    );

    if ($result['success']) {
        echo "✅ SUCCESS!\n";
        $bill = $result['bill'];
        echo "• Bill Number: {$bill->bill_number}\n";
        echo "• Bill ID: {$bill->bill_id}\n";
        echo '• Total Amount: ₱'.number_format($bill->total_amount, 2)."\n";
        echo "• Due Date: {$bill->due_date}\n";
        echo "• Status: {$bill->status}\n";
        echo "• Message: {$result['message']}\n";

        echo "\n🌐 VIEW YOUR BILL:\n";
        echo "------------------\n";
        echo "Dashboard: http://localhost:8000/bills\n";
        echo "Bill Details: http://localhost:8000/bills/{$bill->bill_id}\n";

        echo "\n🎉 Your purchase order now has a bill!\n";
        echo "Go to http://localhost:8000/bills/create and you should see\n";
        echo "that PO-2025-000001 is no longer in the dropdown (because it has a bill).\n";

    } else {
        echo "❌ FAILED: {$result['message']}\n";
        if (isset($result['errors'])) {
            foreach ($result['errors'] as $field => $errors) {
                echo "  • {$field}: ".implode(', ', $errors)."\n";
            }
        }
    }

} catch (\Exception $e) {
    echo '💥 ERROR: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\n✅ Process complete!\n";
