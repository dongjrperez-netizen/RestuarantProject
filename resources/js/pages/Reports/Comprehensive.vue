<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { FileText, Download, Calendar } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    reportData?: any;
    reportType: string;
    reportTitle: string;
    period: string;
    date: string;
    chartUrls?: any;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports & Analytics', href: '/reports' },
    { title: 'Comprehensive Report', href: '/reports/comprehensive' },
];

const reportType = ref(props.reportType || 'daily');
const selectedDate = ref(props.date || new Date().toISOString().split('T')[0]);
const showGraphs = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};

const generateReport = () => {
    router.get('/reports/comprehensive', {
        type: reportType.value,
        date: selectedDate.value,
        show_graphs: showGraphs.value ? '1' : '0',
    }, {
        preserveState: true,
    });
};

const downloadPDF = () => {
    const graphParam = showGraphs.value ? '&show_graphs=1' : '';
    window.location.href = `/reports/comprehensive?type=${reportType.value}&date=${selectedDate.value}&export=pdf${graphParam}`;
};
</script>

<template>
    <Head title="Comprehensive Sales Report" />

    <AppLayout title="Comprehensive Sales Report" :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Report Generator Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="w-5 h-5" />
                        Generate Comprehensive Sales Report
                    </CardTitle>
                    <CardDescription>
                        Generate detailed sales reports (Daily, Monthly, or Yearly) with complete business insights including sales trends, inventory status, purchases, billing, and spoilage data. Option to include visual graphs for better analysis.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Report Type Selection -->
                        <div class="space-y-2">
                            <Label for="report-type">Report Type</Label>
                            <select
                                id="report-type"
                                v-model="reportType"
                                class="w-full px-3 py-2 border border-border rounded-md bg-background"
                            >
                                <option value="daily">Daily Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="space-y-2">
                            <Label for="report-date">
                                {{ reportType === 'yearly' ? 'Select Year' : reportType === 'monthly' ? 'Select Month' : 'Select Date' }}
                            </Label>
                            <Input
                                v-if="reportType === 'yearly'"
                                id="report-date"
                                v-model="selectedDate"
                                type="number"
                                :min="2020"
                                :max="new Date().getFullYear()"
                                placeholder="YYYY"
                                class="w-full"
                            />
                            <Input
                                v-else
                                id="report-date"
                                v-model="selectedDate"
                                :type="reportType === 'monthly' ? 'month' : 'date'"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Graph Option -->
                    <div class="flex items-center space-x-2 p-4 bg-muted/50 rounded-md">
                        <input
                            type="checkbox"
                            id="show-graphs"
                            v-model="showGraphs"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <Label for="show-graphs" class="cursor-pointer">
                            Include Visual Graphs
                        </Label>
                    </div>

                    <div v-if="showGraphs" class="p-4 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-md text-sm">
                        <p class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 Graphs Included:</p>
                        <ul class="list-disc list-inside text-blue-800 dark:text-blue-200 space-y-1">
                            <li>📈 Sales Trend (Line Chart)</li>
                            <li>📊 Top Selling Items (Bar Chart)</li>
                            <li>📦 Inventory Levels (Bar Chart)</li>
                            <li>💰 Supplier Spending (Pie Chart)</li>
                            <li>🗑 Spoilage Analysis (Bar Chart)</li>
                        </ul>
                    </div>

                    <div class="flex gap-3">
                        <Button @click="generateReport" variant="default">
                            <Calendar class="w-4 h-4 mr-2" />
                            Generate Report
                        </Button>
                        <Button
                            v-if="reportData"
                            @click="downloadPDF"
                            variant="outline"
                            :disabled="showGraphs"
                        >
                            <Download class="w-4 h-4 mr-2" />
                            Download PDF
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Report Preview (if data exists) -->
            <div v-if="reportData" class="space-y-6">
                <!-- Report Header -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ reportTitle }}</CardTitle>
                        <CardDescription>{{ period }}</CardDescription>
                    </CardHeader>
                </Card>

                <!-- A. Summary Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>A. Summary Section</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="p-4 border rounded-lg">
                                <div class="text-sm text-muted-foreground">Total Sales</div>
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ formatCurrency(reportData.summary.total_sales) }}
                                </div>
                            </div>
                            <div class="p-4 border rounded-lg">
                                <div class="text-sm text-muted-foreground">Total Orders</div>
                                <div class="text-2xl font-bold">
                                    {{ reportData.summary.total_orders }}
                                </div>
                            </div>
                            <div class="p-4 border rounded-lg">
                                <div class="text-sm text-muted-foreground">Total Expenses</div>
                                <div class="text-2xl font-bold text-orange-600">
                                    {{ formatCurrency(reportData.summary.total_expenses) }}
                                </div>
                            </div>
                            <div class="p-4 border rounded-lg">
                                <div class="text-sm text-muted-foreground">Spoilage Cost</div>
                                <div class="text-2xl font-bold text-red-600">
                                    {{ formatCurrency(reportData.summary.total_spoilage_cost) }}
                                </div>
                            </div>
                            <div class="p-4 border rounded-lg md:col-span-2">
                                <div class="text-sm text-muted-foreground">Net Profit</div>
                                <div
                                    class="text-2xl font-bold"
                                    :class="reportData.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatCurrency(reportData.summary.net_profit) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Visual Graphs Section -->
                <Card v-if="showGraphs && chartUrls">
                    <CardHeader>
                        <CardTitle>Visual Analytics</CardTitle>
                        <CardDescription>
                            Graphical representation of your business data
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Sales Trend Chart -->
                        <div v-if="chartUrls.sales_trend" class="space-y-2">
                            <h3 class="text-lg font-semibold">Sales Trend</h3>
                            <div class="flex justify-center">
                                <img :src="chartUrls.sales_trend" alt="Sales Trend Chart" class="max-w-full h-auto rounded-lg border" />
                            </div>
                        </div>

                        <!-- Top Selling Items Chart -->
                        <div v-if="chartUrls.top_items" class="space-y-2">
                            <h3 class="text-lg font-semibold">Top Selling Items</h3>
                            <div class="flex justify-center">
                                <img :src="chartUrls.top_items" alt="Top Selling Items Chart" class="max-w-full h-auto rounded-lg border" />
                            </div>
                        </div>

                        <!-- Inventory Levels Chart -->
                        <div v-if="chartUrls.inventory" class="space-y-2">
                            <h3 class="text-lg font-semibold">Inventory Levels</h3>
                            <div class="flex justify-center">
                                <img :src="chartUrls.inventory" alt="Inventory Levels Chart" class="max-w-full h-auto rounded-lg border" />
                            </div>
                        </div>

                        <!-- Supplier Spending Chart -->
                        <div v-if="chartUrls.supplier_spending" class="space-y-2">
                            <h3 class="text-lg font-semibold">Supplier Spending Distribution</h3>
                            <div class="flex justify-center">
                                <img :src="chartUrls.supplier_spending" alt="Supplier Spending Chart" class="max-w-full h-auto rounded-lg border" />
                            </div>
                        </div>

                        <!-- Spoilage Analysis Chart -->
                        <div v-if="chartUrls.spoilage" class="space-y-2">
                            <h3 class="text-lg font-semibold">Spoilage Analysis</h3>
                            <div class="flex justify-center">
                                <img :src="chartUrls.spoilage" alt="Spoilage Analysis Chart" class="max-w-full h-auto rounded-lg border" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- B. Detailed Data Sections -->
                <Card>
                    <CardHeader>
                        <CardTitle>B. Detailed Data Sections</CardTitle>
                        <CardDescription>
                            Complete breakdown of sales, inventory, purchases, billing, and spoilage
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Data Summary -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ reportData.sales_orders.length }}</div>
                                <div class="text-xs text-muted-foreground">Sales Orders</div>
                            </div>
                            <div class="p-3 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ reportData.inventory_items.length }}</div>
                                <div class="text-xs text-muted-foreground">Inventory Items</div>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ reportData.purchase_orders.length }}</div>
                                <div class="text-xs text-muted-foreground">Purchase Orders</div>
                            </div>
                            <div class="p-3 bg-indigo-50 rounded-lg">
                                <div class="text-2xl font-bold text-indigo-600">{{ reportData.billing_reports.length }}</div>
                                <div class="text-xs text-muted-foreground">Billing Records</div>
                            </div>
                            <div class="p-3 bg-red-50 rounded-lg">
                                <div class="text-2xl font-bold text-red-600">{{ reportData.spoilage_reports.length }}</div>
                                <div class="text-xs text-muted-foreground">Spoilage Incidents</div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <p class="text-sm text-muted-foreground">
                                Download the PDF report to view the complete detailed breakdown of all sections including:
                            </p>
                            <ul class="list-disc list-inside text-sm text-muted-foreground mt-2 space-y-1">
                                <li>Sales Report (Order ID, Amount, Payment Type, Cashier)</li>
                                <li>Inventory Report (Item Name, Current Stock, Status)</li>
                                <li>Purchase Order Report (PO No., Supplier, Items, Cost, Date)</li>
                                <li>Billing Report (Invoice No., Order ID, Customer, Amount, Status)</li>
                                <li>Spoilage Report (Item Name, Quantity, Reason, Date, Value Lost)</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <!-- Download Button Bottom -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex justify-center">
                            <Button
                                @click="downloadPDF"
                                size="lg"
                                class="w-full md:w-auto"
                                :disabled="showGraphs"
                            >
                                <Download class="w-5 h-5 mr-2" />
                                Download Complete PDF Report
                            </Button>
                        </div>
                        <p v-if="showGraphs" class="text-center text-sm text-muted-foreground mt-2">
                            PDF download is disabled when visual graphs are enabled. Uncheck "Include Visual Graphs" to download PDF.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
