<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
    base_unit: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  supplier_delivered_quantity?: number;
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
  quality_rating?: string;
  has_discrepancy?: boolean;
  condition_notes?: string;
  discrepancy_reason?: string;
  notes?: string;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date?: string;
  actual_delivery_date?: string;
  subtotal: number;
  total_amount: number;
  notes?: string;
  delivery_instructions?: string;
  delivery_condition?: string;
  received_by?: string | null;
  receiving_notes?: string | null;
  created_by?: {
    name: string;
  } | null;
  created_by_employee?: {
    full_name: string;
  } | null;
  approved_by?: {
    name: string;
  } | null;
  supplier?: {
    phone?: string;
    email?: string;
  } | null;
  items: PurchaseOrderItem[];
  created_by?: {
    name: string;
  };
  approved_by?: {
    name: string;
  };
  bill?: {
    payments: any[];
  };
}

interface Props {
  purchaseOrder: PurchaseOrder;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: '#' },
];

const submitForm = useForm({});

const getStatusBadge = (order: PurchaseOrder) => {
  const status = order.status;
  const receivedBy = order.received_by;

  // Derived statuses for supplier vs owner
  if (status === 'partially_delivered') {
    if (!receivedBy) {
      return { variant: 'warning' as const, label: 'Supplier Partial  Awaiting Receive' };
    }
    return { variant: 'default' as const, label: 'Partially Received (Owner)' };
  }

  if (status === 'delivered') {
    if (!receivedBy) {
      return { variant: 'warning' as const, label: 'Supplier Delivered  Awaiting Receive' };
    }
    return { variant: 'success' as const, label: 'Completed (Owner)' };
  }

  const statusConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'; label: string }> = {
    'draft': { variant: 'secondary', label: 'Draft' },
    'pending': { variant: 'default', label: 'Pending' },
    'sent': { variant: 'default', label: 'Sent' },
    'confirmed': { variant: 'success', label: 'Confirmed' },
    'cancelled': { variant: 'destructive', label: 'Cancelled' }
  };
  
  return statusConfig[status] || { variant: 'secondary' as const, label: status };
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

// Extract supplier info
const supplierInfo = computed(() => {
  if (props.purchaseOrder.supplier) {
    return {
      name: props.purchaseOrder.supplier.supplier_name,
      contactPerson: props.purchaseOrder.supplier.contact_person,
      phone: props.purchaseOrder.supplier.phone,
      email: props.purchaseOrder.supplier.email,
    };
  }

  return {
    name: 'Unknown Supplier',
    contactPerson: null,
    phone: null,
    email: null,
  };
});

const submitOrder = () => {
  submitForm.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/submit`);
};

// Determine if the "Receive Delivery" button should be shown
const canReceiveDelivery = computed(() => {
  const status = props.purchaseOrder.status;
  const receivedBy = props.purchaseOrder.received_by;

  // Always show for confirmed or partially_delivered
  if (status === 'confirmed' || status === 'partially_delivered') {
    return true;
  }

  // For 'delivered' status, only show if not yet received by owner
  // (supplier marked as delivered but owner hasn't received it yet)
  if (status === 'delivered' && !receivedBy) {
    return true;
  }

  // Hide in all other cases (including when delivered and already received)
  return false;
});
</script>

<template>
  <Head :title="`Purchase Order ${purchaseOrder.po_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Purchase Order {{ purchaseOrder.po_number }}</h1>
          <p class="text-muted-foreground">View and manage purchase order details</p>
        </div>
        <div class="flex space-x-2">
          <Badge :variant="getStatusBadge(purchaseOrder).variant" class="text-sm px-3 py-1">
            {{ getStatusBadge(purchaseOrder).label }}
          </Badge>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-2">
        <Link
          v-if="purchaseOrder.status === 'draft'"
          :href="`/purchase-orders/${purchaseOrder.purchase_order_id}/edit`"
        >
          <Button variant="outline">Edit Order</Button>
        </Link>

        <Button
          v-if="purchaseOrder.status === 'draft'"
          @click="submitOrder"
          :disabled="submitForm.processing"
        >
          Create PO
        </Button>

        <Link
          v-if="canReceiveDelivery"
          :href="`/purchase-orders/${purchaseOrder.purchase_order_id}/receive`"
        >
          <Button>Receive Delivery</Button>
        </Link>
      </div>

      <!-- Order Details -->
      <Card>
        <CardHeader>
          <CardTitle>Order Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-3 gap-6">
            <div>
              <label class="text-sm font-medium text-muted-foreground">Order Date</label>
              <div>{{ formatDate(purchaseOrder.order_date) }}</div>
            </div>

            <div v-if="purchaseOrder.created_by_employee || purchaseOrder.created_by">
              <label class="text-sm font-medium text-muted-foreground">Created By</label>
              <div>
                {{ purchaseOrder.created_by_employee?.full_name ?? purchaseOrder.created_by?.name }}
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-muted-foreground">Supplier Name</label>
              <div class="font-medium">{{ supplierInfo.name }}</div>
            </div>
          </div>

          <div v-if="purchaseOrder.actual_delivery_date">
            <label class="text-sm font-medium text-muted-foreground">Actual Delivery Date</label>
            <div>{{ formatDate(purchaseOrder.actual_delivery_date) }}</div>
          </div>

          <!-- Owner Receive Status -->
          <div v-if="['partially_delivered', 'delivered'].includes(purchaseOrder.status)">
            <label class="text-sm font-medium text-muted-foreground">Owner Receive Status</label>
            <div class="text-sm">
              <template v-if="!purchaseOrder.received_by">
                <span class="text-amber-700">
                  Supplier has marked this order as
                  <strong>{{ purchaseOrder.status === 'delivered' ? 'delivered' : 'partially delivered' }}</strong>.
                  The owner has not recorded receipt yet.
                </span>
              </template>
              <template v-else>
                <span>
                  Received by <strong>{{ purchaseOrder.received_by }}</strong>
                  <span v-if="purchaseOrder.actual_delivery_date">
                    on {{ formatDate(purchaseOrder.actual_delivery_date) }}
                  </span>
                  <span v-else>
                    (date not recorded)
                  </span>.
                </span>
              </template>
            </div>
          </div>

          <div v-if="purchaseOrder.delivery_instructions">
            <label class="text-sm font-medium text-muted-foreground">Delivery Instructions</label>
            <div class="text-sm">{{ purchaseOrder.delivery_instructions }}</div>
          </div>

          <div v-if="purchaseOrder.delivery_condition">
            <label class="text-sm font-medium text-muted-foreground">Delivery Condition</label>
            <div class="capitalize">{{ purchaseOrder.delivery_condition }}</div>
          </div>

          <div v-if="purchaseOrder.received_by">
            <label class="text-sm font-medium text-muted-foreground">Received By</label>
            <div>{{ purchaseOrder.received_by }}</div>
          </div>

          <div v-if="purchaseOrder.receiving_notes">
            <label class="text-sm font-medium text-muted-foreground">Receiving Notes</label>
            <div class="text-sm">{{ purchaseOrder.receiving_notes }}</div>
          </div>
        </CardContent>
      </Card>

      <!-- Order Items -->
      <Card>
        <CardHeader>
          <CardTitle>Order Items</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="min-w-[280px]">Ingredient</TableHead>
                <TableHead class="min-w-[100px]">Ordered Qty</TableHead>
                <TableHead class="min-w-[100px]">Received Qty</TableHead>
                <TableHead class="min-w-[100px]">Base Unit</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in purchaseOrder.items" :key="item.purchase_order_item_id">
                <TableCell class="font-medium">
                  {{ item.ingredient.ingredient_name }}
                </TableCell>
                <TableCell>
                  {{ item.ordered_quantity }}
                </TableCell>
                <TableCell>
                  <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2">
                      <span>
                        {{ item.received_quantity > 0
                          ? item.received_quantity
                          : (item.supplier_delivered_quantity ?? 0)
                        }}
                      </span>
                      <Badge
                        v-if="item.received_quantity > 0 && item.received_quantity < item.ordered_quantity"
                        variant="secondary"
                        class="text-xs"
                      >
                        Partial
                      </Badge>
                      <Badge
                        v-else-if="item.received_quantity >= item.ordered_quantity && item.received_quantity > 0"
                        variant="default"
                        class="text-xs"
                      >
                        Complete
                      </Badge>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <span class="text-sm text-muted-foreground">{{ item.ingredient.base_unit }}</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>