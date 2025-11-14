<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface ReceiveItem {
  ingredient_id: number | null;
  ingredient_name: string;
  base_unit: string;
  packages: number;
  contents_quantity: number;
  package_price: number;
  notes: string;
  is_new: boolean;
}

interface Props {
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Purchase Orders', href: '/purchase-orders' },
  { title: 'Manual Receive', href: '/purchase-orders/manual-receive' },
];

const receiveItems = ref<ReceiveItem[]>([{
  ingredient_id: null,
  ingredient_name: '',
  base_unit: 'kg',
  packages: 0,
  contents_quantity: 0,
  package_price: 0,
  notes: '',
  is_new: true
}]);

const form = useForm({
  supplier_name: '',
  supplier_contact: '',
  supplier_email: '',
  reference_number: '',
  delivery_date: new Date().toISOString().split('T')[0],
  notes: '',
  items: [] as ReceiveItem[]
});

const subtotal = computed(() => {
  return receiveItems.value.reduce((sum, item) => {
    return sum + (item.packages * item.package_price);
  }, 0);
});

const getTotalStock = (item: ReceiveItem) => {
  return item.packages * item.contents_quantity;
};

const getTotalCost = (item: ReceiveItem) => {
  return item.packages * item.package_price;
};

const addItem = () => {
  receiveItems.value.push({
    ingredient_id: null,
    ingredient_name: '',
    base_unit: 'kg',
    packages: 0,
    contents_quantity: 0,
    package_price: 0,
    notes: '',
    is_new: true
  });
};

const removeItem = (index: number) => {
  if (receiveItems.value.length > 1) {
    receiveItems.value.splice(index, 1);
  }
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const onIngredientSelect = (index: number, ingredientId: number) => {
  const ingredient = props.ingredients.find(ing => ing.ingredient_id === ingredientId);
  if (ingredient) {
    receiveItems.value[index].ingredient_id = ingredient.ingredient_id;
    receiveItems.value[index].ingredient_name = ingredient.ingredient_name;
    receiveItems.value[index].base_unit = ingredient.base_unit;
    receiveItems.value[index].is_new = false;
  }
};

const onIngredientNameInput = (index: number) => {
  // When user types manually, mark as new ingredient
  receiveItems.value[index].is_new = true;
  receiveItems.value[index].ingredient_id = null;
};

const submit = () => {
  console.log('=== SUBMIT STARTED ===');
  console.log('All items:', receiveItems.value);

  // Filter out empty items
  const validItems = receiveItems.value.filter(item => {
    const hasIngredient = item.is_new ? item.ingredient_name.trim() : item.ingredient_id;
    const isValid = hasIngredient && item.packages > 0 && item.contents_quantity > 0 && item.package_price > 0;
    console.log('Item validation:', {
      ingredient: item.ingredient_name,
      hasIngredient,
      packages: item.packages,
      contents_quantity: item.contents_quantity,
      package_price: item.package_price,
      isValid
    });
    return isValid;
  });

  console.log('Valid items:', validItems);

  if (validItems.length === 0) {
    alert('Please add at least one item with valid ingredient name, packages, contents quantity and price');
    console.error('No valid items found');
    return;
  }

  // Check for new ingredients without base unit
  const invalidNewItems = validItems.filter(item => item.is_new && !item.base_unit.trim());
  if (invalidNewItems.length > 0) {
    alert('Please specify a unit for all new ingredients');
    console.error('Invalid new items:', invalidNewItems);
    return;
  }

  if (!form.supplier_name.trim()) {
    alert('Please enter supplier name');
    console.error('Supplier name is empty');
    return;
  }

  console.log('Form data before submit:', {
    supplier_name: form.supplier_name,
    supplier_contact: form.supplier_contact,
    supplier_email: form.supplier_email,
    reference_number: form.reference_number,
    delivery_date: form.delivery_date,
    notes: form.notes,
    items: validItems
  });

  form.items = validItems;

  console.log('Submitting form to /purchase-orders/manual-receive');
  form.post('/purchase-orders/manual-receive', {
    onSuccess: (response) => {
      console.log('SUCCESS:', response);
      alert('Items received successfully!');
    },
    onError: (errors) => {
      console.error('=== FORM ERRORS ===', errors);
      alert('Error: ' + JSON.stringify(errors));
    },
    onFinish: () => {
      console.log('=== FORM SUBMISSION FINISHED ===');
    }
  });
};
</script>

<template>
  <Head title="Manual Receive" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6 mb-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Manual Receive Orders</h1>
        <p class="text-muted-foreground">
          Receive ingredients from suppliers not registered in the system
        </p>
      </div>

      <form @submit.prevent="submit">
        <!-- Supplier Information -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
            <CardDescription>Enter details about the unregistered supplier</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="supplier_name">Supplier Name <span class="text-destructive">*</span></Label>
                <Input
                  id="supplier_name"
                  v-model="form.supplier_name"
                  placeholder="Enter supplier name"
                  required
                />
              </div>

              <div class="space-y-2">
                <Label for="supplier_contact">Contact Number</Label>
                <Input
                  id="supplier_contact"
                  v-model="form.supplier_contact"
                  placeholder="e.g., 09123456789"
                />
              </div>

              <div class="space-y-2">
                <Label for="supplier_email">Email Address</Label>
                <Input
                  id="supplier_email"
                  v-model="form.supplier_email"
                  type="email"
                  placeholder="supplier@example.com"
                />
              </div>

              <div class="space-y-2">
                <Label for="delivery_date">Delivery Date <span class="text-destructive">*</span></Label>
                <Input
                  id="delivery_date"
                  v-model="form.delivery_date"
                  type="date"
                  required
                />
              </div>

              <div class="space-y-2">
                <Label for="reference_number">Invoice/Reference Number</Label>
                <Input
                  id="reference_number"
                  v-model="form.reference_number"
                  placeholder="e.g., INV-2024-001"
                />
              </div>
            </div>

            <div class="space-y-2">
              <Label for="notes">Notes</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                placeholder="Additional notes about this delivery"
                rows="3"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Items -->
        <Card class="mb-6">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Received Items</CardTitle>
                <CardDescription>
                  Add ingredients received. You can select existing ingredients or type new ingredient names.
                </CardDescription>
              </div>
              <Button type="button" @click="addItem" variant="outline" size="sm">
                <Plus class="w-4 h-4 mr-2" />
                Add Item
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-[220px]">Ingredient Name</TableHead>
                    <TableHead class="w-[100px]">Base Unit</TableHead>
                    <TableHead class="w-[100px]">Packages</TableHead>
                    <TableHead class="w-[140px]">Contents Quantity</TableHead>
                    <TableHead class="w-[120px]">Package Price (₱)</TableHead>
                    <TableHead class="w-[120px]">Total Stock</TableHead>
                    <TableHead class="w-[120px]">Total Cost</TableHead>
                    <TableHead class="w-[150px]">Notes</TableHead>
                    <TableHead class="w-[60px]"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(item, index) in receiveItems" :key="index">
                    <TableCell>
                      <div class="space-y-1">
                        <Input
                          v-model="item.ingredient_name"
                          :list="`ingredients-list-${index}`"
                          placeholder="Enter or select ingredient"
                          @input="onIngredientNameInput(index)"
                          @change="() => {
                            const match = ingredients.find(ing =>
                              ing.ingredient_name.toLowerCase() === item.ingredient_name.toLowerCase()
                            );
                            if (match) onIngredientSelect(index, match.ingredient_id);
                          }"
                        />
                        <datalist :id="`ingredients-list-${index}`">
                          <option
                            v-for="ingredient in ingredients"
                            :key="ingredient.ingredient_id"
                            :value="ingredient.ingredient_name"
                          />
                        </datalist>
                        <div v-if="item.is_new && item.ingredient_name" class="text-xs text-muted-foreground">
                          New ingredient
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Select v-model="item.base_unit" :disabled="!item.is_new">
                        <SelectTrigger>
                          <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="kg">kg</SelectItem>
                          <SelectItem value="g">g</SelectItem>
                          <SelectItem value="L">L</SelectItem>
                          <SelectItem value="mL">mL</SelectItem>
                          <SelectItem value="pcs">pcs</SelectItem>
                          <SelectItem value="pack">pack</SelectItem>
                          <SelectItem value="bottle">bottle</SelectItem>
                          <SelectItem value="can">can</SelectItem>
                          <SelectItem value="box">box</SelectItem>
                          <SelectItem value="bag">bag</SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="item.packages"
                        type="number"
                        step="1"
                        min="0"
                        placeholder="0"
                      />
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2">
                        <Input
                          v-model.number="item.contents_quantity"
                          type="number"
                          step="0.01"
                          min="0"
                          placeholder="0"
                          class="flex-1"
                        />
                        <span class="text-sm text-muted-foreground whitespace-nowrap">
                          {{ item.base_unit || 'unit' }}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="item.package_price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                      />
                    </TableCell>
                    <TableCell>
                      <div class="text-sm font-medium">
                        {{ getTotalStock(item).toFixed(2) }} {{ item.base_unit || 'unit' }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="font-medium">
                        {{ formatCurrency(getTotalCost(item)) }}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model="item.notes"
                        placeholder="Optional"
                      />
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeItem(index)"
                        :disabled="receiveItems.length === 1"
                      >
                        <Trash2 class="w-4 h-4 text-destructive" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <!-- Summary -->
            <div class="flex justify-end mt-6 pt-4 border-t">
              <div class="w-64 space-y-2">
                <div class="flex justify-between text-lg font-semibold">
                  <span>Total Amount:</span>
                  <span>{{ formatCurrency(subtotal) }}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Error Display -->
        <Card v-if="Object.keys(form.errors).length > 0" class="mb-6 border-destructive">
          <CardHeader>
            <CardTitle class="text-destructive">Validation Errors</CardTitle>
          </CardHeader>
          <CardContent>
            <ul class="list-disc list-inside space-y-1 text-sm">
              <li v-for="(error, key) in form.errors" :key="key" class="text-destructive">
                <strong>{{ key }}:</strong> {{ error }}
              </li>
            </ul>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="$inertia.visit('/purchase-orders')">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Receive Items' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
