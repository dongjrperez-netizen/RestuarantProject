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

interface ExistingPlanRange {
  menu_plan_id: number;
  plan_name: string;
  start_date: string;
  end_date: string;
}

interface Props {
  dishes: Dish[];
  ingredients: Ingredient[];
  existingPlans: ExistingPlanRange[];
  hasDefaultPlan: boolean;
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

// ---- Date range conflict detection (frontend helper) ----
const rangeConflictMessage = ref<string>('');

const hasRangeConflict = computed(() => rangeConflictMessage.value.length > 0);

const checkRangeConflict = () => {
  rangeConflictMessage.value = '';

  if (!form.start_date || !form.end_date) return;

  const newStart = new Date(form.start_date);
  const newEnd = new Date(form.end_date);
  if (isNaN(newStart.getTime()) || isNaN(newEnd.getTime())) return;

  // Overlap logic: existing.start <= newEnd && existing.end >= newStart
  const conflictingPlan = props.existingPlans.find((plan) => {
    const existingStart = new Date(plan.start_date);
    const existingEnd = new Date(plan.end_date);
    if (isNaN(existingStart.getTime()) || isNaN(existingEnd.getTime())) return false;

    return existingStart <= newEnd && existingEnd >= newStart;
  });

  if (conflictingPlan) {
    rangeConflictMessage.value = `This date range conflicts with existing plan "${conflictingPlan.plan_name}" (${new Date(conflictingPlan.start_date).toLocaleDateString()} - ${new Date(conflictingPlan.end_date).toLocaleDateString()}).`;
  }
};

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
  // Re-check conflicts whenever start changes
  checkRangeConflict();
});

// Watch end date changes for conflict detection
watch(() => form.end_date, () => {
  checkRangeConflict();
});

// When plan is marked as default, force frequency to daily
watch(() => form.is_default, (isDefault) => {
  if (isDefault) {
    selectedFrequency.value = 'daily';
  }
  // When unchecked, keep current value but allow user to change it
});

// Validate if adding a dish will cause stock shortage
const validateStockForDish = (dish: Dish, quantity: number) => {
  const missingIngredients: string[] = [];
  let sufficient = true;

  if (!dish.ingredients) return { sufficient: true, missingIngredients: [] };

  dish.ingredients.forEach(dishIngredient => {
    if (dishIngredient.pivot.is_optional) return;

    // Calculate total needed including current plan + new dish with unit conversion
    const currentRequirement = ingredientRequirements.value[dishIngredient.ingredient_id]?.total_needed || 0;

    // Convert recipe quantity to inventory base unit
    const recipeQuantity = dishIngredient.pivot.quantity_needed * quantity;
    const recipeUnit = dishIngredient.pivot.unit_of_measure || dishIngredient.base_unit;
    const additionalNeed = convertToBaseUnit(recipeQuantity, recipeUnit, dishIngredient.base_unit);

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

  // Check stock availability before adding (informational only, do not block planning)
  if (dish.ingredients && dish.ingredients.length > 0) {
    const stockCheck = validateStockForDish(dish, selectedQuantity.value);
    if (!stockCheck.sufficient) {
      // Optional: show a soft warning, but allow adding so user can plan for future stock
      console.warn(`Insufficient stock for ${stockCheck.missingIngredients.join(', ')}, but allowing planning.`);
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
  // For default plans, keep frequency locked to daily so user can add multiple dishes
  selectedFrequency.value = form.is_default ? 'daily' : '';
  selectedDate.value = '';
  selectedDayOfWeek.value = '';
};

const removeDishFromPlan = (index: number) => {
  form.dishes.splice(index, 1);
};

interface DishGroup {
  dishName: string;
  pattern: string;
  quantityPerDay: number | string;
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

// Unit conversion utility
const convertToBaseUnit = (quantity: number, fromUnit: string, toUnit: string): number => {
  // Normalize units to lowercase for comparison
  const from = fromUnit?.toLowerCase() || '';
  const to = toUnit?.toLowerCase() || '';

  // If units are the same, no conversion needed
  if (from === to) return quantity;

  // Weight conversions
  const weightConversions: Record<string, number> = {
    'g': 1,
    'gram': 1,
    'grams': 1,
    'kg': 1000,
    'kilogram': 1000,
    'kilograms': 1000,
    'mg': 0.001,
    'milligram': 0.001,
    'milligrams': 0.001,
    'oz': 28.3495,
    'ounce': 28.3495,
    'ounces': 28.3495,
    'lb': 453.592,
    'pound': 453.592,
    'pounds': 453.592,
  };

  // Volume conversions (in milliliters)
  const volumeConversions: Record<string, number> = {
    'ml': 1,
    'milliliter': 1,
    'milliliters': 1,
    'l': 1000,
    'liter': 1000,
    'liters': 1000,
    'cup': 236.588,
    'cups': 236.588,
    'tbsp': 14.7868,
    'tablespoon': 14.7868,
    'tablespoons': 14.7868,
    'tsp': 4.92892,
    'teaspoon': 4.92892,
    'teaspoons': 4.92892,
  };

  // Try weight conversion
  if (from in weightConversions && to in weightConversions) {
    // Convert from source unit to grams, then to target unit
    const inGrams = quantity * weightConversions[from];
    return inGrams / weightConversions[to];
  }

  // Try volume conversion
  if (from in volumeConversions && to in volumeConversions) {
    // Convert from source unit to ml, then to target unit
    const inMl = quantity * volumeConversions[from];
    return inMl / volumeConversions[to];
  }

  // If no conversion available, return original quantity
  console.warn(`Cannot convert from ${fromUnit} to ${toUnit}, using original quantity`);
  return quantity;
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

    // Get quantity per day (check if all quantities are the same)
    const quantities = dishes.map(d => d.planned_quantity);
    const uniqueQuantities = [...new Set(quantities)];
    const quantityPerDay = uniqueQuantities.length === 1 ? uniqueQuantities[0] : 'varies';

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
      quantityPerDay,
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

      // Convert recipe quantity to inventory base unit before adding
      const recipeQuantity = dishIngredient.pivot.quantity_needed * planDish.planned_quantity;
      const recipeUnit = dishIngredient.pivot.unit_of_measure || dishIngredient.base_unit;
      const convertedQuantity = convertToBaseUnit(recipeQuantity, recipeUnit, dishIngredient.base_unit);

      // Add converted quantity needed
      requirements[ingredientId].total_needed += convertedQuantity;

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
   <div class="mx-6 space-y-6">
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
                  :class="{ 'border-red-500': form.errors.start_date || hasRangeConflict }"
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
                  :class="{ 'border-red-500': form.errors.end_date || hasRangeConflict }"
                />
                <p v-if="form.errors.end_date" class="text-sm text-red-500">
                  {{ form.errors.end_date }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ planType === 'daily' ? 'Set to same date as start for daily plans' : 'Select end date for multi-day plans' }}
                </p>
              </div>
            </div>

            <!-- Range conflict warning -->
            <div v-if="hasRangeConflict" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-md p-3">
              {{ rangeConflictMessage }}
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

            <!-- Default plan toggle: only show when no default plan exists yet -->
            <div v-if="!props.hasDefaultPlan" class="flex items-center space-x-2">
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
            <div v-else class="text-xs text-muted-foreground">
              A default menu plan already exists and is used as fallback when no specific plan is active.
            </div>
          </CardContent>
        </Card>


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
                    >
                      {{ dish.dish_name }}
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
                <Select v-model="selectedFrequency" :disabled="form.is_default">
                  <SelectTrigger>
                    <SelectValue placeholder="Choose frequency" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="daily">Daily (Every day)</SelectItem>
                    <SelectItem value="weekly">Weekly (Once per week)</SelectItem>
                    <SelectItem value="specific">Specific Date</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.is_default" class="text-xs text-muted-foreground">
                  Default plans always use daily frequency; the frequency is locked to Daily.
                </p>
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
                  <TableHead>Quantity (per day)</TableHead>
                  <TableHead>Dates Count</TableHead>
                  <TableHead class="w-20">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(group, index) in groupedDishes" :key="index">
                  <TableCell class="font-medium">{{ group.dishName }}</TableCell>
                  <TableCell>
                    <div class="text-sm">
                      {{ form.is_default ? '-' : group.pattern }}
                    </div>
                  </TableCell>
                  <TableCell>{{ group.quantityPerDay }}</TableCell>
                  <TableCell>{{ group.dates.length }} days</TableCell>
                  <TableCell>
                    <Button
                      type="button"
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
            :disabled="form.processing || !form.plan_name || !form.start_date || !form.end_date || hasRangeConflict"
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