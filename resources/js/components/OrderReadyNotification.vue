<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bell, X, CheckCircle } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';

interface OrderNotification {
  id: string;
  orderId: number;
  orderNumber: string;
  tableName: string;
  tableNumber: string;
  message: string;
  timestamp: string;
  read: boolean;
}

const props = defineProps<{
  restaurantId: number;
}>();

const notifications = ref<OrderNotification[]>([]);
const showNotificationPanel = ref(false);
const audioRef = ref<HTMLAudioElement | null>(null);

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.read).length;
});

const hasUnread = computed(() => unreadCount.value > 0);

// Add new notification
const addNotification = (data: any) => {
  const notification: OrderNotification = {
    id: `${data.order.order_id}-${Date.now()}`,
    orderId: data.order.order_id,
    orderNumber: data.order.order_number,
    tableName: data.table.name,
    tableNumber: data.table.number,
    message: data.message,
    timestamp: data.timestamp,
    read: false,
  };

  notifications.value.unshift(notification);

  // Play notification sound
  playNotificationSound();

  // Auto-remove after 30 seconds if not manually removed
  setTimeout(() => {
    removeNotification(notification.id);
  }, 30000);
};

// Mark notification as read
const markAsRead = (notificationId: string) => {
  const notification = notifications.value.find(n => n.id === notificationId);
  if (notification) {
    notification.read = true;
  }
};

// Mark all as read
const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true);
};

// Remove notification
const removeNotification = (notificationId: string) => {
  const index = notifications.value.findIndex(n => n.id === notificationId);
  if (index > -1) {
    notifications.value.splice(index, 1);
  }
};

// Clear all notifications
const clearAllNotifications = () => {
  notifications.value = [];
};

// Play notification sound
const playNotificationSound = () => {
  if (audioRef.value) {
    audioRef.value.currentTime = 0;
    audioRef.value.play().catch(error => {
      console.log('Could not play notification sound:', error);
    });
  }
};

// Toggle notification panel
const toggleNotificationPanel = () => {
  showNotificationPanel.value = !showNotificationPanel.value;
  if (showNotificationPanel.value) {
    markAllAsRead();
  }
};

// Format timestamp
const formatTimestamp = (timestamp: string) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (diff < 60) return 'Just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return date.toLocaleDateString();
};

// Initialize Echo listener
let echoChannel: any = null;

onMounted(() => {
  if (window.Echo && props.restaurantId) {
    echoChannel = window.Echo.private(`restaurant.${props.restaurantId}.waiter`)
      .listen('.order.ready.to.serve', (data: any) => {
        console.log('Order ready notification received:', data);
        addNotification(data);
      });

    console.log('Subscribed to waiter channel for restaurant:', props.restaurantId);
  } else {
    console.warn('Echo not available or restaurant ID missing');
  }
});

onUnmounted(() => {
  if (echoChannel) {
    window.Echo.leave(`restaurant.${props.restaurantId}.waiter`);
  }
});

defineExpose({
  addNotification,
  notifications,
  unreadCount,
});
</script>

<template>
  <div class="relative">
    <!-- Notification Bell Icon -->
    <button
      @click="toggleNotificationPanel"
      class="relative p-2 rounded-full hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
      :class="{ 'animate-pulse': hasUnread }"
    >
      <Bell :class="hasUnread ? 'text-primary' : 'text-gray-600'" :size="24" />
      <span
        v-if="hasUnread"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Panel -->
    <Teleport to="body">
      <div
        v-if="showNotificationPanel"
        class="fixed inset-0 z-50 flex items-start justify-end p-4 sm:p-6 pointer-events-none"
      >
        <!-- Backdrop -->
        <div
          @click="toggleNotificationPanel"
          class="fixed inset-0 bg-black bg-opacity-25 pointer-events-auto"
        ></div>

        <!-- Panel -->
        <div
          class="relative w-full max-w-sm bg-white rounded-lg shadow-2xl pointer-events-auto max-h-[calc(100vh-2rem)] flex flex-col"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b bg-gray-50 rounded-t-lg">
            <div class="flex items-center gap-2">
              <Bell :size="20" class="text-primary" />
              <h3 class="text-lg font-semibold text-gray-900">Order Notifications</h3>
              <span v-if="unreadCount > 0" class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                {{ unreadCount }}
              </span>
            </div>
            <button
              @click="toggleNotificationPanel"
              class="p-1 rounded-full hover:bg-gray-200 transition-colors"
            >
              <X :size="20" class="text-gray-600" />
            </button>
          </div>

          <!-- Notifications List -->
          <div class="flex-1 overflow-y-auto">
            <div v-if="notifications.length === 0" class="p-8 text-center">
              <Bell :size="48" class="mx-auto mb-4 text-gray-300" />
              <p class="text-gray-500">No notifications yet</p>
              <p class="text-sm text-gray-400 mt-1">You'll be notified when orders are ready</p>
            </div>

            <div v-else class="divide-y">
              <div
                v-for="notification in notifications"
                :key="notification.id"
                class="p-4 hover:bg-gray-50 transition-colors"
                :class="{ 'bg-blue-50': !notification.read }"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <CheckCircle :size="18" class="text-green-600 flex-shrink-0" />
                      <span class="font-semibold text-gray-900 truncate">
                        {{ notification.orderNumber }}
                      </span>
                      <span
                        v-if="!notification.read"
                        class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                      >
                        New
                      </span>
                    </div>
                    <p class="text-sm text-gray-700 mb-2">{{ notification.message }}</p>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                      <span class="font-medium">{{ notification.tableName }}</span>
                      <span class="text-gray-400">•</span>
                      <span>Table #{{ notification.tableNumber }}</span>
                      <span class="text-gray-400">•</span>
                      <span>{{ formatTimestamp(notification.timestamp) }}</span>
                    </div>
                  </div>
                  <button
                    @click="removeNotification(notification.id)"
                    class="p-1 rounded-full hover:bg-gray-200 transition-colors flex-shrink-0"
                    title="Dismiss"
                  >
                    <X :size="16" class="text-gray-400" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div v-if="notifications.length > 0" class="p-4 border-t bg-gray-50 rounded-b-lg">
            <div class="flex gap-2">
              <Button
                @click="markAllAsRead"
                variant="outline"
                size="sm"
                class="flex-1 text-xs"
                :disabled="!hasUnread"
              >
                Mark all as read
              </Button>
              <Button
                @click="clearAllNotifications"
                variant="outline"
                size="sm"
                class="flex-1 text-xs text-red-600 hover:text-red-700"
              >
                Clear all
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Notification Sound -->
    <audio ref="audioRef" preload="auto">
      <!-- Using a simple notification sound data URL (short beep) -->
      <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQkTY7jp66lVFApGnuDyvmwhBzbK4PTIdysFIHfB79yPOQ==" type="audio/wav">
    </audio>
  </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
