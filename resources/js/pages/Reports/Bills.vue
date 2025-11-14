<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import {
  ArrowLeft,
  Download,
  FileText,
  Clock,
  CheckCircle,
  AlertCircle,
  DollarSign,
  TrendingUp,
  Calendar,
  Filter,
  Banknote
} from 'lucide-vue-next';

interface Bill {
  bill_id: number;
  bill_number: string;
  bill_date: string;
  due_date: string;
  status: string;
  total_amount: number;
  paid_amount: number;
  outstanding_amount: number;
  is_overdue: boolean;
  supplier: {
    supplier_name: string;
  } | null;
  purchase_order?: {
    po_number: string;
  } | null;
  notes?: string;
  payments: any[];
}

interface Summary {
  total_bills: number;
  total_amount: number;
  total_paid: number;
  total_outstanding: number;
  overdue_count: number;
  overdue_amount: number;
}

interface BillsByStatus {
  [key: string]: number;
}

interface SupplierData {
  count: number;
  total_amount: number;
  paid_amount: number;
  outstanding_amount: number;
}

interface BySupplier {
  [key: string]: SupplierData;
}

interface MonthlyTrend {
  month: string;
  total_amount: number;
  paid_amount: number;
  count: number;
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface BillsData {
  bills: Bill[];
  summary: Summary;
  bills_by_status: BillsByStatus;
  by_supplier: BySupplier;
  monthly_trend: MonthlyTrend[];
  suppliers: Supplier[];
}

interface Filters {
  date_from: string;
  date_to: string;
  status: string;
  supplier_id: string;
}

interface Props {
  billsData: BillsData;
  filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Bills Report', href: '#' },
];

const filterForm = ref({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
  status: props.filters.status,
  supplier_id: props.filters.supplier_id,
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getSupplierName = (bill: Bill) => {
  if (bill.supplier) {
    return bill.supplier.supplier_name;
  }

  // Manual receive - extract from notes
  if (bill.notes) {
    const manualReceiveMatch = bill.notes.match(/manual receive\s*-\s*([^|]+)/i);
    if (manualReceiveMatch) {
      return manualReceiveMatch[1].trim();
    }

    const supplierMatch = bill.notes.match(/Supplier:\s*([^|]+)/);
    if (supplierMatch) {
      return supplierMatch[1].trim();
    }
  }

  return 'Unknown Supplier';
};

const applyFilters = () => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, String(value));
  });

  router.get(`/reports/bills?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const exportReport = (format: 'pdf' | 'csv' | 'excel') => {
  const params = new URLSearchParams();
  Object.entries(filterForm.value).forEach(([key, value]) => {
    if (value && value !== 'all') params.append(key, String(value));
  });
  params.append('export', format);

  window.location.href = `/reports/bills?${params.toString()}`;
};

const getStatusVariant = (status: string, isOverdue: boolean) => {
  if (isOverdue) return 'destructive';

  switch (status.toLowerCase()) {
    case 'paid':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'partially_paid':
      return 'outline';
    case 'overdue':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const getStatusLabel = (status: string, isOverdue: boolean) => {
  if (isOverdue) return 'Overdue';
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const paymentProgress = (bill: Bill) => {
  if (bill.total_amount === 0) return 0;
  return Math.round((bill.paid_amount / bill.total_amount) * 100);
};
</script>

<template>
  <AppLayout title="Bills Report" :breadcrumbs="breadcrumbs">
    <Head title="Bills Report" />

      <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="outline" size="sm" as-child>
            <a href="/reports">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Reports
            </a>
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Bills Report</h1>
            <p class="text-muted-foreground">Track supplier bills and payments</p>
          </div>
        </div>

        <div class="flex space-x-2">
          <Button variant="outline" @click="exportReport('pdf')">
            <Download class="h-4 w-4 mr-2" />
            PDF
          </Button>
          <Button variant="outline" @click="exportReport('csv')">
            <Download class="h-4 w-4 mr-2" />
            CSV
          </Button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Bills</CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatNumber(billsData.summary.total_bills) }}</div>
            <p class="text-xs text-muted-foreground">
              Total bills in period
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Amount</CardTitle>
            <Banknote class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatCurrency(billsData.summary.total_amount) }}</div>
            <p class="text-xs text-muted-foreground">
              Total billed amount
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Paid</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ formatCurrency(billsData.summary.total_paid) }}</div>
            <p class="text-xs text-muted-foreground">
              Amount paid to suppliers
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
            <AlertCircle class="h-4 w-4 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(billsData.summary.total_outstanding) }}</div>
            <p class="text-xs text-muted-foreground">
              {{ billsData.summary.overdue_count }} overdue
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center">
            <Filter class="h-5 w-5 mr-2" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
            <div>
              <Label for="date_from">From Date</Label>
              <Input
                id="date_from"
                v-model="filterForm.date_from"
                type="date"
                class="mt-1.5"
              />
            </div>

            <div>
              <Label for="date_to">To Date</Label>
              <Input
                id="date_to"
                v-model="filterForm.date_to"
                type="date"
                class="mt-1.5"
              />
            </div>

            <div>
              <Label for="status">Status</Label>
              <Select v-model="filterForm.status">
                <SelectTrigger id="status" class="mt-1.5">
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="partially_paid">Partially Paid</SelectItem>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="overdue">Overdue</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="supplier">Supplier</Label>
              <Select v-model="filterForm.supplier_id">
                <SelectTrigger id="supplier" class="mt-1.5">
                  <SelectValue placeholder="All suppliers" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Suppliers</SelectItem>
                  <SelectItem
                    v-for="supplier in billsData.suppliers"
                    :key="supplier.supplier_id"
                    :value="supplier.supplier_id.toString()"
                  >
                    {{ supplier.supplier_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="mt-4 flex justify-end">
            <Button @click="applyFilters">
              Apply Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Bills by Status -->
      <div class="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Bills by Status</CardTitle>
            <CardDescription>Distribution of bill statuses</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div
              v-for="(count, status) in billsData.bills_by_status"
              :key="status"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <Badge :variant="getStatusVariant(String(status), false)" class="mr-2">
                  {{ getStatusLabel(String(status), false) }}
                </Badge>
              </div>
              <div class="font-semibold">{{ count }}</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>By Supplier</CardTitle>
            <CardDescription>Top suppliers by amount</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div
              v-for="(data, supplier) in billsData.by_supplier"
              :key="supplier"
              class="border-b pb-2 last:border-0"
            >
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ supplier }}</span>
                <span class="text-sm text-muted-foreground">{{ data.count }} bills</span>
              </div>
              <div class="mt-1 text-sm">
                <span class="text-muted-foreground">Total:</span>
                <span class="font-semibold ml-1">{{ formatCurrency(data.total_amount) }}</span>
                <span class="text-muted-foreground ml-3">Outstanding:</span>
                <span class="font-semibold ml-1 text-orange-600">{{ formatCurrency(data.outstanding_amount) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Bills Table -->
      <Card>
        <CardHeader>
          <CardTitle>Bills ({{ billsData.bills.length }})</CardTitle>
          <CardDescription>Detailed list of all bills in the selected period</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bill Number</TableHead>
                <TableHead>Supplier</TableHead>
                <TableHead>Bill Date</TableHead>
                <TableHead>Due Date</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Paid</TableHead>
                <TableHead>Outstanding</TableHead>
                <TableHead>Progress</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="bill in billsData.bills"
                :key="bill.bill_id"
                :class="{ 'bg-red-50 dark:bg-red-950/20': bill.is_overdue }"
              >
                <TableCell class="font-medium">
                  <div>{{ bill.bill_number }}</div>
                  <div v-if="bill.purchase_order" class="text-xs text-muted-foreground">
                    PO: {{ bill.purchase_order.po_number }}
                  </div>
                </TableCell>
                <TableCell>{{ getSupplierName(bill) }}</TableCell>
                <TableCell>{{ formatDate(bill.bill_date) }}</TableCell>
                <TableCell>{{ formatDate(bill.due_date) }}</TableCell>
                <TableCell>{{ formatCurrency(bill.total_amount) }}</TableCell>
                <TableCell class="text-green-600 dark:text-green-400">{{ formatCurrency(bill.paid_amount) }}</TableCell>
                <TableCell class="text-orange-600 dark:text-orange-400">{{ formatCurrency(bill.outstanding_amount) }}</TableCell>
                <TableCell>
                  <div class="w-24">
                    <div class="text-xs text-muted-foreground mb-1">{{ paymentProgress(bill) }}%</div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="h-2 rounded-full"
                        :class="paymentProgress(bill) === 100 ? 'bg-green-600 dark:bg-green-500' : 'bg-blue-600 dark:bg-blue-500'"
                        :style="{ width: `${paymentProgress(bill)}%` }"
                      ></div>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusVariant(bill.status, bill.is_overdue)">
                    {{ getStatusLabel(bill.status, bill.is_overdue) }}
                  </Badge>
                </TableCell>
              </TableRow>

              <TableRow v-if="billsData.bills.length === 0">
                <TableCell colspan="9" class="text-center py-8 text-muted-foreground">
                  No bills found for the selected filters
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
