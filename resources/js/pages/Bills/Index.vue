<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Alert from '@/components/ui/alert/Alert.vue';
import AlertDescription from '@/components/ui/alert/AlertDescription.vue';
import { type BreadcrumbItem } from '@/types';

interface Bill {
  bill_id: number;
  bill_number: string;
  status: string;
  bill_date: string;
  due_date: string;
  total_amount: number;
  outstanding_amount: number;
  paid_amount: number;
  is_overdue: boolean;
  days_overdue: number;
  supplier: {
    supplier_name: string;
  };
  purchase_order?: {
    po_number: string;
  };
}

interface Summary {
  total_outstanding: number;
  overdue_amount: number;
  total_bills: number;
  overdue_count: number;
}

interface Props {
  bills: Bill[];
  summary: Summary;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
];

// ✅ Typed union for statuses
type BillStatus =
  | 'draft'
  | 'pending'
  | 'overdue'
  | 'paid'
  | 'partially_paid'
  | 'cancelled';

const statusConfig: Record<BillStatus, { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'; label: string }> = {
  draft: { variant: 'secondary', label: 'Draft' },
  pending: { variant: 'warning', label: 'Pending' },
  overdue: { variant: 'destructive', label: 'Overdue' },
  paid: { variant: 'success', label: 'Paid' },
  partially_paid: { variant: 'warning', label: 'Partially Paid' },
  cancelled: { variant: 'secondary', label: 'Cancelled' },
};

const getStatusBadge = (status: string, isOverdue: boolean): { variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'; label: string } => {
  if (isOverdue) {
    return { variant: 'destructive', label: 'Overdue' };
  }

  if (status in statusConfig) {
    return statusConfig[status as BillStatus];
  }

  // fallback for unexpected statuses
  return { variant: 'secondary', label: status };
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return `₱${Number(amount).toLocaleString()}`;
};

const getPaymentProgress = (bill: Bill) => {
  if (bill.total_amount === 0) return 0;
  const progress = Math.round((bill.paid_amount / bill.total_amount) * 100);
  return Math.min(progress, 100); // Cap at 100%
};
</script>

<template>
  <Head title="Supplier Bills" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="mx-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Supplier Bills</h1>
          <p class="text-muted-foreground">
            Manage supplier invoices and track payments
          </p>
        </div>
        <Link href="/bills/create">
          <Button>Create Bill</Button>
        </Link>
      </div>

      <!-- Overdue Alert -->
      <Alert v-if="summary.overdue_count > 0" variant="destructive">
        <AlertDescription>
          You have {{ summary.overdue_count }} overdue bills totaling
          {{ formatCurrency(summary.overdue_amount) }}.
          <Link href="/bills?filter=overdue" class="underline ml-2"
            >View overdue bills</Link
          >
        </AlertDescription>
      </Alert>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Total Bills</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.total_bills }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">
              {{ formatCurrency(summary.total_outstanding) }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Overdue</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">
              {{ formatCurrency(summary.overdue_amount) }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ summary.overdue_count }} bills
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Paid This Month</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{
                formatCurrency(
                  bills
                    .filter(b => ['paid', 'partially_paid'].includes(b.status))
                    .reduce((sum, b) => sum + Number(b.paid_amount || 0), 0)
                )
              }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Bills Table -->
      <Card>
        <CardHeader>
          <CardTitle>Bills</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bill Number</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Bill Date</TableHead>
                <TableHead>Due Date</TableHead>
                <TableHead>Total Amount</TableHead>
                <TableHead>Outstanding</TableHead>
                <TableHead>Payment Progress</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="bill in bills"
                :key="bill.bill_id"
                :class="{ 'bg-red-50': bill.is_overdue }"
              >
                <TableCell class="font-medium">
                  <div>
                    <div>{{ bill.bill_number }}</div>
                    <div
                      v-if="bill.purchase_order"
                      class="text-sm text-muted-foreground"
                    >
                      PO: {{ bill.purchase_order.po_number }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  {{ bill.supplier.supplier_name }}
                </TableCell>
                <TableCell>{{ formatDate(bill.bill_date) }}</TableCell>
                <TableCell>
                  <div>
                    {{ formatDate(bill.due_date) }}
                    <div
                      v-if="bill.is_overdue"
                      class="text-xs text-red-600"
                    >
                      {{ bill.days_overdue }} days overdue
                    </div>
                  </div>
                </TableCell>
                <TableCell class="font-medium">
                  {{ formatCurrency(bill.total_amount) }}
                </TableCell>
                <TableCell>
                  <div class="text-right">
                    <div
                      class="font-medium"
                      :class="bill.outstanding_amount > 0 ? 'text-orange-600' : 'text-green-600'"
                    >
                      {{ formatCurrency(bill.outstanding_amount) }}
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                      <div
                        class="h-2 rounded-full transition-all"
                        :class="getPaymentProgress(bill) === 100 ? 'bg-green-600' : 'bg-blue-600'"
                        :style="{ width: `${getPaymentProgress(bill)}%` }"
                      ></div>
                    </div>
                    <div class="text-xs text-muted-foreground">
                      {{ getPaymentProgress(bill) }}% paid
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge
                    :variant="getStatusBadge(bill.status, bill.is_overdue).variant"
                  >
                    {{ getStatusBadge(bill.status, bill.is_overdue).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end space-x-2">
                    <Link :href="`/bills/${bill.bill_id}`">
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                    <Link
                      v-if="bill.outstanding_amount > 0"
                      :href="`/payments/create/${bill.bill_id}`"
                    >
                      <Button size="sm">Pay</Button>
                    </Link>
                  </div>
                </TableCell>
              </TableRow>

              <TableRow v-if="bills.length === 0">
                <TableCell colspan="9" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <div class="text-lg mb-2">No bills found</div>
                    <div class="text-sm">
                      Bills will appear here once you create them or receive
                      deliveries.
                    </div>
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
