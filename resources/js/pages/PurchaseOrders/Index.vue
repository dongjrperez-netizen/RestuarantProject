<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';

interface PurchaseOrderItem {
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  received_quantity: number;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date: string;
  total_amount: number;
  supplier: {
    supplier_name: string;
  };
  items: PurchaseOrderItem[];
}

interface Props {
  purchaseOrders: PurchaseOrder[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
];

const getStatusBadge = (status: string) => {
  type BadgeVariant = "default" | "destructive" | "outline" | "secondary" | "success" | "warning" | undefined;
  
  const statusConfig: Record<string, { variant: BadgeVariant; label: string }> = {
    'draft': { variant: 'secondary', label: 'Draft' },
    'pending': { variant: 'default', label: 'Pending' },
    'sent': { variant: 'default', label: 'Sent' },
    'confirmed': { variant: 'default', label: 'Confirmed' },
    'partially_delivered': { variant: 'default', label: 'Partially Delivered' },
    'delivered': { variant: 'default', label: 'Delivered' },
    'cancelled': { variant: 'destructive', label: 'Cancelled' }
  };
  
  return statusConfig[status] || { variant: 'secondary' as BadgeVariant, label: status };
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getTotalItems = (items: PurchaseOrderItem[]) => {
  return items.reduce((sum, item) => sum + item.ordered_quantity, 0);
};
</script>

<template>
  <Head title="Purchase Orders" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Purchase Orders</h1>
          <p class="text-muted-foreground">Manage your purchase orders and track deliveries</p>
        </div>
        <Link href="/purchase-orders/create">
          <Button>Create Purchase Order</Button>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ purchaseOrders.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">
              {{ purchaseOrders.filter(po => ['draft', 'pending', 'sent'].includes(po.status)).length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">In Transit</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">
              {{ purchaseOrders.filter(po => ['confirmed', 'partially_delivered'].includes(po.status)).length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Delivered</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{ purchaseOrders.filter(po => po.status === 'delivered').length }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Purchase Orders Table -->
      <Card>
        <CardHeader>
          <CardTitle>Purchase Orders</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>PO Number</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Order Date</TableHead>
                <TableHead>Expected Delivery</TableHead>
                <TableHead>Items</TableHead>
                <TableHead>Total Amount</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="order in purchaseOrders" :key="order.purchase_order_id">
                <TableCell class="font-medium">
                  {{ order.po_number }}
                </TableCell>
                <TableCell>
                  {{ order.supplier.supplier_name }}
                </TableCell>
                <TableCell>
                  {{ formatDate(order.order_date) }}
                </TableCell>
                <TableCell>
                  <span v-if="order.expected_delivery_date">
                    {{ formatDate(order.expected_delivery_date) }}
                  </span>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell>
                  <div class="text-sm">
                    <div>{{ order.items.length }} types</div>
                    <div class="text-muted-foreground">{{ getTotalItems(order.items) }} total qty</div>
                  </div>
                </TableCell>
                <TableCell class="font-medium">
                  {{ formatCurrency(order.total_amount) }}
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadge(order.status).variant">
                    {{ getStatusBadge(order.status).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Link :href="`/purchase-orders/${order.purchase_order_id}`">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link 
                      v-if="['draft', 'pending'].includes(order.status)"
                      :href="`/purchase-orders/${order.purchase_order_id}/edit`"
                    >
                      <Button variant="outline" size="sm">Edit</Button>
                    </Link>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="purchaseOrders.length === 0">
                <TableCell colspan="8" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No purchase orders found</div>
                    <div class="text-sm">Create your first purchase order to get started.</div>
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