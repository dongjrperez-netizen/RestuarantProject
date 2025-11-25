<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { Progress } from '@/components/ui/progress';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import {
  ArrowLeft,
  Download,
  Package,
  Clock,
  CheckCircle,
  XCircle,
  DollarSign,
  TrendingUp,
  Calendar,
  Filter,
  Banknote
} from 'lucide-vue-next';

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  received_quantity: number;
  unit_of_measure: string;
  ingredient: {
    ingredient_name: string;
  };
}

interface PurchaseOrder {
  id: number;
  order_number: string;
  status: 'pending' | 'approved' | 'ordered' | 'received' | 'cancelled';
  total_amount: number;
  created_at: string;
  expected_delivery_date?: string;
  supplier?: {
    supplier_name: string;
  } | null;
  notes?: string;
  items?: PurchaseOrderItem[];
  created_by?: { name: string } | null;
  created_by_employee?: { full_name: string } | null;
  approved_by?: { name: string } | null;
}

interface Summary {
  total_orders: number;
  total_amount: number;
  pending_orders: number;
  completed_orders: number;
}

interface OrdersByStatus {
  [key: string]: number;
}

interface PurchaseData {
  orders: PurchaseOrder[];
  summary: Summary;
  orders_by_status: OrdersByStatus;
}

interface Filters {
  date_from: string;
  date_to: string;
  status: string;
}

interface Props {
  purchaseData: PurchaseData;
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Purchase Orders', href: '/reports/purchase-orders' },
];

const showFilters = ref(false);
const filterForm = ref({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
  status: props.filters.status,
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getSupplierName = (order: PurchaseOrder) => {
  if (order.supplier) {
    return order.supplier.supplier_name;
  }

  // Manual receive - extract from notes
  if (order.notes) {
    const match = order.notes.match(/Supplier:\s*([^|]+)/);
    if (match) {
      return match[1].trim() + ' (Manual)';
    }
  }

  return 'Unknown Supplier';
};

const applyFilters = () => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, String(value));
  });

  router.get(`/reports/purchase-orders?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const exportReport = (format: string | number | bigint | null) => {
  if (!format || (typeof format !== 'string' && typeof format !== 'number')) return;

  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, String(value));
  });
  params.append('export', String(format));

  window.open(`/reports/purchase-orders?${params.toString()}`);
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'pending':
      return Clock;
    case 'approved':
    case 'ordered':
      return Package;
    case 'received':
      return CheckCircle;
    case 'cancelled':
      return XCircle;
    default:
      return Package;
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending':
      return 'text-yellow-600';
    case 'approved':
    case 'ordered':
      return 'text-blue-600';
    case 'received':
      return 'text-green-600';
    case 'cancelled':
      return 'text-red-600';
    default:
      return 'text-gray-600';
  }
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'pending':
      return 'secondary';
    case 'approved':
    case 'ordered':
      return 'default';
    case 'received':
      return 'secondary';
    case 'cancelled':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const pendingOrders = computed(() => {
  return props.purchaseData.orders.filter(order => order.status === 'pending');
});

const recentOrders = computed(() => {
  return [...props.purchaseData.orders]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 10);
});

const completionRate = computed(() => {
  const { total_orders, completed_orders } = props.purchaseData.summary;
  return total_orders > 0 ? (completed_orders / total_orders) * 100 : 0;
});

const statusOptions = [
  { value: 'all', label: 'All Status' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'ordered', label: 'Ordered' },
  { value: 'received', label: 'Received' },
  { value: 'cancelled', label: 'Cancelled' },
];

const getReceivedItems = (order: PurchaseOrder) => {
  if (!order.items || order.items.length === 0) {
    return 'Not yet received';
  }

  const receivedItems = order.items
    .filter(item => item.received_quantity > 0)
    .map(item => {
      // Convert to number first, then format
      const qty = Number(item.received_quantity);
      const formattedQty = qty % 1 === 0 ? qty.toFixed(0) : qty.toFixed(2);
      return `${item.ingredient.ingredient_name} (${formattedQty} ${item.unit_of_measure})`;
    });

  return receivedItems.length > 0 ? receivedItems.join(', ') : 'Not yet received';
};
</script>

<template>
  <Head title="Purchase Orders Report" />

  <AppLayout :breadcrumbs="breadcrumbs">
     <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="router.get('/reports')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Reports
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Purchase Orders Report</h1>
            <p class="text-muted-foreground">Monitor purchase order status and supplier performance</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="showFilters = !showFilters">
            <Filter class="w-4 h-4 mr-2" />
            Filters
          </Button>
          <Select @update:model-value="(value: string | number | bigint | null) => exportReport(value)">
            <SelectTrigger class="w-32">
              <SelectValue placeholder="Export" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pdf">
                <Download class="w-4 h-4 mr-2" />
                PDF
              </SelectItem>
              <!-- <SelectItem value="excel">
                <Download class="w-4 h-4 mr-2" />
                Excel
              </SelectItem>
              <SelectItem value="csv">
                <Download class="w-4 h-4 mr-2" />
                CSV
              </SelectItem> -->
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Filters -->
      <Card v-if="showFilters">
        <CardHeader>
          <CardTitle>Report Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
              <Label>Date From</Label>
              <Input
                v-model="filterForm.date_from"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>
            <div class="space-y-2">
              <Label>Date To</Label>
              <Input
                v-model="filterForm.date_to"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>
            <div class="space-y-2">
              <Label>Status</Label>
              <Select v-model="filterForm.status">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="flex items-end">
              <Button @click="applyFilters" class="w-full">
                Apply Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                <p class="text-2xl font-bold">{{ formatNumber(purchaseData.summary.total_orders) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Purchase orders</p>
              </div>
              <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                <Package class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Amount</p>
                <p class="text-2xl font-bold">{{ formatCurrency(purchaseData.summary.total_amount) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Order value</p>
              </div>
              <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                <Banknote class="h-4 w-4 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Pending Orders</p>
                <p class="text-2xl font-bold text-yellow-600">{{ formatNumber(purchaseData.summary.pending_orders) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Awaiting action</p>
              </div>
              <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <Clock class="h-4 w-4 text-yellow-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Completion Rate</p>
                <p class="text-2xl font-bold">{{ completionRate.toFixed(1) }}%</p>
                <p class="text-xs text-muted-foreground mt-1">Orders completed</p>
              </div>
              <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                <TrendingUp class="h-4 w-4 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Status Distribution -->
      <Card>
        <CardHeader>
          <CardTitle>Order Status Distribution</CardTitle>
          <CardDescription>Breakdown of orders by status</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="(count, status) in purchaseData.orders_by_status"
              :key="status"
              class="flex items-center justify-between"
            >
              <div class="flex items-center space-x-2">
                <component :is="getStatusIcon(String(status))" :class="['w-4 h-4', getStatusColor(String(status))]" />
                <span class="text-sm font-medium capitalize">{{ status }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-muted-foreground">{{ count }} orders</span>
                <div class="w-24">
                  <Progress
                    :value="purchaseData.summary.total_orders > 0 ? (count / purchaseData.summary.total_orders) * 100 : 0"
                    class="h-2"
                  />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Pending Orders Alert -->
      <Card v-if="pendingOrders.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-yellow-600">
            <Clock class="h-5 w-5" />
            Pending Orders
          </CardTitle>
          <CardDescription>Orders that require immediate attention</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div
              v-for="order in pendingOrders"
              :key="order.id"
              class="flex items-center justify-between p-3 border border-yellow-200 rounded-lg bg-yellow-50"
            >
              <div>
                <p class="font-medium">{{ order.order_number }}</p>
                <p class="text-sm text-muted-foreground">
                  {{ getSupplierName(order) }} • {{ formatDate(order.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ formatCurrency(order.total_amount) }}</p>
                <Badge variant="secondary" class="bg-yellow-100 text-yellow-800">
                  {{ Math.floor((new Date().getTime() - new Date(order.created_at).getTime()) / (1000 * 60 * 60 * 24)) }} days
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Orders -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Purchase Orders</CardTitle>
          <CardDescription>Latest 10 purchase orders (sorted by newest first)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b text-xs">
                  <th class="text-left p-3 font-semibold">Order #</th>
                  <th class="text-left p-3 font-semibold">Supplier</th>
                  <th class="text-left p-3 font-semibold">Date</th>
                  <th class="text-left p-3 font-semibold">Created By</th>
                  <th class="text-left p-3 font-semibold">Status</th>
                  <th class="text-left p-3 font-semibold">Amount</th>
                  <th class="text-left p-3 font-semibold">Received Items</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in recentOrders"
                  :key="order.id"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-3 font-medium text-xs">{{ order.order_number }}</td>
                  <td class="p-3 text-xs">{{ getSupplierName(order) }}</td>
                  <td class="p-3 text-xs">{{ formatDate(order.created_at) }}</td>
                  <td class="p-3 text-xs">
                    {{ order.created_by_employee?.full_name ?? order.created_by?.name ?? 'N/A' }}
                  </td>
                  <td class="p-3 text-xs">
                    <Badge :variant="getStatusBadgeVariant(order.status)">
                      {{ order.status }}
                    </Badge>
                  </td>
                  <td class="p-3 text-xs">{{ formatCurrency(order.total_amount) }}</td>
                  <td class="p-3 text-[11px] text-muted-foreground">
                    {{ getReceivedItems(order) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- All Orders Table -->
      <Card>
        <CardHeader>
          <CardTitle>All Purchase Orders</CardTitle>
          <CardDescription>Complete list of purchase orders (sorted by newest first)</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b text-xs">
                  <th class="text-left p-3 font-semibold">Order #</th>
                  <th class="text-left p-3 font-semibold">Supplier</th>
                  <th class="text-left p-3 font-semibold">Date</th>
                  <th class="text-left p-3 font-semibold">Created By</th>
                  <th class="text-left p-3 font-semibold">Status</th>
                  <th class="text-left p-3 font-semibold">Amount</th>
                  <th class="text-left p-3 font-semibold">Received Items</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in purchaseData.orders"
                  :key="order.id"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-3 font-medium text-xs">{{ order.order_number }}</td>
                  <td class="p-3 text-xs">{{ getSupplierName(order) }}</td>
                  <td class="p-3 text-xs">{{ formatDate(order.created_at) }}</td>
                  <td class="p-3 text-xs">
                    {{ order.created_by_employee?.full_name ?? order.created_by?.name ?? 'N/A' }}
                  </td>
                  <td class="p-3 text-xs">
                    <Badge :variant="getStatusBadgeVariant(order.status)">
                      {{ order.status }}
                    </Badge>
                  </td>
                  <td class="p-3 text-xs">{{ formatCurrency(order.total_amount) }}</td>
                  <td class="p-3 text-[11px] text-muted-foreground">
                    {{ getReceivedItems(order) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>