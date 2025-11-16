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
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
            padding: 20px;
        }

        .receipt {
            max-width: 300px;
            margin: 0 auto;
            background: white;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .restaurant-name {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }

        .restaurant-info {
            font-size: 10px;
            text-align: center;
            margin-bottom: 3px;
        }

        .divider {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .item-row {
            margin-bottom: 8px;
        }

        .item-name {
            font-size: 12px;
            margin-bottom: 2px;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-left: 10px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }

        .total-final {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 0;
            margin: 10px 0;
        }

        .footer-message {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
        }

        .highlight {
            font-weight: bold;
            font-size: 13px;
        }

        .payment-method {
            text-align: center;
            margin: 8px 0;
            font-size: 11px;
        }

        .tip-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin: 3px 0;
        }

        .asterisks {
            text-align: center;
            font-size: 10px;
            letter-spacing: 1px;
            margin: 5px 0;
        }

        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            .receipt {
                max-width: none;
                width: 80mm; /* Thermal printer width */
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
            font-family: Arial, sans-serif;
        }

        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Bill</button>

    <div class="receipt">
        <!-- Restaurant Header -->
        <div class="restaurant-name">{{ $restaurant->restaurant_name ?? 'RESTAURANT NAME' }}</div>
        <div class="restaurant-info">{{ $restaurant->address ?? 'Restaurant Address' }}</div>
        <div class="restaurant-info">{{ $restaurant->contact_number ?? '000-000-0000' }}</div>

        <div class="divider"></div>

        <!-- Order Type -->
        <div class="section-title">{{ $order->order_type ?? 'DINE IN' }}</div>

        <!-- Table Info -->
        @if($order->table)
        <div class="info-row">
            <span class="bold">Table: {{ $order->table->table_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span>Table #{{ $order->table->table_number ?? 'N/A' }}</span>
        </div>
        @endif

        <div class="divider"></div>

        <!-- Server & Order Info -->
        <div class="info-row">
            <span>Server: {{ $order->employee->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span>Check #{{ $order->order_id }}</span>
            <span>{{ $order->created_at->format('m/d/y') }}</span>
        </div>
        <div class="info-row">
            <span></span>
            <span>{{ $order->created_at->format('g:i A') }}</span>
        </div>

        <div class="divider"></div>

        <!-- Order Items -->
        @if($order->orderItems && $order->orderItems->count() > 0)
            @foreach($order->orderItems as $item)
            <div class="item-row">
                <div class="item-name">
                    {{ $item->quantity }} {{ $item->dish->dish_name ?? 'Item' }}
                    @if($item->variant)
                        <span style="font-size: 10px;">({{ $item->variant->size_name }})</span>
                    @endif
                </div>
                <div class="item-details">
                    <span></span>
                    <span>₱{{ number_format($item->quantity * $item->unit_price, 2) }}</span>
                </div>
            </div>
            @endforeach
        @endif

        <div class="divider"></div>

        <!-- Totals -->
        @php
            $subtotal = $order->orderItems->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });
            $tax = $subtotal * 0.12; // 12% VAT

            // Check for temporary discount first, then stored discount
            $discountAmount = 0;
            $discountReason = '';

            if (isset($tempDiscount) && $tempDiscount) {
                $discountAmount = $tempDiscount['amount'];
                $discountReason = $tempDiscount['reason'];
            } elseif ($order->discount_amount && $order->discount_amount > 0) {
                $discountAmount = $order->discount_amount;
                $discountReason = 'Discount';
            }

            // Base total without add-ons (order total minus discount)
            $baseWithoutDiscount = $order->total_amount - $discountAmount;

            // If we have a payment record with a final_amount, trust it for the final total
            $finalTotal = $baseWithoutDiscount;
            $addonAmount = 0;
            if (isset($payment) && $payment && $payment->final_amount !== null) {
                $finalTotal = $payment->final_amount;
                $addonAmount = max(0, $payment->final_amount - $baseWithoutDiscount);
            }
        @endphp

        <div class="totals-row">
            <span>Subtotal</span>
            <span>₱{{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Tax</span>
            <span>₱{{ number_format($tax, 2) }}</span>
        </div>
        @if($order->reservation_fee && $order->reservation_fee > 0)
        <div class="totals-row">
            <span>Reservation Fee</span>
            <span>₱{{ number_format($order->reservation_fee, 2) }}</span>
        </div>
        @endif
        @if($discountAmount > 0)
        <div class="totals-row" style="color: #000;">
            <span>Discount ({{ $discountReason }})</span>
            <span>-₱{{ number_format($discountAmount, 2) }}</span>
        </div>
        @endif
        @if(isset($addonAmount) && $addonAmount > 0)
        <div class="totals-row" style="color: #000;">
            <span>Add-ons</span>
            <span>₱{{ number_format($addonAmount, 2) }}</span>
        </div>
        @endif

        <div class="totals-row total-final">
            <span class="bold">Total</span>
            <span class="bold">₱{{ number_format($finalTotal, 2) }}</span>
        </div>

        <!-- Payment Info (if paid) -->
        @if($order->status === 'paid' && isset($payment))
        <div class="payment-method">
            {{ strtoupper($payment->payment_method ?? 'CASH') }} Card
        </div>
        <div class="totals-row">
            <span>Amount Paid</span>
            <span>₱{{ number_format($payment->amount_paid ?? $finalTotal, 2) }}</span>
        </div>
        @if($payment->payment_method === 'cash' && ($payment->change_amount ?? 0) > 0)
        <div class="totals-row bold">
            <span>Change</span>
            <span>₱{{ number_format($payment->change_amount, 2) }}</span>
        </div>
        @endif

        <div class="divider"></div>
        @endif

        <!-- Website/Contact -->
        <div class="footer-message bold highlight">
            {{ $restaurant->contact_number ?? 'Contact Us' }}
        </div>
        <div class="footer-message" style="font-size: 10px;">
            {{ $restaurant->address ?? '' }}
        </div>

        <div class="asterisks">
            ****************************
        </div>

        <!-- Tip Suggestions -->
        <div class="tip-row">
            <span>15% - ₱{{ number_format($finalTotal * 0.15, 2) }}</span>
            <span>18% - ₱{{ number_format($finalTotal * 0.18, 2) }}</span>
        </div>
        <div class="tip-row">
            <span>20% - ₱{{ number_format($finalTotal * 0.20, 2) }}</span>
            <span>25% - ₱{{ number_format($finalTotal * 0.25, 2) }}</span>
        </div>

        <div class="divider" style="margin-top: 15px;"></div>

        <!-- Thank You Message -->
        <div class="footer-message bold" style="margin-top: 10px;">
            THANK YOU FOR DINING WITH US!
        </div>
        <div class="footer-message" style="font-size: 10px; margin-top: 5px;">
            {{ now()->format('m/d/Y g:i A') }}
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
