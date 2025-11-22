<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  email: string;
}

interface Payment {
  payment_reference: string;
  payment_date: string;
  payment_method: string;
  payment_amount: number;
  status: string;
  po_number: string;
  total_amount: number;
  po_status: string;
  balance: number;
}

interface PaginatedPayments {
  data: Payment[];
  links: {
    url: string;
    label: string;
    active: boolean;
  }[];
  meta?: {
    current_page: number;
    last_page: number;
  };
}



interface Props {
  supplier: Supplier;
  payments: PaginatedPayments;
}

defineProps<Props>();

const formatCurrency = (amount: number) =>
  `₱${Number(amount).toLocaleString(undefined, {
    minimumFractionDigits: 2,
  })}`;

const formatDate = (dateString: string) => {
  const d = new Date(dateString);
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

const getStatusColor = (status?: string) => {
  switch (status?.toLowerCase()) {
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'failed':
      return 'bg-red-100 text-red-800';
    case 'cancelled':
      return 'bg-gray-200 text-gray-700';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <Head title="Payment History" />

  <SupplierLayout :supplier="supplier">
    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Payment History</h1>
        <p class="text-muted-foreground">
          A list of all your payment transactions and partial payments
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Recent Payments</CardTitle>
          <CardDescription>
            View all your completed, pending, or partial payments linked to your
            purchase orders.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div
            v-if="payments.data.length === 0"
            class="text-center text-muted-foreground py-8"
          >
            No payment records found.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-lg text-sm">
              <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                  <th class="px-4 py-2 text-left">Date</th>
                  <th class="px-4 py-2 text-left">Reference</th>
                  <th class="px-4 py-2 text-left">PO Number</th>
                  <th class="px-4 py-2 text-left">PO Status</th>
                  <th class="px-4 py-2 text-left">Amount Paid</th>
                  <th class="px-4 py-2 text-left">Total</th>
                  <th class="px-4 py-2 text-left">Balance</th>
                  <th class="px-4 py-2 text-left">Method</th>
                  <th class="px-4 py-2 text-left">Status</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(payment, index) in payments.data"
                  :key="index"
                  class="border-t hover:bg-gray-50 transition"
                >
                  <td class="px-4 py-2">
                    {{ formatDate(payment.payment_date) }}
                  </td>
                  <td class="px-4 py-2 font-medium">
                    {{ payment.payment_reference }}
                  </td>
                  <td class="px-4 py-2">
                    {{ payment.po_number || 'N/A' }}
                  </td>
                  <td class="px-4 py-2">
                    {{ payment.po_status || 'N/A' }}
                  </td>
                  <td class="px-4 py-2 font-semibold text-green-700">
                    {{ formatCurrency(payment.payment_amount) }}
                  </td>
                  <td class="px-4 py-2">
                    {{ formatCurrency(payment.total_amount) }}
                  </td>
                  <td class="px-4 py-2 text-red-700">
                    {{ formatCurrency(payment.balance) }}
                  </td>
                  <td class="px-4 py-2">{{ payment.payment_method }}</td>
                  <td class="px-4 py-2">
                    <Badge :class="getStatusColor(payment.status)">
                      {{ payment.status }}
                    </Badge>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="flex justify-center items-center gap-3 py-4 border-t bg-gray-50">
              <button
                v-if="payments.links[0]?.url"
                @click="$inertia.get(payments.links[0].url)"
                class="px-4 py-1.5 border rounded-md text-sm bg-white hover:bg-gray-100 transition"
              >
                ‹ Previous
              </button>

              <span class="text-gray-500 text-sm">
                Page {{ payments.meta?.current_page || '?' }} of {{ payments.meta?.last_page || '?' }}
              </span>

              <button
                v-if="payments.links[payments.links.length - 1]?.url"
                @click="$inertia.get(payments.links[payments.links.length - 1].url)"
                class="px-4 py-1.5 border rounded-md text-sm bg-white hover:bg-gray-100 transition"
              >
                Next ›
              </button>
            </div>


          </div>
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>