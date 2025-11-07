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


interface DishVariant {
  size_name: string;
  price_modifier: number | string;
  quantity_multiplier: number | string;
  is_default: boolean;
}

const form = useForm({
  dish_name: '',
  description: '',
  category_id: '',
  image_url: '',
  price: '',
  ingredients: [] as DishIngredient[],
  has_variants: false,
  variants: [] as DishVariant[],
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

// Get compatible units for a given ingredient
const getCompatibleUnits = (ingredient: Ingredient | undefined): string[] => {
  if (!ingredient || !ingredient.base_unit) {
    return allUnits.value;
  }

  const baseUnit = ingredient.base_unit.toLowerCase();

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
      credentials: 'same-origin', // IMPORTANT: Send cookies with request (needed for CSRF on Railway)
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

// Watch for has_variants changes
watch(() => form.has_variants, (newValue) => {
  console.log('has_variants changed to:', newValue);
});

const addVariant = () => {
  console.log('Adding variant, has_variants is:', form.has_variants);
  form.variants.push({
    size_name: '',
    price_modifier: '',
    quantity_multiplier: 1.0,
    is_default: form.variants.length === 0, // First variant is default
  });
  console.log('Variants array now:', form.variants);
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

const submit = () => {
  console.log('Form data being submitted:', {
    has_variants: form.has_variants,
    variants: form.variants,
    variants_count: form.variants.length
  });

  form.post('/menu', {
    onSuccess: () => {
      // Handled by redirect
    },
    onError: (errors) => {
      // Form errors will be displayed
      console.log('Form errors:', errors);
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
              </div>

              <div v-if="!form.ingredients || form.ingredients.length === 0" class="text-sm text-muted-foreground text-center py-8">
                No ingredients selected yet
              </div>
              <div v-else class="space-y-3">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-[30%]">Ingredient</TableHead>
                      <TableHead class="w-[15%]">Quantity</TableHead>
                      <TableHead class="w-[10%]">Unit</TableHead>
                      <TableHead class="w-[10%]">Optional</TableHead>
                      <TableHead class="w-[25%]">Notes</TableHead>
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
                        <Checkbox
                          v-model:checked="ingredient.is_optional"
                          class="mx-2"
                        />
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
                    <TableHead class="w-[25%]">Size</TableHead>
                    <TableHead class="w-[20%]">Price</TableHead>
                    <TableHead class="w-[20%]">Quantity Multiplier</TableHead>
                    <TableHead class="w-[15%]">Default</TableHead>
                    <TableHead class="w-[20%]">Actions</TableHead>
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

        <!-- Bottom Section: Pricing and Add Button -->
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

          <Button type="submit" size="lg" :disabled="form.processing">
            <Clock v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Adding...' : 'Add Dish' }}
          </Button>
        </div>


      </form>
    </div>
  </AppLayout>
</template>