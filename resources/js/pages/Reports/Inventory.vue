<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Progress } from '@/components/ui/progress';
import { type BreadcrumbItem } from '@/types';
import {
  ArrowLeft,
  Download,
  Package,
  AlertTriangle,
  TrendingDown,
  DollarSign,
  BarChart3,
  Search,
  XCircle,
  Banknote,
  Calendar,
  Filter,
  Truck,
  History
} from 'lucide-vue-next';

interface InventoryItem {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  current_stock: number;
  reorder_level: number;
  cost_per_unit: number;
  total_value: number;
  stock_status: 'low' | 'normal';
  exclusion_count: number;
}

interface Summary {
  total_items: number;
  low_stock_items: number;
  total_value: number;
  avg_value_per_item: number;
  total_exclusions: number;
}

interface StockLevels {
  low?: number;
  normal?: number;
}

interface InventoryData {
  items: InventoryItem[];
  summary: Summary;
  stock_levels: StockLevels;
}

interface StockInRecord {
  date_received: string;
  date_received_timestamp: string;
  ingredient_id: number;
  ingredient_name: string;
  supplier_name: string;
  quantity_received: number;
  unit: string;
  stock_increase: number;
  base_unit: string;
  cost_per_unit: number;
  total_value: number;
  po_number: string;
  po_id: number;
  received_by: string;
  quality_rating?: string;
  condition_notes?: string;
}

interface StockInSummary {
  total_transactions: number;
  total_value: number;
  total_packages: number;
  unique_ingredients: number;
}

interface StockInFilters {
  date_from: string;
  date_to: string;
  ingredient?: number;
  supplier?: number;
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface StockInHistory {
  records: StockInRecord[];
  summary: StockInSummary;
  suppliers: Supplier[];
  filters: StockInFilters;
}

interface Props {
  inventoryData: InventoryData;
  stockInHistory: StockInHistory;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Inventory Analysis', href: '/reports/inventory' },
];

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const exportReport = (format: string) => {
  window.open(`/reports/inventory?export=${format}`);
};

const getStockPercentage = (current: number, reorder: number) => {
  if (reorder === 0) return 100;
  return Math.min((current / (reorder * 2)) * 100, 100);
};

const getStockColor = (status: string) => {
  return status === 'low' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
};

const getStockBadgeVariant = (status: string) => {
  return status === 'low' ? 'destructive' : 'secondary';
};

const lowStockItems = props.inventoryData.items.filter(item => item.stock_status === 'low');
const highValueItems = [...props.inventoryData.items]
  .sort((a, b) => b.total_value - a.total_value)
  .slice(0, 10);

// Stock-in history filters
const showHistoryFilters = ref(false);
const historyFilters = ref({
  date_from: props.stockInHistory.filters.date_from,
  date_to: props.stockInHistory.filters.date_to,
  ingredient: props.stockInHistory.filters.ingredient?.toString() || '',
  supplier: props.stockInHistory.filters.supplier?.toString() || '',
});

const applyHistoryFilters = () => {
  const params = new URLSearchParams();
  if (historyFilters.value.date_from) params.append('history_date_from', historyFilters.value.date_from);
  if (historyFilters.value.date_to) params.append('history_date_to', historyFilters.value.date_to);
  if (historyFilters.value.ingredient) params.append('history_ingredient', historyFilters.value.ingredient);
  if (historyFilters.value.supplier) params.append('history_supplier', historyFilters.value.supplier);

  router.get(`/reports/inventory?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const exportStockInHistory = (format: string) => {
  const params = new URLSearchParams();
  if (historyFilters.value.date_from) params.append('history_date_from', historyFilters.value.date_from);
  if (historyFilters.value.date_to) params.append('history_date_to', historyFilters.value.date_to);
  if (historyFilters.value.ingredient) params.append('history_ingredient', historyFilters.value.ingredient);
  if (historyFilters.value.supplier) params.append('history_supplier', historyFilters.value.supplier);
  params.append('export_history', format);

  window.open(`/reports/inventory?${params.toString()}`);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getQualityBadgeVariant = (rating?: string) => {
  if (!rating) return 'secondary';
  switch (rating.toLowerCase()) {
    case 'excellent': return 'default';
    case 'good': return 'secondary';
    case 'fair': return 'outline';
    case 'poor': return 'destructive';
    default: return 'secondary';
  }
};
</script>

<template>
  <Head title="Inventory Analysis Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
     <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="router.get('/reports')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Reports
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Inventory Analysis</h1>
            <p class="text-muted-foreground">Track stock levels, stock-in history, and reorder alerts</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Select @update:model-value="(format) => exportReport(format)">
            <SelectTrigger class="w-32">
              <SelectValue placeholder="Export" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pdf">
                <Download class="w-4 h-4 mr-2" />
                PDF
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Items</p>
                <p class="text-2xl font-bold">{{ formatNumber(inventoryData.summary.total_items) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Inventory items</p>
              </div>
              <div class="h-8 w-8 bg-blue-100 dark:bg-blue-950/30 rounded-full flex items-center justify-center">
                <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Low Stock Items</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatNumber(inventoryData.summary.low_stock_items) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Need reordering</p>
              </div>
              <div class="h-8 w-8 bg-red-100 dark:bg-red-950/30 rounded-full flex items-center justify-center">
                <AlertTriangle class="h-4 w-4 text-red-600 dark:text-red-400" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Value</p>
                <p class="text-2xl font-bold">{{ formatCurrency(inventoryData.summary.total_value) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Inventory worth</p>
              </div>
              <div class="h-8 w-8 bg-green-100 dark:bg-green-950/30 rounded-full flex items-center justify-center">
                <Banknote class="h-4 w-4 text-green-600 dark:text-green-400" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Customer Exclusions</p>
                <p class="text-2xl font-bold text-amber-600">{{ formatNumber(inventoryData.summary.total_exclusions) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Ingredients not deducted</p>
              </div>
              <div class="h-8 w-8 bg-amber-100 rounded-full flex items-center justify-center">
                <XCircle class="h-4 w-4 text-amber-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Stock Level Distribution -->
      <Card>
        <CardHeader>
          <CardTitle>Stock Level Distribution</CardTitle>
          <CardDescription>Overview of stock status across all items</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Normal Stock</span>
                  <span class="text-sm font-semibold">{{ inventoryData.stock_levels.normal || 0 }} items ({{ inventoryData.summary.total_items > 0 ? Math.round(((inventoryData.stock_levels.normal || 0) / inventoryData.summary.total_items) * 100) : 0 }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                  <div
                    class="bg-green-600 dark:bg-green-500 h-3 rounded-full transition-all"
                    :style="{ width: `${inventoryData.summary.total_items > 0 ? ((inventoryData.stock_levels.normal || 0) / inventoryData.summary.total_items) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-red-600 dark:text-red-400">Low Stock</span>
                  <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ inventoryData.stock_levels.low || 0 }} items ({{ inventoryData.summary.total_items > 0 ? Math.round(((inventoryData.stock_levels.low || 0) / inventoryData.summary.total_items) * 100) : 0 }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                  <div
                    class="bg-red-600 dark:bg-red-500 h-3 rounded-full transition-all"
                    :style="{ width: `${inventoryData.summary.total_items > 0 ? ((inventoryData.stock_levels.low || 0) / inventoryData.summary.total_items) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-center">
              <div class="text-center">
                <Package class="h-16 w-16 mx-auto mb-4 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">Stock level visualization</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Low Stock Alert -->
      <Card v-if="lowStockItems.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-red-600 dark:text-red-400">
            <AlertTriangle class="h-5 w-5" />
            Low Stock Alert
          </CardTitle>
          <CardDescription>Items that need immediate attention</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div
              v-for="item in lowStockItems"
              :key="item.ingredient_id"
              class="flex items-center justify-between p-3 border border-red-200 dark:border-red-900/50 rounded-lg bg-red-50 dark:bg-red-950/20"
            >
              <div>
                <p class="font-medium">{{ item.ingredient_name }}</p>
                <p class="text-sm text-muted-foreground">
                  Current: {{ item.current_stock }} {{ item.base_unit }} |
                  Reorder Level: {{ item.reorder_level }} {{ item.base_unit }}
                </p>
              </div>
              <div class="text-right">
                <Badge variant="destructive">Low Stock</Badge>
                <p class="text-sm text-muted-foreground mt-1">{{ formatCurrency(item.total_value) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- High Value Items -->
      <Card>
        <CardHeader>
          <CardTitle>Highest Value Items</CardTitle>
          <CardDescription>Top 10 items by inventory value</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="(item, index) in highValueItems"
              :key="item.ingredient_id"
              class="flex items-center justify-between p-3 border rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-950/30 rounded-full">
                  <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ index + 1 }}</span>
                </div>
                <div>
                  <p class="font-medium">{{ item.ingredient_name }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ item.current_stock }} {{ item.base_unit }} @ {{ formatCurrency(item.cost_per_unit) }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold">{{ formatCurrency(item.total_value) }}</p>
                <Badge :variant="getStockBadgeVariant(item.stock_status)">
                  {{ item.stock_status === 'low' ? 'Low Stock' : 'In Stock' }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Complete Inventory Table -->
      <Card>
        <CardHeader>
          <CardTitle>Complete Inventory</CardTitle>
          <CardDescription>Detailed view of all inventory items</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-4 font-semibold">Item</th>
                  <th class="text-left p-4 font-semibold">Current Stock</th>
                  <th class="text-left p-4 font-semibold">Reorder Level</th>
                  <th class="text-left p-4 font-semibold">Unit Cost</th>
                  <th class="text-left p-4 font-semibold">Total Value</th>
                  <th class="text-left p-4 font-semibold">Exclusions</th>
                  <th class="text-left p-4 font-semibold">Status</th>
                  <th class="text-left p-4 font-semibold">Stock Level</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="inventoryData.items.length === 0">
                  <td colspan="8" class="p-8 text-center text-muted-foreground">
                    <Package class="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>No inventory items found</p>
                  </td>
                </tr>
                <tr
                  v-for="item in inventoryData.items"
                  :key="item.ingredient_id"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-4">
                    <div>
                      <p class="font-medium">{{ item.ingredient_name }}</p>
                      <p class="text-sm text-muted-foreground">{{ item.base_unit }}</p>
                    </div>
                  </td>
                  <td class="p-4">
                    <span :class="getStockColor(item.stock_status)">
                      {{ formatNumber(item.current_stock) }} {{ item.base_unit }}
                    </span>
                  </td>
                  <td class="p-4">{{ formatNumber(item.reorder_level) }} {{ item.base_unit }}</td>
                  <td class="p-4">{{ formatCurrency(item.cost_per_unit) }}</td>
                  <td class="p-4 font-medium">{{ formatCurrency(item.total_value) }}</td>
                  <td class="p-4">
                    <span v-if="item.exclusion_count > 0" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-950/30 text-amber-800 dark:text-amber-400">
                      {{ item.exclusion_count }}x excluded
                    </span>
                    <span v-else class="text-sm text-muted-foreground">-</span>
                  </td>
                  <td class="p-4">
                    <Badge :variant="getStockBadgeVariant(item.stock_status)">
                      {{ item.stock_status === 'low' ? 'Low Stock' : 'In Stock' }}
                    </Badge>
                  </td>
                  <td class="p-4">
                    <div class="space-y-1">
                      <div class="text-xs text-muted-foreground">
                        {{ Math.round(getStockPercentage(item.current_stock, item.reorder_level)) }}%
                      </div>
                      <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                          class="h-2 rounded-full transition-all"
                          :class="item.stock_status === 'low' ? 'bg-red-600 dark:bg-red-500' : 'bg-green-600 dark:bg-green-500'"
                          :style="{ width: `${getStockPercentage(item.current_stock, item.reorder_level)}%` }"
                        ></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Stock-In History Section -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <History class="h-5 w-5" />
                Stock-In History
              </CardTitle>
              <CardDescription>Track all ingredient receipts from purchase orders (sorted by newest first)</CardDescription>
            </div>
            <div class="flex gap-2">
              <Button variant="outline" size="sm" @click="showHistoryFilters = !showHistoryFilters">
                <Filter class="w-4 h-4 mr-2" />
                Filters
              </Button>
              <Select @update:model-value="(format) => exportStockInHistory(format)">
                <SelectTrigger class="w-28 h-9">
                  <SelectValue placeholder="Export" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="pdf">
                    <Download class="w-4 h-4 mr-2 inline" />
                    PDF
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <!-- History Filters -->
          <div v-if="showHistoryFilters" class="border rounded-lg p-4 mb-6 bg-muted/30">
            <h3 class="font-semibold mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="space-y-2">
                <Label>Date From</Label>
                <Input
                  v-model="historyFilters.date_from"
                  type="date"
                  :max="new Date().toISOString().split('T')[0]"
                />
              </div>
              <div class="space-y-2">
                <Label>Date To</Label>
                <Input
                  v-model="historyFilters.date_to"
                  type="date"
                  :max="new Date().toISOString().split('T')[0]"
                />
              </div>
              <div class="space-y-2">
                <Label>Supplier</Label>
                <select
                  v-model="historyFilters.supplier"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                  <option value="">All Suppliers</option>
                  <option
                    v-for="supplier in stockInHistory.suppliers"
                    :key="supplier.supplier_id"
                    :value="supplier.supplier_id.toString()"
                  >
                    {{ supplier.supplier_name }}
                  </option>
                </select>
              </div>
              <div class="flex items-end">
                <Button @click="applyHistoryFilters" class="w-full">
                  Apply Filters
                </Button>
              </div>
            </div>
          </div>

          <!-- Stock-In Records Table -->
          <div v-if="stockInHistory.records.length === 0" class="text-center py-12">
            <History class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No stock-in records found</h3>
            <p class="text-muted-foreground">No ingredient receipts match your current filters.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-3 font-semibold text-xs">Date</th>
                  <th class="text-left p-3 font-semibold text-xs">Ingredient</th>
                  <th class="text-left p-3 font-semibold text-xs">Supplier</th>
                  <th class="text-left p-3 font-semibold text-xs">Quantity</th>
                  <th class="text-left p-3 font-semibold text-xs">Stock Added</th>
                  <th class="text-left p-3 font-semibold text-xs">Unit Cost</th>
                  <th class="text-left p-3 font-semibold text-xs">Total Value</th>
                  <th class="text-left p-3 font-semibold text-xs">PO Reference</th>
                  <th class="text-left p-3 font-semibold text-xs">Quality</th>
                  <th class="text-left p-3 font-semibold text-xs">Received By</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="record in stockInHistory.records"
                  :key="`${record.po_id}-${record.ingredient_id}`"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-3">
                    <div class="flex items-center gap-1.5">
                      <Calendar class="h-3 w-3 text-muted-foreground" />
                      <span class="text-xs">{{ formatDate(record.date_received_timestamp) }}</span>
                    </div>
                  </td>
                  <td class="p-3">
                    <p class="font-medium text-xs">{{ record.ingredient_name }}</p>
                  </td>
                  <td class="p-3">
                    <div class="flex items-center gap-1.5">
                      <Truck class="h-3 w-3 text-muted-foreground" />
                      <span class="text-xs">{{ record.supplier_name }}</span>
                    </div>
                  </td>
                  <td class="p-3">
                    <span class="font-medium text-xs">{{ formatNumber(record.quantity_received) }}</span>
                    <span class="text-xs text-muted-foreground ml-1">{{ record.unit }}</span>
                  </td>
                  <td class="p-3">
                    <span class="font-medium text-green-600 dark:text-green-400 text-xs">+{{ formatNumber(record.stock_increase) }}</span>
                    <span class="text-xs text-muted-foreground ml-1">{{ record.base_unit }}</span>
                  </td>
                  <td class="p-3 text-xs">{{ formatCurrency(record.cost_per_unit) }}</td>
                  <td class="p-3 font-medium text-xs">{{ formatCurrency(record.total_value) }}</td>
                  <td class="p-3">
                    <a
                      :href="`/purchase-orders/${record.po_id}`"
                      class="text-blue-600 dark:text-blue-400 hover:underline font-medium text-xs"
                      target="_blank"
                    >
                      {{ record.po_number }}
                    </a>
                  </td>
                  <td class="p-3">
                    <Badge v-if="record.quality_rating" :variant="getQualityBadgeVariant(record.quality_rating)" class="text-xs">
                      {{ record.quality_rating }}
                    </Badge>
                    <span v-else class="text-xs text-muted-foreground">-</span>
                  </td>
                  <td class="p-3">
                    <span class="text-xs">{{ record.received_by }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>