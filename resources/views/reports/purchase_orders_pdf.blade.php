<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        .header .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin: 10px 0;
        }
        .header .report-period {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        .header .generated-date {
            font-size: 11px;
            color: #9ca3af;
            margin: 5px 0;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }
        .summary-row {
            display: table-row;
        }
        .summary-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .summary-label {
            display: table-cell;
            padding: 10px 15px;
            font-weight: 600;
            color: #374151;
            width: 60%;
            border: 1px solid #e5e7eb;
        }
        .summary-value {
            display: table-cell;
            padding: 10px 15px;
            text-align: right;
            font-weight: bold;
            color: #2563eb;
            border: 1px solid #e5e7eb;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background-color: #f3f4f6;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
            font-size: 10px;
        }
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #f59e0b;
        }
        .badge-approved {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .badge-ordered {
            background-color: #e0e7ff;
            color: #6366f1;
        }
        .badge-received {
            background-color: #d1fae5;
            color: #059669;
        }
        .badge-cancelled {
            background-color: #fecaca;
            color: #dc2626;
        }
        .alert-box {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .alert-title {
            color: #f59e0b;
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="report-title">{{ $title }}</div>
        <div class="report-period">Report Period: {{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }}</div>
        <div class="generated-date">Generated on: {{ date('M d, Y \a\t g:i A') }}</div>
    </div>

    <!-- Summary Section -->
    <div class="section-title">Summary</div>
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-label">Total Orders</div>
            <div class="summary-value">{{ number_format($data['summary']['total_orders']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Amount</div>
            <div class="summary-value">₱{{ number_format($data['summary']['total_amount'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Pending Orders</div>
            <div class="summary-value">{{ number_format($data['summary']['pending_orders']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Completed Orders</div>
            <div class="summary-value">{{ number_format($data['summary']['completed_orders']) }}</div>
        </div>
    </div>

    <!-- Orders by Status -->
    <div class="section-title">Orders by Status</div>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-right">Count</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['orders_by_status'] as $status => $count)
            <tr>
                <td>
                    <span class="badge badge-{{ $status }}">{{ ucfirst($status) }}</span>
                </td>
                <td class="text-right">{{ $count }}</td>
                <td class="text-right">
                    {{ $data['summary']['total_orders'] > 0 ? number_format(($count / $data['summary']['total_orders']) * 100, 1) : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Purchase Order Details -->
    <div class="section-title">Purchase Order Details</div>
    <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-right">Amount</th>
                    <th>Expected Delivery</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['orders'] as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->supplier->supplier_name ?? 'N/A' }}</td>
                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                    <td>
                        <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="text-right">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td>{{ $order->expected_delivery_date ? date('M d, Y', strtotime($order->expected_delivery_date)) : 'N/A' }}</td>
                </tr>
                @if($order->items && $order->items->filter(fn($item) => $item->received_quantity > 0)->count() > 0)
                <tr>
                    <td colspan="6" style="background-color: #f9fafb; padding-left: 30px; font-size: 12px;">
                        <strong>Received Items:</strong>
                        @php
                            $receivedItems = [];
                            foreach($order->items as $item) {
                                if($item->received_quantity > 0) {
                                    $receivedItems[] = $item->ingredient->ingredient_name . ' (' . number_format($item->received_quantity, 2) . ' ' . $item->unit_of_measure . ')';
                                }
                            }
                            echo implode(', ', $receivedItems);
                        @endphp
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @if($data['orders']->where('status', 'pending')->count() > 0)
    <!-- Pending Orders Alert -->
    <div class="alert-box">
        <div class="alert-title">⏳ Pending Orders Requiring Attention</div>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Days Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['orders']->where('status', 'pending') as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->supplier->supplier_name ?? 'N/A' }}</td>
                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                    <td class="text-right">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="text-center">{{ $order->created_at->diffInDays(now()) }} days</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        This report was generated automatically by the Restaurant Management System
    </div>
</body>
</html>