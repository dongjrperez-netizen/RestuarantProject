<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import OrderReadyNotification from '@/components/OrderReadyNotification.vue';
import {
  Menu,
  LogOut,
  Users,
  TableProperties,
  ChefHat,
  X
} from 'lucide-vue-next';

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
  user_id?: number;
}

interface Props {
  employee: Employee;
}

const props = defineProps<Props>();

const page = usePage();

// Get restaurant ID from employee's user_id or from page props
const restaurantId = computed(() => {
  return props.employee.user_id || (page.props as any).auth?.user?.id || null;
});

const sidebarOpen = ref(false);

const logoutForm = useForm({});

const logout = () => {
  logoutForm.post(route('logout'));
};
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

        <!-- Notification Bell & User Info -->
        <div class="flex items-center gap-2">
          <OrderReadyNotification v-if="restaurantId" :restaurant-id="restaurantId" />
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

            <!-- Notification Bell & User Info -->
            <div class="flex items-center gap-1 sm:gap-3">
              <div class="text-right hidden md:block">
                <p class="text-sm font-medium text-gray-900">{{ employee.firstname }} {{ employee.lastname }}</p>
                <p class="text-xs text-gray-500">{{ employee.role.role_name }}</p>
              </div>
              <OrderReadyNotification v-if="restaurantId" :restaurant-id="restaurantId" />
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