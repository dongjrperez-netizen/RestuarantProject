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

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
}

interface Props {
  ingredients: Ingredient[];
}

defineProps<Props>();

const form = useForm({
  supplier_name: '',
  contact_number: '',
  email: '',
  password: '',
  address: '',
  business_registration: '',
  tax_id: '',
  payment_terms: '',
  credit_limit: '',
  notes: '',
  ingredients: [] as any[],
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Suppliers',
    href: '/suppliers',
  },
  {
    title: 'Create Supplier',
    href: '/suppliers/create',
  },
];

const submit = () => {
  form.post(route('suppliers.store'));
};
</script>

<template>
  <Head title="Create Supplier" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-foreground">Create Supplier</h1>
          <p class="text-muted-foreground mt-1">
            Add a new supplier to your restaurant
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
            Fill in the details for the new supplier
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
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
                <Label for="password">Password</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  placeholder="Leave empty to send invitation"
                  :class="{ 'border-red-500': form.errors.password }"
                />
                <div
                  v-if="form.errors.password"
                  class="text-red-500 text-sm mt-1"
                >
                  {{ form.errors.password }}
                </div>
                <p class="text-sm text-muted-foreground mt-1">
                  If left empty, the supplier will receive an invitation to set their own password
                </p>
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
                {{ form.processing ? 'Creating...' : 'Create Supplier' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
