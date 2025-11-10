<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { CreditCard, Users, Clock, Award, Calendar, Eye, History, Search, Filter } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Subscription Management',
    href: '/admin/subscriptions',
  },
];

interface Subscription {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  start_date: string;
  end_date: string;
  remaining_days: number;
  status: 'active' | 'archive';
  is_trial: boolean;
  plan_id: number;
}

interface SubscriptionHistory {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  plan_name: string;
  start_date: string;
  end_date: string;
  status: string;
  is_trial: boolean;
  created_at: string;
  amount?: number;
}

interface Stats {
  total: number;
  active: number;
  expired: number;
  trial: number;
}

const props = defineProps<{
  subscriptions: Subscription[];
  stats: Stats;
  subscriptionHistory?: SubscriptionHistory[];
}>();

const filterStatus = ref<string>('active');
const searchQuery = ref<string>('');
const extendDays = ref<number>(30);
const selectedSubscription = ref<Subscription | null>(null);

// History section state
const historySearchQuery = ref<string>('');
const historyStatusFilter = ref<string>('all');
const showHistory = ref<boolean>(false);

// View subscription state
const showViewDialog = ref<boolean>(false);
const viewingSubscription = ref<Subscription | null>(null);

const filteredSubscriptions = computed(() => {
  let filtered = props.subscriptions;

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(sub => sub.status === filterStatus.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(sub => 
      sub.user_name.toLowerCase().includes(query) ||
      sub.user_email.toLowerCase().includes(query)
    );
  }

  return filtered;
});

// Filtered subscription history
const filteredHistory = computed(() => {
  // Use subscriptionHistory if available, otherwise use all subscriptions as history
  const historyData = props.subscriptionHistory || props.subscriptions.map(sub => ({
    ...sub,
    plan_name: sub.is_trial ? 'Trial Plan' : 'Standard Plan',
    created_at: sub.start_date,
    amount: sub.is_trial ? 0 : 29.99
  }));
  
  // First filter: Always show only archived subscriptions for history
  let filtered = historyData.filter(sub => sub.status === 'archive');

  // Second filter: Apply status filter only if not 'all'
  if (historyStatusFilter.value !== 'all' && historyStatusFilter.value !== 'archive') {
    filtered = filtered.filter(sub => sub.status === historyStatusFilter.value);
  }

  // Third filter: Apply search query
  if (historySearchQuery.value) {
    const query = historySearchQuery.value.toLowerCase();
    filtered = filtered.filter(sub => 
      sub.user_name.toLowerCase().includes(query) ||
      sub.user_email.toLowerCase().includes(query) ||
      (sub.plan_name && sub.plan_name.toLowerCase().includes(query))
    );
  }

  return filtered.sort((a, b) => new Date(b.created_at || b.start_date).getTime() - new Date(a.created_at || a.start_date).getTime());
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusBadge = (status: string, isExpired: boolean = false) => {
  if (isExpired || status === 'archive') {
    return 'destructive';
  }
  return status === 'active' ? 'default' : 'secondary';
};

const isExpired = (endDate: string) => {
  return new Date(endDate) < new Date();
};

const extendSubscription = (subscription: Subscription) => {
  router.post(`/admin/subscriptions/${subscription.id}/extend`, {
    days: extendDays.value
  });
};

const toggleSubscription = (subscription: Subscription) => {
  const action = subscription.status === 'active' ? 'suspend' : 'activate';
  router.post(`/admin/subscriptions/${subscription.id}/${action}`);
};

// View subscription details
const viewSubscription = (subscription: Subscription) => {
  viewingSubscription.value = subscription;
  showViewDialog.value = true;
};
</script>

<template>
  <Head title="Subscription Management" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Subscriptions</CardTitle>
            <CreditCard class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active</CardTitle>
            <Users class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Expired</CardTitle>
            <Clock class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.expired }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Trial Users</CardTitle>
            <Award class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ stats.trial }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters and Search -->
      <Card>
        <CardHeader>
          <CardTitle>Subscription Management</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Label for="search">Search Users</Label>
              <Input 
                id="search"
                v-model="searchQuery" 
                placeholder="Search by name or email..." 
                class="max-w-sm"
              />
            </div>
            <div>
              <Label for="status">Filter by Status</Label>
              <select 
                id="status"
                v-model="filterStatus" 
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
              >
                <option value="active">Active</option>
                <option value="all">All Subscriptions</option>
                <option value="archive">Expired/Archived</option>
              </select>
            </div>
          </div>

          <!-- Subscriptions Table -->
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead>Days Left</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead class="text-center">Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="subscription in filteredSubscriptions"
                  :key="subscription.id"
                  class="hover:bg-muted/50"
                >
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ subscription.user_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ subscription.user_email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ formatDate(subscription.start_date) }}</TableCell>
                  <TableCell>{{ formatDate(subscription.end_date) }}</TableCell>
                  <TableCell>
                    <span :class="subscription.remaining_days <= 7 ? 'text-red-600 font-medium' : ''">
                      {{ subscription.remaining_days }} days
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(subscription.status, isExpired(subscription.end_date))">
                      {{ isExpired(subscription.end_date) ? 'Expired' : subscription.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ subscription.is_trial ? 'Trial' : 'Paid' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <div class="flex justify-center gap-2">
                      <!-- View Details -->
                      <Button
                        size="sm"
                        variant="outline"
                        @click="viewSubscription(subscription)"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>

                      <!-- Extend Subscription -->
                      <Dialog>
                        <DialogTrigger as-child>
                          <Button 
                            size="sm" 
                            variant="outline"
                            @click="selectedSubscription = subscription"
                          >
                            <Calendar class="h-4 w-4" />
                          </Button>
                        </DialogTrigger>
                        <DialogContent>
                          <DialogHeader>
                            <DialogTitle>Extend Subscription</DialogTitle>
                          </DialogHeader>
                          <div class="space-y-4">
                            <div>
                              <Label for="days">Extend by (days)</Label>
                              <Input
                                id="days"
                                v-model="extendDays"
                                type="number"
                                min="1"
                                max="365"
                                placeholder="30"
                              />
                            </div>
                            <Button 
                              @click="extendSubscription(selectedSubscription!)"
                              class="w-full"
                            >
                              Extend Subscription
                            </Button>
                          </div>
                        </DialogContent>
                      </Dialog>
                    </div>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredSubscriptions.length === 0">
                  <TableCell colspan="7" class="text-center py-4 text-muted-foreground">
                    No subscriptions found.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Subscription History Section -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <History class="h-5 w-5" />
              <CardTitle>Subscription History</CardTitle>
            </div>
            <Button 
              variant="outline" 
              @click="showHistory = !showHistory"
            >
              {{ showHistory ? 'Hide History' : 'Show History' }}
            </Button>
          </div>
        </CardHeader>
        
        <CardContent v-if="showHistory">
          <!-- History Filters -->
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Label for="history-search">Search History</Label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  id="history-search"
                  v-model="historySearchQuery" 
                  placeholder="Search by user, email, or plan..." 
                  class="pl-10"
                />
              </div>
            </div>
            <div>
              <Label for="history-status">Filter by Status</Label>
              <select 
                id="history-status"
                v-model="historyStatusFilter" 
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
              >
                <option value="all">All Archived History</option>
                <option value="archive">Archived/Expired Only</option>
              </select>
            </div>
          </div>

          <!-- History Table -->
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Plan</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Created</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="history in filteredHistory"
                  :key="`history-${history.id}`"
                  class="hover:bg-muted/50"
                >
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ history.user_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ history.user_email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{{ history.plan_name }}</Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(history.start_date) }}</TableCell>
                  <TableCell>{{ formatDate(history.end_date) }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(history.status)">
                      {{ history.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ history.is_trial ? 'Trial' : 'Paid' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <span v-if="history.amount">
                      ₱{{ history.amount.toFixed(2) }}
                    </span>
                    <span v-else class="text-muted-foreground">-</span>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ formatDate(history.created_at) }}
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredHistory.length === 0">
                  <TableCell colspan="8" class="text-center py-4 text-muted-foreground">
                    No subscription history found.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- View Subscription Dialog -->
    <Dialog v-model:open="showViewDialog">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Subscription Details</DialogTitle>
        </DialogHeader>
        
        <div v-if="viewingSubscription" class="space-y-6">
          <!-- User Information -->
          <div class="grid grid-cols-2 gap-4">
            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-sm">User Information</CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <div>
                  <Label class="text-xs text-muted-foreground">Name</Label>
                  <p class="font-medium">{{ viewingSubscription.user_name }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Email</Label>
                  <p class="font-medium">{{ viewingSubscription.user_email }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">User ID</Label>
                  <p class="font-medium">#{{ viewingSubscription.user_id }}</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-sm">Subscription Status</CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <div>
                  <Label class="text-xs text-muted-foreground">Current Status</Label>
                  <div class="mt-1">
                    <Badge :variant="getStatusBadge(viewingSubscription.status, isExpired(viewingSubscription.end_date))">
                      {{ isExpired(viewingSubscription.end_date) ? 'Expired' : viewingSubscription.status }}
                    </Badge>
                  </div>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Plan Type</Label>
                  <div class="mt-1">
                    <Badge variant="outline">
                      {{ viewingSubscription.is_trial ? 'Trial' : 'Paid' }}
                    </Badge>
                  </div>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Days Remaining</Label>
                  <p :class="viewingSubscription.remaining_days <= 7 ? 'text-red-600 font-medium' : 'font-medium'">
                    {{ viewingSubscription.remaining_days }} days
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Subscription Timeline -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm">Subscription Timeline</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label class="text-xs text-muted-foreground">Start Date</Label>
                  <p class="font-medium">{{ formatDate(viewingSubscription.start_date) }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">End Date</Label>
                  <p class="font-medium">{{ formatDate(viewingSubscription.end_date) }}</p>
                </div>
              </div>
              
              <!-- Progress Bar -->
              <div class="space-y-2">
                <Label class="text-xs text-muted-foreground">Subscription Progress</Label>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ 
                      width: `${Math.max(0, Math.min(100, 
                        ((new Date().getTime() - new Date(viewingSubscription.start_date).getTime()) / 
                         (new Date(viewingSubscription.end_date).getTime() - new Date(viewingSubscription.start_date).getTime())) * 100
                      ))}%` 
                    }"
                  ></div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button variant="outline" @click="showViewDialog = false">
              Close
            </Button>
            <Button @click="selectedSubscription = viewingSubscription; showViewDialog = false">
              <Calendar class="h-4 w-4 mr-2" />
              Extend
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayoutAdministrator>
</template>