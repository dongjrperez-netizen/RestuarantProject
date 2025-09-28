<?php

use App\Http\Controllers\SupplierBillController;
use App\Http\Controllers\SupplierPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'verified', 'check.subscription'])->prefix('billing')->group(function () {

    // Enhanced Bill Management Routes
    Route::post('/bills/auto-generate', [SupplierBillController::class, 'autoGenerateFromPurchaseOrder'])
        ->name('bills.auto-generate');

    Route::post('/bills/process-received', [SupplierBillController::class, 'processReceived'])
        ->name('bills.process-received');

    Route::post('/bills/bulk-generate', [SupplierBillController::class, 'bulkGenerate'])
        ->name('bills.bulk-generate');

    Route::get('/bills/analytics', [SupplierBillController::class, 'analytics'])
        ->name('bills.analytics');

    Route::post('/bills/auto-mark-overdue', [SupplierBillController::class, 'autoMarkOverdue'])
        ->name('bills.auto-mark-overdue');

    Route::post('/bills/{bill}/quick-payment', [SupplierBillController::class, 'quickPayment'])
        ->name('bills.quick-payment');

    // Enhanced Payment Routes with BillingService integration
    Route::post('/payments/record-payment', [SupplierPaymentController::class, 'recordPayment'])
        ->name('payments.record-payment');

    Route::get('/payments/analytics', [SupplierPaymentController::class, 'paymentAnalytics'])
        ->name('payments.analytics');

    Route::post('/payments/{payment}/refund', [SupplierPaymentController::class, 'refundPayment'])
        ->name('payments.refund');

    // Bulk Operations
    Route::post('/bulk/mark-overdue', [SupplierBillController::class, 'bulkMarkOverdue'])
        ->name('billing.bulk.mark-overdue');

    Route::post('/bulk/send-reminders', [SupplierBillController::class, 'bulkSendReminders'])
        ->name('billing.bulk.send-reminders');

    // Reporting and Analytics
    Route::get('/reports/cash-flow', [SupplierBillController::class, 'cashFlowReport'])
        ->name('billing.reports.cash-flow');

    Route::get('/reports/supplier-summary', [SupplierBillController::class, 'supplierSummaryReport'])
        ->name('billing.reports.supplier-summary');

    Route::get('/reports/aging', [SupplierBillController::class, 'agingReport'])
        ->name('billing.reports.aging');

    // API Endpoints for AJAX calls
    Route::prefix('api')->group(function () {
        Route::get('/bills/{bill}/payment-history', [SupplierBillController::class, 'getPaymentHistory'])
            ->name('billing.api.payment-history');

        Route::get('/purchase-orders/available-for-billing', [SupplierBillController::class, 'getAvailablePurchaseOrders'])
            ->name('billing.api.available-pos');

        Route::post('/bills/validate-payment', [SupplierBillController::class, 'validatePayment'])
            ->name('billing.api.validate-payment');
    });
});
