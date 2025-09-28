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
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            background: white;
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
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .restaurant-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .bill-info > div {
            width: 48%;
        }

        .bill-info h3 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #2c3e50;
        }

        .bill-info p {
            margin-bottom: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            color: #2c3e50;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .totals {
            width: 350px;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .totals td {
            padding: 8px 15px;
            border-bottom: 1px solid #ddd;
        }

        .totals .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
            background-color: #f8f9fa;
        }

        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .payment-info h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .payment-details {
            display: flex;
            justify-content: space-between;
        }

        .payment-details > div {
            width: 48%;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
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

        .status-pending {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Print-specific styles */
        @media print {
            .container {
                max-width: none;
                margin: 0;
                padding: 15px;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table th {
                background-color: #f8f9fa !important;
            }

            .total-row {
                background-color: #f8f9fa !important;
            }

            .payment-info {
                background-color: #f8f9fa !important;
            }
        }

        /* Auto-print when page loads */
        @media print {
            .no-print {
                display: none;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Bill</button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="restaurant-name">{{ $restaurant->restaurant_name ?? 'Restaurant Name' }}</div>
            <div class="restaurant-details">{{ $restaurant->restaurant_address ?? 'Restaurant Address' }}</div>
            <div class="restaurant-details">Phone: {{ $restaurant->restaurant_phone ?? 'N/A' }}</div>
            @if($restaurant->restaurant_email)
                <div class="restaurant-details">Email: {{ $restaurant->restaurant_email }}</div>
            @endif
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
                @if($order->orderItems && $order->orderItems->count() > 0)
                    @foreach($order->orderItems as $item)
                    <tr>
                        <td>{{ $item->dish->dish_name ?? 'Unknown Item' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">₱{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center">No items found</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals -->
        @php
            $subtotal = $order->orderItems->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });
            $tax = $subtotal * 0.12; // 12% VAT

            // Check for temporary discount first, then stored discount
            $discountAmount = 0;
            $discountReason = '';
            $discountNotes = '';

            if (isset($tempDiscount) && $tempDiscount) {
                $discountAmount = $tempDiscount['amount'];
                $discountReason = $tempDiscount['reason'];
                $discountNotes = $tempDiscount['notes'] ?? '';
            } elseif ($order->discount_amount && $order->discount_amount > 0) {
                $discountAmount = $order->discount_amount;
                $discountReason = 'Discount Applied';
                $discountNotes = $payment->notes ?? '';
            }

            $finalTotal = $order->total_amount - $discountAmount;
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
                <td>Discount ({{ $discountReason }}):</td>
                <td class="text-right" style="color: #dc3545;">-₱{{ number_format($discountAmount, 2) }}</td>
            </tr>
            @if($discountNotes)
            <tr>
                <td>Discount Notes:</td>
                <td class="text-right" style="font-size: 12px; color: #666;">{{ $discountNotes }}</td>
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
            <p><strong>Thank you for dining with us!</strong></p>
            <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
            @if($order->status !== 'paid')
                <p style="margin-top: 10px;"><strong>This bill is pending payment</strong></p>
                @if(isset($tempDiscount) && $tempDiscount)
                    <p style="margin-top: 5px; color: #dc3545;"><strong>Amount to Pay: ₱{{ number_format($finalTotal, 2) }}</strong></p>
                    <p style="font-size: 12px; color: #666;">*Discount applied as shown above</p>
                @endif
            @endif
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };
    </script>
</body>
</html>