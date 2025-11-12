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
import { Plus, Trash2, Calendar, CalendarDays, Clock, CheckCircle, AlertCircle } from 'lucide-vue-next';

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price?: number;
}

interface MenuPlanDish {
  id: number;
  dish_id: number;
  planned_quantity: number;
  meal_type: string;
  planned_date: string;
  day_of_week?: number;
  notes?: string;
  dish: Dish;
}

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: 'daily' | 'weekly';
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
  is_default?: boolean;
  menu_plan_dishes: MenuPlanDish[];
}

interface Props {
  dishes: Dish[];
  menuPlan: MenuPlan;
  flash?: {
    success?: string;
    error?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Planning', href: '/menu-planning' },
  { title: props.menuPlan.plan_name, href: `/menu-planning/${props.menuPlan.menu_plan_id}` },
  { title: 'Edit', href: `/menu-planning/${props.menuPlan.menu_plan_id}/edit` },
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

// Convert existing menu plan dishes to the format we need
const existingDishes: PlanDish[] = props.menuPlan.menu_plan_dishes.map(mpd => ({
  dish_id: mpd.dish_id,
  dish_name: mpd.dish.dish_name,
  planned_quantity: mpd.planned_quantity,
  meal_type: mpd.meal_type || '',
  planned_date: mpd.planned_date.split('T')[0], // Extract date part
  day_of_week: mpd.day_of_week || undefined,
  notes: mpd.notes || '',
}));

// Format dates properly for form inputs
const formatDateForInput = (dateString: string) => {
  return dateString.split('T')[0]; // Extract YYYY-MM-DD format
};

const form = useForm({
  plan_name: props.menuPlan.plan_name,
  start_date: formatDateForInput(props.menuPlan.start_date),
  end_date: formatDateForInput(props.menuPlan.end_date),
  description: props.menuPlan.description || '',
  is_default: props.menuPlan.is_default ?? false,
  dishes: existingDishes,
});

// Add computed property to determine plan type based on date range
const planType = computed(() => {
  if (!form.start_date || !form.end_date) return props.menuPlan.plan_type;
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
const selectedMealType = ref<string>('');

// planTypes array removed - now using computed planType

// Auto-adjust end date when start date changes (optional behavior)
watch(() => form.start_date, (newDate) => {
  if (newDate && planType.value === 'daily' && form.end_date !== newDate) {
    // Only auto-adjust if it's currently a daily plan and dates don't match
    form.end_date = newDate;
  }
});

// Auto-extend date range when adding dishes outside current range
const autoExtendDateRange = (dishDate: string) => {
  const dishDateObj = new Date(dishDate);
  const startDateObj = new Date(form.start_date);
  const endDateObj = new Date(form.end_date);

  // Extend start date if dish date is earlier
  if (dishDateObj < startDateObj) {
    form.start_date = dishDate;
  }

  // Extend end date if dish date is later
  if (dishDateObj > endDateObj) {
    form.end_date = dishDate;
  }
};

const addDishToPlan = () => {
  if (!selectedDishId.value || !selectedFrequency.value) return;

  const dish = props.dishes.find(d => d.dish_id.toString() === selectedDishId.value);
  if (!dish) return;

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
    // Auto-extend date range if needed
    autoExtendDateRange(date);

    // Check if dish already exists for this date
    const existingIndex = form.dishes.findIndex(d =>
      d.dish_id === dish.dish_id &&
      d.planned_date === date
    );

    if (existingIndex >= 0) {
      // Update existing entry
      form.dishes[existingIndex].planned_quantity += selectedQuantity.value;
      form.dishes[existingIndex].notes = selectedNotes.value;
      if (selectedMealType.value) {
        form.dishes[existingIndex].meal_type = selectedMealType.value;
      }
    } else {
      // Add new entry
      form.dishes.push({
        dish_id: dish.dish_id,
        dish_name: dish.dish_name,
        planned_quantity: selectedQuantity.value,
        meal_type: selectedMealType.value,
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
  selectedMealType.value = '';
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

const submit = () => {
  console.log('Submit function called');

  // Fix day_of_week values to ensure they're within 0-6 range (JavaScript standard)
  const cleanedDishes = form.dishes.map(dish => ({
    ...dish,
    day_of_week: new Date(dish.planned_date).getDay() // Recalculate to ensure accuracy
  }));

  // Add the computed plan_type to the cleaned data
  const formData = {
    plan_name: form.plan_name,
    plan_type: planType.value,
    start_date: form.start_date,
    end_date: form.end_date,
    description: form.description,
    is_default: form.is_default,
    dishes: cleanedDishes
  };

  console.log('Form data being submitted:', formData);
  console.log('Number of dishes:', cleanedDishes.length);

  // Submit the form data with computed plan_type
  form.transform(() => formData).put(`/menu-planning/${props.menuPlan.menu_plan_id}`, {
    onSuccess: (response) => {
      console.log('Update successful:', response);
      // Success message will be handled by the redirect with flash message
    },
    onError: (errors) => {
      console.error('Update failed:', errors);
      console.log('Form errors:', form.errors);
    },
    onStart: () => {
      console.log('Form submission started');
    },
    onFinish: () => {
      console.log('Form submission finished');
    }
  });
};
</script>

<template>
  <Head title="Edit Menu Plan" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto space-y-8 px-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Menu Plan</h1>
        <p class="text-muted-foreground">Update your daily or weekly menu schedule</p>
      </div>

      <!-- Flash Messages -->
      <div v-if="flash?.success" class="flex items-center gap-2 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        <CheckCircle class="w-5 h-5" />
        <span>{{ flash.success }}</span>
      </div>

      <div v-if="flash?.error" class="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <AlertCircle class="w-5 h-5" />
        <span>{{ flash.error }}</span>
      </div>

      <!-- General Form Errors -->
      <div v-if="Object.keys(form.errors).length > 0" class="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
        <AlertCircle class="w-5 h-5" />
        <div>
          <p class="font-medium">Please fix the following errors:</p>
          <ul class="list-disc list-inside mt-1">
            <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
          </ul>
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
                <p class="text-xs text-muted-foreground">
                  Dates will automatically extend when you add dishes outside this range
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
                  {{ planType === 'daily' ? 'Set to same date as start for daily plans' : 'Will auto-extend when adding dishes beyond this date' }}
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

        <!-- Add Dishes to Plan -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Plus class="w-5 h-5" />
              Add Dishes to Plan
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                <Label for="meal_type">Meal Type</Label>
                <Select v-model="selectedMealType">
                  <SelectTrigger>
                    <SelectValue placeholder="Choose meal" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="breakfast">Breakfast</SelectItem>
                    <SelectItem value="lunch">Lunch</SelectItem>
                    <SelectItem value="dinner">Dinner</SelectItem>
                    <SelectItem value="snack">Snack</SelectItem>
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
                  <TableCell colspan="5" class="text-center py-8">
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
          <Button type="button" variant="outline" @click="$inertia.visit(`/menu-planning/${menuPlan.menu_plan_id}`)">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Clock v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Updating...' : 'Update Menu Plan' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>