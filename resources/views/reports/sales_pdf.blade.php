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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
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
            color: #2563eb;
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
        tr:hover {
            background-color: #f9fafb;
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
        .badge-primary {
            background-color: #dbeafe;
            color: #1d4ed8;
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
            <h3>Total Sales</h3>
            <div class="value">₱{{ number_format($data['summary']['total_sales'], 2) }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Orders</h3>
            <div class="value">{{ number_format($data['summary']['total_orders']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Average Order Value</h3>
            <div class="value">₱{{ number_format($data['summary']['avg_order_value'], 2) }}</div>
        </div>
        <div class="summary-card">
            <h3>Items Sold</h3>
            <div class="value">{{ number_format($data['summary']['total_items_sold']) }}</div>
        </div>
    </div>

    <div class="table-container">
        <h2>Sales Breakdown by Period</h2>
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="text-right">Sales</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Avg Order Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['chart_data'] as $item)
                <tr>
                    <td>{{ $item->period }}</td>
                    <td class="text-right">₱{{ number_format($item->total_sales, 2) }}</td>
                    <td class="text-right">{{ number_format($item->order_count) }}</td>
                    <td class="text-right">₱{{ number_format($item->avg_order_value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-container">
        <h2>Top Selling Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Item Name</th>
                    <th class="text-right">Quantity Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_items'] as $index => $item)
                <tr>
                    <td>
                        <span class="badge badge-primary">{{ $index + 1 }}</span>
                    </td>
                    <td>{{ $item->dish->dish_name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->total_quantity) }}</td>
                    <td class="text-right">₱{{ number_format($item->total_revenue, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This report was generated automatically by the Restaurant Management System</p>
    </div>
</body>
</html>