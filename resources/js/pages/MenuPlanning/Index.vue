<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { Calendar, CalendarDays, Eye, Edit2, Trash2, Play, Pause, Plus } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';

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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
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
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Plans</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ menuPlans.total || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Plans</CardTitle>
            <Play class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ (menuPlans.data || []).filter(plan => plan.is_active).length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Daily Plans</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ (menuPlans.data || []).filter(plan => plan.plan_type === 'daily').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Weekly Plans</CardTitle>
            <CalendarDays class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ (menuPlans.data || []).filter(plan => plan.plan_type === 'weekly').length }}
            </div>
          </CardContent>
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
                <TableHead>Dishes</TableHead>
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
                  <div class="text-sm">
                    <div>{{ formatDate(plan.start_date) }}</div>
                    <div class="text-muted-foreground">to {{ formatDate(plan.end_date) }}</div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(plan)">
                    {{ getStatusText(plan) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <span class="text-sm font-medium">{{ plan.dishes_count || 0 }} dishes</span>
                </TableCell>
                <TableCell class="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <span class="sr-only">Open menu</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zM12 13a1 1 0 110-2 1 1 0 010 2zM12 20a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem as-child>
                        <Link :href="`/menu-planning/${plan.menu_plan_id}`" class="flex items-center">
                          <Eye class="mr-2 h-4 w-4" />
                          View Plan
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem as-child>
                        <Link :href="`/menu-planning/${plan.menu_plan_id}/edit`" class="flex items-center">
                          <Edit2 class="mr-2 h-4 w-4" />
                          Edit Plan
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        @click="togglePlanStatus(plan)"
                        class="flex items-center"
                      >
                        <Play v-if="!plan.is_active" class="mr-2 h-4 w-4" />
                        <Pause v-else class="mr-2 h-4 w-4" />
                        {{ plan.is_active ? 'Deactivate' : 'Activate' }}
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        @click="deletePlan(plan)"
                        class="flex items-center text-destructive focus:text-destructive"
                      >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="!menuPlans.data || menuPlans.data.length === 0">
                <TableCell colspan="6" class="text-center py-8">
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