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

        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(3)->startOfMonth()->toDateString());
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


    public function wastage(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(3)->startOfMonth()->toDateString());
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
            ->where('status', 'paid');

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
                  ->where('status', 'paid');
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
                  ->where('status', 'paid');
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

        // Get customer request exclusions count for each ingredient
        $exclusions = \App\Models\CustomerRequest::where('restaurant_id', $restaurantId)
            ->where('request_type', 'exclude')
            ->selectRaw('ingredient_id, COUNT(*) as exclusion_count')
            ->groupBy('ingredient_id')
            ->pluck('exclusion_count', 'ingredient_id');

        // Add exclusion count to each ingredient
        $ingredients = $ingredients->map(function($ingredient) use ($exclusions) {
            $ingredient->exclusion_count = $exclusions[$ingredient->ingredient_id] ?? 0;
            return $ingredient;
        });

        $summary = [
            'total_items' => $ingredients->count(),
            'low_stock_items' => $ingredients->where('stock_status', 'low')->count(),
            'total_value' => $ingredients->sum('total_value'),
            'avg_value_per_item' => $ingredients->avg('total_value') ?? 0,
            'total_exclusions' => $exclusions->sum(),
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
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with(['supplier', 'items.ingredient']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Map delivered status to received for compatibility
        $orders = $orders->map(function($order) {
            if ($order->status === 'delivered') {
                $order->status = 'received';
            }
            return $order;
        });

        $summary = [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('total_amount'),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'completed_orders' => $orders->whereIn('status', ['received', 'delivered'])->count(),
        ];

        $ordersByStatus = $orders->groupBy('status')->map->count();

        return [
            'orders' => $orders,
            'summary' => $summary,
            'orders_by_status' => $ordersByStatus,
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
        $options->set('isRemoteEnabled', true); // Enable loading external images from URLs
        $options->set('chroot', realpath(base_path())); // Set base path for security

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
                'Customer Exclusions' => $item->exclusion_count . 'x',
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

    // Comprehensive Daily/Monthly Sales Report
    public function comprehensiveReport(Request $request)
    {
        $restaurantId = $this->getRestaurantId();

        if (!$restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $reportType = $request->get('type', 'daily'); // daily, monthly, or yearly
        $date = $request->get('date', Carbon::now()->toDateString());
        $showGraphs = $request->get('show_graphs', false); // Option to show graphs in PDF

        if ($reportType === 'yearly') {
            $startDate = Carbon::parse($date)->startOfYear();
            $endDate = Carbon::parse($date)->endOfYear();
            $reportTitle = 'Yearly Sales Report';
            $period = $startDate->format('Y');
        } elseif ($reportType === 'monthly') {
            $startDate = Carbon::parse($date)->startOfMonth();
            $endDate = Carbon::parse($date)->endOfMonth();
            $reportTitle = 'Monthly Sales Report';
            $period = $startDate->format('F j') . ' - ' . $endDate->format('j, Y');
        } else {
            $startDate = Carbon::parse($date)->startOfDay();
            $endDate = Carbon::parse($date)->endOfDay();
            $reportTitle = 'Daily Sales Report';
            $period = $startDate->format('F j, Y');
        }

        $data = $this->getComprehensiveReportData($restaurantId, $startDate, $endDate);

        if ($request->get('export') === 'pdf') {
            return $this->exportComprehensiveReport($data, $reportTitle, $period, $startDate, $endDate, $showGraphs, $reportType);
        }

        // Generate chart URLs for web view if requested
        $chartUrls = null;
        if ($showGraphs) {
            // Add breakdown data needed for charts
            if ($reportType === 'yearly') {
                $monthlyBreakdown = [];
                for ($month = 1; $month <= 12; $month++) {
                    $monthStart = Carbon::parse($startDate)->month($month)->startOfMonth();
                    $monthEnd = Carbon::parse($startDate)->month($month)->endOfMonth();
                    $monthSales = CustomerOrder::where('restaurant_id', $restaurantId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->where('status', 'paid')
                        ->sum('total_amount');
                    $monthlyBreakdown[$monthStart->format('M')] = (float) $monthSales;
                }
                $data['monthly_breakdown'] = $monthlyBreakdown;
            } elseif ($reportType === 'monthly') {
                $dailyBreakdown = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $daySales = CustomerOrder::where('restaurant_id', $restaurantId)
                        ->whereDate('created_at', $currentDate)
                        ->where('status', 'paid')
                        ->sum('total_amount');
                    $dailyBreakdown[$currentDate->format('M j')] = (float) $daySales;
                    $currentDate->addDay();
                }
                $data['daily_breakdown'] = $dailyBreakdown;
            } elseif ($reportType === 'daily') {
                // Hourly breakdown for daily reports
                $hourlyBreakdown = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    $hourStart = $startDate->copy()->hour($hour)->minute(0)->second(0);
                    $hourEnd = $startDate->copy()->hour($hour)->minute(59)->second(59);
                    $hourSales = CustomerOrder::where('restaurant_id', $restaurantId)
                        ->whereBetween('created_at', [$hourStart, $hourEnd])
                        ->where('status', 'paid')
                        ->sum('total_amount');
                    $hourlyBreakdown[str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00'] = (float) $hourSales;
                }
                $data['hourly_breakdown'] = $hourlyBreakdown;
            }

            $chartUrls = $this->generateChartUrlsForWeb($data, $reportType);
        }

        return Inertia::render('Reports/Comprehensive', [
            'reportData' => $data,
            'reportType' => $reportType,
            'reportTitle' => $reportTitle,
            'period' => $period,
            'date' => $date,
            'chartUrls' => $chartUrls,
        ]);
    }

   private function getComprehensiveReportData($restaurantId, $startDate, $endDate)
    {
        // Get restaurant data
        $restaurant = Restaurant_Data::where('user_id', Auth::id())
            ->orWhere('id', $restaurantId)
            ->first();

        // A. Summary Section
        $totalSales = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalOrders = CustomerOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->count();

        $totalExpenses = PurchaseOrder::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'received'])
            ->sum('total_amount');

        $totalSpoilageCost = DamageSpoilageLog::where('restaurant_id', $restaurantId)
            ->whereBetween('incident_date', [$startDate, $endDate])
            ->sum('estimated_cost');

        $netProfit = $totalSales - $totalExpenses - $totalSpoilageCost;

        // B. Detailed Data Sections

        // 1. Sales Report
        $salesOrders = CustomerOrder::with(['employee', 'payments.cashier'])
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->orderBy('created_at')
            ->get()
            ->map(function ($order) {
                $payment = $order->payments()->where('status', 'completed')->with('cashier')->latest()->first();
                return [
                    'date' => $order->created_at->format('M j'),
                    'order_id' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_type' => $payment ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'N/A',
                    'cashier' => ($payment && $payment->cashier) ? $payment->cashier->firstname . ' ' . $payment->cashier->lastname : 'N/A',
                ];
            });

        // 2. Inventory Report
        $inventoryItems = Ingredients::where('restaurant_id', $restaurantId)
            ->selectRaw('
                ingredient_name,
                current_stock,
                base_unit,
                CASE
                    WHEN current_stock <= reorder_level THEN "Low Stock"
                    WHEN current_stock = 0 THEN "Out of Stock"
                    ELSE "Available"
                END as status
            ')
            ->orderBy('ingredient_name')
            ->get()
            ->map(function ($item) {
                return [
                    'item_name' => $item->ingredient_name,
                    'current_stock' => number_format($item->current_stock, 2) . ' ' . $item->base_unit,
                    'current_stock_raw' => $item->current_stock, // For charts
                    'status' => $item->status,
                ];
            });

        // Top Selling Items (for charts)
        $topItems = CustomerOrderItem::selectRaw('dish_id, SUM(quantity) as quantity_sold')
            ->whereHas('order', function ($query) use ($restaurantId, $startDate, $endDate) {
                $query->where('restaurant_id', $restaurantId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'paid');
            })
            ->with('dish')
            ->groupBy('dish_id')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'dish_name' => $item->dish ? $item->dish->dish_name : 'Unknown',
                    'quantity_sold' => $item->quantity_sold,
                ];
            });

        // 3. Purchase Order Report
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'received'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($po) {
                return [
                    'po_no' => $po->order_number,
                    'supplier' => $po->supplier ? $po->supplier->supplier_name : 'N/A',
                    'items' => $po->items->pluck('ingredient.ingredient_name')->join(', '),
                    'total_cost' => $po->total_amount,
                    'date_received' => $po->updated_at->format('M j, Y'),
                ];
            });

        // 4. Billing Report (same as sales but with invoice format)
        $billingReports = CustomerOrder::with(['employee'])
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->orderBy('created_at')
            ->get()
            ->map(function ($order) {
                return [
                    'invoice_no' => 'INV-' . $order->order_number,
                    'order_id' => $order->order_number,
                    'customer' => $order->customer_name ?: 'Walk-in',
                    'amount' => $order->total_amount,
                    'status' => 'Paid',
                    'date' => $order->created_at->format('M j, Y'),
                ];
            });

        // 5. Spoilage Report
        $spoilageReports = DamageSpoilageLog::with(['ingredient'])
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('incident_date', [$startDate, $endDate])
            ->orderBy('incident_date')
            ->get()
            ->map(function ($log) {
                return [
                    'item_name' => $log->ingredient ? $log->ingredient->ingredient_name : 'N/A',
                    'quantity' => $log->quantity . ' ' . ($log->ingredient ? $log->ingredient->base_unit : ''),
                    'reason' => $log->reason,
                    'date' => Carbon::parse($log->incident_date)->format('M j, Y'),
                    'value_lost' => $log->estimated_cost,
                ];
            });

        return [
            'restaurant' => $restaurant,
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'total_expenses' => $totalExpenses,
                'total_spoilage_cost' => $totalSpoilageCost,
                'net_profit' => $netProfit,
            ],
            'sales_summary' => [ // For charts
                'total_sales' => $totalSales,
            ],
            'sales_orders' => $salesOrders,
            'top_items' => $topItems,
            'inventory_items' => $inventoryItems,
            'inventory_status' => $inventoryItems, // For charts
            'purchase_orders' => $purchaseOrders,
            'billing_reports' => $billingReports,
            'spoilage_reports' => $spoilageReports,
        ];
    }

    private function exportComprehensiveReport($data, $reportTitle, $period, $startDate, $endDate, $showGraphs = false, $reportType = 'daily')
    {
        $generatedBy = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $generatedDate = Carbon::now()->format('F j, Y');

        $filename = strtolower(str_replace(' ', '_', $reportTitle)) . '_' . $startDate->format('Y_m_d');

        // Generate chart URLs if graphs are requested
        $chartUrls = [];
        if ($showGraphs) {
            // Add breakdown data needed for charts
            if ($reportType === 'yearly') {
                // Generate monthly breakdown for yearly report
                $monthlyBreakdown = [];
                for ($month = 1; $month <= 12; $month++) {
                    $monthStart = Carbon::parse($startDate)->month($month)->startOfMonth();
                    $monthEnd = Carbon::parse($startDate)->month($month)->endOfMonth();
                    $monthSales = CustomerOrder::where('restaurant_id', Auth::user()->restaurant_id)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->where('status', 'paid')
                        ->sum('total_amount');
                    $monthlyBreakdown[$monthStart->format('M')] = (float) $monthSales;
                }
                $data['monthly_breakdown'] = $monthlyBreakdown;
            } elseif ($reportType === 'monthly') {
                // Generate daily breakdown for monthly report
                $dailyBreakdown = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $daySales = CustomerOrder::where('restaurant_id', Auth::user()->restaurant_id)
                        ->whereDate('created_at', $currentDate)
                        ->where('status', 'paid')
                        ->sum('total_amount');
                    $dailyBreakdown[$currentDate->format('M j')] = (float) $daySales;
                    $currentDate->addDay();
                }
                $data['daily_breakdown'] = $dailyBreakdown;
            }

            $chartUrls = $this->generateChartUrls($data, $reportType);
        }

        $pdf = $this->generatePDF('reports.comprehensive_pdf', [
            'data' => $data,
            'reportTitle' => $reportTitle,
            'period' => $period,
            'generatedBy' => $generatedBy,
            'generatedDate' => $generatedDate,
            'showGraphs' => $showGraphs,
            'chartUrls' => $chartUrls,
        ], $filename);

        // Clean up temporary chart files
        if ($showGraphs && !empty($chartUrls)) {
            $this->cleanupTempCharts($chartUrls);
        }

        return $pdf;
    }

    /**
     * Clean up temporary chart files
     */
    private function cleanupTempCharts($chartPaths)
    {
        foreach ($chartPaths as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
                \Log::info("Cleaned up temp chart file", ['path' => $path]);
            }
        }
    }

    /**
     * Generate chart URLs for web view (returns QuickChart.io URLs directly)
     */
    private function generateChartUrlsForWeb($data, $reportType)
    {
        $charts = [];

        // 1. Sales Trend Chart (Line Chart)
        if (!empty($data['sales_summary'])) {
            $salesChartUrl = $this->generateSalesTrendChart($data, $reportType);
            if ($salesChartUrl) {
                $charts['sales_trend'] = $salesChartUrl;
            }
        }

        // 2. Top Selling Items (Bar Chart)
        if (!empty($data['top_items'])) {
            $topItemsUrl = $this->generateTopItemsChart($data['top_items']);
            if ($topItemsUrl) {
                $charts['top_items'] = $topItemsUrl;
            }
        }

        // 3. Inventory Status (Bar Chart)
        if (!empty($data['inventory_status'])) {
            $inventoryUrl = $this->generateInventoryChart($data['inventory_status']);
            if ($inventoryUrl) {
                $charts['inventory'] = $inventoryUrl;
            }
        }

        // 4. Supplier Spending (Pie Chart)
        if (!empty($data['purchase_orders'])) {
            $supplierUrl = $this->generateSupplierSpendingChart($data['purchase_orders']);
            if ($supplierUrl) {
                $charts['supplier_spending'] = $supplierUrl;
            }
        }

        // 5. Spoilage Report (Bar Chart)
        if (!empty($data['spoilage_reports'])) {
            $spoilageUrl = $this->generateSpoilageChart($data['spoilage_reports']);
            if ($spoilageUrl) {
                $charts['spoilage'] = $spoilageUrl;
            }
        }

        return $charts;
    }

    /**
     * Generate chart URLs using QuickChart.io API
     * This generates chart images that can be embedded in PDFs
     */
    private function generateChartUrls($data, $reportType)
    {
        $charts = [];

        // 1. Sales Trend Chart (Line Chart)
        if (!empty($data['sales_summary'])) {
            $salesChartUrl = $this->generateSalesTrendChart($data, $reportType);
            if ($salesChartUrl) {
                $base64Chart = $this->convertChartUrlToBase64($salesChartUrl);
                if ($base64Chart) {
                    $charts['sales_trend'] = $base64Chart;
                }
            }
        }

        // 2. Top Selling Items (Bar Chart)
        if (!empty($data['top_items'])) {
            $topItemsUrl = $this->generateTopItemsChart($data['top_items']);
            if ($topItemsUrl) {
                $base64Chart = $this->convertChartUrlToBase64($topItemsUrl);
                if ($base64Chart) {
                    $charts['top_items'] = $base64Chart;
                }
            }
        }

        // 3. Inventory Status (Bar Chart)
        if (!empty($data['inventory_status'])) {
            $inventoryUrl = $this->generateInventoryChart($data['inventory_status']);
            if ($inventoryUrl) {
                $base64Chart = $this->convertChartUrlToBase64($inventoryUrl);
                if ($base64Chart) {
                    $charts['inventory'] = $base64Chart;
                }
            }
        }

        // 4. Supplier Spending (Pie Chart)
        if (!empty($data['purchase_orders'])) {
            $supplierUrl = $this->generateSupplierSpendingChart($data['purchase_orders']);
            if ($supplierUrl) {
                $base64Chart = $this->convertChartUrlToBase64($supplierUrl);
                if ($base64Chart) {
                    $charts['supplier_spending'] = $base64Chart;
                }
            }
        }

        // 5. Spoilage Report (Bar Chart)
        if (!empty($data['spoilage_reports'])) {
            $spoilageUrl = $this->generateSpoilageChart($data['spoilage_reports']);
            if ($spoilageUrl) {
                $base64Chart = $this->convertChartUrlToBase64($spoilageUrl);
                if ($base64Chart) {
                    $charts['spoilage'] = $base64Chart;
                }
            }
        }

        return $charts;
    }

    private function generateSalesTrendChart($data, $reportType)
    {
        // For daily: show hourly trends
        // For monthly: show daily trends
        // For yearly: show monthly trends

        $labels = [];
        $values = [];

        if ($reportType === 'yearly' && isset($data['monthly_breakdown'])) {
            foreach ($data['monthly_breakdown'] as $month => $amount) {
                $labels[] = $month;
                $values[] = $amount;
            }
        } elseif ($reportType === 'monthly' && isset($data['daily_breakdown'])) {
            foreach ($data['daily_breakdown'] as $day => $amount) {
                $labels[] = $day;
                $values[] = $amount;
            }
        } elseif ($reportType === 'daily' && isset($data['hourly_breakdown'])) {
            foreach ($data['hourly_breakdown'] as $hour => $amount) {
                $labels[] = $hour;
                $values[] = $amount;
            }
        }

        if (empty($labels)) {
            return null;
        }

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Sales',
                    'data' => $values,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'fill' => true,
                ]]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Sales Trend'
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=800&height=400';
    }

    private function generateTopItemsChart($topItems)
    {
        $labels = [];
        $values = [];

        // Convert Collection to array if needed
        $itemsArray = is_array($topItems) ? $topItems : $topItems->toArray();

        foreach (array_slice($itemsArray, 0, 10) as $item) {
            $labels[] = substr($item['dish_name'], 0, 20);
            $values[] = $item['quantity_sold'];
        }

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Quantity Sold',
                    'data' => $values,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                ]]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Top Selling Items'
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=800&height=400';
    }

    private function generateInventoryChart($inventory)
    {
        $labels = [];
        $values = [];

        // Convert Collection to array if needed
        $inventoryArray = is_array($inventory) ? $inventory : $inventory->toArray();

        foreach (array_slice($inventoryArray, 0, 15) as $item) {
            $labels[] = substr($item['item_name'] ?? 'Unknown', 0, 15);
            // Use raw value if available, otherwise parse formatted string
            if (isset($item['current_stock_raw'])) {
                $values[] = $item['current_stock_raw'];
            } else {
                $stockStr = $item['current_stock'] ?? '0';
                $values[] = floatval(preg_replace('/[^0-9.]/', '', $stockStr));
            }
        }

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Current Stock',
                    'data' => $values,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                ]]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Inventory Levels'
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=800&height=400';
    }

    private function generateSupplierSpendingChart($purchaseOrders)
    {
        $supplierTotals = [];

        // Convert Collection to array if needed
        $ordersArray = is_array($purchaseOrders) ? $purchaseOrders : $purchaseOrders->toArray();

        foreach ($ordersArray as $po) {
            $supplier = $po['supplier'] ?? 'Unknown';
            if (!isset($supplierTotals[$supplier])) {
                $supplierTotals[$supplier] = 0;
            }
            $supplierTotals[$supplier] += $po['total_cost'] ?? 0;
        }

        $labels = array_keys($supplierTotals);
        $values = array_values($supplierTotals);

        $chartConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                    ]
                ]]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Supplier Spending Distribution'
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=800&height=400';
    }

    private function generateSpoilageChart($spoilage)
    {
        $labels = [];
        $values = [];

        // Convert Collection to array if needed
        $spoilageArray = is_array($spoilage) ? $spoilage : $spoilage->toArray();

        foreach (array_slice($spoilageArray, 0, 10) as $item) {
            $labels[] = substr($item['item_name'] ?? 'Unknown', 0, 20);
            // Extract numeric value from quantity string (e.g., "100 kg" -> 100)
            $quantityStr = $item['quantity'] ?? '0';
            $quantity = floatval(preg_replace('/[^0-9.]/', '', $quantityStr));
            $values[] = $quantity;
        }

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Quantity Wasted',
                    'data' => $values,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                ]]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Top Spoiled Items'
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=800&height=400';
    }

    /**
     * Download chart and save as temporary file for PDF embedding
     */
    private function convertChartUrlToBase64($url)
    {
        try {
            \Log::info("Attempting to fetch chart from URL", ['url' => substr($url, 0, 200)]);

            // Fetch the image from QuickChart.io
            $imageData = @file_get_contents($url);

            if ($imageData === false) {
                \Log::warning("Failed to fetch chart image from URL: " . substr($url, 0, 200));
                return null;
            }

            \Log::info("Successfully fetched chart image", ['size' => strlen($imageData) . ' bytes']);

            // Save to temporary file instead of base64
            $tempDir = storage_path('app/temp_charts');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filename = 'chart_' . md5($url) . '.png';
            $filePath = $tempDir . '/' . $filename;

            file_put_contents($filePath, $imageData);

            \Log::info("Chart saved to temp file", ['path' => $filePath]);

            // Return the local file path
            return $filePath;
        } catch (\Exception $e) {
            \Log::error("Error saving chart to temp file: " . $e->getMessage());
            return null;
        }
    }
}