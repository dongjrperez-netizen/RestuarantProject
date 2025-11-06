<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import  Badge  from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import {
  ArrowLeft,
  Download,
  AlertTriangle,
  Package,
  TrendingDown,
  DollarSign,
  BarChart3,
  Calendar,
  Filter,
  User
} from 'lucide-vue-next';

interface WastageLog {
  id: number;
  type: string;
  quantity: number;
  unit: string;
  reason: string | null;
  notes: string | null;
  incident_date: string;
  estimated_cost: number | null;
  ingredient: {
    ingredient_name: string;
  };
  user: {
    firstname: string;
    lastname: string;
  };
}

interface IngredientSummary {
  count: number;
  total_quantity: number;
  total_cost: number;
}

interface TypeSummary {
  count: number;
  total_cost: number;
}

interface Summary {
  total_incidents: number;
  total_cost: number;
  damage_incidents: number;
  spoilage_incidents: number;
}

interface WastageData {
  logs: WastageLog[];
  by_ingredient: Record<string, IngredientSummary>;
  by_type: Record<string, TypeSummary>;
  summary: Summary;
}

interface Filters {
  date_from: string;
  date_to: string;
  type: string;
}

interface Props {
  wastageData: WastageData;
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Wastage & Spoilage', href: '/reports/wastage' },
];

const showFilters = ref(false);
const filterForm = ref({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
  type: props.filters.type,
});

const formatCurrency = (amount: number | null) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount || 0);
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};

const applyFilters = () => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, value);
  });

  router.get(`/reports/wastage?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const exportReport = (format: string) => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, value);
  });
  params.append('export', format);

  window.open(`/reports/wastage?${params.toString()}`);
};

const typeOptions = [
  { value: 'all', label: 'All Types' },
  { value: 'damage', label: 'Damage' },
  { value: 'spoilage', label: 'Spoilage' },
];

const getTypeVariant = (type: string) => {
  return type === 'damage' ? 'destructive' : 'secondary';
};

const topIngredientsByLoss = computed(() => {
  return Object.entries(props.wastageData.by_ingredient)
    .map(([name, data]) => ({ name, ...data }))
    .sort((a, b) => b.total_cost - a.total_cost)
    .slice(0, 10);
});

const costDistribution = computed(() => {
  const total = props.wastageData.summary.total_cost;
  return {
    damage: props.wastageData.by_type.damage ? (props.wastageData.by_type.damage.total_cost / total) * 100 : 0,
    spoilage: props.wastageData.by_type.spoilage ? (props.wastageData.by_type.spoilage.total_cost / total) * 100 : 0,
  };
});

const avgCostPerIncident = computed(() => {
  return props.wastageData.summary.total_incidents > 0
    ? props.wastageData.summary.total_cost / props.wastageData.summary.total_incidents
    : 0;
});
</script>

<template>
  <Head title="Wastage & Spoilage Report" />

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
            <h1 class="text-3xl font-bold tracking-tight">Wastage & Spoilage Analysis</h1>
            <p class="text-muted-foreground">Monitor waste, spoilage costs, and identify areas for improvement</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="showFilters = !showFilters">
            <Filter class="w-4 h-4 mr-2" />
            Filters
          </Button>
          <Select @update:model-value="(format) => exportReport(format)">
            <SelectTrigger class="w-32">
              <SelectValue placeholder="Export" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="pdf">
                <Download class="w-4 h-4 mr-2" />
                PDF
              </SelectItem>
              <SelectItem value="excel">
                <Download class="w-4 h-4 mr-2" />
                Excel
              </SelectItem>
              <SelectItem value="csv">
                <Download class="w-4 h-4 mr-2" />
                CSV
              </SelectItem>
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
              <Label>Type</Label>
              <Select v-model="filterForm.type">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in typeOptions"
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
                <p class="text-sm font-medium text-muted-foreground">Total Incidents</p>
                <p class="text-2xl font-bold text-red-600">{{ formatNumber(wastageData.summary.total_incidents) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Wastage events</p>
              </div>
              <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                <AlertTriangle class="h-4 w-4 text-red-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Cost</p>
                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(wastageData.summary.total_cost) }}</p>
                <p class="text-xs text-muted-foreground mt-1">Financial impact</p>
              </div>
              <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
                <DollarSign class="h-4 w-4 text-red-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Damage Incidents</p>
                <p class="text-2xl font-bold text-orange-600">{{ formatNumber(wastageData.summary.damage_incidents) }}</p>
                <p class="text-xs text-muted-foreground mt-1">
                  {{ wastageData.summary.total_incidents > 0 ? ((wastageData.summary.damage_incidents / wastageData.summary.total_incidents) * 100).toFixed(0) : 0 }}% of total
                </p>
              </div>
              <div class="h-8 w-8 bg-orange-100 rounded-full flex items-center justify-center">
                <Package class="h-4 w-4 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Spoilage Incidents</p>
                <p class="text-2xl font-bold text-purple-600">{{ formatNumber(wastageData.summary.spoilage_incidents) }}</p>
                <p class="text-xs text-muted-foreground mt-1">
                  {{ wastageData.summary.total_incidents > 0 ? ((wastageData.summary.spoilage_incidents / wastageData.summary.total_incidents) * 100).toFixed(0) : 0 }}% of total
                </p>
              </div>
              <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                <TrendingDown class="h-4 w-4 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Cost Analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Cost Distribution by Type</CardTitle>
            <CardDescription>Financial impact breakdown</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                  <span class="font-medium">Damage Costs</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-red-600">
                    {{ formatCurrency(wastageData.by_type.damage?.total_cost || 0) }}
                  </span>
                  <p class="text-xs text-muted-foreground">{{ costDistribution.damage.toFixed(1) }}%</p>
                </div>
              </div>

              <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                  <span class="font-medium">Spoilage Costs</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-purple-600">
                    {{ formatCurrency(wastageData.by_type.spoilage?.total_cost || 0) }}
                  </span>
                  <p class="text-xs text-muted-foreground">{{ costDistribution.spoilage.toFixed(1) }}%</p>
                </div>
              </div>

              <div class="border-t pt-3">
                <div class="flex items-center justify-between">
                  <span class="font-medium">Average Cost per Incident</span>
                  <span class="font-bold">{{ formatCurrency(avgCostPerIncident) }}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Incident Distribution</CardTitle>
            <CardDescription>Count breakdown by type</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-6">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Damage Incidents</span>
                  <span class="font-medium">{{ formatNumber(wastageData.summary.damage_incidents) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div
                    class="bg-red-500 h-3 rounded-full"
                    :style="{ width: `${wastageData.summary.total_incidents > 0 ? (wastageData.summary.damage_incidents / wastageData.summary.total_incidents) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Spoilage Incidents</span>
                  <span class="font-medium">{{ formatNumber(wastageData.summary.spoilage_incidents) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div
                    class="bg-purple-500 h-3 rounded-full"
                    :style="{ width: `${wastageData.summary.total_incidents > 0 ? (wastageData.summary.spoilage_incidents / wastageData.summary.total_incidents) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Top Affected Ingredients -->
      <Card>
        <CardHeader>
          <CardTitle>Most Affected Ingredients</CardTitle>
          <CardDescription>Ingredients with highest wastage costs</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="(item, index) in topIngredientsByLoss"
              :key="item.name"
              class="flex items-center justify-between p-4 border rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-8 h-8 bg-red-100 rounded-full">
                  <span class="text-sm font-bold text-red-600">{{ index + 1 }}</span>
                </div>
                <div>
                  <p class="font-medium">{{ item.name }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatNumber(item.count) }} incidents | {{ formatNumber(item.total_quantity) }} units lost
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold text-red-600">{{ formatCurrency(item.total_cost) }}</p>
                <p class="text-sm text-muted-foreground">Cost impact</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Detailed Incidents Log -->
      <Card>
        <CardHeader>
          <CardTitle>Incident Details</CardTitle>
          <CardDescription>Complete log of wastage and spoilage incidents</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="wastageData.logs.length === 0" class="text-center py-12">
            <AlertTriangle class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No incidents recorded</h3>
            <p class="text-muted-foreground">No wastage or spoilage incidents match your current filters.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-4 font-semibold">Date</th>
                  <th class="text-left p-4 font-semibold">Type</th>
                  <th class="text-left p-4 font-semibold">Ingredient</th>
                  <th class="text-left p-4 font-semibold">Quantity</th>
                  <th class="text-left p-4 font-semibold">Cost</th>
                  <th class="text-left p-4 font-semibold">Reason</th>
                  <th class="text-left p-4 font-semibold">Reported By</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="log in wastageData.logs"
                  :key="log.id"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-4">{{ formatDate(log.incident_date) }}</td>
                  <td class="p-4">
                    <Badge :variant="getTypeVariant(log.type)">
                      {{ log.type.charAt(0).toUpperCase() + log.type.slice(1) }}
                    </Badge>
                  </td>
                  <td class="p-4">
                    <div>
                      <p class="font-medium">{{ log.ingredient.ingredient_name }}</p>
                    </div>
                  </td>
                  <td class="p-4">{{ formatNumber(log.quantity) }} {{ log.unit }}</td>
                  <td class="p-4">{{ formatCurrency(log.estimated_cost) }}</td>
                  <td class="p-4">
                    <span class="text-sm" :title="log.reason || 'No reason provided'">
                      {{ log.reason ? (log.reason.length > 30 ? log.reason.substring(0, 30) + '...' : log.reason) : 'No reason' }}
                    </span>
                  </td>
                  <td class="p-4">
                    <div class="flex items-center space-x-2">
                      <User class="h-4 w-4 text-muted-foreground" />
                      <span class="text-sm">{{ log.user.firstname }} {{ log.user.lastname }}</span>
                    </div>
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