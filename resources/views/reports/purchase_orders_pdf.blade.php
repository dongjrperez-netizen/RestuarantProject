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
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #f59e0b;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9fafb;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #f59e0b;
        }
        .table-container {
            margin-bottom: 30px;
        }
        .table-container h2 {
            color: #374151;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
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
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Report Period: {{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }}</p>
        <p>Generated on: {{ date('M d, Y \a\t g:i A') }}</p>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Orders</h3>
            <div class="value">{{ number_format($data['summary']['total_orders']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Amount</h3>
            <div class="value">₱{{ number_format($data['summary']['total_amount'], 2) }}</div>
        </div>
        <div class="summary-card">
            <h3>Pending Orders</h3>
            <div class="value">{{ number_format($data['summary']['pending_orders']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Completed Orders</h3>
            <div class="value">{{ number_format($data['summary']['completed_orders']) }}</div>
        </div>
    </div>

    <div class="table-container">
        <h2>Orders by Status</h2>
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
    </div>

    <div class="table-container">
        <h2>Purchase Order Details</h2>
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
                @endforeach
            </tbody>
        </table>
    </div>

    @if($data['orders']->where('status', 'pending')->count() > 0)
    <div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h2 style="color: #f59e0b; margin: 0 0 15px 0;">⏳ Pending Orders Requiring Attention</h2>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th class="text-right">Amount</th>
                    <th>Days Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['orders']->where('status', 'pending') as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->supplier->supplier_name ?? 'N/A' }}</td>
                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                    <td class="text-right">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td>{{ $order->created_at->diffInDays(now()) }} days</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the Restaurant Management System</p>
    </div>
</body>
</html>