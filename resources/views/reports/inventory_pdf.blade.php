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
            border-bottom: 2px solid #059669;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #059669;
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
            color: #059669;
        }
        .alert-section {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .alert-section h2 {
            color: #dc2626;
            margin: 0 0 15px 0;
            font-size: 18px;
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
        .badge-danger {
            background-color: #fecaca;
            color: #dc2626;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #059669;
        }
        .text-red {
            color: #dc2626;
            font-weight: 600;
        }
        .text-green {
            color: #059669;
            font-weight: 600;
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
        <p>Generated on: {{ date('M d, Y \a\t g:i A') }}</p>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Items</h3>
            <div class="value">{{ number_format($data['summary']['total_items']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Low Stock Items</h3>
            <div class="value text-red">{{ number_format($data['summary']['low_stock_items']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Inventory Value</h3>
            <div class="value">₱{{ number_format($data['summary']['total_value'], 2) }}</div>
        </div>
        <div class="summary-card">
            <h3>Average Item Value</h3>
            <div class="value">₱{{ number_format($data['summary']['avg_value_per_item'], 2) }}</div>
        </div>
    </div>

    @if($data['summary']['low_stock_items'] > 0)
    <div class="alert-section">
        <h2>⚠️ Low Stock Alert</h2>
        <p>The following items require immediate attention due to low stock levels:</p>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th class="text-right">Current Stock</th>
                    <th class="text-right">Reorder Level</th>
                    <th class="text-right">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items']->where('stock_status', 'low') as $item)
                <tr>
                    <td>{{ $item->ingredient_name }}</td>
                    <td class="text-right text-red">{{ number_format($item->current_stock) }} {{ $item->base_unit }}</td>
                    <td class="text-right">{{ number_format($item->reorder_level) }} {{ $item->base_unit }}</td>
                    <td class="text-right">₱{{ number_format($item->total_value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="table-container">
        <h2>Complete Inventory</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th class="text-right">Current Stock</th>
                    <th class="text-right">Reorder Level</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Total Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $item)
                <tr>
                    <td>{{ $item->ingredient_name }}</td>
                    <td class="text-right {{ $item->stock_status === 'low' ? 'text-red' : 'text-green' }}">
                        {{ number_format($item->current_stock) }} {{ $item->base_unit }}
                    </td>
                    <td class="text-right">{{ number_format($item->reorder_level) }} {{ $item->base_unit }}</td>
                    <td class="text-right">₱{{ number_format($item->cost_per_unit, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->total_value, 2) }}</td>
                    <td>
                        <span class="badge {{ $item->stock_status === 'low' ? 'badge-danger' : 'badge-success' }}">
                            {{ $item->stock_status === 'low' ? 'Low Stock' : 'In Stock' }}
                        </span>
                    </td>
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