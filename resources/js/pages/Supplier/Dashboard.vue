<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Package, ShoppingCart, Clock, CheckCircle } from 'lucide-vue-next';

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  total_amount: number;
  restaurant: {
    restaurant_name: string;
  };
}

interface Stats {
  total_orders: number;
  pending_orders: number;
  total_ingredients: number;
  active_ingredients: number;
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  email: string;
}

interface Props {
  supplier: Supplier;
  recentOrders: PurchaseOrder[];
  stats: Stats;
}

defineProps<Props>();

const getStatusColor = (status: string) => {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-800';
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'sent': return 'bg-blue-100 text-blue-800';
    case 'confirmed': return 'bg-green-100 text-green-800';
    case 'delivered': return 'bg-green-100 text-green-800';
    case 'cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>

<template>
  <Head title="Supplier Dashboard" />

  <SupplierLayout :supplier="supplier">
    <div class="space-y-4 md:space-y-6 p-4 md:p-6">
      <!-- Welcome Section -->
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Welcome back, {{ supplier.supplier_name }}!</h1>
        <p class="text-sm md:text-base text-muted-foreground">Here's an overview of your supplier activities</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
            <ShoppingCart class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_orders }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending Orders</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.pending_orders }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Ingredients</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_ingredients }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Ingredients</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.active_ingredients }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Orders -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Purchase Orders</CardTitle>
          <CardDescription>
            Latest orders from restaurants
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="recentOrders.length === 0" class="text-center py-8 text-muted-foreground">
            No recent orders found
          </div>
          <div v-else class="space-y-3">
            <Link
              v-for="order in recentOrders"
              :key="order.purchase_order_id"
              :href="`/supplier/purchase-orders/${order.purchase_order_id}`"
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 md:p-4 border rounded-lg hover:bg-gray-50 transition-colors cursor-pointer gap-2"
            >
              <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <div class="font-medium text-sm md:text-base">{{ order.po_number }}</div>
                  <Badge :class="getStatusColor(order.status)" class="text-xs">
                    {{ order.status.replace('_', ' ').toUpperCase() }}
                  </Badge>
                </div>
                <div class="text-xs md:text-sm text-muted-foreground mt-1">
                  {{ order.restaurant.restaurant_name }} • {{ formatDate(order.order_date) }}
                </div>
              </div>
              <div class="text-left sm:text-right">
                <div class="font-medium text-sm md:text-base">{{ formatCurrency(order.total_amount) }}</div>
              </div>
            </Link>
          </div>
        </CardContent>
      </Card>

      <!-- Quick Actions -->
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>
            Common tasks you might want to perform
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a
              href="/supplier/ingredients/create"
              class="flex items-start p-3 md:p-4 border rounded-lg hover:bg-gray-50 transition-colors"
            >
              <Package class="h-6 w-6 md:h-8 md:w-8 text-primary mr-3 flex-shrink-0" />
              <div>
                <div class="font-medium text-sm md:text-base">Add New Ingredient</div>
                <div class="text-xs md:text-sm text-muted-foreground">Offer a new ingredient to restaurants</div>
              </div>
            </a>

            <a
              href="/supplier/ingredients"
              class="flex items-start p-3 md:p-4 border rounded-lg hover:bg-gray-50 transition-colors"
            >
              <CheckCircle class="h-6 w-6 md:h-8 md:w-8 text-primary mr-3 flex-shrink-0" />
              <div>
                <div class="font-medium text-sm md:text-base">Manage Ingredients</div>
                <div class="text-xs md:text-sm text-muted-foreground">Update your ingredient offers</div>
              </div>
            </a>

            <a
              href="/supplier/purchase-orders"
              class="flex items-start p-3 md:p-4 border rounded-lg hover:bg-gray-50 transition-colors"
            >
              <ShoppingCart class="h-6 w-6 md:h-8 md:w-8 text-primary mr-3 flex-shrink-0" />
              <div>
                <div class="font-medium text-sm md:text-base">View All Orders</div>
                <div class="text-xs md:text-sm text-muted-foreground">Manage purchase orders from restaurants</div>
              </div>
            </a>

            <!-- 🆕 View History -->
            <a
              href="/supplier/payments/history"
              class="flex items-start p-3 md:p-4 border rounded-lg hover:bg-gray-50 transition-colors"
            >
              <Clock class="h-6 w-6 md:h-8 md:w-8 text-primary mr-3 flex-shrink-0" />
              <div>
                <div class="font-medium text-sm md:text-base">View History</div>
                <div class="text-xs md:text-sm text-muted-foreground">Check your past transactions and order logs</div>
              </div>
            </a>
          </div>
        </CardContent>
      </Card>

    </div>
  </SupplierLayout>
</template>