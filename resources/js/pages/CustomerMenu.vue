<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { ChefHat, Clock, Star, DollarSign } from 'lucide-vue-next';

interface MenuCategory {
  category_id: number;
  category_name: string;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price?: number;
  category?: MenuCategory;
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

interface Props {
  dishes: MenuPlanDish[];
  planDate: string;
  planName?: string;
}

const props = defineProps<Props>();

// Group dishes by category
const categorizedDishes = computed(() => {
  const categories: { [key: string]: MenuPlanDish[] } = {};

  props.dishes.forEach(dish => {
    const categoryName = dish.dish.category?.category_name || 'Main Dishes';
    if (!categories[categoryName]) {
      categories[categoryName] = [];
    }
    categories[categoryName].push(dish);
  });

  return categories;
});

// Format date for display
const formattedDate = computed(() => {
  return new Date(props.planDate).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

// Get category icon
const getCategoryIcon = (category: string) => {
  const icons: { [key: string]: any } = {
    'Appetizers': '🥗',
    'Main Dishes': '🍽️',
    'Desserts': '🍰',
    'Beverages': '🥤',
    'Soups': '🍲',
    'Rice': '🍚',
    'Noodles': '🍜'
  };
  return icons[category] || '🍽️';
};

// Category colors
const getCategoryColor = (category: string) => {
  const colors: { [key: string]: string } = {
    'Appetizers': 'bg-green-50 border-green-200',
    'Main Dishes': 'bg-orange-50 border-orange-200',
    'Desserts': 'bg-pink-50 border-pink-200',
    'Beverages': 'bg-blue-50 border-blue-200',
    'Soups': 'bg-yellow-50 border-yellow-200',
    'Rice': 'bg-amber-50 border-amber-200',
    'Noodles': 'bg-purple-50 border-purple-200'
  };
  return colors[category] || 'bg-gray-50 border-gray-200';
};
</script>

<template>
  <Head title="Today's Menu" />

  <div class="min-h-screen bg-gradient-to-br from-orange-50 to-red-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="text-center">
          <div class="flex items-center justify-center mb-2">
            <ChefHat class="w-8 h-8 text-orange-600 mr-2" />
            <h1 class="text-4xl font-bold text-gray-800">Today's Menu</h1>
          </div>
          <p class="text-xl text-gray-600 mb-2">{{ formattedDate }}</p>
          <p v-if="planName" class="text-sm text-orange-600 font-medium">{{ planName }}</p>
        </div>
      </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Categories -->
      <div class="space-y-8">
        <div
          v-for="(dishes, category) in categorizedDishes"
          :key="category"
          class="space-y-4"
        >
          <!-- Category Header -->
          <div class="flex items-center space-x-3">
            <span class="text-3xl">{{ getCategoryIcon(category) }}</span>
            <h2 class="text-2xl font-bold text-gray-800">{{ category }}</h2>
            <Badge variant="secondary" class="ml-auto">
              {{ dishes.length }} item{{ dishes.length !== 1 ? 's' : '' }}
            </Badge>
          </div>

          <!-- Dishes Grid -->
          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Card
              v-for="dish in dishes"
              :key="dish.id"
              :class="['overflow-hidden hover:shadow-lg transition-shadow duration-300', getCategoryColor(category)]"
            >
              <CardHeader class="pb-3">
                <div class="flex justify-between items-start">
                  <CardTitle class="text-lg font-bold text-gray-800 leading-tight">
                    {{ dish.dish.dish_name }}
                  </CardTitle>
                  <Badge v-if="dish.planned_quantity > 1" variant="outline" class="ml-2">
                    {{ dish.planned_quantity }}x
                  </Badge>
                </div>
              </CardHeader>

              <CardContent class="pt-0">
                <!-- Description -->
                <p
                  v-if="dish.dish.description"
                  class="text-gray-600 text-sm mb-4 line-clamp-2"
                >
                  {{ dish.dish.description }}
                </p>

                <!-- Price -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-1">
                    <DollarSign class="w-4 h-4 text-green-600" />
                    <span class="text-2xl font-bold text-green-600">
                      ₱{{ Number(dish.dish.price || 0).toLocaleString() }}
                    </span>
                  </div>

                  <!-- Availability Badge -->
                  <Badge class="bg-green-100 text-green-800 border-green-300">
                    <Clock class="w-3 h-3 mr-1" />
                    Available Today
                  </Badge>
                </div>

                <!-- Special Notes -->
                <div v-if="dish.notes" class="mt-3 p-2 bg-blue-50 rounded-lg border border-blue-200">
                  <p class="text-xs text-blue-700">
                    <Star class="w-3 h-3 inline mr-1" />
                    {{ dish.notes }}
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="Object.keys(categorizedDishes).length === 0" class="text-center py-16">
        <ChefHat class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Menu Available</h3>
        <p class="text-gray-500">There are no dishes planned for this date.</p>
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-white border-t mt-16">
      <div class="max-w-7xl mx-auto px-4 py-6 text-center">
        <p class="text-gray-600">
          Fresh ingredients • Made with love • Served daily
        </p>
        <Button
          variant="outline"
          class="mt-4"
          @click="window.close()"
        >
          Close Menu
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>