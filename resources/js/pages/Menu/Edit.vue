<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch, onMounted } from 'vue';
import { Plus, Trash2, Clock, Save, X } from 'lucide-vue-next';
import ImageUpload from '@/components/ImageUpload.vue';

interface MenuCategory {
  category_id: number;
  category_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  unit_of_measure: string;
  cost_per_unit: number;
  current_stock: number;
}

interface DishIngredient {
  [key: string]: any;
  ingredient_id?: number;
  ingredient_name: string;
  quantity: number;
  unit: string;
  is_optional: boolean;
  preparation_note: string;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  category_id: number;
  image_url?: string;
  price: number;
  ingredients?: any[];
}

interface Props {
  dish: Dish;
  categories: MenuCategory[];
  ingredients: Ingredient[];
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
});

// Initialize ingredients from existing dish
onMounted(() => {
  if (props.dish.ingredients) {
    form.ingredients = props.dish.ingredients.map(ingredient => ({
      ingredient_id: ingredient.ingredient_id,
      ingredient_name: ingredient.ingredient_name || ingredient.ingredient?.ingredient_name || '',
      quantity: ingredient.quantity_needed || ingredient.quantity || 1,
      unit: ingredient.unit_of_measure || ingredient.unit || '',
      is_optional: ingredient.is_optional || false,
      preparation_note: ingredient.preparation_note || '',
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

// Watch for ingredient selection
watch(selectedIngredientId, (newId) => {
  if (newId) {
    const ingredient = props.ingredients.find(i => i.ingredient_id.toString() === newId);
    if (ingredient && !form.ingredients.some(i => i.ingredient_name === ingredient.ingredient_name)) {
      form.ingredients.push({
        ingredient_id: ingredient.ingredient_id,
        ingredient_name: ingredient.ingredient_name,
        quantity: 1,
        unit: ingredient.unit_of_measure,
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
  if (ingredient && !form.ingredients.some(i => i.ingredient_name === ingredient.ingredient_name)) {
    form.ingredients.push({
      ingredient_id: ingredient.ingredient_id,
      ingredient_name: ingredient.ingredient_name,
      quantity: 1,
      unit: ingredient.unit_of_measure,
      is_optional: false,
      preparation_note: '',
    });
  }
  // Reset search
  ingredientSearchTerm.value = '';
  selectedIngredientId.value = '';
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
    <div class="max-w-6xl mx-auto space-y-8">
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
            <div class="relative">
              <Input
                v-model="ingredientSearchTerm"
                placeholder="Drop down - Type to search ingredients..."
                class="cursor-pointer"
                @focus="ingredientSearchTerm = ''"
              />

              <!-- Dropdown Results -->
              <div
                v-if="ingredientSearchTerm || filteredIngredients.length > 0"
                class="absolute z-50 w-full mt-1 bg-background border rounded-lg shadow-lg overflow-hidden"
                style="max-height: 200px;"
              >
                <div class="overflow-y-auto" style="max-height: 200px;">
                  <div
                    v-for="ingredient in filteredIngredients"
                    :key="ingredient.ingredient_id"
                    @click="addIngredientById(ingredient.ingredient_id.toString())"
                    class="p-3 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0 min-h-[48px] flex items-center"
                  >
                    <div class="font-medium">{{ ingredient.ingredient_name }}</div>
                  </div>
                  <div v-if="filteredIngredients.length === 0 && ingredientSearchTerm" class="p-3 text-sm text-gray-600 dark:text-gray-400 min-h-[48px] flex items-center">
                    No ingredients found
                  </div>
                </div>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              But user can also auto search filter when he type the ingredients
            </p>
          </div>

          <!-- Right: Selected Ingredients List -->
          <div class="space-y-4">
            <div class="border rounded-lg p-4 min-h-[200px]">
              <p class="text-sm font-medium mb-4">Ingredients list that the user choose</p>
              <div class="space-y-2">
                <div
                  v-for="(ingredient, index) in form.ingredients"
                  :key="index"
                  class="flex items-center justify-between p-2 bg-gray-100 dark:bg-gray-800 rounded"
                >
                  <span class="text-sm">{{ ingredient.ingredient_name }}</span>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="removeIngredient(index)"
                    class="h-6 w-6 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                  >
                    <X class="w-3 h-3" />
                  </Button>
                </div>
                <div v-if="!form.ingredients || form.ingredients.length === 0" class="text-sm text-gray-600 dark:text-gray-400">
                  No ingredients selected yet
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Section: Pricing and Update Button -->
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
  </AppLayout>
</template>