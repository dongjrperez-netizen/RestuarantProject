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
        .summary-value.success {
            color: #16a34a;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        .info-box {
            background-color: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .info-title {
            color: #1e40af;
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
        .badge-excellent {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-good {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-fair {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-poor {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .value-highlight {
            font-weight: bold;
            color: #16a34a;
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
            <div class="summary-label">Total Stock-In Transactions</div>
            <div class="summary-value">{{ number_format($data['summary']['total_transactions']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Value Received</div>
            <div class="summary-value success">₱{{ number_format($data['summary']['total_value'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Packages Received</div>
            <div class="summary-value">{{ number_format($data['summary']['total_packages']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Unique Ingredients</div>
            <div class="summary-value">{{ number_format($data['summary']['unique_ingredients']) }}</div>
        </div>
    </div>

    @if($data['summary']['total_value'] > 10000)
    <!-- High Value Alert -->
    <div class="info-box">
        <div class="info-title">ℹ️ Inventory Investment</div>
        <p style="margin: 0; font-size: 10px;">During this period, you received inventory worth <strong>₱{{ number_format($data['summary']['total_value'], 2) }}</strong> across {{ $data['summary']['total_transactions'] }} transactions. This represents your investment in maintaining optimal stock levels.</p>
    </div>
    @endif

    <!-- Detailed Stock-In Records -->
    <div class="section-title">Detailed Stock-In History</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 12%;">PO Number</th>
                <th style="width: 18%;">Ingredient</th>
                <th style="width: 15%;">Supplier</th>
                <th class="text-right" style="width: 10%;">Qty Received</th>
                <th class="text-right" style="width: 12%;">Stock Increase</th>
                <th class="text-right" style="width: 10%;">Cost/Unit</th>
                <th class="text-right" style="width: 13%;">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['records'] as $record)
            <tr>
                <td>{{ date('M d, Y', strtotime($record['date_received'])) }}</td>
                <td>{{ $record['po_number'] }}</td>
                <td>{{ $record['ingredient_name'] }}</td>
                <td>{{ $record['supplier_name'] }}</td>
                <td class="text-right">{{ number_format($record['quantity_received'], 2) }} {{ $record['unit'] }}</td>
                <td class="text-right">{{ number_format($record['stock_increase'], 2) }} {{ $record['base_unit'] }}</td>
                <td class="text-right">₱{{ number_format($record['cost_per_unit'], 2) }}</td>
                <td class="text-right value-highlight">₱{{ number_format($record['total_value'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($data['records']) === 0)
    <p style="text-align: center; color: #9ca3af; font-style: italic; margin: 20px 0; font-size: 10px;">
        No stock-in records found for the selected period
    </p>
    @endif

    <!-- Quality Summary (if applicable) -->
    @php
        $qualityRecords = collect($data['records'])->filter(function($record) {
            return !empty($record['quality_rating']);
        })->values()->all();
    @endphp

    @if(count($qualityRecords) > 0)
    <div class="section-title">Quality Ratings Summary</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Ingredient</th>
                <th>Supplier</th>
                <th>Quality Rating</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($qualityRecords as $record)
            <tr>
                <td>{{ date('M d, Y', strtotime($record['date_received'])) }}</td>
                <td>{{ $record['ingredient_name'] }}</td>
                <td>{{ $record['supplier_name'] }}</td>
                <td>
                    @if($record['quality_rating'])
                        <span class="badge badge-{{ strtolower($record['quality_rating']) }}">
                            {{ ucfirst($record['quality_rating']) }}
                        </span>
                    @else
                        <span style="color: #9ca3af;">N/A</span>
                    @endif
                </td>
                <td style="font-size: 9px;">{{ $record['condition_notes'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        This report was generated automatically by the Restaurant Management System<br>
        Stock-in history helps track inventory receipts and supplier performance
    </div>
</body>
</html>
