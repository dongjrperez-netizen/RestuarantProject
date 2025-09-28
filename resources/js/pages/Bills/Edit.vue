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
}


interface Bill {
  bill_id: number;
  bill_number: string;
  supplier_invoice_number?: string;
  bill_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  total_amount: number;
  notes?: string;
  supplier: Supplier;
}

interface Props {
  bill: Bill;
  suppliers: Supplier[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Bills', href: '/bills' },
  { title: props.bill.bill_number, href: `/bills/${props.bill.bill_id}` },
  { title: 'Edit', href: `/bills/${props.bill.bill_id}/edit` },
];

const form = useForm({
  supplier_invoice_number: props.bill.supplier_invoice_number || '',
  bill_date: props.bill.bill_date,
  due_date: props.bill.due_date,
  subtotal: props.bill.subtotal,
  tax_amount: props.bill.tax_amount,
  discount_amount: props.bill.discount_amount,
  total_amount: props.bill.total_amount,
  notes: props.bill.notes || ''
});

const updateTotals = () => {
  const subtotalAfterDiscount = form.subtotal - form.discount_amount;
  form.total_amount = subtotalAfterDiscount + form.tax_amount;
};

const submit = () => {
  form.put(route('bills.update', props.bill.bill_id));
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};
</script>

<template>
  <Head :title="`Edit Bill ${bill.bill_number}`" />

  <AppLayout title="Edit Bill" :breadcrumbs="breadcrumbs">
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Edit Bill</h2>
        <p class="text-muted-foreground">
          {{ bill.bill_number }} - {{ bill.supplier.supplier_name }}
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Bill Details -->
        <Card>
          <CardHeader>
            <CardTitle>Bill Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="invoice_number">Supplier Invoice Number</Label>
                <Input
                  id="invoice_number"
                  v-model="form.supplier_invoice_number"
                  placeholder="Optional invoice number"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="bill_date">Bill Date</Label>
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
                <Label for="due_date">Due Date</Label>
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
              <Label for="subtotal">Subtotal</Label>
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
                <Label for="tax">Tax Amount</Label>
                <Input
                  id="tax"
                  v-model.number="form.tax_amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  @input="updateTotals"
                />
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
                required
                readonly
                class="bg-muted"
              />
              <p class="text-sm text-muted-foreground mt-1">
                Calculated automatically: (Subtotal - Discount) + Tax
              </p>
              <p v-if="form.errors.total_amount" class="text-sm text-destructive mt-1">
                {{ form.errors.total_amount }}
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
            <p v-if="form.errors.notes" class="text-sm text-destructive mt-1">
              {{ form.errors.notes }}
            </p>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-between">
          <Button variant="outline" as-child>
            <Link :href="`/bills/${bill.bill_id}`">← Cancel</Link>
          </Button>
          
          <Button 
            type="submit" 
            :disabled="form.processing"
          >
            {{ form.processing ? 'Updating...' : 'Update Bill' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>