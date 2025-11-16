<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Clock, User, Utensils } from 'lucide-vue-next';

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface TakeOutOrder {
  order_id: number;
  order_number: string;
  status: string;
  customer_name?: string | null;
  total_amount?: number | null;
  created_at: string;
  table_id?: number | null;
  table_name?: string | null;
  table_number?: string | null;
}

interface ReadyOrder {
  order_id: number;
  order_number: string;
  table_number: string;
  table_name: string;
}

interface Props {
  employee: Employee;
  orders: TakeOutOrder[];
  readyOrders?: ReadyOrder[];
}

const props = defineProps<Props>();

const formatStatus = (status: string) => {
  switch (status) {
    case 'pending':
      return 'Pending';
    case 'in_progress':
      return 'In Progress';
    case 'ready':
      return 'Ready';
    default:
      return status;
  }
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'pending':
      return 'secondary';
    case 'in_progress':
      return 'warning';
    case 'ready':
      return 'success';
    default:
      return 'secondary';
  }
};

const formatDateTime = (value: string) => {
  try {
    const date = new Date(value);
    return `${date.toLocaleDateString()} ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } catch {
    return value;
  }
};

// Modal state for viewing and updating take-out orders
const showOrderModal = ref(false);
const tableOrders = ref<any[]>([]);
const selectedOrderNumber = ref<string | null>(null);
const selectedOrderId = ref<number | null>(null);

const openOrder = async (order: TakeOutOrder) => {
  if (!order.table_id) {
    console.error('No table_id found for take-out order', order);
    return;
  }

  selectedOrderNumber.value = order.order_number;
  selectedOrderId.value = order.order_id;
  showOrderModal.value = true;

  try {
    const response = await fetch(`/waiter/tables/${order.table_id}/orders`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    const ordersWithDefaults = (data.orders || []).map((o: any) => ({
      ...o,
      order_items: (o.order_items || []).map((item: any) => ({
        ...item,
        served_quantity: item.served_quantity || 0,
      })),
    }));

    // Only keep the selected order (match by ID or order number)
    tableOrders.value = ordersWithDefaults.filter((o: any) =>
      (selectedOrderId.value && o.order_id === selectedOrderId.value) ||
      o.order_number === order.order_number
    );
  } catch (error) {
    console.error('Error fetching take-out orders:', error);
    tableOrders.value = [];
    showOrderModal.value = false;
  }
};

const closeModal = () => {
  showOrderModal.value = false;
  selectedOrderNumber.value = null;
  selectedOrderId.value = null;
  tableOrders.value = [];
};

// Helpers for served/partially served state (same as in waiter dashboard)
const isItemFullyServed = (item: any) => {
  const servedQty = item.served_quantity || 0;
  return servedQty >= item.quantity;
};

const isItemPartiallyServed = (item: any) => {
  const servedQty = item.served_quantity || 0;
  return servedQty > 0 && servedQty < item.quantity;
};

const getServingStatusText = (item: any) => {
  const servedQty = item.served_quantity || 0;
  if (servedQty >= item.quantity) {
    return 'Served';
  } else if (servedQty > 0) {
    return `Served ${servedQty} of ${item.quantity}`;
  }
  return null;
};

const toggleItemServed = async (orderId: number, itemId: number, markAsServed: boolean) => {
  let response: Response | undefined;

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
      console.error('CSRF token not found');
      alert('Security token not found. Please refresh the page.');
      return;
    }

    response = await fetch(`/waiter/orders/${orderId}/items/${itemId}/served`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        served: markAsServed,
      }),
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Server error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success && data.item) {
      tableOrders.value = tableOrders.value.map(order => {
        if (order.order_id === orderId) {
          return {
            ...order,
            order_items: order.order_items.map((item: { item_id: number }) => {
              if (item.item_id === itemId) {
                return {
                  ...item,
                  quantity: data.item.quantity,
                  served_quantity: data.item.served_quantity || 0,
                  status: data.item.status,
                };
              }
              return item;
            }),
          };
        }
        return order;
      });
    } else {
      console.error('Unexpected server response:', data);
      alert('Failed to update item status: ' + (data.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error updating item served status:', error);
    if (response) {
      if (response.status === 419) {
        alert('Your session may have expired. Please refresh the page and try again.');
      } else if (response.status === 403) {
        alert('Access denied. Please make sure you are logged in as a waiter.');
      } else {
        alert(`Failed to update item status (Error ${response.status})`);
      }
    } else {
      alert('Failed to update item status - network error');
    }
  }
};

// Mark all items in a specific take-out order as served
const markOrderFullyServed = async (orderId: number) => {
  let response: Response | undefined;

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
      alert('Security token not found. Please refresh the page.');
      return;
    }

    response = await fetch(`/waiter/orders/${orderId}/serve-all`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({}),
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Server error response (serve-all):', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Update local modal state: mark all items in this order as served
    tableOrders.value = tableOrders.value.map(order => {
      if (order.order_id === orderId) {
        return {
          ...order,
          status: 'served',
          order_items: (order.order_items || []).map((item: any) => ({
            ...item,
            served_quantity: item.quantity,
            status: 'served',
          })),
        };
      }
      return order;
    });
  } catch (error) {
    console.error('Error marking take-out order as fully served:', error);
    if (response) {
      if (response.status === 419) {
        alert('Your session may have expired. Please refresh the page and try again.');
      } else if (response.status === 403) {
        alert('Access denied. Please make sure you are logged in as a waiter.');
      } else {
        alert(`Failed to mark order as served (Error ${response.status})`);
      }
    } else {
      alert('Failed to mark order as served - network error');
    }
  }
};

</script>
<template>
  <Head title="Take Out Orders" />

  <WaiterLayout :employee="employee" :ready-orders="readyOrders">
    <template #title>Take Out Orders</template>

    <div class="p-4 sm:p-6 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl sm:text-2xl font-semibold">Take Out Orders</h1>
          <p class="text-sm text-muted-foreground">Active take out orders for this restaurant</p>
        </div>
      </div>

      <div v-if="orders.length === 0" class="text-center py-12 text-muted-foreground">
        <Utensils class="h-10 w-10 mx-auto mb-3 opacity-40" />
        <p class="font-medium">No active take out orders</p>
        <p class="text-xs">Use the "Take Out" option in the sidebar to create a new takeaway order.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card
          v-for="order in orders"
          :key="order.order_id"
          class="border border-gray-200 shadow-sm"
        >
          <CardHeader class="pb-2">
            <div class="flex items-center justify-between mb-1">
              <CardTitle class="text-base">
                {{ order.order_number }}
              </CardTitle>
              <Badge :variant="getStatusVariant(order.status)">
                {{ formatStatus(order.status) }}
              </Badge>
            </div>
            <p class="text-xs text-muted-foreground">
              {{ formatDateTime(order.created_at) }}
            </p>
          </CardHeader>
          <CardContent class="space-y-2 pt-1">
            <div class="flex items-center gap-2 text-sm">
              <User class="h-4 w-4 text-muted-foreground" />
              <span class="truncate">
                {{ order.customer_name || 'Walk-in Customer' }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <Clock class="h-4 w-4" />
              <span>Created</span>
            </div>
            <div v-if="order.total_amount" class="text-sm font-semibold">
              Total: ₱{{ Number(order.total_amount).toFixed(2) }}
            </div>

            <!-- Quick Actions -->
            <div class="pt-3 border-t mt-3">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Quick Actions</p>
              <div class="flex flex-wrap gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  class="text-xs px-2 py-1 h-auto bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  @click="openOrder(order)"
                >
                  View Order
                </Button>
                <Button
                  as="a"
                  :href="route('waiter.orders.create', { tableId: order.table_id, orderId: order.order_id })"
                  size="sm"
                  variant="outline"
                  class="text-xs px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                >
                  Add Order
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Order Modal for Take Out -->
    <Teleport to="body">
      <div v-if="showOrderModal" class="fixed inset-0 overflow-y-auto h-full w-full z-50" @click="closeModal">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
          <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b">
              <h3 class="text-lg font-medium text-gray-900">
                Take Out Orders
                <span v-if="selectedOrderNumber" class="text-sm text-gray-500">(Order {{ selectedOrderNumber }})</span>
              </h3>
              <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <!-- Modal Content -->
            <div class="py-4">
              <div v-if="tableOrders.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Orders</h3>
                <p class="mt-1 text-sm text-gray-500">This table has no active orders.</p>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="order in tableOrders"
                  :key="order.order_id"
                  class="border rounded-lg p-4 bg-gray-50"
                >
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <h4 class="font-semibold">{{ order.order_number }}</h4>
                      <p class="text-sm text-gray-600">
                        Status:
                        <span
                          class="capitalize px-2 py-1 text-xs rounded-full"
                          :class="order.status === 'pending'
                            ? 'bg-yellow-100 text-yellow-800'
                            : order.status === 'in_progress'
                              ? 'bg-blue-100 text-blue-800'
                              : order.status === 'ready'
                                ? 'bg-green-100 text-green-800'
                                : order.status === 'served'
                                  ? 'bg-green-200 text-green-900'
                                  : 'bg-gray-100 text-gray-800'"
                        >
                          {{ order.status }}
                        </span>
                      </p>
                    </div>
                    <div class="flex items-center gap-2">
                      <Button
                        v-if="order.order_items && order.order_items.length > 0"
                        size="xs"
                        variant="outline"
                        class="text-[11px] px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                        @click="markOrderFullyServed(order.order_id)"
                      >
                        Mark All Served
                      </Button>
                    </div>
                  </div>

                  <div v-if="order.order_items && order.order_items.length > 0">
                    <h5 class="font-medium text-sm mb-2">Order Items:</h5>
                    <div class="space-y-2">
                      <div
                        v-for="item in order.order_items"
                        :key="item.item_id"
                        class="flex items-center justify-between text-sm p-2 rounded border"
                        :class="isItemFullyServed(item)
                          ? 'bg-green-50 border-green-200'
                          : isItemPartiallyServed(item)
                            ? 'bg-yellow-50 border-yellow-200'
                            : 'bg-white border-gray-200'"
                      >
                        <div class="flex items-center space-x-3">
                          <!-- Show checkbox only if item is not fully served -->
                          <input
                            v-if="!isItemFullyServed(item)"
                            type="checkbox"
                            @change="toggleItemServed(order.order_id, item.item_id, true)"
                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                          />
                          <!-- Show serving status if item has any served quantity -->
                          <span
                            v-if="getServingStatusText(item)"
                            class="px-2 py-1 text-xs rounded-full font-medium"
                            :class="isItemFullyServed(item)
                              ? 'bg-green-100 text-green-800'
                              : 'bg-yellow-100 text-yellow-800'"
                          >
                            {{ getServingStatusText(item) }}
                          </span>
                          <span :class="isItemFullyServed(item) ? 'line-through text-gray-500' : ''">
                            {{ item.dish?.dish_name || 'Unknown Item' }}
                            <span v-if="item.variant" class="text-xs text-blue-600 font-semibold">
                              ({{ item.variant.size_name }})
                            </span>
                          </span>
                        </div>
                        <div class="flex items-center space-x-2">
                          <span>{{ item.quantity }}x</span>
                          <!-- Show unserve button for partially/fully served items -->
                          <button
                            v-if="item.served_quantity > 0"
                            @click="toggleItemServed(order.order_id, item.item_id, false)"
                            class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                            title="Unserve one item"
                          >
                            Unserve
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-4 border-t">
              <Button @click="closeModal" variant="outline">
                Close
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </WaiterLayout>
</template>
