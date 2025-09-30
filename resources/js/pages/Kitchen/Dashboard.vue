<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DamageSpoilageModal from '@/components/DamageSpoilageModal.vue';
import {
  ChefHat,
  Clock,
  CheckCircle,
  AlertCircle,
  Users,
  Utensils,
  Timer,
  Play,
  Eye,
  User,
  DollarSign,
  CalendarClock,
  AlertTriangle
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import axios from 'axios';

interface Dish {
  dish_id: number;
  dish_name: string;
  dish_description?: string;
}

interface OrderItem {
  item_id: number;
  dish_id: number;
  quantity: number;
  served_quantity: number;
  unit_price: number;
  special_instructions?: string;
  status: string;
  dish: Dish;
}

interface Table {
  table_id: number;
  table_number: string;
  table_name?: string;
}

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
}

interface CustomerOrder {
  order_id: number;
  order_number: string;
  status: string;
  customer_name?: string;
  created_at: string;
  updated_at: string;
  total_amount?: number;
  table?: Table;
  employee?: Employee;
  order_items: OrderItem[];
}

interface TodayStats {
  total_orders: number;
  pending_orders: number;
  in_progress_orders: number;
  ready_orders: number;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface Props {
  unpaidOrders: CustomerOrder[];
  todayStats: TodayStats;
  employee: Employee;
  ingredients: Ingredient[];
  damageTypes: Record<string, string>;
}

const props = defineProps<Props>();


const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'pending':
    case 'confirmed': return 'secondary';
    case 'in_progress': return 'warning';
    case 'ready': return 'success';
    default: return 'secondary';
  }
};

const getOrderAge = (dateString: string) => {
  const now = new Date();
  const created = new Date(dateString);
  const diffMinutes = Math.floor((now.getTime() - created.getTime()) / (1000 * 60));

  if (diffMinutes < 60) {
    return `${diffMinutes}m ago`;
  } else {
    const hours = Math.floor(diffMinutes / 60);
    return `${hours}h ${diffMinutes % 60}m ago`;
  }
};

const loading = ref(false);
const showDamageModal = ref(false);

const updateOrderStatus = async (orderId: number, newStatus: string) => {
  if (loading.value) return;

  loading.value = true;
  try {
    await axios.post(`/kitchen/orders/${orderId}/status`, {
      status: newStatus
    });
    // Refresh the page to show updated data
    router.reload();
  } catch (error) {
    console.error('Error updating order status:', error);
    alert('Failed to update order status');
  } finally {
    loading.value = false;
  }
};

const getNextStatus = (currentStatus: string) => {
  switch (currentStatus) {
    case 'pending':
    case 'confirmed': return 'in_progress';
    case 'in_progress': return 'ready';
    default: return currentStatus;
  }
};

const getStatusButtonText = (status: string) => {
  switch (status) {
    case 'pending':
    case 'confirmed': return 'Start Cooking';
    case 'in_progress': return 'Mark Ready';
    case 'ready': return 'Order Ready';
    default: return 'Update';
  }
};

const getStatusButtonClass = (status: string) => {
  switch (status) {
    case 'pending':
    case 'confirmed': return 'bg-orange-600 hover:bg-orange-700';
    case 'in_progress': return 'bg-green-600 hover:bg-green-700';
    case 'ready': return 'bg-blue-600 hover:bg-blue-700';
    default: return 'bg-gray-600 hover:bg-gray-700';
  }
};

const canUpdateStatus = (status: string) => {
  return ['pending', 'confirmed', 'in_progress'].includes(status);
};

const openDamageModal = () => {
  showDamageModal.value = true;
};

const closeDamageModal = () => {
  showDamageModal.value = false;
};

const onDamageReportSuccess = () => {
  // Optional: You could reload data or show a success message here
  console.log('Damage/spoilage report submitted successfully');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head title="Kitchen Dashboard" />

    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <ChefHat class="h-8 w-8 text-orange-600" />
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Kitchen Dashboard</h1>
              <p class="text-sm text-gray-600">Active orders and kitchen operations</p>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <Button @click="openDamageModal" class="bg-red-600 hover:bg-red-700 text-white">
              <AlertTriangle class="w-4 h-4 mr-2" />
              Report Damage/Spoilage
            </Button>
            <div class="text-right">
              <p class="text-sm text-gray-500">{{ new Date().toLocaleDateString() }}</p>
              <p class="text-xs text-gray-400">{{ new Date().toLocaleTimeString() }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Orders Grid -->
      <div class="flex gap-6 overflow-x-auto pb-4">
        <Card
          v-for="order in unpaidOrders"
          :key="order.order_id"
          class="flex-shrink-0 w-80 border-2 border-gray-300 hover:shadow-lg transition-all duration-200 flex flex-col h-full"
        >
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between mb-2">
              <Badge :variant="getStatusBadgeVariant(order.status)">
                {{ order.status }}
              </Badge>
              <span class="text-xs text-gray-500">{{ getOrderAge(order.created_at) }}</span>
            </div>
            <h3 class="font-bold text-lg">{{ order.order_number }}</h3>
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-blue-600">
                  {{ order.table?.table_name || 'Table' }}
                </span>
                <span v-if="order.customer_name" class="text-xs text-gray-500">
                  • {{ order.customer_name }}
                </span>
              </div>
              <span class="text-sm font-bold text-blue-700 bg-blue-50 px-2 py-1 rounded">
                #{{ order.table?.table_number || 'N/A' }}
              </span>
            </div>
          </CardHeader>

          <CardContent class="pt-0 flex-grow flex flex-col">
            <div class="flex-grow">
              <h4 class="font-semibold text-gray-900 mb-3">Dish Items</h4>
              <div class="space-y-3">
                <div v-if="!order.order_items || order.order_items.length === 0" class="text-sm text-gray-500">
                  No dishes found
                </div>
                <div
                  v-for="item in order.order_items"
                  :key="item.item_id"
                  class="flex justify-between items-center text-sm p-3 rounded border"
                  :class="item.served_quantity >= item.quantity ? 'bg-green-50 border-green-200' :
                          item.served_quantity > 0 ? 'bg-yellow-50 border-yellow-200' :
                          'bg-white border-gray-200'"
                >
                  <div class="flex items-center space-x-2">
                    <span class="font-medium text-base" :class="item.served_quantity >= item.quantity ? 'line-through text-gray-500' : ''">
                      {{ item.dish?.dish_name || 'Unknown Dish' }}
                    </span>
                    <!-- Show serving status badges -->
                    <span v-if="item.served_quantity >= item.quantity" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                      Served
                    </span>
                    <span v-else-if="item.served_quantity > 0" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">
                      Served {{ item.served_quantity }}/{{ item.quantity }}
                    </span>
                  </div>
                  <span class="font-bold text-lg">{{ item.quantity }}x</span>
                </div>
              </div>
            </div>

            <!-- Buttons pushed to bottom -->
            <div class="mt-6 space-y-2">
              <Button
                v-if="canUpdateStatus(order.status)"
                @click="updateOrderStatus(order.order_id, getNextStatus(order.status))"
                class="w-full"
                :class="getStatusButtonClass(order.status)"
                :disabled="loading"
              >
                {{ loading ? 'Updating...' : getStatusButtonText(order.status) }}
              </Button>

              <Button
                v-else
                class="w-full bg-gray-400 cursor-not-allowed"
                disabled
              >
                {{ getStatusButtonText(order.status) }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Empty state -->
        <div v-if="unpaidOrders.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <ChefHat class="h-16 w-16 mx-auto mb-4 text-gray-300" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">No orders to display</h3>
          <p>Orders will appear here when they are ready for kitchen preparation</p>
        </div>
      </div>
    </div>

    <!-- Damage/Spoilage Modal -->
    <DamageSpoilageModal
      :is-open="showDamageModal"
      :ingredients="ingredients"
      :types="damageTypes"
      @close="closeDamageModal"
      @success="onDamageReportSuccess"
    />
  </div>
</template>