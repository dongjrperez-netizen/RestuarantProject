<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Badge from '@/components/ui/badge/Badge.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Edit } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Declare Echo type for TypeScript
declare global {
  interface Window {
    Echo: any;
  }
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  current_stock: number;
  reorder_level: number;
  base_unit: string;
  supplier_name: string;
  is_low_stock: boolean;
  suppliers: Supplier[];
}

interface Stats {
  total_ingredients: number;
  low_stock_count: number;
}

const props = defineProps<{
  ingredients: Ingredient[];
  stats: Stats;
  user?: any;
}>();

// Make ingredients reactive so we can update them in real-time
const ingredients = ref<Ingredient[]>([...props.ingredients]);
const stats = ref({ ...props.stats });

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
];

// Pagination
const currentPage = ref(1);
const itemsPerPage = 6;

const paginatedIngredients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return ingredients.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(ingredients.value.length / itemsPerPage);
});

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const formatNumber = (num: number): string => {
  return new Intl.NumberFormat().format(num);
};

// Get flash messages from page props
const page = usePage();
const successMessage = computed(() => (page.props as any).flash?.success);
const errorMessage = computed(() => (page.props as any).flash?.error);

// Edit functionality
const showEditModal = ref(false);
const currentIngredient = ref<Ingredient | null>(null);
const editForm = useForm({
  reorder_level: 0,
  base_unit: '',
});

const startEdit = (ingredient: Ingredient) => {
  currentIngredient.value = ingredient;
  editForm.reorder_level = ingredient.reorder_level;
  editForm.base_unit = ingredient.base_unit;
  showEditModal.value = true;
};

const cancelEdit = () => {
  showEditModal.value = false;
  currentIngredient.value = null;
  editForm.reset();
  editForm.clearErrors();
};

const saveEdit = () => {
  if (!currentIngredient.value) return;

  editForm.put(`/inventory/ingredients/${currentIngredient.value.ingredient_id}`, {
    onSuccess: () => {
      showEditModal.value = false;
      currentIngredient.value = null;
      editForm.reset();
    },
    onError: () => {
      // Form errors will be handled by Inertia automatically
    },
  });
};

// Real-time inventory updates
onMounted(() => {
  const user = (page.props as any).auth?.user;

  if (window.Echo && user) {
    const restaurantId = user.id; // User ID is the restaurant ID

    console.log('Setting up inventory real-time listener for restaurant:', restaurantId);

    // Listen for inventory updates for this restaurant
    window.Echo.private(`restaurant.${restaurantId}.inventory`)
      .listen('.inventory.updated', (event: any) => {
        console.log('Inventory update received:', event);

        // Find and update the ingredient in the list
        const index = ingredients.value.findIndex(
          (ing) => ing.ingredient_id === event.ingredient.ingredient_id
        );

        if (index !== -1) {
          // Update the existing ingredient
          ingredients.value[index] = {
            ...ingredients.value[index],
            current_stock: event.ingredient.current_stock,
            is_low_stock: event.ingredient.current_stock <= ingredients.value[index].reorder_level,
          };

          // Update low stock count
          const lowStockCount = ingredients.value.filter(
            (ing) => ing.is_low_stock
          ).length;
          stats.value.low_stock_count = lowStockCount;

          console.log(`Updated ${event.ingredient.ingredient_name}: ${event.previous_stock} → ${event.new_stock}`);
        }
      });

    console.log('Inventory real-time updates enabled');
  }
});

onUnmounted(() => {
  const user = (page.props as any).auth?.user;

  if (window.Echo && user) {
    const restaurantId = user.id;
    window.Echo.leave(`restaurant.${restaurantId}.inventory`);
    console.log('Inventory real-time listener disconnected');
  }
});
</script>

<template>
  <Head title="Ingredients Inventory" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Success/Error Messages -->
      <div v-if="successMessage" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
        {{ errorMessage }}
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Ingredients Inventory</h1>
          <p class="text-muted-foreground">Monitor your ingredient stock levels and reorder points</p>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Total Ingredients</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatNumber(stats.total_ingredients) }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Low Stock Ingredients</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(stats.low_stock_count) }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ stats.low_stock_count === 0 ? 'All ingredients are well stocked' : 'Items need reordering' }}
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Ingredients Table -->
      <Card>
        <CardHeader>
          <CardTitle>Inventory Stock Levels</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient Name</TableHead>
                <TableHead>Current Stock</TableHead>
                <TableHead>Reorder Level</TableHead>
                <TableHead>Unit</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-center">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="ingredient in paginatedIngredients"
                :key="ingredient.ingredient_id"
                :class="{ 'bg-yellow-50 border-l-4 border-yellow-400': ingredient.is_low_stock }"
              >
                <TableCell class="font-medium">
                  {{ ingredient.ingredient_name }}
                  <Badge v-if="ingredient.is_low_stock" variant="secondary" class="ml-2 text-xs">
                    Low Stock
                  </Badge>
                </TableCell>
                <TableCell>
                  <span :class="{ 'text-yellow-600 font-semibold': ingredient.is_low_stock }">
                    {{ formatNumber(ingredient.current_stock) }}
                  </span>
                </TableCell>
                <TableCell>{{ formatNumber(ingredient.reorder_level) }}</TableCell>
                <TableCell>{{ ingredient.base_unit }}</TableCell>
                <TableCell>{{ ingredient.supplier_name }}</TableCell>
                <TableCell>
                  <Badge :variant="ingredient.is_low_stock ? 'destructive' : 'default'">
                    {{ ingredient.is_low_stock ? 'Reorder Now' : 'In Stock' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-center">
                  <Button
                    @click="startEdit(ingredient)"
                    size="sm"
                    variant="outline"
                  >
                    <Edit class="h-4 w-4" />
                  </Button>
                </TableCell>
              </TableRow>

              <TableRow v-if="ingredients.length === 0">
                <TableCell colspan="7" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No ingredients found</div>
                    <div class="text-sm">Your ingredient inventory is empty.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between px-6 py-4 border-t">
          <div class="text-sm text-muted-foreground">
            Showing {{ ((currentPage - 1) * itemsPerPage) + 1 }} to {{ Math.min(currentPage * itemsPerPage, ingredients.length) }} of {{ ingredients.length }} ingredients
          </div>
          <div class="flex items-center gap-2">
            <Button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              variant="outline"
              size="sm"
            >
              Previous
            </Button>
            <div class="flex items-center gap-1">
              <Button
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                :variant="currentPage === page ? 'default' : 'outline'"
                size="sm"
                class="w-10"
              >
                {{ page }}
              </Button>
            </div>
            <Button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              variant="outline"
              size="sm"
            >
              Next
            </Button>
          </div>
        </div>
      </Card>
    </div>

    <!-- Edit Ingredient Details Modal -->
    <Dialog v-model:open="showEditModal">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Edit Ingredient Details</DialogTitle>
          <DialogDescription>
            Update the reorder level and unit for <strong>{{ currentIngredient?.ingredient_name }}</strong>.
            The reorder level determines when you'll be notified to restock this ingredient.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="saveEdit" class="grid gap-4 py-4">
          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="current-stock" class="text-right text-sm text-muted-foreground">
              Current Stock
            </Label>
            <div class="col-span-3 text-sm">
              {{ formatNumber(currentIngredient?.current_stock || 0) }} {{ currentIngredient?.base_unit }}
            </div>
          </div>

          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="reorder-level" class="text-right">
              Reorder Level
            </Label>
            <div class="col-span-3">
              <Input
                id="reorder-level"
                v-model.number="editForm.reorder_level"
                type="number"
                min="0"
                step="0.01"
                :placeholder="`Enter reorder level`"
                :class="{ 'border-red-500': editForm.errors.reorder_level }"
                required
              />
              <p v-if="editForm.errors.reorder_level" class="text-red-500 text-xs mt-1">
                {{ editForm.errors.reorder_level }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-4 items-center gap-4">
            <Label for="base-unit" class="text-right">
              Unit
            </Label>
            <div class="col-span-3">
              <Input
                id="base-unit"
                v-model="editForm.base_unit"
                type="text"
                placeholder="e.g., kg, lbs, pcs, ml, etc."
                list="unit-options"
                :class="{ 'border-red-500': editForm.errors.base_unit }"
                required
              />
              <datalist id="unit-options">
                <option value="kg">kg - Kilogram</option>
                <option value="g">g - Gram</option>
                <option value="lbs">lbs - Pounds</option>
                <option value="oz">oz - Ounces</option>
                <option value="pcs">pcs - Pieces</option>
                <option value="ml">ml - Milliliter</option>
                <option value="L">L - Liter</option>
                <option value="cups">cups - Cups</option>
                <option value="tbsp">tbsp - Tablespoon</option>
                <option value="tsp">tsp - Teaspoon</option>
                <option value="boxes">boxes - Boxes</option>
                <option value="bottles">bottles - Bottles</option>
                <option value="cans">cans - Cans</option>
                <option value="bags">bags - Bags</option>
                <option value="packets">packets - Packets</option>
              </datalist>
              <p v-if="editForm.errors.base_unit" class="text-red-500 text-xs mt-1">
                {{ editForm.errors.base_unit }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">
                Common units: kg, g, lbs, oz, pcs, ml, L, etc.
              </p>
            </div>
          </div>
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" @click="cancelEdit">
            Cancel
          </Button>
          <Button
            type="button"
            @click="saveEdit"
            :disabled="editForm.processing"
            class="ml-2"
          >
            <span v-if="editForm.processing">Saving...</span>
            <span v-else>Save Changes</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>