<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Calendar, CalendarDays, Edit2, ArrowLeft, ChevronLeft, ChevronRight, ChefHat } from 'lucide-vue-next';

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
  menu_plan_dishes: MenuPlanDish[];
  created_at: string;
  updated_at: string;
}

interface Props {
  menuPlan: MenuPlan;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Planning', href: '/menu-planning' },
  { title: props.menuPlan.plan_name, href: `/menu-planning/${props.menuPlan.menu_plan_id}` },
];

const currentViewDate = ref(new Date(props.menuPlan.start_date));
const viewMode = ref<'day' | 'week'>(props.menuPlan.plan_type === 'daily' ? 'day' : 'week');

// For single-day plans, start from the planned date when viewing weekly
const isSingleDayPlan = computed(() => {
  const start = new Date(props.menuPlan.start_date);
  const end = new Date(props.menuPlan.end_date);
  return start.toDateString() === end.toDateString();
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatDateFromObject = (date: Date) => {
  return date.toLocaleDateString();
};

const formatLongDate = (date: Date) => {
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Get dishes for a specific date
const getDishesForDate = (date: Date) => {
  const dateString = date.toISOString().split('T')[0];

  return props.menuPlan.menu_plan_dishes.filter(dish => {
    // Handle ISO timestamp format: "2025-09-20T00:00:00.0000000Z"
    let dishDate = dish.planned_date;

    // If it's an ISO string, extract just the date part
    if (dishDate.includes('T')) {
      dishDate = dishDate.split('T')[0];
    }
    // If it's a datetime string with space, extract date part
    else if (dishDate.includes(' ')) {
      dishDate = dishDate.split(' ')[0];
    }

    return dishDate === dateString;
  });
};


// Get week dates starting from current view date
const getWeekDates = computed(() => {
  const dates = [];
  const currentDate = new Date(currentViewDate.value);

  // Find the Sunday of the current week
  const dayOfWeek = currentDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
  const startOfWeek = new Date(currentDate);
  startOfWeek.setDate(currentDate.getDate() - dayOfWeek);

  // Generate all 7 days of the week (Sunday to Saturday)
  for (let i = 0; i < 7; i++) {
    const weekDate = new Date(startOfWeek);
    weekDate.setDate(startOfWeek.getDate() + i);
    dates.push(weekDate);
  }

  return dates;
});

const navigateDate = (direction: 'prev' | 'next') => {
  const newDate = new Date(currentViewDate.value);
  if (viewMode.value === 'day') {
    newDate.setDate(newDate.getDate() + (direction === 'next' ? 1 : -1));

    // For day view, constrain to exact plan date range
    const startDate = new Date(props.menuPlan.start_date);
    const endDate = new Date(props.menuPlan.end_date);
    const newDateStr = newDate.toDateString();
    const startDateStr = startDate.toDateString();
    const endDateStr = endDate.toDateString();

    if (newDateStr >= startDateStr && newDateStr <= endDateStr) {
      currentViewDate.value = newDate;
    }
  } else {
    // For week view, allow navigation if the new week has any overlap with plan range
    newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));

    // Get the week dates for the new date
    const dayOfWeek = newDate.getDay();
    const startOfWeek = new Date(newDate);
    startOfWeek.setDate(newDate.getDate() - dayOfWeek);
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    // Check if this week has any overlap with the plan range
    const planStart = new Date(props.menuPlan.start_date);
    const planEnd = new Date(props.menuPlan.end_date);

    const hasOverlap = startOfWeek <= planEnd && endOfWeek >= planStart;

    if (hasOverlap) {
      currentViewDate.value = newDate;
    }
  }
};

// Check if navigation is possible
const canNavigate = computed(() => ({
  prev: (() => {
    if (viewMode.value === 'day') {
      const testDate = new Date(currentViewDate.value);
      testDate.setDate(testDate.getDate() - 1);
      return testDate.toDateString() >= new Date(props.menuPlan.start_date).toDateString();
    } else {
      // For week view, check if the previous week would have any overlap with the plan range
      const testDate = new Date(currentViewDate.value);
      testDate.setDate(testDate.getDate() - 7);

      // Get the week dates for the test date
      const dayOfWeek = testDate.getDay();
      const startOfWeek = new Date(testDate);
      startOfWeek.setDate(testDate.getDate() - dayOfWeek);
      const endOfWeek = new Date(startOfWeek);
      endOfWeek.setDate(startOfWeek.getDate() + 6);

      // Check if this week has any overlap with the plan range
      const planStart = new Date(props.menuPlan.start_date);
      const planEnd = new Date(props.menuPlan.end_date);

      return endOfWeek >= planStart;
    }
  })(),
  next: (() => {
    if (viewMode.value === 'day') {
      const testDate = new Date(currentViewDate.value);
      testDate.setDate(testDate.getDate() + 1);
      return testDate.toDateString() <= new Date(props.menuPlan.end_date).toDateString();
    } else {
      // For week view, check if the next week would have any overlap with the plan range
      const testDate = new Date(currentViewDate.value);
      testDate.setDate(testDate.getDate() + 7);

      // Get the week dates for the test date
      const dayOfWeek = testDate.getDay();
      const startOfWeek = new Date(testDate);
      startOfWeek.setDate(testDate.getDate() - dayOfWeek);

      // Check if this week has any overlap with the plan range
      const planStart = new Date(props.menuPlan.start_date);
      const planEnd = new Date(props.menuPlan.end_date);

      return startOfWeek <= planEnd;
    }
  })()
}));

// Check if current date is within plan range
const isWithinPlanRange = computed(() => {
  const current = currentViewDate.value.toDateString();
  const start = new Date(props.menuPlan.start_date).toDateString();
  const end = new Date(props.menuPlan.end_date).toDateString();
  return current >= start && current <= end;
});

const getTotalDishesForDate = (date: Date) => {
  return getDishesForDate(date).reduce((total, dish) => total + dish.planned_quantity, 0);
};

const getDishCountForDate = (date: Date) => {
  return getDishesForDate(date).length;
};

// Navigate to mobile menu view for specific date
const viewDayMenu = (date: Date) => {
  const dateString = date.toISOString().split('T')[0];
  const url = `/menu-planning/${props.menuPlan.menu_plan_id}/mobile-view/${dateString}`;

  console.log('ViewDayMenu clicked', {
    date: dateString,
    menuPlanId: props.menuPlan.menu_plan_id,
    url: url
  });

  // Navigate to mobile menu view using Inertia router
  router.visit(url);
};

// Jump to plan start date
const jumpToPlanStart = () => {
  currentViewDate.value = new Date(props.menuPlan.start_date);
};
</script>

<template>
  <Head :title="menuPlan.plan_name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 ml-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link href="/menu-planning">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="w-4 h-4 mr-2" />
              Back to Plans
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ menuPlan.plan_name }}</h1>
            <div class="flex items-center gap-2 text-muted-foreground">
              <Badge :variant="menuPlan.is_active ? 'default' : 'secondary'">
                {{ menuPlan.is_active ? 'Active' : 'Inactive' }}
              </Badge>
              <span>{{ formatDate(menuPlan.start_date) }} - {{ formatDate(menuPlan.end_date) }}</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- View Mode Toggle -->
          <div class="flex items-center bg-muted rounded-lg p-1">
            <Button
              variant="ghost"
              size="sm"
              :class="viewMode === 'day' ? 'bg-background shadow-sm' : ''"
              @click="viewMode = 'day'"
            >
              <Calendar class="w-4 h-4 mr-2" />
              Day
            </Button>
            <Button
              variant="ghost"
              size="sm"
              :class="viewMode === 'week' ? 'bg-background shadow-sm' : ''"
              @click="viewMode = 'week'"
            >
              <CalendarDays class="w-4 h-4 mr-2" />
              Week
            </Button>
          </div>

          <Link :href="`/menu-planning/${menuPlan.menu_plan_id}/edit`">
            <Button>
              <Edit2 class="w-4 h-4 mr-2" />
              Edit Plan
            </Button>
          </Link>
        </div>
      </div>


      <!-- Navigation -->
      <div class="flex items-center justify-between">
        <Button
          variant="outline"
          @click="navigateDate('prev')"
          :disabled="!canNavigate.prev"
        >
          <ChevronLeft class="w-4 h-4 mr-2" />
          Previous {{ viewMode === 'day' ? 'Day' : 'Week' }}
        </Button>

        <div class="text-center">
          <h2 class="text-xl font-semibold">
            {{ viewMode === 'day' ? formatLongDate(currentViewDate) : `Week of ${formatDateFromObject(getWeekDates[0])}` }}
          </h2>
          <div v-if="!isWithinPlanRange" class="text-sm text-orange-600 mt-1 space-y-2">
            <div>⚠️ Outside menu plan range ({{ formatDate(menuPlan.start_date) }} - {{ formatDate(menuPlan.end_date) }})</div>
            <Button size="sm" variant="outline" @click="jumpToPlanStart">
              Jump to Plan Start
            </Button>
          </div>
        </div>

        <Button
          variant="outline"
          @click="navigateDate('next')"
          :disabled="!canNavigate.next"
        >
          Next {{ viewMode === 'day' ? 'Day' : 'Week' }}
          <ChevronRight class="w-4 h-4 ml-2" />
        </Button>
      </div>

      <!-- Day View -->
      <div v-if="viewMode === 'day'" class="space-y-6">
        <div class="text-center">
          <!-- View Plan Button or No Dishes Message -->
          <div v-if="getDishesForDate(currentViewDate).length > 0" class="space-y-4">
            <div class="text-lg text-muted-foreground">
              {{ getDishesForDate(currentViewDate).length }} dish{{ getDishesForDate(currentViewDate).length !== 1 ? 'es' : '' }} planned for this day
            </div>
            <Button
              size="lg"
              @click="viewDayMenu(currentViewDate)"
              class="bg-orange-600 hover:bg-orange-700"
            >
              <ChefHat class="w-5 h-5 mr-2" />
              View Today's Menu
            </Button>
          </div>

          <!-- No dishes message -->
          <div v-else class="py-12 text-muted-foreground">
            <div class="text-lg mb-2">No dishes planned for this day</div>
            <div class="text-sm">
              <span v-if="isWithinPlanRange">
                Edit this menu plan to add dishes for {{ formatLongDate(currentViewDate) }}.
              </span>
              <span v-else>
                This date is outside the menu plan range ({{ formatDate(menuPlan.start_date) }} - {{ formatDate(menuPlan.end_date) }}).
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Week View -->
      <div v-else class="space-y-6">
        <div class="grid grid-cols-7 gap-4">
          <div v-for="date in getWeekDates" :key="date.toISOString()" class="space-y-3">
            <!-- Day Header -->
            <div class="text-center">
              <div class="text-sm font-medium">{{ date.toLocaleDateString('en-US', { weekday: 'short' }) }}</div>
              <div class="text-2xl font-bold">{{ date.getDate() }}</div>
              <div class="text-xs text-muted-foreground">
                {{ getDishCountForDate(date) }} dishes
              </div>
            </div>

            <!-- View Plan Button for days with dishes -->
            <div v-if="getDishesForDate(date).length > 0" class="mt-3">
              <Button
                size="sm"
                variant="outline"
                class="w-full text-xs"
                @click="viewDayMenu(date)"
              >
                View Plan ({{ getDishCountForDate(date) }} dishes)
              </Button>
            </div>

            <!-- No dishes message -->
            <div v-if="getDishesForDate(date).length === 0"
                 class="text-center py-8 text-muted-foreground">
              <div class="text-xs">
                <span v-if="date.toDateString() >= new Date(menuPlan.start_date).toDateString() && date.toDateString() <= new Date(menuPlan.end_date).toDateString()">
                  No dishes planned
                </span>
                <span v-else class="text-orange-600">
                  Outside plan range
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>