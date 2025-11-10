<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
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
import { Users, CheckCircle, Clock, XCircle, MoreVertical, Eye, Mail, Search, UserCheck, Shield } from 'lucide-vue-next';

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
  has_documents: boolean;
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

// Email verification confirmation dialog
const showEmailVerificationDialog = ref<boolean>(false);
const emailVerificationUser = ref<User | null>(null);

// Send email dialog
const showSendEmailDialog = ref<boolean>(false);
const sendEmailUser = ref<User | null>(null);
const emailForm = ref({
  subject: '',
  message: ''
});

// Loading states
const loadingActions = ref<Record<string, boolean>>({});

// Dropdown open states
const openDropdowns = ref<Record<number, boolean>>({});

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
  // Close dropdown first
  openDropdowns.value[userId] = false;

  // Wait for dropdown to close before making request
  await new Promise(resolve => setTimeout(resolve, 150));

  const actionKey = `status_${userId}`;
  loadingActions.value[actionKey] = true;

  router.post(`/admin/users/${userId}/status`, { status }, {
    preserveScroll: true,
    only: ['users', 'stats'],
    onFinish: () => {
      loadingActions.value[actionKey] = false;
    },
    onError: () => {
      loadingActions.value[actionKey] = false;
    }
  });
};

const toggleEmailVerification = (user: User) => {
  // Close dropdown and show confirmation modal
  openDropdowns.value[user.id] = false;
  emailVerificationUser.value = user;
  showEmailVerificationDialog.value = true;
};

const confirmToggleEmailVerification = () => {
  if (!emailVerificationUser.value) return;

  const userId = emailVerificationUser.value.id;
  const actionKey = `email_${userId}`;
  loadingActions.value[actionKey] = true;

  router.post(`/admin/users/${userId}/toggle-email`, {}, {
    preserveScroll: true,
    only: ['users', 'stats'],
    onFinish: () => {
      loadingActions.value[actionKey] = false;
      showEmailVerificationDialog.value = false;
      emailVerificationUser.value = null;
    },
    onError: () => {
      loadingActions.value[actionKey] = false;
    }
  });
};

// View user details
const viewUser = (user: User) => {
  // Close dropdown first
  openDropdowns.value[user.id] = false;

  viewingUser.value = user;
  showViewDialog.value = true;
};

// Open send email modal
const sendEmail = (user: User) => {
  // Close dropdown and open send email modal
  openDropdowns.value[user.id] = false;
  sendEmailUser.value = user;
  emailForm.value = {
    subject: '',
    message: ''
  };
  showSendEmailDialog.value = true;
};

// Submit email
const submitSendEmail = () => {
  if (!sendEmailUser.value) return;

  const userId = sendEmailUser.value.id;
  const actionKey = `email_${userId}`;
  loadingActions.value[actionKey] = true;

  router.post(`/admin/users/${userId}/send-email`, emailForm.value, {
    preserveScroll: true,
    only: ['users', 'stats'],
    onFinish: () => {
      loadingActions.value[actionKey] = false;
      showSendEmailDialog.value = false;
      sendEmailUser.value = null;
      emailForm.value = {
        subject: '',
        message: ''
      };
    },
    onError: () => {
      loadingActions.value[actionKey] = false;
    }
  });
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
                  <TableHead>Documents</TableHead>
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
                    <Badge :variant="user.has_documents ? 'default' : 'outline'">
                      {{ user.has_documents ? 'Uploaded' : 'None' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getSubscriptionBadge(user.subscription_status)">
                      {{ user.subscription_status || 'None' }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                  <TableCell class="text-center">
                    <DropdownMenu v-model:open="openDropdowns[user.id]">
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

                        <!-- Send Email -->
                        <DropdownMenuItem
                          @click="sendEmail(user)"
                          class="flex items-center gap-2"
                        >
                          <Mail class="h-4 w-4" />
                          Send Email
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
                          @click="toggleEmailVerification(user)"
                          class="flex items-center gap-2"
                        >
                          <Shield class="h-4 w-4" />
                          {{ user.email_verified ? 'Unverify Email' : 'Verify Email' }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
                <TableRow v-if="filteredUsers.length === 0">
                  <TableCell colspan="8" class="text-center py-4 text-muted-foreground">
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
          <DialogDescription>
            View complete information about this user and their account status.
          </DialogDescription>
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
            <Button variant="outline" @click="sendEmail(viewingUser)">
              <Mail class="h-4 w-4 mr-2" />
              Send Email
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Email Verification Confirmation Dialog -->
    <Dialog v-model:open="showEmailVerificationDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Confirm Email Verification</DialogTitle>
          <DialogDescription>
            Review the user details and confirm the email verification action.
          </DialogDescription>
        </DialogHeader>

        <div v-if="emailVerificationUser" class="space-y-4">
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">
              Are you sure you want to
              <strong>{{ emailVerificationUser.email_verified ? 'unverify' : 'verify' }}</strong>
              the email address for this user?
            </p>

            <Card class="bg-muted/50">
              <CardContent class="p-4 space-y-2">
                <div>
                  <Label class="text-xs text-muted-foreground">User</Label>
                  <p class="font-medium">{{ emailVerificationUser.name }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Email</Label>
                  <p class="font-medium">{{ emailVerificationUser.email }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Current Status</Label>
                  <div class="mt-1">
                    <Badge :variant="emailVerificationUser.email_verified ? 'default' : 'secondary'">
                      {{ emailVerificationUser.email_verified ? 'Verified' : 'Unverified' }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="outline"
              @click="showEmailVerificationDialog = false"
              :disabled="loadingActions[`email_${emailVerificationUser.id}`]"
            >
              Cancel
            </Button>
            <Button
              @click="confirmToggleEmailVerification"
              :disabled="loadingActions[`email_${emailVerificationUser.id}`]"
            >
              <Shield class="h-4 w-4 mr-2" />
              {{ loadingActions[`email_${emailVerificationUser.id}`]
                ? 'Processing...'
                : (emailVerificationUser.email_verified ? 'Unverify Email' : 'Verify Email')
              }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Send Email Dialog -->
    <Dialog v-model:open="showSendEmailDialog">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Send Email to User</DialogTitle>
          <DialogDescription>
            Compose and send an email directly to this user's registered email address.
          </DialogDescription>
        </DialogHeader>

        <div v-if="sendEmailUser" class="space-y-4">
          <!-- Recipient Info -->
          <Card class="bg-muted/50">
            <CardContent class="p-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label class="text-xs text-muted-foreground">Recipient</Label>
                  <p class="font-medium">{{ sendEmailUser.name }}</p>
                </div>
                <div>
                  <Label class="text-xs text-muted-foreground">Email Address</Label>
                  <p class="font-medium">{{ sendEmailUser.email }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Email Form -->
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="email-subject">Subject *</Label>
              <Input
                id="email-subject"
                v-model="emailForm.subject"
                placeholder="Enter email subject"
                :disabled="loadingActions[`email_${sendEmailUser.id}`]"
              />
            </div>

            <div class="space-y-2">
              <Label for="email-message">Message *</Label>
              <Textarea
                id="email-message"
                v-model="emailForm.message"
                placeholder="Enter your message here..."
                rows="10"
                :disabled="loadingActions[`email_${sendEmailUser.id}`]"
              />
              <p class="text-xs text-muted-foreground">
                Maximum 5000 characters. This message will be sent to the user's email address.
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="outline"
              @click="showSendEmailDialog = false"
              :disabled="loadingActions[`email_${sendEmailUser.id}`]"
            >
              Cancel
            </Button>
            <Button
              @click="submitSendEmail"
              :disabled="loadingActions[`email_${sendEmailUser.id}`] || !emailForm.subject || !emailForm.message"
            >
              <Mail class="h-4 w-4 mr-2" />
              {{ loadingActions[`email_${sendEmailUser.id}`] ? 'Sending...' : 'Send Email' }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayoutAdministrator>
</template>