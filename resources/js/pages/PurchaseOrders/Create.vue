<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, Search } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import PurchaseOrderSummaryModal from '@/components/PurchaseOrderSummaryModal.vue';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_person?: string;
  email?: string;
  phone?: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  unit: string;
  current_stock: number;
  reorder_level: number;
  cost_per_unit: number;
}

interface OrderItem {
  ingredient_id: number | null;
  ingredient_name?: string;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
}

interface Props {
  suppliers: Supplier[];
  ingredients: Ingredient[];
  purchaseOrder?: any;
  flash?: {
    success?: string;
    error?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: 'Create Order', href: '#' },
];

// Supplier selection
const selectedSupplierId = ref<number | null>(null);
const supplierSearchQuery = ref('');
const showSupplierDropdown = ref(false);

// Ingredient search - single search bar for adding ingredients
const ingredientSearchQuery = ref('');
const showIngredientDropdown = ref(false);

const orderItems = ref<OrderItem[]>([]);
const showSummaryModal = ref(false);
const createdPurchaseOrder = ref<any>(null);

// Watch for purchaseOrder prop changes (when backend returns after successful creation)
watch(() => props.purchaseOrder, (newPurchaseOrder) => {
  console.log('Purchase Order prop changed:', newPurchaseOrder);
  if (newPurchaseOrder) {
    console.log('Setting modal to show with PO:', newPurchaseOrder);
    createdPurchaseOrder.value = newPurchaseOrder;
    showSummaryModal.value = true;
  }
}, { immediate: true });

const form = useForm({
  supplier_id: null as number | null,
  supplier_name: '',
  supplier_contact: '',
  supplier_email: '',
  supplier_phone: '',
  expected_delivery_date: '',
  notes: '',
  delivery_instructions: '',
  items: [] as OrderItem[]
});

// Filter suppliers based on search query
const filteredSuppliers = computed(() => {
  if (!supplierSearchQuery.value) return props.suppliers;

  const query = supplierSearchQuery.value.toLowerCase();
  return props.suppliers.filter(s =>
    s.supplier_name.toLowerCase().includes(query)
  );
});

// Filter ingredients based on search query
const filteredIngredients = computed(() => {
  if (!ingredientSearchQuery.value) return props.ingredients;

  const query = ingredientSearchQuery.value.toLowerCase();
  return props.ingredients.filter(ing =>
    ing.ingredient_name.toLowerCase().includes(query)
  );
});

const subtotal = computed(() => {
  return orderItems.value.reduce((sum, item) => {
    return sum + (item.ordered_quantity * item.unit_price);
  }, 0);
});

// Check if typing a new supplier (not in the existing list)
const isNewSupplier = computed(() => {
  if (!supplierSearchQuery.value) return false;
  if (selectedSupplierId.value) return false;

  // Check if the current input matches any existing supplier
  const query = supplierSearchQuery.value.toLowerCase().trim();
  const exactMatch = props.suppliers.some(s =>
    s.supplier_name.toLowerCase() === query
  );

  return !exactMatch && query.length > 0;
});

const selectSupplier = (supplier: Supplier) => {
  selectedSupplierId.value = supplier.supplier_id;
  supplierSearchQuery.value = supplier.supplier_name;
  form.supplier_id = supplier.supplier_id;
  form.supplier_name = '';
  form.supplier_contact = '';
  form.supplier_email = '';
  form.supplier_phone = '';
  showSupplierDropdown.value = false;
};

const onSupplierInputChange = () => {
  // Reset selected supplier when user types
  if (selectedSupplierId.value) {
    selectedSupplierId.value = null;
    form.supplier_id = null;
  }

  // Set supplier_name for manual entry
  form.supplier_name = supplierSearchQuery.value;
};

// Blur handlers use the global timer safely from script context
const onSupplierBlur = () => {
  window.setTimeout(() => {
    showSupplierDropdown.value = false;
  }, 200);
};

const onIngredientBlur = () => {
  window.setTimeout(() => {
    showIngredientDropdown.value = false;
  }, 200);
};

const addIngredientToOrder = (ingredient: Ingredient) => {
  // Check if ingredient is already in the order
  const existingItem = orderItems.value.find(item => item.ingredient_id === ingredient.ingredient_id);

  if (existingItem) {
    // If already exists, just increment quantity
    existingItem.ordered_quantity += 1;
  } else {
    // Add new item to order
    orderItems.value.push({
      ingredient_id: ingredient.ingredient_id,
      ingredient_name: ingredient.ingredient_name,
      ordered_quantity: 1,
      unit_price: ingredient.cost_per_unit || 0,
      unit_of_measure: ingredient.unit,
      notes: ''
    });
  }

  // Clear search
  ingredientSearchQuery.value = '';
  showIngredientDropdown.value = false;
};

const removeOrderItem = (index: number) => {
  orderItems.value.splice(index, 1);
};

const getIngredientName = (ingredientId: number | null) => {
  if (!ingredientId) return '';
  const ingredient = props.ingredients.find(i => i.ingredient_id === ingredientId);
  return ingredient?.ingredient_name || '';
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const submit = () => {
  // Filter out empty items and validate
  const validItems = orderItems.value.filter(item =>
    item.ingredient_id &&
    item.ordered_quantity > 0 &&
    item.unit_price > 0
  );

  if (validItems.length === 0) {
    alert('Please add at least one valid item to the order.');
    return;
  }

  // Validate supplier: must have either supplier_id OR supplier_name
  if (!form.supplier_id && !supplierSearchQuery.value.trim()) {
    alert('Please enter or select a supplier.');
    return;
  }

  // If manual entry, ensure supplier_name is set
  if (!form.supplier_id) {
    form.supplier_name = supplierSearchQuery.value.trim();
  }

  form.items = validItems;
  form.post('/purchase-orders', {
    onSuccess: (page) => {
      console.log('Purchase order created successfully', page);
    },
    onError: (errors) => {
      console.error('Error creating purchase order', errors);
    }
  });
};

const handleViewDetails = () => {
  if (createdPurchaseOrder.value) {
    router.visit(`/purchase-orders/${createdPurchaseOrder.value.purchase_order_id}`);
  }
};

const handleCloseSummary = () => {
  showSummaryModal.value = false;
  if (createdPurchaseOrder.value) {
    router.visit(`/purchase-orders/${createdPurchaseOrder.value.purchase_order_id}`);
  }
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

      <!-- Flash Messages -->
      <div v-if="flash?.success" class="flex items-center gap-2 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ flash.success }}</span>
      </div>

      <div v-if="flash?.error" class="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ flash.error }}</span>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Supplier Information -->
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Unified Supplier Input -->
            <div class="space-y-2">
              <Label for="supplier">Supplier Name *</Label>
              <div class="relative">
                <Input
                  id="supplier"
                  v-model="supplierSearchQuery"
                  type="text"
                  placeholder="Type to search or enter new supplier name..."
                  @input="onSupplierInputChange"
                  @focus="showSupplierDropdown = true"
                  @blur="onSupplierBlur"
                  required
                />
                <div
                  v-if="showSupplierDropdown && filteredSuppliers.length > 0 && !selectedSupplierId"
                  class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-auto"
                >
                  <div
                    v-for="supplier in filteredSuppliers"
                    :key="supplier.supplier_id"
                    @click="selectSupplier(supplier)"
                    class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                  >
                    {{ supplier.supplier_name }}
                  </div>
                </div>
              </div>
              <div v-if="selectedSupplierId" class="text-xs text-green-600">
                ✓ Selected from existing suppliers
              </div>
              <div v-else-if="isNewSupplier" class="text-xs text-blue-600">
                → New supplier - please fill in additional details below
              </div>
              <div v-if="form.errors.supplier_id || form.errors.supplier_name" class="text-sm text-red-600">
                {{ form.errors.supplier_id || form.errors.supplier_name }}
              </div>
            </div>

            <!-- Additional Fields for New Supplier -->
            <div v-if="isNewSupplier" class="grid gap-4 md:grid-cols-2 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div class="col-span-2">
                <p class="text-sm font-medium text-blue-800 mb-2">Additional Supplier Details (Optional)</p>
              </div>

              <div class="space-y-2">
                <Label for="supplier_contact">Contact Person</Label>
                <Input
                  id="supplier_contact"
                  v-model="form.supplier_contact"
                  type="text"
                  placeholder="Enter contact person name"
                />
              </div>

              <div class="space-y-2">
                <Label for="supplier_phone">Phone</Label>
                <Input
                  id="supplier_phone"
                  v-model="form.supplier_phone"
                  type="tel"
                  placeholder="+63 XXX XXX XXXX"
                />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="supplier_email">Email</Label>
                <Input
                  id="supplier_email"
                  v-model="form.supplier_email"
                  type="email"
                  placeholder="supplier@example.com"
                />
              </div>
            </div>

            <!-- Delivery Date -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="expected_delivery_date">Expected Delivery Date</Label>
                <Input
                  id="expected_delivery_date"
                  v-model="form.expected_delivery_date"
                  type="date"
                />
                <div v-if="form.errors.expected_delivery_date" class="text-sm text-red-600">
                  {{ form.errors.expected_delivery_date }}
                </div>
              </div>
            </div>

            <!-- Notes and Instructions -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="notes">Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Any additional notes for this order..."
                  rows="3"
                />
              </div>

              <div class="space-y-2">
                <Label for="delivery_instructions">Delivery Instructions</Label>
                <Textarea
                  id="delivery_instructions"
                  v-model="form.delivery_instructions"
                  placeholder="Special delivery instructions..."
                  rows="3"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Order Items -->
        <Card>
          <CardHeader>
            <CardTitle>Order Items</CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Ingredient Search Bar -->
            <div class="space-y-2">
              <Label>Search and Add Ingredients</Label>
              <div class="relative">
                <div class="relative">
                  <Input
                    v-model="ingredientSearchQuery"
                    type="text"
                    placeholder="Type to search ingredients from library..."
                    @focus="showIngredientDropdown = true"
                    @blur="onIngredientBlur"
                    class="w-full pr-8"
                  />
                  <Search class="absolute right-3 top-2.5 h-4 w-4 text-muted-foreground" />
                </div>

                <!-- Ingredient Dropdown -->
                <div
                  v-if="showIngredientDropdown && filteredIngredients.length > 0"
                  class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-auto"
                >
                  <div
                    v-for="ingredient in filteredIngredients"
                    :key="ingredient.ingredient_id"
                    @click="addIngredientToOrder(ingredient)"
                    class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b last:border-b-0"
                  >
                    <div class="font-medium">{{ ingredient.ingredient_name }}</div>
                    <div class="text-xs text-muted-foreground">
                      Stock: {{ ingredient.current_stock }} {{ ingredient.unit }} |
                      Cost: {{ formatCurrency(ingredient.cost_per_unit) }}/{{ ingredient.unit }}
                    </div>
                  </div>
                </div>

                <div v-if="showIngredientDropdown && ingredientSearchQuery && filteredIngredients.length === 0"
                     class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg p-4 text-center text-muted-foreground text-sm">
                  No ingredients found. Add ingredients in the Ingredients Library first.
                </div>
              </div>
              <p class="text-xs text-muted-foreground">
                Click on an ingredient to add it to your order
              </p>
            </div>

            <!-- Order Items Table -->
            <div v-if="orderItems.length > 0" class="space-y-2">
              <div class="flex items-center justify-between">
                <Label>Added Items ({{ orderItems.length }})</Label>
              </div>
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Ingredient</TableHead>
                      <TableHead>Quantity</TableHead>
                      <TableHead>Unit Price (₱)</TableHead>
                      <TableHead>Unit</TableHead>
                      <TableHead>Total</TableHead>
                      <TableHead>Notes</TableHead>
                      <TableHead class="w-12"></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="(item, index) in orderItems" :key="index">
                      <TableCell class="font-medium">
                        {{ item.ingredient_name }}
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model.number="item.ordered_quantity"
                          type="number"
                          step="0.01"
                          min="0.01"
                          placeholder="0"
                          class="w-24"
                        />
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model.number="item.unit_price"
                          type="number"
                          step="0.01"
                          min="0.01"
                          placeholder="0.00"
                          class="w-28"
                        />
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model="item.unit_of_measure"
                          placeholder="kg, pcs, etc."
                          class="w-24"
                        />
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
                          class="h-8 w-8 p-0"
                        >
                          <Trash2 class="w-4 h-4 text-red-600" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8 text-muted-foreground">
              <p>No items added yet. Search for ingredients above to add them to your order.</p>
            </div>

            <div v-if="form.errors.items" class="text-sm text-red-600">
              {{ form.errors.items }}
            </div>
          </CardContent>
        </Card>

        <!-- Order Summary -->
        <Card v-if="orderItems.some(item => item.ingredient_id)">
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
            :disabled="form.processing"
          >
            {{ form.processing ? 'Creating...' : 'Create Purchase Order' }}
          </Button>

          <Button type="button" variant="outline" onclick="history.back()">
            Cancel
          </Button>
        </div>
      </form>

      <!-- Purchase Order Summary Modal -->
      <PurchaseOrderSummaryModal
        v-if="createdPurchaseOrder"
        :purchase-order="createdPurchaseOrder"
        :is-open="showSummaryModal"
        @close="handleCloseSummary"
        @view-details="handleViewDetails"
      />
    </div>
  </AppLayout>
</template>
