<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, Clock, Package, CheckCircle } from 'lucide-vue-next';

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
  restaurant: {
    restaurant_name: string;
    contact_number: string;
    address: string;
  };
  items: PurchaseOrderItem[];
}

interface Props {
  purchaseOrders: PurchaseOrder[];
}

defineProps<Props>();

const getStatusBadge = (status: string) => {
  type BadgeVariant = "default" | "destructive" | "outline" | "secondary" | "success" | "warning" | undefined;
  
  const statusConfig: Record<string, { variant: BadgeVariant; label: string }> = {
    'draft': { variant: 'secondary', label: 'Draft' },
    'pending': { variant: 'warning', label: 'Pending' },
    'sent': { variant: 'default', label: 'Sent - Action Required' },
    'confirmed': { variant: 'success', label: 'Confirmed' },
    'partially_delivered': { variant: 'warning', label: 'Partially Delivered' },
    'delivered': { variant: 'success', label: 'Delivered' },
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

const needsAction = (status: string) => {
  return status === 'sent';
};
</script>

<template>
  <Head title="Purchase Orders" />

  <SupplierLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Purchase Orders</h1>
          <p class="text-muted-foreground">Manage orders from restaurants and track deliveries</p>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ purchaseOrders.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Needs Action</CardTitle>
            <Clock class="h-4 w-4 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">
              {{ purchaseOrders.filter(po => po.status === 'sent').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Confirmed</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{ purchaseOrders.filter(po => po.status === 'confirmed').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Delivered</CardTitle>
            <CheckCircle class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">
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
                <TableHead>Restaurant</TableHead>
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
                  <div>
                    <div class="font-medium">{{ order.restaurant.restaurant_name }}</div>
                    <div class="text-sm text-muted-foreground">{{ order.restaurant.contact_number }}</div>
                  </div>
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
                  <div class="flex items-center gap-2">
                    <Badge :variant="getStatusBadge(order.status).variant">
                      {{ getStatusBadge(order.status).label }}
                    </Badge>
                    <Clock 
                      v-if="needsAction(order.status)" 
                      class="h-4 w-4 text-orange-500 animate-pulse" 
                      title="Action Required"
                    />
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <Link :href="`/supplier/purchase-orders/${order.purchase_order_id}`">
                    <Button variant="outline" size="sm">
                      <Eye class="h-4 w-4 mr-2" />
                      View Details
                    </Button>
                  </Link>
                </TableCell>
              </TableRow>
              <TableRow v-if="purchaseOrders.length === 0">
                <TableCell colspan="8" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No purchase orders found</div>
                    <div class="text-sm">Orders from restaurants will appear here.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>