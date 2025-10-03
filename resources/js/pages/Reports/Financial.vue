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
  CreditCard,
  AlertTriangle,
  BarChart3,
  Calendar,
  Filter
} from 'lucide-vue-next';

interface Summary {
  revenue: number;
  expenses: number;
  wastage_cost: number;
  net_profit: number;
  profit_margin: number;
}

interface DailyBreakdown {
  date: string;
  daily_revenue: number;
  daily_orders: number;
}

interface FinancialData {
  summary: Summary;
  daily_breakdown: DailyBreakdown[];
}

interface Filters {
  date_from: string;
  date_to: string;
}

interface Props {
  financialData: FinancialData;
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Financial Summary', href: '/reports/financial' },
];

const showFilters = ref(false);
const filterForm = ref({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
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

const formatPercentage = (value: number) => {
  return `${value >= 0 ? '+' : ''}${value.toFixed(1)}%`;
};

const applyFilters = () => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });

  router.get(`/reports/financial?${params.toString()}`, {}, {
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

  window.open(`/reports/financial?${params.toString()}`);
};

const profitabilityStatus = computed(() => {
  const margin = props.financialData.summary.profit_margin;
  if (margin >= 15) return { status: 'excellent', color: 'text-green-600', bg: 'bg-green-100' };
  if (margin >= 10) return { status: 'good', color: 'text-blue-600', bg: 'bg-blue-100' };
  if (margin >= 5) return { status: 'fair', color: 'text-yellow-600', bg: 'bg-yellow-100' };
  return { status: 'poor', color: 'text-red-600', bg: 'bg-red-100' };
});

const chartData = computed(() => {
  return props.financialData.daily_breakdown.map(item => ({
    date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    revenue: item.daily_revenue,
    orders: item.daily_orders,
  }));
});

const avgDailyRevenue = computed(() => {
  const total = props.financialData.daily_breakdown.reduce((sum, item) => sum + item.daily_revenue, 0);
  return props.financialData.daily_breakdown.length > 0 ? total / props.financialData.daily_breakdown.length : 0;
});

const totalOrders = computed(() => {
  return props.financialData.daily_breakdown.reduce((sum, item) => sum + item.daily_orders, 0);
});
</script>

<template>
  <Head title="Financial Summary Report" />

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
            <h1 class="text-3xl font-bold tracking-tight">Financial Summary</h1>
            <p class="text-muted-foreground">Review revenue, expenses, profit margins, and financial health</p>
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
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <div class="flex items-end">
              <Button @click="applyFilters" class="w-full">
                Apply Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Key Financial Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(financialData.summary.revenue) }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ totalOrders }} orders</p>
              </div>
              <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                <TrendingUp class="h-4 w-4 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(financialData.summary.expenses) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Operating costs</p>
              </div>
              <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                <CreditCard class="h-4 w-4 text-red-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Net Profit</p>
                <p class="text-2xl font-bold" :class="financialData.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ formatCurrency(financialData.summary.net_profit) }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">After all expenses</p>
              </div>
              <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="financialData.summary.net_profit >= 0 ? 'bg-green-100' : 'bg-red-100'">
                <DollarSign class="h-4 w-4" :class="financialData.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Profit Margin</p>
                <p class="text-2xl font-bold" :class="profitabilityStatus.color">
                  {{ formatPercentage(financialData.summary.profit_margin) }}
                </p>
                <Badge :class="profitabilityStatus.bg + ' ' + profitabilityStatus.color" class="text-xs mt-1">
                  {{ profitabilityStatus.status.toUpperCase() }}
                </Badge>
              </div>
              <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="profitabilityStatus.bg">
                <BarChart3 class="h-4 w-4" :class="profitabilityStatus.color" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Profitability Analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Cost Breakdown</CardTitle>
            <CardDescription>Analysis of revenue vs expenses</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span class="font-medium">Revenue</span>
                </div>
                <span class="font-bold text-green-600">{{ formatCurrency(financialData.summary.revenue) }}</span>
              </div>

              <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  <span class="font-medium">Operating Expenses</span>
                </div>
                <span class="font-bold text-red-600">{{ formatCurrency(financialData.summary.expenses) }}</span>
              </div>

              <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                  <span class="font-medium">Wastage Costs</span>
                </div>
                <span class="font-bold text-orange-600">{{ formatCurrency(financialData.summary.wastage_cost) }}</span>
              </div>

              <div class="border-t pt-3">
                <div class="flex items-center justify-between p-3 rounded-lg" :class="financialData.summary.net_profit >= 0 ? 'bg-green-100' : 'bg-red-100'">
                  <div class="flex items-center space-x-2">
                    <TrendingUp v-if="financialData.summary.net_profit >= 0" class="w-4 h-4 text-green-600" />
                    <TrendingDown v-else class="w-4 h-4 text-red-600" />
                    <span class="font-bold">Net Profit</span>
                  </div>
                  <span class="font-bold text-lg" :class="financialData.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ formatCurrency(financialData.summary.net_profit) }}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Key Performance Indicators</CardTitle>
            <CardDescription>Financial health metrics</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-6">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Profit Margin</span>
                  <span class="font-medium">{{ formatPercentage(financialData.summary.profit_margin) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full"
                    :class="profitabilityStatus.color.replace('text-', 'bg-')"
                    :style="{ width: `${Math.min(Math.abs(financialData.summary.profit_margin), 100)}%` }"
                  ></div>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Avg Daily Revenue</span>
                  <span class="font-medium">{{ formatCurrency(avgDailyRevenue) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Cost Ratio</span>
                  <span class="font-medium">
                    {{ ((financialData.summary.expenses / financialData.summary.revenue) * 100).toFixed(1) }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-orange-500 h-2 rounded-full"
                    :style="{ width: `${(financialData.summary.expenses / financialData.summary.revenue) * 100}%` }"
                  ></div>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Wastage Impact</span>
                  <span class="font-medium">
                    {{ ((financialData.summary.wastage_cost / financialData.summary.revenue) * 100).toFixed(1) }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-red-500 h-2 rounded-full"
                    :style="{ width: `${(financialData.summary.wastage_cost / financialData.summary.revenue) * 100}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Revenue Trend Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Daily Revenue Trend</CardTitle>
          <CardDescription>Daily performance over the selected period</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-80 w-full flex items-center justify-center text-muted-foreground">
            <div class="text-center">
              <BarChart3 class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>Revenue trend chart visualization will be implemented with chart library</p>
              <p class="text-sm mt-2">Chart data: {{ chartData.length }} data points</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Daily Breakdown Table -->
      <Card>
        <CardHeader>
          <CardTitle>Daily Financial Breakdown</CardTitle>
          <CardDescription>Detailed daily revenue and order count</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-4 font-semibold">Date</th>
                  <th class="text-left p-4 font-semibold">Revenue</th>
                  <th class="text-left p-4 font-semibold">Orders</th>
                  <th class="text-left p-4 font-semibold">Avg Order Value</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in financialData.daily_breakdown"
                  :key="item.date"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-4 font-medium">
                    {{ new Date(item.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                  </td>
                  <td class="p-4">{{ formatCurrency(item.daily_revenue) }}</td>
                  <td class="p-4">{{ formatNumber(item.daily_orders) }}</td>
                  <td class="p-4">
                    {{ item.daily_orders > 0 ? formatCurrency(item.daily_revenue / item.daily_orders) : formatCurrency(0) }}
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