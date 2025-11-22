<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';
import { Eye, Edit, CheckCircle, X } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';


interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number: string;
  email: string;
  address: string;
  payment_terms: string;
  is_active: boolean;
  ingredients?: any[];
  purchase_orders?: any[];
}

interface Filters {
  status: string;
  search?: string;
}

interface Props {
  suppliers: Supplier[];
  restaurant_id: number;
  filters: Filters;
}

const props = defineProps<Props>();
const processing = ref(false);
const showNotification = ref(false);
const notificationMessage = ref('');
const notificationType = ref<'success' | 'error'>('success');
const selectedStatus = ref(props.filters.status);
const searchQuery = ref(props.filters.search || '');

watch(selectedStatus, (newValue) => {
  router.get('/suppliers', { status: newValue, search: searchQuery.value }, { preserveState: true, preserveScroll: true });
});

const handleSearch = () => {
  router.get('/suppliers', { status: selectedStatus.value, search: searchQuery.value }, { preserveState: true, preserveScroll: true });
};

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
      <div class="flex items-center justify-between mt-6">
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
        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Total Suppliers</div>
            <div class="text-2xl font-bold">{{ suppliers.length }}</div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">Active Suppliers</div>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.is_active).length }}</div>
          </CardContent>
        </Card>

        <Card class="h-24 flex flex-col justify-center">
          <CardContent class="p-4">
            <div class="text-sm font-medium text-muted-foreground mb-1">With Email</div>
            <div class="text-2xl font-bold">{{ suppliers.filter(s => s.email).length }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Suppliers Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Suppliers List</CardTitle>
            <div class="flex items-center gap-2">
              <Input
                v-model="searchQuery"
                type="text"
                placeholder="Search by supplier name..."
                class="w-[280px]"
                @keyup.enter="handleSearch"
              />
              <Select v-model="selectedStatus">
                <SelectTrigger class="w-[180px]">
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="all">All Suppliers</SelectItem>
                </SelectContent>
              </Select>
              <Button @click="handleSearch" variant="default">Search</Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Address</TableHead>
                <TableHead>Payment Terms</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="supplier in suppliers" :key="supplier.supplier_id">
                <TableCell class="font-medium">
                  {{ supplier.supplier_name }}
                </TableCell>
                <TableCell>
                  {{ supplier.contact_number || '-' }}
                </TableCell>
                <TableCell>
                  {{ supplier.email || '-' }}
                </TableCell>
                <TableCell>
                  {{ supplier.address || '-' }}
                </TableCell>
                <TableCell>
                  {{ getPaymentTermsLabel(supplier.payment_terms) }}
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