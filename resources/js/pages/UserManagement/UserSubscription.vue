<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
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
  TableRow
} from '@/components/ui/table';
import {
  CreditCard,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Plus,
  RefreshCw,
  History,
  Clock
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/user-management',
    },
    {
        title: 'My Subscriptions',
        href: '/user-management/subscriptions',
    },
];

interface SubscriptionData {
  userSubscription_id: number;
  plan_name: string;
  plan_price: number;
  plan_duration: number;
  subscription_status: 'active' | 'expired' | 'cancelled' | 'suspended';
  subscription_startDate: string;
  subscription_endDate: string;
  remaining_days: number;
  remaining_time: string;
  is_expired: boolean;
  is_trial: boolean;
  created_at: string;
}

interface Stats {
  total_subscriptions: number;
  active_subscriptions: number;
  expired_subscriptions: number;
  trial_subscriptions: number;
  total_spent: number;
}

const props = defineProps<{
  currentSubscription: SubscriptionData | null;
  subscriptionHistory: SubscriptionData[];
  stats: Stats;
}>();

// Filter state for history
const statusFilter = ref<string>('all');

// Computed filtered subscription history
const filteredHistory = computed(() => {
  let filtered = props.subscriptionHistory;

  // Apply status filter
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(sub => sub.subscription_status === statusFilter.value);
  }

  return filtered;
});

// Utility functions
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'expired':
      return 'destructive';
    case 'cancelled':
      return 'secondary';
    case 'suspended':
      return 'outline';
    default:
      return 'secondary';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active':
      return CheckCircle;
    case 'expired':
      return XCircle;
    case 'cancelled':
      return XCircle;
    case 'suspended':
      return AlertTriangle;
    default:
      return Clock;
  }
};

const getRemainingDaysColor = (days: number) => {
  if (days < 0) return 'text-red-600';
  if (days <= 7) return 'text-orange-600';
  if (days <= 30) return 'text-yellow-600';
  return 'text-green-600';
};

// Handle renewal and upgrade actions
const renewSubscription = () => {
  window.location.href = '/user-management/subscriptions/renew';
};

const upgradeSubscription = () => {
  window.location.href = '/user-management/subscriptions/upgrade';
};
</script>

<template>
    <Head title="My Subscriptions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold">My Subscriptions</h1>
                <p class="text-muted-foreground">View your current subscription and subscription history</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Subscriptions</CardTitle>
                        <CreditCard class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.total_subscriptions }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ props.stats.active_subscriptions }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Expired</CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ props.stats.expired_subscriptions }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Current Subscription -->
            <Card v-if="props.currentSubscription" class="border-2 border-primary/20">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                <CreditCard class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <CardTitle class="text-xl">Current Subscription</CardTitle>
                                <p class="text-sm text-muted-foreground">Your active plan</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <component :is="getStatusIcon(props.currentSubscription.subscription_status)" class="h-5 w-5" />
                            <Badge :variant="getStatusBadge(props.currentSubscription.subscription_status)" class="text-sm">
                                {{ props.currentSubscription.subscription_status }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="grid md:grid-cols-2 gap-6">
                    <!-- Plan Details -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-lg">{{ props.currentSubscription.plan_name }}</h3>
                            <p class="text-2xl font-bold text-primary">{{ formatCurrency(props.currentSubscription.plan_price) }}</p>
                            <p class="text-sm text-muted-foreground">per {{ props.currentSubscription.plan_duration }} days</p>
                        </div>

                        <div v-if="props.currentSubscription.is_trial" class="p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <Clock class="h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-800">Trial Period</span>
                            </div>
                            <p class="text-xs text-blue-600 mt-1">You're currently on a trial subscription</p>
                        </div>
                    </div>

                    <!-- Subscription Timeline -->
                    <div class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Start Date:</span>
                                <span class="font-medium">{{ formatDate(props.currentSubscription.subscription_startDate) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">End Date:</span>
                                <span class="font-medium">{{ formatDate(props.currentSubscription.subscription_endDate) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Days Remaining:</span>
                                <span class="font-medium" :class="getRemainingDaysColor(props.currentSubscription.remaining_days)">
                                    {{ props.currentSubscription.remaining_time }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <Button variant="default" class="flex-1" @click="renewSubscription">
                                <RefreshCw class="h-4 w-4 mr-2" />
                                Renew Plan
                            </Button>
                            <Button variant="outline" class="flex-1" @click="upgradeSubscription">
                                Upgrade Plan
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Current Subscription -->
            <Card v-else class="border-2 border-dashed border-muted-foreground/25">
                <CardContent class="text-center py-12">
                    <CreditCard class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                    <h3 class="text-lg font-medium mb-2">No Active Subscription</h3>
                    <p class="text-muted-foreground mb-4">You don't have an active subscription at the moment.</p>
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        Subscribe Now
                    </Button>
                </CardContent>
            </Card>

            <!-- Subscription History -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <History class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <CardTitle>Subscription History</CardTitle>
                                <p class="text-sm text-muted-foreground">All your past and present subscriptions</p>
                            </div>
                        </div>
                    </div>
                    <!-- Filter -->
                    <div class="mt-4">
                        <select
                            v-model="statusFilter"
                            class="flex h-10 w-48 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="filteredHistory.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Plan</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Start Date</TableHead>
                                    <TableHead>End Date</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Type</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="subscription in filteredHistory" :key="subscription.userSubscription_id">
                                    <TableCell>
                                        <div class="font-medium">{{ subscription.plan_name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <component :is="getStatusIcon(subscription.subscription_status)" class="h-4 w-4" />
                                            <Badge :variant="getStatusBadge(subscription.subscription_status)">
                                                {{ subscription.subscription_status }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ formatDate(subscription.subscription_startDate) }}</TableCell>
                                    <TableCell>{{ formatDate(subscription.subscription_endDate) }}</TableCell>
                                    <TableCell>{{ subscription.plan_duration }} days</TableCell>
                                    <TableCell class="font-medium">{{ formatCurrency(subscription.plan_price) }}</TableCell>
                                    <TableCell>
                                        <Badge v-if="subscription.is_trial" variant="secondary" class="text-xs">
                                            Trial
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground">Paid</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <History class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                        <h3 class="text-lg font-medium mb-2">No subscription history found</h3>
                        <p class="text-muted-foreground">
                            {{ props.subscriptionHistory.length === 0 ? 'You haven\'t subscribed to any plans yet.' : 'Try adjusting your search or filter criteria.' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>