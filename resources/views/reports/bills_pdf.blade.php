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
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 20px;
        }
        .header .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #f59e0b;
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
            color: #f59e0b;
            border: 1px solid #e5e7eb;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #92400e;
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
            background-color: #fef3c7;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: #92400e;
            border: 1px solid #fde68a;
            font-size: 10px;
        }
        td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #fffbeb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-semibold {
            font-weight: 600;
        }
        .text-green {
            color: #16a34a;
        }
        .text-orange {
            color: #ea580c;
        }
        .text-red {
            color: #dc2626;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-partially-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="report-title">{{ $title }}</div>
        <div class="report-period">
            Period: {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}
        </div>
        <div class="generated-date">Generated on {{ now()->format('F d, Y \a\t h:i A') }}</div>
    </div>

    <!-- Summary Section -->
    <div class="section-title">Summary Overview</div>
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-label">Total Bills</div>
            <div class="summary-value">{{ number_format($data['summary']['total_bills']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Billed Amount</div>
            <div class="summary-value">PHP {{ number_format($data['summary']['total_amount'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Paid</div>
            <div class="summary-value text-green">PHP {{ number_format($data['summary']['total_paid'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total Outstanding</div>
            <div class="summary-value text-orange">PHP {{ number_format($data['summary']['total_outstanding'], 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Overdue Bills</div>
            <div class="summary-value text-red">{{ $data['summary']['overdue_count'] }} (PHP {{ number_format($data['summary']['overdue_amount'], 2) }})</div>
        </div>
    </div>

    <!-- Bills by Supplier -->
    @if(count($data['by_supplier']) > 0)
    <div class="section-title">Bills by Supplier</div>
    <table>
        <thead>
            <tr>
                <th>Supplier Name</th>
                <th class="text-center">Bills Count</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Paid Amount</th>
                <th class="text-right">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['by_supplier'] as $supplierName => $supplierData)
            <tr>
                <td class="font-semibold">{{ $supplierName }}</td>
                <td class="text-center">{{ $supplierData['count'] }}</td>
                <td class="text-right">PHP {{ number_format($supplierData['total_amount'], 2) }}</td>
                <td class="text-right text-green">PHP {{ number_format($supplierData['paid_amount'], 2) }}</td>
                <td class="text-right text-orange">PHP {{ number_format($supplierData['outstanding_amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Bills Detail Table -->
    <div class="section-title">Bills Detail</div>
    <table>
        <thead>
            <tr>
                <th>Bill Number</th>
                <th>Supplier</th>
                <th>PO Number</th>
                <th>Bill Date</th>
                <th>Due Date</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Outstanding</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['bills'] as $bill)
            <tr>
                <td class="font-semibold">{{ $bill->bill_number }}</td>
                <td>
                    @if($bill->supplier)
                        {{ $bill->supplier->supplier_name }}
                    @else
                        @php
                            $supplierName = 'Unknown Supplier';
                            if ($bill->notes && preg_match('/manual receive\s*-\s*([^|]+)/i', $bill->notes, $matches)) {
                                $supplierName = trim($matches[1]) . ' (Unregistered)';
                            }
                        @endphp
                        {{ $supplierName }}
                    @endif
                </td>
                <td>{{ $bill->purchase_order ? $bill->purchase_order->po_number : 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($bill->bill_date)->format('M d, Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') }}</td>
                <td class="text-right">PHP {{ number_format($bill->total_amount, 2) }}</td>
                <td class="text-right text-green">PHP {{ number_format($bill->paid_amount, 2) }}</td>
                <td class="text-right text-orange">PHP {{ number_format($bill->outstanding_amount, 2) }}</td>
                <td>
                    @php
                        $statusClass = 'status-pending';
                        $statusLabel = ucfirst(str_replace('_', ' ', $bill->status));

                        if ($bill->is_overdue) {
                            $statusClass = 'status-overdue';
                            $statusLabel = 'Overdue';
                        } elseif ($bill->status === 'paid') {
                            $statusClass = 'status-paid';
                        } elseif ($bill->status === 'partially_paid') {
                            $statusClass = 'status-partially-paid';
                        }
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #9ca3af;">
                    No bills found for the selected period
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>This is a system-generated report from {{ config('app.name') }}</div>
        <div>For internal use only</div>
    </div>
</body>
</html>
