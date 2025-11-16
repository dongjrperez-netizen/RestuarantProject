<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ArrowLeft, CheckCircle, XCircle, Package, Calendar, User } from 'lucide-vue-next';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  supplier_delivered_quantity?: number; // outstanding delivered but not yet received
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
  notes?: string;
}

interface DeliveredItem {
  purchase_order_item_id: number;
  ordered_quantity: number;
  delivered_quantity: number;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_delivery_date: string;
  actual_delivery_date?: string;
  subtotal: number;
  total_amount: number;
  received_by?: string | null;
  notes?: string;
  delivery_instructions?: string;
  restaurant: {
    restaurant_name: string;
    contact_number: string;
    address: string;
  };
  items: PurchaseOrderItem[];
  created_by: {
    first_name: string;
    last_name: string;
    email: string;
  };
}

interface Props {
  purchaseOrder: PurchaseOrder;
}

const props = defineProps<Props>();

const showConfirmDialog = ref(false);
const showRejectDialog = ref(false);
const showDeliveredDialog = ref(false);

const toNumber = (value: unknown): number => {
  if (typeof value === 'number') return value;
  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
  }
  return 0;
};

const getRemainingQuantity = (item: PurchaseOrderItem) => {
  const ordered = toNumber(item.ordered_quantity);
  const delivered = toNumber(item.supplier_delivered_quantity);
  const remaining = ordered - delivered;
  return remaining > 0 ? remaining : 0;
};

const deliveredItems = ref<DeliveredItem[]>(
  props.purchaseOrder.items.map(item => ({
    purchase_order_item_id: item.purchase_order_item_id,
    ordered_quantity: item.ordered_quantity,
    delivered_quantity: getRemainingQuantity(item),
  }))
);

const getMaxDeliverable = (row: DeliveredItem) => {
  const base = props.purchaseOrder.items.find(i => i.purchase_order_item_id === row.purchase_order_item_id);
  if (!base) return row.ordered_quantity;
  return getRemainingQuantity(base);
};

const confirmForm = useForm({
  expected_delivery_date: props.purchaseOrder.expected_delivery_date || '',
  notes: ''
});

const rejectForm = useForm({
  rejection_reason: ''
});

const deliveredForm = useForm({
  delivery_date: new Date().toISOString().split('T')[0],
  delivery_type: 'full' as 'full' | 'partial',
  delivery_notes: '',
  items: deliveredItems.value,
});

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

const canConfirm = (status: string) => {
  return ['sent', 'pending'].includes(status);
};

const canReject = (status: string) => {
  return ['sent', 'pending'].includes(status);
};

const canMarkDelivered = (order: PurchaseOrder) => {
  const status = order.status;

  // If the owner has already fully received this order, supplier should no longer mark it as delivered
  const fullyReceivedByOwner = status === 'delivered' && !!order.received_by;
  if (fullyReceivedByOwner) {
    return false;
  }

  // Allow marking delivered when confirmed or already partially delivered
  return ['confirmed', 'partially_delivered'].includes(status);
};

const confirmOrder = () => {
  confirmForm.post(`/supplier/purchase-orders/${props.purchaseOrder.purchase_order_id}/confirm`, {
    onSuccess: () => {
      showConfirmDialog.value = false;
      confirmForm.reset();
    }
  });
};

const rejectOrder = () => {
  rejectForm.post(`/supplier/purchase-orders/${props.purchaseOrder.purchase_order_id}/reject`, {
    onSuccess: () => {
      showRejectDialog.value = false;
      rejectForm.reset();
    }
  });
};

const openDeliveredDialog = () => {
  // Always use today's date for delivery date
  deliveredForm.delivery_date = new Date().toISOString().split('T')[0];

  // Refresh remaining quantities each time dialog is opened
  deliveredItems.value = props.purchaseOrder.items.map(item => ({
    purchase_order_item_id: item.purchase_order_item_id,
    ordered_quantity: item.ordered_quantity,
    delivered_quantity: getRemainingQuantity(item),
  }));
  showDeliveredDialog.value = true;
};

const markDelivered = () => {
  // Derive full vs partial based on cumulative delivered quantities (previous + this batch)
  const items = deliveredItems.value;
  const allFull = items.every(row => {
    const base = props.purchaseOrder.items.find(i => i.purchase_order_item_id === row.purchase_order_item_id);
    if (!base) return false;
    const alreadyDelivered = toNumber(base.supplier_delivered_quantity);
    const cumulative = alreadyDelivered + row.delivered_quantity;
    return cumulative >= toNumber(base.ordered_quantity) && cumulative > 0;
  });
  deliveredForm.delivery_type = allFull ? 'full' : 'partial';

  // Build a concise summary into delivery_notes (e.g. "Chicken: 5/10 kg; Rice: 10/10 kg")
  deliveredForm.delivery_notes = items
    .map(item => {
      const base = props.purchaseOrder.items.find(i => i.purchase_order_item_id === item.purchase_order_item_id);
      if (!base) return null;
      return `${base.ingredient.ingredient_name}: ${item.delivered_quantity}/${item.ordered_quantity} ${base.unit_of_measure}`;
    })
    .filter((s): s is string => !!s)
    .join('; ');

  // Sync items into the form payload (backend currently ignores, but we send for future use)
  (deliveredForm as any).items = items;

  deliveredForm.post(`/supplier/purchase-orders/${props.purchaseOrder.purchase_order_id}/mark-delivered`, {
    onSuccess: () => {
      showDeliveredDialog.value = false;
      deliveredForm.reset();
      // Re-init deliveredItems after reset so dialog starts with remaining quantities next time
      deliveredItems.value = props.purchaseOrder.items.map(item => ({
        purchase_order_item_id: item.purchase_order_item_id,
        ordered_quantity: item.ordered_quantity,
        delivered_quantity: getRemainingQuantity(item),
      }));
    }
  });
};
</script>

<template>
  <Head :title="`Purchase Order ${purchaseOrder.po_number}`" />

  <SupplierLayout>
    <div class="space-y-4 md:space-y-6 p-4 md:p-6">
      <!-- Header -->
      <div class="flex flex-col space-y-3">
        <Link href="/supplier/purchase-orders">
          <Button variant="ghost" size="sm" class="self-start">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back to Orders
          </Button>
        </Link>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex-1">
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">{{ purchaseOrder.po_number }}</h1>
            <p class="text-sm md:text-base text-muted-foreground">Purchase order from {{ purchaseOrder.restaurant.restaurant_name }}</p>
          </div>
          <Badge :variant="getStatusBadge(purchaseOrder.status).variant" class="self-start sm:self-center">
            {{ getStatusBadge(purchaseOrder.status).label }}
          </Badge>
        </div>
      </div>

      <!-- Action Buttons -->
      <div v-if="canConfirm(purchaseOrder.status) || canReject(purchaseOrder.status) || canMarkDelivered(purchaseOrder)" class="flex flex-col sm:flex-row gap-2">
        <Button 
          v-if="canConfirm(purchaseOrder.status)"
          @click="showConfirmDialog = true"
          class="bg-green-600 hover:bg-green-700"
        >
          <CheckCircle class="h-4 w-4 mr-2" />
          Confirm Order
        </Button>
        
        <Button 
          v-if="canReject(purchaseOrder.status)"
          @click="showRejectDialog = true"
          variant="destructive"
        >
          <XCircle class="h-4 w-4 mr-2" />
          Reject Order
        </Button>

        <Button 
          v-if="canMarkDelivered(purchaseOrder)"
          @click="openDeliveredDialog"
          class="bg-blue-600 hover:bg-blue-700"
        >
          <Package class="h-4 w-4 mr-2" />
          Mark as Delivered
        </Button>
      </div>

      <div class="grid gap-4 md:gap-6 lg:grid-cols-3">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
          <!-- Order Items -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg md:text-xl">Order Items</CardTitle>
            </CardHeader>
            <CardContent>
              <!-- Desktop Table View (hidden on mobile) -->
              <div class="hidden md:block overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Ingredient</TableHead>
                      <TableHead>Ordered</TableHead>
                      <TableHead>Delivered in Restaurant</TableHead>
                      <TableHead>Unit Price</TableHead>
                      <TableHead>Unit</TableHead>
                      <TableHead>Total</TableHead>
                      <TableHead>Notes</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="item in purchaseOrder.items" :key="item.purchase_order_item_id">
                      <TableCell class="font-medium">
                        {{ item.ingredient.ingredient_name }}
                      </TableCell>
                      <TableCell>
                        {{ toNumber(item.ordered_quantity) }} {{ item.unit_of_measure }}
                      </TableCell>
                      <TableCell>
                        {{ toNumber(item.supplier_delivered_quantity) }} {{ item.unit_of_measure }}
                      </TableCell>
                      <TableCell>
                        {{ formatCurrency(item.unit_price) }}
                      </TableCell>
                      <TableCell>
                        {{ item.unit_of_measure }}
                      </TableCell>
                      <TableCell class="font-medium">
                        {{ formatCurrency(item.total_price) }}
                      </TableCell>
                      <TableCell>
                        <span v-if="item.notes" class="text-sm text-muted-foreground">{{ item.notes }}</span>
                        <span v-else class="text-muted-foreground">-</span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>

              <!-- Mobile Card View (hidden on desktop) -->
              <div class="md:hidden space-y-3">
                <div v-for="item in purchaseOrder.items" :key="item.purchase_order_item_id" class="border rounded-lg p-3">
                  <div class="font-semibold text-base mb-2">{{ item.ingredient.ingredient_name }}</div>

                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                      <div class="text-muted-foreground text-xs">Ordered</div>
                      <div class="font-medium">{{ toNumber(item.ordered_quantity) }} {{ item.unit_of_measure }}</div>
                    </div>
                    <div>
                      <div class="text-muted-foreground text-xs">Delivered in Restaurant</div>
                      <div class="font-medium">{{ toNumber(item.supplier_delivered_quantity) }} {{ item.unit_of_measure }}</div>
                    </div>
                    <div>
                      <div class="text-muted-foreground text-xs">Unit Price</div>
                      <div>{{ formatCurrency(item.unit_price) }}</div>
                    </div>
                  </div>

                  <div class="mt-2 pt-2 border-t flex justify-between items-center">
                    <div class="text-xs text-muted-foreground">Total</div>
                    <div class="font-semibold text-base">{{ formatCurrency(item.total_price) }}</div>
                  </div>

                  <div v-if="item.notes" class="mt-2 pt-2 border-t">
                    <div class="text-xs text-muted-foreground">Notes</div>
                    <div class="text-sm">{{ item.notes }}</div>
                  </div>
                </div>
              </div>

              <!-- Order Total -->
              <div class="border-t pt-3 md:pt-4 mt-3 md:mt-4">
                <div class="flex justify-between items-center">
                  <span class="text-base md:text-lg font-semibold">Total Amount</span>
                  <span class="text-lg md:text-xl font-bold">{{ formatCurrency(purchaseOrder.total_amount) }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Notes and Instructions -->
          <Card v-if="purchaseOrder.notes || purchaseOrder.delivery_instructions">
            <CardHeader>
              <CardTitle class="text-lg md:text-xl">Additional Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 md:space-y-4">
              <div v-if="purchaseOrder.notes">
                <Label class="text-sm font-medium">Order Notes</Label>
                <p class="text-sm text-muted-foreground mt-1">{{ purchaseOrder.notes }}</p>
              </div>

              <div v-if="purchaseOrder.delivery_instructions">
                <Label class="text-sm font-medium">Delivery Instructions</Label>
                <p class="text-sm text-muted-foreground mt-1">{{ purchaseOrder.delivery_instructions }}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 md:space-y-6">
          <!-- Restaurant Information -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center text-base md:text-lg">
                <User class="h-4 w-4 mr-2" />
                Restaurant Details
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <Label class="text-xs md:text-sm font-medium">Restaurant Name</Label>
                <p class="text-sm">{{ purchaseOrder.restaurant.restaurant_name }}</p>
              </div>

              <div>
                <Label class="text-xs md:text-sm font-medium">Contact Number</Label>
                <p class="text-sm">{{ purchaseOrder.restaurant.contact_number }}</p>
              </div>

              <div v-if="purchaseOrder.restaurant.address">
                <Label class="text-xs md:text-sm font-medium">Address</Label>
                <p class="text-sm">{{ purchaseOrder.restaurant.address }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Order Timeline -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center text-base md:text-lg">
                <Calendar class="h-4 w-4 mr-2" />
                Order Timeline
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <Label class="text-xs md:text-sm font-medium">Order Date</Label>
                <p class="text-sm">{{ formatDate(purchaseOrder.order_date) }}</p>
              </div>

              <div v-if="purchaseOrder.expected_delivery_date">
                <Label class="text-xs md:text-sm font-medium">Expected Delivery</Label>
                <p class="text-sm">{{ formatDate(purchaseOrder.expected_delivery_date) }}</p>
              </div>

              <div v-if="purchaseOrder.actual_delivery_date">
                <Label class="text-xs md:text-sm font-medium">Actual Delivery</Label>
                <p class="text-sm">{{ formatDate(purchaseOrder.actual_delivery_date) }}</p>
              </div>

              <div>
                <Label class="text-xs md:text-sm font-medium">Ordered By</Label>
                <p class="text-sm">{{ purchaseOrder.created_by.first_name }} {{ purchaseOrder.created_by.last_name }}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Confirm Dialog -->
    <Dialog v-model:open="showConfirmDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirm Purchase Order</DialogTitle>
          <DialogDescription>
            Confirm this purchase order and optionally update the expected delivery date.
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="confirmOrder" class="space-y-4">
          <div>
            <Label for="expected_delivery_date">Expected Delivery Date</Label>
            <Input
              id="expected_delivery_date"
              v-model="confirmForm.expected_delivery_date"
              type="date"
              :min="new Date().toISOString().split('T')[0]"
            />
            <div v-if="confirmForm.errors.expected_delivery_date" class="text-sm text-red-600 mt-1">
              {{ confirmForm.errors.expected_delivery_date }}
            </div>
          </div>
          
          <div>
            <Label for="notes">Additional Notes</Label>
            <Textarea
              id="notes"
              v-model="confirmForm.notes"
              placeholder="Any additional notes for the restaurant..."
              rows="3"
            />
            <div v-if="confirmForm.errors.notes" class="text-sm text-red-600 mt-1">
              {{ confirmForm.errors.notes }}
            </div>
          </div>
          
          <div class="flex justify-end space-x-2">
            <Button type="button" variant="outline" @click="showConfirmDialog = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="confirmForm.processing">
              {{ confirmForm.processing ? 'Confirming...' : 'Confirm Order' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Reject Dialog -->
    <Dialog v-model:open="showRejectDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject Purchase Order</DialogTitle>
          <DialogDescription>
            Please provide a reason for rejecting this purchase order.
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="rejectOrder" class="space-y-4">
          <div>
            <Label for="rejection_reason">Rejection Reason *</Label>
            <Textarea
              id="rejection_reason"
              v-model="rejectForm.rejection_reason"
              placeholder="Please explain why you're rejecting this order..."
              rows="3"
              required
            />
            <div v-if="rejectForm.errors.rejection_reason" class="text-sm text-red-600 mt-1">
              {{ rejectForm.errors.rejection_reason }}
            </div>
          </div>
          
          <div class="flex justify-end space-x-2">
            <Button type="button" variant="outline" @click="showRejectDialog = false">
              Cancel
            </Button>
            <Button type="submit" variant="destructive" :disabled="rejectForm.processing">
              {{ rejectForm.processing ? 'Rejecting...' : 'Reject Order' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Mark Delivered Dialog -->
    <Dialog v-model:open="showDeliveredDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Mark as Delivered</DialogTitle>
          <DialogDescription>
            Mark this purchase order as delivered and provide delivery details.
          </DialogDescription>
        </DialogHeader>
        
        <form @submit.prevent="markDelivered" class="space-y-4">
          <div>
            <Label>Delivery Date</Label>
            <div class="mt-1 text-sm font-medium">
              {{ deliveredForm.delivery_date }}
            </div>
          </div>

          <!-- Per-item delivered quantities -->
          <div>
            <Label class="mb-2 block">Delivered Quantities per Item</Label>
            <div class="hidden md:block overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ingredient</TableHead>
                    <TableHead>Ordered</TableHead>
                    <TableHead>Delivered Now</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="row in deliveredItems"
                    :key="row.purchase_order_item_id"
                  >
                    <TableCell class="font-medium">
                      {{ purchaseOrder.items.find(i => i.purchase_order_item_id === row.purchase_order_item_id)?.ingredient.ingredient_name }}
                    </TableCell>
                    <TableCell>
                      {{ row.ordered_quantity }}
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        min="0"
                        :max="getMaxDeliverable(row)"
                        step="0.01"
                        v-model.number="row.delivered_quantity"
                        class="w-28"
                      />
                    </TableCell>
                    <TableCell>
                      <span class="text-xs px-2 py-1 rounded-full"
                            :class="{
                              'bg-secondary text-secondary-foreground': row.delivered_quantity === 0,
                              'bg-yellow-100 text-yellow-800': row.delivered_quantity > 0 && row.delivered_quantity < row.ordered_quantity,
                              'bg-green-100 text-green-800': row.delivered_quantity >= row.ordered_quantity,
                            }">
                        <template v-if="row.delivered_quantity === 0">
                          Not delivered
                        </template>
                        <template v-else-if="row.delivered_quantity < row.ordered_quantity">
                          Partial
                        </template>
                        <template v-else>
                          Complete
                        </template>
                      </span>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <!-- Mobile view -->
            <div class="md:hidden space-y-3 mt-2">
              <div
                v-for="row in deliveredItems"
                :key="row.purchase_order_item_id"
                class="border rounded-lg p-3"
              >
                <div class="font-semibold mb-1">
                  {{ purchaseOrder.items.find(i => i.purchase_order_item_id === row.purchase_order_item_id)?.ingredient.ingredient_name }}
                </div>
                <div class="text-xs text-muted-foreground mb-1">
                  Ordered: {{ row.ordered_quantity }}
                </div>
                <div class="flex items-center justify-between gap-2">
                  <Input
                    type="number"
                    min="0"
                    :max="getMaxDeliverable(row)"
                    step="0.01"
                    v-model.number="row.delivered_quantity"
                    class="w-24"
                  />
                  <span class="text-xs px-2 py-1 rounded-full"
                        :class="{
                          'bg-secondary text-secondary-foreground': row.delivered_quantity === 0,
                          'bg-yellow-100 text-yellow-800': row.delivered_quantity > 0 && row.delivered_quantity < row.ordered_quantity,
                          'bg-green-100 text-green-800': row.delivered_quantity >= row.ordered_quantity,
                        }">
                    <template v-if="row.delivered_quantity === 0">
                      Not delivered
                    </template>
                    <template v-else-if="row.delivered_quantity < row.ordered_quantity">
                      Partial
                    </template>
                    <template v-else>
                      Complete
                    </template>
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div>
            <Label for="delivery_notes">Delivery Notes</Label>
            <Textarea
              id="delivery_notes"
              v-model="deliveredForm.delivery_notes"
              placeholder="Any notes about the delivery... (e.g. which items are pending or backordered)"
              rows="3"
            />
            <div v-if="deliveredForm.errors.delivery_notes" class="text-sm text-red-600 mt-1">
              {{ deliveredForm.errors.delivery_notes }}
            </div>
          </div>
          
          <div class="flex justify-end space-x-2">
            <Button type="button" variant="outline" @click="showDeliveredDialog = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="deliveredForm.processing">
              {{ deliveredForm.processing ? 'Saving...' : 'Save Delivery Status' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  </SupplierLayout>
</template>