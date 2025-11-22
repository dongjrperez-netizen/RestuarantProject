<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
  received_by?: string;
  supplier: {
    supplier_name: string;
  } | null;
  items: PurchaseOrderItem[];
}

// Badge variants used in this page
type BadgeVariant = 'default' | 'destructive' | 'outline' | 'secondary' | 'success' | 'warning' | undefined;

interface Filters {
  search: string;
  status: string; // "default", "all", or comma-separated statuses
}

interface Props {
  purchaseOrders: PurchaseOrder[];
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
];

// Filter state
const search = ref(props.filters.search);
const status = ref(props.filters.status);

// Handle search button click
const handleSearch = () => {
  router.get('/purchase-orders', {
    search: search.value,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getStatusBadge = (order: PurchaseOrder): { variant: BadgeVariant; label: string } => {
  const status = order.status;
  const receivedBy = order.received_by;

  // Derived statuses for supplier vs owner actions
  if (status === 'partially_delivered') {
    if (!receivedBy) {
      return { variant: 'warning' as BadgeVariant, label: 'Supplier Partial  Awaiting Receive' };
    }
    return { variant: 'default' as BadgeVariant, label: 'Partially Received (Owner)' };
  }

  if (status === 'delivered') {
    if (!receivedBy) {
      return { variant: 'warning' as BadgeVariant, label: 'Supplier Delivered  Awaiting Receive' };
    }
    return { variant: 'success' as BadgeVariant, label: 'Completed (Owner)' };
  }

  const statusConfig: Record<string, { variant: BadgeVariant; label: string }> = {
    draft: { variant: 'secondary', label: 'Draft' },
    pending: { variant: 'default', label: 'Pending' },
    sent: { variant: 'default', label: 'Sent' },
    confirmed: { variant: 'default', label: 'Confirmed' },
    cancelled: { variant: 'destructive', label: 'Cancelled' },
  };

  return (
    statusConfig[status] ?? {
      variant: 'secondary' as BadgeVariant,
      label: status,
    }
  );
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getTotalItems = (items: PurchaseOrderItem[]) => {
  const total = items.reduce((sum, item) => sum + Number(item.ordered_quantity), 0);
  // Format number: remove trailing zeros and unnecessary decimals
  const formatted = parseFloat(total.toFixed(3));
  return formatted % 1 === 0 ? formatted.toFixed(0) : formatted.toString();
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
        <div>
          <Link href="/purchase-orders/create">
            <Button>Create Purchase Order</Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Total Orders</div>
            <div class="text-2xl font-bold">{{ purchaseOrders.length }}</div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Pending</div>
            <div class="text-2xl font-bold text-orange-600">
              {{ purchaseOrders.filter(po => ['draft', 'pending', 'sent'].includes(po.status)).length }}
            </div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Ready to Receive</div>
            <div class="text-2xl font-bold text-blue-600">
              {{ purchaseOrders.filter(po => {
                const status = po.status;
                const receivedBy = po.received_by;
                // Count orders that can be received: confirmed, partially_delivered, or delivered but not yet received
                return status === 'confirmed' || status === 'partially_delivered' || (status === 'delivered' && !receivedBy);
              }).length }}
            </div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Completed</div>
            <div class="text-2xl font-bold text-green-600">
              {{ purchaseOrders.filter(po => po.status === 'delivered' && po.received_by).length }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Purchase Orders Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between gap-4">
            <CardTitle>Purchase Orders</CardTitle>
            <div class="flex items-center gap-3">
              <Input
                v-model="search"
                type="text"
                placeholder="Search by PO number or supplier name..."
                class="w-80"
                @keyup.enter="handleSearch"
              />
              <Select v-model="status">
                <SelectTrigger class="w-52">
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="default">Default (Pending, Draft, Sent, Confirmed, Awaiting Receive)</SelectItem>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="sent">Sent</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="confirmed">Confirmed</SelectItem>
                  <SelectItem value="partially_delivered">Partially Delivered</SelectItem>
                  <SelectItem value="delivered">Delivered</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
              <Button @click="handleSearch">Search</Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>PO Number</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Order Date</TableHead>
                <TableHead>Items</TableHead>
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
                  {{ order.supplier?.supplier_name || 'Unknown Supplier' }}
                </TableCell>
                <TableCell>
                  {{ formatDate(order.order_date) }}
                </TableCell>
                <TableCell>
                  <div class="text-sm">
                    <div>{{ order.items.length }} types</div>
                    <div class="text-muted-foreground">{{ getTotalItems(order.items) }} total qty</div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadge(order).variant">
                    {{ getStatusBadge(order).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Link :href="`/purchase-orders/${order.purchase_order_id}`">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link
                      v-if="order.status === 'draft'"
                      :href="`/purchase-orders/${order.purchase_order_id}/edit`"
                    >
                      <Button variant="outline" size="sm">Edit</Button>
                    </Link>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="purchaseOrders.length === 0">
                <TableCell colspan="6" class="text-center py-8">
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