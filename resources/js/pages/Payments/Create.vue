<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number?: string;
  email?: string;
}

interface Bill {
  bill_id: number;
  bill_number: string;
  supplier_invoice_number?: string;
  status: string;
  bill_date: string;
  due_date: string;
  total_amount: number;
  paid_amount: number;
  outstanding_amount: number;
  supplier: Supplier;
}

interface Props {
  bills: Bill[];
  selectedBill?: Bill | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
  { title: 'Record Payment', href: '#' },
];

const selectedBillId = ref<number | null>(props.selectedBill?.bill_id || null);

const form = useForm({
  bill_id: props.selectedBill?.bill_id || null,
  payment_amount: 0,
  payment_method: 'cash',
  payment_date: new Date().toISOString().split('T')[0],
  transaction_reference: '',
  notes: ''
});

const selectedBill = computed(() => {
  if (!selectedBillId.value) return null;
  return props.bills.find(bill => bill.bill_id === selectedBillId.value);
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getStatusVariant = (status: string) => {
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

const onBillSelect = (billId: number) => {
  selectedBillId.value = billId;
  form.bill_id = billId;
  form.payment_amount = 0; // Reset amount when switching bills
};

const payFullAmount = () => {
  if (selectedBill.value) {
    form.payment_amount = selectedBill.value.outstanding_amount;
  }
};

const submit = () => {
  form.post('/billing/payments/record-payment');
};

// PayPal payment functionality
const payWithPaypal = async () => {
  if (!selectedBill.value) {
    alert('Please select a bill first');
    return;
  }

  try {
    const response = await fetch(route('bills.paypal.pay', selectedBill.value.bill_id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        payment_amount: form.payment_amount,
        notes: form.notes,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Payment failed');
    }

    const data = await response.json();

    if (data.success && data.approval_url) {
      // Redirect to PayPal
      window.location.href = data.approval_url;
    } else {
      throw new Error(data.message || 'PayPal payment failed');
    }
  } catch (error) {
    console.error('PayPal payment error:', error);
    alert('PayPal payment failed: ' + error.message);
  }
};
</script>

<template>
  <Head title="Record Payment" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Record Payment</h1>
        <p class="text-muted-foreground">Record a payment for supplier bills</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Bill Selection -->
        <Card>
          <CardHeader>
            <CardTitle>Select Bill</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-if="bills.length === 0" class="text-center py-8 text-muted-foreground">
                No outstanding bills found.
              </div>

              <Table v-else>
                <TableHeader>
                  <TableRow>
                    <TableHead>Bill</TableHead>
                    <TableHead>Supplier</TableHead>
                    <TableHead>Due Date</TableHead>
                    <TableHead>Outstanding</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="bill in bills"
                    :key="bill.bill_id"
                    :class="{ 'bg-muted/50': selectedBillId === bill.bill_id }"
                  >
                    <TableCell class="font-medium">{{ bill.bill_number }}</TableCell>
                    <TableCell>{{ bill.supplier.supplier_name }}</TableCell>
                    <TableCell>{{ formatDate(bill.due_date) }}</TableCell>
                    <TableCell>{{ formatCurrency(bill.outstanding_amount) }}</TableCell>
                    <TableCell>
                      <Badge :variant="getStatusVariant(bill.status)">
                        {{ bill.status.replace('_', ' ') }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Button
                        variant="outline"
                        size="sm"
                        @click="onBillSelect(bill.bill_id)"
                        :class="{ 'bg-primary text-primary-foreground': selectedBillId === bill.bill_id }"
                      >
                        {{ selectedBillId === bill.bill_id ? 'Selected' : 'Select' }}
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        <!-- Payment Form -->
        <Card>
          <CardHeader>
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="!selectedBill" class="text-center py-8 text-muted-foreground">
              Please select a bill to record payment.
            </div>

            <form v-else @submit.prevent="submit" class="space-y-4">
              <!-- Selected Bill Info -->
              <div class="bg-muted p-4 rounded-lg">
                <h4 class="font-medium mb-2">{{ selectedBill.bill_number }}</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <div>
                    <span class="text-muted-foreground">Supplier:</span>
                    <span class="ml-1">{{ selectedBill.supplier.supplier_name }}</span>
                  </div>
                  <div>
                    <span class="text-muted-foreground">Outstanding:</span>
                    <span class="ml-1 font-medium">{{ formatCurrency(selectedBill.outstanding_amount) }}</span>
                  </div>
                  <div>
                    <span class="text-muted-foreground">Total:</span>
                    <span class="ml-1">{{ formatCurrency(selectedBill.total_amount) }}</span>
                  </div>
                  <div>
                    <span class="text-muted-foreground">Paid:</span>
                    <span class="ml-1">{{ formatCurrency(selectedBill.paid_amount) }}</span>
                  </div>
                </div>
              </div>

              <div>
                <Label for="amount">Payment Amount *</Label>
                <div class="flex space-x-2">
                  <Input
                    id="amount"
                    v-model.number="form.payment_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :max="selectedBill.outstanding_amount"
                    required
                  />
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="payFullAmount"
                  >
                    Full
                  </Button>
                </div>
                <div v-if="form.errors.payment_amount" class="text-sm text-red-600 mt-1">
                  {{ form.errors.payment_amount }}
                </div>
              </div>

              <div>
                <Label for="method">Payment Method *</Label>
                <Select v-model="form.payment_method" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select payment method" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                    <SelectItem value="check">Check</SelectItem>
                    <SelectItem value="credit_card">Credit Card</SelectItem>
                    <SelectItem value="paypal">PayPal</SelectItem>
                    <SelectItem value="online">Online Payment</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.payment_method" class="text-sm text-red-600 mt-1">
                  {{ form.errors.payment_method }}
                </div>
              </div>

              <div>
                <Label for="date">Payment Date *</Label>
                <Input
                  id="date"
                  v-model="form.payment_date"
                  type="date"
                  required
                />
                <div v-if="form.errors.payment_date" class="text-sm text-red-600 mt-1">
                  {{ form.errors.payment_date }}
                </div>
              </div>

              <div>
                <Label for="reference">Transaction Reference</Label>
                <Input
                  id="reference"
                  v-model="form.transaction_reference"
                  placeholder="Optional transaction reference"
                />
                <div v-if="form.errors.transaction_reference" class="text-sm text-red-600 mt-1">
                  {{ form.errors.transaction_reference }}
                </div>
              </div>

              <div>
                <Label for="notes">Notes</Label>
                <Textarea
                  id="notes"
                  v-model="form.notes"
                  placeholder="Optional payment notes"
                  rows="3"
                />
                <div v-if="form.errors.notes" class="text-sm text-red-600 mt-1">
                  {{ form.errors.notes }}
                </div>
              </div>

              <div class="flex justify-end space-x-2 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  onclick="history.back()"
                >
                  Cancel
                </Button>
                <Button
                  type="button"
                  @click="payWithPaypal"
                  :disabled="!selectedBill || !form.payment_amount"
                  class="bg-blue-600 hover:bg-blue-700 text-white"
                >
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a9.124 9.124 0 0 1-.465 2.963c-1.127 5.731-5.139 7.12-9.928 7.12h-1.988a.641.641 0 0 0-.633.74l-.654 4.14-.186 1.179a.33.33 0 0 0 .325.381h2.735a.563.563 0 0 0 .555-.474l.023-.12.447-2.83.029-.156a.563.563 0 0 1 .555-.474h.35c3.828 0 6.822-1.553 7.702-6.05.37-1.896.18-3.476-.733-4.612a3.896 3.896 0 0 0-1.033-.778z"/>
                  </svg>
                  Pay with PayPal
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing || !selectedBill"
                >
                  {{ form.processing ? 'Recording...' : 'Record Payment' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>