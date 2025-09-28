<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Users, CheckCircle, Clock, XCircle, MoreVertical, Eye, Settings, Trash2, RefreshCw, Mail, Search, Edit, UserCheck, UserX, Shield } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'User Management',
    href: '/admin/users',
  },
];

interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  status: 'Pending' | 'Approved' | 'Rejected';
  email_verified: boolean;
  restaurant_name: string;
  subscription_status: string;
  created_at: string;
  last_login: string;
}

interface Stats {
  total: number;
  approved: number;
  pending: number;
  rejected: number;
}

const props = defineProps<{
  users: User[];
  stats: Stats;
}>();

const filterStatus = ref<string>('all');
const searchQuery = ref<string>('');

// View user dialog state
const showViewDialog = ref<boolean>(false);
const viewingUser = ref<User | null>(null);

// Loading states
const loadingActions = ref<Record<string, boolean>>({});

const filteredUsers = computed(() => {
  let filtered = props.users;

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(user => user.status === filterStatus.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(user => 
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query) ||
      user.restaurant_name.toLowerCase().includes(query)
    );
  }

  return filtered;
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'Approved':
      return 'default';
    case 'Pending':
      return 'secondary';
    case 'Rejected':
      return 'destructive';
    default:
      return 'outline';
  }
};

const getSubscriptionBadge = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'archive':
      return 'destructive';
    case 'none':
      return 'outline';
    default:
      return 'secondary';
  }
};

const updateUserStatus = async (userId: number, status: string) => {
  const actionKey = `status_${userId}`;
  loadingActions.value[actionKey] = true;
  
  try {
    router.post(`/admin/users/${userId}/status`, { status }, {
      onFinish: () => {
        loadingActions.value[actionKey] = false;
      }
    });
  } catch (error) {
    loadingActions.value[actionKey] = false;
  }
};

const toggleEmailVerification = async (userId: number) => {
  const actionKey = `email_${userId}`;
  loadingActions.value[actionKey] = true;
  
  try {
    router.post(`/admin/users/${userId}/toggle-email`, {}, {
      onFinish: () => {
        loadingActions.value[actionKey] = false;
      }
    });
  } catch (error) {
    loadingActions.value[actionKey] = false;
  }
};

const resetPassword = async (userId: number) => {
  const actionKey = `reset_${userId}`;
  loadingActions.value[actionKey] = true;
  
  try {
    router.post(`/admin/users/${userId}/reset-password`, {}, {
      onFinish: () => {
        loadingActions.value[actionKey] = false;
      }
    });
  } catch (error) {
    loadingActions.value[actionKey] = false;
  }
};

const deleteUser = async (userId: number) => {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    const actionKey = `delete_${userId}`;
    loadingActions.value[actionKey] = true;
    
    try {
      router.delete(`/admin/users/${userId}`, {
        onFinish: () => {
          loadingActions.value[actionKey] = false;
        }
      });
    } catch (error) {
      loadingActions.value[actionKey] = false;
    }
  }
};

// View user details
const viewUser = (user: User) => {
  viewingUser.value = user;
  showViewDialog.value = true;
};

// Edit user
const editUser = (user: User) => {
  router.visit(`/admin/users/${user.id}/edit`);
};

// Send email to user
const sendEmail = (user: User) => {
  router.visit(`/admin/users/${user.id}/send-email`);
};
</script>

<template>
  <Head title="User Management" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Clock class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Users Table -->
      <Card>
        <CardHeader>
          <CardTitle>User Management</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4 mb-4">
            <div class="flex-1">
              <Label for="search">Search Users</Label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  id="search"
                  v-model="searchQuery" 
                  placeholder="Search by name, email, or restaurant..." 
                  class="pl-10 max-w-sm"
                />
              </div>
            </div>
            <div>
              <Label for="status">Filter by Status</Label>
              <select 
                id="status"
                v-model="filterStatus" 
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
              >
                <option value="all">All Users</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>
          </div>

          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Restaurant</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Email Status</TableHead>
                  <TableHead>Subscription</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead class="text-center">Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="hover:bg-muted/50"
                >
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ user.name }}</div>
                      <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                      <div class="text-sm text-muted-foreground">{{ user.phone }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ user.restaurant_name }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(user.status)">
                      {{ user.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Badge :variant="user.email_verified ? 'default' : 'secondary'">
                        {{ user.email_verified ? 'Verified' : 'Unverified' }}
                      </Badge>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getSubscriptionBadge(user.subscription_status)">
                      {{ user.subscription_status || 'None' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                  <TableCell class="text-center">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreVertical class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <!-- View Details -->
                        <DropdownMenuItem 
                          @click="viewUser(user)"
                          class="flex items-center gap-2"
                        >
                          <Eye class="h-4 w-4" />
                          View Details
                        </DropdownMenuItem>
                        
                        <!-- Edit User -->
                        <DropdownMenuItem 
                          @click="editUser(user)"
                          class="flex items-center gap-2"
                        >
                          <Edit class="h-4 w-4" />
                          Edit User
                        </DropdownMenuItem>
                        
                        <!-- Send Email -->
                        <DropdownMenuItem 
                          @click="sendEmail(user)"
                          class="flex items-center gap-2"
                        >
                          <Mail class="h-4 w-4" />
                          Send Email
                        </DropdownMenuItem>
                        
                        <!-- Approve (for pending users) -->
                        <DropdownMenuItem 
                          v-if="user.status === 'Pending'"
                          @click="updateUserStatus(user.id, 'Approved')"
                          class="flex items-center gap-2"
                          :disabled="loadingActions[`status_${user.id}`]"
                        >
                          <CheckCircle class="h-4 w-4" />
                          {{ loadingActions[`status_${user.id}`] ? 'Approving...' : 'Approve' }}
                        </DropdownMenuItem>
                        
                        <!-- Reject (for pending users) -->
                        <DropdownMenuItem 
                          v-if="user.status === 'Pending'"
                          @click="updateUserStatus(user.id, 'Rejected')"
                          class="flex items-center gap-2"
                          :disabled="loadingActions[`status_${user.id}`]"
                        >
                          <XCircle class="h-4 w-4" />
                          {{ loadingActions[`status_${user.id}`] ? 'Rejecting...' : 'Reject' }}
                        </DropdownMenuItem>
                        
                        <!-- Reactivate (for rejected users) -->
                        <DropdownMenuItem 
                          v-if="user.status === 'Rejected'"
                          @click="updateUserStatus(user.id, 'Pending')"
                          class="flex items-center gap-2"
                          :disabled="loadingActions[`status_${user.id}`]"
                        >
                          <UserCheck class="h-4 w-4" />
                          {{ loadingActions[`status_${user.id}`] ? 'Reactivating...' : 'Reactivate' }}
                        </DropdownMenuItem>
                        
                        <!-- Toggle Email Verification -->
                        <DropdownMenuItem 
                          @click="toggleEmailVerification(user.id)"
                          class="flex items-center gap-2"
                          :disabled="loadingActions[`email_${user.id}`]"
                        >
                          <Shield class="h-4 w-4" />
                          {{ loadingActions[`email_${user.id}`] ? 'Processing...' : (user.email_verified ? 'Unverify Email' : 'Verify Email') }}
                        </DropdownMenuItem>
                        
                        <!-- Reset Password -->
                        <DropdownMenuItem 
                          @click="resetPassword(user.id)"
                          class="flex items-center gap-2"
                          :disabled="loadingActions[`reset_${user.id}`]"
                        >
                          <RefreshCw class="h-4 w-4" />
                          {{ loadingActions[`reset_${user.id}`] ? 'Resetting...' : 'Reset Password' }}
                        </DropdownMenuItem>
                        
                        <!-- Delete User -->
                        <DropdownMenuItem 
                          @click="deleteUser(user.id)"
                          class="flex items-center gap-2 text-destructive"
                          :disabled="loadingActions[`delete_${user.id}`]"
                        >
                          <Trash2 class="h-4 w-4" />
                          {{ loadingActions[`delete_${user.id}`] ? 'Deleting...' : 'Delete User' }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredUsers.length === 0">
                  <TableCell colspan="7" class="text-center py-4 text-muted-foreground">
                    No users found.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- View User Details Dialog -->
    <Dialog v-model:open="showViewDialog">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle>User Details</DialogTitle>
        </DialogHeader>
        
        <div v-if="viewingUser" class="space-y-6">
          <!-- User Information Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Personal Information -->
            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-sm">Personal Information</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-xs text-muted-foreground">Full Name</Label>
                  <p class="font-medium">{{ viewingUser.name }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Email Address</Label>
                  <div class="flex items-center gap-2">
                    <p class="font-medium">{{ viewingUser.email }}</p>
                    <Badge :variant="viewingUser.email_verified ? 'default' : 'secondary'" class="text-xs">
                      {{ viewingUser.email_verified ? 'Verified' : 'Unverified' }}
                    </Badge>
                  </div>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Phone Number</Label>
                  <p class="font-medium">{{ viewingUser.phone }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">User ID</Label>
                  <p class="font-medium">#{{ viewingUser.id }}</p>
                </div>
              </CardContent>
            </Card>

            <!-- Business Information -->
            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-sm">Business Information</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div>
                  <Label class="text-xs text-muted-foreground">Restaurant Name</Label>
                  <p class="font-medium">{{ viewingUser.restaurant_name }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Account Status</Label>
                  <div class="mt-1">
                    <Badge :variant="getStatusBadge(viewingUser.status)">
                      {{ viewingUser.status }}
                    </Badge>
                  </div>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Subscription Status</Label>
                  <div class="mt-1">
                    <Badge :variant="getSubscriptionBadge(viewingUser.subscription_status)">
                      {{ viewingUser.subscription_status || 'None' }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Account Timeline -->
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-sm">Account Timeline</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label class="text-xs text-muted-foreground">Account Created</Label>
                  <p class="font-medium">{{ formatDate(viewingUser.created_at) }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Last Login</Label>
                  <p class="font-medium">{{ viewingUser.last_login ? formatDate(viewingUser.last_login) : 'Never' }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button variant="outline" @click="showViewDialog = false">
              Close
            </Button>
            <Button variant="outline" @click="editUser(viewingUser)">
              <Edit class="h-4 w-4 mr-2" />
              Edit User
            </Button>
            <Button variant="outline" @click="sendEmail(viewingUser)">
              <Mail class="h-4 w-4 mr-2" />
              Send Email
            </Button>
            <Button 
              v-if="viewingUser.status === 'Pending'"
              @click="updateUserStatus(viewingUser.id, 'Approved')"
              :disabled="loadingActions[`status_${viewingUser.id}`]"
            >
              <CheckCircle class="h-4 w-4 mr-2" />
              {{ loadingActions[`status_${viewingUser.id}`] ? 'Approving...' : 'Approve' }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayoutAdministrator>
</template>