<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SupplierPurchaseOrderController extends Controller
{
    /**
     * Supplier responds to a purchase order via signed link (confirm or reject)
     */
    public function respond(Request $request, $id)
    {
        \Log::info('Supplier PO respond called', [
            'id' => $id,
            'has_signature' => $request->hasValidSignature(),
            'query_params' => $request->query(),
            'full_url' => $request->fullUrl(),
        ]);

        // Validate signed URL
        if (! $request->hasValidSignature()) {
            \Log::warning('Invalid signature for PO response', [
                'id' => $id,
                'url' => $request->fullUrl(),
            ]);
            abort(403, 'Invalid or expired link.');
        }

        $action = $request->query('action');
        if (! in_array($action, ['confirm', 'reject'])) {
            \Log::warning('Invalid action for PO response', ['action' => $action]);
            abort(400, 'Invalid action.');
        }

        $purchaseOrder = PurchaseOrder::findOrFail($id);

        \Log::info('Processing PO response', [
            'po_id' => $purchaseOrder->purchase_order_id,
            'po_number' => $purchaseOrder->po_number,
            'action' => $action,
            'current_status' => $purchaseOrder->status,
        ]);

        if ($action === 'confirm') {
            $purchaseOrder->status = 'confirmed';
            $purchaseOrder->supplier_response = 'confirm';
        } else {
            $purchaseOrder->status = 'cancelled';
            $purchaseOrder->supplier_response = 'reject';
        }

        $purchaseOrder->supplier_responded_at = now();
        $purchaseOrder->save();

        \Log::info('PO response saved', [
            'po_id' => $purchaseOrder->purchase_order_id,
            'new_status' => $purchaseOrder->status,
        ]);

        // Simple plain response for supplier (they are not authenticated in this flow)
        $message = $action === 'confirm' ? 'You have confirmed the purchase order. Thank you.' : 'You have rejected the purchase order.';

        return response()->view('supplier.purchase_order_response', [
            'message' => $message,
            'po_number' => $purchaseOrder->po_number,
            'action' => $action,
        ]);
    }
}
