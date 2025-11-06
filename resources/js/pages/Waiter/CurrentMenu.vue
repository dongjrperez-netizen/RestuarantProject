<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';

interface MenuCategory {
  category_name: string;
}

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
  price: number;
  is_available: boolean;
  status: 'active' | 'inactive';
  category?: MenuCategory;
  variants?: DishVariant[];
}

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: string;
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
}

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface Props {
  employee: Employee;
  activeMenuPlan?: MenuPlan | null;
  dishes: { [categoryName: string]: Dish[] };
  isDefaultPlan?: boolean;
  currentDate?: string;
}

const props = defineProps<Props>();
</script>

<template>
  <Head title="Current Menu" />

  <WaiterLayout :employee="employee">
    <template #title>Current Menu</template>

    <div class="p-4 sm:p-6">
      <!-- Current Menu Section -->
      <div class="bg-white rounded-lg shadow border">
        <div class="px-6 py-4 border-b">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Current Menu</h2>
              <div v-if="activeMenuPlan" class="space-y-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm text-gray-600">
                    {{ isDefaultPlan ? 'Default Plan:' : 'Active Plan:' }} {{ activeMenuPlan.plan_name }}
                  </p>
                  <span
                    v-if="isDefaultPlan"
                    class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                  >
                    Fallback
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full"
                  >
                    Specific
                  </span>
                </div>
                <p class="text-xs text-gray-500">
                  {{ new Date(activeMenuPlan.start_date).toLocaleDateString() }} - {{ new Date(activeMenuPlan.end_date).toLocaleDateString() }}
                  {{ isDefaultPlan ? '(Using default - no specific plan for today)' : `(Plan for ${currentDate ? new Date(currentDate).toLocaleDateString() : 'today'})` }}
                </p>
              </div>
              <p v-else class="text-sm text-gray-600">
                No menu plan available - please contact manager
              </p>
            </div>
            <Link
              :href="route('waiter.take-order')"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
              Take Order
            </Link>
          </div>
        </div>
        <div class="p-6">
          <!-- Menu Categories -->
          <div v-if="Object.keys(dishes).length > 0" class="space-y-6">
            <div v-for="(categoryDishes, categoryName) in dishes" :key="categoryName" class="space-y-3">
              <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">
                {{ categoryName || 'Uncategorized' }}
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="dish in categoryDishes"
                  :key="dish.dish_id"
                  class="border rounded-lg p-4 space-y-2 hover:shadow-md transition-shadow"
                  :class="dish.is_available ? 'border-gray-200 bg-white' : 'border-gray-300 bg-gray-50 opacity-60'"
                >
                  <div class="flex justify-between items-start">
                    <h4 class="font-semibold text-base" :class="dish.is_available ? 'text-gray-900' : 'text-gray-500'">
                      {{ dish.dish_name }}
                    </h4>
                    <div class="flex flex-col items-end space-y-1">
                      <span v-if="!dish.variants || dish.variants.length === 0" class="font-bold text-lg text-green-600">
                        ₱{{ Number(dish.price).toFixed(2) }}
                      </span>
                      <span v-else class="text-xs text-gray-500 font-medium">
                        Multiple sizes
                      </span>
                      <span
                        class="px-2 py-1 text-xs font-medium rounded-full"
                        :class="dish.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      >
                        {{ dish.is_available ? 'Available' : 'Unavailable' }}
                      </span>
                    </div>
                  </div>
                  <p v-if="dish.description" class="text-sm text-gray-600">
                    {{ dish.description }}
                  </p>

                  <!-- Variants Display -->
                  <div v-if="dish.variants && dish.variants.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs font-medium text-gray-700 mb-2">Available Sizes:</p>
                    <div class="space-y-1">
                      <div
                        v-for="variant in dish.variants"
                        :key="variant.variant_id"
                        class="flex justify-between items-center text-sm"
                      >
                        <div class="flex items-center gap-2">
                          <span class="font-medium text-gray-700">{{ variant.size_name }}</span>
                          <span v-if="variant.is_default" class="text-xs text-blue-600">(Default)</span>
                        </div>
                        <span class="font-semibold text-green-600">₱{{ Number(variant.price_modifier).toFixed(2) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No Menu Available</h3>
            <p class="mt-1 text-sm text-gray-500">
              There are no dishes available in the current menu plan.
            </p>
          </div>
        </div>
      </div>
    </div>
  </WaiterLayout>
</template>