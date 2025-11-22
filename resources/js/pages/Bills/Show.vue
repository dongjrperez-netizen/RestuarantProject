<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItem } from '@/types';
import { ref, reactive } from 'vue';


interface Payment {
  payment_id: number;
  payment_reference: string;
  payment_amount: number;
  payment_method: string;
  payment_date: string;
  transaction_reference?: string;
  notes?: string;
  status: string;
  created_by?: {
    first_name: string;
    last_name: string;
  };
}

interface PurchaseOrderItem {
  purchase_order_item_id: number;
  ingredient: {
    ingredient_name: string;
  };
  ordered_quantity: number;
  received_quantity: number;
  unit_price: number;
  total_price: number;
  unit_of_measure: string;
}

interface Bill {
  bill_id: number;
  bill_number: string;
  supplier_invoice_number?: string;
  status: string;
  bill_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  total_amount: number;
  paid_amount: number;
  outstanding_amount: number;
  notes?: string;
  is_overdue: boolean;
  days_overdue: number;
  supplier: {
    supplier_id: number;
    supplier_name: string;
    contact_number?: string;
    email?: string;
    payment_terms?: string;
  } | null;
  purchase_order?: {
    po_number: string;
    order_date: string;
    items: PurchaseOrderItem[];
  } | null;
  payments: Payment[];
}

interface Props {
  bill: Bill;
}

const props = defineProps<Props>();

// Create a reactive bill object that can be updated
const bill = reactive<Bill>({ ...props.bill });

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
  { title: bill.bill_number, href: `/bills/${bill.bill_id}` },
];

// Status color mapping
const getStatusVariant = (status: string) => {
  switch (status.toLowerCase()) {
    case 'paid':
      return 'default'; // Green
    case 'pending':
      return 'secondary'; // Gray
    case 'partially_paid':
      return 'outline'; // Blue outline
    case 'overdue':
      return 'destructive'; // Red
    case 'cancelled':
      return 'outline'; // Gray outline
    default:
      return 'secondary';
  }
};

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

const formatStatus = (status: string) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Extract supplier info (handles manual receives)
const supplierInfo = ref({
  name: bill.supplier ? bill.supplier.supplier_name : extractSupplierNameFromNotes(bill.notes),
  contactNumber: bill.supplier?.contact_number || null,
  email: bill.supplier?.email || null,
  paymentTerms: bill.supplier?.payment_terms || 'NET 30',
  isManual: !bill.supplier,
});

function extractSupplierNameFromNotes(notes?: string): string {
  if (!notes) return 'Unknown Supplier';

  // Pattern: "Auto-generated from manual receive - {supplier_name}"
  const manualReceiveMatch = notes.match(/manual receive\s*-\s*([^|]+)/i);
  if (manualReceiveMatch) {
    return manualReceiveMatch[1].trim();
  }

  // Fallback pattern for PO notes: "Supplier: {name}"
  const supplierMatch = notes.match(/Supplier:\s*([^|]+)/);
  if (supplierMatch) {
    return supplierMatch[1].trim();
  }

  return 'Unknown Supplier';
}

// Payment form
const showPaymentDialog = ref(false);
const paymentForm = useForm({
  payment_amount: 0,
  payment_method: 'cash',
  payment_date: new Date().toISOString().split('T')[0],
  transaction_reference: '',
  notes: ''
});

const recordPayment = async () => {
  try {
    // Validate form data before sending
    if (!paymentForm.payment_amount || paymentForm.payment_amount <= 0) {
      throw new Error('Please enter a valid payment amount');
    }

    if (paymentForm.payment_amount > bill.outstanding_amount) {
      throw new Error('Payment amount cannot exceed outstanding amount');
    }

    // If payment method is GCash, redirect to PayMongo checkout
    if (paymentForm.payment_method === 'gcash') {
      await processGCashPayment();
      return;
    }

    // Get fresh CSRF token to ensure it's not stale
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
      throw new Error('CSRF token not found. Please refresh the page and try again.');
    }

    const response = await fetch(route('bills.quick-payment', bill.bill_id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        payment_amount: paymentForm.payment_amount,
        payment_method: paymentForm.payment_method,
        payment_date: paymentForm.payment_date,
        transaction_reference: paymentForm.transaction_reference,
        notes: paymentForm.notes,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Payment failed');
    }

    const data = await response.json();

    if (data.success) {
      // Update the reactive bill object with the updated data
      if (data.data && data.data.bill) {
        try {
          // Update each property individually to maintain reactivity
          bill.status = data.data.bill.status;
          bill.paid_amount = data.data.bill.paid_amount;
          bill.outstanding_amount = data.data.bill.outstanding_amount;

          // Update payments array if provided
          if (data.data.bill.payments) {
            bill.payments = [...data.data.bill.payments];
          }

          console.log('Bill updated successfully:', {
            status: bill.status,
            paid_amount: bill.paid_amount,
            outstanding_amount: bill.outstanding_amount
          });
        } catch (updateError) {
          console.error('Error updating bill data:', updateError);
          // Fallback: reload the page if update fails
          window.location.reload();
          return;
        }
      }

      showPaymentDialog.value = false;
      paymentForm.reset();

      // Show success message
      console.log('Payment recorded successfully:', data.message);
    } else {
      throw new Error(data.message || 'Payment recording failed');
    }
  } catch (error) {
    console.error('Payment recording error:', error);
    let message = 'Payment recording failed';
    if (error instanceof Error) {
      message += ': ' + error.message;
    }
    alert(message);
  }
};

// Process GCash payment via PayMongo
const processGCashPayment = async () => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
      throw new Error('CSRF token not found. Please refresh the page and try again.');
    }

    const response = await fetch(route('bills.gcash.checkout'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        bill_id: bill.bill_id,
        payment_amount: paymentForm.payment_amount,
        notes: paymentForm.notes,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'GCash payment processing failed');
    }

    const data = await response.json();

    if (data.checkout_url) {
      // Redirect to PayMongo checkout page
      window.location.href = data.checkout_url;
    } else {
      throw new Error('No checkout URL received');
    }
  } catch (error) {
    console.error('GCash payment error:', error);
    alert('GCash payment failed: ' + (error instanceof Error ? error.message : 'Unknown error'));
  }
};

// Set payment amount to full outstanding amount
const payFullAmount = () => {
  paymentForm.payment_amount = bill?.outstanding_amount || 0;
};

const getPaymentMethodDisplay = (method: string) => {
  const methods: Record<string, string> = {
    'cash': 'Cash',
    'gcash': 'GCash',
    'check': 'Check',
    'bank_transfer': 'Bank Transfer',
    'credit_card': 'Credit Card',
    'paypal': 'PayPal',
    'online': 'Online Payment',
    'other': 'Other'
  };
  return methods[method] || method;
};

// PayPal payment form
const showPaypalDialog = ref(false);
const paypalForm = ref({
  payment_amount: 0,
  notes: '',
  processing: false
});

const payWithPaypal = async () => {
  paypalForm.value.processing = true;

  try {
    // Validate PayPal form data before sending
    if (!paypalForm.value.payment_amount || paypalForm.value.payment_amount <= 0) {
      throw new Error('Please enter a valid payment amount');
    }

    if (paypalForm.value.payment_amount > bill.outstanding_amount) {
      throw new Error('Payment amount cannot exceed outstanding amount');
    }

    // Get fresh CSRF token to ensure it's not stale
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
      throw new Error('CSRF token not found. Please refresh the page and try again.');
    }

    const response = await fetch(route('bills.paypal.pay', bill.bill_id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        payment_amount: paypalForm.value.payment_amount,
        notes: paypalForm.value.notes,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Payment failed');
    }

    const data = await response.json();

    if (data.success && data.approval_url) {
      // Close the dialog and redirect to PayPal
      showPaypalDialog.value = false;
      window.location.href = data.approval_url;
    } else {
      throw new Error(data.message || 'PayPal payment failed');
    }
  } catch (error) {
    console.error('PayPal payment error:', error);
    // You could show an error message to the user here
    let message = 'PayPal payment failed';
    if (error instanceof Error) {
      message += ': ' + error.message;
    }
    alert(message);
  } finally {
    paypalForm.value.processing = false;
  }
};

// Set PayPal payment amount to full outstanding amount
const payFullAmountPaypal = () => {
  paypalForm.value.payment_amount = bill?.outstanding_amount || 0;
};
</script>

<template>
  <Head :title="`Bill ${bill.bill_number}`" />

  <AppLayout title="Bill Details" :breadcrumbs="breadcrumbs">
    <div class="mx-6 space-y-6">
      <!-- Bill Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold tracking-tight">{{ bill.bill_number }}</h2>
          <p class="text-muted-foreground">
            Supplier: {{ supplierInfo.name }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <Badge :variant="getStatusVariant(bill.status)">
            {{ formatStatus(bill.status) }}
          </Badge>
          <Badge v-if="bill.is_overdue" variant="destructive">
            {{ bill.days_overdue }} days overdue
          </Badge>

          <!-- Payment Buttons -->
          <!-- <div v-if="bill.outstanding_amount > 0" class="flex space-x-2"> -->
             <!-- Manual Payment Dialog -->
            <!-- <Dialog v-model:open="showPaymentDialog">
              <DialogTrigger as-child>
                <Button variant="outline">Record Payment</Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-md">
                <DialogHeader>
                  <DialogTitle>Record Payment</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="recordPayment" class="space-y-4">
                  <div>
                    <Label for="amount">Payment Amount</Label>
                    <div class="flex space-x-2">
                      <Input
                        id="amount"
                        v-model.number="paymentForm.payment_amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="bill.outstanding_amount"
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
                    <p class="text-sm text-muted-foreground mt-1">
                      Outstanding: {{ formatCurrency(bill.outstanding_amount) }}
                    </p>
                  </div>

                  <div>
                    <Label for="method">Payment Method</Label>
                    <Select v-model="paymentForm.payment_method" required>
                      <SelectTrigger>
                        <SelectValue placeholder="Select payment method" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="cash">Cash</SelectItem>
                        <SelectItem value="gcash">GCash</SelectItem>
                        <SelectItem value="check">Check</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div>
                    <Label for="date">Payment Date</Label>
                    <Input
                      id="date"
                      v-model="paymentForm.payment_date"
                      type="date"
                      required
                    />
                  </div>

                  <div>
                    <Label for="reference">Transaction Reference</Label>
                    <Input
                      id="reference"
                      v-model="paymentForm.transaction_reference"
                      placeholder="Optional transaction reference"
                    />
                  </div>

                  <div>
                    <Label for="notes">Notes</Label>
                    <Textarea
                      id="notes"
                      v-model="paymentForm.notes"
                      placeholder="Optional payment notes"
                      rows="2"
                    />
                  </div>

                  <div class="flex justify-end space-x-2">
                    <Button
                      type="button"
                      variant="outline"
                      @click="showPaymentDialog = false"
                    >
                      Cancel
                    </Button>
                    <Button
                      type="submit"
                      :disabled="paymentForm.processing"
                    >
                      {{ paymentForm.processing ? 'Recording...' : 'Record Payment' }}
                    </Button>
                  </div>
                </form>
              </DialogContent>
            </Dialog> -->

            <!-- PayPal Payment Dialog -->
            <!-- <Dialog v-model:open="showPaypalDialog">
              <DialogTrigger as-child>
                <Button class="bg-blue-600 hover:bg-blue-700">
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a9.124 9.124 0 0 1-.465 2.963c-1.127 5.731-5.139 7.12-9.928 7.12h-1.988a.641.641 0 0 0-.633.74l-.654 4.14-.186 1.179a.33.33 0 0 0 .325.381h2.735a.563.563 0 0 0 .555-.474l.023-.12.447-2.83.029-.156a.563.563 0 0 1 .555-.474h.35c3.828 0 6.822-1.553 7.702-6.05.37-1.896.18-3.476-.733-4.612a3.896 3.896 0 0 0-1.033-.778z"/>
                  </svg>
                  Pay with PayPal
                </Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-md">
                <DialogHeader>
                  <DialogTitle>Pay with PayPal</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="payWithPaypal" class="space-y-4">
                  <div>
                    <Label for="paypal-amount">Payment Amount</Label>
                    <div class="flex space-x-2">
                      <Input
                        id="paypal-amount"
                        v-model.number="paypalForm.payment_amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="bill.outstanding_amount"
                        required
                      />
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="payFullAmountPaypal"
                      >
                        Full
                      </Button>
                    </div>
                    <p class="text-sm text-muted-foreground mt-1">
                      Outstanding: {{ formatCurrency(bill.outstanding_amount) }}
                    </p>
                  </div>

                  <div>
                    <Label for="paypal-notes">Notes (Optional)</Label>
                    <Textarea
                      id="paypal-notes"
                      v-model="paypalForm.notes"
                      placeholder="Optional payment notes"
                      rows="2"
                    />
                  </div>

                  <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                      <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a9.124 9.124 0 0 1-.465 2.963c-1.127 5.731-5.139 7.12-9.928 7.12h-1.988a.641.641 0 0 0-.633.74l-.654 4.14-.186 1.179a.33.33 0 0 0 .325.381h2.735a.563.563 0 0 0 .555-.474l.023-.12.447-2.83.029-.156a.563.563 0 0 1 .555-.474h.35c3.828 0 6.822-1.553 7.702-6.05.37-1.896.18-3.476-.733-4.612a3.896 3.896 0 0 0-1.033-.778z"/>
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-blue-900">Secure PayPal Payment</p>
                        <p class="text-xs text-blue-700">You'll be redirected to PayPal to complete the payment</p>
                      </div>
                    </div>
                  </div>

                  <div class="flex justify-end space-x-2">
                    <Button
                      type="button"
                      variant="outline"
                      @click="showPaypalDialog = false"
                    >
                      Cancel
                    </Button>
                    <Button
                      type="submit"
                      :disabled="paypalForm.processing"
                      class="bg-blue-600 hover:bg-blue-700"
                    >
                      {{ paypalForm.processing ? 'Processing...' : 'Continue to PayPal' }}
                    </Button>
                  </div>
                </form>
              </DialogContent>
            </Dialog>
          </div> -->
        </div>
      </div> 

      <div class="grid gap-6 md:grid-cols-2">
        <!-- Bill Information -->
        <Card>
          <CardHeader>
            <CardTitle>Bill Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Bill Number</p>
                <p class="font-medium">{{ bill.bill_number }}</p>
              </div>
              <div v-if="bill.supplier_invoice_number">
                <p class="text-sm font-medium text-muted-foreground">Invoice Number</p>
                <p class="font-medium">{{ bill.supplier_invoice_number }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Bill Date</p>
                <p class="font-medium">{{ formatDate(bill.bill_date) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Due Date</p>
                <p class="font-medium" :class="bill.is_overdue ? 'text-destructive' : ''">
                  {{ formatDate(bill.due_date) }}
                </p>
              </div>
            </div>
            <div v-if="bill.notes" class="pt-4">
              <p class="text-sm font-medium text-muted-foreground">Notes</p>
              <p class="text-sm mt-1">{{ bill.notes }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Supplier Information -->
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Supplier</p>
              <p class="font-medium">
                {{ supplierInfo.name }}
                <Badge v-if="supplierInfo.isManual" variant="secondary" class="ml-2">Unregister</Badge>
              </p>
            </div>
            <div v-if="supplierInfo.contactNumber || supplierInfo.paymentTerms" class="grid grid-cols-2 gap-4">
              <div v-if="supplierInfo.contactNumber">
                <p class="text-sm font-medium text-muted-foreground">Contact</p>
                <p class="font-medium">{{ supplierInfo.contactNumber }}</p>
              </div>
              <div v-if="supplierInfo.paymentTerms">
                <p class="text-sm font-medium text-muted-foreground">Terms</p>
                <p class="font-medium">{{ supplierInfo.paymentTerms }}</p>
              </div>
            </div>
            <div v-if="supplierInfo.email">
              <p class="text-sm font-medium text-muted-foreground">Email</p>
              <p class="font-medium">{{ supplierInfo.email }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Amount Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>Amount Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span>Subtotal</span>
              <span>{{ formatCurrency(bill.subtotal) }}</span>
            </div>
            <div v-if="bill.discount_amount > 0" class="flex justify-between text-green-600">
              <span>Discount</span>
              <span>-{{ formatCurrency(bill.discount_amount) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Tax</span>
              <span>{{ formatCurrency(bill.tax_amount) }}</span>
            </div>
            <Separator />
            <div class="flex justify-between font-semibold">
              <span>Total Amount</span>
              <span>{{ formatCurrency(bill.total_amount) }}</span>
            </div>
            <div v-if="bill.paid_amount > 0" class="flex justify-between text-green-600">
              <span>Paid Amount</span>
              <span>{{ formatCurrency(bill.paid_amount) }}</span>
            </div>
            <div v-if="bill.outstanding_amount > 0" class="flex justify-between font-semibold text-orange-600">
              <span>Outstanding</span>
              <span>{{ formatCurrency(bill.outstanding_amount) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Purchase Order Items (if available) -->
      <Card v-if="bill.purchase_order">
        <CardHeader>
          <CardTitle>Purchase Order Items</CardTitle>
          <p class="text-sm text-muted-foreground">
            From PO: {{ bill.purchase_order.po_number }}
            ({{ formatDate(bill.purchase_order.order_date) }})
          </p>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Item</TableHead>
                <TableHead>Ordered</TableHead>
                <TableHead>Received</TableHead>
                <TableHead>Unit Price</TableHead>
                <TableHead>Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in bill.purchase_order.items" :key="item.purchase_order_item_id">
                <TableCell class="font-medium">
                  {{ item.ingredient.ingredient_name }}
                </TableCell>
                <TableCell>
                  {{ item.ordered_quantity }} {{ item.unit_of_measure }}
                </TableCell>
                <TableCell>
                  {{ item.received_quantity }} {{ item.unit_of_measure }}
                </TableCell>
                <TableCell>{{ formatCurrency(item.unit_price) }}</TableCell>
                <TableCell>{{ formatCurrency(item.total_price) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Payment History -->
      <Card v-if="bill.payments.length > 0">
        <CardHeader>
          <CardTitle>Payment History</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Reference</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Method</TableHead>
                <TableHead>Transaction Ref</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Created By</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="payment in bill.payments" :key="payment.payment_id">
                <TableCell class="font-medium">
                  {{ payment.payment_reference }}
                </TableCell>
                <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                <TableCell>{{ formatCurrency(payment.payment_amount) }}</TableCell>
                <TableCell>{{ getPaymentMethodDisplay(payment.payment_method) }}</TableCell>
                <TableCell>
                  <span v-if="payment.transaction_reference" class="text-sm">
                    {{ payment.transaction_reference }}
                  </span>
                  <span v-else class="text-sm text-muted-foreground">-</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="payment.status === 'completed' ? 'default' : 'secondary'">
                    {{ formatStatus(payment.status) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ payment.created_by ? `${payment.created_by.first_name} ${payment.created_by.last_name}` : '-' }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex justify-between">
        <Button variant="outline" as-child>
          <Link href="/bills">← Back to Bills</Link>
        </Button>

        <div class="space-x-2">
          <!-- <Button v-if="bill.status !== 'paid'" variant="outline" as-child>
            <Link :href="`/bills/${bill.bill_id}/edit`">Edit Bill</Link>
          </Button> -->
          <Button variant="outline" as-child>
            <a :href="route('bills.download-pdf', bill.bill_id)" target="_blank">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Download PDF
            </a>
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>