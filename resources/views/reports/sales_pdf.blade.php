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
        .badge-primary {
            background-color: #dbeafe;
            color: #1d4ed8;
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
            <div class="summary-label">Total Sales</div>
            <div class="summary-value">PHP {{ number_format($data['summary']['total_sales'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Orders</div>
            <div class="summary-value">{{ number_format($data['summary']['total_orders']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Average Order Value</div>
            <div class="summary-value">PHP {{ number_format($data['summary']['avg_order_value'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Items Sold</div>
            <div class="summary-value">{{ number_format($data['summary']['total_items_sold']) }}</div>
        </div>
    </div>

    <!-- Sales Breakdown by Period -->
    <div class="section-title">Sales Breakdown by Period</div>
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
                <td class="text-right">PHP {{ number_format($item->total_sales, 2) }}</td>
                <td class="text-right">{{ number_format($item->order_count) }}</td>
                <td class="text-right">PHP {{ number_format($item->avg_order_value, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Top Selling Items -->
    <div class="section-title">Top Selling Items</div>
    <table>
        <thead>
            <tr>
                <th class="text-center">Rank</th>
                <th>Item Name</th>
                <th class="text-right">Quantity Sold</th>
                <th class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['top_items'] as $index => $item)
            <tr>
                <td class="text-center">
                    <span class="badge badge-primary">{{ $index + 1 }}</span>
                </td>
                <td>{{ $item->dish->dish_name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($item->total_quantity) }}</td>
                <td class="text-right">PHP{{ number_format($item->total_revenue, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        This report was generated automatically by the Restaurant Management System
    </div>
</body>
</html>
