<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Declare Echo type for TypeScript
declare global {
  interface Window {
    Echo: any;
  }
}

interface DishVariant {
  variant_id: number;
  size_name: string;
  price_modifier: number;
  quantity_multiplier: number;
  is_default: boolean;
  is_available: boolean;
}

interface OrderItem {
  item_id: number;
  variant_id?: number;
  quantity: number;
  served_quantity: number;
  status: 'pending' | 'served';
  dish?: {
    dish_id: number;
    dish_name: string;
  };
  variant?: DishVariant;
}

interface Order {
  order_id: number;
  order_number: string;
  status: 'pending' | 'in_progress' | 'ready' | 'served' | 'voided';
  order_items: OrderItem[];
}

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
  user_id: number;
  role: {
    role_name: string;
  };
}


interface Props {
  tables: Table[];
  employee: Employee;
}

const props = defineProps<Props>();


const updateTableStatusForm = useForm({
  status: ''
});

// Modal state
const showOrderModal = ref(false);
const selectedTable = ref<Table | null>(null);
const tableOrders = ref<any[]>([]);

// Highlighted order ID (for scrolling to specific order)
const highlightedOrderId = ref<number | string | null>(null);

// Function to open table orders modal by table ID
const openTableOrdersByTableId = (tableId: number, orderId?: number | string) => {
  const table = props.tables.find(t => t.id === tableId);
  if (table) {
    // Set highlighted order if provided
    if (orderId) {
      highlightedOrderId.value = orderId;
      console.log('Setting highlighted order ID:', orderId);
    }
    viewTableOrders(table);

    // Scroll to the highlighted order after modal opens
    if (orderId) {
      setTimeout(() => {
        const orderElement = document.getElementById(`order-${orderId}`);
        if (orderElement) {
          orderElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
          // Flash highlight effect
          orderElement.classList.add('highlight-flash');
          setTimeout(() => {
            orderElement.classList.remove('highlight-flash');
            highlightedOrderId.value = null;
          }, 3000);
        }
      }, 300);
    }
  }
};

// Note: Toast notifications have been removed in favor of the bell icon notification system
// All notifications now appear in the OrderReadyNotification component

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

// Function to view orders for a table
const viewTableOrders = async (table: Table) => {
  try {
    selectedTable.value = table;
    showOrderModal.value = true;

    // Fetch orders for this table
    const response = await fetch(`/waiter/tables/${table.id}/orders`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    // Ensure served_quantity is properly set for all items
    const ordersWithDefaults = (data.orders || []).map((order: any) => ({
      ...order,
      order_items: (order.order_items || []).map((item: any) => ({
        ...item,
        served_quantity: item.served_quantity || 0
      }))
    }));
    tableOrders.value = ordersWithDefaults;
  } catch (error) {
    console.error('Error fetching table orders:', error);
    tableOrders.value = [];
    // Close modal on error to prevent stuck state
    closeModal();
  }
};

const closeModal = () => {
  showOrderModal.value = false;
  selectedTable.value = null;
  tableOrders.value = [];
};

// Function to check if an item is fully served
const isItemFullyServed = (item: any) => {
  const servedQty = item.served_quantity || 0;
  return servedQty >= item.quantity;
};

// Function to check if an item is partially served
const isItemPartiallyServed = (item: any) => {
  const servedQty = item.served_quantity || 0;
  return servedQty > 0 && servedQty < item.quantity;
};

// Function to get serving status text
const getServingStatusText = (item: any) => {
  const servedQty = item.served_quantity || 0;
  if (servedQty >= item.quantity) {
    return 'Served';
  } else if (servedQty > 0) {
    return `Served ${servedQty} of ${item.quantity}`;
  }
  return null;
};

// Function to toggle item served status
const toggleItemServed = async (orderId: number, itemId: number, markAsServed: boolean) => {
  let response;
  try {
    // Get CSRF token more reliably
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
      console.error('CSRF token not found');
      alert('Security token not found. Please refresh the page.');
      return;
    }

    console.log('Making request with CSRF token:', csrfToken.substring(0, 10) + '...');

    response = await fetch(`/waiter/orders/${orderId}/items/${itemId}/served`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        served: markAsServed
      })
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Server error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log('Server response:', data);

    if (data.success && data.item) {
      // Update the local item with the new data from the server
      tableOrders.value = tableOrders.value.map(order => {
        if (order.order_id === orderId) {
          return {
            ...order,
            order_items: order.order_items.map((item: { item_id: number; }) => {
              if (item.item_id === itemId) {
                return {
                  ...item,
                  quantity: data.item.quantity,
                  served_quantity: data.item.served_quantity || 0,
                  status: data.item.status
                };
              }
              return item;
            })
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
      console.error('Response status:', response.status);
      if (response.status === 419) {
        console.error('CSRF token mismatch - this might indicate an expired session');
        alert('Your session may have expired. Please refresh the page and try again.');
      } else if (response.status === 403) {
        console.error('Access denied - authentication issue');
        alert('Access denied. Please make sure you are logged in as a waiter.');
      } else {
        alert(`Failed to update item status (Error ${response.status})`);
      }
    } else {
      alert('Failed to update item status - network error');
    }
  }
};

// Computed properties to group tables by status
const availableTables = computed(() =>
  props.tables.filter(table => table.status === 'available')
);

const occupiedTables = computed(() =>
  props.tables.filter(table => table.status === 'occupied')
);

const reservedTables = computed(() =>
  props.tables.filter(table => table.status === 'reserved')
);

const maintenanceTables = computed(() =>
  props.tables.filter(table => table.status === 'maintenance')
);

// Real-time order status updates
onMounted(() => {
  if (window.Echo && props.employee) {
    const restaurantId = props.employee.user_id;

    console.log('Setting up real-time listener for restaurant:', restaurantId);

    // Listen for order status updates for this restaurant
    const channel = window.Echo.private(`restaurant.${restaurantId}.waiter`);

    channel.listen('.order.status.updated', (event: any) => {
        console.log('Order status updated event received:', event);
        console.log('Event new_status:', event.new_status);
        console.log('Event order:', event.order);

        // If an order is voided, remove it from the current view
        if (event.new_status === 'voided' && event.order) {
          const voidedOrderId = event.order.order_id;

          console.log(`Processing voided order ${voidedOrderId}`);

          // Remove the voided order from tableOrders if it exists
          tableOrders.value = tableOrders.value.filter(order =>
            order.order_id !== voidedOrderId
          );

          console.log(`Order ${voidedOrderId} was voided and removed from view`);
        }
      })
      .error((error: any) => {
        console.error('Channel subscription error:', error);
      });

    // Log when subscription is successful
    channel.subscribed(() => {
      console.log('Successfully subscribed to waiter channel');
    });

    console.log('Waiter dashboard listening for order updates on channel:', `restaurant.${restaurantId}.waiter`);
  } else {
    console.warn('Echo or employee not available:', {
      hasEcho: !!window.Echo,
      hasEmployee: !!props.employee
    });
  }

  // Listen for custom events to open table orders modal
  const handleViewTableOrders = (event: any) => {
    console.log('view-table-orders event received:', event.detail);
    if (event.detail?.tableId) {
      const orderId = event.detail?.orderId;
      console.log('Opening table orders for table ID:', event.detail.tableId, 'order ID:', orderId);
      openTableOrdersByTableId(event.detail.tableId, orderId);
    }
  };

  window.addEventListener('view-table-orders', handleViewTableOrders);
  console.log('Event listener registered for view-table-orders');
});

onUnmounted(() => {
  if (window.Echo && props.employee) {
    const restaurantId = props.employee.user_id;
    window.Echo.leave(`restaurant.${restaurantId}.waiter`);
  }

  // Remove custom event listener
  const handleViewTableOrders = (event: any) => {
    if (event.detail?.tableId) {
      openTableOrdersByTableId(event.detail.tableId);
    }
  };
  window.removeEventListener('view-table-orders', handleViewTableOrders);
});
</script>

<template>
  <Head title="Waiter Dashboard" />

  <WaiterLayout :employee="employee">
    <template #title>Tables Overview</template>

    <div class="p-4 sm:p-6 space-y-6">
      <!-- Available Tables -->
      <div v-if="availableTables.length > 0" class="bg-white rounded-lg shadow border">
        <div class="px-6 py-4 border-b bg-green-50">
          <div class="flex items-center gap-3">
            <div class="h-4 w-4 bg-green-500 rounded-full"></div>
            <div>
              <h2 class="text-lg font-semibold text-green-800">Available Tables</h2>
              <p class="text-sm text-green-600">{{ availableTables.length }} table{{ availableTables.length !== 1 ? 's' : '' }} ready for customers</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="table in availableTables"
              :key="table.id"
              class="border border-green-200 bg-green-50 rounded-lg p-4 space-y-3"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-sm text-green-600">Table #{{ table.table_number }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 border-green-200">
                  Available
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
                  <!-- View Order Button -->
                  <Button
                    @click="viewTableOrders(table)"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  >
                    View Order
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Occupied Tables -->
      <div v-if="occupiedTables.length > 0" class="bg-white rounded-lg shadow border">
        <div class="px-6 py-4 border-b bg-red-50">
          <div class="flex items-center gap-3">
            <div class="h-4 w-4 bg-red-500 rounded-full"></div>
            <div>
              <h2 class="text-lg font-semibold text-red-800">Occupied Tables</h2>
              <p class="text-sm text-red-600">{{ occupiedTables.length }} table{{ occupiedTables.length !== 1 ? 's' : '' }} currently serving customers</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="table in occupiedTables"
              :key="table.id"
              class="border border-red-200 bg-red-50 rounded-lg p-4 space-y-3"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-sm text-red-600">Table #{{ table.table_number }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 border-red-200">
                  Occupied
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
                    @click="updateTableStatus(table, 'available')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                  >
                    Set Available
                  </Button>

                  <!-- View Order Button -->
                  <Button
                    @click="viewTableOrders(table)"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  >
                    View Order
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reserved Tables -->
      <div v-if="reservedTables.length > 0" class="bg-white rounded-lg shadow border">
        <div class="px-6 py-4 border-b bg-yellow-50">
          <div class="flex items-center gap-3">
            <div class="h-4 w-4 bg-yellow-500 rounded-full"></div>
            <div>
              <h2 class="text-lg font-semibold text-yellow-800">Reserved Tables</h2>
              <p class="text-sm text-yellow-600">{{ reservedTables.length }} table{{ reservedTables.length !== 1 ? 's' : '' }} reserved for upcoming guests</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="table in reservedTables"
              :key="table.id"
              class="border border-yellow-200 bg-yellow-50 rounded-lg p-4 space-y-3"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-sm text-yellow-600">Table #{{ table.table_number }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 border-yellow-200">
                  Reserved
                </span>
              </div>

              <div class="flex items-center gap-2 text-sm">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 715.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
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
                    @click="updateTableStatus(table, 'available')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                  >
                    Set Available
                  </Button>

                  <!-- View Order Button -->
                  <Button
                    @click="viewTableOrders(table)"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  >
                    View Order
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Maintenance Tables -->
      <div v-if="maintenanceTables.length > 0" class="bg-white rounded-lg shadow border">
        <div class="px-6 py-4 border-b bg-gray-50">
          <div class="flex items-center gap-3">
            <div class="h-4 w-4 bg-gray-500 rounded-full"></div>
            <div>
              <h2 class="text-lg font-semibold text-gray-800">Maintenance Tables</h2>
              <p class="text-sm text-gray-600">{{ maintenanceTables.length }} table{{ maintenanceTables.length !== 1 ? 's' : '' }} currently under maintenance</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="table in maintenanceTables"
              :key="table.id"
              class="border border-gray-200 bg-gray-50 rounded-lg p-4 space-y-3"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                  <p class="text-sm text-gray-600">Table #{{ table.table_number }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 border-gray-200">
                  Maintenance
                </span>
              </div>

              <div class="flex items-center gap-2 text-sm">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
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
                    @click="updateTableStatus(table, 'available')"
                    :disabled="updateTableStatusForm.processing"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                  >
                    Set Available
                  </Button>

                  <!-- View Order Button -->
                  <Button
                    @click="viewTableOrders(table)"
                    size="sm"
                    variant="outline"
                    class="text-xs px-2 py-1 h-auto bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  >
                    View Order
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Tables Message -->
      <div v-if="tables.length === 0" class="bg-white rounded-lg shadow border p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 715.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 916 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 914 0z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Tables Found</h3>
        <p class="text-gray-600">There are no tables configured for this restaurant yet.</p>
      </div>
    </div>

    <!-- Order Modal -->
    <Teleport to="body">
      <div v-if="showOrderModal" class="fixed inset-0 overflow-y-auto h-full w-full z-50" @click="closeModal">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">
              Orders for {{ selectedTable?.table_name }} (Table #{{ selectedTable?.table_number }})
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
              <div v-for="order in tableOrders" :key="order.order_id"
                :id="`order-${order.order_id}`"
                :class="{ 'highlighted-order': highlightedOrderId === order.order_id }"
                   class="border rounded-lg p-4 bg-gray-50">
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <h4 class="font-semibold">{{ order.order_number }}</h4>
                    <p class="text-sm text-gray-600">Status:
                      <span class="capitalize px-2 py-1 text-xs rounded-full"
                            :class="order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                   order.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                   order.status === 'ready' ? 'bg-green-100 text-green-800' :
                                   'bg-gray-100 text-gray-800'">
                        {{ order.status }}
                      </span>
                    </p>
                  </div>
                </div>

                <div v-if="order.order_items && order.order_items.length > 0">
                  <h5 class="font-medium text-sm mb-2">Order Items:</h5>
                  <div class="space-y-2">
                    <div v-for="item in order.order_items" :key="item.item_id"
                         class="flex items-center justify-between text-sm p-2 rounded border"
                         :class="isItemFullyServed(item) ? 'bg-green-50 border-green-200' :
                                 isItemPartiallyServed(item) ? 'bg-yellow-50 border-yellow-200' :
                                 'bg-white border-gray-200'">
                      <div class="flex items-center space-x-3">
                        <!-- Show checkbox only if item is not fully served -->
                        <input
                          v-if="!isItemFullyServed(item)"
                          type="checkbox"
                          @change="toggleItemServed(order.order_id, item.item_id, true)"
                          class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                        />
                        <!-- Show serving status if item has any served quantity -->
                        <span v-if="getServingStatusText(item)"
                              class="px-2 py-1 text-xs rounded-full font-medium"
                              :class="isItemFullyServed(item) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
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

<style scoped>
/* Highlight animation for specific order */
@keyframes highlight-flash {
  0%, 100% {
    background-color: transparent;
  }
  50% {
    background-color: rgba(250, 204, 21, 0.3);
  }
}

.highlight-flash {
  animation: highlight-flash 0.8s ease-in-out 3;
}

.highlighted-order {
  border: 2px solid #facc15;
  border-radius: 0.5rem;
}
</style>