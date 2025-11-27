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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';



interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  current_stock: number;
  reorder_level: number;
  base_unit: string;
  is_low_stock: boolean;
}

interface Stats {
  total_ingredients: number;
  low_stock_count: number;
}

const props = defineProps<{
  ingredients: Ingredient[];
  stats: Stats;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
];

// Pagination
const currentPage = ref(1);
const itemsPerPage = 7;

const searchQuery = ref('');
const stockFilter = ref('all'); // all | low | in

const filteredIngredients = computed(() => {
  return props.ingredients.filter((ingredient) => {
    const matchesSearch = ingredient.ingredient_name
      .toLowerCase()
      .includes(searchQuery.value.toLowerCase());

    const matchesFilter =
      stockFilter.value === 'all' ||
      (stockFilter.value === 'low' && ingredient.is_low_stock) ||
      (stockFilter.value === 'in' && !ingredient.is_low_stock);

    return matchesSearch && matchesFilter;
  });
});

// Updated pagination to use filtered results
const paginatedIngredients = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredIngredients.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredIngredients.value.length / itemsPerPage);
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
  ingredient_name: '',
  reorder_level: 0,
  base_unit: '',
});

const startEdit = (ingredient: Ingredient) => {
  currentIngredient.value = ingredient;
  editForm.reorder_level = ingredient.reorder_level;
  editForm.ingredient_name = ingredient.ingredient_name;
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



const selectedCategory = ref('all');

// Perform search
const search = () => {
  router.get(route('inventory.ingredients.index'), {
    search: searchQuery.value,
    status: stockFilter.value,
  }, { preserveState: true, replace: true });
};

// Clear filters

const clearFilters = () => {
  searchQuery.value = '';
  stockFilter.value = 'all';
  search();
};
</script>

<template>
  <Head title="Ingredients Inventory" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Success/Error Messages -->
      <div v-if="successMessage" class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-md">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-md">
        {{ errorMessage }}
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Ingredients Inventory</h1>
          <p class="text-muted-foreground">Manage your ingredient stock levels</p>
        </div>
        <div>
          <Button @click="router.visit('/inventory/ingredients/create')">
            Add Ingredient
          </Button>
        </div>
      </div>
         <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-2">
        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Total Ingredients</div>
            <div class="text-2xl font-bold">{{ formatNumber(stats.total_ingredients) }}</div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Low Stock Ingredients</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ formatNumber(stats.low_stock_count) }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ stats.low_stock_count === 0 ? 'All ingredients are well stocked' : 'Items need reordering' }}
            </p>
          </CardContent>
        </Card>
      </div>
      <!-- Ingredients Table -->
      <Card>
       <CardHeader>
        <div class="flex items-center justify-between flex-wrap gap-3">
          <!-- Left side title -->
          <CardTitle>Inventory Stock Levels</CardTitle>

          <!-- Right side search + filter controls -->
          <div class="flex items-center gap-2">
            <!-- Filter by stock status -->
            <Select v-model="stockFilter" @change="search">
              <SelectTrigger class="w-36">
                <SelectValue placeholder="All Stock" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="in">In Stock</SelectItem>
                <SelectItem value="low">Low Stock</SelectItem>
              </SelectContent>
            </Select>

            <!-- Search input -->
            <Input
              v-model="searchQuery"
              placeholder="Search ingredients..."
              @keyup.enter="search"
              class="w-48"
            />

            <Button @click="clearFilters" variant="ghost" size="sm">Clear</Button>
          </div>
        </div>
      </CardHeader>

        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient Name</TableHead>
                <TableHead>Current Stock</TableHead>
                <TableHead>Reorder Level</TableHead>
                <TableHead>Unit</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-center">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="ingredient in paginatedIngredients"
                :key="ingredient.ingredient_id"
                :class="{ 'bg-yellow-50 dark:bg-yellow-950/20 border-l-4 border-yellow-400 dark:border-yellow-600': ingredient.is_low_stock }"
              >
                <TableCell class="font-medium">
                  {{ ingredient.ingredient_name }}
                  <Badge v-if="ingredient.is_low_stock" variant="secondary" class="ml-2 text-xs bg-yellow-100 dark:bg-yellow-950/50 text-yellow-800 dark:text-yellow-300">
                    Low Stock
                  </Badge>
                </TableCell>
                <TableCell>
                  <span :class="{ 'text-yellow-600 dark:text-yellow-400 font-semibold': ingredient.is_low_stock }">
                    {{ formatNumber(ingredient.current_stock) }}
                  </span>
                </TableCell>
                <TableCell>{{ formatNumber(ingredient.reorder_level) }}</TableCell>
                <TableCell>{{ ingredient.base_unit }}</TableCell>
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
                <TableCell colspan="6" class="text-center py-8">
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
            <Label for="ingredient-name" class="text-right">Ingredient Name</Label>
            <div class="col-span-3">
              <Input
                id="ingredient-name"
                v-model="editForm.ingredient_name"
                type="text"
                placeholder="Enter ingredient name"
                :class="{ 'border-red-500': editForm.errors.ingredient_name }"
                required
              />
              <p v-if="editForm.errors.ingredient_name" class="text-red-500 text-xs mt-1">
                {{ editForm.errors.ingredient_name }}
              </p>
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