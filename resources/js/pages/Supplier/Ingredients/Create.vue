<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SupplierLayout from '@/layouts/SupplierLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// Removed Select components as restaurant selection is no longer needed

interface Restaurant {
  id: number;
  restaurant_name: string;
  address: string;
}

interface Props {
  restaurant: Restaurant;
}

defineProps<Props>();

const form = useForm({
  ingredient_name: '',
  package_unit: '',
  package_quantity: 1,
  package_contents_quantity: 1,
  package_contents_unit: '',
  package_price: 0,
  lead_time_days: 0,
  minimum_order_quantity: 1,
});


const submit = () => {
  form.post('/supplier/ingredients');
};
</script>

<template>
  <Head title="Add Ingredient Offer" />

  <SupplierLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Add Ingredient Offer</h1>
        <p class="text-muted-foreground">Offer your ingredients to restaurants</p>
      </div>

      <!-- Form -->
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>New Ingredient Offer</CardTitle>
          <CardDescription>
            Add your own ingredients to offer to restaurants with your pricing and terms
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Restaurant Information (Read-only) -->
            <div class="space-y-2">
              <Label>Offering to Restaurant</Label>
              <div class="p-3 bg-muted/50 rounded-md border">
                <div class="font-medium text-foreground">{{ restaurant.restaurant_name }}</div>
                <div class="text-sm text-muted-foreground">{{ restaurant.address }}</div>
              </div>
              <p class="text-xs text-muted-foreground">
                You can only offer ingredients to the restaurant that invited you to the system.
              </p>
            </div>

            <!-- Custom Ingredient Details -->
            <div class="space-y-2">
              <Label for="ingredient_name">Ingredient Name *</Label>
              <Input
                id="ingredient_name"
                v-model="form.ingredient_name"
                placeholder="e.g., Fresh Tomatoes, Olive Oil, etc."
                required
              />
              <div v-if="form.errors.ingredient_name" class="text-sm text-red-600">
                {{ form.errors.ingredient_name }}
              </div>
            </div>

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
                  placeholder="box, bag, sack, etc."
                  required
                />
                <div v-if="form.errors.package_unit" class="text-sm text-red-600">
                  {{ form.errors.package_unit }}
                </div>
              </div>
            </div>

            <!-- Package Contents Details -->
            <div class="border-t pt-4">
              <h3 class="text-lg font-medium mb-3">Package Contents & Base Unit</h3>
              <p class="text-sm text-muted-foreground mb-4">Specify what's inside each package (this will also be used as the ingredient's base unit for inventory tracking)</p>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="package_contents_quantity">Contents Quantity *</Label>
                  <Input
                    id="package_contents_quantity"
                    v-model.number="form.package_contents_quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="e.g., 50"
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

            <!-- Actions -->
            <div class="flex space-x-4">
              <Button
                type="submit"
                :disabled="form.processing || !form.ingredient_name || !form.package_contents_unit"
              >
                {{ form.processing ? 'Adding...' : 'Add Ingredient Offer' }}
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