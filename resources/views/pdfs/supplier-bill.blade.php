<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $bill->bill_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #333;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .bill-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .bill-info-left, .bill-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .bill-info-right {
            text-align: right;
        }

        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .status.paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status.pending {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .status.partially_paid {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status.overdue {
            background-color: #f8d7da;
            color: #721c24;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
        }

        .items-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .items-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .amount-summary {
            margin-top: 30px;
            float: right;
            width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .amount-row.total {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            padding-top: 10px;
        }

        .amount-row.outstanding {
            color: #dc3545;
            font-weight: bold;
        }

        .amount-row.paid {
            color: #28a745;
        }

        .payment-history {
            clear: both;
            margin-top: 40px;
        }

        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SUPPLIER BILL</h1>
        <p>{{ config('app.name', 'Restaurant Management System') }}</p>
        <p>Generated on {{ now()->format('F j, Y g:i A') }}</p>
    </div>

    <div class="bill-info">
        <div class="bill-info-left">
            <div class="section-title">Bill Information</div>
            <div class="info-row">
                <span class="info-label">Bill Number:</span>
                <strong>{{ $bill->bill_number }}</strong>
            </div>
            @if($bill->supplier_invoice_number)
            <div class="info-row">
                <span class="info-label">Invoice Number:</span>
                {{ $bill->supplier_invoice_number }}
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Bill Date:</span>
                {{ $bill->bill_date ? date('F j, Y', strtotime($bill->bill_date)) : 'N/A' }}
            </div>
            <div class="info-row">
                <span class="info-label">Due Date:</span>
                <span @if($bill->is_overdue) style="color: #dc3545; font-weight: bold;" @endif>
                    {{ $bill->due_date ? date('F j, Y', strtotime($bill->due_date)) : 'N/A' }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="status {{ strtolower(str_replace(' ', '_', $bill->status)) }}">
                    {{ ucwords(str_replace('_', ' ', $bill->status)) }}
                </span>
            </div>
            @if($bill->is_overdue)
            <div class="info-row">
                <span class="info-label">Days Overdue:</span>
                <span style="color: #dc3545; font-weight: bold;">{{ $bill->days_overdue }} days</span>
            </div>
            @endif
        </div>

        <div class="bill-info-right">
            <div class="section-title">Supplier Information</div>
            <div class="info-row">
                <span class="info-label">Supplier:</span>
                <strong>{{ $bill->supplier->supplier_name }}</strong>
            </div>
            @if($bill->supplier->contact_number)
            <div class="info-row">
                <span class="info-label">Contact:</span>
                {{ $bill->supplier->contact_number }}
            </div>
            @endif
            @if($bill->supplier->email)
            <div class="info-row">
                <span class="info-label">Email:</span>
                {{ $bill->supplier->email }}
            </div>
            @endif
            @if($bill->supplier->payment_terms)
            <div class="info-row">
                <span class="info-label">Payment Terms:</span>
                {{ $bill->supplier->payment_terms }}
            </div>
            @endif
        </div>
    </div>

    @if($bill->purchase_order && $bill->purchase_order->items)
    <div class="section-title">Purchase Order Items</div>
    <p style="margin-bottom: 10px; color: #666;">
        From PO: {{ $bill->purchase_order->po_number }}
        ({{ $bill->purchase_order->order_date ? date('F j, Y', strtotime($bill->purchase_order->order_date)) : 'N/A' }})
    </p>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Ordered</th>
                <th class="text-center">Received</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->purchase_order->items as $item)
            <tr>
                <td>{{ $item->ingredient->ingredient_name ?? 'N/A' }}</td>
                <td class="text-center">{{ $item->ordered_quantity }} {{ $item->unit_of_measure }}</td>
                <td class="text-center">{{ $item->received_quantity }} {{ $item->unit_of_measure }}</td>
                <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">₱{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="clearfix">
        <div class="amount-summary">
            <div class="amount-row">
                <span>Subtotal:</span>
                <span>₱{{ number_format($bill->subtotal, 2) }}</span>
            </div>
            @if($bill->discount_amount > 0)
            <div class="amount-row" style="color: #28a745;">
                <span>Discount:</span>
                <span>-₱{{ number_format($bill->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="amount-row">
                <span>Tax:</span>
                <span>₱{{ number_format($bill->tax_amount, 2) }}</span>
            </div>
            <div class="amount-row total">
                <span>Total Amount:</span>
                <span>₱{{ number_format($bill->total_amount, 2) }}</span>
            </div>
            @if($bill->paid_amount > 0)
            <div class="amount-row paid">
                <span>Paid Amount:</span>
                <span>₱{{ number_format($bill->paid_amount, 2) }}</span>
            </div>
            @endif
            @if($bill->outstanding_amount > 0)
            <div class="amount-row outstanding">
                <span>Outstanding:</span>
                <span>₱{{ number_format($bill->outstanding_amount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($bill->payments && count($bill->payments) > 0)
    <div class="payment-history">
        <div class="section-title">Payment History</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>{{ $payment->payment_date ? date('M j, Y', strtotime($payment->payment_date)) : 'N/A' }}</td>
                    <td class="text-right">₱{{ number_format($payment->payment_amount, 2) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td>
                        <span class="status {{ $payment->status === 'completed' ? 'paid' : 'pending' }}">
                            {{ ucwords($payment->status) }}
                        </span>
                    </td>
                    <td>
                        @if($payment->created_by)
                            {{ $payment->created_by->first_name }} {{ $payment->created_by->last_name }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($bill->notes)
    <div class="notes">
        <div class="section-title" style="border: none; margin-bottom: 10px;">Notes</div>
        <p style="margin: 0;">{{ $bill->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>This document was generated automatically by {{ config('app.name', 'Restaurant Management System') }}</p>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>