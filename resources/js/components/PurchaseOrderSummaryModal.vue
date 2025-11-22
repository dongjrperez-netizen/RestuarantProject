<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { X } from 'lucide-vue-next';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
    base_unit: string;
  };
  ordered_quantity: number;
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
  subtotal: number;
  tax_amount: number;
  shipping_amount: number;
  discount_amount: number;
  total_amount: number;
  notes?: string;
  supplier?: {
    supplier_id: number;
    supplier_name: string;
    email?: string;
    phone?: string;
  };
  items: PurchaseOrderItem[];
}

interface Props {
  purchaseOrder: PurchaseOrder;
  isOpen: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  viewDetails: [];
}>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const handleViewDetails = () => {
  emit('viewDetails');
};

const handleClose = () => {
  emit('close');
};
</script>

<template>
  <!-- Modal Overlay -->
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    @click.self="handleClose"
  >
    <!-- Modal Content -->
    <Card class="w-full max-w-2xl max-h-[90vh] overflow-auto">
      <!-- Modal Header -->
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4 sticky top-0 bg-white border-b">
        <CardTitle>Purchase Order Summary</CardTitle>
        <Button
          variant="ghost"
          size="sm"
          class="h-8 w-8 p-0"
          @click="handleClose"
        >
          <X class="h-4 w-4" />
        </Button>
      </CardHeader>

      <CardContent class="space-y-6 pt-6">
        <!-- PO Header Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-muted-foreground">PO Number</label>
            <div class="text-lg font-semibold">{{ purchaseOrder.po_number }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-muted-foreground">Status</label>
            <div class="text-lg font-semibold capitalize">{{ purchaseOrder.status }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-muted-foreground">Order Date</label>
            <div>{{ formatDate(purchaseOrder.order_date) }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-muted-foreground">Expected Delivery</label>
            <div>{{ purchaseOrder.expected_delivery_date ? formatDate(purchaseOrder.expected_delivery_date) : 'Not specified' }}</div>
          </div>
        </div>

        <Separator />

        <!-- Supplier Information -->
        <div v-if="purchaseOrder.supplier">
          <label class="text-sm font-medium text-muted-foreground block mb-2">Supplier</label>
          <div class="text-lg font-semibold">{{ purchaseOrder.supplier.supplier_name }}</div>
          <div class="text-sm text-muted-foreground">
            <div v-if="purchaseOrder.supplier.email">{{ purchaseOrder.supplier.email }}</div>
            <div v-if="purchaseOrder.supplier.phone">{{ purchaseOrder.supplier.phone }}</div>
          </div>
        </div>

        <Separator />

        <!-- Items Summary -->
        <div>
          <label class="text-sm font-medium text-muted-foreground block mb-3">Order Items ({{ purchaseOrder.items.length }})</label>
          <div class="space-y-2">
            <div
              v-for="item in purchaseOrder.items"
              :key="item.purchase_order_item_id"
              class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded"
            >
              <div class="flex-1">
                <div class="font-medium">{{ item.ingredient.ingredient_name }}</div>
                <div class="text-sm text-muted-foreground">
                  {{ item.ordered_quantity }} {{ item.unit_of_measure }} @ {{ formatCurrency(item.unit_price) }}
                </div>
              </div>
              <div class="font-semibold text-right">{{ formatCurrency(item.total_price) }}</div>
            </div>
          </div>
        </div>

        <Separator />

        <!-- Order Totals -->
        <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
          <div class="flex justify-between">
            <span class="font-medium">Subtotal</span>
            <span>{{ formatCurrency(purchaseOrder.subtotal) }}</span>
          </div>
          <div v-if="purchaseOrder.tax_amount > 0" class="flex justify-between">
            <span class="font-medium">Tax</span>
            <span>{{ formatCurrency(purchaseOrder.tax_amount) }}</span>
          </div>
          <div v-if="purchaseOrder.shipping_amount > 0" class="flex justify-between">
            <span class="font-medium">Shipping</span>
            <span>{{ formatCurrency(purchaseOrder.shipping_amount) }}</span>
          </div>
          <div v-if="purchaseOrder.discount_amount > 0" class="flex justify-between text-green-600">
            <span class="font-medium">Discount</span>
            <span>-{{ formatCurrency(purchaseOrder.discount_amount) }}</span>
          </div>
          <Separator />
          <div class="flex justify-between text-xl font-bold">
            <span>Total Amount</span>
            <span class="text-green-600">{{ formatCurrency(purchaseOrder.total_amount) }}</span>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="purchaseOrder.notes">
          <label class="text-sm font-medium text-muted-foreground">Notes</label>
          <div class="text-sm mt-1 p-3 bg-gray-50 rounded">{{ purchaseOrder.notes }}</div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4">
          <Button
            @click="handleViewDetails"
            class="flex-1"
          >
            View Full Details
          </Button>
          <Button
            @click="handleClose"
            variant="outline"
            class="flex-1"
          >
            Close
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
