<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  description?: string;
  x_position?: number;
  y_position?: number;
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

interface MenuCategory {
  category_name: string;
}

interface Dish {
  dish_id: number;
  dish_name: string;
  description?: string;
  price: number;
  is_available: boolean;
  status: 'active' | 'inactive';
  category?: MenuCategory;
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

interface Props {
  tables: Table[];
  employee: Employee;
  activeMenuPlan?: MenuPlan | null;
  dishes: { [categoryName: string]: Dish[] };
  isDefaultPlan?: boolean;
  currentDate?: string;
}

const props = defineProps<Props>();

const updateTableStatusForm = useForm({
  status: ''
});

const updateTableStatus = (table: Table, newStatus: string) => {
  updateTableStatusForm.status = newStatus;
  updateTableStatusForm.patch(route('waiter.tables.update-status', { table: table.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Success message is handled by the backend flash message
    },
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-800 border-green-200';
    case 'occupied':
      return 'bg-red-100 text-red-800 border-red-200';
    case 'reserved':
      return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    case 'maintenance':
      return 'bg-gray-100 text-gray-800 border-gray-200';
    default:
      return 'bg-gray-100 text-gray-800 border-gray-200';
  }
};
</script>

<template>
  <Head title="Waiter Dashboard" />

  <WaiterLayout :employee="employee">
    <template #title>Tables Overview</template>

    <div class="p-4 sm:p-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Available Tables</p>
              <p class="text-2xl font-bold text-green-600">
                {{ tables.filter(t => t.status === 'available').length }}
              </p>
            </div>
            <div class="h-3 w-3 bg-green-500 rounded-full"></div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Occupied Tables</p>
              <p class="text-2xl font-bold text-red-600">
                {{ tables.filter(t => t.status === 'occupied').length }}
              </p>
            </div>
            <div class="h-3 w-3 bg-red-500 rounded-full"></div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Reserved Tables</p>
              <p class="text-2xl font-bold text-yellow-600">
                {{ tables.filter(t => t.status === 'reserved').length }}
              </p>
            </div>
            <div class="h-3 w-3 bg-yellow-500 rounded-full"></div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Tables</p>
              <p class="text-2xl font-bold text-gray-900">{{ tables.length }}</p>
            </div>
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Tables Overview -->
      <div class="bg-white rounded-lg shadow border mb-6">
        <div class="px-6 py-4 border-b">
          <h2 class="text-lg font-semibold text-gray-900">Restaurant Tables</h2>
          <p class="text-sm text-gray-600">Manage table statuses and track occupancy</p>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="table in tables"
              :key="table.id"
              class="border rounded-lg p-4 space-y-3"
              :class="[
                table.status === 'available' ? 'border-green-200 bg-green-50' :
                table.status === 'occupied' ? 'border-red-200 bg-red-50' :
                table.status === 'reserved' ? 'border-yellow-200 bg-yellow-50' :
                'border-gray-200 bg-gray-50'
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-sm text-gray-600">Table #{{ table.table_number }}</p>
                </div>
                <span
                  class="px-2 py-1 text-xs font-medium rounded-full capitalize"
                  :class="getStatusColor(table.status)"
                >
                  {{ table.status }}
                </span>
              </div>

              <div class="flex items-center gap-2 text-sm">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>{{ table.seats }} seats</span>
              </div>

              <div v-if="table.description" class="text-sm text-gray-600">
                {{ table.description }}
              </div>

              <!-- Table Status Actions -->
              <div class="pt-3 border-t space-y-2">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Quick Actions</p>
                <div class="flex flex-wrap gap-1">
                  <!-- Available Button -->
                  <Button
                    v-if="table.status !== 'available'"
                    @click="updateTableStatus(table, 'available')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                  >
                    Available
                  </Button>

                  <!-- Occupied Button -->
                  <Button
                    v-if="table.status !== 'occupied'"
                    @click="updateTableStatus(table, 'occupied')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-red-50 hover:bg-red-100 text-red-700 border-red-200"
                  >
                    Occupied
                  </Button>

                  <!-- Reserved Button -->
                  <Button
                    v-if="table.status !== 'reserved'"
                    @click="updateTableStatus(table, 'reserved')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border-yellow-200"
                  >
                    Reserved
                  </Button>

                  <!-- Maintenance Button -->
                  <Button
                    v-if="table.status !== 'maintenance'"
                    @click="updateTableStatus(table, 'maintenance')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200"
                  >
                    Maintenance
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

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
                      <span class="font-bold text-lg text-green-600">
                        ₱{{ Number(dish.price).toFixed(2) }}
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