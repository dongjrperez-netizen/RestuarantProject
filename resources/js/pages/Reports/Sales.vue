<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import {
  ArrowLeft,
  Download,
  TrendingUp,
  TrendingDown,
  DollarSign,
  ShoppingCart,
  Users,
  BarChart3,
  Calendar,
  Filter
} from 'lucide-vue-next';

interface SalesDataItem {
  period: string;
  total_sales: number;
  order_count: number;
  avg_order_value: number;
}

interface TopItem {
  dish_id: number;
  total_quantity: number;
  total_revenue: number;
  dish: {
    dish_name: string;
  };
}

interface Summary {
  total_sales: number;
  total_orders: number;
  avg_order_value: number;
  total_items_sold: number;
}

interface SalesData {
  chart_data: SalesDataItem[];
  top_items: TopItem[];
  summary: Summary;
}

interface Filters {
  date_from: string;
  date_to: string;
  group_by: string;
}

interface Props {
  salesData: SalesData;
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Sales Performance', href: '/reports/sales' },
];

const showFilters = ref(false);
const filterForm = ref({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
  group_by: props.filters.group_by,
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const applyFilters = () => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });

  router.get(`/reports/sales?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const exportReport = (format: string) => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });
  params.append('export', format);

  window.open(`/reports/sales?${params.toString()}`);
};

const chartData = computed(() => {
  return props.salesData.chart_data.map(item => ({
    period: item.period,
    total_sales: parseFloat(item.total_sales) || 0,
    order_count: item.order_count || 0,
    avg_order_value: parseFloat(item.avg_order_value) || 0,
  }));
});

const maxSales = computed(() => {
  if (chartData.value.length === 0) return 1;
  return Math.max(...chartData.value.map(item => item.total_sales));
});

const formatPeriodLabel = (period: string) => {
  // Format based on the groupBy filter
  if (props.filters.group_by === 'day') {
    // Format: 2025-09-30 -> Sep 30
    const date = new Date(period);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  } else if (props.filters.group_by === 'week') {
    // Format: 2025-40 -> Week 40
    return `Week ${period.split('-')[1]}`;
  } else {
    // Format: 2025-09 -> Sep 2025
    const [year, month] = period.split('-');
    const date = new Date(parseInt(year), parseInt(month) - 1);
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
  }
};

const salesTrend = computed(() => {
  const data = props.salesData.chart_data;
  if (data.length < 2) return 0;

  const recent = data[data.length - 1]?.total_sales || 0;
  const previous = data[data.length - 2]?.total_sales || 0;

  if (previous === 0) return 0;
  return ((recent - previous) / previous) * 100;
});

const groupByOptions = [
  { value: 'day', label: 'Daily' },
  { value: 'week', label: 'Weekly' },
  { value: 'month', label: 'Monthly' },
];
</script>

<template>
  <Head title="Sales Performance Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-6 px-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="router.get('/reports')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Reports
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Sales Performance</h1>
            <p class="text-muted-foreground">Analyze sales trends and revenue patterns</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="showFilters = !showFilters">
            <Filter class="w-4 h-4 mr-2" />
            Filters
          </Button>
          <Select @update:model-value="(format) => exportReport(format)">
            <SelectTrigger class="w-32">
              <SelectValue placeholder="Export" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pdf">
                <Download class="w-4 h-4 mr-2" />
                PDF
              </SelectItem>
              <SelectItem value="excel">
                <Download class="w-4 h-4 mr-2" />
                Excel
              </SelectItem>
              <SelectItem value="csv">
                <Download class="w-4 h-4 mr-2" />
                CSV
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Filters -->
      <Card v-if="showFilters">
        <CardHeader>
          <CardTitle>Report Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
              <Label>Date From</Label>
              <Input
                v-model="filterForm.date_from"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>
            <div class="space-y-2">
              <Label>Date To</Label>
              <Input
                v-model="filterForm.date_to"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>
            <div class="space-y-2">
              <Label>Group By</Label>
              <Select v-model="filterForm.group_by">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in groupByOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="flex items-end">
              <Button @click="applyFilters" class="w-full">
                Apply Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Sales</p>
                <p class="text-2xl font-bold">{{ formatCurrency(salesData.summary.total_sales) }}</p>
                <div class="flex items-center mt-1" v-if="salesTrend !== 0">
                  <TrendingUp v-if="salesTrend > 0" class="h-3 w-3 text-green-500 mr-1" />
                  <TrendingDown v-else class="h-3 w-3 text-red-500 mr-1" />
                  <span :class="salesTrend > 0 ? 'text-green-500' : 'text-red-500'" class="text-xs">
                    {{ Math.abs(salesTrend).toFixed(1) }}%
                  </span>
                </div>
              </div>
              <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                <DollarSign class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                <p class="text-2xl font-bold">{{ formatNumber(salesData.summary.total_orders) }}</p>
                <p class="text-xs text-muted-foreground mt-1">
                  {{ formatNumber(salesData.summary.total_items_sold) }} items sold
                </p>
              </div>
              <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                <ShoppingCart class="h-4 w-4 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Avg Order Value</p>
                <p class="text-2xl font-bold">{{ formatCurrency(salesData.summary.avg_order_value) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Per transaction</p>
              </div>
              <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                <BarChart3 class="h-4 w-4 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Sales Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Sales Trend</CardTitle>
          <CardDescription>
            {{ groupByOptions.find(opt => opt.value === filters.group_by)?.label }} sales performance
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="chartData.length > 0" class="h-80 w-full">
            <div class="flex items-end justify-between h-full gap-2 pb-8">
              <div
                v-for="(item, index) in chartData"
                :key="index"
                class="flex-1 flex flex-col items-center gap-2"
              >
                <div class="text-xs font-medium text-muted-foreground">
                  {{ formatCurrency(item.total_sales) }}
                </div>
                <div
                  class="w-full bg-blue-500 rounded-t-md hover:bg-blue-600 transition-colors cursor-pointer relative group"
                  :style="{ height: `${(item.total_sales / maxSales) * 100}%`, minHeight: '20px' }"
                  :title="`${item.period}: ${formatCurrency(item.total_sales)} (${item.order_count} orders)`"
                >
                  <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    {{ item.order_count }} orders
                  </div>
                </div>
                <div class="text-xs text-muted-foreground truncate w-full text-center">
                  {{ formatPeriodLabel(item.period) }}
                </div>
              </div>
            </div>
          </div>
          <div v-else class="h-80 w-full flex items-center justify-center text-muted-foreground">
            <div class="text-center">
              <BarChart3 class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No sales data available for the selected period</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Sales Data Table -->
      <Card>
        <CardHeader>
          <CardTitle>Sales Breakdown</CardTitle>
          <CardDescription>Detailed sales data by time period</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-4 font-semibold">Period</th>
                  <th class="text-left p-4 font-semibold">Sales</th>
                  <th class="text-left p-4 font-semibold">Orders</th>
                  <th class="text-left p-4 font-semibold">Avg Order Value</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in salesData.chart_data"
                  :key="item.period"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-4 font-medium">{{ item.period }}</td>
                  <td class="p-4">{{ formatCurrency(item.total_sales) }}</td>
                  <td class="p-4">{{ formatNumber(item.order_count) }}</td>
                  <td class="p-4">{{ formatCurrency(item.avg_order_value) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Top Selling Items -->
      <Card>
        <CardHeader>
          <CardTitle>Top Selling Items</CardTitle>
          <CardDescription>Best performing dishes by revenue</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="(item, index) in salesData.top_items"
              :key="item.dish_id"
              class="flex items-center justify-between p-4 border rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                  <span class="text-sm font-bold text-blue-600">{{ index + 1 }}</span>
                </div>
                <div>
                  <p class="font-medium">{{ item.dish.dish_name }}</p>
                  <p class="text-sm text-muted-foreground">{{ formatNumber(item.total_quantity) }} sold</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold">{{ formatCurrency(item.total_revenue) }}</p>
                <p class="text-sm text-muted-foreground">Revenue</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>