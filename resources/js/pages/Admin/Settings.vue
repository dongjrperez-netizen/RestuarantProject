<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Settings, Users, Package, Server, Plus, Trash2, Edit } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'System Settings',
    href: '/admin/settings',
  },
];

interface Admin {
  id: number;
  email: string;
  created_at: string;
}

interface SubscriptionPlan {
  plan_id: number;
  plan_name: string;
  plan_description: string;
  plan_price: number;
  plan_duration: number;
  plan_features: string;
  created_at: string;
}

interface SystemStats {
  total_admins: number;
  total_plans: number;
  system_version: string;
  last_backup: string;
}

const props = defineProps<{
  admins: Admin[];
  subscriptionPlans: SubscriptionPlan[];
  systemStats: SystemStats;
}>();

// Admin Management
const adminForm = useForm({
  email: '',
  password: '',
  password_confirmation: ''
});

// Subscription Plan Management
const planForm = useForm({
  plan_name: '',
  plan_description: '',
  plan_price: 0,
  plan_duration: 30,
  plan_features: ''
});

const editingPlan = ref<SubscriptionPlan | null>(null);
const showAdminDialog = ref(false);
const showPlanDialog = ref(false);

const createAdmin = () => {
  adminForm.post('/admin/settings/admins', {
    onSuccess: () => {
      adminForm.reset();
      showAdminDialog.value = false;
    }
  });
};

const deleteAdmin = (id: number) => {
  if (confirm('Are you sure you want to delete this administrator?')) {
    router.delete(`/admin/settings/admins/${id}`);
  }
};

const createPlan = () => {
  planForm.post('/admin/settings/subscription-plans', {
    onSuccess: () => {
      planForm.reset();
      showPlanDialog.value = false;
    }
  });
};

const editPlan = (plan: SubscriptionPlan) => {
  editingPlan.value = plan;
  planForm.plan_name = plan.plan_name;
  planForm.plan_description = plan.plan_description;
  planForm.plan_price = plan.plan_price;
  planForm.plan_duration = plan.plan_duration;
  planForm.plan_features = plan.plan_features || '';
  showPlanDialog.value = true;
};

const updatePlan = () => {
  if (editingPlan.value) {
    planForm.put(`/admin/settings/subscription-plans/${editingPlan.value.plan_id}`, {
      onSuccess: () => {
        planForm.reset();
        editingPlan.value = null;
        showPlanDialog.value = false;
      }
    });
  }
};

const deletePlan = (id: number) => {
  if (confirm('Are you sure you want to delete this subscription plan?')) {
    router.delete(`/admin/settings/subscription-plans/${id}`);
  }
};

const resetPlanForm = () => {
  planForm.reset();
  editingPlan.value = null;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};
</script>

<template>
  <Head title="System Settings" />

  <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- System Overview -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">System Version</CardTitle>
            <Server class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ systemStats.system_version }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Administrators</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ systemStats.total_admins }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Subscription Plans</CardTitle>
            <Package class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ systemStats.total_plans }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Last Backup</CardTitle>
            <Settings class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-sm font-bold">{{ formatDate(systemStats.last_backup) }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Settings Tabs -->
      <Card>
        <CardHeader>
          <CardTitle>System Configuration</CardTitle>
        </CardHeader>
        <CardContent>
          <Tabs default-value="admins" class="w-full">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="admins">Administrator Management</TabsTrigger>
              <TabsTrigger value="plans">Subscription Plans</TabsTrigger>
            </TabsList>

            <!-- Admin Management Tab -->
            <TabsContent value="admins" class="space-y-4">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">System Administrators</h3>
                <Dialog v-model:open="showAdminDialog">
                  <DialogTrigger as-child>
                    <Button @click="showAdminDialog = true">
                      <Plus class="h-4 w-4 mr-2" />
                      Add Administrator
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Create New Administrator</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="createAdmin" class="space-y-4">
                      <div>
                        <Label for="email">Email Address</Label>
                        <Input
                          id="email"
                          v-model="adminForm.email"
                          type="email"
                          required
                          placeholder="admin@example.com"
                        />
                        <div v-if="adminForm.errors.email" class="text-sm text-red-600">
                          {{ adminForm.errors.email }}
                        </div>
                      </div>
                      
                      <div>
                        <Label for="password">Password</Label>
                        <Input
                          id="password"
                          v-model="adminForm.password"
                          type="password"
                          required
                          placeholder="Enter password"
                        />
                        <div v-if="adminForm.errors.password" class="text-sm text-red-600">
                          {{ adminForm.errors.password }}
                        </div>
                      </div>
                      
                      <div>
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input
                          id="password_confirmation"
                          v-model="adminForm.password_confirmation"
                          type="password"
                          required
                          placeholder="Confirm password"
                        />
                      </div>
                      
                      <Button type="submit" :disabled="adminForm.processing" class="w-full">
                        Create Administrator
                      </Button>
                    </form>
                  </DialogContent>
                </Dialog>
              </div>

              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Email</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead class="text-center">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="admin in admins" :key="admin.id">
                    <TableCell>{{ admin.email }}</TableCell>
                    <TableCell>{{ formatDate(admin.created_at) }}</TableCell>
                    <TableCell class="text-center">
                      <Button
                        variant="destructive"
                        size="sm"
                        @click="deleteAdmin(admin.id)"
                        :disabled="admins.length <= 1"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </TabsContent>

            <!-- Subscription Plans Tab -->
            <TabsContent value="plans" class="space-y-4">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Subscription Plans</h3>
                <Dialog v-model:open="showPlanDialog">
                  <DialogTrigger as-child>
                    <Button @click="showPlanDialog = true; resetPlanForm()">
                      <Plus class="h-4 w-4 mr-2" />
                      Add Plan
                    </Button>
                  </DialogTrigger>
                  <DialogContent class="max-w-2xl">
                    <DialogHeader>
                      <DialogTitle>
                        {{ editingPlan ? 'Edit' : 'Create' }} Subscription Plan
                      </DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="editingPlan ? updatePlan() : createPlan()" class="space-y-4">
                      <div class="grid grid-cols-2 gap-4">
                        <div>
                          <Label for="plan_name">Plan Name</Label>
                          <Input
                            id="plan_name"
                            v-model="planForm.plan_name"
                            required
                            placeholder="Basic Plan"
                          />
                        </div>
                        
                        <div>
                          <Label for="plan_price">Price ($)</Label>
                          <Input
                            id="plan_price"
                            v-model="planForm.plan_price"
                            type="number"
                            step="0.01"
                            required
                            placeholder="29.99"
                          />
                        </div>
                      </div>
                      
                      <div>
                        <Label for="plan_duration">Duration (Days)</Label>
                        <Input
                          id="plan_duration"
                          v-model="planForm.plan_duration"
                          type="number"
                          required
                          placeholder="30"
                        />
                      </div>
                      
                      <div>
                        <Label for="plan_description">Description</Label>
                        <Textarea
                          id="plan_description"
                          v-model="planForm.plan_description"
                          required
                          placeholder="Plan description..."
                        />
                      </div>
                      
                      <div>
                        <Label for="plan_features">Features (optional)</Label>
                        <Textarea
                          id="plan_features"
                          v-model="planForm.plan_features"
                          placeholder="Feature 1, Feature 2, Feature 3..."
                        />
                      </div>
                      
                      <Button type="submit" :disabled="planForm.processing" class="w-full">
                        {{ editingPlan ? 'Update' : 'Create' }} Plan
                      </Button>
                    </form>
                  </DialogContent>
                </Dialog>
              </div>

              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Plan Name</TableHead>
                    <TableHead>Price</TableHead>
                    <TableHead>Duration</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead class="text-center">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="plan in subscriptionPlans" :key="plan.plan_id">
                    <TableCell class="font-medium">{{ plan.plan_name }}</TableCell>
                    <TableCell>{{ formatCurrency(plan.plan_price) }}</TableCell>
                    <TableCell>{{ plan.plan_duration }} days</TableCell>
                    <TableCell>
                      <div class="max-w-xs truncate">{{ plan.plan_description }}</div>
                    </TableCell>
                    <TableCell class="text-center">
                      <div class="flex justify-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          @click="editPlan(plan)"
                        >
                          <Edit class="h-4 w-4" />
                        </Button>
                        <Button
                          variant="destructive"
                          size="sm"
                          @click="deletePlan(plan.plan_id)"
                        >
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  </AppLayoutAdministrator>
</template>