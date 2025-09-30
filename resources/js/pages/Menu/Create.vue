<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import Badge from '@/components/ui/badge/Badge.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxList,
  ComboboxItem,
  ComboboxEmpty,
  ComboboxTrigger
} from '@/components/ui/combobox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import { Plus, Trash2, Clock, Users, Edit, Package, X, ChevronDown, Search } from 'lucide-vue-next';
import ImageUpload from '@/components/ImageUpload.vue';

interface MenuCategory {
  category_id: number;
  category_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  cost_per_unit: number;
  current_stock: number;
}

interface AvailableUnits {
  weight: string[];
  volume: string[];
  count: string[];
}

interface Props {
  categories: MenuCategory[];
  ingredients: Ingredient[];
  availableUnits: AvailableUnits;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: 'Create Dish', href: '/menu/create' },
];

interface DishIngredient {
  [key: string]: any;
  unique_key: string;
  ingredient_id?: number;
  ingredient_name: string;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note: string;
}


const form = useForm({
  dish_name: '',
  description: '',
  category_id: '',
  image_url: '',
  price: '',
  ingredients: [] as DishIngredient[],
});





const removeIngredient = (index: number) => {
  if (!Array.isArray(form.ingredients)) return;
  form.ingredients.splice(index, 1);
};



const imageUploadRef = ref();
const selectedIngredientId = ref('');
const ingredientSearchTerm = ref('');
const comboboxValue = ref<Ingredient | undefined>();

// Get all available units in a flat array for easy use
const allUnits = computed(() => [
  ...props.availableUnits.weight,
  ...props.availableUnits.volume,
  ...props.availableUnits.count
]);

// Unit conversion utility functions
const convertQuantity = (quantity: number, fromUnit: string, toUnit: string): number => {
  // Simple conversion logic - in a real app you might want to make an API call
  // or implement the full conversion logic client-side

  // Weight conversions (to grams)
  const weightConversions: Record<string, number> = {
    'g': 1, 'gram': 1, 'grams': 1,
    'kg': 1000, 'kilogram': 1000, 'kilograms': 1000,
    'lb': 453.592, 'pound': 453.592, 'pounds': 453.592,
    'oz': 28.3495, 'ounce': 28.3495, 'ounces': 28.3495,
  };

  // Volume conversions (to ml)
  const volumeConversions: Record<string, number> = {
    'ml': 1, 'milliliter': 1, 'milliliters': 1,
    'l': 1000, 'liter': 1000, 'liters': 1000,
    'cup': 236.588, 'cups': 236.588,
    'tbsp': 14.7868, 'tablespoon': 14.7868, 'tablespoons': 14.7868,
    'tsp': 4.92892, 'teaspoon': 4.92892, 'teaspoons': 4.92892,
  };

  // Count units (no conversion)
  const countConversions: Record<string, number> = {
    'pcs': 1, 'piece': 1, 'pieces': 1,
    'item': 1, 'items': 1,
    'unit': 1, 'units': 1,
  };

  if (fromUnit === toUnit) return quantity;

  let conversions: Record<string, number> | null = null;

  if (weightConversions[fromUnit] && weightConversions[toUnit]) {
    conversions = weightConversions;
  } else if (volumeConversions[fromUnit] && volumeConversions[toUnit]) {
    conversions = volumeConversions;
  } else if (countConversions[fromUnit] && countConversions[toUnit]) {
    conversions = countConversions;
  }

  if (!conversions) return quantity; // Can't convert

  const baseQuantity = quantity * conversions[fromUnit];
  return baseQuantity / conversions[toUnit];
};

// Get compatible units for a given ingredient
const getCompatibleUnits = (ingredient: Ingredient): string[] => {
  const baseUnit = ingredient.base_unit.toLowerCase();

  if (props.availableUnits.weight.includes(baseUnit)) {
    return props.availableUnits.weight;
  } else if (props.availableUnits.volume.includes(baseUnit)) {
    return props.availableUnits.volume;
  } else if (props.availableUnits.count.includes(baseUnit)) {
    return props.availableUnits.count;
  }

  // Default to weight units if we can't determine
  return props.availableUnits.weight;
};

// Watch for ingredient selection
watch(selectedIngredientId, (newId) => {
  if (newId) {
    const ingredient = props.ingredients.find(i => i.ingredient_id.toString() === newId);
    if (ingredient) {
      form.ingredients.push({
        unique_key: `${ingredient.ingredient_id}_${Date.now()}_${Math.random()}`,
        ingredient_id: ingredient.ingredient_id,
        ingredient_name: ingredient.ingredient_name,
        quantity: 1,
        unit: ingredient.base_unit,
        is_optional: false,
        preparation_note: '',
      });
    }
    selectedIngredientId.value = ''; // Reset selection
  }
});

// Filtered ingredients based on search
const filteredIngredients = computed(() => {
  if (!ingredientSearchTerm.value) {
    return props.ingredients || [];
  }
  return (props.ingredients || []).filter(ingredient =>
    ingredient.ingredient_name.toLowerCase().includes(ingredientSearchTerm.value.toLowerCase())
  );
});

// Calculate total stock requirements per ingredient
const stockRequirements = computed(() => {
  const requirements: Record<number, { total_needed: number; current_stock: number; ingredient_name: string; base_unit: string }> = {};

  form.ingredients.forEach(dishIngredient => {
    if (!dishIngredient.ingredient_id || dishIngredient.is_optional) return;

    const ingredientId = dishIngredient.ingredient_id;
    const ingredient = props.ingredients.find(i => i.ingredient_id === ingredientId);

    if (ingredient) {
      if (!requirements[ingredientId]) {
        requirements[ingredientId] = {
          total_needed: 0,
          current_stock: ingredient.current_stock,
          ingredient_name: ingredient.ingredient_name,
          base_unit: ingredient.base_unit
        };
      }
      // Convert dish ingredient quantity to ingredient's base unit for comparison
      const quantityInBaseUnit = convertQuantity(dishIngredient.quantity, dishIngredient.unit, ingredient.base_unit);
      requirements[ingredientId].total_needed += quantityInBaseUnit;
    }
  });

  return requirements;
});

// Check if individual ingredient has sufficient stock
const getIngredientStockStatus = (dishIngredient: DishIngredient) => {
  if (!dishIngredient.ingredient_id || dishIngredient.is_optional) {
    return { sufficient: true, available: 0, needed: 0, availableInDisplayUnit: 0, neededInDisplayUnit: dishIngredient.quantity };
  }

  const ingredient = props.ingredients.find(i => i.ingredient_id === dishIngredient.ingredient_id);
  if (!ingredient) return {
    sufficient: false,
    available: 0,
    needed: dishIngredient.quantity,
    availableInDisplayUnit: 0,
    neededInDisplayUnit: dishIngredient.quantity
  };

  const requirements = stockRequirements.value[dishIngredient.ingredient_id];
  const totalNeededInBaseUnit = requirements?.total_needed || convertQuantity(dishIngredient.quantity, dishIngredient.unit, ingredient.base_unit);

  // Convert available stock to display unit
  const availableInDisplayUnit = convertQuantity(ingredient.current_stock, ingredient.base_unit, dishIngredient.unit);

  // The needed amount is just the dish ingredient quantity in its own unit
  const neededInDisplayUnit = dishIngredient.quantity;

  return {
    sufficient: ingredient.current_stock >= totalNeededInBaseUnit,
    available: ingredient.current_stock,
    needed: totalNeededInBaseUnit,
    availableInDisplayUnit: availableInDisplayUnit,
    neededInDisplayUnit: neededInDisplayUnit
  };
};

// Check if the entire dish can be produced
const canProduceDish = computed(() => {
  return Object.values(stockRequirements.value).every(req =>
    req.current_stock >= req.total_needed
  );
});

// Get overall stock status message
const stockStatusMessage = computed(() => {
  const insufficient = Object.values(stockRequirements.value).filter(req =>
    req.current_stock < req.total_needed
  );

  if (insufficient.length === 0) {
    return { type: 'success', message: 'All ingredients are available in stock!' };
  } else {
    return {
      type: 'warning',
      message: `${insufficient.length} ingredient(s) have insufficient stock`
    };
  }
});

// Function to add ingredient by ID
const addIngredientById = (ingredientId: string) => {
  const ingredient = props.ingredients.find(i => i.ingredient_id.toString() === ingredientId);
  if (ingredient) {
    form.ingredients.push({
      unique_key: `${ingredient.ingredient_id}_${Date.now()}_${Math.random()}`,
      ingredient_id: ingredient.ingredient_id,
      ingredient_name: ingredient.ingredient_name,
      quantity: 1,
      unit: ingredient.base_unit,
      is_optional: false,
      preparation_note: '',
    });
  }
  // Reset search
  ingredientSearchTerm.value = '';
  selectedIngredientId.value = '';
};

// Handle combobox selection
const handleComboboxSelect = (value: any) => {
  if (value && typeof value === 'object' && 'ingredient_id' in value) {
    addIngredientById(value.ingredient_id.toString());
    comboboxValue.value = undefined;
    ingredientSearchTerm.value = '';
  }
};

const handleImageUpload = async (file: File) => {
  console.log('Starting upload for file:', file.name);
  
  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'dish');

    const response = await fetch('/api/images/upload', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData
    });

    const result = await response.json();
    console.log('Upload response:', result);

    if (!response.ok) {
      // Handle subscription-related errors (403 Forbidden)
      if (response.status === 403 && result.redirect) {
        alert(result.message);
        window.location.href = result.redirect;
        return;
      }
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    if (result.success) {
      // Update the form with the server URL
      console.log('Old URL:', form.image_url);
      form.image_url = result.url;
      console.log('New URL:', result.url);
      console.log('Form image_url after update:', form.image_url);
    } else {
      console.error('Upload failed:', result.message);
      handleImageError(result.message || 'Upload failed');
    }
  } catch (error) {
    console.error('Upload error:', error);
    handleImageError('Failed to upload image. Please try again.');
  } finally {
    // Reset uploading state
    imageUploadRef.value?.resetUploadingState?.();
  }
};

const handleImageError = (message: string) => {
  console.error('Image error:', message);
};

const submit = () => {
  form.post('/menu', {
    onSuccess: () => {
      // Handled by redirect
    },
    onError: () => {
      // Form errors will be displayed
    }
  });
};
</script>

<template>
  <Head title="Create New Dish" />

  <AppLayout :breadcrumbs="breadcrumbs">
     <div class="mx-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create New Dish</h1>
        <p class="text-muted-foreground">Add a new dish to your menu</p>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Top Section: Dish Details and Image -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Left: Dish Details -->
          <Card>
            <CardHeader>
              <CardTitle>Dish Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="space-y-2">
                <Label for="dish_name">Dish name</Label>
                <Input
                  id="dish_name"
                  v-model="form.dish_name"
                  placeholder="Enter dish name"
                  :class="{ 'border-red-500': form.errors.dish_name }"
                />
                <p v-if="form.errors.dish_name" class="text-sm text-red-500">
                  {{ form.errors.dish_name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="category_id">Category</Label>
                <Select v-model="form.category_id">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.category_id }">
                    <SelectValue placeholder="Drop down" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="category in (categories || []).filter(c => c.category_id && c.category_id > 0)"
                      :key="category.category_id"
                      :value="category.category_id.toString()"
                    >
                      {{ category.category_name }}
                    </SelectItem>
                    <SelectItem v-if="!(categories || []).length" value="no-categories" disabled>
                      No categories available
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.category_id" class="text-sm text-red-500">
                  {{ form.errors.category_id }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Describe the dish..."
                  class="min-h-[120px]"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Right: Dish Image -->
          <Card>
            <CardHeader>
              <CardTitle>Dish Image</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="h-[300px] border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
                <ImageUpload
                  ref="imageUploadRef"
                  v-model="form.image_url"
                  @upload="handleImageUpload"
                  @error="handleImageError"
                  :disabled="form.processing"
                />
              </div>
            </CardContent>
          </Card>
        </div>


        <!-- Ingredients Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Left: Ingredient Dropdown -->
          <div class="space-y-4">
            <Combobox v-model="comboboxValue" by="ingredient_name" @update:model-value="handleComboboxSelect">
              <ComboboxAnchor as-child>
                <ComboboxTrigger as-child>
                  <Button variant="outline" class="w-full justify-between">
                    {{ comboboxValue?.ingredient_name ?? 'Drop down - Type to search ingredients...' }}
                    <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                  </Button>
                </ComboboxTrigger>
              </ComboboxAnchor>

              <ComboboxList class="w-[--reka-combobox-trigger-width] max-h-[200px]">
                <div class="relative w-full max-w-sm items-center">
                  <ComboboxInput
                    v-model:search-term="ingredientSearchTerm"
                    class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10"
                    placeholder="Search ingredients..."
                  />
                  <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                    <Search class="size-4 text-muted-foreground" />
                  </span>
                </div>

                <ComboboxEmpty>No ingredients found</ComboboxEmpty>

                <ComboboxItem
                  v-for="ingredient in filteredIngredients"
                  :key="ingredient.ingredient_id"
                  :value="ingredient"
                  class="cursor-pointer"
                >
                  {{ ingredient.ingredient_name }}
                </ComboboxItem>
              </ComboboxList>
            </Combobox>
          </div>

          <!-- Right: Selected Ingredients List -->
          <div class="space-y-4">
            <div class="border rounded-lg p-4 min-h-[200px]">
              <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium">Recipe Ingredients</p>
                <div v-if="form.ingredients.length > 0" class="flex items-center gap-2">
                  <Badge
                    :variant="canProduceDish ? 'default' : 'destructive'"
                    class="text-xs"
                  >
                    {{ canProduceDish ? '✓ Can Produce' : '⚠ Insufficient Stock' }}
                  </Badge>
                </div>
              </div>

              <!-- Stock Status Alert -->
              <div v-if="form.ingredients.length > 0" class="mb-4">
                <div
                  :class="[
                    'p-3 rounded-lg text-sm',
                    stockStatusMessage.type === 'success'
                      ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800'
                      : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800'
                  ]"
                >
                  {{ stockStatusMessage.message }}
                </div>
              </div>

              <div v-if="!form.ingredients || form.ingredients.length === 0" class="text-sm text-muted-foreground text-center py-8">
                No ingredients selected yet
              </div>
              <div v-else class="space-y-3">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-[25%]">Ingredient</TableHead>
                      <TableHead class="w-[12%]">Quantity</TableHead>
                      <TableHead class="w-[8%]">Unit</TableHead>
                      <TableHead class="w-[8%]">Optional</TableHead>
                      <TableHead class="w-[20%]">Stock Status</TableHead>
                      <TableHead class="w-[17%]">Notes</TableHead>
                      <TableHead class="w-[10%]">Action</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow
                      v-for="(ingredient, index) in form.ingredients"
                      :key="ingredient.unique_key"
                    >
                      <TableCell class="font-medium">
                        {{ ingredient.ingredient_name }}
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model.number="ingredient.quantity"
                          type="number"
                          step="0.01"
                          min="0.01"
                          class="w-20"
                        />
                      </TableCell>
                      <TableCell>
                        <Select v-model="ingredient.unit">
                          <SelectTrigger class="w-20">
                            <SelectValue :placeholder="ingredient.unit || 'Unit'" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem
                              v-for="unit in ingredient.ingredient_id ? getCompatibleUnits(props.ingredients.find(i => i.ingredient_id === ingredient.ingredient_id)!) : allUnits"
                              :key="unit"
                              :value="unit"
                            >
                              {{ unit }}
                            </SelectItem>
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell>
                        <Checkbox
                          v-model:checked="ingredient.is_optional"
                          class="mx-2"
                        />
                      </TableCell>
                      <TableCell>
                        <div v-if="ingredient.ingredient_id" class="text-xs space-y-1">
                          <div class="flex items-center gap-1">
                            <Badge
                              :variant="getIngredientStockStatus(ingredient).sufficient ? 'default' : 'destructive'"
                              class="text-xs px-1"
                            >
                              {{ getIngredientStockStatus(ingredient).sufficient ? '✓' : '⚠' }}
                            </Badge>
                            <span :class="getIngredientStockStatus(ingredient).sufficient ? 'text-green-600' : 'text-red-600'">
                              {{ Math.round(getIngredientStockStatus(ingredient).availableInDisplayUnit * 100) / 100 }} {{ ingredient.unit }}
                            </span>
                          </div>
                          <div class="text-muted-foreground">
                            Need: {{ getIngredientStockStatus(ingredient).neededInDisplayUnit }} {{ ingredient.unit }}
                          </div>
                        </div>
                        <div v-else class="text-xs text-muted-foreground">
                          No stock info
                        </div>
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model="ingredient.preparation_note"
                          placeholder="e.g., diced, chopped..."
                          class="w-full text-xs"
                        />
                      </TableCell>
                      <TableCell>
                        <Button
                          variant="ghost"
                          size="sm"
                          @click="removeIngredient(index)"
                          class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                        >
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Section: Pricing and Add Button -->
        <div class="flex items-end justify-between">
          <div class="space-y-2">
            <Label for="pricing">Pricing</Label>
            <Input
              id="pricing"
              v-model="form.price"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-48"
            />
          </div>

          <Button type="submit" size="lg" :disabled="form.processing">
            <Clock v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Adding...' : 'Add Dish' }}
          </Button>
        </div>


      </form>
    </div>
  </AppLayout>
</template>