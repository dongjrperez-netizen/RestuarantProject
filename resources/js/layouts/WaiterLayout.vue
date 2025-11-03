<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import {
  Menu,
  LogOut,
  Users,
  TableProperties,
  ChefHat,
  X,
  Bell,
  BellRing
} from 'lucide-vue-next';

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface ReadyOrder {
  order_id: number;
  order_number: string;
  table_number: string;
  table_name: string;
}

interface Props {
  employee: Employee;
  readyOrders?: ReadyOrder[];
}

const props = defineProps<Props>();

const sidebarOpen = ref(false);
const showNotifications = ref(false);
const readyOrdersList = ref<ReadyOrder[]>(props.readyOrders || []);
const previousReadyOrdersCount = ref(props.readyOrders?.length || 0);
const newNotifications = ref(false);

const logoutForm = useForm({});

const logout = () => {
  logoutForm.post(route('logout'));
};

// Auto-refresh for ready orders
let refreshInterval: ReturnType<typeof setInterval> | null = null;
const REFRESH_INTERVAL = 10000; // Check every 10 seconds

const startAutoRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }

  refreshInterval = setInterval(() => {
    router.reload({
      only: ['readyOrders'],
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        const page = usePage();
        const newReadyOrders = (page.props.readyOrders as ReadyOrder[]) || [];

        // Check if there are new ready orders
        if (newReadyOrders.length > previousReadyOrdersCount.value) {
          newNotifications.value = true;
          // Play notification sound if available
          playNotificationSound();
        }

        readyOrdersList.value = newReadyOrders;
        previousReadyOrdersCount.value = newReadyOrders.length;
      }
    });
  }, REFRESH_INTERVAL);
};

const stopAutoRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
    refreshInterval = null;
  }
};

const playNotificationSound = () => {
  // Simple beep sound using Audio API
  try {
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZUQ0PVq3n7q9aGQxDmN301H0pBSuB0PLaizsIGGS57OihUxELTKXh8bllHgU2j9Xx0YQ2Bx1rwO7mnVIPD1Ks5O+uWBkLQZje87d/Kgc');
    audio.play();
  } catch (error) {
    console.log('Notification sound failed:', error);
  }
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value) {
    newNotifications.value = false;
  }
};

const hasReadyOrders = computed(() => readyOrdersList.value.length > 0);

// Remove individual notification
const removeNotification = (orderId: number) => {
  readyOrdersList.value = readyOrdersList.value.filter(order => order.order_id !== orderId);
  previousReadyOrdersCount.value = readyOrdersList.value.length;
};

// Clear all notifications
const clearAllNotifications = () => {
  readyOrdersList.value = [];
  previousReadyOrdersCount.value = 0;
  showNotifications.value = false;
};

onMounted(() => {
  startAutoRefresh();
  readyOrdersList.value = props.readyOrders || [];
  previousReadyOrdersCount.value = props.readyOrders?.length || 0;
});

onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile Header (visible only on mobile) -->
    <header class="lg:hidden bg-white shadow-sm border-b fixed top-0 left-0 right-0 z-30">
      <div class="flex items-center justify-between px-3 sm:px-4 h-12">
        <!-- Menu Button -->
        <Button
          variant="ghost"
          size="sm"
          @click="sidebarOpen = !sidebarOpen"
          class="p-2"
        >
          <Menu class="h-5 w-5" />
        </Button>

        <!-- Page Title -->
        <div class="flex-1 text-center">
          <h1 class="text-lg font-semibold text-gray-900 truncate">
            <slot name="title">Dashboard</slot>
          </h1>
        </div>

        <!-- Notification Bell & Logout -->
        <div class="flex items-center gap-2">
          <!-- Notification Bell -->
          <div class="relative">
            <Button
              @click="toggleNotifications"
              variant="ghost"
              size="sm"
              class="p-2 relative"
              :class="hasReadyOrders ? 'text-green-600 hover:text-green-700 hover:bg-green-50' : ''"
            >
              <component :is="newNotifications ? BellRing : Bell" class="h-5 w-5" :class="newNotifications ? 'animate-pulse' : ''" />
              <span
                v-if="hasReadyOrders"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
              >
                {{ readyOrdersList.length }}
              </span>
            </Button>

            <!-- Notification Dropdown -->
            <div
              v-if="showNotifications"
              class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50 max-h-96 overflow-hidden flex flex-col"
              @click.stop
            >
              <div class="p-3 border-b bg-green-50 flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-green-800">Ready to Serve</h3>
                  <p class="text-xs text-green-600">{{ readyOrdersList.length }} order{{ readyOrdersList.length !== 1 ? 's' : '' }} ready</p>
                </div>
                <Button
                  v-if="readyOrdersList.length > 0"
                  @click="clearAllNotifications"
                  variant="ghost"
                  size="sm"
                  class="text-xs h-7 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                >
                  Clear All
                </Button>
              </div>

              <div class="overflow-y-auto flex-1">
                <div v-if="readyOrdersList.length === 0" class="p-4 text-center text-gray-500">
                  <Bell class="h-8 w-8 mx-auto mb-2 opacity-50" />
                  <p class="text-sm">No orders ready</p>
                </div>

                <div v-else class="divide-y">
                  <div
                    v-for="order in readyOrdersList"
                    :key="order.order_id"
                    class="p-3 hover:bg-gray-50 group"
                  >
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                        <span class="text-green-700 font-bold text-sm">{{ order.table_number }}</span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm">{{ order.table_name }}</p>
                        <p class="text-xs text-gray-600">Order {{ order.order_number }}</p>
                        <p class="text-xs text-green-600 font-medium">Ready to serve!</p>
                      </div>
                      <Button
                        @click="removeNotification(order.order_id)"
                        variant="ghost"
                        size="sm"
                        class="h-7 w-7 p-0 text-gray-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity"
                      >
                        <X class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Logout Button -->
          <Button
            @click="logout"
            variant="ghost"
            size="sm"
            class="text-red-600 hover:text-red-700 hover:bg-red-50 p-2"
            :disabled="logoutForm.processing"
          >
            <LogOut class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </header>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <!-- Layout Container (flex on desktop) -->
    <div class="lg:flex lg:h-screen">
      <!-- Mobile Sidebar -->
      <div
        class="fixed top-0 left-0 h-full bg-white shadow-lg border-r transition-transform duration-300 z-40 lg:hidden w-64"
        :class="[
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
      >
        <div class="flex flex-col h-full">
          <!-- Sidebar Header -->
          <div class="p-3 pt-4 border-b bg-primary text-primary-foreground">
            <div class="flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <h2 class="text-base font-semibold truncate">Waiter</h2>
                <p class="text-xs opacity-90 truncate">{{ employee.firstname }}</p>
                <p class="text-xs opacity-75">{{ employee.role.role_name }}</p>
              </div>
              <Button
                variant="ghost"
                size="sm"
                @click="sidebarOpen = false"
                class="text-primary-foreground hover:bg-primary-foreground/20 p-1"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <!-- Sidebar Navigation -->
          <nav class="flex-1 p-3 space-y-2">
            <Link
              :href="route('waiter.dashboard')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors touch-manipulation"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/Dashboard' }"
              @click="sidebarOpen = false"
            >
              <TableProperties class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Tables</span>
            </Link>

            <Link
              :href="route('waiter.current-menu')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors touch-manipulation"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/CurrentMenu' }"
              @click="sidebarOpen = false"
            >
              <ChefHat class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Current Menu</span>
            </Link>

            <Link
              :href="route('waiter.take-order')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors touch-manipulation"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/TakeOrder' }"
              @click="sidebarOpen = false"
            >
              <Users class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Take Order</span>
            </Link>
          </nav>

          <!-- Sidebar Footer -->
          <div class="p-3 border-t">
            <Button
              @click="logout"
              variant="ghost"
              class="w-full justify-start gap-2 px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 touch-manipulation"
              :disabled="logoutForm.processing"
            >
              <LogOut class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
            </Button>
          </div>
        </div>
      </div>

      <!-- Desktop Sidebar -->
      <div class="hidden lg:block lg:w-48 bg-white shadow-lg border-r">
        <div class="flex flex-col h-screen">
          <!-- Sidebar Header -->
          <div class="p-3 pt-6 border-b bg-primary text-primary-foreground">
            <div class="flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <h2 class="text-base font-semibold truncate">Waiter</h2>
                <p class="text-xs opacity-90 truncate">{{ employee.firstname }}</p>
                <p class="text-xs opacity-75">{{ employee.role.role_name }}</p>
              </div>
            </div>
          </div>

          <!-- Sidebar Navigation -->
          <nav class="flex-1 p-3 space-y-2">
            <Link
              :href="route('waiter.dashboard')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/Dashboard' }"
            >
              <TableProperties class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Tables</span>
            </Link>

            <Link
              :href="route('waiter.current-menu')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/CurrentMenu' }"
            >
              <ChefHat class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Current Menu</span>
            </Link>

            <Link
              :href="route('waiter.take-order')"
              class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors"
              :class="{ 'bg-gray-100': $page.component === 'Waiter/TakeOrder' }"
            >
              <Users class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">Take Order</span>
            </Link>
          </nav>

          <!-- Sidebar Footer -->
          <div class="p-3 border-t">
            <Button
              @click="logout"
              variant="ghost"
              class="w-full justify-start gap-2 px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50"
              :disabled="logoutForm.processing"
            >
              <LogOut class="h-4 w-4 flex-shrink-0" />
              <span class="text-sm">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
            </Button>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="flex-1 lg:flex lg:flex-col lg:min-w-0">
        <!-- Desktop Header (visible only on desktop) -->
        <header class="hidden lg:block bg-white shadow-sm border-b">
          <div class="flex items-center justify-between px-3 sm:px-4 h-12">
            <!-- Page Title -->
            <div class="flex-1">
              <h1 class="text-lg sm:text-xl font-semibold text-gray-900 truncate">
                <slot name="title">Dashboard</slot>
              </h1>
            </div>

            <!-- Notifications & User Info -->
            <div class="flex items-center gap-1 sm:gap-3">
              <!-- Notification Bell -->
              <div class="relative">
                <Button
                  @click="toggleNotifications"
                  variant="ghost"
                  size="sm"
                  class="p-2 relative"
                  :class="hasReadyOrders ? 'text-green-600 hover:text-green-700 hover:bg-green-50' : ''"
                >
                  <component :is="newNotifications ? BellRing : Bell" class="h-5 w-5" :class="newNotifications ? 'animate-pulse' : ''" />
                  <span
                    v-if="hasReadyOrders"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                  >
                    {{ readyOrdersList.length }}
                  </span>
                </Button>

                <!-- Notification Dropdown (Desktop) -->
                <div
                  v-if="showNotifications"
                  class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50 max-h-96 overflow-hidden flex flex-col"
                  @click.stop
                >
                  <div class="p-3 border-b bg-green-50 flex items-center justify-between">
                    <div>
                      <h3 class="font-semibold text-green-800">Ready to Serve</h3>
                      <p class="text-xs text-green-600">{{ readyOrdersList.length }} order{{ readyOrdersList.length !== 1 ? 's' : '' }} ready</p>
                    </div>
                    <Button
                      v-if="readyOrdersList.length > 0"
                      @click="clearAllNotifications"
                      variant="ghost"
                      size="sm"
                      class="text-xs h-7 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                      Clear All
                    </Button>
                  </div>

                  <div class="overflow-y-auto flex-1">
                    <div v-if="readyOrdersList.length === 0" class="p-4 text-center text-gray-500">
                      <Bell class="h-8 w-8 mx-auto mb-2 opacity-50" />
                      <p class="text-sm">No orders ready</p>
                    </div>

                    <div v-else class="divide-y">
                      <div
                        v-for="order in readyOrdersList"
                        :key="order.order_id"
                        class="p-3 hover:bg-gray-50 group"
                      >
                        <div class="flex items-center gap-3">
                          <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-green-700 font-bold text-sm">{{ order.table_number }}</span>
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm">{{ order.table_name }}</p>
                            <p class="text-xs text-gray-600">Order {{ order.order_number }}</p>
                            <p class="text-xs text-green-600 font-medium">Ready to serve!</p>
                          </div>
                          <Button
                            @click="removeNotification(order.order_id)"
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0 text-gray-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity"
                          >
                            <X class="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="text-right hidden md:block">
                <p class="text-sm font-medium text-gray-900">{{ employee.firstname }} {{ employee.lastname }}</p>
                <p class="text-xs text-gray-500">{{ employee.role.role_name }}</p>
              </div>
              <Button
                @click="logout"
                variant="ghost"
                size="sm"
                class="text-red-600 hover:text-red-700 hover:bg-red-50 p-2"
                :disabled="logoutForm.processing"
              >
                <LogOut class="h-4 w-4" />
                <span class="text-sm hidden sm:inline ml-2">{{ logoutForm.processing ? 'Logging out...' : 'Logout' }}</span>
              </Button>
            </div>
          </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 lg:overflow-y-auto pt-12 lg:pt-0">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>