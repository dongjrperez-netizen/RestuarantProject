<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Mail, Phone, MapPin, CreditCard, Calendar } from 'lucide-vue-next';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number: string;
  email: string;
  address: string;
  payment_terms: string;
  credit_limit: number;
  is_active: boolean;
  created_at: string;
  ingredients?: any[];
  purchase_orders?: any[];
  bills?: any[];
}

interface Props {
  supplier: Supplier;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Suppliers', href: '/suppliers' },
  { title: props.supplier.supplier_name, href: `/suppliers/${props.supplier.supplier_id}` },
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

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <Head :title="`Supplier - ${supplier.supplier_name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link href="/suppliers">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Suppliers
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ supplier.supplier_name }}</h1>
            <p class="text-muted-foreground">Supplier details and information</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Link :href="route('suppliers.edit', supplier.supplier_id)">
            <Button>Edit Supplier</Button>
          </Link>
        </div>
      </div>

      <!-- Supplier Information -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Contact Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Phone class="h-5 w-5" />
              Contact Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="supplier.contact_number">
              <label class="text-sm font-medium text-muted-foreground">Phone Number</label>
              <p class="text-sm">{{ supplier.contact_number }}</p>
            </div>
            <div v-if="supplier.email">
              <label class="text-sm font-medium text-muted-foreground">Email Address</label>
              <div class="flex items-center gap-2">
                <p class="text-sm">{{ supplier.email }}</p>
                <Mail class="h-4 w-4 text-muted-foreground" />
              </div>
            </div>
            <div v-if="supplier.address">
              <label class="text-sm font-medium text-muted-foreground">Address</label>
              <div class="flex items-start gap-2">
                <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                <p class="text-sm">{{ supplier.address }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Business Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <CreditCard class="h-5 w-5" />
              Business Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label class="text-sm font-medium text-muted-foreground">Payment Terms</label>
              <p class="text-sm">{{ getPaymentTermsLabel(supplier.payment_terms) }}</p>
            </div>
            <div v-if="supplier.credit_limit">
              <label class="text-sm font-medium text-muted-foreground">Credit Limit</label>
              <p class="text-sm">{{ formatCurrency(supplier.credit_limit) }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Status</label>
              <div class="mt-1">
                <Badge :variant="supplier.is_active ? 'default' : 'secondary'">
                  {{ supplier.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-muted-foreground">Added Date</label>
              <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <p class="text-sm">{{ formatDate(supplier.created_at) }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Statistics -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ingredients Supplied</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ supplier.ingredients?.length || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Purchase Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ supplier.purchase_orders?.length || 0 }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Bills</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ supplier.bills?.length || 0 }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Ingredients Supplied -->
      <Card v-if="supplier.ingredients && supplier.ingredients.length > 0">
        <CardHeader>
          <CardTitle>Ingredients Supplied</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ingredient Name</TableHead>
                <TableHead>Package Unit</TableHead>
                <TableHead>Package Quantity</TableHead>
                <TableHead class="text-right">Package Price</TableHead>
                <TableHead class="text-right">Price per Unit</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="ingredient in supplier.ingredients" :key="ingredient.ingredient_id">
                <TableCell class="font-medium">{{ ingredient.ingredient_name }}</TableCell>
                <TableCell>{{ ingredient.pivot?.package_unit || '-' }}</TableCell>
                <TableCell>{{ ingredient.pivot?.package_quantity || '-' }}</TableCell>
                <TableCell class="text-right">
                  <span v-if="ingredient.pivot?.package_price">
                    {{ formatCurrency(Number(ingredient.pivot.package_price)) }}
                  </span>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell class="text-right">
                  <span v-if="ingredient.pivot?.package_price && ingredient.pivot?.package_quantity">
                    {{ formatCurrency(Number(ingredient.pivot.package_price) / Number(ingredient.pivot.package_quantity)) }}
                  </span>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>