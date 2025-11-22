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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'

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
  supplier: Supplier | null;
  notes?: string;
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

const payFullAmount = () => {
  if (selectedBill.value) {
    form.payment_amount = selectedBill.value.outstanding_amount;
  }
};


const showModal = ref(false)
const modalTitle = ref('')
const modalMessage = ref('')
const modalType = ref('success') 

const dataRedirectUrl = ref('')

const handleOk = () => {
  showModal.value = false
  if (modalType.value === 'success') {
    // redirect only if success
    window.location.href = dataRedirectUrl.value || route('bills.index')
  }
}


const submit = async () => {
  if (!selectedBill.value) {
    alert('Please select a bill first');
    return;
  }

  // All payment methods are now recorded offline
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(route('payments.cash'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        bill_id: selectedBill.value.bill_id,
        payment_amount: form.payment_amount,
        payment_method: form.payment_method,
        payment_date: form.payment_date,
        transaction_reference: form.transaction_reference,
        notes: form.notes,
      }),
    });

    const data = await response.json();

    dataRedirectUrl.value = data.redirect_url

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Payment recording failed');
    }

    // ✅ Show success modal
    modalTitle.value = 'Payment Recorded'
    modalMessage.value = data.message
    modalType.value = 'success'
    showModal.value = true
  } catch (error) {
    console.error('Payment recording error:', error)
    modalTitle.value = 'Payment Failed'
    modalMessage.value = error instanceof Error ? error.message : 'Unknown error'
    modalType.value = 'error'
    showModal.value = true
  }
};
</script>

<template>
  <Head title="Record Payment" />

  <AppLayout :breadcrumbs="breadcrumbs">
      <div class="space-y-6 mx-6">
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
                    <TableCell>{{ getSupplierName(bill) }}</TableCell>
                    <TableCell>{{ formatDate(bill.due_date) }}</TableCell>
                    <TableCell>{{ formatCurrency(bill.outstanding_amount) }}</TableCell>
                    <TableCell>
                      <Badge :variant="getStatusVariant(bill.status)">
                        {{ bill.status.replace('_', ' ') }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Button
                        :variant="selectedBillId === bill.bill_id ? 'default' : 'outline'"
                        size="sm"
                        @click="onBillSelect(bill.bill_id)"
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
                    <span class="ml-1">{{ getSupplierName(selectedBill) }}</span>
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
                    <SelectItem value="gcash">GCash</SelectItem>
                    <SelectItem value="paypal">PayPal</SelectItem>
                    <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                    <SelectItem value="check">Check</SelectItem>
                    <SelectItem value="credit_card">Credit Card</SelectItem>
                    <SelectItem value="online">Online Payment</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.payment_method" class="text-sm text-red-600 mt-1">
                  {{ form.errors.payment_method }}
                </div>
              </div>

              <div>
                <Label for="transaction_reference">Transaction Reference / Receipt No. *</Label>
                <Input
                  id="transaction_reference"
                  v-model="form.transaction_reference"
                  type="text"
                  placeholder="e.g., GCash Ref No., PayPal Transaction ID, Check No."
                  required
                />
                <p class="text-xs text-muted-foreground mt-1">
                  Enter the reference number from your payment receipt or transaction confirmation
                </p>
                <div v-if="form.errors.transaction_reference" class="text-sm text-red-600 mt-1">
                  {{ form.errors.transaction_reference }}
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
                  type="submit"
                  :disabled="form.processing || !selectedBill || !form.payment_amount || !form.transaction_reference"
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



<Dialog v-model:open="showModal">
  <DialogContent class="sm:max-w-md">
    <DialogHeader>
      <DialogTitle
        :class="modalType === 'success' ? 'text-green-600' : 'text-red-600'"
      >
        {{ modalTitle }}
      </DialogTitle>
      <DialogDescription class="mt-2">
        {{ modalMessage }}
      </DialogDescription>
    </DialogHeader>

    <!-- Add OK button -->
    <div class="mt-6 flex justify-end">
      <button
        type="button"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        @click="handleOk"
      >
        OK
      </button>
    </div>
  </DialogContent>
</Dialog>


</template>

