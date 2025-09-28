<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;

echo "🐛 DEBUGGING BILL CREATE PAGE\n";
echo "============================\n\n";

// Simulate the exact same logic as the SupplierBillController::create() method
$user = User::with('restaurantData')->where('email', 'rogerjrperez286@gmail.com')->first();

if ($user && $user->restaurantData) {
    $restaurantId = $user->restaurantData->id;

    echo "📍 SIMULATING CONTROLLER LOGIC:\n";
    echo "-------------------------------\n";
    echo "• User: {$user->first_name} {$user->last_name}\n";
    echo "• Restaurant ID: {$restaurantId}\n";
    echo "• Restaurant Name: {$user->restaurantData->restaurant_name}\n";

    echo "\n🏪 SUPPLIERS (is_active = true):\n";
    echo "--------------------------------\n";
    $suppliers = Supplier::where('is_active', true)
        ->orderBy('supplier_name')
        ->get();

    echo "Count: {$suppliers->count()}\n";
    foreach ($suppliers as $supplier) {
        echo "• {$supplier->supplier_name} (ID: {$supplier->supplier_id})\n";
    }

    echo "\n📋 PURCHASE ORDERS QUERY:\n";
    echo "-------------------------\n";
    echo "Filtering criteria:\n";
    echo "• restaurant_id = {$restaurantId}\n";
    echo "• status IN ('delivered', 'partially_delivered')\n";
    echo "• does not have a bill (whereDoesntHave('bill'))\n";

    $purchaseOrders = PurchaseOrder::with('supplier')
        ->where('restaurant_id', $restaurantId)
        ->whereIn('status', ['delivered', 'partially_delivered'])
        ->whereDoesntHave('bill')
        ->orderBy('created_at', 'desc')
        ->get();

    echo "\nResult count: {$purchaseOrders->count()}\n";

    if ($purchaseOrders->count() > 0) {
        echo "\n✅ AVAILABLE PURCHASE ORDERS:\n";
        foreach ($purchaseOrders as $po) {
            echo "• {$po->po_number} | Status: {$po->status} | Supplier: {$po->supplier->supplier_name} | Date: {$po->created_at}\n";
        }
    } else {
        echo "\n❌ NO PURCHASE ORDERS FOUND\n";

        echo "\n🔍 DEBUGGING - Let's check each condition:\n";
        echo "------------------------------------------\n";

        // Check restaurant_id condition
        $posByRestaurant = PurchaseOrder::where('restaurant_id', $restaurantId)->get();
        echo "1. POs for restaurant {$restaurantId}: {$posByRestaurant->count()}\n";

        foreach ($posByRestaurant as $po) {
            echo "   • {$po->po_number} | Status: {$po->status}\n";
        }

        // Check status condition
        $posByStatus = PurchaseOrder::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['delivered', 'partially_delivered'])
            ->get();
        echo "\n2. POs with correct status: {$posByStatus->count()}\n";

        foreach ($posByStatus as $po) {
            echo "   • {$po->po_number} | Status: {$po->status}\n";
        }

        // Check bill relationship
        $posWithoutBill = PurchaseOrder::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['delivered', 'partially_delivered'])
            ->with('bill')
            ->get();
        echo "\n3. POs with bill check:\n";

        foreach ($posWithoutBill as $po) {
            $billStatus = $po->bill ? "HAS BILL ({$po->bill->bill_number})" : 'NO BILL';
            echo "   • {$po->po_number} | Status: {$po->status} | {$billStatus}\n";
        }
    }

    echo "\n📤 DATA THAT WOULD BE SENT TO FRONTEND:\n";
    echo "---------------------------------------\n";
    echo 'suppliers: '.$suppliers->count()." items\n";
    echo 'purchaseOrders: '.$purchaseOrders->count()." items\n";

} else {
    echo "❌ User or restaurant data not found!\n";
}

echo "\n✅ Debug complete!\n";
