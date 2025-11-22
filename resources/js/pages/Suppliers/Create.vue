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
  address: '',
  payment_terms: '',
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
      <Card class="max-w-3xl mx-auto">
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
