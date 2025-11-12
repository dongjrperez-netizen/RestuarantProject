<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
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
  Banknote
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

interface Props {
  inventoryData: InventoryData;
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
  return status === 'low' ? 'text-red-600' : 'text-green-600';
};

const getStockBadgeVariant = (status: string) => {
  return status === 'low' ? 'destructive' : 'secondary';
};

const lowStockItems = props.inventoryData.items.filter(item => item.stock_status === 'low');
const highValueItems = [...props.inventoryData.items]
  .sort((a, b) => b.total_value - a.total_value)
  .slice(0, 10);
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
            <p class="text-muted-foreground">Track stock levels, movement history, and reorder alerts</p>
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
              <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                <Package class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Low Stock Items</p>
                <p class="text-2xl font-bold text-red-600">{{ formatNumber(inventoryData.summary.low_stock_items) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Need reordering</p>
              </div>
              <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                <AlertTriangle class="h-4 w-4 text-red-600" />
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
              <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                <Banknote class="h-4 w-4 text-green-600" />
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
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Normal Stock</span>
                <span class="text-sm text-muted-foreground">{{ inventoryData.stock_levels.normal || 0 }} items</span>
              </div>
              <Progress :value="((inventoryData.stock_levels.normal || 0) / inventoryData.summary.total_items) * 100" class="h-2" />

              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-red-600">Low Stock</span>
                <span class="text-sm text-muted-foreground">{{ inventoryData.stock_levels.low || 0 }} items</span>
              </div>
              <Progress
                :value="((inventoryData.stock_levels.low || 0) / inventoryData.summary.total_items) * 100"
                class="h-2 text-red-600"
              />
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
          <CardTitle class="flex items-center gap-2 text-red-600">
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
              class="flex items-center justify-between p-3 border border-red-200 rounded-lg bg-red-50"
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
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                  <span class="text-sm font-bold text-blue-600">{{ index + 1 }}</span>
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
                    <span v-if="item.exclusion_count > 0" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
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
                    <div class="w-20">
                      <Progress
                        :value="getStockPercentage(item.current_stock, item.reorder_level)"
                        :class="item.stock_status === 'low' ? 'text-red-600' : ''"
                        class="h-2"
                      />
                    </div>
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