<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

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
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date?: string;
  supplier: {
    supplier_name: string;
  } | null;
  items: PurchaseOrderItem[];
  notes?: string;
}

interface ReceiveItem {
  purchase_order_item_id: number;
  received_quantity: number;
  unit_price: number;
}

interface Props {
  purchaseOrder: PurchaseOrder;
}

const props = defineProps<Props>();

const page = usePage();
const authUser = computed(() => page.props.auth.user as any);
const currentUserName = computed(() => {
  if (!authUser.value) return '';
  return `${authUser.value.firstname || ''} ${authUser.value.lastname || ''}`.trim() || authUser.value.name || '';
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: `/purchase-orders/${props.purchaseOrder.purchase_order_id}` },
  { title: 'Receive', href: '#' },
];

const receiveItems = ref<ReceiveItem[]>(
  props.purchaseOrder.items.map(item => ({
    purchase_order_item_id: item.purchase_order_item_id,
    // Start with 0 - let user decide how much to receive
    received_quantity: 0,
    // Auto-fill with previous price if this is a partial receive, otherwise 0
    unit_price: item.received_quantity > 0 ? item.unit_price : 0
  }))
);

const form = useForm({
  received_by: currentUserName.value,
  items: receiveItems.value
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getRemainingQuantity = (item: PurchaseOrderItem) => {
  const alreadyReceived = item.received_quantity;
  const supplierTotalDelivered = item.supplier_delivered_quantity ?? 0;
  const outstandingFromSupplier = Math.max(supplierTotalDelivered - alreadyReceived, 0);

  // If supplier has delivered more than we've already received, prefer that
  // amount for this batch, but never exceed remaining ordered.
  if (outstandingFromSupplier > 0) {
    const remainingToOrder = item.ordered_quantity - alreadyReceived;
    return Math.min(outstandingFromSupplier, remainingToOrder);
  }

  // Fallback: original behavior (remaining to fully complete the order)
  return item.ordered_quantity - alreadyReceived;
};

const setFullQuantity = (index: number) => {
  const item = props.purchaseOrder.items[index];
  receiveItems.value[index].received_quantity = getRemainingQuantity(item);
};

const setAllFullQuantities = () => {
  props.purchaseOrder.items.forEach((item, index) => {
    // Receive full remaining quantity to complete the order, not just supplier's delivered amount
    receiveItems.value[index].received_quantity = item.ordered_quantity - item.received_quantity;
  });
};

const clearQuantity = (index: number) => {
  receiveItems.value[index].received_quantity = 0;
};

const clearAllQuantities = () => {
  receiveItems.value.forEach(item => {
    item.received_quantity = 0;
  });
};

const getTotalReceiving = () => {
  return receiveItems.value.reduce((sum, receiveItem) => {
    return sum + (receiveItem.received_quantity * receiveItem.unit_price);
  }, 0);
};

const getReceiveStatus = (item: PurchaseOrderItem, receiveQuantity: number): { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' } => {
  const totalAfterReceive = Number(item.received_quantity) + Number(receiveQuantity);
  const ordered = Number(item.ordered_quantity);
  const tolerance = 0.01; // Allow small floating-point differences

  // Show status based on what WILL BE received after this action
  if (totalAfterReceive === 0) return { label: 'Not Received', variant: 'secondary' };
  if (Math.abs(totalAfterReceive - ordered) < tolerance) return { label: 'Will be Complete', variant: 'success' };
  if (totalAfterReceive > 0 && totalAfterReceive < ordered) return { label: 'Will be Partial', variant: 'warning' };
  if (totalAfterReceive > ordered) return { label: 'Over Received', variant: 'destructive' };
  return { label: 'Not Received', variant: 'secondary' };
};

const calculateItemTotal = (index: number): number => {
  const receiveItem = receiveItems.value[index];
  return receiveItem.received_quantity * receiveItem.unit_price;
};

const validationError = ref<string>('');

const submit = () => {
  // Validate that all items with quantity > 0 have price > 0
  const itemsWithoutPrice = receiveItems.value.filter((item, index) => {
    const poItem = props.purchaseOrder.items[index];
    // Allow if: item has price OR item is partial receive with existing price
    return item.received_quantity > 0 && item.unit_price <= 0 && poItem.received_quantity === 0;
  });

  if (itemsWithoutPrice.length > 0) {
    validationError.value = 'Please enter a price for all new items being received. Price must be greater than 0.';
    // Scroll to top to show error
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  validationError.value = '';
  form.items = receiveItems.value;
  form.post(`/purchase-orders/${props.purchaseOrder.purchase_order_id}/receive`);
};
</script>

<template>
  <Head :title="`Receive Delivery - PO ${purchaseOrder.po_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="space-y-6 mx-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Receive Delivery</h1>
        <p class="text-muted-foreground">Record the quantities received for Purchase Order {{ purchaseOrder.po_number }}</p>
      </div>

      <!-- Validation Error Alert -->
      <div
        v-if="validationError"
        class="p-4 mb-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-400 dark:border-red-800"
      >
        <div class="font-medium">{{ validationError }}</div>
      </div>

      <!-- Order Information -->
      <Card>
        <CardHeader>
          <CardTitle>Purchase Order Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
            <div>
              <label class="text-sm font-medium text-muted-foreground">PO Number</label>
              <div class="font-medium">{{ purchaseOrder.po_number }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Supplier</label>
              <div>{{ purchaseOrder.supplier?.supplier_name || 'Manual Receive' }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Order Date</label>
              <div>{{ formatDate(purchaseOrder.order_date) }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Received By</label>
              <div>{{ form.received_by }}</div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Actual Delivery Date</label>
              <div>{{ new Date().toLocaleDateString() }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Receiving Items -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Items to Receive</CardTitle>
              <div class="flex space-x-2">
                <Button type="button" @click="setAllFullQuantities" variant="outline" size="sm">
                  Receive All
                </Button>
                <Button type="button" @click="clearAllQuantities" variant="outline" size="sm">
                  Clear All
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="(item, index) in purchaseOrder.items" :key="item.purchase_order_item_id" class="border rounded-lg p-4 space-y-4">
                <!-- Item Header -->
                <div class="flex justify-between items-start">
                  <div>
                    <h4 class="font-medium">{{ item.ingredient.ingredient_name }}</h4>
                    <p class="text-sm text-muted-foreground">
                      Ordered: {{ item.ordered_quantity }} {{ item.unit_of_measure }} |
                      Already Received: {{ item.received_quantity }} |
                      Remaining: {{ getRemainingQuantity(item) }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                      Package Unit: <span class="font-medium">{{ item.unit_of_measure }}</span> |
                      Base Unit: <span class="font-medium">{{ item.ingredient.base_unit }}</span>
                    </p>
                  </div>
                  <Badge :variant="getReceiveStatus(item, receiveItems[index].received_quantity).variant">
                    {{ getReceiveStatus(item, receiveItems[index].received_quantity).label }}
                  </Badge>
                </div>

                <!-- Receiving Details -->
                <div class="grid gap-4 md:grid-cols-5">
                  <div class="space-y-2">
                    <Label>Quantity</Label>
                    <Input
                      v-model.number="receiveItems[index].received_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      :max="getRemainingQuantity(item) * 1.1"
                    />
                  </div>

                  <div class="space-y-2">
                    <Label>Price *</Label>
                    <Input
                      v-model.number="receiveItems[index].unit_price"
                      type="number"
                      step="0.01"
                      min="0.01"
                      placeholder="0"
                      :disabled="item.received_quantity > 0"
                      :class="{
                        'border-red-500': receiveItems[index].received_quantity > 0 && receiveItems[index].unit_price <= 0 && item.received_quantity === 0,
                        'opacity-60 cursor-not-allowed': item.received_quantity > 0
                      }"
                      :title="item.received_quantity > 0 ? 'Price locked from previous receive' : ''"
                    />
                    <p v-if="item.received_quantity > 0 && receiveItems[index].unit_price > 0" class="text-xs text-green-600">
                      ✓ Using previous price
                    </p>
                    <p v-else-if="receiveItems[index].received_quantity > 0 && receiveItems[index].unit_price <= 0 && item.received_quantity === 0" class="text-xs text-red-600">
                      Price is required and must be greater than 0
                    </p>
                  </div>

                  <div class="space-y-2">
                    <Label>Unit</Label>
                    <div class="px-3 py-2 border border-input rounded-md bg-muted text-sm">
                      {{ item.ingredient.base_unit }}
                    </div>
                  </div>

                  <div class="space-y-2">
                    <Label>Total Price</Label>
                    <div class="px-3 py-2 border border-input rounded-md bg-muted text-sm font-medium">
                      {{ formatCurrency(calculateItemTotal(index)) }}
                    </div>
                  </div>

                  <div class="flex flex-col space-y-2">
                    <Label>Actions</Label>
                    <div class="flex space-x-1">
                      <Button
                        type="button"
                        @click="setFullQuantity(index)"
                        variant="outline"
                        size="sm"
                        :disabled="getRemainingQuantity(item) === 0"
                      >
                        Full
                      </Button>
                      <Button
                        type="button"
                        @click="clearQuantity(index)"
                        variant="outline"
                        size="sm"
                      >
                        Clear
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="form.errors.items" class="text-sm text-red-600 mt-4">
              {{ form.errors.items }}
            </div>
          </CardContent>
        </Card>

        <!-- Receiving Summary -->
        <Card v-if="getTotalReceiving() > 0">
          <CardHeader>
            <CardTitle>Receiving Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>Items being received:</span>
                <span>{{ receiveItems.filter(item => item.received_quantity > 0).length }} of {{ receiveItems.length }}</span>
              </div>
              <div class="flex justify-between text-lg font-semibold">
                <span>Total Value Receiving:</span>
                <span>{{ formatCurrency(getTotalReceiving()) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button 
            type="submit" 
            :disabled="form.processing || receiveItems.every(item => item.received_quantity === 0)"
          >
            {{ form.processing ? 'Processing...' : 'Confirm Receipt' }}
          </Button>
          
          <Button 
            type="button" 
            variant="outline" 
            @click="$inertia.visit(`/purchase-orders/${purchaseOrder.purchase_order_id}`)"
          >
            Cancel
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>