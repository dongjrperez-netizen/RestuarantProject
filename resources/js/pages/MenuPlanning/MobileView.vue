<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Clock, Users, Utensils, AlertCircle, Printer } from 'lucide-vue-next';
import { computed } from 'vue';

interface DishVariant {
  variant_id: number;
  size_name: string;
  price_modifier: number;
  quantity_multiplier: number;
  is_default: boolean;
  is_available: boolean;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price?: number;
  image_url?: string;
  allergens?: string[];
  dietary_tags?: string[];
  calories?: number;
  preparation_time?: number;
  planned_quantity: number;
  meal_type?: string;
  notes?: string;
  variants?: DishVariant[];
}

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: 'daily' | 'weekly';
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
}

interface Props {
  menuPlan: MenuPlan;
  selectedDate: string;
  dishesByCategory: Record<string, Dish[]>;
  totalDishes: number;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getMealTypeColor = (mealType?: string) => {
  switch (mealType) {
    case 'breakfast':
      return 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200';
    case 'lunch':
      return 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200';
    case 'dinner':
      return 'bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200';
    case 'snack':
      return 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200';
    default:
      return 'bg-muted text-muted-foreground';
  }
};

const totalQuantity = computed(() => {
  return Object.values(props.dishesByCategory).flat().reduce((sum, dish) => sum + dish.planned_quantity, 0);
});

const categoryCount = computed(() => {
  return Object.keys(props.dishesByCategory).length;
});

const sortedCategories = computed(() => {
  return Object.keys(props.dishesByCategory).sort();
});

const printMenu = () => {
  window.print();
};

</script>

<template>
  <Head :title="`Menu for ${formatDate(selectedDate)}`" />

  <!-- Mobile-First Layout without Sidebar -->
  <div class="min-h-screen bg-background">
    <!-- Header -->
    <header class="bg-card shadow-sm border-b sticky top-0 z-10">
      <div class="px-4 py-3 max-w-lg mx-auto">
        <div class="flex items-center justify-between">
          <Link :href="`/menu-planning/${menuPlan.menu_plan_id}`">
            <Button variant="ghost" size="sm" class="p-2">
              <ArrowLeft class="w-5 h-5" />
            </Button>
          </Link>
          <div class="text-center flex-1 mx-3">
            <h1 class="text-lg font-semibold text-foreground truncate">Today's Menu</h1>
            <p class="text-sm text-muted-foreground">{{ formatDate(selectedDate) }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Badge :variant="menuPlan.is_active ? 'default' : 'secondary'" class="text-xs">
              {{ menuPlan.is_active ? 'Active' : 'Inactive' }}
            </Badge>
            <Button variant="outline" size="sm" @click="printMenu" class="p-2">
              <Printer class="w-4 h-4" />
            </Button>
          </div>
        </div>
      </div>
    </header>


    <!-- Menu by Categories -->
    <div class="px-4 pb-6 max-w-lg mx-auto space-y-4">
      <div v-if="Object.keys(dishesByCategory).length === 0" class="text-center py-12">
        <div class="text-muted-foreground mb-2">
          <Utensils class="w-12 h-12 mx-auto mb-3 opacity-50" />
          <p class="text-lg font-medium">No dishes planned</p>
          <p class="text-sm">No dishes are planned for this date.</p>
        </div>
      </div>

      <div v-for="category in sortedCategories" :key="category" class="space-y-3">
        <!-- Category Header -->
        <div class="flex items-center gap-2 px-1">
          <h2 class="text-lg font-semibold text-foreground">{{ category }}</h2>
        </div>

        <!-- Dishes in Category -->
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="dish in dishesByCategory[category]"
            :key="dish.dish_id"
            class="bg-card rounded-xl shadow-sm border border-border overflow-hidden"
          >
            <!-- Card Layout -->
            <div class="flex flex-col h-full">
              <!-- Dish Image with overlays -->
              <div class="relative" style="height: 140px;">
                <img
                  v-if="dish.image_url"
                  :src="dish.image_url"
                  :alt="dish.dish_name"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full bg-muted flex items-center justify-center"
                >
                  <Utensils class="w-12 h-12 text-muted-foreground/50" />
                </div>

                <!-- Price Badge (Top Right) -->
                <div v-if="dish.variants && dish.variants.length > 0" class="absolute top-2 right-2">
                  <div class="bg-orange-600 text-white px-2 py-1 rounded-md text-sm font-bold shadow-sm">
                    ₱{{ Number(dish.variants.find(v => v.is_default)?.price_modifier || 0).toFixed(2) }}
                  </div>
                </div>
                <div v-else-if="dish.price" class="absolute top-2 right-2">
                  <div class="bg-orange-600 text-white px-2 py-1 rounded-md text-sm font-bold shadow-sm">
                    ₱{{ Number(dish.price).toFixed(2) }}
                  </div>
                </div>

                <!-- Meal Type Badge (Top Left) -->
                <div v-if="dish.meal_type" class="absolute top-2 left-2">
                  <Badge :class="getMealTypeColor(dish.meal_type)" class="text-xs">
                    {{ dish.meal_type }}
                  </Badge>
                </div>

              </div>

              <!-- Dish Info (Bottom section) -->
              <div class="p-3 space-y-2">
                <!-- Dish Name -->
                <h3 class="font-semibold text-foreground text-sm leading-tight overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                  {{ dish.dish_name }}
                </h3>

                <!-- Variants/Sizes -->
                <div v-if="dish.variants && dish.variants.length > 0" class="flex flex-wrap gap-1">
                  <div
                    v-for="variant in dish.variants.filter(v => v.is_available)"
                    :key="variant.variant_id"
                    class="px-2 py-0.5 text-xs rounded-md"
                    :class="variant.is_default ? 'bg-orange-100 text-orange-800 font-medium' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ variant.size_name }} ₱{{ Number(variant.price_modifier || 0).toFixed(2) }}
                  </div>
                </div>

                <!-- Additional Info -->
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                  <div v-if="dish.preparation_time" class="flex items-center gap-1">
                    <Clock class="w-3 h-3" />
                    <span>{{ dish.preparation_time }}m</span>
                  </div>
                  <div v-if="dish.calories">
                    <span>{{ dish.calories }} cal</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-6 max-w-lg mx-auto">
      <div class="text-center text-sm text-muted-foreground">
        <p>{{ menuPlan.plan_name }}</p>
        <p>{{ formatDate(menuPlan.start_date) }} - {{ formatDate(menuPlan.end_date) }}</p>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  /* Hide navigation and buttons when printing */
  header {
    display: none !important;
  }

  /* Ensure proper page layout for printing */
  .min-h-screen {
    min-height: auto !important;
  }

  /* Optimize card layout for print */
  .grid-cols-2 {
    grid-template-columns: repeat(3, 1fr) !important;
  }

  /* Ensure images print properly */
  img {
    print-color-adjust: exact !important;
    -webkit-print-color-adjust: exact !important;
  }

  /* Adjust font sizes for print */
  .text-lg {
    font-size: 16px !important;
  }

  .text-sm {
    font-size: 12px !important;
  }

  .text-xs {
    font-size: 10px !important;
  }

  /* Ensure page breaks work well with cards */
  .bg-card {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }

  /* Hide footer with dates when printing */
  .px-4.py-6.max-w-lg.mx-auto:last-child {
    display: none !important;
  }

  /* Hide Laravel branding and any watermarks */
  [class*="laravel"],
  [id*="laravel"],
  .laravel,
  #laravel {
    display: none !important;
  }

  /* Hide any potential branding elements */
  [data-laravel],
  [data-powered-by],
  .powered-by,
  .framework-info {
    display: none !important;
  }
}
</style>