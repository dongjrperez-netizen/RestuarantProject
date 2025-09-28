<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PurchaseOrder;

echo "🔍 CHECKING PURCHASE ORDERS\n";
echo "==========================\n\n";

// Check all purchase orders
$allPOs = PurchaseOrder::with(['supplier', 'bill'])->get();

echo "📋 ALL PURCHASE ORDERS:\n";
echo "-----------------------\n";
foreach ($allPOs as $po) {
    $billExists = $po->bill ? "HAS BILL ({$po->bill->bill_number})" : 'NO BILL';
    echo "• {$po->po_number} | Status: {$po->status} | Restaurant: {$po->restaurant_id} | Supplier: {$po->supplier->supplier_name} | {$billExists}\n";
}

echo "\n📊 PURCHASE ORDERS BY STATUS:\n";
echo "-----------------------------\n";
$statusCounts = $allPOs->groupBy('status')->map(function ($group) {
    return $group->count();
});

foreach ($statusCounts as $status => $count) {
    echo "• {$status}: {$count}\n";
}

echo "\n🎯 ELIGIBLE FOR BILLING:\n";
echo "------------------------\n";
$eligiblePOs = PurchaseOrder::with('supplier')
    ->whereIn('status', ['delivered', 'partially_delivered'])
    ->whereDoesntHave('bill')
    ->get();

if ($eligiblePOs->count() > 0) {
    foreach ($eligiblePOs as $po) {
        echo "✅ {$po->po_number} | Status: {$po->status} | Restaurant: {$po->restaurant_id} | Supplier: {$po->supplier->supplier_name}\n";
    }
} else {
    echo "❌ No purchase orders are eligible for billing.\n";
    echo "   Requirements: status = 'delivered' or 'partially_delivered' AND no existing bill\n";
}

echo "\n🔧 RECOMMENDATIONS:\n";
echo "-------------------\n";

// Check if there are delivered POs with bills
$deliveredWithBills = PurchaseOrder::with(['supplier', 'bill'])
    ->whereIn('status', ['delivered', 'partially_delivered'])
    ->whereHas('bill')
    ->get();

if ($deliveredWithBills->count() > 0) {
    echo "ℹ️  Found {$deliveredWithBills->count()} delivered PO(s) that already have bills:\n";
    foreach ($deliveredWithBills as $po) {
        echo "   • {$po->po_number} → {$po->bill->bill_number}\n";
    }
}

// Check if there are POs that need to be marked as delivered
$confirmedPOs = PurchaseOrder::with('supplier')
    ->where('status', 'confirmed')
    ->get();

if ($confirmedPOs->count() > 0) {
    echo "💡 Found {$confirmedPOs->count()} confirmed PO(s) that could be marked as delivered:\n";
    foreach ($confirmedPOs as $po) {
        echo "   • {$po->po_number} | Supplier: {$po->supplier->supplier_name}\n";
    }
    echo "   → Go to /purchase-orders/{purchase_order_id}/receive to mark them as delivered\n";
}

echo "\n✅ Check complete!\n";
