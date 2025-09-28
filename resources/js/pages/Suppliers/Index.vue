<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';
import { Eye, Edit, Mail, Copy, UserCheck, UserX, CheckCircle, X } from 'lucide-vue-next';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number: string;
  email: string;
  address: string;
  payment_terms: string;
  credit_limit: number;
  is_active: boolean;
  ingredients?: any[];
  purchase_orders?: any[];
}

interface Props {
  suppliers: Supplier[];
  restaurant_id: number;
}

const props = defineProps<Props>();
const processing = ref(false);
const showNotification = ref(false);
const notificationMessage = ref('');
const notificationType = ref<'success' | 'error'>('success');

const page = usePage();

// Watch for flash messages
watch(() => (page.props as any).flash?.success, (success) => {
  if (success) {
    showSuccessNotification('Email sent!');
  }
});

watch(() => (page.props as any).flash?.error, (error) => {
  if (error) {
    showErrorNotification(error as string);
  }
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Suppliers', href: '/suppliers' },
];

const getPaymentTermsLabel = (terms: string) => {
  const labels: Record<string, string> = {
    'COD': 'Cash on Delivery',
    'NET_7': 'Net 7 days',
    'NET_15': 'Net 15 days',
    'NET_30': 'Net 30 days',
    'NET_60': 'Net 60 days',
    'NET_90': 'Net 90 days',
  };
  return labels[terms] || terms;
};

const getStatusBadgeVariant = (isActive: boolean) => {
  return isActive ? 'default' : 'secondary';
};

const showSuccessNotification = (message: string) => {
  notificationMessage.value = message;
  notificationType.value = 'success';
  showNotification.value = true;
  setTimeout(() => {
    hideNotification();
  }, 3000);
};

const showErrorNotification = (message: string) => {
  notificationMessage.value = message;
  notificationType.value = 'error';
  showNotification.value = true;
  setTimeout(() => {
    hideNotification();
  }, 4000);
};

const hideNotification = () => {
  showNotification.value = false;
  setTimeout(() => {
    notificationMessage.value = '';
  }, 300);
};

const toggleSupplierStatus = (supplier: Supplier) => {
  if (processing.value) return;
  
  processing.value = true;
  router.post(`/suppliers/${supplier.supplier_id}/toggle-status`, {}, {
    onSuccess: () => {
      supplier.is_active = !supplier.is_active;
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const sendSupplierInvitation = (supplier: Supplier) => {
  if (!supplier.email) {
    showErrorNotification('Supplier must have an email address to send invitation');
    return;
  }

  if (processing.value) return;

  if (!confirm(`Send invitation email to ${supplier.email}?`)) {
    return;
  }

  processing.value = true;
  router.post(`/suppliers/${supplier.supplier_id}/send-invitation`, {}, {
    onFinish: () => {
      processing.value = false;
    }
  });
};

const getInvitationLink = (supplier: Supplier) => {
  return route('supplier.register', {
    restaurant_id: props.restaurant_id,
    supplier_id: supplier.supplier_id
  });
};

const copyInvitationLink = async (supplier: Supplier) => {
  const link = getInvitationLink(supplier);
  try {
    await navigator.clipboard.writeText(link);
    showSuccessNotification('Link copied!');
  } catch {
    showErrorNotification('Failed to copy link to clipboard');
  }
};
</script>

<template>
  <Head title="Suppliers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Small Toast Notification -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-x-full"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-full"
    >
      <div
        v-if="showNotification"
        :class="[
          'fixed top-20 right-4 z-50 flex items-center gap-2 px-3 py-2 rounded shadow-lg text-sm font-medium',
          notificationType === 'success'
            ? 'bg-green-600 text-white'
            : 'bg-red-600 text-white'
        ]"
      >
        <CheckCircle v-if="notificationType === 'success'" class="h-4 w-4" />
        <X v-else class="h-4 w-4" />
        <span>{{ notificationMessage }}</span>
        <Button
          variant="ghost"
          size="sm"
          class="h-6 w-6 p-0 text-white hover:bg-white/20 ml-1"
          @click="hideNotification"
        >
          <X class="h-3 w-3" />
        </Button>
      </div>
    </Transition>

    <div class="space-y-6 mx-8">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Suppliers</h1>
          <p class="text-muted-foreground">Manage your suppliers and their contact information</p>
        </div>
        <Link href="/suppliers/create">
          <Button>Add Supplier</Button>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Suppliers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.length }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Suppliers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.is_active).length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">With Email</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.email).length }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Suppliers Table -->
      <Card>
        <CardHeader>
          <CardTitle>Suppliers List</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Payment Terms</TableHead>
                <TableHead>Credit Limit</TableHead>
                <TableHead>Ingredients</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="supplier in suppliers" :key="supplier.supplier_id">
                <TableCell class="font-medium">
                  <div>
                    <div class="font-semibold">{{ supplier.supplier_name }}</div>
                    <div v-if="supplier.address" class="text-sm text-muted-foreground">
                      {{ supplier.address }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div v-if="supplier.contact_number" class="text-sm">
                      📞 {{ supplier.contact_number }}
                    </div>
                    <div v-if="supplier.email" class="text-sm">
                      📧 {{ supplier.email }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ getPaymentTermsLabel(supplier.payment_terms) }}
                </TableCell>
                <TableCell>
                  <span v-if="supplier.credit_limit">
                    ₱{{ Number(supplier.credit_limit).toLocaleString() }}
                  </span>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ supplier.ingredients?.length || 0 }} items
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(supplier.is_active)">
                    {{ supplier.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end items-center gap-1">
                    <Link :href="route('suppliers.show', supplier.supplier_id)">
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="View Details">
                        <Eye class="h-4 w-4" />
                      </Button>
                    </Link>
                    <Link :href="route('suppliers.edit', supplier.supplier_id)">
                      <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="Edit Supplier">
                        <Edit class="h-4 w-4" />
                      </Button>
                    </Link>
                    <Button
                      v-if="supplier.email"
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="sendSupplierInvitation(supplier)"
                      :disabled="processing"
                      title="Send Email Invitation"
                    >
                      <Mail class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="copyInvitationLink(supplier)"
                      title="Copy Invitation Link"
                    >
                      <Copy class="h-4 w-4" />
                    </Button>
                    <Button
                      :variant="supplier.is_active ? 'ghost' : 'ghost'"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="toggleSupplierStatus(supplier)"
                      :disabled="processing"
                      :title="supplier.is_active ? 'Deactivate Supplier' : 'Activate Supplier'"
                      :class="supplier.is_active ? 'text-red-600 hover:text-red-700 hover:bg-red-50' : 'text-green-600 hover:text-green-700 hover:bg-green-50'"
                    >
                      <UserX v-if="supplier.is_active" class="h-4 w-4" />
                      <UserCheck v-else class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="suppliers.length === 0">
                <TableCell colspan="7" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No suppliers found</div>
                    <div class="text-sm">Get started by adding your first supplier.</div>
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