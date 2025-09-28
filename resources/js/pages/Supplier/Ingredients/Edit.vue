<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import  Switch  from '@/components/ui/switch/Switch.vue';

interface Restaurant {
  id: number;
  restaurant_name: string;
}

interface IngredientOffer {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
  restaurant: Restaurant;
  pivot: {
    package_unit: string;
    package_quantity: number;
    package_contents_quantity: number;
    package_contents_unit: string;
    package_price: number;
    lead_time_days: number;
    minimum_order_quantity: number;
    is_active: boolean;
  };
}

interface Supplier {
  supplier_id: number;
  supplier_name: string;
  email: string;
}

interface Props {
  ingredient: IngredientOffer;
  supplier: Supplier;
}

const props = defineProps<Props>();

const form = useForm({
  package_unit: props.ingredient.pivot.package_unit,
  package_quantity: props.ingredient.pivot.package_quantity,
  package_contents_quantity: props.ingredient.pivot.package_contents_quantity,
  package_contents_unit: props.ingredient.pivot.package_contents_unit,
  package_price: props.ingredient.pivot.package_price,
  lead_time_days: props.ingredient.pivot.lead_time_days,
  minimum_order_quantity: props.ingredient.pivot.minimum_order_quantity,
  is_active: props.ingredient.pivot.is_active,
});

const submit = () => {
  form.put(`/supplier/ingredients/${props.ingredient.ingredient_id}`);
};
</script>

<template>
  <Head title="Edit Ingredient Offer" />

  <SupplierLayout :supplier="supplier">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Ingredient Offer</h1>
        <p class="text-muted-foreground">Update your ingredient offer details</p>
      </div>

      <!-- Form -->
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>{{ ingredient.ingredient_name }}</CardTitle>
          <CardDescription>
            <div class="space-y-1">
              <div>Restaurant: {{ ingredient.restaurant.restaurant_name }}</div>
              <div>Base Unit: {{ ingredient.base_unit }}</div>
            </div>
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Package Details -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="package_quantity">Package Quantity *</Label>
                <Input
                  id="package_quantity"
                  v-model.number="form.package_quantity"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                />
                <div v-if="form.errors.package_quantity" class="text-sm text-red-600">
                  {{ form.errors.package_quantity }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="package_unit">Package Unit *</Label>
                <Input
                  id="package_unit"
                  v-model="form.package_unit"
                  placeholder="kg, lbs, pcs, etc."
                  required
                />
                <div v-if="form.errors.package_unit" class="text-sm text-red-600">
                  {{ form.errors.package_unit }}
                </div>
              </div>
            </div>

            <!-- Package Contents Details -->
            <div class="border-t pt-4">
              <h3 class="text-lg font-medium mb-3">Package Contents</h3>
              <p class="text-sm text-muted-foreground mb-4">Specify what's inside each package</p>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="package_contents_quantity">Contents Quantity *</Label>
                  <Input
                    id="package_contents_quantity"
                    v-model.number="form.package_contents_quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                  />
                  <div v-if="form.errors.package_contents_quantity" class="text-sm text-red-600">
                    {{ form.errors.package_contents_quantity }}
                  </div>
                </div>

                <div class="space-y-2">
                  <Label for="package_contents_unit">Contents Unit *</Label>
                  <Input
                    id="package_contents_unit"
                    v-model="form.package_contents_unit"
                    placeholder="pcs, kg, lbs, liters, etc."
                    required
                  />
                  <div v-if="form.errors.package_contents_unit" class="text-sm text-red-600">
                    {{ form.errors.package_contents_unit }}
                  </div>
                </div>
              </div>

              <div class="mt-3 p-3 bg-blue-50 rounded-md">
                <p class="text-sm text-blue-800">
                  <strong>Example:</strong> 1 box contains 50 pcs of tomatoes
                </p>
              </div>
            </div>

            <!-- Pricing and Terms -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="package_price">Package Price (₱) *</Label>
                <Input
                  id="package_price"
                  v-model.number="form.package_price"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                />
                <div v-if="form.errors.package_price" class="text-sm text-red-600">
                  {{ form.errors.package_price }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="lead_time_days">Lead Time (days) *</Label>
                <Input
                  id="lead_time_days"
                  v-model.number="form.lead_time_days"
                  type="number"
                  min="0"
                  required
                />
                <div v-if="form.errors.lead_time_days" class="text-sm text-red-600">
                  {{ form.errors.lead_time_days }}
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="minimum_order_quantity">Minimum Order Quantity *</Label>
              <Input
                id="minimum_order_quantity"
                v-model.number="form.minimum_order_quantity"
                type="number"
                step="0.01"
                min="0.01"
                required
              />
              <div v-if="form.errors.minimum_order_quantity" class="text-sm text-red-600">
                {{ form.errors.minimum_order_quantity }}
              </div>
            </div>

            <!-- Status -->
            <div class="flex items-center space-x-2">
              <Switch 
                id="is_active" 
                v-model:checked="form.is_active"
              />
              <Label for="is_active">Active Offer</Label>
            </div>
            <div v-if="form.errors.is_active" class="text-sm text-red-600">
              {{ form.errors.is_active }}
            </div>

            <!-- Actions -->
            <div class="flex space-x-4">
              <Button 
                type="submit" 
                :disabled="form.processing"
              >
                {{ form.processing ? 'Updating...' : 'Update Offer' }}
              </Button>
              
              <Button type="button" variant="outline" onclick="history.back()">
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </SupplierLayout>
</template>