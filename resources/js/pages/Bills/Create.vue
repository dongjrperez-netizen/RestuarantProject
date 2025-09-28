<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItem } from '@/types';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  payment_terms?: string;
}


interface PurchaseOrder {
  purchase_order_id: number;
  po_number: string;
  supplier: {
    supplier_name: string;
  };
  total_amount: number;
  order_date: string;
  actual_delivery_date?: string;
  subtotal?: number;
  tax_amount?: number;
  discount_amount?: number;
}

interface Props {
  suppliers: Supplier[];
  purchaseOrders: PurchaseOrder[];
}

const props = defineProps<Props>();

// Debug: Log the props to console
console.log('Bills/Create - Props received:', props);
console.log('Purchase Orders count:', props.purchaseOrders?.length || 0);
console.log('Suppliers count:', props.suppliers?.length || 0);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
  { title: 'Create', href: '/bills/create' },
];

const form = useForm({
  purchase_order_id: 'none' as string | null,
  supplier_id: '',
  supplier_invoice_number: '',
  bill_date: new Date().toISOString().split('T')[0],
  due_date: '',
  subtotal: 0,
  tax_amount: 0,
  discount_amount: 0,
  total_amount: 0,
  notes: ''
});

const updateTotals = () => {
  const subtotalAfterDiscount = form.subtotal - form.discount_amount;
  form.total_amount = subtotalAfterDiscount + form.tax_amount;
};

const calculateTaxFromRate = (rate: number) => {
  const subtotalAfterDiscount = form.subtotal - form.discount_amount;
  form.tax_amount = (subtotalAfterDiscount * rate) / 100;
  updateTotals();
};

const onPurchaseOrderChange = () => {
  if (form.purchase_order_id && form.purchase_order_id !== 'none') {
    const po = props.purchaseOrders.find(p => p.purchase_order_id.toString() === form.purchase_order_id);
    if (po) {
      // Auto-fill supplier
      const supplier = props.suppliers.find(s => s.supplier_name === po.supplier.supplier_name);
      if (supplier) {
        form.supplier_id = supplier.supplier_id.toString();
      }

      // Auto-fill bill date (use actual delivery date or today)
      const billDate = po.actual_delivery_date || new Date().toISOString().split('T')[0];
      form.bill_date = billDate;

      // Auto-fill due date based on supplier payment terms
      if (supplier && supplier.payment_terms) {
        const date = new Date(billDate);
        let daysToAdd = 30; // Default

        switch (supplier.payment_terms) {
          case 'COD':
          case 'NET_0':
            daysToAdd = 0;
            break;
          case 'NET_7':
            daysToAdd = 7;
            break;
          case 'NET_15':
            daysToAdd = 15;
            break;
          case 'NET_30':
            daysToAdd = 30;
            break;
          case 'NET_60':
            daysToAdd = 60;
            break;
          case 'NET_90':
            daysToAdd = 90;
            break;
        }

        const dueDate = new Date(date);
        dueDate.setDate(dueDate.getDate() + daysToAdd);
        form.due_date = dueDate.toISOString().split('T')[0];
      }

      // Auto-fill amounts from PO
      form.subtotal = Number(po.subtotal || po.total_amount || 0);
      form.discount_amount = Number(po.discount_amount || 0);
      form.tax_amount = Number(po.tax_amount || 0);
      
      // Calculate total amount
      updateTotals();
    }
  } else {
    // Clear fields when "No Purchase Order" is selected
    form.supplier_id = '';
    form.subtotal = 0;
    form.discount_amount = 0;
    form.tax_amount = 0;
    form.total_amount = 0;
  }
};

const onSupplierChange = () => {
  if (form.supplier_id) {
    const supplier = props.suppliers.find(s => s.supplier_id.toString() === form.supplier_id);
    if (supplier && supplier.payment_terms) {
      // Set due date based on payment terms
      const billDate = new Date(form.bill_date);
      let daysToAdd = 30; // Default
      
      switch (supplier.payment_terms) {
        case 'COD':
        case 'NET_0':
          daysToAdd = 0;
          break;
        case 'NET_7':
          daysToAdd = 7;
          break;
        case 'NET_15':
          daysToAdd = 15;
          break;
        case 'NET_30':
          daysToAdd = 30;
          break;
        case 'NET_60':
          daysToAdd = 60;
          break;
        case 'NET_90':
          daysToAdd = 90;
          break;
      }
      
      const dueDate = new Date(billDate);
      dueDate.setDate(dueDate.getDate() + daysToAdd);
      form.due_date = dueDate.toISOString().split('T')[0];
    }
  }
};

const submit = () => {
  form.transform((data) => ({
    ...data,
    purchase_order_id: data.purchase_order_id === 'none' ? null : data.purchase_order_id
  })).post(route('bills.store'));
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};
</script>

<template>
  <Head title="Create Bill" />

  <AppLayout title="Create Bill" :breadcrumbs="breadcrumbs">
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Create New Bill</h2>
        <p class="text-muted-foreground">
          Create a supplier bill manually or from a purchase order
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Purchase Order Selection -->
        <Card>
          <CardHeader>
            <CardTitle>Source Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="purchase_order">Purchase Order (Optional)</Label>
              <Select v-model="form.purchase_order_id" @update:model-value="onPurchaseOrderChange">
                <SelectTrigger>
                  <SelectValue placeholder="Select a purchase order" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No Purchase Order</SelectItem>
                  <SelectItem 
                    v-for="po in purchaseOrders" 
                    :key="po.purchase_order_id"
                    :value="po.purchase_order_id.toString()"
                  >
                    {{ po.po_number }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p class="text-sm text-muted-foreground mt-1">
                Select a purchase order to pre-fill bill details
              </p>
            </div>

            <div>
              <Label for="supplier">Supplier *</Label>
              <Select v-model="form.supplier_id" @update:model-value="onSupplierChange" required>
                <SelectTrigger>
                  <SelectValue placeholder="Select supplier" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="supplier in suppliers" 
                    :key="supplier.supplier_id"
                    :value="supplier.supplier_id.toString()"
                  >
                    {{ supplier.supplier_name }}
                    {{ supplier.payment_terms ? `(${supplier.payment_terms})` : '' }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.supplier_id" class="text-sm text-destructive mt-1">
                {{ form.errors.supplier_id }}
              </p>
            </div>

            <div>
              <Label for="invoice_number">Supplier Invoice Number</Label>
              <Input
                id="invoice_number"
                v-model="form.supplier_invoice_number"
                placeholder="Supplier's invoice number"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Bill Details -->
        <Card>
          <CardHeader>
            <CardTitle>Bill Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="bill_date">Bill Date *</Label>
                <Input
                  id="bill_date"
                  v-model="form.bill_date"
                  type="date"
                  required
                />
                <p v-if="form.errors.bill_date" class="text-sm text-destructive mt-1">
                  {{ form.errors.bill_date }}
                </p>
              </div>
              <div>
                <Label for="due_date">Due Date *</Label>
                <Input
                  id="due_date"
                  v-model="form.due_date"
                  type="date"
                  required
                />
                <p v-if="form.errors.due_date" class="text-sm text-destructive mt-1">
                  {{ form.errors.due_date }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Amount Details -->
        <Card>
          <CardHeader>
            <CardTitle>Amount Breakdown</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="subtotal">Subtotal *</Label>
              <Input
                id="subtotal"
                v-model.number="form.subtotal"
                type="number"
                step="0.01"
                min="0"
                required
                @input="updateTotals"
              />
              <p v-if="form.errors.subtotal" class="text-sm text-destructive mt-1">
                {{ form.errors.subtotal }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="discount">Discount Amount</Label>
                <Input
                  id="discount"
                  v-model.number="form.discount_amount"
                  type="number"
                  step="0.01"
                  min="0"
                  @input="updateTotals"
                />
                <p v-if="form.errors.discount_amount" class="text-sm text-destructive mt-1">
                  {{ form.errors.discount_amount }}
                </p>
              </div>
              <div>
                <Label for="tax">Tax Amount *</Label>
                <div class="flex space-x-2">
                  <Input
                    id="tax"
                    v-model.number="form.tax_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    @input="updateTotals"
                  />
                  <Button 
                    type="button" 
                    variant="outline" 
                    size="sm"
                    @click="calculateTaxFromRate(12)"
                  >
                    12%
                  </Button>
                </div>
                <p v-if="form.errors.tax_amount" class="text-sm text-destructive mt-1">
                  {{ form.errors.tax_amount }}
                </p>
              </div>
            </div>

            <div>
              <Label for="total">Total Amount</Label>
              <Input
                id="total"
                v-model.number="form.total_amount"
                type="number"
                step="0.01"
                min="0"
                readonly
                class="bg-muted font-semibold text-lg"
              />
              <p class="text-sm text-muted-foreground mt-1">
                Calculated: ({{ formatCurrency(form.subtotal) }} - {{ formatCurrency(form.discount_amount) }}) + {{ formatCurrency(form.tax_amount) }}
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Notes -->
        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <Label for="notes">Additional Notes</Label>
            <Textarea
              id="notes"
              v-model="form.notes"
              placeholder="Optional notes about this bill"
              rows="3"
            />
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-between">
          <Button variant="outline" as-child>
            <Link href="/bills">← Cancel</Link>
          </Button>
          
          <Button 
            type="submit" 
            :disabled="form.processing"
          >
            {{ form.processing ? 'Creating...' : 'Create Bill' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>