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
            border-bottom: 2px solid #dc2626;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #dc2626;
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
            color: #dc2626;
        }
        .alert-section {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
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
            <h3>Total Incidents</h3>
            <div class="value">{{ number_format($data['summary']['total_incidents']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Cost</h3>
            <div class="value cost-highlight">₱{{ number_format($data['summary']['total_cost'], 2) }}</div>
        </div>
        <div class="summary-card">
            <h3>Damage Incidents</h3>
            <div class="value">{{ number_format($data['summary']['damage_incidents']) }}</div>
        </div>
        <div class="summary-card">
            <h3>Spoilage Incidents</h3>
            <div class="value">{{ number_format($data['summary']['spoilage_incidents']) }}</div>
        </div>
    </div>

    @if($data['summary']['total_cost'] > 1000)
    <div class="alert-section">
        <h2>⚠️ High Wastage Alert</h2>
        <p>The total wastage cost of <strong>₱{{ number_format($data['summary']['total_cost'], 2) }}</strong> exceeds the recommended threshold. Consider reviewing inventory management and storage practices.</p>
    </div>
    @endif

    <div class="table-container">
        <h2>Wastage by Ingredient</h2>
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
    </div>

    <div class="table-container">
        <h2>Breakdown by Type</h2>
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
    </div>

    <div class="table-container">
        <h2>Detailed Incident Log</h2>
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
        <p style="text-align: center; color: #666; font-style: italic;">
            Showing first 20 incidents of {{ $data['logs']->count() }} total incidents
        </p>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated automatically by the Restaurant Management System</p>
        <p>Regular monitoring of wastage patterns can help reduce costs and improve efficiency</p>
    </div>
</body>
</html>