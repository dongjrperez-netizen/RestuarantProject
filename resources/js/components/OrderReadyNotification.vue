<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bell, X, CheckCircle, AlertTriangle } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';
import { router } from '@inertiajs/vue3';

type NotificationType = 'ready' | 'voided' | 'info';

interface OrderNotification {
  id: string;
  orderId: number | string;
  orderNumber: string;
  tableName: string;
  tableNumber: string;
  tableId?: number;
  message: string;
  timestamp: string;
  read: boolean;
  type: NotificationType;
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
const addNotification = (data: any, type: NotificationType = 'ready') => {
  const notification: OrderNotification = {
    id: `${data.order?.order_id || data.orderId}-${Date.now()}`,
    orderId: data.order?.order_id || data.orderId,
    orderNumber: data.order?.order_number || data.orderNumber,
    tableName: data.table?.name || data.tableName || 'Unknown Table',
    tableNumber: data.table?.number || data.tableNumber || '?',
    tableId: data.table?.id || data.tableId,
    message: data.message,
    timestamp: data.timestamp || new Date().toISOString(),
    read: false,
    type: type,
  };

  notifications.value.unshift(notification);

  // Play notification sound
  playNotificationSound();

  // Auto-remove after 60 seconds if not manually removed (longer for important notifications)
  const autoRemoveDelay = type === 'voided' ? 60000 : 30000;
  setTimeout(() => {
    removeNotification(notification.id);
  }, autoRemoveDelay);
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

// Handle notification click - redirect to order details
const handleNotificationClick = (notification: OrderNotification) => {
  // Mark as read
  markAsRead(notification.id);

  // Close the panel
  showNotificationPanel.value = false;

  // If we have a table ID and order ID, open the table orders modal and highlight the order
  if (notification.tableId && notification.orderId) {
    console.log('Notification clicked, opening order:', notification.orderId, 'for table ID:', notification.tableId);

    // Use setTimeout to ensure the panel closes first, then trigger the event
    setTimeout(() => {
      // Trigger the table orders modal via a custom event with both tableId and orderId
      window.dispatchEvent(new CustomEvent('view-table-orders', {
        detail: {
          tableId: notification.tableId,
          orderId: notification.orderId,
          highlightOrder: true
        }
      }));
      console.log('Custom event dispatched for table ID:', notification.tableId, 'order ID:', notification.orderId);
    }, 100);
  }
};

// Get notification icon based on type
const getNotificationIcon = (type: NotificationType) => {
  switch (type) {
    case 'ready':
      return CheckCircle;
    case 'voided':
      return AlertTriangle;
    default:
      return Bell;
  }
};

// Get notification color based on type
const getNotificationColor = (type: NotificationType) => {
  switch (type) {
    case 'ready':
      return 'text-green-600';
    case 'voided':
      return 'text-yellow-600';
    default:
      return 'text-blue-600';
  }
};

// Get notification background color based on type
const getNotificationBgColor = (type: NotificationType, read: boolean) => {
  if (read) return '';
  switch (type) {
    case 'ready':
      return 'bg-green-50';
    case 'voided':
      return 'bg-yellow-50';
    default:
      return 'bg-blue-50';
  }
};

// Initialize Echo listener
let echoChannel: any = null;

onMounted(() => {
  if (window.Echo && props.restaurantId) {
    echoChannel = window.Echo.private(`restaurant.${props.restaurantId}.waiter`)
      .listen('.order.ready.to.serve', (data: any) => {
        console.log('Order ready notification received:', data);
        addNotification(data, 'ready');
      })
      .listen('.order.status.updated', (event: any) => {
        console.log('Order status updated notification received:', event);

        // Handle voided orders
        if (event.new_status === 'voided' && event.order) {
          const voidNotificationData = {
            order: event.order,
            orderId: event.order.order_id,
            orderNumber: event.order.order_number,
            tableName: event.order.table?.table_name || 'Unknown Table',
            tableNumber: event.order.table?.table_number || '?',
            tableId: event.order.table?.id,
            message: `Order ${event.order.order_number} has been voided by cashier`,
            timestamp: event.timestamp || new Date().toISOString(),
          };

          addNotification(voidNotificationData, 'voided');
        }
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
        <!-- Backdrop - Lightened -->
        <div
          @click="toggleNotificationPanel"
          class="fixed inset-0 bg-black bg-opacity-10 pointer-events-auto"
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
                class="p-4 transition-colors cursor-pointer relative"
                :class="[
                  getNotificationBgColor(notification.type, notification.read),
                  'hover:bg-gray-100'
                ]"
                @click="handleNotificationClick(notification)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <component
                        :is="getNotificationIcon(notification.type)"
                        :size="18"
                        :class="getNotificationColor(notification.type)"
                        class="flex-shrink-0"
                      />
                      <span class="font-semibold text-gray-900 truncate">
                        {{ notification.orderNumber }}
                      </span>
                      <span
                        v-if="!notification.read"
                        class="px-2 py-0.5 text-xs font-medium rounded-full"
                        :class="notification.type === 'voided' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'"
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
                    <p class="text-xs text-gray-400 mt-1 italic">Click to view order</p>
                  </div>
                  <button
                    @click.stop="removeNotification(notification.id)"
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
