<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\DamageSpoilageLog;
use App\Models\Ingredients;
use App\Models\PurchaseOrder;
use App\Models\SupplierBill;
use App\Models\Restaurant_Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportsController extends Controller
{
    protected function getRestaurantId()
    {
        $user = Auth::user();
        return $user->restaurant_id ?? ($user->restaurantData->id ?? null);
    }

    public function index()
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        // Get basic stats for dashboard overview
        $overview = $this->getDashboardOverview($restaurantId);

        return Inertia::render('Reports/Index', [
            'overview' => $overview,
        ]);
    }

    public function sales(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        \Log::info('Sales Report Query', [
            'restaurant_id' => $restaurantId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'group_by' => $groupBy,
        ]);

        $salesData = $this->getSalesData($restaurantId, $dateFrom, $dateTo, $groupBy);

        \Log::info('Sales Data Result', [
            'total_sales' => $salesData['summary']['total_sales'],
            'total_orders' => $salesData['summary']['total_orders'],
            'chart_data_count' => count($salesData['chart_data']),
        ]);

        if ($request->get('export')) {
            return $this->exportSalesReport($salesData, $request->get('export'), $dateFrom, $dateTo);
        }

        return Inertia::render('Reports/Sales', [
            'salesData' => $salesData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
        ]);
    }

    public function inventory(Request $request)
    {
        $user = Auth::user();
        \Log::info('User ID: ' . $user->id);
        \Log::info('User restaurant_id: ' . ($user->restaurant_id ?? 'NULL'));
        \Log::info('User restaurantData: ' . ($user->restaurantData ? $user->restaurantData->id : 'NULL'));

        $restaurantId = $this->getRestaurantId();
        \Log::info('Final Restaurant ID: ' . $restaurantId);

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        // Test query
        $testCount = Ingredients::where('restaurant_id', $restaurantId)->count();
        \Log::info('Test ingredient count: ' . $testCount);

        $inventoryData = $this->getInventoryData($restaurantId);

        \Log::info('Inventory Data Summary:', [
            'total_items' => $inventoryData['summary']['total_items'],
            'low_stock_items' => $inventoryData['summary']['low_stock_items'],
            'total_value' => $inventoryData['summary']['total_value'],
            'items_count' => count($inventoryData['items']),
        ]);

        if ($request->get('export')) {
            return $this->exportInventoryReport($inventoryData, $request->get('export'));
        }

        return Inertia::render('Reports/Inventory', [
            'inventoryData' => $inventoryData,
        ]);
    }

    public function purchaseOrders(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());
        $status = $request->get('status', 'all');

        $purchaseData = $this->getPurchaseOrdersData($restaurantId, $dateFrom, $dateTo, $status);

        if ($request->get('export')) {
            return $this->exportPurchaseOrdersReport($purchaseData, $request->get('export'), $dateFrom, $dateTo);
        }

        return Inertia::render('Reports/PurchaseOrders', [
            'purchaseData' => $purchaseData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ],
        ]);
    }

    public function financial(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());

        $financialData = $this->getFinancialData($restaurantId, $dateFrom, $dateTo);

        if ($request->get('export')) {
            return $this->exportFinancialReport($financialData, $request->get('export'), $dateFrom, $dateTo);
        }

        return Inertia::render('Reports/Financial', [
            'financialData' => $financialData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function wastage(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());
        $type = $request->get('type', 'all');

        $wastageData = $this->getWastageData($restaurantId, $dateFrom, $dateTo, $type);

        if ($request->get('export')) {
            return $this->exportWastageReport($wastageData, $request->get('export'), $dateFrom, $dateTo);
        }

        return Inertia::render('Reports/Wastage', [
            'wastageData' => $wastageData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'type' => $type,
            ],
        ]);
    }

    // Private helper methods for data collection

    private function getDashboardOverview($restaurantId)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'sales' => [
                'today' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->whereDate('created_at', $today)
                    ->sum('total_amount') ?? 0,
                'this_month' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('total_amount') ?? 0,
            ],
            'orders' => [
                'today' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->whereDate('created_at', $today)
                    ->count(),
                'this_month' => CustomerOrder::where('restaurant_id', $restaurantId)
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
            ],
            'inventory' => [
                'low_stock_items' => Ingredients::where('restaurant_id', $restaurantId)
                    ->whereColumn('current_stock', '<=', 'reorder_level')
                    ->count(),
                'total_value' => Ingredients::where('restaurant_id', $restaurantId)
                    ->selectRaw('SUM(current_stock * cost_per_unit) as total')
                    ->value('total') ?? 0,
            ],
            'wastage' => [
                'this_month_cost' => DamageSpoilageLog::where('restaurant_id', $restaurantId)
                    ->where('incident_date', '>=', $thisMonth)
                    ->sum('estimated_cost') ?? 0,
                'this_month_count' => DamageSpoilageLog::where('restaurant_id', $restaurantId)
                    ->where('incident_date', '>=', $thisMonth)
                    ->count(),
            ],
        ];
    }

    private function getSalesData($restaurantId, $dateFrom, $dateTo, $groupBy)
    {
        $query = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled');

        \Log::info('Sales Query Debug', [
            'restaurant_id' => $restaurantId,
            'date_from' => $dateFrom . ' 00:00:00',
            'date_to' => $dateTo . ' 23:59:59',
            'raw_count' => CustomerOrder::where('restaurant_id', $restaurantId)->count(),
            'filtered_count' => (clone $query)->count(),
        ]);

        // Group sales data by specified period
        $groupFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $salesByPeriod = (clone $query)
            ->selectRaw("DATE_FORMAT(created_at, '$groupFormat') as period")
            ->selectRaw('SUM(total_amount) as total_sales')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('AVG(total_amount) as avg_order_value')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Top selling items
        $topItems = CustomerOrderItem::whereHas('order', function($q) use ($restaurantId, $dateFrom, $dateTo) {
                $q->where('restaurant_id', $restaurantId)
                  ->whereBetween('created_at', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->with('dish')
            ->selectRaw('dish_id, SUM(quantity) as total_quantity, SUM(quantity * unit_price) as total_revenue')
            ->groupBy('dish_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        // Summary statistics
        $summary = [
            'total_sales' => $query->sum('total_amount') ?? 0,
            'total_orders' => $query->count(),
            'avg_order_value' => $query->avg('total_amount') ?? 0,
            'total_items_sold' => CustomerOrderItem::whereHas('order', function($q) use ($restaurantId, $dateFrom, $dateTo) {
                $q->where('restaurant_id', $restaurantId)
                  ->whereBetween('created_at', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            })->sum('quantity') ?? 0,
        ];

        return [
            'chart_data' => $salesByPeriod,
            'top_items' => $topItems,
            'summary' => $summary,
        ];
    }

    private function getInventoryData($restaurantId)
    {
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->selectRaw('*')
            ->selectRaw('(current_stock * cost_per_unit) as total_value')
            ->selectRaw('CASE WHEN current_stock <= reorder_level THEN "low" ELSE "normal" END as stock_status')
            ->orderBy('ingredient_name')
            ->get();

        $summary = [
            'total_items' => $ingredients->count(),
            'low_stock_items' => $ingredients->where('stock_status', 'low')->count(),
            'total_value' => $ingredients->sum('total_value'),
            'avg_value_per_item' => $ingredients->avg('total_value') ?? 0,
        ];

        $stockLevels = $ingredients->groupBy('stock_status')->map->count();

        return [
            'items' => $ingredients,
            'summary' => $summary,
            'stock_levels' => $stockLevels,
        ];
    }

    private function getPurchaseOrdersData($restaurantId, $dateFrom, $dateTo, $status)
    {
        $query = PurchaseOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with(['supplier', 'items.ingredient']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $summary = [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('total_amount'),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'completed_orders' => $orders->where('status', 'received')->count(),
        ];

        $ordersByStatus = $orders->groupBy('status')->map->count();

        return [
            'orders' => $orders,
            'summary' => $summary,
            'orders_by_status' => $ordersByStatus,
        ];
    }

    private function getFinancialData($restaurantId, $dateFrom, $dateTo)
    {
        // Revenue from customer orders
        $revenue = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount') ?? 0;

        // Expenses from supplier bills
        $expenses = SupplierBill::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('total_amount') ?? 0;

        // Wastage costs
        $wastageCost = DamageSpoilageLog::where('restaurant_id', $restaurantId)
            ->whereBetween('incident_date', [$dateFrom, $dateTo])
            ->sum('estimated_cost') ?? 0;

        $netProfit = $revenue - $expenses - $wastageCost;
        $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        // Daily breakdown
        $dailyBreakdown = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(total_amount) as daily_revenue')
            ->selectRaw('COUNT(*) as daily_orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'wastage_cost' => $wastageCost,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
            ],
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    private function getWastageData($restaurantId, $dateFrom, $dateTo, $type)
    {
        $query = DamageSpoilageLog::where('restaurant_id', $restaurantId)
            ->whereBetween('incident_date', [$dateFrom, $dateTo])
            ->with(['ingredient', 'user']);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $logs = $query->orderBy('incident_date', 'desc')->get();

        // Group by ingredient
        $byIngredient = $logs->groupBy('ingredient.ingredient_name')
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'total_cost' => $items->sum('estimated_cost'),
                ];
            });

        // Group by type
        $byType = $logs->groupBy('type')
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_cost' => $items->sum('estimated_cost'),
                ];
            });

        $summary = [
            'total_incidents' => $logs->count(),
            'total_cost' => $logs->sum('estimated_cost'),
            'damage_incidents' => $logs->where('type', 'damage')->count(),
            'spoilage_incidents' => $logs->where('type', 'spoilage')->count(),
        ];

        return [
            'logs' => $logs,
            'by_ingredient' => $byIngredient,
            'by_type' => $byType,
            'summary' => $summary,
        ];
    }

    // Export methods
    private function exportSalesReport($data, $format, $dateFrom, $dateTo)
    {
        $filename = "sales_report_{$dateFrom}_to_{$dateTo}";

        switch ($format) {
            case 'pdf':
                return $this->generatePDF('reports.sales_pdf', [
                    'data' => $data,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'title' => 'Sales Performance Report'
                ], $filename);

            case 'csv':
                return $this->generateCSV($this->prepareSalesCSVData($data), $filename);

            case 'excel':
                // For now, export as CSV with .xlsx extension
                return $this->generateCSV($this->prepareSalesCSVData($data), $filename, 'xlsx');

            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    private function exportInventoryReport($data, $format)
    {
        $filename = "inventory_report_" . now()->format('Y_m_d');

        switch ($format) {
            case 'pdf':
                return $this->generatePDF('reports.inventory_pdf', [
                    'data' => $data,
                    'title' => 'Inventory Analysis Report'
                ], $filename);

            case 'csv':
                return $this->generateCSV($this->prepareInventoryCSVData($data), $filename);

            case 'excel':
                return $this->generateCSV($this->prepareInventoryCSVData($data), $filename, 'xlsx');

            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    private function exportPurchaseOrdersReport($data, $format, $dateFrom, $dateTo)
    {
        $filename = "purchase_orders_report_{$dateFrom}_to_{$dateTo}";

        switch ($format) {
            case 'pdf':
                return $this->generatePDF('reports.purchase_orders_pdf', [
                    'data' => $data,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'title' => 'Purchase Orders Report'
                ], $filename);

            case 'csv':
                return $this->generateCSV($this->preparePurchaseOrdersCSVData($data), $filename);

            case 'excel':
                return $this->generateCSV($this->preparePurchaseOrdersCSVData($data), $filename, 'xlsx');

            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    private function exportFinancialReport($data, $format, $dateFrom, $dateTo)
    {
        $filename = "financial_report_{$dateFrom}_to_{$dateTo}";

        switch ($format) {
            case 'pdf':
                return $this->generatePDF('reports.financial_pdf', [
                    'data' => $data,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'title' => 'Financial Summary Report'
                ], $filename);

            case 'csv':
                return $this->generateCSV($this->prepareFinancialCSVData($data), $filename);

            case 'excel':
                return $this->generateCSV($this->prepareFinancialCSVData($data), $filename, 'xlsx');

            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    private function exportWastageReport($data, $format, $dateFrom, $dateTo)
    {
        $filename = "wastage_report_{$dateFrom}_to_{$dateTo}";

        switch ($format) {
            case 'pdf':
                return $this->generatePDF('reports.wastage_pdf', [
                    'data' => $data,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'title' => 'Wastage & Spoilage Report'
                ], $filename);

            case 'csv':
                return $this->generateCSV($this->prepareWastageCSVData($data), $filename);

            case 'excel':
                return $this->generateCSV($this->prepareWastageCSVData($data), $filename, 'xlsx');

            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    // Helper methods for export functionality
    private function generatePDF($view, $data, $filename)
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);

        $html = view($view, $data)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename={$filename}.pdf"
        ]);
    }

    private function generateCSV($data, $filename, $extension = 'csv')
    {
        $output = fopen('php://temp', 'w+');

        if (!empty($data)) {
            // Write headers
            fputcsv($output, array_keys($data[0]));

            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $contentType = $extension === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv';

        return response($csvContent, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename={$filename}.{$extension}",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    private function prepareSalesCSVData($data)
    {
        $csvData = [];

        // Sales summary
        $csvData[] = [
            'Report Type' => 'Sales Summary',
            'Total Sales' => '₱' . number_format($data['summary']['total_sales'], 2),
            'Total Orders' => $data['summary']['total_orders'],
            'Average Order Value' => '₱' . number_format($data['summary']['avg_order_value'], 2),
            'Items Sold' => $data['summary']['total_items_sold']
        ];

        // Add empty row
        $csvData[] = [];

        // Period breakdown
        foreach ($data['chart_data'] as $item) {
            $csvData[] = [
                'Period' => $item->period,
                'Sales' => '₱' . number_format($item->total_sales, 2),
                'Orders' => $item->order_count,
                'Average Order Value' => '₱' . number_format($item->avg_order_value, 2)
            ];
        }

        return $csvData;
    }

    private function prepareInventoryCSVData($data)
    {
        $csvData = [];

        foreach ($data['items'] as $item) {
            $csvData[] = [
                'Item Name' => $item->ingredient_name,
                'Current Stock' => $item->current_stock . ' ' . $item->base_unit,
                'Reorder Level' => $item->reorder_level . ' ' . $item->base_unit,
                'Unit Cost' => '₱' . number_format($item->cost_per_unit, 2),
                'Total Value' => '₱' . number_format($item->total_value, 2),
                'Status' => $item->stock_status === 'low' ? 'Low Stock' : 'In Stock'
            ];
        }

        return $csvData;
    }

    private function preparePurchaseOrdersCSVData($data)
    {
        $csvData = [];

        foreach ($data['orders'] as $order) {
            $csvData[] = [
                'Order Number' => $order->order_number,
                'Supplier' => $order->supplier->supplier_name ?? 'N/A',
                'Date' => $order->created_at->format('Y-m-d'),
                'Status' => ucfirst($order->status),
                'Total Amount' => '₱' . number_format($order->total_amount, 2),
                'Expected Delivery' => $order->expected_delivery_date ?? 'N/A'
            ];
        }

        return $csvData;
    }

    private function prepareFinancialCSVData($data)
    {
        $csvData = [];

        // Summary
        $csvData[] = [
            'Financial Summary',
            'Revenue' => '₱' . number_format($data['summary']['revenue'], 2),
            'Expenses' => '₱' . number_format($data['summary']['expenses'], 2),
            'Wastage Cost' => '₱' . number_format($data['summary']['wastage_cost'], 2),
            'Net Profit' => '₱' . number_format($data['summary']['net_profit'], 2),
            'Profit Margin' => number_format($data['summary']['profit_margin'], 2) . '%'
        ];

        // Add empty row
        $csvData[] = [];

        // Daily breakdown
        foreach ($data['daily_breakdown'] as $day) {
            $csvData[] = [
                'Date' => $day->date,
                'Revenue' => '₱' . number_format($day->daily_revenue, 2),
                'Orders' => $day->daily_orders
            ];
        }

        return $csvData;
    }

    private function prepareWastageCSVData($data)
    {
        $csvData = [];

        foreach ($data['logs'] as $log) {
            $csvData[] = [
                'Date' => $log->incident_date,
                'Type' => ucfirst($log->type),
                'Ingredient' => $log->ingredient->ingredient_name ?? 'N/A',
                'Quantity' => $log->quantity . ' ' . ($log->ingredient->base_unit ?? ''),
                'Estimated Cost' => '₱' . number_format($log->estimated_cost, 2),
                'Reason' => $log->reason,
                'Reported By' => $log->user->name ?? 'N/A'
            ];
        }

        return $csvData;
    }
}