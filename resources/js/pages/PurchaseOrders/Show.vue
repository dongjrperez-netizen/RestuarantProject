<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
    base_unit: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
  notes?: string;
  quality_rating?: string;
  condition_notes?: string;
  has_discrepancy?: boolean;
  discrepancy_reason?: string;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date?: string;
  actual_delivery_date?: string;
  delivery_condition?: string;
  received_by?: string;
  receiving_notes?: string;
  subtotal: number;
  tax_amount: number;
  shipping_amount: number;
  discount_amount: number;
  total_amount: number;
  notes?: string;
  delivery_instructions?: string;
  supplier: {
    supplier_id: number;
    supplier_name: string;
    contact_person?: string;
    phone?: string;
    email?: string;
  };
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
const approveForm = useForm({});
const cancelForm = useForm({});

const getStatusBadge = (status: string) => {
  const statusConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'; label: string }> = {
    'draft': { variant: 'secondary', label: 'Draft' },
    'pending': { variant: 'default', label: 'Pending' },
    'sent': { variant: 'default', label: 'Sent' },
    'confirmed': { variant: 'success', label: 'Confirmed' },
    'partially_delivered': { variant: 'warning', label: 'Partially Delivered' },
    'delivered': { variant: 'success', label: 'Delivered' },
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

const submitOrder = () => {
  submitForm.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/submit`);
};

const approveOrder = () => {
  approveForm.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/approve`);
};

const cancelOrder = () => {
  if (confirm('Are you sure you want to cancel this purchase order?')) {
    cancelForm.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/cancel`);
  }
};
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
          <Badge :variant="getStatusBadge(purchaseOrder.status).variant" class="text-sm px-3 py-1">
            {{ getStatusBadge(purchaseOrder.status).label }}
          </Badge>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-2">
        <Link 
          v-if="['draft', 'pending'].includes(purchaseOrder.status)"
          :href="`/purchase-orders/${purchaseOrder.purchase_order_id}/edit`"
        >
          <Button variant="outline">Edit Order</Button>
        </Link>
        
        <Button 
          v-if="purchaseOrder.status === 'draft'"
          @click="submitOrder"
          :disabled="submitForm.processing"
        >
          Submit for Approval
        </Button>

        <Button 
          v-if="purchaseOrder.status === 'pending'"
          @click="approveOrder"
          :disabled="approveForm.processing"
        >
          Approve & Send
        </Button>

        <Link 
          v-if="['confirmed', 'partially_delivered'].includes(purchaseOrder.status)"
          :href="`/purchase-orders/${purchaseOrder.purchase_order_id}/receive`"
        >
          <Button>Receive Delivery</Button>
        </Link>

        <Button 
          v-if="!['delivered', 'cancelled'].includes(purchaseOrder.status)"
          @click="cancelOrder"
          variant="destructive"
          :disabled="cancelForm.processing"
        >
          Cancel Order
        </Button>
      </div>

      <!-- Order Details -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle>Order Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-muted-foreground">Order Date</label>
                <div>{{ formatDate(purchaseOrder.order_date) }}</div>
              </div>
              <div>
                <label class="text-sm font-medium text-muted-foreground">Expected Delivery</label>
                <div>
                  <span v-if="purchaseOrder.expected_delivery_date">
                    {{ formatDate(purchaseOrder.expected_delivery_date) }}
                  </span>
                  <span v-else class="text-muted-foreground">Not specified</span>
                </div>
              </div>
            </div>
            
            <div v-if="purchaseOrder.actual_delivery_date">
              <label class="text-sm font-medium text-muted-foreground">Actual Delivery Date</label>
              <div>{{ formatDate(purchaseOrder.actual_delivery_date) }}</div>
            </div>

            <div v-if="purchaseOrder.created_by">
              <label class="text-sm font-medium text-muted-foreground">Created By</label>
              <div>{{ purchaseOrder.created_by.name }}</div>
            </div>

            <div v-if="purchaseOrder.approved_by">
              <label class="text-sm font-medium text-muted-foreground">Approved By</label>
              <div>{{ purchaseOrder.approved_by.name }}</div>
            </div>

            <div v-if="purchaseOrder.notes">
              <label class="text-sm font-medium text-muted-foreground">Notes</label>
              <div class="text-sm">{{ purchaseOrder.notes }}</div>
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

        <!-- Supplier Information -->
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label class="text-sm font-medium text-muted-foreground">Supplier Name</label>
              <div class="font-medium">{{ purchaseOrder.supplier.supplier_name }}</div>
            </div>
            
            <div v-if="purchaseOrder.supplier.contact_person">
              <label class="text-sm font-medium text-muted-foreground">Contact Person</label>
              <div>{{ purchaseOrder.supplier.contact_person }}</div>
            </div>

            <div v-if="purchaseOrder.supplier.phone">
              <label class="text-sm font-medium text-muted-foreground">Phone</label>
              <div>{{ purchaseOrder.supplier.phone }}</div>
            </div>

            <div v-if="purchaseOrder.supplier.email">
              <label class="text-sm font-medium text-muted-foreground">Email</label>
              <div>{{ purchaseOrder.supplier.email }}</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Order Items -->
      <Card>
        <CardHeader>
          <CardTitle>Order Items</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient</TableHead>
                <TableHead>Ordered Qty</TableHead>
                <TableHead>Received Qty</TableHead>
                <TableHead>Unit Price</TableHead>
                <TableHead>Total Price</TableHead>
                <TableHead>Package Unit</TableHead>
                <TableHead>Base Unit</TableHead>
                <TableHead>Quality</TableHead>
                <TableHead>Notes</TableHead>
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
                  <div class="flex items-center space-x-2">
                    <span>{{ item.received_quantity }}</span>
                    <Badge 
                      v-if="item.received_quantity > 0 && item.received_quantity < item.ordered_quantity"
                      variant="secondary"
                      class="text-xs"
                    >
                      Partial
                    </Badge>
                    <Badge 
                      v-else-if="item.received_quantity >= item.ordered_quantity"
                      variant="default"
                      class="text-xs"
                    >
                      Complete
                    </Badge>
                  </div>
                </TableCell>
                <TableCell>
                  {{ formatCurrency(item.unit_price) }}
                </TableCell>
                <TableCell class="font-medium">
                  {{ formatCurrency(item.total_price) }}
                </TableCell>
                <TableCell>
                  {{ item.unit_of_measure }}
                </TableCell>
                <TableCell>
                  <span class="text-sm text-muted-foreground">{{ item.ingredient.base_unit }}</span>
                </TableCell>
                <TableCell>
                  <div v-if="item.quality_rating" class="space-y-1">
                    <Badge 
                      :variant="item.quality_rating === 'excellent' ? 'default' : 
                               item.quality_rating === 'good' ? 'secondary' : 
                               item.quality_rating === 'fair' ? 'warning' : 'destructive'"
                      class="text-xs capitalize"
                    >
                      {{ item.quality_rating }}
                    </Badge>
                    <div v-if="item.has_discrepancy" class="flex items-center space-x-1">
                      <Badge variant="destructive" class="text-xs">
                        Discrepancy
                      </Badge>
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground text-sm">Not rated</span>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <span v-if="item.notes" class="text-sm text-muted-foreground block">{{ item.notes }}</span>
                    <span v-if="item.condition_notes" class="text-sm text-muted-foreground block">
                      <span class="font-medium">Condition:</span> {{ item.condition_notes }}
                    </span>
                    <span v-if="item.discrepancy_reason" class="text-sm text-red-600 block">
                      <span class="font-medium">Issue:</span> {{ item.discrepancy_reason }}
                    </span>
                    <span v-if="!item.notes && !item.condition_notes && !item.discrepancy_reason" class="text-muted-foreground">-</span>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Order Summary -->
      <Card>
        <CardHeader>
          <CardTitle>Order Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span>Subtotal</span>
              <span>{{ formatCurrency(purchaseOrder.subtotal) }}</span>
            </div>
            <div v-if="purchaseOrder.tax_amount > 0" class="flex justify-between">
              <span>Tax</span>
              <span>{{ formatCurrency(purchaseOrder.tax_amount) }}</span>
            </div>
            <div v-if="purchaseOrder.shipping_amount > 0" class="flex justify-between">
              <span>Shipping</span>
              <span>{{ formatCurrency(purchaseOrder.shipping_amount) }}</span>
            </div>
            <div v-if="purchaseOrder.discount_amount > 0" class="flex justify-between text-green-600">
              <span>Discount</span>
              <span>-{{ formatCurrency(purchaseOrder.discount_amount) }}</span>
            </div>
            <Separator />
            <div class="flex justify-between text-lg font-semibold">
              <span>Total Amount</span>
              <span>{{ formatCurrency(purchaseOrder.total_amount) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>