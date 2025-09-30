<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { AlertTriangle, Plus, Search, Calendar, Package, Eye, Edit, Trash2, Filter } from 'lucide-vue-next';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
}

interface User {
  id: number;
  name: string;
}

interface DamageSpoilageLog {
  id: number;
  type: string;
  quantity: number;
  unit: string;
  reason: string | null;
  notes: string | null;
  incident_date: string;
  estimated_cost: number | null;
  created_at: string;
  ingredient: {
    ingredient_name: string;
  };
  user: {
    name: string;
  };
}

interface Props {
  logs: {
    data: DamageSpoilageLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: any[];
  };
  ingredients: Ingredient[];
  types: Record<string, string>;
  filters: {
    type?: string;
    ingredient_id?: string;
    date_from?: string;
    date_to?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Damage & Spoilage', href: '/damage-spoilage' },
];

// Filter state
const filterForm = ref({
  type: props.filters.type || '',
  ingredient_id: props.filters.ingredient_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
});

const showFilters = ref(false);

const applyFilters = () => {
  const params = Object.fromEntries(
    Object.entries(filterForm.value).filter(([_, value]) => value !== '')
  );

  router.get('/damage-spoilage', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  filterForm.value = {
    type: '',
    ingredient_id: '',
    date_from: '',
    date_to: '',
  };
  router.get('/damage-spoilage', {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const deleteLog = (id: number) => {
  if (confirm('Are you sure you want to delete this damage/spoilage log?')) {
    router.delete(`/damage-spoilage/${id}`, {
      preserveScroll: true,
    });
  }
};

const getTypeVariant = (type: string) => {
  return type === 'damage' ? 'destructive' : 'secondary';
};

const formatCurrency = (amount: number | null) => {
  return amount ? `$${amount.toFixed(2)}` : 'N/A';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const hasActiveFilters = computed(() => {
  return Object.values(filterForm.value).some(value => value !== '');
});
</script>

<template>
  <Head title="Damage & Spoilage Logs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-6 px-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Damage & Spoilage Logs</h1>
          <p class="text-muted-foreground">Track and manage ingredient damage and spoilage incidents</p>
        </div>
        <Link href="/damage-spoilage/create">
          <Button class="bg-orange-600 hover:bg-orange-700">
            <Plus class="w-4 h-4 mr-2" />
            Report Incident
          </Button>
        </Link>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-2">
              <Filter class="w-5 h-5" />
              Filters
              <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2">
                {{ Object.values(filterForm).filter(v => v).length }} active
              </Badge>
            </CardTitle>
            <Button
              variant="ghost"
              size="sm"
              @click="showFilters = !showFilters"
            >
              {{ showFilters ? 'Hide' : 'Show' }} Filters
            </Button>
          </div>
        </CardHeader>
        <CardContent v-if="showFilters" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Type Filter -->
            <div class="space-y-2">
              <Label>Type</Label>
              <Select v-model="filterForm.type">
                <SelectTrigger>
                  <SelectValue placeholder="All types" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Types</SelectItem>
                  <SelectItem
                    v-for="(label, value) in types"
                    :key="value"
                    :value="value"
                  >
                    {{ label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Ingredient Filter -->
            <div class="space-y-2">
              <Label>Ingredient</Label>
              <Select v-model="filterForm.ingredient_id">
                <SelectTrigger>
                  <SelectValue placeholder="All ingredients" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Ingredients</SelectItem>
                  <SelectItem
                    v-for="ingredient in ingredients"
                    :key="ingredient.ingredient_id"
                    :value="ingredient.ingredient_id.toString()"
                  >
                    {{ ingredient.ingredient_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Date From -->
            <div class="space-y-2">
              <Label>Date From</Label>
              <Input
                v-model="filterForm.date_from"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>

            <!-- Date To -->
            <div class="space-y-2">
              <Label>Date To</Label>
              <Input
                v-model="filterForm.date_to"
                type="date"
                :max="new Date().toISOString().split('T')[0]"
              />
            </div>
          </div>

          <div class="flex gap-2">
            <Button @click="applyFilters" size="sm">
              <Search class="w-4 h-4 mr-2" />
              Apply Filters
            </Button>
            <Button @click="clearFilters" variant="outline" size="sm" v-if="hasActiveFilters">
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Summary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6" v-if="logs.data.length > 0">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <AlertTriangle class="w-8 h-8 text-red-600" />
              <div class="ml-4">
                <p class="text-sm font-medium text-muted-foreground">Total Damage</p>
                <p class="text-2xl font-bold">
                  {{ logs.data.filter(log => log.type === 'damage').length }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <Package class="w-8 h-8 text-orange-600" />
              <div class="ml-4">
                <p class="text-sm font-medium text-muted-foreground">Total Spoilage</p>
                <p class="text-2xl font-bold">
                  {{ logs.data.filter(log => log.type === 'spoilage').length }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <Calendar class="w-8 h-8 text-blue-600" />
              <div class="ml-4">
                <p class="text-sm font-medium text-muted-foreground">This Page</p>
                <p class="text-2xl font-bold">{{ logs.data.length }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Logs Table -->
      <Card>
        <CardHeader>
          <CardTitle>Incident Reports</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="logs.data.length === 0" class="text-center py-12">
            <AlertTriangle class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No incidents recorded</h3>
            <p class="text-muted-foreground mb-4">
              {{ hasActiveFilters ? 'No incidents match your current filters.' : 'Start by reporting your first damage or spoilage incident.' }}
            </p>
            <Link href="/damage-spoilage/create" v-if="!hasActiveFilters">
              <Button class="bg-orange-600 hover:bg-orange-700">
                <Plus class="w-4 h-4 mr-2" />
                Report First Incident
              </Button>
            </Link>
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
                  <th class="text-left p-4 font-semibold">Reported By</th>
                  <th class="text-left p-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="log in logs.data"
                  :key="log.id"
                  class="border-b hover:bg-muted/50"
                >
                  <td class="p-4">
                    <div class="text-sm">
                      <div class="font-medium">{{ formatDate(log.incident_date) }}</div>
                      <div class="text-muted-foreground">{{ formatDate(log.created_at) }}</div>
                    </div>
                  </td>
                  <td class="p-4">
                    <Badge :variant="getTypeVariant(log.type)">
                      {{ types[log.type] }}
                    </Badge>
                  </td>
                  <td class="p-4">
                    <div class="font-medium">{{ log.ingredient.ingredient_name }}</div>
                    <div class="text-sm text-muted-foreground" v-if="log.reason">
                      {{ log.reason.substring(0, 50) }}{{ log.reason.length > 50 ? '...' : '' }}
                    </div>
                  </td>
                  <td class="p-4">
                    <div class="font-medium">{{ log.quantity }} {{ log.unit }}</div>
                  </td>
                  <td class="p-4">
                    <div class="font-medium">{{ formatCurrency(log.estimated_cost) }}</div>
                  </td>
                  <td class="p-4">
                    <div class="text-sm">{{ log.user.name }}</div>
                  </td>
                  <td class="p-4">
                    <div class="flex gap-2">
                      <Link :href="`/damage-spoilage/${log.id}`">
                        <Button variant="ghost" size="sm">
                          <Eye class="w-4 h-4" />
                        </Button>
                      </Link>
                      <Link :href="`/damage-spoilage/${log.id}/edit`">
                        <Button variant="ghost" size="sm">
                          <Edit class="w-4 h-4" />
                        </Button>
                      </Link>
                      <Button
                        variant="ghost"
                        size="sm"
                        @click="deleteLog(log.id)"
                        class="text-red-600 hover:text-red-700"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="logs.last_page > 1" class="flex items-center justify-between mt-6">
            <div class="text-sm text-muted-foreground">
              Showing {{ ((logs.current_page - 1) * logs.per_page) + 1 }} to
              {{ Math.min(logs.current_page * logs.per_page, logs.total) }} of
              {{ logs.total }} results
            </div>
            <div class="flex gap-2">
              <Button
                v-for="link in logs.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                @click="link.url && router.get(link.url)"
                v-html="link.label"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>