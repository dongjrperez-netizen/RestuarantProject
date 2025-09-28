<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;

echo "👤 CHECKING USER AND RESTAURANT DATA\n";
echo "====================================\n\n";

// Find the user (assuming the logged-in user is Roger Perez based on previous context)
$user = User::with('restaurantData')->where('email', 'roger@example.com')->first();

if ($user) {
    echo "📍 CURRENT USER:\n";
    echo "----------------\n";
    echo "• Name: {$user->first_name} {$user->last_name}\n";
    echo "• Email: {$user->email}\n";
    echo '• Restaurant ID: '.($user->restaurantData ? $user->restaurantData->id : 'NONE')."\n";
    echo '• Restaurant Name: '.($user->restaurantData ? $user->restaurantData->restaurant_name : 'NONE')."\n";

    if ($user->restaurantData) {
        $restaurantId = $user->restaurantData->id;

        echo "\n🏢 PURCHASE ORDERS FOR THIS RESTAURANT:\n";
        echo "---------------------------------------\n";

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('restaurant_id', $restaurantId)
            ->get();

        if ($purchaseOrders->count() > 0) {
            foreach ($purchaseOrders as $po) {
                echo "✅ {$po->po_number} | Status: {$po->status} | Supplier: {$po->supplier->supplier_name}\n";
            }
        } else {
            echo "❌ No purchase orders found for this restaurant\n";
        }

        echo "\n📋 ELIGIBLE FOR BILLING (for this restaurant):\n";
        echo "----------------------------------------------\n";

        $eligiblePOs = PurchaseOrder::with('supplier')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['delivered', 'partially_delivered'])
            ->whereDoesntHave('bill')
            ->get();

        if ($eligiblePOs->count() > 0) {
            foreach ($eligiblePOs as $po) {
                echo "✅ {$po->po_number} | Status: {$po->status} | Supplier: {$po->supplier->supplier_name}\n";
            }
        } else {
            echo "❌ No purchase orders are eligible for billing for this restaurant\n";
        }

        echo "\n🏪 AVAILABLE SUPPLIERS:\n";
        echo "-----------------------\n";

        $suppliers = Supplier::where('is_active', true)->get();
        foreach ($suppliers as $supplier) {
            echo "• {$supplier->supplier_name}\n";
        }
    }
} else {
    echo "❌ User not found with email 'roger@example.com'\n";

    echo "\n👥 ALL USERS:\n";
    echo "-------------\n";
    $allUsers = User::with('restaurantData')->get();
    foreach ($allUsers as $u) {
        $restaurantInfo = $u->restaurantData ? "Restaurant: {$u->restaurantData->restaurant_name} (ID: {$u->restaurantData->id})" : 'No restaurant';
        echo "• {$u->first_name} {$u->last_name} ({$u->email}) | {$restaurantInfo}\n";
    }
}

echo "\n✅ Check complete!\n";
