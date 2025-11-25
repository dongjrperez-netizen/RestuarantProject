<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { Calendar, CalendarDays, Eye, Edit2, Trash2, Play, Pause, Plus } from 'lucide-vue-next';

interface MenuPlan {
  menu_plan_id: number;
  plan_name: string;
  plan_type: 'daily' | 'weekly';
  start_date: string;
  end_date: string;
  description?: string;
  is_active: boolean;
  is_default: boolean;
  dishes_count?: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  menuPlans: {
    data: MenuPlan[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Menu Planning', href: '/menu-planning' },
];

const getStatusBadgeVariant = (plan: MenuPlan) => {
  if (plan.is_active) return 'default';

  // Check if plan is expired (archived)
  const endDate = new Date(plan.end_date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (endDate < today) {
    return 'destructive'; // Red for archived/expired
  }

  return 'secondary'; // Gray for manually inactive
};

const getStatusText = (plan: MenuPlan) => {
  if (plan.is_active) return 'Active';

  // Check if plan is expired (archived)
  const endDate = new Date(plan.end_date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (endDate < today) {
    return 'Archived';
  }

  return 'Inactive';
};

const getPlanTypeBadgeVariant = (planType: string) => {
  return planType === 'daily' ? 'outline' : 'secondary';
};

const formatDate = (dateString: string | null) => {
  if (!dateString) return '';
  const d = new Date(dateString);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleDateString();
};

const togglePlanStatus = (plan: MenuPlan) => {
  router.post(`/menu-planning/${plan.menu_plan_id}/toggle-active`, {}, {
    preserveState: true,
  });
};

const deletePlan = (plan: MenuPlan) => {
  if (confirm(`Are you sure you want to delete "${plan.plan_name}"? This action cannot be undone.`)) {
    router.delete(`/menu-planning/${plan.menu_plan_id}`, {
      preserveState: true,
    });
  }
};
</script>

<template>
  <Head title="Menu Planning" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Menu Planning</h1>
          <p class="text-muted-foreground">Plan your daily and weekly menus</p>
        </div>
        <Link href="/menu-planning/create">
          <Button>
            <Plus class="w-4 h-4 mr-2" />
            Create Menu Plan
          </Button>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-3 md:grid-cols-4">
      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Total Plans</p>
          <Calendar class="h-4 w-4 text-muted-foreground" />
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ menuPlans.total || 0 }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Active Plans</p>
          <Play class="h-4 w-4 text-muted-foreground" />
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ (menuPlans.data || []).filter(plan => plan.is_active).length }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Daily Plans</p>
          <Calendar class="h-4 w-4 text-muted-foreground" />
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ (menuPlans.data || []).filter(plan => plan.plan_type === 'daily').length }}
        </div>
      </Card>

      <Card class="h-24 flex flex-col justify-between p-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-muted-foreground">Weekly Plans</p>
          <CalendarDays class="h-4 w-4 text-muted-foreground" />
        </div>
        <div class="text-xl font-bold leading-tight">
          {{ (menuPlans.data || []).filter(plan => plan.plan_type === 'weekly').length }}
        </div>
      </Card>
    </div>


      <!-- Menu Plans Table -->
      <Card>
        <CardHeader>
          <CardTitle>Menu Plans</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Plan Name</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Date Range</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="plan in (menuPlans.data || [])" :key="plan.menu_plan_id">
                <TableCell class="font-medium">
                  <div>
                    <div class="font-semibold">{{ plan.plan_name }}</div>
                    <div v-if="plan.description" class="text-sm text-muted-foreground max-w-xs">
                      {{ plan.description.substring(0, 80) }}{{ plan.description.length > 80 ? '...' : '' }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getPlanTypeBadgeVariant(plan.plan_type)">
                    {{ plan.plan_type.charAt(0).toUpperCase() + plan.plan_type.slice(1) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="text-sm" v-if="plan.start_date && plan.end_date && !plan.is_default">
                    <div>{{ formatDate(plan.start_date) }}</div>
                    <div class="text-muted-foreground">to {{ formatDate(plan.end_date) }}</div>
                  </div>
                  <div v-else class="text-sm text-muted-foreground">
                    Default plan (no date range)
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(plan)">
                    {{ getStatusText(plan) }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1">
                    <!-- View Button -->
                    <Link :href="`/menu-planning/${plan.menu_plan_id}`">
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                        title="View Plan"
                      >
                        <Eye class="w-4 h-4" />
                      </Button>
                    </Link>

                    <!-- Edit Button -->
                    <Link :href="`/menu-planning/${plan.menu_plan_id}/edit`">
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0 text-gray-600 hover:text-gray-700 hover:bg-gray-50"
                        title="Edit Plan"
                      >
                        <Edit2 class="w-4 h-4" />
                      </Button>
                    </Link>

                    <!-- Toggle Active/Inactive Button -->
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="togglePlanStatus(plan)"
                      class="h-8 w-8 p-0 hover:bg-green-50"
                      :class="plan.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'"
                      :title="plan.is_active ? 'Deactivate' : 'Activate'"
                    >
                      <Play v-if="!plan.is_active" class="w-4 h-4" />
                      <Pause v-else class="w-4 h-4" />
                    </Button>

                    <!-- Delete Button -->
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="deletePlan(plan)"
                      class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                      title="Delete Plan"
                    >
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="!menuPlans.data || menuPlans.data.length === 0">
                <TableCell colspan="5" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <Calendar class="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
                    <div class="text-lg mb-2">No menu plans found</div>
                    <div class="text-sm">Get started by creating your first menu plan.</div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>