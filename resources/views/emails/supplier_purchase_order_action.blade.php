<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order - Action Required</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">Purchase Order</h1>
        <p style="color: #f0f0f0; margin: 10px 0 0 0;">Action Required</p>
    </div>

    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Hello <strong>{{ $notifiableName }}</strong>,</p>

        <p>You have received a purchase order from:</p>
        <p style="font-size: 18px; font-weight: bold; color: #667eea; margin: 10px 0;">{{ $purchaseOrder->restaurant->restaurant_name }}</p>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>PO Number:</strong> {{ $purchaseOrder->po_number }}</p>
            <p style="margin: 5px 0;"><strong>Order Date:</strong> {{ $purchaseOrder->order_date->format('F d, Y') }}</p>
            @if($purchaseOrder->expected_delivery_date)
            <p style="margin: 5px 0;"><strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_delivery_date->format('F d, Y') }}</p>
            @endif
            <p style="margin: 5px 0;"><strong>Total Amount:</strong> <span style="font-size: 20px; color: #10b981;">₱{{ number_format($purchaseOrder->total_amount, 2) }}</span></p>
        </div>

        <h3 style="color: #333; margin-top: 30px; margin-bottom: 15px;">Order Items:</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <thead>
                <tr style="background: #667eea; color: white;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Item</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Quantity</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Unit</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Unit Price</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                <tr style="background: {{ $loop->even ? '#f9f9f9' : 'white' }};">
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>{{ $item->ingredient->ingredient_name ?? 'N/A' }}</strong>
                        @if($item->notes)
                        <br><span style="font-size: 12px; color: #666;">{{ $item->notes }}</span>
                        @endif
                    </td>
                    <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">{{ number_format($item->ordered_quantity, 2) }}</td>
                    <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">{{ $item->unit_of_measure }}</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd; font-weight: bold;">₱{{ number_format($item->ordered_quantity * $item->unit_price, 2) }}</td>
                </tr>
                @endforeach
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="4" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Subtotal:</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₱{{ number_format($purchaseOrder->subtotal, 2) }}</td>
                </tr>
                @if($purchaseOrder->tax_amount > 0)
                <tr>
                    <td colspan="4" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Tax:</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₱{{ number_format($purchaseOrder->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($purchaseOrder->shipping_amount > 0)
                <tr>
                    <td colspan="4" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Shipping:</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd;">₱{{ number_format($purchaseOrder->shipping_amount, 2) }}</td>
                </tr>
                @endif
                @if($purchaseOrder->discount_amount > 0)
                <tr>
                    <td colspan="4" style="padding: 12px; text-align: right; border: 1px solid #ddd;">Discount:</td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #ddd; color: #ef4444;">-₱{{ number_format($purchaseOrder->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr style="background: #667eea; color: white; font-size: 16px;">
                    <td colspan="4" style="padding: 15px; text-align: right; border: 1px solid #ddd;"><strong>TOTAL:</strong></td>
                    <td style="padding: 15px; text-align: right; border: 1px solid #ddd;"><strong>₱{{ number_format($purchaseOrder->total_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if($purchaseOrder->notes)
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #856404;"><strong>Notes:</strong></p>
            <p style="margin: 5px 0 0 0; color: #856404;">{{ $purchaseOrder->notes }}</p>
        </div>
        @endif

        @if($purchaseOrder->delivery_instructions)
        <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #0c5460;"><strong>Delivery Instructions:</strong></p>
            <p style="margin: 5px 0 0 0; color: #0c5460;">{{ $purchaseOrder->delivery_instructions }}</p>
        </div>
        @endif

        <p style="margin-top: 30px; margin-bottom: 20px;">Please review and respond to this purchase order:</p>

        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; text-align: center;">
                    <a href="{{ $confirmUrl }}" style="display: inline-block; background: #10b981; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                        ✓ Confirm Order
                    </a>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <a href="{{ $rejectUrl }}" style="display: inline-block; background: #ef4444; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                        ✕ Reject Order
                    </a>
                </td>
            </tr>
        </table>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="font-size: 12px; color: #666;"><strong>If the buttons above don't work, you can copy and paste these links:</strong></p>
            <p style="font-size: 12px; color: #666; word-break: break-all;">
                <strong>Confirm:</strong> {{ $confirmUrl }}
            </p>
            <p style="font-size: 12px; color: #666; word-break: break-all;">
                <strong>Reject:</strong> {{ $rejectUrl }}
            </p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">⏰ These links will expire in 7 days.</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
        <p>Thank you,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
