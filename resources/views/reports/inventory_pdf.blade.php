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
        .summary-value.danger {
            color: #dc2626;
        }
        .summary-value.warning {
            color: #d97706;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        .alert-box {
            background-color: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .alert-title {
            color: #dc2626;
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px 0;
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
        .badge-danger {
            background-color: #fecaca;
            color: #dc2626;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #059669;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #d97706;
        }
        .text-red {
            color: #dc2626;
            font-weight: 600;
        }
        .text-green {
            color: #059669;
            font-weight: 600;
        }
        .text-amber {
            color: #d97706;
            font-weight: 600;
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
        <div class="generated-date">Generated on: {{ date('M d, Y \a\t g:i A') }}</div>
    </div>

    <!-- Summary Section -->
    <div class="section-title">Summary</div>
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-label">Total Items</div>
            <div class="summary-value">{{ number_format($data['summary']['total_items']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Low Stock Items</div>
            <div class="summary-value danger">{{ number_format($data['summary']['low_stock_items']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Inventory Value</div>
            <div class="summary-value">₱{{ number_format($data['summary']['total_value'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Customer Exclusions</div>
            <div class="summary-value warning">{{ number_format($data['summary']['total_exclusions']) }}</div>
        </div>
    </div>

    @if($data['summary']['low_stock_items'] > 0)
    <!-- Low Stock Alert -->
    <div class="alert-box">
        <div class="alert-title">⚠️ Low Stock Alert</div>
        <p style="margin: 0 0 10px 0; font-size: 10px;">The following items require immediate attention due to low stock levels:</p>
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

    <!-- Complete Inventory -->
    <div class="section-title">Complete Inventory</div>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th class="text-right">Current Stock</th>
                <th class="text-right">Reorder Level</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Value</th>
                <th class="text-center">Exclusions</th>
                <th class="text-center">Status</th>
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
                <td class="text-center">
                    @if($item->exclusion_count > 0)
                        <span class="badge badge-warning">{{ $item->exclusion_count }}x</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge {{ $item->stock_status === 'low' ? 'badge-danger' : 'badge-success' }}">
                        {{ $item->stock_status === 'low' ? 'Low Stock' : 'In Stock' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        This report was generated automatically by the Restaurant Management System
    </div>
</body>
</html>
