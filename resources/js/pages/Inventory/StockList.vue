<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface OrderItem {
  order_item_id: number;
  ingredient_name: string;
  item_type: string;
  unit: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

interface Order {
  order_id: number;
  supplier?: Supplier | null;
  reference_no?: string | null;
  status: 'Received' | 'Pending' | 'Cancelled' | string;
  total_amount: number;
  order_date: string;
  items: OrderItem[];
}

const props = defineProps<{
  stockOrders: Order[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Stock List', href: '/inventory/stock-list' },
];

const getStatusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
  switch (status) {
    case 'Received': return 'default';
    case 'Pending': return 'secondary';
    case 'Cancelled': return 'destructive';
    default: return 'outline';
  }
};
</script>

<template>
  <Head title="Stock List" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Stock In Records</h1>
          <p class="text-muted-foreground">Manage your stock-in transactions</p>
        </div>
        <Link href="/inventory/stock-in">
          <Button>+ New Stock-In</Button>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stockOrders.length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Received Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ stockOrders.filter(o => o.status === 'Received').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Pending Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ stockOrders.filter(o => o.status === 'Pending').length }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Stock Orders Table -->
      <Card>
        <CardHeader>
          <CardTitle>Stock Orders</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Reference No</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-center">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
  <TableRow
    v-for="order in stockOrders"
    :key="order.order_id"
  >
    <TableCell>
      {{ new Date(order.order_date).toLocaleDateString() }}
    </TableCell>
    <TableCell>{{ order.supplier?.supplier_name ?? '-' }}</TableCell>
    <TableCell>{{ order.reference_no ?? '-' }}</TableCell>
    <TableCell>
      <Badge :variant="getStatusVariant(order.status)">
        {{ order.status }}
      </Badge>
    </TableCell>
    <TableCell class="text-center">
      <details class="cursor-pointer">
        <summary class="text-blue-600 hover:underline text-sm">View Items</summary>
        <div class="mt-3 border rounded-lg p-3 bg-gray-50">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Unit</TableHead>
                <TableHead class="text-right">Qty</TableHead>
                <TableHead class="text-right">Unit Price</TableHead>
                <TableHead class="text-right">Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in order.items" :key="item.order_item_id">
                <TableCell>{{ item.ingredient_name ?? '-' }}</TableCell>
                <TableCell>{{ item.item_type ?? '-' }}</TableCell>
                <TableCell>{{ item.unit ?? '-' }}</TableCell>
                <TableCell class="text-right">{{ Number(item.quantity ?? 0) }}</TableCell>
                <TableCell class="text-right">₱{{ Number(item.unit_price ?? 0).toFixed(2) }}</TableCell>
                <TableCell class="text-right font-bold">₱{{ Number(item.total_price ?? 0).toFixed(2) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </details>
    </TableCell>
  </TableRow>

  <TableRow v-if="stockOrders.length === 0">
    <TableCell colspan="6" class="text-center py-8">
      <div class="text-muted-foreground">
        <div class="text-lg mb-2">No stock-in records found</div>
        <div class="text-sm">Get started by adding your first stock-in record.</div>
      </div>
    </TableCell>
  </TableRow>
</TableBody>

          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
