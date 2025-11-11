<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class SupplierBillController extends Controller
{
    private BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index()
    {
         $restaurantId = auth()->user()->restaurantData->id;

        $bills = SupplierBill::with(['supplier', 'purchaseOrder', 'payments'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    ...$bill->toArray(),
                    'is_overdue' => $bill->is_overdue,
                    'days_overdue' => $bill->days_overdue,
                ];
            });

        // Calculate paid this month by summing payments made in current month
        $paidThisMonth = \App\Models\SupplierPayment::where('restaurant_id', $restaurantId)
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('payment_amount');

        $summary = [
            'total_outstanding' => $bills->sum('outstanding_amount'),
            'overdue_amount' => $bills->where('is_overdue', true)->sum('outstanding_amount'),
            'total_bills' => $bills->count(),
            'overdue_count' => $bills->where('is_overdue', true)->count(),
            'paid_this_month' => $paidThisMonth,
        ];

        return Inertia::render('Bills/Index', [
            'bills' => $bills,
            'summary' => $summary,
        ]);
    }

    public function show($id)
    {
        $bill = SupplierBill::with([
            'supplier',
            'purchaseOrder.items.ingredient',
            'payments.createdBy',
        ])->findOrFail($id);

        $bill->is_overdue = $bill->is_overdue;
        $bill->days_overdue = $bill->days_overdue;

        Log::info('Bills/Show - Bill data being sent to frontend', [
            'bill_id' => $bill->bill_id,
            'status' => $bill->status,
            'total_amount' => $bill->total_amount,
            'paid_amount' => $bill->paid_amount,
            'outstanding_amount' => $bill->outstanding_amount,
            'payments_count' => $bill->payments->count(),
        ]);

        return Inertia::render('Bills/Show', [
            'bill' => $bill,
        ]);
    }

    public function create()
    {
         $restaurantId = auth()->user()->restaurantData->id;

        $suppliers = Supplier::where('is_active', true)
            ->where('restaurant_id', $restaurantId)
            ->orderBy('supplier_name')
            ->get();

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['delivered', 'partially_delivered'])
            ->whereDoesntHave('bill')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Bills/Create', [
            'suppliers' => $suppliers,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,purchase_order_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $bill = SupplierBill::create([
            'purchase_order_id' => $validated['purchase_order_id'],
            'restaurant_id' => $this->getRestaurantId(),
            'supplier_id' => $validated['supplier_id'],
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'discount_amount' => $validated['discount_amount'],
            'total_amount' => $validated['total_amount'],
            'outstanding_amount' => $validated['total_amount'],
            'status' => 'pending',
            'notes' => $validated['notes'],
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('supplier-bills', 'public');
            $bill->update(['attachment_path' => $path]);
        }

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill created successfully.');
    }

    public function createFromPurchaseOrder($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->findOrFail($purchaseOrderId);

        if ($purchaseOrder->bill) {
            return redirect()->route('bills.show', $purchaseOrder->bill->bill_id)
                ->with('info', 'Bill already exists for this purchase order.');
        }

        $supplier = $purchaseOrder->supplier;
        $dueDate = $this->calculateDueDate($purchaseOrder->actual_delivery_date ?? $purchaseOrder->order_date, $supplier->payment_terms);

        $bill = SupplierBill::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'restaurant_id' => $purchaseOrder->restaurant_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'bill_date' => $purchaseOrder->actual_delivery_date ?? $purchaseOrder->order_date,
            'due_date' => $dueDate,
            'subtotal' => $purchaseOrder->subtotal,
            'tax_amount' => $purchaseOrder->tax_amount,
            'discount_amount' => $purchaseOrder->discount_amount,
            'total_amount' => $purchaseOrder->total_amount,
            'outstanding_amount' => $purchaseOrder->total_amount,
            'status' => 'pending',
        ]);

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill created from purchase order successfully.');
    }

    public function edit($id)
    {
        $bill = SupplierBill::with('supplier')->findOrFail($id);

        if ($bill->status === 'paid') {
            return redirect()->route('bills.show', $id)
                ->with('error', 'Cannot edit paid bills.');
        }

          $restaurantId = auth()->user()->restaurantData->id;

        $suppliers = Supplier::where('is_active', true)
            ->where('restaurant_id', $restaurantId)
            ->orderBy('supplier_name')
            ->get();

        return Inertia::render('Bills/Edit', [
            'bill' => $bill,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, $id)
    {
        $bill = SupplierBill::findOrFail($id);

        if ($bill->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Cannot update paid bills.');
        }

        $validated = $request->validate([
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $oldTotal = $bill->total_amount;
        $newTotal = $validated['total_amount'];
        $paidAmount = $bill->paid_amount;

        $bill->update([
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'discount_amount' => $validated['discount_amount'],
            'total_amount' => $newTotal,
            'outstanding_amount' => $newTotal - $paidAmount,
            'notes' => $validated['notes'],
        ]);

        if ($request->hasFile('attachment')) {
            if ($bill->attachment_path) {
                Storage::disk('public')->delete($bill->attachment_path);
            }
            $path = $request->file('attachment')->store('supplier-bills', 'public');
            $bill->update(['attachment_path' => $path]);
        }

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill updated successfully.');
    }

    public function markOverdue()
    {
        $overdueBills = SupplierBill::where('due_date', '<', now())
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get();

        foreach ($overdueBills as $bill) {
            $bill->update(['status' => 'overdue']);
        }

        return response()->json(['message' => 'Overdue bills updated.']);
    }

    private function calculateDueDate($billDate, $paymentTerms)
    {
        $date = Carbon::parse($billDate);

        switch ($paymentTerms) {
            case 'COD':
                return $date;
            case 'NET_7':
                return $date->addDays(7);
            case 'NET_15':
                return $date->addDays(15);
            case 'NET_30':
                return $date->addDays(30);
            case 'NET_60':
                return $date->addDays(60);
            case 'NET_90':
                return $date->addDays(90);
            default:
                return $date->addDays(30);
        }
    }

    public function downloadAttachment($id)
    {
        $bill = SupplierBill::findOrFail($id);

        if (! $bill->attachment_path || ! Storage::disk('public')->exists($bill->attachment_path)) {
            return redirect()->back()->with('error', 'Attachment not found.');
        }

        return Storage::disk('public')->download($bill->attachment_path);
    }

    /**
     * Auto-generate bill from purchase order using BillingService
     */
    public function autoGenerateFromPurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,purchase_order_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->billingService->generateBillFromPurchaseOrder(
                $validated['purchase_order_id'],
                array_filter($validated)
            );

            return redirect()->route('bills.show', $result['bill']->bill_id)
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('Auto-generate bill failed', [
                'purchase_order_id' => $validated['purchase_order_id'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to generate bill: '.$e->getMessage());
        }
    }

    /**
     * Process complete workflow: Receive inventory + Generate bill
     */
    public function processReceived(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,purchase_order_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->billingService->processReceivedPurchaseOrder(
                $validated['purchase_order_id'],
                array_filter($validated)
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'inventory_result' => $result['inventory_result'],
                    'bill' => $result['bill_result']['bill'],
                    'redirect_url' => route('bills.show', $result['bill_result']['bill']->bill_id),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Process received purchase order failed', [
                'purchase_order_id' => $validated['purchase_order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate bills in bulk for multiple purchase orders
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_ids' => 'required|array|min:1',
            'purchase_order_ids.*' => 'exists:purchase_orders,purchase_order_id',
            'global_options' => 'nullable|array',
            'global_options.tax_rate' => 'nullable|numeric|min:0|max:100',
            'global_options.notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->billingService->generateBulkBills(
                $validated['purchase_order_ids'],
                $validated['global_options'] ?? []
            );

            $message = $result['success']
                ? "Successfully generated {$result['success_count']} bills"
                : "Generated {$result['success_count']} bills with {$result['error_count']} errors";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk bill generation failed', [
                'purchase_order_ids' => $validated['purchase_order_ids'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk generation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing analytics and dashboard data
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $restaurantId = $this->getRestaurantId();
            $analytics = $this->billingService->getBillingAnalytics($restaurantId, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $analytics,
                ]);
            }

            return Inertia::render('Bills/Analytics', [
                'analytics' => $analytics,
            ]);

        } catch (\Exception $e) {
            Log::error('Billing analytics failed', ['error' => $e->getMessage()]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load analytics: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load analytics');
        }
    }

    /**
     * Auto-mark overdue bills (can be run via cron)
     */
    public function autoMarkOverdue()
    {
        try {
             $restaurantId = auth()->user()->restaurantData->id;
            $result = $this->billingService->markOverdueBills($restaurantId);

            return response()->json([
                'success' => true,
                'message' => "Marked {$result['marked_count']} bills as overdue",
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-mark overdue failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark overdue bills: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick payment recording
     */
    public function quickPayment(Request $request, $billId)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,paypal,online,other',
            'payment_date' => 'nullable|date',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $paymentData = array_merge($validated, [
                'payment_date' => $validated['payment_date'] ?? now(),
                'created_by_user_id' => auth()->id(),
            ]);

            $result = $this->billingService->recordPayment($billId, $paymentData);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment' => $result['payment'],
                    'bill' => $result['bill'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Quick payment failed', [
                'bill_id' => $billId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate PayPal payment for supplier bill
     */
    public function payWithPaypal(Request $request, SupplierBill $bill)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $bill->load('supplier');

        if ($bill->status === 'paid' || $bill->outstanding_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bill is already paid or has no outstanding amount',
            ], 400);
        }

        if ($validated['payment_amount'] > $bill->outstanding_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed outstanding amount',
            ], 400);
        }

        try {
            // Check if PayPal is configured
            $paypalConfig = config('paypal');
            if (empty($paypalConfig['sandbox']['client_id']) && empty($paypalConfig['live']['client_id'])) {
                throw new \Exception('PayPal credentials not configured. Please set PAYPAL_SANDBOX_CLIENT_ID and PAYPAL_SANDBOX_CLIENT_SECRET in your .env file.');
            }

            $provider = new PayPalClient;
            $provider->setApiCredentials($paypalConfig);
            $accessToken = $provider->getAccessToken();

            Log::info('PayPal client initialized successfully', [
                'mode' => $paypalConfig['mode'] ?? 'sandbox',
                'has_access_token' => ! empty($accessToken),
            ]);

            $returnUrl = route('bills.paypal.success', ['bill' => $bill->bill_id]);
            $cancelUrl = route('bills.paypal.cancel', ['bill' => $bill->bill_id]);

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => config('paypal.currency', 'USD'),
                            'value' => number_format($validated['payment_amount'], 2, '.', ''),
                        ],
                        'description' => "Payment for Bill #{$bill->bill_number} - {$bill->supplier->supplier_name}",
                        'invoice_id' => $bill->bill_number.'-'.time(),
                    ],
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'locale' => 'en-US',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = $provider->createOrder($orderData);

            Log::info('PayPal createOrder Response (Sandbox)', [
                'bill_id' => $bill->bill_id,
                'paypal_order_id' => $response['id'] ?? 'missing',
                'response_status' => $response['status'] ?? 'missing',
                'links_count' => isset($response['links']) ? count($response['links']) : 0,
            ]);

            if (! isset($response['id']) || ! isset($response['links'])) {
                Log::error('PayPal createOrder response missing required fields', [
                    'response' => $response,
                    'bill_id' => $bill->bill_id,
                ]);
                throw new \Exception('Invalid PayPal response - missing ID or links');
            }

            // Store payment details in session for processing after approval
            session([
                'paypal_bill_payment' => [
                    'bill_id' => $bill->bill_id,
                    'payment_amount' => $validated['payment_amount'],
                    'notes' => $validated['notes'],
                    'order_id' => $response['id'],
                ],
            ]);

            // Find approval URL
            $approvalUrl = null;
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            if (! $approvalUrl) {
                throw new \Exception('PayPal approval URL not found');
            }

            // Store the approval URL in session for frontend to access
            session()->flash('approval_url', $approvalUrl);

            return response()->json([
                'success' => true,
                'approval_url' => $approvalUrl,
                'order_id' => $response['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal bill payment initiation failed', [
                'bill_id' => $bill->bill_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'validated_data' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate PayPal payment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle successful PayPal payment
     */
    public function paypalSuccess(Request $request, SupplierBill $bill)
    {
        $orderId = $request->get('token') ?? $request->get('paymentId');
        $paymentData = session('paypal_bill_payment');

        Log::info('PayPal success callback', [
            'bill_id' => $bill->bill_id,
            'order_id' => $orderId,
            'request_params' => $request->all(),
            'session_data' => $paymentData,
        ]);

        if (! $paymentData || $paymentData['bill_id'] != $bill->bill_id) {
            return redirect()->route('bills.show', $bill->bill_id)
                ->with('error', 'Invalid payment session data');
        }

        if (! $orderId) {
            return redirect()->route('bills.show', $bill->bill_id)
                ->with('error', 'Missing PayPal order ID');
        }

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            Log::info('PayPal Payment Initiation (Sandbox)', [
                'bill_id' => $bill->bill_id,
                'payment_amount' => $paymentData['payment_amount'],
                'currency' => config('paypal.currency'),
                'mode' => config('paypal.mode'),
            ]);

            // Capture the payment
            $response = $provider->capturePaymentOrder($orderId);

            Log::info('PayPal capturePaymentOrder Response (Sandbox)', [
                'bill_id' => $bill->bill_id,
                'order_id' => $orderId,
                'response_status' => $response['status'] ?? 'missing',
                'response_id' => $response['id'] ?? 'missing',
                'full_response' => $response,
            ]);

            if (! isset($response['status']) || $response['status'] !== 'COMPLETED') {
                Log::error('PayPal payment capture failed - detailed response', [
                    'expected_status' => 'COMPLETED',
                    'actual_status' => $response['status'] ?? 'missing',
                    'response' => $response,
                    'order_id' => $orderId,
                ]);
                throw new \Exception('PayPal payment capture failed - Status: '.($response['status'] ?? 'unknown'));
            }

            // Use the BillingService to record payment consistently
            $paymentData['payment_method'] = 'paypal';
            $paymentData['transaction_reference'] = $response['id'];
            $paymentData['created_by_user_id'] = auth()->id();

            $result = $this->billingService->recordPayment($bill->bill_id, $paymentData);

            // Clear session data
            session()->forget('paypal_bill_payment');

            return redirect()->route('bills.show', $bill->bill_id)
                ->with('success', 'Payment completed successfully via PayPal');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('PayPal bill payment completion failed', [
                'bill_id' => $bill->bill_id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('bills.show', $bill->bill_id)
                ->with('error', 'Payment processing failed: '.$e->getMessage());
        }
    }

    /**
     * Handle cancelled PayPal payment
     */
    public function paypalCancel(Request $request, SupplierBill $bill)
    {
        // Clear session data
        session()->forget('paypal_bill_payment');

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('info', 'PayPal payment was cancelled');
    }

    /**
     * Download bill as PDF
     */
    public function downloadPdf(SupplierBill $bill)
    {
        // Load all necessary relationships
        $bill->load([
            'supplier',
            'purchaseOrder.items.ingredient',
            'payments.createdBy',
        ]);

        // Generate PDF from the view
        $pdf = Pdf::loadView('pdfs.supplier-bill', compact('bill'));

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Configure PDF options for better rendering
        $pdf->setOptions([
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
            'dpi' => 150,
        ]);

        // Generate filename
        $filename = 'bill-'.$bill->bill_number.'-'.date('Y-m-d').'.pdf';

        // Return PDF download response
        return $pdf->download($filename);
    }
}
