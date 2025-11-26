<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus, UserPlus, X } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  cost_per_unit: number;
  current_stock: number;
}

interface OrderItem {
  ingredient_id: number | null;
  ingredient_name: string;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
  showDropdown?: boolean;
  dropdownPosition?: { top: number; left: number; width: number; showAbove: boolean };
  [key: string]: any;
}

interface Props {
  ingredients: Ingredient[];
  suppliers: Supplier[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: 'Create Order', href: '#' },
];

const orderItems = ref<OrderItem[]>([{
  ingredient_id: null,
  ingredient_name: '',
  ordered_quantity: 0,
  unit_price: 0,
  unit_of_measure: '',
  notes: '',
  showDropdown: false,
  dropdownPosition: undefined
}]);

interface PurchaseOrderForm {
  supplier_id: number | null;
  supplier_name: string;
  items: OrderItem[];
  [key: string]: any; // Index signature for FormDataType compatibility
}

const form = useForm<PurchaseOrderForm>({
  supplier_id: null,
  supplier_name: '',
  items: []
});

const showSupplierDropdown = ref(false);
const supplierDropdownPosition = ref<{ top: number; left: number; width: number; showAbove: boolean } | null>(null);

// New supplier creation
const showNewSupplierForm = ref(false);
const creatingSupplier = ref(false);
const newSupplierData = ref({
  supplier_name: '',
  email: '',
  phone: '',
  address: ''
});

// Helper function to calculate smart dropdown position
const calculateDropdownPosition = (inputElement: HTMLElement) => {
  const rect = inputElement.getBoundingClientRect();
  const dropdownMaxHeight = 240; // max-h-60 = 240px
  const spaceBelow = window.innerHeight - rect.bottom;
  const spaceAbove = rect.top;

  // If there's not enough space below but there is above, show above.
  // We keep coordinates relative to the viewport because dropdowns are rendered
  // with `position: fixed` via Teleport. Do NOT add scroll offsets here.
  const showAbove = spaceBelow < dropdownMaxHeight && spaceAbove > spaceBelow;

  // Use rect.bottom for the dropdown top when showing below, and rect.top when
  // showing above. The template applies `translateY(-100%)` when `showAbove`
  // is true so we don't need to subtract dropdown height here.
  const top = showAbove ? rect.top : rect.bottom;
  const left = Math.max(0, rect.left);

  return {
    top,
    left,
    width: rect.width,
    showAbove
  };
};

const addOrderItem = () => {
  orderItems.value.push({
    ingredient_id: null,
    ingredient_name: '',
    ordered_quantity: 0,
    unit_price: 0,
    unit_of_measure: '',
    notes: '',
    showDropdown: false,
    dropdownPosition: undefined
  });
};

const getFilteredIngredients = (itemIndex: number) => {
  const searchQuery = orderItems.value[itemIndex].ingredient_name?.toLowerCase() || '';

  if (!searchQuery) {
    return [];
  }

  return props.ingredients.filter(ingredient =>
    ingredient.ingredient_name.toLowerCase().includes(searchQuery)
  );
};

const getFilteredSuppliers = computed(() => {
  const searchQuery = form.supplier_name?.toLowerCase() || '';

  if (!searchQuery) {
    return [];
  }

  return props.suppliers.filter(supplier =>
    supplier.supplier_name.toLowerCase().includes(searchQuery)
  );
});

const showCreateNewSupplierOption = computed(() => {
  return form.supplier_name.length > 0 &&
         getFilteredSuppliers.value.length === 0 &&
         !showNewSupplierForm.value;
});

const removeOrderItem = (index: number) => {
  if (orderItems.value.length > 1) {
    orderItems.value.splice(index, 1);
  }
};

const onIngredientInput = (itemIndex: number, event?: Event | InputEvent) => {
  const item = orderItems.value[itemIndex];
  item.showDropdown = item.ingredient_name.length > 0;
  item.ingredient_id = null; // Reset ID when user types

  // Calculate dropdown position with smart positioning
  if (event && event.target) {
    const input = event.target as HTMLElement;
    item.dropdownPosition = calculateDropdownPosition(input);
  }
};

const onIngredientFocus = (itemIndex: number, event: Event | FocusEvent) => {
  const item = orderItems.value[itemIndex];
  item.showDropdown = item.ingredient_name.length > 0;

  // Calculate dropdown position with smart positioning
  if (event && event.target) {
    const input = event.target as HTMLElement;
    item.dropdownPosition = calculateDropdownPosition(input);
  }
};

const selectIngredient = (itemIndex: number, ingredient: Ingredient) => {
  const item = orderItems.value[itemIndex];
  item.ingredient_id = ingredient.ingredient_id;
  item.ingredient_name = ingredient.ingredient_name;
  item.unit_of_measure = ingredient.base_unit;
  item.unit_price = ingredient.cost_per_unit;
  item.showDropdown = false;
};

// Add ingredient to the last row (used by the right-side lookup table)
const addIngredientToLast = (ingredient: Ingredient) => {
  const items = orderItems.value;
  const last = items[items.length - 1];
  if (last && last.ingredient_id) {
    addOrderItem();
  }

  const idx = orderItems.value.length - 1;
  orderItems.value[idx].ingredient_id = ingredient.ingredient_id;
  orderItems.value[idx].ingredient_name = ingredient.ingredient_name;
  orderItems.value[idx].unit_of_measure = ingredient.base_unit;
  orderItems.value[idx].unit_price = ingredient.cost_per_unit;
};

const getSelectedIngredient = (itemIndex: number) => {
  const item = orderItems.value[itemIndex];
  if (!item.ingredient_id) return null;

  return props.ingredients.find(ing => ing.ingredient_id === item.ingredient_id);
};


const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

// Ingredients lookup scrolling
const ingredientsListRef = ref<HTMLElement | null>(null);

const scrollIngredients = (amount: number) => {
  const el = ingredientsListRef.value;
  if (!el) return;
  el.scrollBy({ top: amount, behavior: 'smooth' });
};

const scrollUpIngredients = () => scrollIngredients(-120);
const scrollDownIngredients = () => scrollIngredients(120);

const initiateNewSupplier = () => {
  newSupplierData.value.supplier_name = form.supplier_name;
  showNewSupplierForm.value = true;
  showSupplierDropdown.value = false;
};

const cancelNewSupplier = () => {
  showNewSupplierForm.value = false;
  newSupplierData.value = {
    supplier_name: '',
    email: '',
    phone: '',
    address: ''
  };
  form.supplier_name = '';
  form.supplier_id = null;
};

const createAndUseSupplier = async () => {
  creatingSupplier.value = true;

  try {
    const response = await axios.post('/api/suppliers/quick-create', newSupplierData.value);

    if (response.data.success) {
      // Set the newly created supplier
      form.supplier_id = response.data.supplier.supplier_id;
      form.supplier_name = response.data.supplier.supplier_name;

      // Update the suppliers list
      props.suppliers.push(response.data.supplier);

      // Close the form
      showNewSupplierForm.value = false;

      // Reset new supplier data
      newSupplierData.value = {
        supplier_name: '',
        email: '',
        phone: '',
        address: ''
      };

      // Show success message (you can use a toast notification instead)
      alert('Supplier created successfully!');
    }
  } catch (error: any) {
    console.error('Error creating supplier:', error);
    const errorMessage = error.response?.data?.message || 'Failed to create supplier. Please try again.';
    alert(errorMessage);
  } finally {
    creatingSupplier.value = false;
  }
};

const submit = () => {
  // Filter out empty items and validate
  const validItems = orderItems.value
    .filter(item =>
      item.ingredient_id &&
      item.ordered_quantity > 0
    )
    .map(item => ({
      ingredient_id: item.ingredient_id,
      ingredient_name: item.ingredient_name,
      ordered_quantity: item.ordered_quantity,
      unit_price: item.unit_price || 0,
      unit_of_measure: item.unit_of_measure,
      notes: item.notes,
      showDropdown: false,
      dropdownPosition: undefined
    }));

  form.items = validItems;

  form.post('/purchase-orders', {
    onError: (errors) => {
      // Surface any backend validation errors in the console so we can debug hosting issues
      console.error('Purchase order validation/server errors', errors);
    },
  });
};

const closeDropdown = (itemIndex: number) => {
  setTimeout(() => {
    orderItems.value[itemIndex].showDropdown = false;
  }, 200); // Delay to allow click event to fire
};

const onSupplierInput = (event: Event | InputEvent) => {
  showSupplierDropdown.value = form.supplier_name.length > 0;
  // Reset supplier_id when user types (forces them to select from dropdown)
  form.supplier_id = null;

  // Calculate dropdown position with smart positioning
  if (event && event.target) {
    const input = event.target as HTMLElement;
    supplierDropdownPosition.value = calculateDropdownPosition(input);
  }
};

const onSupplierFocus = (event: Event | FocusEvent) => {
  showSupplierDropdown.value = form.supplier_name.length > 0;

  // Calculate dropdown position with smart positioning
  if (event && event.target) {
    const input = event.target as HTMLElement;
    supplierDropdownPosition.value = calculateDropdownPosition(input);
  }
};

const selectSupplier = (supplier: Supplier) => {
  form.supplier_id = supplier.supplier_id;
  form.supplier_name = supplier.supplier_name;
  showSupplierDropdown.value = false;
};

const closeSupplierDropdown = () => {
  setTimeout(() => {
    showSupplierDropdown.value = false;
  }, 200);
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
        <!-- Global error alert -->
        <div
          v-if="Object.keys(form.errors).length"
          class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded"
        >
          <div v-for="(message, field) in form.errors" :key="field">
            {{ message }}
          </div>
        </div>

        <!-- Basic Information -->
        <Card>
          <CardContent class="space-y-6">
            <div class="space-y-2">
              <Label for="supplier">Supplier *</Label>
              <div class="flex gap-2">
                <Input
                  id="supplier"
                  v-model="form.supplier_name"
                  @input="onSupplierInput"
                  @focus="onSupplierFocus"
                  @blur="closeSupplierDropdown"
                  placeholder="Type supplier name..."
                  required
                  :disabled="showNewSupplierForm"
                  class="flex-1"
                />
                <Button
                  v-if="form.supplier_id"
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="() => { form.supplier_id = null; form.supplier_name = ''; }"
                  class="shrink-0"
                >
                  <X class="w-4 h-4 mr-1" />
                  Clear
                </Button>
              </div>
              <div v-if="(form.errors as any).supplier_name" class="text-sm text-red-600">
                {{ (form.errors as any).supplier_name }}
              </div>
              <div v-if="form.supplier_id" class="text-sm text-green-600 font-medium">
                ✓ Supplier selected
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- New Supplier Form -->
        <Card v-if="showNewSupplierForm" class="border-blue-200 bg-blue-50/50">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="text-blue-900">Create New Supplier</CardTitle>
                <CardDescription class="text-blue-700">
                  Add supplier details to create and use immediately
                </CardDescription>
              </div>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="cancelNewSupplier"
                :disabled="creatingSupplier"
              >
                <X class="w-4 h-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2 md:col-span-2">
                <Label for="new-supplier-name">Supplier Name *</Label>
                <Input
                  id="new-supplier-name"
                  v-model="newSupplierData.supplier_name"
                  placeholder="Enter supplier name"
                  required
                />
              </div>

              <div class="space-y-2">
                <Label for="new-email">Email</Label>
                <Input
                  id="new-email"
                  v-model="newSupplierData.email"
                  type="email"
                  placeholder="supplier@example.com"
                />
              </div>

              <div class="space-y-2">
                <Label for="new-phone">Phone</Label>
                <Input
                  id="new-phone"
                  v-model="newSupplierData.phone"
                  placeholder="+63 XXX XXX XXXX"
                />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="new-address">Address</Label>
                <Input
                  id="new-address"
                  v-model="newSupplierData.address"
                  placeholder="Enter supplier address"
                />
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <Button
                type="button"
                variant="outline"
                @click="cancelNewSupplier"
                :disabled="creatingSupplier"
              >
                Cancel
              </Button>
              <Button
                type="button"
                @click="createAndUseSupplier"
                :disabled="!newSupplierData.supplier_name || creatingSupplier"
                class="bg-blue-600 hover:bg-blue-700"
              >
                <UserPlus class="w-4 h-4 mr-2" />
                {{ creatingSupplier ? 'Creating...' : 'Create & Use Supplier' }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Order Items Card with Ingredients Lookup -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left: Order Items (2 columns) -->
          <Card class="lg:col-span-2">
            <CardHeader>
              <CardTitle>Order Items</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <Button
                    type="button"
                    @click="addOrderItem"
                    variant="outline"
                    size="sm"
                  >
                    <Plus class="w-4 h-4 mr-2" />
                    Add Item
                  </Button>
                </div>

                <div class="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead class="min-w-[280px]">Ingredient</TableHead>
                        <TableHead class="min-w-[100px]">Quantity</TableHead>
                        <TableHead class="min-w-[100px]">Base Unit</TableHead>
                        <TableHead class="w-12"></TableHead>
                      </TableRow>
                    </TableHeader>
                  <TableBody>
                    <TableRow v-for="(item, index) in orderItems" :key="index">
                      <TableCell>
                        <div class="space-y-1">
                          <!-- Autocomplete Input -->
                          <Input
                            v-model="orderItems[index].ingredient_name"
                            @input="(e: Event) => onIngredientInput(index, e)"
                            @focus="(e: Event) => onIngredientFocus(index, e)"
                            @blur="closeDropdown(index)"
                            placeholder="Type ingredient name..."
                            class="min-w-[250px]"
                          />
                          <div
                            v-if="(form.errors as any)[`items.${index}.ingredient_id`]"
                            class="text-xs text-red-600"
                          >
                            {{ (form.errors as any)[`items.${index}.ingredient_id`] }}
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div class="space-y-1">
                          <Input
                            v-model.number="item.ordered_quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0"
                            class="min-w-[90px]"
                          />
                          <!-- Backend validation error for this row -->
                          <div
                            v-if="(form.errors as any)[`items.${index}.ordered_quantity`]"
                            class="text-xs text-red-600"
                          >
                            {{ (form.errors as any)[`items.${index}.ordered_quantity`] }}
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <span v-if="getSelectedIngredient(index)" class="text-sm text-muted-foreground">
                          {{ getSelectedIngredient(index)?.base_unit || '-' }}
                        </span>
                        <span v-else class="text-sm text-muted-foreground">-</span>
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
                
                <div v-if="(form.errors as any).items" class="text-sm text-red-600">
                  {{ (form.errors as any).items }}
                </div>
              </div>
              </div>
            </CardContent>
          </Card>

          <!-- Right: Ingredients Lookup Table -->
          <Card class="lg:col-span-1">
            <CardHeader>
                <div class="flex items-center justify-between w-full">
                  <CardTitle class="text-base">Available Ingredients</CardTitle>
                  <div class="flex items-center space-x-2">
                    <Button type="button" size="sm" variant="outline" @click="scrollUpIngredients" aria-label="Scroll up">
                      ▲
                    </Button>
                    <Button type="button" size="sm" variant="outline" @click="scrollDownIngredients" aria-label="Scroll down">
                      ▼
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div ref="ingredientsListRef" class="overflow-auto max-h-96">
                <Table class="text-xs">
                  <TableHeader>
                    <TableRow>
                      <TableHead class="text-xs">Ingredient</TableHead>
                      <TableHead class="text-xs text-right">Stock</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="ingredient in props.ingredients" :key="ingredient.ingredient_id">
                      <TableCell class="text-xs py-2">
                        <button
                          type="button"
                          @click="addIngredientToLast(ingredient)"
                          class="hover:text-blue-600 cursor-pointer truncate text-left w-full"
                          :title="ingredient.ingredient_name"
                        >
                          {{ ingredient.ingredient_name }}
                        </button>
                      </TableCell>
                      <TableCell class="text-xs py-2 text-right">
                        <span :class="ingredient.current_stock > 0 ? 'text-green-600' : 'text-red-600'">
                          {{ ingredient.current_stock }}
                        </span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button
            type="submit"
            :disabled="form.processing || !form.supplier_id || orderItems.length === 0"
          >
            {{ form.processing ? 'Creating...' : 'Create Purchase Order' }}
          </Button>

          <Button type="button" variant="outline" onclick="history.back()">
            Cancel
          </Button>
        </div>
      </form>
    </div>

    <!-- Dropdown teleported to body -->
    <Teleport to="body">
      <!-- Supplier Dropdown -->
      <div
        v-show="(showSupplierDropdown && (getFilteredSuppliers.length > 0 || showCreateNewSupplierOption)) && supplierDropdownPosition"
        :style="{
          position: 'fixed',
          top: `${supplierDropdownPosition?.top || 0}px`,
          left: `${supplierDropdownPosition?.left || 0}px`,
          width: `${supplierDropdownPosition?.width || 250}px`,
          transform: supplierDropdownPosition?.showAbove ? 'translateY(-100%)' : 'none',
          zIndex: 9999
        }"
        class="bg-popover border border-border rounded-md shadow-lg max-h-60 overflow-auto"
      >
        <div
          v-for="supplier in getFilteredSuppliers"
          :key="supplier.supplier_id"
          @mousedown.prevent="selectSupplier(supplier)"
          class="px-3 py-2 cursor-pointer hover:bg-accent hover:text-accent-foreground text-sm"
        >
          <div class="font-medium text-foreground">{{ supplier.supplier_name }}</div>
        </div>

        <!-- Create New Supplier Option -->
        <div
          v-if="showCreateNewSupplierOption"
          @mousedown.prevent="initiateNewSupplier"
          class="px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm border-t border-border bg-blue-50/30"
        >
          <div class="flex items-center gap-2 font-medium text-blue-600">
            <UserPlus class="w-4 h-4" />
            <span>Create new supplier "{{ form.supplier_name }}"</span>
          </div>
          <div class="text-xs text-blue-500 mt-1">
            Click to add supplier details
          </div>
        </div>
      </div>

      <!-- Ingredient Dropdowns -->
      <div
        v-for="(item, index) in orderItems"
        :key="`dropdown-${index}`"
        v-show="item.showDropdown && getFilteredIngredients(index).length > 0 && item.dropdownPosition"
        :style="{
          position: 'fixed',
          top: `${item.dropdownPosition?.top || 0}px`,
          left: `${item.dropdownPosition?.left || 0}px`,
          width: `${item.dropdownPosition?.width || 250}px`,
          transform: item.dropdownPosition?.showAbove ? 'translateY(-100%)' : 'none',
          zIndex: 9999
        }"
        class="bg-popover border border-border rounded-md shadow-lg max-h-60 overflow-auto"
      >
        <div
          v-for="ingredient in getFilteredIngredients(index)"
          :key="ingredient.ingredient_id"
          @mousedown.prevent="selectIngredient(index, ingredient)"
          class="px-3 py-2 cursor-pointer hover:bg-accent hover:text-accent-foreground text-sm"
        >
          <div class="font-medium text-foreground">{{ ingredient.ingredient_name }}</div>
          <div class="text-xs text-muted-foreground">
            Unit: {{ ingredient.base_unit }} | Stock: {{ ingredient.current_stock }}
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>