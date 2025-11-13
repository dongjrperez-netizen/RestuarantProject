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
        .summary-value.danger {
            color: #dc2626;
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
        .badge-damage {
            background-color: #fecaca;
            color: #dc2626;
        }
        .badge-spoilage {
            background-color: #fed7aa;
            color: #ea580c;
        }
        .cost-highlight {
            font-weight: bold;
            color: #dc2626;
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
            <div class="summary-label">Total Incidents</div>
            <div class="summary-value danger">{{ number_format($data['summary']['total_incidents']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Cost</div>
            <div class="summary-value danger">₱{{ number_format($data['summary']['total_cost'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Damage Incidents</div>
            <div class="summary-value">{{ number_format($data['summary']['damage_incidents']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Spoilage Incidents</div>
            <div class="summary-value">{{ number_format($data['summary']['spoilage_incidents']) }}</div>
        </div>
    </div>

    @if($data['summary']['total_cost'] > 1000)
    <!-- High Wastage Alert -->
    <div class="alert-box">
        <div class="alert-title">⚠️ High Wastage Alert</div>
        <p style="margin: 0; font-size: 10px;">The total wastage cost of <strong>₱{{ number_format($data['summary']['total_cost'], 2) }}</strong> exceeds the recommended threshold. Consider reviewing inventory management and storage practices.</p>
    </div>
    @endif

    <!-- Wastage by Ingredient -->
    <div class="section-title">Wastage by Ingredient</div>
    <table>
        <thead>
            <tr>
                <th>Ingredient</th>
                <th class="text-right">Incidents</th>
                <th class="text-right">Total Quantity</th>
                <th class="text-right">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['by_ingredient'] as $ingredient => $stats)
            <tr>
                <td>{{ $ingredient }}</td>
                <td class="text-right">{{ $stats['count'] }}</td>
                <td class="text-right">{{ number_format($stats['total_quantity'], 2) }}</td>
                <td class="text-right cost-highlight">₱{{ number_format($stats['total_cost'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Breakdown by Type -->
    <div class="section-title">Breakdown by Type</div>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th class="text-right">Incidents</th>
                <th class="text-right">Total Cost</th>
                <th class="text-right">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['by_type'] as $type => $stats)
            <tr>
                <td>
                    <span class="badge badge-{{ $type }}">{{ ucfirst($type) }}</span>
                </td>
                <td class="text-right">{{ $stats['count'] }}</td>
                <td class="text-right cost-highlight">₱{{ number_format($stats['total_cost'], 2) }}</td>
                <td class="text-right">
                    {{ $data['summary']['total_cost'] > 0 ? number_format(($stats['total_cost'] / $data['summary']['total_cost']) * 100, 1) : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Detailed Incident Log -->
    <div class="section-title">Detailed Incident Log</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Ingredient</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Cost</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['logs']->take(20) as $log)
            <tr>
                <td>{{ date('M d, Y', strtotime($log->incident_date)) }}</td>
                <td>
                    <span class="badge badge-{{ $log->type }}">{{ ucfirst($log->type) }}</span>
                </td>
                <td>{{ $log->ingredient->ingredient_name ?? 'N/A' }}</td>
                <td class="text-right">
                    {{ number_format($log->quantity, 2) }} {{ $log->ingredient->base_unit ?? '' }}
                </td>
                <td class="text-right cost-highlight">₱{{ number_format($log->estimated_cost, 2) }}</td>
                <td>{{ $log->reason }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($data['logs']->count() > 20)
    <p style="text-align: center; color: #9ca3af; font-style: italic; margin: 0; font-size: 10px;">
        Showing first 20 incidents of {{ $data['logs']->count() }} total incidents
    </p>
    @endif

    <div class="footer">
        This report was generated automatically by the Restaurant Management System<br>
        Regular monitoring of wastage patterns can help reduce costs and improve efficiency
    </div>
</body>
</html>
