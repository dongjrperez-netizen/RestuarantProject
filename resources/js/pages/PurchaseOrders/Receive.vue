<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxList,
  ComboboxItem,
  ComboboxEmpty,
  ComboboxTrigger
} from '@/components/ui/combobox';
import { ChevronDown, Search } from 'lucide-vue-next';
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
  quality_rating: 'excellent' | 'good' | 'fair' | 'poor' | '';
  condition_notes: string;
  has_discrepancy: boolean;
  discrepancy_reason: string;
}

interface Staff {
  id: number;
  name: string;
}

interface Props {
  purchaseOrder: PurchaseOrder;
  staff: Staff[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: `/purchase-orders/${props.purchaseOrder.purchase_order_id}` },
  { title: 'Receive', href: '#' },
];

const receiveItems = ref<ReceiveItem[]>(
  props.purchaseOrder.items.map(item => ({
    purchase_order_item_id: item.purchase_order_item_id,
    // Pre-fill with supplier-delivered quantity minus already received, so owner can confirm
    received_quantity: Math.max((item.supplier_delivered_quantity ?? 0) - item.received_quantity, 0),
    quality_rating: '',
    condition_notes: '',
    has_discrepancy: false,
    discrepancy_reason: ''
  }))
);

const staffSearchTerm = ref('');
const comboboxValue = ref<Staff | undefined>();

const form = useForm({
  actual_delivery_date: new Date().toISOString().split('T')[0],
  delivery_condition: 'good',
  received_by: '',
  general_notes: '',
  items: receiveItems.value
});

// Filtered staff based on search
const filteredStaff = computed(() => {
  if (!staffSearchTerm.value) {
    // Show only first 5 by default
    return props.staff.slice(0, 5);
  }
  // Show all matching results when searching
  return props.staff.filter(staff =>
    staff.name.toLowerCase().includes(staffSearchTerm.value.toLowerCase())
  );
});

// Handle staff selection
const handleStaffSelect = (value: any) => {
  if (value && typeof value === 'object' && 'name' in value) {
    form.received_by = value.name;
    comboboxValue.value = value;
  }
};

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
    receiveItems.value[index].received_quantity = getRemainingQuantity(item);
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
  return receiveItems.value.reduce((sum, receiveItem, index) => {
    const item = props.purchaseOrder.items[index];
    return sum + (receiveItem.received_quantity * item.unit_price);
  }, 0);
};

const getReceiveStatus = (item: PurchaseOrderItem, receiveQuantity: number): { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' } => {
  const totalAfterReceive = item.received_quantity + receiveQuantity;
  const ordered = item.ordered_quantity;
  
  if (totalAfterReceive === 0) return { label: 'Not Received', variant: 'secondary' };
  if (totalAfterReceive < ordered) return { label: 'Partial', variant: 'warning' };
  if (totalAfterReceive === ordered) return { label: 'Complete', variant: 'success' };
  return { label: 'Over Received', variant: 'destructive' };
};

const getQualityBadge = (rating: string): { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' } => {
  const qualityConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' }> = {
    'excellent': { variant: 'success' },
    'good': { variant: 'default' },
    'fair': { variant: 'warning' },
    'poor': { variant: 'destructive' }
  };
  return qualityConfig[rating] || { variant: 'secondary' };
};

const checkForDiscrepancy = (index: number) => {
  const item = props.purchaseOrder.items[index];
  const receiveItem = receiveItems.value[index];
  
  const hasQuantityDiscrepancy = receiveItem.received_quantity !== getRemainingQuantity(item);
  const hasQualityIssue = receiveItem.quality_rating === 'fair' || receiveItem.quality_rating === 'poor';
  
  receiveItems.value[index].has_discrepancy = hasQuantityDiscrepancy || hasQualityIssue;
  
  if (!receiveItems.value[index].has_discrepancy) {
    receiveItems.value[index].discrepancy_reason = '';
  }
};

const submit = () => {
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

      <!-- Order Information -->
      <Card>
        <CardHeader>
          <CardTitle>Purchase Order Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
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
              <label class="text-sm font-medium text-muted-foreground">Expected Delivery</label>
              <div>
                <span v-if="purchaseOrder.expected_delivery_date">
                  {{ formatDate(purchaseOrder.expected_delivery_date) }}
                </span>
                <span v-else class="text-muted-foreground">Not specified</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Delivery Information -->
        <Card>
          <CardHeader>
            <CardTitle>Delivery Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-3">
              <div class="space-y-2">
                <Label for="actual_delivery_date">Actual Delivery Date *</Label>
                <Input
                  id="actual_delivery_date"
                  v-model="form.actual_delivery_date"
                  type="date"
                  required
                />
                <div v-if="form.errors.actual_delivery_date" class="text-sm text-red-600">
                  {{ form.errors.actual_delivery_date }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="delivery_condition">Overall Delivery Condition</Label>
                <select
                  id="delivery_condition"
                  v-model="form.delivery_condition"
                  class="w-full px-3 py-2 border border-input rounded-md shadow-sm bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring"
                >
                  <option value="excellent">Excellent</option>
                  <option value="good">Good</option>
                  <option value="fair">Fair</option>
                  <option value="poor">Poor</option>
                </select>
              </div>

              <div class="space-y-2">
                <Label for="received_by">Received By *</Label>
                <Combobox v-model="comboboxValue" by="name" @update:model-value="handleStaffSelect">
                  <ComboboxAnchor as-child>
                    <ComboboxTrigger as-child>
                      <Button variant="outline" class="w-full justify-between">
                        {{ comboboxValue?.name ?? 'Select staff member...' }}
                        <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                      </Button>
                    </ComboboxTrigger>
                  </ComboboxAnchor>

                  <ComboboxList class="w-[--reka-combobox-trigger-width] max-h-[200px]">
                    <div class="relative w-full max-w-sm items-center">
                      <ComboboxInput
                        v-model:search-term="staffSearchTerm"
                        class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10"
                        placeholder="Search staff..."
                      />
                      <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                        <Search class="size-4 text-muted-foreground" />
                      </span>
                    </div>

                    <ComboboxEmpty>No staff found</ComboboxEmpty>

                    <ComboboxItem
                      v-for="staff in filteredStaff"
                      :key="staff.id"
                      :value="staff"
                      class="cursor-pointer"
                    >
                      {{ staff.name }}
                    </ComboboxItem>
                  </ComboboxList>
                </Combobox>
                <div v-if="form.errors.received_by" class="text-sm text-red-600">
                  {{ form.errors.received_by }}
                </div>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <Label for="general_notes">General Delivery Notes</Label>
              <textarea
                id="general_notes"
                v-model="form.general_notes"
                rows="3"
                class="w-full px-3 py-2 border border-input rounded-md shadow-sm bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring"
                placeholder="Add any general notes about the delivery..."
              ></textarea>
            </div>
          </CardContent>
        </Card>

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
                <div class="grid gap-4 md:grid-cols-4">
                  <div class="space-y-2">
                    <Label>Receiving Now</Label>
                    <Input
                      v-model.number="receiveItems[index].received_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      :max="getRemainingQuantity(item) * 1.1"
                      @input="checkForDiscrepancy(index)"
                    />
                  </div>

                  <div class="space-y-2">
                    <Label>Quality Rating</Label>
                    <select
                      v-model="receiveItems[index].quality_rating"
                      @change="checkForDiscrepancy(index)"
                      class="w-full px-3 py-2 border border-input rounded-md shadow-sm bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring"
                    >
                      <option value="">Select Quality</option>
                      <option value="excellent">Excellent</option>
                      <option value="good">Good</option>
                      <option value="fair">Fair</option>
                      <option value="poor">Poor</option>
                    </select>
                    <Badge v-if="receiveItems[index].quality_rating" :variant="getQualityBadge(receiveItems[index].quality_rating).variant" class="text-xs">
                      {{ receiveItems[index].quality_rating.charAt(0).toUpperCase() + receiveItems[index].quality_rating.slice(1) }}
                    </Badge>
                  </div>

                  <div class="space-y-2">
                    <Label>Condition Notes</Label>
                    <Input
                      v-model="receiveItems[index].condition_notes"
                      type="text"
                      placeholder="e.g., Fresh, slightly damaged"
                    />
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

                <!-- Discrepancy Section -->
                <div v-if="receiveItems[index].has_discrepancy" class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                  <div class="flex items-center space-x-2 mb-2">
                    <Badge variant="warning">Discrepancy Detected</Badge>
                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Please provide reason</span>
                  </div>
                  <textarea
                    v-model="receiveItems[index].discrepancy_reason"
                    rows="2"
                    class="w-full px-3 py-2 border border-yellow-300 dark:border-yellow-700 rounded-md shadow-sm bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                    placeholder="Explain the discrepancy (quantity difference, quality issues, etc.)"
                    required
                  ></textarea>
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