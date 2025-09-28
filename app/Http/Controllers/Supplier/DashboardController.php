<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $supplier = Auth::guard('supplier')->user();

        // Get recent purchase orders for this supplier
        $recentOrders = PurchaseOrder::with(['restaurant' => function ($query) {
            $query->select('id', 'restaurant_name');
        }])
            ->where('supplier_id', $supplier->supplier_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get statistics
        $stats = [
            'total_orders' => PurchaseOrder::where('supplier_id', $supplier->supplier_id)->count(),
            'pending_orders' => PurchaseOrder::where('supplier_id', $supplier->supplier_id)->where('status', 'sent')->count(),
            'total_ingredients' => $supplier->ingredients()->count(),
            'active_ingredients' => $supplier->ingredients()->wherePivot('is_active', true)->count(),
        ];

        return Inertia::render('Supplier/Dashboard', [
            'supplier' => $supplier,
            'recentOrders' => $recentOrders,
            'stats' => $stats,
        ]);
    }
}
