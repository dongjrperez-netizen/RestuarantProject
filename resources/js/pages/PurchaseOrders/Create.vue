<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface SupplierOffering {
  id: number;
  ingredient_id: number;
  supplier_id: number;
  package_unit: string;
  package_quantity: number;
  package_price: number;
  lead_time_days: number;
  minimum_order_quantity: number;
  ingredient: {
    ingredient_id: number;
    ingredient_name: string;
    base_unit: string;
  };
  supplier: {
    supplier_id: number;
    supplier_name: string;
  };
}

interface OrderItem {
  ingredient_id: number | null;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
  max_quantity?: number;
  [key: string]: any;
}

interface Props {
  suppliers: Supplier[];
  supplierOfferings: Record<number, SupplierOffering[]>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: 'Create Order', href: '#' },
];

const selectedSupplier = ref<number | null>(null);
const orderItems = ref<OrderItem[]>([{
  ingredient_id: null,
  ordered_quantity: 0,
  unit_price: 0,
  unit_of_measure: '',
  notes: ''
}]);

const form = useForm({
  supplier_id: null as number | null,
  expected_delivery_date: '',
  notes: '',
  delivery_instructions: '',
  items: [] as OrderItem[]
});

const availableIngredients = computed(() => {
  if (!selectedSupplier.value) return [];
  
  return props.supplierOfferings[selectedSupplier.value] || [];
});

const subtotal = computed(() => {
  return orderItems.value.reduce((sum, item) => {
    return sum + (item.ordered_quantity * item.unit_price);
  }, 0);
});

const expectedDeliveryDate = computed(() => {
  if (!selectedSupplier.value || orderItems.value.length === 0) return '';
  
  // Find the maximum lead time from all selected ingredients
  let maxLeadTime = 0;
  
  for (const item of orderItems.value) {
    if (item.ingredient_id) {
      const offering = availableIngredients.value.find(off => off.ingredient.ingredient_id === item.ingredient_id);
      if (offering && offering.lead_time_days > maxLeadTime) {
        maxLeadTime = offering.lead_time_days;
      }
    }
  }
  
  if (maxLeadTime === 0) return '';
  
  // Calculate delivery date: today + max lead time
  const today = new Date();
  const deliveryDate = new Date(today);
  deliveryDate.setDate(today.getDate() + Math.ceil(maxLeadTime));
  
  return deliveryDate.toISOString().split('T')[0];
});

const addOrderItem = () => {
  orderItems.value.push({
    ingredient_id: null,
    ordered_quantity: 0,
    unit_price: 0,
    unit_of_measure: '',
    notes: ''
  });
};

const removeOrderItem = (index: number) => {
  if (orderItems.value.length > 1) {
    orderItems.value.splice(index, 1);
  }
};

const onIngredientSelect = (itemIndex: number, ingredientId: number) => {
  if (!ingredientId) return;

  const offering = availableIngredients.value.find(off => off.ingredient.ingredient_id === ingredientId);
  if (offering) {
    orderItems.value[itemIndex].ingredient_id = ingredientId;
    orderItems.value[itemIndex].unit_price = offering.package_price;
    orderItems.value[itemIndex].unit_of_measure = offering.package_unit;
    // Set max quantity hint for user
    orderItems.value[itemIndex].max_quantity = offering.minimum_order_quantity;
  }
};

const getSelectedOffering = (itemIndex: number) => {
  const item = orderItems.value[itemIndex];
  if (!item.ingredient_id) return null;

  return availableIngredients.value.find(off => off.ingredient.ingredient_id === item.ingredient_id);
};


const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

watch(selectedSupplier, (newValue) => {
  form.supplier_id = newValue;
  // Reset items when supplier changes
  orderItems.value = [{
    ingredient_id: null,
    ordered_quantity: 0,
    unit_price: 0,
    unit_of_measure: '',
    notes: ''
  }];
});

// Watch for changes in expected delivery date and update form
watch(expectedDeliveryDate, (newDate) => {
  form.expected_delivery_date = newDate;
});

const submit = () => {
  // Filter out empty items and validate
  const validItems = orderItems.value.filter(item => 
    item.ingredient_id && 
    item.ordered_quantity > 0 && 
    item.unit_price > 0
  );

  form.items = validItems;
  form.post('/purchase-orders');
};
</script>

<template>
  <Head title="Create Purchase Order" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="space-y-6 mx-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create Purchase Order</h1>
        <p class="text-muted-foreground">Create a new purchase order for your restaurant</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle>Order Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="supplier">Supplier *</Label>
                <Select v-model="selectedSupplier" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a supplier" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="supplier in suppliers" 
                      :key="supplier.supplier_id"
                      :value="supplier.supplier_id"
                    >
                      {{ supplier.supplier_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.supplier_id" class="text-sm text-red-600">
                  {{ form.errors.supplier_id }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="expected_delivery_date">Expected Delivery Date</Label>
                <Input
                  id="expected_delivery_date"
                  v-model="form.expected_delivery_date"
                  type="date"
                  readonly
                  class="bg-muted cursor-not-allowed"
                />
                <div class="text-xs text-muted-foreground">
                  <span v-if="expectedDeliveryDate">
                    Automatically calculated based on supplier lead times
                  </span>
                  <span v-else>
                    Select ingredients to calculate delivery date
                  </span>
                </div>
                <div v-if="form.errors.expected_delivery_date" class="text-sm text-red-600">
                  {{ form.errors.expected_delivery_date }}
                </div>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="notes">Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Any additional notes for this order..."
                  rows="3"
                />
                <div v-if="form.errors.notes" class="text-sm text-red-600">
                  {{ form.errors.notes }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="delivery_instructions">Delivery Instructions</Label>
                <Textarea
                  id="delivery_instructions"
                  v-model="form.delivery_instructions"
                  placeholder="Special delivery instructions..."
                  rows="3"
                />
                <div v-if="form.errors.delivery_instructions" class="text-sm text-red-600">
                  {{ form.errors.delivery_instructions }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Order Items -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Order Items</CardTitle>
              <Button 
                type="button" 
                @click="addOrderItem"
                variant="outline"
                size="sm"
                :disabled="!selectedSupplier"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add Item
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="!selectedSupplier" class="text-center py-8 text-muted-foreground">
              Please select a supplier first to add order items.
            </div>
            
            <div v-else class="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ingredient</TableHead>
                    <TableHead>Quantity</TableHead>
                    <TableHead>Unit Price</TableHead>
                    <TableHead>Package Unit</TableHead>
                    <TableHead>Base Unit</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Notes</TableHead>
                    <TableHead class="w-12"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(item, index) in orderItems" :key="index">
                    <TableCell>
                      <Select 
                        v-model="orderItems[index].ingredient_id"
                        @update:model-value="(value) => value && onIngredientSelect(index, Number(value))"

                        >
                        <SelectTrigger>
                          <SelectValue placeholder="Select ingredient" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem 
                            v-for="offering in availableIngredients" 
                            :key="offering.ingredient.ingredient_id"
                            :value="offering.ingredient.ingredient_id"
                          >
                            {{ offering.ingredient.ingredient_name }} 
                            ({{ formatCurrency(offering.package_price) }}/{{ offering.package_unit }})
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell>
                      <div class="space-y-1">
                        <Input
                          v-model.number="item.ordered_quantity"
                          type="number"
                          step="0.01"
                          min="0.01"
                          :max="getSelectedOffering(index)?.minimum_order_quantity"
                          placeholder="0"
                          class="w-24"
                          :class="{ 'border-red-500': getSelectedOffering(index) && getSelectedOffering(index)?.minimum_order_quantity !== undefined && item.ordered_quantity > getSelectedOffering(index)!.minimum_order_quantity }"
                        />
                        <div v-if="getSelectedOffering(index)" class="text-xs text-muted-foreground">
                          Max: {{ getSelectedOffering(index)?.minimum_order_quantity }} {{ getSelectedOffering(index)?.package_unit }}
                        </div>
                        <div 
                          v-if="getSelectedOffering(index) && getSelectedOffering(index)?.minimum_order_quantity !== undefined && item.ordered_quantity > getSelectedOffering(index)!.minimum_order_quantity"
                          class="text-xs text-red-600">
                          Exceeds maximum order quantity
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="item.unit_price"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0.00"
                        class="w-24"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model="item.unit_of_measure"
                        placeholder="kg, pcs, etc."
                        class="w-20"
                      />
                    </TableCell>
                    <TableCell>
                      <span v-if="getSelectedOffering(index)" class="text-sm text-muted-foreground">
                        {{ getSelectedOffering(index)?.ingredient.base_unit || '-' }}
                      </span>
                      <span v-else class="text-sm text-muted-foreground">-</span>
                    </TableCell>
                    <TableCell class="font-medium">
                      {{ formatCurrency(item.ordered_quantity * item.unit_price) }}
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model="item.notes"
                        placeholder="Optional notes"
                        class="w-32"
                      />
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        @click="removeOrderItem(index)"
                        variant="ghost"
                        size="sm"
                        :disabled="orderItems.length === 1"
                        class="h-8 w-8 p-0"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              
              <div v-if="form.errors.items" class="text-sm text-red-600">
                {{ form.errors.items }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Order Summary -->
        <Card v-if="selectedSupplier && orderItems.some(item => item.ingredient_id)">
          <CardHeader>
            <CardTitle>Order Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between text-lg font-semibold">
                <span>Total Amount</span>
                <span>{{ formatCurrency(subtotal) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button 
            type="submit" 
            :disabled="form.processing || !selectedSupplier || orderItems.length === 0"
          >
            {{ form.processing ? 'Creating...' : 'Create Purchase Order' }}
          </Button>
          
          <Button type="button" variant="outline" onclick="history.back()">
            Cancel
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>