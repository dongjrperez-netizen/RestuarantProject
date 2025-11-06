<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  contact_number: string | null;
  email: string | null;
  address: string | null;
  business_registration: string | null;
  tax_id: string | null;
  payment_terms: string;
  credit_limit: number | null;
  notes: string | null;
  is_active: boolean;
  ingredients: Array<{
    ingredient_id: number;
    ingredient_name: string;
    pivot: {
      package_unit: string;
      package_quantity: number;
      package_price: number;
      lead_time_days: number;
      minimum_order_quantity: number;
    };
  }>;
}

interface Props {
  supplier: Supplier;
  ingredients: Ingredient[];
}

const props = defineProps<Props>();

const form = useForm({
  supplier_name: props.supplier.supplier_name,
  contact_number: props.supplier.contact_number || '',
  email: props.supplier.email || '',
  address: props.supplier.address || '',
  business_registration: props.supplier.business_registration || '',
  tax_id: props.supplier.tax_id || '',
  payment_terms: props.supplier.payment_terms,
  credit_limit: props.supplier.credit_limit || '',
  notes: props.supplier.notes || '',
  is_active: !!props.supplier.is_active,
  ingredients: (props.supplier.ingredients || []).map((ingredient) => ({
    ingredient_id: ingredient.ingredient_id,
    package_unit: ingredient.pivot.package_unit,
    package_quantity: ingredient.pivot.package_quantity,
    package_price: ingredient.pivot.package_price,
    lead_time_days: ingredient.pivot.lead_time_days,
    minimum_order_quantity: ingredient.pivot.minimum_order_quantity,
  })),
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Suppliers',
    href: '/suppliers',
  },
  {
    title: 'Edit Supplier',
    href: `/suppliers/${props.supplier.supplier_id}/edit`,
  },
];

const submit = () => {
  form.patch(route('suppliers.update', props.supplier.supplier_id));
};
</script>

<template>
  <Head title="Edit Supplier" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Supplier</h1>
          <p class="text-gray-600 mt-1">
            Update supplier information
          </p>
        </div>
        <Link :href="route('suppliers.index')">
          <Button variant="outline">Back to Suppliers</Button>
        </Link>
      </div>

      <!-- Form -->
      <Card class="max-w-3xl">
        <CardHeader>
          <CardTitle>Supplier Information</CardTitle>
          <CardDescription>
            Update the details for this supplier
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Active Status -->
            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
              <Switch v-model="form.is_active" />
              <div>
                <Label class="font-medium">Active Status</Label>
                <p class="text-sm text-gray-600">
                  {{ form.is_active ? 'This supplier is active' : 'This supplier is inactive' }}
                </p>
              </div>
            </div>

            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="supplier_name">Supplier Name *</Label>
                <Input
                  id="supplier_name"
                  v-model="form.supplier_name"
                  type="text"
                  required
                  :class="{ 'border-red-500': form.errors.supplier_name }"
                />
                <div
                  v-if="form.errors.supplier_name"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.supplier_name }}
                </div>
              </div>

              <div>
                <Label for="contact_number">Contact Number</Label>
                <Input
                  id="contact_number"
                  v-model="form.contact_number"
                  type="text"
                  :class="{ 'border-red-500': form.errors.contact_number }"
                />
                <div
                  v-if="form.errors.contact_number"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.contact_number }}
                </div>
              </div>

              <div>
                <Label for="email">Email</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  :class="{ 'border-red-500': form.errors.email }"
                />
                <div
                  v-if="form.errors.email"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.email }}
                </div>
              </div>

              <div>
                <Label for="address">Address</Label>
                <Input
                  id="address"
                  v-model="form.address"
                  type="text"
                  :class="{ 'border-red-500': form.errors.address }"
                />
                <div
                  v-if="form.errors.address"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.address }}
                </div>
              </div>
            </div>

            <!-- Business Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="business_registration">Business Registration</Label>
                <Input
                  id="business_registration"
                  v-model="form.business_registration"
                  type="text"
                  :class="{
                    'border-red-500': form.errors.business_registration,
                  }"
                />
                <div
                  v-if="form.errors.business_registration"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.business_registration }}
                </div>
              </div>

              <div>
                <Label for="tax_id">Tax ID</Label>
                <Input
                  id="tax_id"
                  v-model="form.tax_id"
                  type="text"
                  :class="{ 'border-red-500': form.errors.tax_id }"
                />
                <div
                  v-if="form.errors.tax_id"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.tax_id }}
                </div>
              </div>

              <div>
                <Label for="payment_terms">Payment Terms *</Label>
                <Select v-model="form.payment_terms">
                  <SelectTrigger
                    :class="{ 'border-red-500': form.errors.payment_terms }"
                  >
                    <SelectValue placeholder="Select terms" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="COD">Cash on Delivery</SelectItem>
                    <SelectItem value="NET_7">Net 7</SelectItem>
                    <SelectItem value="NET_15">Net 15</SelectItem>
                    <SelectItem value="NET_30">Net 30</SelectItem>
                    <SelectItem value="NET_60">Net 60</SelectItem>
                    <SelectItem value="NET_90">Net 90</SelectItem>
                  </SelectContent>
                </Select>
                <div
                  v-if="form.errors.payment_terms"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.payment_terms }}
                </div>
              </div>

              <div>
                <Label for="credit_limit">Credit Limit</Label>
                <Input
                  id="credit_limit"
                  v-model="form.credit_limit"
                  type="number"
                  step="0.01"
                  :class="{ 'border-red-500': form.errors.credit_limit }"
                />
                <div
                  v-if="form.errors.credit_limit"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.credit_limit }}
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <Label for="notes">Notes</Label>
              <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="w-full border rounded-md p-2 focus:ring focus:ring-indigo-200 focus:border-indigo-400"
                :class="{ 'border-red-500': form.errors.notes }"
              ></textarea>
              <div
                v-if="form.errors.notes"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.notes }}
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-4 pt-4">
              <Link :href="route('suppliers.index')">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Updating...' : 'Update Supplier' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>