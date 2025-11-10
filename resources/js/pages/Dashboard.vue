<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import BarChart from '@/components/ui/chart-bar/BarChart.vue';
import DonutChart from '@/components/ui/chart-donut/DonutChart.vue';
import { 
  DollarSign, 
  ShoppingCart, 
  Users, 
  TrendingUp, 
  Clock, 
  Package, 
  AlertTriangle,
  Plus,
  Eye,
  CheckCircle,
  XCircle,
  Calendar,
  BarChart3,
  Settings,
  UtensilsCrossed,
  Truck
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Interface definitions
interface DashboardStats {
  revenue: {
    today: number;
    thisMonth: number;
    growth: number;
  };
  orders: {
    today: number;
    pending: number;
    completed: number;
  };
  inventory: {
    lowStock: number;
    totalItems: number;
  };
  employees: {
    active: number;
    total: number;
  };
}

interface RecentOrder {
  id: number;
  orderNumber: string;
  customerName: string;
  total: number;
  status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled';
  items: number;
  time: string;
}

interface LowStockItem {
  id: number;
  name: string;
  currentStock: number;
  minStock: number;
  unit: string;
}

interface QuickAction {
  title: string;
  href: string;
  icon: any;
  color: string;
  description: string;
}

interface WeeklyDataItem {
  day: string;
  revenue: number;
  orders: number;
  'Revenue (₱)': number;
  'Orders': number;
}

interface OrderStatusItem {
  status: string;
  count: number;
  percentage: number;
}

// Props from backend
interface DashboardProps {
  stats: DashboardStats;
  recentOrders: RecentOrder[];
  lowStockItems: LowStockItem[];
  weeklyData: WeeklyDataItem[];
  orderStatusData: OrderStatusItem[];
}

const props = defineProps<DashboardProps>();

// Use real data from props
const stats = ref<DashboardStats>(props.stats);
const recentOrders = ref<RecentOrder[]>(props.recentOrders);
const lowStockItems = ref<LowStockItem[]>(props.lowStockItems);

const quickActions = ref<QuickAction[]>([
  {
    title: 'Menu Planning',
    href: '/menu-planning',
    icon: Plus,
    color: 'bg-blue-500 hover:bg-blue-600',
    description: 'Plan your weekly menu'
  },
  {
    title: 'Menu Management',
    href: '/menu',
    icon: UtensilsCrossed,
    color: 'bg-green-500 hover:bg-green-600',
    description: 'Manage menu items'
  },
  {
    title: 'Inventory',
    href: '/inventory',
    icon: Package,
    color: 'bg-purple-500 hover:bg-purple-600',
    description: 'Check stock levels'
  },
  {
    title: 'Purchase Orders',
    href: '/purchase-orders',
    icon: Truck,
    color: 'bg-orange-500 hover:bg-orange-600',
    description: 'Manage suppliers'
  }
]);

// Chart data from backend
const chartData = ref(props.weeklyData);
const donutData = ref(props.orderStatusData);

const getOrderStatusBadge = (status: string) => {
  switch (status) {
    case 'pending':
      return 'secondary';
    case 'preparing':
      return 'outline';
    case 'ready':
      return 'default';
    case 'completed':
      return 'default';
    case 'cancelled':
      return 'destructive';
    default:
      return 'outline';
  }
};

const getOrderStatusIcon = (status: string) => {
  switch (status) {
    case 'pending':
      return Clock;
    case 'preparing':
      return UtensilsCrossed;
    case 'ready':
      return CheckCircle;
    case 'completed':
      return CheckCircle;
    case 'cancelled':
      return XCircle;
    default:
      return Clock;
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const currentTime = ref(new Date().toLocaleString('en-US', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
}));

// Update time every minute
setInterval(() => {
  currentTime.value = new Date().toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}, 60000);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Welcome Section -->
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold">Welcome Back!</h1>
                <p class="text-muted-foreground">{{ currentTime }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Today's Revenue -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.revenue.today) }}</div>
                        <p class="text-xs text-muted-foreground">
                            <span class="text-green-600">+{{ stats.revenue.growth }}%</span> from yesterday
                        </p>
                    </CardContent>
                </Card>

                <!-- Today's Orders -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Orders</CardTitle>
                        <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.orders.today }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.orders.pending }} pending, {{ stats.orders.completed }} completed
                        </p>
                    </CardContent>
                </Card>

                <!-- Active Staff -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Staff</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.employees.active }}</div>
                        <p class="text-xs text-muted-foreground">
                            of {{ stats.employees.total }} total employees
                        </p>
                    </CardContent>
                </Card>

                <!-- Low Stock Alert -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Stock Alerts</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.inventory.lowStock }}</div>
                        <p class="text-xs text-muted-foreground">
                            items need restocking
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Link 
                            v-for="action in quickActions" 
                            :key="action.title"
                            :href="action.href"
                            class="flex flex-col items-center p-4 rounded-lg border-2 border-dashed border-muted-foreground/25 hover:border-muted-foreground/50 transition-colors"
                        >
                            <div :class="`p-3 rounded-full text-white mb-2 ${action.color}`">
                                <component :is="action.icon" class="h-6 w-6" />
                            </div>
                            <h3 class="font-medium text-center">{{ action.title }}</h3>
                            <p class="text-xs text-muted-foreground text-center">{{ action.description }}</p>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Analytics Charts -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Weekly Revenue Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <DollarSign class="h-5 w-5" />
                            Weekly Revenue
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            :data="chartData"
                            index="day"
                            :categories="['Revenue (₱)']"
                            :colors="['rgb(59, 130, 246)']"
                            :rounded-corners="4"
                            :y-formatter="(value) => {
                                return typeof value === 'number' ? `₱${value}` : `${value}`;
                            }"
                            class="h-64"
                        />
                    </CardContent>
                </Card>

                <!-- Weekly Orders Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ShoppingCart class="h-5 w-5" />
                            Weekly Orders
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            :data="chartData"
                            index="day"
                            :categories="['Orders']"
                            :colors="['rgb(16, 185, 129)']"
                            :rounded-corners="4"
                            :y-formatter="(value) => {
                                return `${value}`;
                            }"
                            class="h-64"
                        />
                    </CardContent>
                </Card>

            </div>

            <!-- Order Status Distribution -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5" />
                        Order Status Distribution
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <DonutChart
                                :data="donutData"
                                index="status"
                                category="count"
                                :colors="['rgb(34, 197, 94)', 'rgb(251, 191, 36)', 'rgb(59, 130, 246)', 'rgb(16, 185, 129)']"
                                :value-formatter="(value) => `${value} orders`"
                                class="h-64"
                            />
                        </div>
                        
                        <!-- Order Status Summary -->
                        <div class="space-y-4">
                            <h3 class="font-semibold">Status Breakdown</h3>
                            <div class="space-y-3">
                                <div v-for="item in donutData" :key="item.status" class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-3 h-3 rounded-full"
                                            :style="{
                                                backgroundColor: item.status === 'Completed' ? 'rgb(34, 197, 94)' :
                                                                 item.status === 'Pending' ? 'rgb(251, 191, 36)' :
                                                                 item.status === 'Preparing' ? 'rgb(59, 130, 246)' :
                                                                 'rgb(16, 185, 129)'
                                            }"
                                        ></div>
                                        <span class="text-sm">{{ item.status }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ item.count }}</span>
                                        <span class="text-xs text-muted-foreground">({{ item.percentage }}%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content Grid -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Recent Orders -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between p-3 rounded-lg border">
                                <div class="flex items-center gap-3">
                                    <component :is="getOrderStatusIcon(order.status)" class="h-4 w-4" />
                                    <div>
                                        <p class="font-medium">{{ order.orderNumber }}</p>
                                        <p class="text-sm text-muted-foreground">{{ order.customerName }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ formatCurrency(order.total) }}</p>
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="getOrderStatusBadge(order.status)" class="text-xs">
                                            {{ order.status }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">{{ order.time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Low Stock Items -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-red-600">Low Stock Items</CardTitle>
                        
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="item in lowStockItems" :key="item.id" class="flex items-center justify-between p-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20">
                                <div class="flex items-center gap-3">
                                    <AlertTriangle class="h-4 w-4 text-red-500" />
                                    <div>
                                        <p class="font-medium">{{ item.name }}</p>
                                        <p class="text-sm text-muted-foreground">Minimum: {{ item.minStock }} {{ item.unit }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-red-600">{{ item.currentStock }} {{ item.unit }}</p>
                                    <p class="text-xs text-red-500">Low stock</p>
                                </div>
                            </div>
                            <Button asChild class="w-full">
                                <Link href="/purchase-orders/create">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Create Purchase Order
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Monthly Overview -->
            <Card>
                <CardHeader>
                    <CardTitle>Monthly Overview</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">{{ formatCurrency(stats.revenue.thisMonth) }}</div>
                            <p class="text-sm text-muted-foreground">Total Revenue</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ stats.orders.today * 25 }}</div>
                            <p class="text-sm text-muted-foreground">Total Orders</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-purple-600">{{ stats.inventory.totalItems }}</div>
                            <p class="text-sm text-muted-foreground">Inventory Items</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>