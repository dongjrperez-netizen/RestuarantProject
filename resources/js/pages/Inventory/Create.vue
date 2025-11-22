<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Trash2, AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface ExistingIngredient {
  name: string;
  unit: string;
}

interface Props {
  existingIngredients: ExistingIngredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Add Ingredients', href: '#' },
];

interface IngredientRow {
  ingredient_name: string;
  base_unit: string;
  reorder_level: number;
  current_stock: number;
  showDropdown?: boolean;
  dropdownPosition?: { top: number; left: number; width: number };
}

const ingredients = ref<IngredientRow[]>([
  {
    ingredient_name: '',
    base_unit: '',
    reorder_level: 0,
    current_stock: 0,
    showDropdown: false,
    dropdownPosition: undefined,
  }
]);

const form = useForm({
  ingredients: ingredients.value,
});

const addIngredientRow = () => {
  ingredients.value.push({
    ingredient_name: '',
    base_unit: '',
    reorder_level: 0,
    current_stock: 0,
    showDropdown: false,
    dropdownPosition: undefined,
  });
};

const removeIngredientRow = (index: number) => {
  if (ingredients.value.length > 1) {
    ingredients.value.splice(index, 1);
  }
};

// Get filtered ingredients for autocomplete
const getFilteredIngredients = (index: number) => {
  const searchQuery = ingredients.value[index].ingredient_name?.toLowerCase() || '';

  if (!searchQuery || searchQuery.length < 2) {
    return [];
  }

  return props.existingIngredients.filter(ingredient =>
    ingredient.name.toLowerCase().includes(searchQuery)
  );
};

// Check if ingredient name is similar to existing ones
const getSimilarIngredient = (index: number) => {
  const name = ingredients.value[index].ingredient_name?.toLowerCase() || '';

  if (!name || name.length < 3) {
    return null;
  }

  // Find exact or very similar match
  return props.existingIngredients.find(ingredient => {
    const existingName = ingredient.name.toLowerCase();
    return existingName === name ||
           existingName.includes(name) ||
           name.includes(existingName);
  });
};

const onIngredientInput = (index: number, event?: Event) => {
  const ingredient = ingredients.value[index];
  ingredient.showDropdown = ingredient.ingredient_name.length >= 2;

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    ingredient.dropdownPosition = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
  }
};

const onIngredientFocus = (index: number, event: Event) => {
  const ingredient = ingredients.value[index];
  ingredient.showDropdown = ingredient.ingredient_name.length >= 2;

  // Calculate dropdown position
  if (event && event.target) {
    const input = event.target as HTMLElement;
    const rect = input.getBoundingClientRect();
    ingredient.dropdownPosition = {
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width
    };
  }
};

const closeDropdown = (index: number) => {
  setTimeout(() => {
    ingredients.value[index].showDropdown = false;
  }, 200);
};

const selectExistingIngredient = (index: number, existing: ExistingIngredient) => {
  // This shouldn't happen, but if user clicks an existing ingredient,
  // we'll pre-fill the unit to help them realize it exists
  ingredients.value[index].ingredient_name = existing.name;
  ingredients.value[index].base_unit = existing.unit;
  ingredients.value[index].showDropdown = false;
};

const submit = () => {
  form.ingredients = ingredients.value;
  form.post('/inventory/ingredients', {
    onSuccess: () => {
      ingredients.value = [{
        ingredient_name: '',
        base_unit: '',
        reorder_level: 0,
        current_stock: 0,
      }];
      form.reset();
    },
  });
};
</script>

<template>
  <Head title="Add Ingredient" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Add Ingredients</h1>
          <p class="text-muted-foreground">Add one or multiple ingredients to your inventory</p>
        </div>
        <Button @click="addIngredientRow" variant="outline" type="button">
          + Add Row
        </Button>
      </div>

      <!-- Form Card -->
      <Card>
        <CardHeader>
          <CardTitle>Ingredient Details</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Ingredient Rows -->
            <div
              v-for="(ingredient, index) in ingredients"
              :key="index"
              class="border rounded-lg p-4 space-y-4 relative"
            >
              <!-- Row Header -->
              <div class="flex items-center justify-between">
                <h3 class="font-medium text-sm text-muted-foreground">Ingredient #{{ index + 1 }}</h3>
                <Button
                  v-if="ingredients.length > 1"
                  @click="removeIngredientRow(index)"
                  type="button"
                  variant="ghost"
                  size="sm"
                  class="text-red-600 hover:text-red-700 hover:bg-red-50"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <!-- Ingredient Name -->
                <div class="space-y-2 relative">
                  <Label :for="`ingredient_name_${index}`">Ingredient Name *</Label>
                  <Input
                    :id="`ingredient_name_${index}`"
                    v-model="ingredient.ingredient_name"
                    @input="(e) => onIngredientInput(index, e)"
                    @focus="(e) => onIngredientFocus(index, e)"
                    @blur="closeDropdown(index)"
                    type="text"
                    placeholder="e.g., Tomatoes, Chicken Breast"
                    required
                    autocomplete="off"
                    :class="{
                      'border-red-500': form.errors[`ingredients.${index}.ingredient_name`],
                      'border-yellow-500': getSimilarIngredient(index)
                    }"
                  />

                  <!-- Autocomplete Dropdown -->
                  <div
                    v-if="ingredient.showDropdown && getFilteredIngredients(index).length > 0"
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-y-auto"
                  >
                    <div class="p-2 text-xs font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                      Existing Ingredients ({{ getFilteredIngredients(index).length }})
                    </div>
                    <button
                      v-for="(existing, idx) in getFilteredIngredients(index)"
                      :key="idx"
                      type="button"
                      @mousedown.prevent="selectExistingIngredient(index, existing)"
                      class="w-full text-left px-3 py-2 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 border-b border-gray-100 dark:border-gray-800 last:border-0 transition-colors"
                    >
                      <div class="flex items-start gap-2">
                        <AlertCircle class="h-4 w-4 text-yellow-600 dark:text-yellow-500 mt-0.5 flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                          <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                            {{ existing.name }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">
                            Unit: {{ existing.unit }} • Already in inventory
                          </div>
                        </div>
                      </div>
                    </button>
                  </div>

                  <!-- Warning for similar ingredient -->
                  <div v-if="getSimilarIngredient(index)" class="flex items-start gap-2 p-2 text-xs text-yellow-800 bg-yellow-50 border border-yellow-200 rounded dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800">
                    <AlertCircle class="h-4 w-4 mt-0.5 flex-shrink-0" />
                    <div>
                      <strong>⚠️ This ingredient already exists:</strong> "{{ getSimilarIngredient(index)?.name }}" ({{ getSimilarIngredient(index)?.unit }})
                      <br />Adding it again will create a duplicate. Please use the existing one instead.
                    </div>
                  </div>
                  <p v-if="form.errors[`ingredients.${index}.ingredient_name`]" class="text-red-500 text-sm">
                    {{ form.errors[`ingredients.${index}.ingredient_name`] }}
                  </p>
                </div>

                <!-- Base Unit -->
                <div class="space-y-2">
                  <Label :for="`base_unit_${index}`">Unit *</Label>
                  <Input
                    :id="`base_unit_${index}`"
                    v-model="ingredient.base_unit"
                    type="text"
                    placeholder="e.g., kg, lbs, pcs"
                    :list="`unit-options-${index}`"
                    required
                    :class="{ 'border-red-500': form.errors[`ingredients.${index}.base_unit`] }"
                  />
                  <datalist :id="`unit-options-${index}`">
                    <option value="kg">kg - Kilogram</option>
                    <option value="g">g - Gram</option>
                    <option value="lbs">lbs - Pounds</option>
                    <option value="oz">oz - Ounces</option>
                    <option value="pcs">pcs - Pieces</option>
                    <option value="ml">ml - Milliliter</option>
                    <option value="L">L - Liter</option>
                    <option value="cups">cups</option>
                    <option value="tbsp">tbsp</option>
                    <option value="tsp">tsp</option>
                  </datalist>
                  <p v-if="form.errors[`ingredients.${index}.base_unit`]" class="text-red-500 text-sm">
                    {{ form.errors[`ingredients.${index}.base_unit`] }}
                  </p>
                </div>

                <!-- Initial Stock -->
                <div class="space-y-2">
                  <Label :for="`current_stock_${index}`">Initial Stock</Label>
                  <Input
                    :id="`current_stock_${index}`"
                    v-model.number="ingredient.current_stock"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0"
                    :class="{ 'border-red-500': form.errors[`ingredients.${index}.current_stock`] }"
                  />
                  <p v-if="form.errors[`ingredients.${index}.current_stock`]" class="text-red-500 text-sm">
                    {{ form.errors[`ingredients.${index}.current_stock`] }}
                  </p>
                </div>

                <!-- Reorder Level -->
                <div class="space-y-2">
                  <Label :for="`reorder_level_${index}`">Reorder Level *</Label>
                  <Input
                    :id="`reorder_level_${index}`"
                    v-model.number="ingredient.reorder_level"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="e.g., 10"
                    required
                    :class="{ 'border-red-500': form.errors[`ingredients.${index}.reorder_level`] }"
                  />
                  <p v-if="form.errors[`ingredients.${index}.reorder_level`]" class="text-red-500 text-sm">
                    {{ form.errors[`ingredients.${index}.reorder_level`] }}
                  </p>
                </div>
              </div>
            </div>

            <!-- General Errors -->
            <div v-if="form.errors.ingredients" class="text-red-500 text-sm">
              {{ form.errors.ingredients }}
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-4 border-t">
              <Button
                @click="addIngredientRow"
                type="button"
                variant="outline"
              >
                + Add Another Ingredient
              </Button>
              <div class="flex space-x-3">
                <Button
                  type="button"
                  variant="outline"
                  @click="$inertia.visit('/inventory')"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing"
                >
                  {{ form.processing ? 'Adding...' : `Add ${ingredients.length} Ingredient${ingredients.length > 1 ? 's' : ''}` }}
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
