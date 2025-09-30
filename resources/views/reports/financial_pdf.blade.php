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
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #7c3aed;
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
            color: #7c3aed;
        }
        .profit-card {
            grid-column: span 2;
            text-align: center;
            background-color: #f0fdf4;
            border-color: #22c55e;
        }
        .profit-card.negative {
            background-color: #fef2f2;
            border-color: #ef4444;
        }
        .profit-card .value {
            color: #22c55e;
            font-size: 32px;
        }
        .profit-card.negative .value {
            color: #ef4444;
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
        .text-green {
            color: #22c55e;
            font-weight: 600;
        }
        .text-red {
            color: #ef4444;
            font-weight: 600;
        }
        .highlight-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
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

    <div class="highlight-box">
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Revenue</h3>
                <div class="value text-green">₱{{ number_format($data['summary']['revenue'], 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Total Expenses</h3>
                <div class="value text-red">₱{{ number_format($data['summary']['expenses'], 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Wastage Cost</h3>
                <div class="value text-red">₱{{ number_format($data['summary']['wastage_cost'], 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Profit Margin</h3>
                <div class="value {{ $data['summary']['profit_margin'] >= 0 ? 'text-green' : 'text-red' }}">
                    {{ number_format($data['summary']['profit_margin'], 2) }}%
                </div>
            </div>
            <div class="summary-card profit-card {{ $data['summary']['net_profit'] < 0 ? 'negative' : '' }}">
                <h3>Net Profit</h3>
                <div class="value">
                    {{ $data['summary']['net_profit'] >= 0 ? '+' : '' }}₱{{ number_format($data['summary']['net_profit'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2>Daily Revenue Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Avg Per Order</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_breakdown'] as $day)
                <tr>
                    <td>{{ date('M d, Y', strtotime($day->date)) }}</td>
                    <td class="text-right">₱{{ number_format($day->daily_revenue, 2) }}</td>
                    <td class="text-right">{{ number_format($day->daily_orders) }}</td>
                    <td class="text-right">
                        ₱{{ $day->daily_orders > 0 ? number_format($day->daily_revenue / $day->daily_orders, 2) : '0.00' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="highlight-box">
        <h2>Financial Summary</h2>
        <table style="background-color: white;">
            <tbody>
                <tr>
                    <td><strong>Revenue (Income from Sales)</strong></td>
                    <td class="text-right text-green"><strong>₱{{ number_format($data['summary']['revenue'], 2) }}</strong></td>
                </tr>
                <tr>
                    <td>Less: Operating Expenses</td>
                    <td class="text-right text-red">(₱{{ number_format($data['summary']['expenses'], 2) }})</td>
                </tr>
                <tr>
                    <td>Less: Wastage & Spoilage</td>
                    <td class="text-right text-red">(₱{{ number_format($data['summary']['wastage_cost'], 2) }})</td>
                </tr>
                <tr style="border-top: 2px solid #374151;">
                    <td><strong>Net Profit</strong></td>
                    <td class="text-right {{ $data['summary']['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                        <strong>{{ $data['summary']['net_profit'] >= 0 ? '+' : '' }}₱{{ number_format($data['summary']['net_profit'], 2) }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This report was generated automatically by the Restaurant Management System</p>
        <p>All financial figures are presented in PHP (Philippine Peso)</p>
    </div>
</body>
</html>