<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import {
  BarChart3,
  Package,
  ShoppingCart,
  DollarSign,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
  Users,
  Calendar,
  FileText,
  Download,
  Eye
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Overview {
  sales: {
    today: number;
    this_month: number;
  };
  orders: {
    today: number;
    this_month: number;
  };
  inventory: {
    low_stock_items: number;
    total_value: number;
  };
  wastage: {
    this_month_cost: number;
    this_month_count: number;
  };
}

interface Props {
  overview: Overview;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports & Analytics', href: '/reports' },
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

const reportModules = [
  {
    id: 'sales',
    title: 'Sales Performance',
    description: 'Analyze sales trends, top-selling items, and revenue patterns',
    icon: BarChart3,
    href: '/reports/sales',
    color: 'bg-blue-500',
    stats: `${formatCurrency(props.overview.sales.this_month)} this month`,
    count: `${props.overview.orders.this_month} orders`
  },
  {
    id: 'inventory',
    title: 'Inventory Analysis',
    description: 'Track stock levels, movement history, and reorder alerts',
    icon: Package,
    href: '/reports/inventory',
    color: 'bg-green-500',
    stats: formatCurrency(props.overview.inventory.total_value),
    count: `${props.overview.inventory.low_stock_items} low stock items`
  },
  {
    id: 'purchase-orders',
    title: 'Purchase Orders',
    description: 'Monitor purchase orders, supplier performance, and procurement costs',
    icon: ShoppingCart,
    href: '/reports/purchase-orders',
    color: 'bg-purple-500',
    stats: 'Track procurement',
    count: 'Supplier insights'
  },
  {
    id: 'wastage',
    title: 'Wastage & Spoilage',
    description: 'Monitor waste, spoilage costs, and identify areas for improvement',
    icon: AlertTriangle,
    href: '/reports/wastage',
    color: 'bg-red-500',
    stats: formatCurrency(props.overview.wastage.this_month_cost),
    count: `${props.overview.wastage.this_month_count} incidents this month`
  }
];
</script>

<template>
  <Head title="Reports & Analytics" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Reports & Analytics</h1>
          <p class="text-muted-foreground">Comprehensive business insights and data-driven analytics</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline">
            <Download class="w-4 h-4 mr-2" />
            Export All
          </Button>
        </div>
      </div>

      <!-- Report Modules -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card
          v-for="module in reportModules"
          :key="module.id"
          class="hover:shadow-lg transition-all duration-200 cursor-pointer group"
        >
          <CardHeader class="pb-4">
            <div class="flex items-center justify-between">
              <div :class="`h-10 w-10 ${module.color} rounded-lg flex items-center justify-center`">
                <component :is="module.icon" class="h-5 w-5 text-white" />
              </div>
              <Link :href="module.href">
                <Button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity">
                  <Eye class="h-4 w-4 mr-2" />
                  View
                </Button>
              </Link>
            </div>
            <CardTitle class="text-lg">{{ module.title }}</CardTitle>
            <CardDescription>{{ module.description }}</CardDescription>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Current Status:</span>
                <span class="font-medium">{{ module.stats }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Details:</span>
                <span class="font-medium">{{ module.count }}</span>
              </div>
            </div>
            <Link :href="module.href" class="block mt-4">
              <Button class="w-full" variant="outline">
                <FileText class="w-4 h-4 mr-2" />
                Generate Report
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>