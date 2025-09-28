<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import { Plus, Trash2, Calendar, CalendarDays, Clock } from 'lucide-vue-next';

interface DishIngredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  current_stock: number;
  cost_per_unit: number;
  pivot: {
    quantity_needed: number;
    unit_of_measure: string;
    is_optional: boolean;
  };
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price?: number;
  ingredients?: DishIngredient[];
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  current_stock: number;
  cost_per_unit: number;
}

interface Props {
  dishes: Dish[];
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Planning', href: '/menu-planning' },
  { title: 'Create Plan', href: '/menu-planning/create' },
];

interface PlanDish {
  dish_id: number;
  dish_name: string;
  planned_quantity: number;
  meal_type: string;
  planned_date: string;
  day_of_week?: number;
  notes?: string;
  [key: string]: any;
}

const form = useForm({
  plan_name: '',
  start_date: '',
  end_date: '',
  description: '',
  is_default: false,
  dishes: [] as PlanDish[],
});

// Add computed property to determine plan type based on date range
const planType = computed(() => {
  if (!form.start_date || !form.end_date) return 'daily';
  const startDate = new Date(form.start_date);
  const endDate = new Date(form.end_date);
  const daysDiff = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
  return daysDiff === 0 ? 'daily' : 'weekly';
});

const selectedDishId = ref<string>('');
const selectedQuantity = ref<number>(1);
const selectedDate = ref<string>('');
const selectedNotes = ref<string>('');
const selectedFrequency = ref<string>('');
const selectedDayOfWeek = ref<string>('');


// planTypes array removed - now using computed planType

// Set default dates
const today = new Date().toISOString().split('T')[0];

// Calculate end date for weekly plan (exactly 7 days: start + 6 days)
const getWeeklyEndDate = (startDate: string) => {
  const start = new Date(startDate);
  const end = new Date(start);
  end.setDate(start.getDate() + 6); // Add 6 days for a 7-day week (start + 6 = 7 total days)
  return end.toISOString().split('T')[0];
};

// Initialize dates
const initializeDates = () => {
  if (!form.start_date) {
    form.start_date = today;
  }
  if (!form.end_date) {
    form.end_date = form.start_date; // Default to daily (same day)
  }
};

// Initialize on component mount
initializeDates();


// Watch start date changes to auto-set end date if not manually changed
watch(() => form.start_date, (newDate) => {
  if (newDate && !form.end_date) {
    form.end_date = newDate; // Default to same day if no end date set
  }
});

// Validate if adding a dish will cause stock shortage
const validateStockForDish = (dish: Dish, quantity: number) => {
  const missingIngredients: string[] = [];
  let sufficient = true;

  if (!dish.ingredients) return { sufficient: true, missingIngredients: [] };

  dish.ingredients.forEach(dishIngredient => {
    if (dishIngredient.pivot.is_optional) return;

    // Calculate total needed including current plan + new dish
    const currentRequirement = ingredientRequirements.value[dishIngredient.ingredient_id]?.total_needed || 0;
    const additionalNeed = dishIngredient.pivot.quantity_needed * quantity;
    const totalNeeded = currentRequirement + additionalNeed;

    if (dishIngredient.current_stock < totalNeeded) {
      missingIngredients.push(dishIngredient.ingredient_name);
      sufficient = false;
    }
  });

  return { sufficient, missingIngredients };
};

const addDishToPlan = () => {
  if (!selectedDishId.value || !selectedFrequency.value) return;

  const dish = props.dishes.find(d => d.dish_id.toString() === selectedDishId.value);
  if (!dish) return;

  // Check stock availability before adding
  if (dish.ingredients && dish.ingredients.length > 0) {
    const stockCheck = validateStockForDish(dish, selectedQuantity.value);
    if (!stockCheck.sufficient) {
      alert(`Cannot add dish: Insufficient stock for ${stockCheck.missingIngredients.join(', ')}`);
      return;
    }
  }

  // Validate specific requirements
  if (selectedFrequency.value === 'specific' && !selectedDate.value) return;
  if (selectedFrequency.value === 'weekly' && !selectedDayOfWeek.value) return;

  // Generate dates based on frequency
  const datesToAdd: string[] = [];
  const startDate = new Date(form.start_date);
  const endDate = new Date(form.end_date);

  if (selectedFrequency.value === 'specific') {
    datesToAdd.push(selectedDate.value);
  } else if (selectedFrequency.value === 'daily') {
    // Add for every day in the range
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
      datesToAdd.push(d.toISOString().split('T')[0]);
    }
  } else if (selectedFrequency.value === 'weekly') {
    // Add for specific day of week in the range
    const targetDayOfWeek = parseInt(selectedDayOfWeek.value);
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
      if (d.getDay() === targetDayOfWeek) {
        datesToAdd.push(d.toISOString().split('T')[0]);
      }
    }
  }

  // Add dishes for all generated dates
  datesToAdd.forEach(date => {
    // Check if dish already exists for this date
    const existingIndex = form.dishes.findIndex(d =>
      d.dish_id === dish.dish_id &&
      d.planned_date === date
    );

    if (existingIndex >= 0) {
      // Update existing entry
      form.dishes[existingIndex].planned_quantity += selectedQuantity.value;
      form.dishes[existingIndex].notes = selectedNotes.value;
    } else {
      // Add new entry
      form.dishes.push({
        dish_id: dish.dish_id,
        dish_name: dish.dish_name,
        planned_quantity: selectedQuantity.value,
        meal_type: '',
        planned_date: date,
        day_of_week: new Date(date).getDay(),
        notes: selectedNotes.value,
      });
    }
  });

  // Reset form
  selectedDishId.value = '';
  selectedQuantity.value = 1;
  selectedNotes.value = '';
  selectedFrequency.value = '';
  selectedDate.value = '';
  selectedDayOfWeek.value = '';
};

const removeDishFromPlan = (index: number) => {
  form.dishes.splice(index, 1);
};

interface DishGroup {
  dishName: string;
  pattern: string;
  totalQuantity: number;
  dishes: PlanDish[];
  dates: string[];
}

const removeDishGroup = (group: DishGroup) => {
  // Remove all dishes that match this group's dish name
  form.dishes = form.dishes.filter(dish =>
    dish.dish_name !== group.dishName
  );
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

// Group dishes for better display
const groupedDishes = computed((): DishGroup[] => {
  const groups: { [key: string]: PlanDish[] } = {};

  form.dishes.forEach(dish => {
    const key = dish.dish_name;
    if (!groups[key]) {
      groups[key] = [];
    }
    groups[key].push(dish);
  });

  return Object.entries(groups).map(([dishName, dishes]) => {
    const dates = dishes.map(d => d.planned_date).sort();
    const totalQuantity = dishes.reduce((sum, d) => sum + d.planned_quantity, 0);

    // Determine pattern
    let pattern = '';
    if (dishes.length === 1) {
      pattern = `Specific date: ${formatDate(dates[0])}`;
    } else if (dates.length === (new Date(form.end_date).getTime() - new Date(form.start_date).getTime()) / (1000 * 60 * 60 * 24) + 1) {
      pattern = 'Daily';
    } else {
      const dayOfWeek = new Date(dates[0]).toLocaleDateString('en-US', { weekday: 'long' });
      if (dates.every(date => new Date(date).getDay() === new Date(dates[0]).getDay())) {
        pattern = `Weekly (${dayOfWeek}s)`;
      } else {
        pattern = `Multiple dates (${dates.length} days)`;
      }
    }

    return {
      dishName,
      pattern,
      totalQuantity,
      dishes,
      dates
    };
  });
});

// Calculate total ingredient requirements for entire menu plan
const ingredientRequirements = computed(() => {
  const requirements: Record<number, {
    ingredient_name: string;
    total_needed: number;
    current_stock: number;
    base_unit: string;
    sufficient: boolean;
  }> = {};

  form.dishes.forEach(planDish => {
    const dish = props.dishes.find(d => d.dish_id === planDish.dish_id);
    if (!dish?.ingredients) return;

    dish.ingredients.forEach(dishIngredient => {
      if (dishIngredient.pivot.is_optional) return; // Skip optional ingredients

      const ingredientId = dishIngredient.ingredient_id;

      if (!requirements[ingredientId]) {
        requirements[ingredientId] = {
          ingredient_name: dishIngredient.ingredient_name,
          total_needed: 0,
          current_stock: dishIngredient.current_stock,
          base_unit: dishIngredient.base_unit,
          sufficient: true
        };
      }

      // Add quantity needed (dish ingredient quantity * planned quantity)
      requirements[ingredientId].total_needed += dishIngredient.pivot.quantity_needed * planDish.planned_quantity;

      // Update sufficient status
      requirements[ingredientId].sufficient =
        requirements[ingredientId].current_stock >= requirements[ingredientId].total_needed;
    });
  });

  return requirements;
});

// Check if entire menu plan can be produced
const canProducePlan = computed(() => {
  return Object.values(ingredientRequirements.value).every(req => req.sufficient);
});

// Get ingredients with insufficient stock
const insufficientIngredients = computed(() => {
  return Object.values(ingredientRequirements.value).filter(req => !req.sufficient);
});

// Get stock status for a specific dish
const getDishStockStatus = (dish: Dish) => {
  if (!dish.ingredients) return { canProduce: true, missingIngredients: [] };

  const missingIngredients: string[] = [];
  let canProduce = true;

  dish.ingredients.forEach(dishIngredient => {
    if (dishIngredient.pivot.is_optional) return;

    const requirements = ingredientRequirements.value[dishIngredient.ingredient_id];
    if (requirements && !requirements.sufficient) {
      missingIngredients.push(requirements.ingredient_name);
      canProduce = false;
    }
  });

  return { canProduce, missingIngredients };
};

const submit = () => {
  console.log('🚀 Starting form submission...');
  console.log('📝 Form data before transform:', form.data());
  console.log('✅ is_default value:', form.is_default);

  // Add the computed plan_type to the form data before submission
  const formData = {
    ...form.data(),
    plan_type: planType.value
  };

  console.log('📦 Final form data being sent:', formData);

  form.transform(() => formData).post('/menu-planning', {
    onStart: () => {
      console.log('📡 Request started');
    },
    onSuccess: (page) => {
      console.log('✅ SUCCESS: Menu plan created successfully!');
    },
    onError: (errors) => {
      console.error('❌ FORM SUBMISSION FAILED!');
      console.error('Server returned these errors:', errors);

      // Show errors in alert
      if (Object.keys(errors).length > 0) {
        const errorList = Object.entries(errors)
          .map(([field, message]) => `• ${field}: ${message}`)
          .join('\n');

        alert(`❌ FORM ERRORS:\n\n${errorList}`);
      } else {
        alert('❌ UNKNOWN ERROR: Form submission failed but no specific errors were returned.');
      }
    },
    onFinish: () => {
      console.log('🏁 Request completed');
    }
  });
};
</script>

<template>
  <Head title="Create Menu Plan" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto space-y-8 px-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create Menu Plan</h1>
        <p class="text-muted-foreground">Plan your dishes for daily or weekly schedules</p>
      </div>

      <!-- Error Messages Only -->
      <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
        <h3 class="text-red-800 font-bold mb-2">❌ Error Messages:</h3>
        <div class="space-y-1">
          <div v-for="(error, field) in form.errors" :key="field" class="text-red-700">
            <strong>{{ field }}:</strong> {{ error }}
          </div>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Plan Details -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calendar class="w-5 h-5" />
              Plan Details
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="plan_name">Plan Name *</Label>
                <Input
                  id="plan_name"
                  v-model="form.plan_name"
                  placeholder="Enter plan name"
                  :class="{ 'border-red-500': form.errors.plan_name }"
                />
                <p v-if="form.errors.plan_name" class="text-sm text-red-500">
                  {{ form.errors.plan_name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label class="text-sm font-medium">Plan Type</Label>
                <p class="text-sm">
                  <strong>{{ planType === 'daily' ? 'Daily Plan' : 'Weekly Plan' }}</strong>
                  - {{ planType === 'daily' ? 'Same start and end date' : 'Multiple days selected' }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="start_date">Start Date *</Label>
                <Input
                  id="start_date"
                  v-model="form.start_date"
                  type="date"
                  :class="{ 'border-red-500': form.errors.start_date }"
                />
                <p v-if="form.errors.start_date" class="text-sm text-red-500">
                  {{ form.errors.start_date }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="end_date">End Date *</Label>
                <Input
                  id="end_date"
                  v-model="form.end_date"
                  type="date"
                  :class="{ 'border-red-500': form.errors.end_date }"
                />
                <p v-if="form.errors.end_date" class="text-sm text-red-500">
                  {{ form.errors.end_date }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ planType === 'daily' ? 'Set to same date as start for daily plans' : 'Select end date for multi-day plans' }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Textarea
                id="description"
                v-model="form.description"
                placeholder="Describe this menu plan..."
                class="min-h-[80px]"
              />
            </div>

            <div class="flex items-center space-x-2">
              <input
                type="checkbox"
                id="is_default"
                v-model="form.is_default"
                class="h-4 w-4 text-primary border-input rounded focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
              <Label for="is_default" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                Set as Default Plan
              </Label>
              <div class="text-xs text-muted-foreground ml-2">
                (This plan will be used as fallback when no specific plan exists for a week)
              </div>
              <div class="text-xs text-blue-600 ml-2" v-if="form.is_default">
                ✓ This will be the default plan
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Overall Stock Status -->
        <div v-if="form.dishes.length > 0" class="mb-6">
          <div
            :class="[
              'p-4 rounded-lg border',
              canProducePlan
                ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
            ]"
          >
            <div class="flex items-center gap-3">
              <Badge
                :variant="canProducePlan ? 'default' : 'destructive'"
                class="text-sm px-3 py-1"
              >
                {{ canProducePlan ? '✓ Plan Feasible' : '⚠ Stock Issues' }}
              </Badge>
              <div :class="canProducePlan ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                <span v-if="canProducePlan" class="font-medium">
                  All dishes can be produced with current inventory!
                </span>
                <span v-else class="font-medium">
                  {{ insufficientIngredients.length }} ingredient(s) have insufficient stock for this plan
                </span>
              </div>
            </div>
            <div v-if="!canProducePlan" class="mt-3">
              <div class="text-sm text-red-700 dark:text-red-300">
                <strong>Missing ingredients:</strong>
                <ul class="mt-1 space-y-1">
                  <li v-for="ingredient in insufficientIngredients" :key="ingredient.ingredient_name" class="flex justify-between">
                    <span>• {{ ingredient.ingredient_name }}</span>
                    <span class="font-mono">
                      Need: {{ ingredient.total_needed }} {{ ingredient.base_unit }} |
                      Have: {{ ingredient.current_stock }} {{ ingredient.base_unit }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Dishes to Plan -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Plus class="w-5 h-5" />
              Add Dishes to Plan
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="space-y-2">
                <Label for="dish_select">Select Dish</Label>
                <Select v-model="selectedDishId">
                  <SelectTrigger>
                    <SelectValue placeholder="Choose dish" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="dish in dishes"
                      :key="dish.dish_id"
                      :value="dish.dish_id.toString()"
                      :class="getDishStockStatus(dish).canProduce ? '' : 'text-red-600 dark:text-red-400'"
                    >
                      <div class="flex items-center justify-between w-full">
                        <span>{{ dish.dish_name }}</span>
                        <div class="flex items-center gap-1">
                          <Badge
                            v-if="getDishStockStatus(dish).canProduce"
                            variant="default"
                            class="text-xs"
                          >
                            ✓
                          </Badge>
                          <Badge
                            v-else
                            variant="destructive"
                            class="text-xs"
                          >
                            ⚠
                          </Badge>
                        </div>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label for="quantity">Quantity</Label>
                <Input
                  id="quantity"
                  v-model="selectedQuantity"
                  type="number"
                  min="1"
                  placeholder="1"
                />
              </div>

              <div class="space-y-2">
                <Label for="frequency">Frequency</Label>
                <Select v-model="selectedFrequency">
                  <SelectTrigger>
                    <SelectValue placeholder="Choose frequency" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="daily">Daily (Every day)</SelectItem>
                    <SelectItem value="weekly">Weekly (Once per week)</SelectItem>
                    <SelectItem value="specific">Specific Date</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label>&nbsp;</Label>
                <Button
                  type="button"
                  @click="addDishToPlan"
                  :disabled="!selectedDishId || !selectedFrequency"
                  class="w-full"
                >
                  <Plus class="w-4 h-4 mr-2" />
                  Add
                </Button>
              </div>
            </div>

            <!-- Conditional inputs based on frequency -->
            <div v-if="selectedFrequency === 'specific'" class="space-y-2">
              <Label for="specific_date">Select Date</Label>
              <Input
                id="specific_date"
                v-model="selectedDate"
                type="date"
                :min="form.start_date"
                :max="form.end_date"
              />
            </div>

            <div v-if="selectedFrequency === 'weekly'" class="space-y-2">
              <Label for="day_of_week">Day of Week</Label>
              <Select v-model="selectedDayOfWeek">
                <SelectTrigger>
                  <SelectValue placeholder="Choose day" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">Sunday</SelectItem>
                  <SelectItem value="1">Monday</SelectItem>
                  <SelectItem value="2">Tuesday</SelectItem>
                  <SelectItem value="3">Wednesday</SelectItem>
                  <SelectItem value="4">Thursday</SelectItem>
                  <SelectItem value="5">Friday</SelectItem>
                  <SelectItem value="6">Saturday</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="notes">Notes (Optional)</Label>
              <Input
                id="notes"
                v-model="selectedNotes"
                placeholder="Special preparation notes..."
              />
            </div>
          </CardContent>
        </Card>

        <!-- Planned Dishes -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CalendarDays class="w-5 h-5" />
                Planned Dishes
                <span class="text-sm font-normal text-muted-foreground">
                  ({{ groupedDishes.length }} dish groups, {{ form.dishes.length }} total entries)
                </span>
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Dish</TableHead>
                  <TableHead>Schedule Pattern</TableHead>
                  <TableHead>Total Quantity</TableHead>
                  <TableHead>Dates Count</TableHead>
                  <TableHead>Stock Status</TableHead>
                  <TableHead class="w-20">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(group, index) in groupedDishes" :key="index">
                  <TableCell class="font-medium">{{ group.dishName }}</TableCell>
                  <TableCell>
                    <div class="text-sm">
                      {{ group.pattern }}
                    </div>
                  </TableCell>
                  <TableCell>{{ group.totalQuantity }}</TableCell>
                  <TableCell>{{ group.dates.length }} days</TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Badge
                        :variant="getDishStockStatus(props.dishes.find(d => d.dish_name === group.dishName)).canProduce ? 'default' : 'destructive'"
                        class="text-xs"
                      >
                        {{ getDishStockStatus(props.dishes.find(d => d.dish_name === group.dishName)).canProduce ? '✓ Available' : '⚠ Low Stock' }}
                      </Badge>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="removeDishGroup(group)"
                      class="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                    >
                      <Trash2 class="w-3 h-3" />
                    </Button>
                  </TableCell>
                </TableRow>
                <TableRow v-if="groupedDishes.length === 0">
                  <TableCell colspan="6" class="text-center py-8">
                    <div class="text-muted-foreground">
                      <CalendarDays class="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
                      <div class="text-lg mb-2">No dishes planned yet</div>
                      <div class="text-sm">Add dishes to your menu plan using the form above.</div>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <Button type="button" variant="outline" @click="$inertia.visit('/menu-planning')">
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing || !form.plan_name || !form.start_date || !form.end_date"
            @click="console.log('🎯 Submit button clicked!')"
          >
            <Clock v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Creating...' : 'Create Menu Plan' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>