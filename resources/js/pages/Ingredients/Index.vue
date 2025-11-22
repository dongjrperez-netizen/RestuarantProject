<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Plus, Pencil, Trash2, Search, AlertTriangle } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  cost_per_unit: number;
  current_stock: number;
  reorder_level: number;
  packages: number;
  is_low_stock: boolean;
}

interface Props {
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Ingredients Library', href: '#' },
];

const searchQuery = ref('');
const showDeleteDialog = ref(false);
const ingredientToDelete = ref<number | null>(null);

const filteredIngredients = computed(() => {
  if (!searchQuery.value) return props.ingredients;

  const query = searchQuery.value.toLowerCase();
  return props.ingredients.filter(ingredient =>
    ingredient.ingredient_name.toLowerCase().includes(query) ||
    ingredient.base_unit.toLowerCase().includes(query)
  );
});

const lowStockCount = computed(() => {
  return props.ingredients.filter(i => i.is_low_stock).length;
});

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatNumber = (num: number) => {
  return Number(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const editIngredient = (id: number) => {
  router.visit(`/ingredients-library/${id}/edit`);
};

const confirmDelete = (id: number) => {
  ingredientToDelete.value = id;
  showDeleteDialog.value = true;
};

const deleteIngredient = () => {
  if (ingredientToDelete.value) {
    router.delete(`/ingredients-library/${ingredientToDelete.value}`, {
      onSuccess: () => {
        showDeleteDialog.value = false;
        ingredientToDelete.value = null;
      },
    });
  }
};

const cancelDelete = () => {
  showDeleteDialog.value = false;
  ingredientToDelete.value = null;
};
</script>

<template>
  <Head title="Ingredients Library" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Ingredients Library</h1>
          <p class="text-muted-foreground">Manage your restaurant's ingredient inventory</p>
        </div>
        <Button @click="router.visit('/ingredients-library/create')">
          <Plus class="w-4 h-4 mr-2" />
          Add Ingredient
        </Button>
      </div>

      <!-- Stats -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total Ingredients</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ ingredients.length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium text-muted-foreground">Low Stock Items</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center space-x-2">
              <div class="text-2xl font-bold" :class="lowStockCount > 0 ? 'text-orange-600' : ''">
                {{ lowStockCount }}
              </div>
              <AlertTriangle v-if="lowStockCount > 0" class="w-5 h-5 text-orange-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total Value</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatCurrency(ingredients.reduce((sum, i) => sum + (i.current_stock * i.cost_per_unit), 0)) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Search and Filters -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Ingredients</CardTitle>
            <div class="relative w-64">
              <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                placeholder="Search ingredients..."
                class="pl-8"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Ingredient Name</TableHead>
                  <TableHead>Base Unit</TableHead>
                  <TableHead>Cost per Unit</TableHead>
                  <TableHead>Current Stock</TableHead>
                  <TableHead>Reorder Level</TableHead>
                  <TableHead>Packages</TableHead>
                  <TableHead>Total Value</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-if="filteredIngredients.length === 0">
                  <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                    {{ searchQuery ? 'No ingredients found matching your search.' : 'No ingredients yet. Add your first ingredient to get started!' }}
                  </TableCell>
                </TableRow>
                <TableRow v-for="ingredient in filteredIngredients" :key="ingredient.ingredient_id">
                  <TableCell class="font-medium">{{ ingredient.ingredient_name }}</TableCell>
                  <TableCell>{{ ingredient.base_unit }}</TableCell>
                  <TableCell>{{ formatCurrency(ingredient.cost_per_unit) }}</TableCell>
                  <TableCell>
                    <span :class="ingredient.is_low_stock ? 'text-orange-600 font-semibold' : ''">
                      {{ formatNumber(ingredient.current_stock) }}
                    </span>
                  </TableCell>
                  <TableCell>{{ formatNumber(ingredient.reorder_level) }}</TableCell>
                  <TableCell>{{ ingredient.packages }}</TableCell>
                  <TableCell>{{ formatCurrency(ingredient.current_stock * ingredient.cost_per_unit) }}</TableCell>
                  <TableCell>
                    <Badge v-if="ingredient.is_low_stock" variant="destructive">Low Stock</Badge>
                    <Badge v-else variant="default">In Stock</Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        @click="editIngredient(ingredient.ingredient_id)"
                      >
                        <Pencil class="w-4 h-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        @click="confirmDelete(ingredient.ingredient_id)"
                      >
                        <Trash2 class="w-4 h-4 text-red-600" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div
      v-if="showDeleteDialog"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click="cancelDelete"
    >
      <Card class="w-full max-w-md" @click.stop>
        <CardHeader>
          <CardTitle>Confirm Delete</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <p>Are you sure you want to delete this ingredient? This action cannot be undone.</p>
          <div class="flex justify-end space-x-2">
            <Button variant="outline" @click="cancelDelete">Cancel</Button>
            <Button variant="destructive" @click="deleteIngredient">Delete</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
