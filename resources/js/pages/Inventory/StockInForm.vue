<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from "vue";
import { SquareX, SquarePlus } from 'lucide-vue-next';

// UI Components (same as PurchaseOrder/Create)
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface FormItem {
  ingredient_id: number | string;
  item_type: string;
  unit: string;
  quantity: number;
  unit_price: number;
}

interface NewIngredient {
  ingredient_name: string;
  base_unit: string;
  reorder_level: number;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Stock In', href: '/inventory/stock-in' },
];

const props = defineProps<{
  suppliers: Supplier[];
  ingredients: Ingredient[];
  restaurant_id: number;
}>();

const ingredientsList = ref<Ingredient[]>([...props.ingredients]);
const showModal = ref(false);
const modalProcessing = ref(false);
const modalError = ref("");

interface StockInForm {
  supplier_id: string | number;
  restaurant_id: number;
  reference_no: string;
  items: FormItem[];
  [key: string]: any;
}

const form = useForm<StockInForm>({
  supplier_id: "",
  restaurant_id: props.restaurant_id,
  reference_no: "",
  items: [],
});

const newIngredient = reactive<NewIngredient>({
  ingredient_name: "",
  base_unit: "kg",
  reorder_level: 0,
});

onMounted(() => {
  if (form.items.length === 0) {
    addItem();
  }
});

function addItem() {
  form.items.push({
    ingredient_id: "",
    item_type: "Bulk",
    unit: "kg",
    quantity: 1,
    unit_price: 0,
  });
}

function removeItem(index: number) {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
}

function setUnitForItem(item: FormItem) {
  const ing = ingredientsList.value.find(i => i.ingredient_id === item.ingredient_id);
  if (ing) {
    item.unit = ing.base_unit;
  }
}

async function saveNewIngredient() {
  modalProcessing.value = true;
  modalError.value = "";
  try {
    const res = await fetch(route("ingredients.store.quick"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(newIngredient),
    });

    const result = await res.json();
    if (!res.ok || result.error) {
      modalError.value = result.error || "Validation failed.";
      modalProcessing.value = false;
      return;
    }

    ingredientsList.value.push(result);
    form.items[form.items.length - 1].ingredient_id = result.ingredient_id;
    showModal.value = false;

    // reset modal
    newIngredient.ingredient_name = "";
    newIngredient.base_unit = "kg";
    newIngredient.reorder_level = 0;
  } catch (e) {
    modalError.value = "Error saving ingredient.";
    console.error("Error saving ingredient:", e);
  }
  modalProcessing.value = false;
}

function resetAndCancel() {
  form.visit(route('inventory.index'));
}

const itemTotal = (item: FormItem) => (item.quantity * item.unit_price).toFixed(2);
const orderTotal = () => form.items.reduce((total, item) => total + (item.quantity * item.unit_price), 0).toFixed(2);
</script>

<template>
  <Head title="Stock In" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Stock In</h1>
          <p class="text-gray-600 mt-1">Record new stock for your restaurant</p>
        </div>
      </div>

      <!-- Main Form -->
      <Card class="max-w-5xl mx-auto">
        <CardHeader>
          <CardTitle>Stock-In Information</CardTitle>
          <CardDescription>Fill in the stock-in details</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="form.post(route('stock-in.store'))" class="space-y-6">
            
            <!-- Supplier & Reference -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="supplier">Supplier *</Label>
                <Select v-model="form.supplier_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select supplier" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="s in suppliers" :key="s.supplier_id" :value="s.supplier_id">
                      {{ s.supplier_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="reference_no">Reference Number</Label>
                <Input v-model="form.reference_no" type="text" placeholder="Optional reference" />
              </div>
            </div>

            <!-- Items Section -->
            <div>
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900">Items</h2>
                <div class="flex gap-2">
                  <Button type="button" variant="success" @click="showModal = true">
                    <SquarePlus class="h-4 w-4 mr-2" /> Add Ingredient
                  </Button>
                  <Button type="button" @click="addItem">
                    <SquarePlus class="h-4 w-4 mr-2" /> Add Item
                  </Button>
                </div>
              </div>

              <div v-for="(item, index) in form.items" :key="index" class="p-4 border rounded-lg space-y-4 mb-4">
                <!-- Ingredient -->
                <div>
                  <Label>Ingredient</Label>
                  <Select v-model="item.ingredient_id" @update:modelValue="setUnitForItem(item)">
                    <SelectTrigger>
                      <SelectValue placeholder="Select Ingredient" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="i in ingredientsList" :key="i.ingredient_id" :value="i.ingredient_id">
                        {{ i.ingredient_name }} ({{ i.base_unit }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Type, Unit, Qty, Price -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div>
                    <Label>Type</Label>
                    <Select v-model="item.item_type">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Bulk">Bulk</SelectItem>
                        <SelectItem value="Box">Box</SelectItem>
                        <SelectItem value="Case">Case</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Unit</Label>
                    <Input v-model="item.unit" placeholder="kg, pcs" />
                  </div>
                  <div>
                    <Label>Quantity</Label>
                    <Input v-model.number="item.quantity" type="number" min="0.01" step="0.01" />
                  </div>
                  <div>
                    <Label>Unit Price</Label>
                    <Input v-model.number="item.unit_price" type="number" min="0" step="0.01" />
                  </div>
                </div>

                <!-- Item Total & Remove -->
                <div class="flex justify-between items-center">
                  <p class="font-semibold">Item Total: ${{ itemTotal(item) }}</p>
                  <Button type="button" variant="destructive" size="sm" @click="removeItem(index)" :disabled="form.items.length <= 1">
                    <SquareX class="h-4 w-4 mr-1" /> Remove
                  </Button>
                </div>
              </div>
            </div>

            <!-- Order Total -->
            <div class="text-right font-bold text-xl">
              Order Total: ${{ orderTotal() }}
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
              <Button type="button" variant="outline" @click="resetAndCancel">Cancel</Button>
              <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Processing...' : 'Save Stock-In' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
        <Card class="relative w-[90%] max-w-lg z-10">
          <CardHeader>
            <CardTitle>Add New Ingredient</CardTitle>
            <CardDescription>Fill in the ingredient details</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="saveNewIngredient" class="space-y-4">
              <div>
                <Label>Ingredient Name</Label>
                <Input v-model="newIngredient.ingredient_name" type="text" required />
              </div>
              <div>
                <Label>Base Unit</Label>
                <Select v-model="newIngredient.base_unit">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="kg">kg</SelectItem>
                    <SelectItem value="pcs">pcs</SelectItem>
                    <SelectItem value="box">box</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Reorder Level</Label>
                <Input v-model.number="newIngredient.reorder_level" type="number" min="0" />
              </div>
              <div v-if="modalError" class="text-red-600 text-sm">{{ modalError }}</div>
              <div class="flex justify-end gap-4">
                <Button type="button" variant="outline" @click="showModal = false" :disabled="modalProcessing">Cancel</Button>
                <Button type="submit" :disabled="modalProcessing">
                  {{ modalProcessing ? 'Saving...' : 'Save Ingredient' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
