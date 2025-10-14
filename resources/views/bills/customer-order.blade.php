<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .restaurant-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .restaurant-details {
            font-size: 12px;
            color: #666;
        }

        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .bill-info > div {
            width: 48%;
        }

        .bill-info h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }

        .bill-info p {
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .totals {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals td {
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }

        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }

        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }

        .payment-info h3 {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .payment-details {
            display: flex;
            justify-content: space-between;
        }

        .payment-details > div {
            width: 48%;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-ready {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="restaurant-name">{{ $restaurant->restaurant_name ?? 'Restaurant Name' }}</div>
            <div class="restaurant-details">
                {{ $restaurant->restaurant_address ?? 'Restaurant Address' }}<br>
                Phone: {{ $restaurant->restaurant_phone ?? 'N/A' }}<br>
                Email: {{ $restaurant->restaurant_email ?? 'N/A' }}
            </div>
        </div>

        <!-- Bill Information -->
        <div class="bill-info">
            <div>
                <h3>Bill Details</h3>
                <p><strong>Bill Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('F j, Y g:i A') }}</p>
                <p><strong>Table:</strong> {{ $order->table->table_name ?? 'N/A' }}</p>
                <p><strong>Served by:</strong> {{ $order->employee->name ?? 'N/A' }}</p>
            </div>
            <div>
                <h3>Customer Information</h3>
                <p><strong>Customer:</strong> {{ $order->customer_name ?? 'Walk-in Customer' }}</p>
                <p><strong>Status:</strong>
                    <span class="status-badge status-{{ strtolower($order->status) }}">
                        {{ strtoupper($order->status) }}
                    </span>
                </p>
                @if($order->status === 'paid' && $order->paid_at)
                    <p><strong>Paid At:</strong> {{ \Carbon\Carbon::parse($order->paid_at)->format('F j, Y g:i A') }}</p>
                @endif
            </div>
        </div>

        <!-- Order Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%">Item Description</th>
                    <th style="width: 15%" class="text-center">Qty</th>
                    <th style="width: 15%" class="text-right">Unit Price</th>
                    <th style="width: 20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                <tr>
                    <td>
                        {{ $item->dish->dish_name ?? 'Unknown Item' }}
                        @if($item->variant)
                            <br><span style="font-size: 10px; color: #0066cc;">({{ $item->variant->size_name }})</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        @php
            $subtotal = $order->orderItems->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });
            $tax = $subtotal * 0.12; // 12% VAT
            $discountAmount = $order->discount_amount ?? 0;
            $finalTotal = $order->total_amount;
        @endphp

        <table class="totals">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">₱{{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Tax (12% VAT):</td>
                <td class="text-right">₱{{ number_format($tax, 2) }}</td>
            </tr>
            @if($discountAmount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right" style="color: #dc3545;">-₱{{ number_format($discountAmount, 2) }}</td>
            </tr>
            @if($order->discount_reason)
            <tr>
                <td>Discount Reason:</td>
                <td class="text-right" style="font-size: 10px;">{{ $order->discount_reason }}</td>
            </tr>
            @endif
            @endif
            <tr class="total-row">
                <td><strong>TOTAL AMOUNT:</strong></td>
                <td class="text-right"><strong>₱{{ number_format($finalTotal, 2) }}</strong></td>
            </tr>
        </table>

        <!-- Payment Information (if paid) -->
        @if($order->status === 'paid')
        <div class="payment-info">
            <h3>Payment Information</h3>
            <div class="payment-details">
                <div>
                    <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? 'N/A') }}</p>
                    <p><strong>Amount Paid:</strong> ₱{{ number_format($order->amount_paid ?? 0, 2) }}</p>
                    @if($order->payment_method === 'cash' && ($order->amount_paid ?? 0) > $finalTotal)
                        <p><strong>Change:</strong> ₱{{ number_format(($order->amount_paid ?? 0) - $finalTotal, 2) }}</p>
                    @endif
                </div>
                <div>
                    @if($order->cashier_id)
                        <p><strong>Processed by:</strong> Cashier #{{ $order->cashier_id }}</p>
                    @endif
                    @if($order->payment_notes)
                        <p><strong>Notes:</strong> {{ $order->payment_notes }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for dining with us!</p>
            <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
            @if($order->status !== 'paid')
                <p><strong>This bill is pending payment</strong></p>
            @endif
        </div>
    </div>
</body>
</html>