<script setup lang="ts">
import { ref, computed } from 'vue';
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

interface OrderItem {
  ingredient_id: number | null;
  ingredient_name: string;
  ordered_quantity: number;
  unit_price: number;
  unit_of_measure: string;
  notes: string;
  showDropdown?: boolean;
  dropdownPosition?: { top: number; left: number; width: number };
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

const form = useForm({
  supplier_id: null as number | null,
  supplier_name: '',
  items: [] as OrderItem[]
});

const showSupplierDropdown = ref(false);
const supplierDropdownPosition = ref<{ top: number; left: number; width: number } | null>(null);

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

const onIngredientInput = (itemIndex: number, event?: Event) => {
  const item = orderItems.value[itemIndex];
  item.showDropdown = item.ingredient_name.length > 0;
  item.ingredient_id = null; // Reset ID when user types

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    item.dropdownPosition = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
  }
};

const onIngredientFocus = (itemIndex: number, event: Event) => {
  const item = orderItems.value[itemIndex];
  item.showDropdown = item.ingredient_name.length > 0;

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    item.dropdownPosition = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
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
      item.ordered_quantity > 0
    )
    .map(item => ({
      ingredient_id: item.ingredient_id,
      ordered_quantity: item.ordered_quantity,
      unit_price: item.unit_price || 0,
      unit_of_measure: item.unit_of_measure,
      notes: item.notes
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

const onSupplierInput = (event: Event) => {
  showSupplierDropdown.value = form.supplier_name.length > 0;
  // Reset supplier_id when user types (forces them to select from dropdown)
  form.supplier_id = null;

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    supplierDropdownPosition.value = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
  }
};

const onSupplierFocus = (event: Event) => {
  showSupplierDropdown.value = form.supplier_name.length > 0;

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    supplierDropdownPosition.value = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
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
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Order Items</h3>
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
                          @input="(e) => onIngredientInput(index, e)"
                          @focus="(e) => onIngredientFocus(index, e)"
                          @blur="closeDropdown(index)"
                          placeholder="Type ingredient name..."
                          class="min-w-[250px]"
                        />
                        <div
                          v-if="form.errors[`items.${index}.ingredient_id`]"
                          class="text-xs text-red-600"
                        >
                          {{ form.errors[`items.${index}.ingredient_id`] }}
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
                          v-if="form.errors[`items.${index}.ordered_quantity`]"
                          class="text-xs text-red-600"
                        >
                          {{ form.errors[`items.${index}.ordered_quantity`] }}
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
              
              <div v-if="form.errors.items" class="text-sm text-red-600">
                {{ form.errors.items }}
              </div>
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
        v-show="showSupplierDropdown && getFilteredSuppliers.length > 0 && supplierDropdownPosition"
        :style="{
          position: 'fixed',
          top: `${supplierDropdownPosition?.top || 0}px`,
          left: `${supplierDropdownPosition?.left || 0}px`,
          width: `${supplierDropdownPosition?.width || 250}px`,
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