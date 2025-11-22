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
import { ref, computed, watch, onMounted } from 'vue';
import { Plus, Trash2, Clock, Users, Edit, Package, X, ChevronDown, Search, Save, Eye } from 'lucide-vue-next';
import ImageUpload from '@/components/ImageUpload.vue';

// Ensure window is typed correctly for TypeScript
declare const window: Window & typeof globalThis;

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

interface DishIngredient {
  unique_key: string;
  ingredient_id?: number;
  ingredient_name: string;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note: string;
  [key: string]: any; // Index signature for FormDataConvertible compatibility
}

interface DishVariant {
  variant_id?: number;
  size_name: string;
  price_modifier: number | string;
  quantity_multiplier: number | string;
  is_default: boolean;
  [key: string]: any; // Index signature for FormDataConvertible compatibility
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  category_id: number;
  image_url?: string;
  price: number;
  dish_ingredients?: any[];
  variants?: DishVariant[];
}

interface Props {
  dish: Dish;
  categories: MenuCategory[];
  ingredients: Ingredient[];
  availableUnits: AvailableUnits;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Management', href: '/menu' },
  { title: props.dish.dish_name, href: `/menu/${props.dish.dish_id}` },
  { title: 'Edit', href: `/menu/${props.dish.dish_id}/edit` },
];

const form = useForm({
  dish_name: props.dish.dish_name,
  description: props.dish.description || '',
  category_id: props.dish.category_id.toString(),
  image_url: props.dish.image_url || '',
  price: props.dish.price || '',
  ingredients: [] as DishIngredient[],
  has_variants: (props.dish.variants && props.dish.variants.length > 0) || false,
  variants: [] as DishVariant[],
});

// Initialize ingredients and variants from existing dish
onMounted(() => {
  if (props.dish.dish_ingredients) {
    form.ingredients = props.dish.dish_ingredients.map((dishIngredient, index) => ({
      unique_key: `${dishIngredient.ingredient_id}_${Date.now()}_${index}`,
      ingredient_id: dishIngredient.ingredient_id,
      ingredient_name: dishIngredient.ingredient?.ingredient_name || '',
      quantity: parseFloat(dishIngredient.quantity_needed) || 1,
      unit: dishIngredient.unit_of_measure || dishIngredient.ingredient?.base_unit || 'g',
      is_optional: dishIngredient.is_optional || false,
      preparation_note: dishIngredient.preparation_note || '',
    }));
  }

  if (props.dish.variants && props.dish.variants.length > 0) {
    form.variants = props.dish.variants.map(variant => ({
      variant_id: variant.variant_id,
      size_name: variant.size_name,
      price_modifier: variant.price_modifier,
      quantity_multiplier: variant.quantity_multiplier,
      is_default: variant.is_default,
    }));
  }
});

const removeIngredient = (index: number) => {
  if (!Array.isArray(form.ingredients)) return;
  form.ingredients.splice(index, 1);
};

const imageUploadRef = ref();
const selectedIngredientId = ref('');
const ingredientSearchTerm = ref('');
const comboboxValue = ref<Ingredient | undefined>(undefined);

// Get all available units in a flat array for easy use
const allUnits = computed(() => [
  ...props.availableUnits.weight,
  ...props.availableUnits.volume,
  ...props.availableUnits.count
]);

// Unit conversion utility functions (same as Create.vue)
const convertQuantity = (quantity: number, fromUnit: string, toUnit: string): number => {
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
const getCompatibleUnits = (ingredient: Ingredient | undefined): string[] => {
  if (!ingredient || !ingredient.base_unit) {
    return allUnits.value;
  }

  const baseUnit = ingredient.base_unit.toLowerCase();

  // For kg ingredients, only show kg to simplify UX
  // Backend will handle g/kg conversions automatically
  if (baseUnit === 'kg') {
    return ['kg'];
  }

  // For count units (pcs, item, unit), only show the same unit
  // to avoid confusion between pieces, items, and units
  if (baseUnit === 'pcs' || baseUnit === 'item' || baseUnit === 'unit') {
    return [baseUnit];
  }

  // For other weight units, show all weight options
  if (props.availableUnits.weight.includes(baseUnit)) {
    return props.availableUnits.weight;
  } else if (props.availableUnits.volume.includes(baseUnit)) {
    return props.availableUnits.volume;
  } else if (props.availableUnits.count.includes(baseUnit)) {
    return props.availableUnits.count;
  }

  // Default to all units if we can't determine
  return allUnits.value;
};

// Get units by ingredient ID - optimized for rendering
const getUnitsByIngredientId = (ingredientId: number | undefined): string[] => {
  if (!ingredientId) return allUnits.value;
  const ingredient = props.ingredients.find(i => i.ingredient_id === ingredientId);
  return getCompatibleUnits(ingredient);
};

// Watch for ingredient selection
watch(selectedIngredientId, (newId) => {
  if (newId) {
    const ingredient = props.ingredients.find(i => i.ingredient_id.toString() === newId);
    if (ingredient && ingredient.base_unit) {
      // Check if ingredient already exists
      const exists = form.ingredients.some(i => i.ingredient_id === ingredient.ingredient_id);
      if (exists) {
        alert('This ingredient is already added to the dish');
        selectedIngredientId.value = '';
        return;
      }

      const newIngredient = {
        unique_key: `${ingredient.ingredient_id}_${Date.now()}_${Math.random()}`,
        ingredient_id: ingredient.ingredient_id,
        ingredient_name: ingredient.ingredient_name,
        quantity: 1,
        unit: ingredient.base_unit,
        is_optional: false,
        preparation_note: '',
      };
      form.ingredients = [...form.ingredients, newIngredient];
    }
    // Reset with nextTick to avoid race condition
    setTimeout(() => {
      selectedIngredientId.value = '';
    }, 0);
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

  let response: Response | undefined;

  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'dish');

    response = await fetch('/api/images/upload', {
      method: 'POST',
      credentials: 'same-origin', // IMPORTANT: Send cookies with request (needed for CSRF on Railway)
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData
    });

    console.log('Response status:', response.status);
    console.log('Response headers:', response.headers.get('content-type'));

    let result;
    try {
      result = await response.json();
      console.log('Upload response:', result);
    } catch (jsonError) {
      console.error('Failed to parse JSON response:', jsonError);
      const textResponse = await response.text();
      console.error('Response text:', textResponse);
      throw new Error(`Server returned non-JSON response: ${response.status} - ${textResponse.substring(0, 200)}`);
    }

    if (!response.ok) {
      // Handle subscription-related errors (403 Forbidden)
      if (response.status === 403 && result.redirect) {
        alert(result.message);
        window.location.href = result.redirect;
        return;
      }
      throw new Error(`HTTP error! status: ${response.status} - ${result.message || 'Unknown error'}`);
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
    console.error('Error details:', {
      message: error instanceof Error ? error.message : String(error),
      stack: error instanceof Error ? error.stack : undefined,
      responseStatus: response?.status,
      url: '/api/images/upload'
    });
    const errorMessage = (error instanceof Error) ? error.message : String(error);
    handleImageError(`Failed to upload image: ${errorMessage}`);
  } finally {
    // Reset uploading state
    imageUploadRef.value?.resetUploadingState?.();
  }
};

const handleImageError = (message: string) => {
  console.error('Image error:', message);
};

const addVariant = () => {
  form.variants.push({
    size_name: '',
    price_modifier: '',
    quantity_multiplier: 1.0,
    is_default: form.variants.length === 0, // First variant is default
  });
};

const removeVariant = (index: number) => {
  form.variants.splice(index, 1);
  // If we removed the default, make the first one default
  if (form.variants.length > 0 && !form.variants.some(v => v.is_default)) {
    form.variants[0].is_default = true;
  }
};

const setDefaultVariant = (index: number) => {
  form.variants.forEach((v, i) => {
    v.is_default = i === index;
  });
};

// Variant ingredient preview
const showVariantPreview = ref(false);
const selectedVariantIndex = ref<number | null>(null);

const previewVariantIngredients = computed(() => {
  if (selectedVariantIndex.value === null || !form.variants[selectedVariantIndex.value]) {
    return [];
  }

  const variant = form.variants[selectedVariantIndex.value];
  const multiplier = parseFloat(variant.quantity_multiplier?.toString() || '1');

  return form.ingredients.map(ingredient => ({
    name: ingredient.ingredient_name,
    baseQuantity: ingredient.quantity,
    variantQuantity: (ingredient.quantity * multiplier).toFixed(2),
    unit: ingredient.unit,
  }));
});

const openVariantPreview = (index: number) => {
  selectedVariantIndex.value = index;
  showVariantPreview.value = true;
};

const closeVariantPreview = () => {
  showVariantPreview.value = false;
  selectedVariantIndex.value = null;
};

const submit = () => {
  form.put(`/menu/${props.dish.dish_id}`, {
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
  <Head :title="`Edit ${dish.dish_name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Dish</h1>
        <p class="text-gray-600 dark:text-gray-400">Update dish information</p>
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

              <div v-if="!form.ingredients || form.ingredients.length === 0" class="text-sm text-muted-foreground text-center py-8">
                No ingredients selected yet
              </div>
              <div v-else class="space-y-3">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-[50%]">Ingredient</TableHead>
                      <TableHead class="w-[20%]">Quantity</TableHead>
                      <TableHead class="w-[20%]">Unit</TableHead>
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
                          <SelectContent :side-offset="5" :disable-portal="true">
                            <SelectItem
                              v-for="unit in getUnitsByIngredientId(ingredient.ingredient_id)"
                              :key="`${ingredient.unique_key}-${unit}`"
                              :value="unit"
                            >
                              {{ unit }}
                            </SelectItem>
                          </SelectContent>
                        </Select>
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

              <!-- Error message for ingredients -->
              <p v-if="form.errors.ingredients" class="text-sm text-red-500 mt-2">
                {{ form.errors.ingredients }}
              </p>
            </div>
          </div>
        </div>

        <!-- Variants Section -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Size Variants (Optional)</CardTitle>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="has_variants"
                  v-model="form.has_variants"
                  class="h-4 w-4 rounded border-gray-300"
                  @change="() => console.log('Checkbox changed to:', form.has_variants)"
                />
                <Label for="has_variants" class="cursor-pointer">Enable multiple sizes</Label>
              </div>
            </div>
          </CardHeader>
          <CardContent v-if="form.has_variants" class="space-y-4">
            <p class="text-sm text-muted-foreground">
              Define different sizes for this dish. The base price and ingredients are defined above.
              Here you set the multipliers for each size.
            </p>

            <div v-if="form.variants.length === 0" class="text-center py-8 border-2 border-dashed rounded-lg">
              <p class="text-sm text-muted-foreground mb-4">No size variants added yet</p>
              <Button @click="addVariant" variant="outline">
                <Plus class="w-4 h-4 mr-2" />
                Add First Size Variant
              </Button>
            </div>

            <div v-else class="space-y-3">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-[20%]">Size Name</TableHead>
                    <TableHead class="w-[15%]">Price</TableHead>
                    <TableHead class="w-[15%]">Quantity Multiplier</TableHead>
                    <TableHead class="w-[10%]">Default</TableHead>
                    <TableHead class="w-[15%]">Preview</TableHead>
                    <TableHead class="w-[15%]">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(variant, index) in form.variants" :key="index">
                    <TableCell>
                      <Input
                        v-model="variant.size_name"
                        placeholder="e.g., Small, Medium, Large"
                        class="w-full"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="variant.price_modifier"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full"
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        v-model.number="variant.quantity_multiplier"
                        type="number"
                        step="0.1"
                        min="0.1"
                        max="10"
                        placeholder="1.0"
                        class="w-full"
                      />
                      <p class="text-xs text-muted-foreground mt-1">
                        {{ variant.quantity_multiplier }}x ingredients
                      </p>
                    </TableCell>
                    <TableCell>
                      <input
                        type="checkbox"
                        :checked="variant.is_default"
                        @change="setDefaultVariant(index)"
                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                      />
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="openVariantPreview(index)"
                        class="flex items-center gap-1"
                      >
                        <Eye class="w-3 h-3" />
                        Show
                      </Button>
                    </TableCell>
                    <TableCell>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeVariant(index)"
                        class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>

              <Button @click="addVariant" type="button" variant="outline" size="sm">
                <Plus class="w-4 h-4 mr-2" />
                Add Another Size
              </Button>
            </div>

            <!-- Error message for variants -->
            <p v-if="form.errors.variants" class="text-sm text-red-500 mt-2">
              {{ form.errors.variants }}
            </p>
          </CardContent>
        </Card>

        <!-- Bottom Section: Pricing and Update Button -->
        <div class="flex items-end justify-between">
          <div class="space-y-2">
            <Label for="pricing">{{ form.has_variants ? 'Base Price (for reference)' : 'Pricing' }}</Label>
            <Input
              id="pricing"
              v-model="form.price"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-48"
              :class="{ 'border-red-500': form.errors.price }"
            />
            <p v-if="form.has_variants" class="text-xs text-muted-foreground">
              Variant prices will be used instead
            </p>
            <p v-if="form.errors.price" class="text-sm text-red-500">
              {{ form.errors.price }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <Button type="button" variant="outline" @click="$inertia.visit(`/menu/${dish.dish_id}`)">
              Cancel
            </Button>
            <Button type="submit" size="lg" :disabled="form.processing">
              <Save v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
              {{ form.processing ? 'Updating...' : 'Update Dish' }}
            </Button>
          </div>
        </div>
      </form>
    </div>

    <!-- Variant Ingredient Preview Modal -->
    <Dialog v-model:open="showVariantPreview">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>
            Ingredient Preview: {{ selectedVariantIndex !== null ? form.variants[selectedVariantIndex]?.size_name : '' }}
          </DialogTitle>
        </DialogHeader>

        <div class="space-y-4">
          <div v-if="selectedVariantIndex !== null" class="text-sm text-muted-foreground">
            <p>Multiplier: <span class="font-semibold">{{ form.variants[selectedVariantIndex]?.quantity_multiplier }}x</span></p>
            <p class="mt-1">This shows the exact ingredients that will be deducted from inventory when this size is ordered.</p>
          </div>

          <div v-if="previewVariantIngredients.length === 0" class="text-center py-8 text-muted-foreground">
            No ingredients added yet. Add ingredients to the dish first.
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[40%]">Ingredient</TableHead>
                <TableHead class="w-[25%]">Base Recipe</TableHead>
                <TableHead class="w-[25%]">This Variant</TableHead>
                <TableHead class="w-[10%]">Unit</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(ingredient, idx) in previewVariantIngredients" :key="idx">
                <TableCell class="font-medium">{{ ingredient.name }}</TableCell>
                <TableCell class="text-muted-foreground">{{ ingredient.baseQuantity }}</TableCell>
                <TableCell class="font-semibold text-green-600">{{ ingredient.variantQuantity }}</TableCell>
                <TableCell>{{ ingredient.unit }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div class="flex justify-end gap-2 pt-4">
            <Button type="button" variant="outline" @click="closeVariantPreview">
              Close
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>