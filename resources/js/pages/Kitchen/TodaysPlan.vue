<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { ArrowLeft, Calendar, ChefHat, UtensilsCrossed, AlertCircle } from 'lucide-vue-next';

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  category?: {
    category_id: number;
    category_name: string;
  };
}

interface MenuPlanDish {
  id: number;
  dish_id: number;
  planned_quantity: number;
  planned_date: string;
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
  is_default: boolean;
  menu_plan_dishes: MenuPlanDish[];
}

interface Props {
  menuPlan: MenuPlan | null;
  currentDate: string;
  isDefaultPlan: boolean;
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

// Group dishes by category
const dishesByCategory = computed(() => {
  if (!props.menuPlan?.menu_plan_dishes) return {};

  const grouped: Record<string, MenuPlanDish[]> = {};

  props.menuPlan.menu_plan_dishes.forEach(planDish => {
    const categoryName = planDish.dish.category?.category_name || 'Uncategorized';
    if (!grouped[categoryName]) {
      grouped[categoryName] = [];
    }
    grouped[categoryName].push(planDish);
  });

  return grouped;
});

const totalPlannedDishes = computed(() => {
  if (!props.menuPlan?.menu_plan_dishes) return 0;
  return props.menuPlan.menu_plan_dishes.reduce((sum, dish) => sum + dish.planned_quantity, 0);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head title="Today's Menu Plan" />

    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center space-x-4">
          <Button
            @click="router.visit('/kitchen/dashboard')"
            variant="outline"
            size="icon"
          >
            <ArrowLeft class="h-5 w-5" />
          </Button>
          <div class="flex items-center space-x-3">
            <Calendar class="h-8 w-8 text-blue-600" />
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Today's Menu Plan</h1>
              <p class="text-sm text-gray-600">{{ formatDate(currentDate) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- No Menu Plan Message -->
      <div v-if="!menuPlan" class="text-center py-12">
        <Card class="max-w-md mx-auto">
          <CardContent class="pt-6">
            <AlertCircle class="h-16 w-16 text-gray-300 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Menu Plan for Today</h3>
            <p class="text-sm text-gray-600 mb-4">
              There is no active menu plan set for {{ formatDate(currentDate) }}.
            </p>
            <p class="text-xs text-gray-500">
              Please contact the restaurant manager to set up a menu plan.
            </p>
            <Button @click="router.visit('/kitchen/dashboard')" class="mt-6" variant="outline">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Dashboard
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- Menu Plan Content -->
      <div v-else class="space-y-6">
        <!-- Summary Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium text-gray-600">Total Dishes</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-bold text-gray-900">{{ menuPlan.menu_plan_dishes.length }}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium text-gray-600">Total Portions</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-bold text-blue-600">{{ totalPlannedDishes }}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium text-gray-600">Categories</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-bold text-green-600">{{ Object.keys(dishesByCategory).length }}</p>
            </CardContent>
          </Card>
        </div>

        <!-- Dishes by Category -->
        <div v-for="(dishes, categoryName) in dishesByCategory" :key="categoryName" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <UtensilsCrossed class="h-5 w-5 text-blue-600" />
                {{ categoryName }}
                <Badge variant="secondary" class="ml-2">
                  {{ dishes.length }} dish{{ dishes.length !== 1 ? 'es' : '' }}
                </Badge>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-[50%]">Dish Name</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead class="text-center">Planned Quantity</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="planDish in dishes" :key="planDish.id">
                    <TableCell class="font-medium">
                      <div class="flex items-center gap-2">
                        <ChefHat class="h-4 w-4 text-gray-400" />
                        {{ planDish.dish.dish_name }}
                      </div>
                    </TableCell>
                    <TableCell class="text-sm text-gray-600">
                      {{ planDish.dish.description || '-' }}
                    </TableCell>
                    <TableCell class="text-center">
                      <Badge variant="default" class="text-lg px-4 py-1">
                        {{ planDish.planned_quantity }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-sm text-gray-500">
                      {{ planDish.notes || '-' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>
