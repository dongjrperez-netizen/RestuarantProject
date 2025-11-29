<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

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

interface PurchaseOrderItem {
  purchase_order_item_id?: number;
  ingredient_id: number;
  ingredient: {
    ingredient_name: string;
    base_unit: string;
  };
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
}

interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  supplier_id: number | null;
  notes: string;
  supplier: {
    supplier_name: string;
  } | null;
  items: PurchaseOrderItem[];
}

interface OrderItem {
  ingredient_id: number | null;
  ingredient_name: string;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
  showDropdown?: boolean;
  dropdownPosition?: { top: number; left: number; width: number; showAbove?: boolean };
  [key: string]: any;
}

interface Props {
  purchaseOrder: PurchaseOrder;
  ingredients: Ingredient[];
  suppliers: Supplier[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: `PO ${props.purchaseOrder.po_number}`, href: `/purchase-orders/${props.purchaseOrder.purchase_order_id}` },
  { title: 'Edit', href: '#' },
];

const orderItems = ref<OrderItem[]>([]);

const form = useForm({
  supplier_id: props.purchaseOrder.supplier_id || null,
  supplier_name: props.purchaseOrder.supplier?.supplier_name || '',
  items: [] as OrderItem[]
});

const showSupplierDropdown = ref(false);
const supplierDropdownPosition = ref<{ top: number; left: number; width: number; showAbove?: boolean } | null>(null);

// Helper function to calculate smart dropdown position
const calculateDropdownPosition = (inputElement: HTMLElement) => {
  const rect = inputElement.getBoundingClientRect();
  const dropdownMaxHeight = 240; // max-h-60 = 240px
  const spaceBelow = window.innerHeight - rect.bottom;
  const spaceAbove = rect.top;

  const showAbove = spaceBelow < dropdownMaxHeight && spaceAbove > spaceBelow;
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

const removeOrderItem = (index: number) => {
  if (orderItems.value.length > 1) {
    orderItems.value.splice(index, 1);
  }
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
  orderItems.value[idx].unit_price = Math.round((ingredient.cost_per_unit ?? 0) * 100) / 100;
};

// Ingredients lookup scrolling
const ingredientsListRef = ref<HTMLElement | null>(null);

// Search for available ingredients in the right-hand lookup
const ingredientsSearch = ref('');

const filteredAvailableIngredients = computed(() => {
  const q = ingredientsSearch.value?.toLowerCase().trim() || '';
  if (!q) return props.ingredients;
  return props.ingredients.filter(i => i.ingredient_name.toLowerCase().includes(q));
});

// Pagination for available ingredients (10 per page)
const ingredientsPage = ref(1);
const ingredientsPerPage = 10;

const ingredientsPageCount = computed(() => {
  return Math.max(1, Math.ceil(filteredAvailableIngredients.value.length / ingredientsPerPage));
});

const paginatedAvailableIngredients = computed(() => {
  const start = (ingredientsPage.value - 1) * ingredientsPerPage;
  return filteredAvailableIngredients.value.slice(start, start + ingredientsPerPage);
});

// Reset to first page when search changes
watch(ingredientsSearch, () => {
  ingredientsPage.value = 1;
  resetIngredientsScroll();
});

const resetIngredientsScroll = () => {
  const el = ingredientsListRef.value;
  if (!el) return;
  el.scrollTop = 0;
};

const prevIngredientsPage = () => {
  if (ingredientsPage.value > 1) {
    ingredientsPage.value -= 1;
    resetIngredientsScroll();
  }
};

const nextIngredientsPage = () => {
  if (ingredientsPage.value < ingredientsPageCount.value) {
    ingredientsPage.value += 1;
    resetIngredientsScroll();
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

const getSelectedIngredient = (itemIndex: number) => {
  const item = orderItems.value[itemIndex];
  if (!item.ingredient_id) return null;

  return props.ingredients.find(ing => ing.ingredient_id === item.ingredient_id);
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const submit = () => {
  // Filter out empty items and validate
  const validItems = orderItems.value
    .filter(item =>
      item.ingredient_id &&
      item.ordered_quantity > 0 &&
      item.unit_price > 0
    )
    .map(item => ({
      ingredient_id: item.ingredient_id,
      ingredient_name: item.ingredient_name,
      ordered_quantity: item.ordered_quantity,
      unit_price: item.unit_price,
      unit_of_measure: item.unit_of_measure,
      notes: item.notes
    }));

  form.items = validItems;

  form.put(`/purchase-orders/${props.purchaseOrder.purchase_order_id}`, {
    onError: (errors) => {
      console.error('Purchase order validation/server errors', errors);
    },
  });
};

const closeDropdown = (itemIndex: number) => {
  setTimeout(() => {
    orderItems.value[itemIndex].showDropdown = false;
  }, 200);
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

// Initialize form with existing data
onMounted(() => {
  orderItems.value = props.purchaseOrder.items.map(item => ({
    ingredient_id: item.ingredient_id,
    ingredient_name: item.ingredient.ingredient_name,
    ordered_quantity: item.ordered_quantity,
    unit_price: item.unit_price,
    unit_of_measure: item.unit_of_measure,
    notes: item.notes || '',
    showDropdown: false,
    dropdownPosition: undefined
  }));
});
</script>

<template>
  <Head :title="`Edit Purchase Order ${purchaseOrder.po_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="space-y-6 mx-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Purchase Order {{ purchaseOrder.po_number }}</h1>
        <p class="text-muted-foreground">Update purchase order details</p>
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
          <CardHeader>
            <CardTitle>Order Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="space-y-2">
              <Label for="supplier">Supplier *</Label>
              <Input
                id="supplier"
                v-model="form.supplier_name"
                @input="onSupplierInput"
                @focus="onSupplierFocus"
                @blur="closeSupplierDropdown"
                placeholder="Type supplier name..."
                required
              />
              <div v-if="form.errors.supplier_name" class="text-sm text-red-600">
                {{ form.errors.supplier_name }}
              </div>
            </div>

            <!-- Order Items -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Order Items</h3>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Order Items (2 columns) -->
                <Card class="lg:col-span-2">
                  <CardContent class="pt-6">
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
                              <TableHead class="min-w-[220px]">Ingredient</TableHead>
                              <TableHead class="min-w-[100px]">Quantity</TableHead>
                              <TableHead class="min-w-[120px]">Unit Price</TableHead>
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
                                    step="1"
                                    min="1"
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
                                <div class="space-y-1">
                                  <Input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="min-w-[100px]"
                                  />
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
                        
                        <div v-if="(form.errors as any).items" class="text-sm text-red-600 mt-2">
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
                      <div class="flex flex-col items-center space-y-1">
                        <div class="flex items-center space-x-2">
                          <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="prevIngredientsPage"
                            :disabled="ingredientsPage === 1"
                            aria-label="Previous page">
                            ▲
                          </Button>

                          <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="nextIngredientsPage"
                            :disabled="ingredientsPage === ingredientsPageCount"
                            aria-label="Next page">
                            ▼
                          </Button>
                        </div>

                        <div class="text-sm text-muted-foreground">
                          Page {{ ingredientsPage }} / {{ ingredientsPageCount }}
                        </div>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div class="mb-3">
                      <Input
                        v-model="ingredientsSearch"
                        placeholder="Search available ingredients..."
                        class="w-full"
                        @input="resetIngredientsScroll"
                      />
                    </div>

                    <div ref="ingredientsListRef" class="overflow-auto max-h-96">
                      <Table class="text-xs">
                        <TableHeader>
                          <TableRow>
                            <TableHead class="text-xs">Ingredient</TableHead>
                            <TableHead class="text-xs text-right">Stock</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          <TableRow v-for="ingredient in paginatedAvailableIngredients" :key="ingredient.ingredient_id">
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
                          <TableRow v-if="filteredAvailableIngredients.length === 0">
                            <TableCell colspan="2" class="text-center py-4 text-sm text-muted-foreground">
                              No ingredients found.
                            </TableCell>
                          </TableRow>
                          <TableRow v-if="filteredAvailableIngredients.length > 0 && paginatedAvailableIngredients.length === 0">
                            <TableCell colspan="2" class="text-center py-4 text-sm text-muted-foreground">
                              No ingredients on this page.
                            </TableCell>
                          </TableRow>
                        </TableBody>
                      </Table>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button
            type="submit"
            :disabled="form.processing || !form.supplier_id || orderItems.length === 0"
          >
            {{ form.processing ? 'Updating...' : 'Update Purchase Order' }}
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

    <!-- Dropdown teleported to body -->
    <Teleport to="body">
      <!-- Supplier Dropdown -->
      <div
        v-show="showSupplierDropdown && getFilteredSuppliers.length > 0 && supplierDropdownPosition"
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
