<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Ingredients Library', href: '/ingredients-library' },
  { title: 'Add Ingredient', href: '#' },
];

const form = useForm({
  ingredient_name: '',
  base_unit: '',
  cost_per_unit: 0,
  current_stock: 0,
  reorder_level: 0,
  packages: 0,
});

const commonUnits = [
  'kg', 'g', 'mg',
  'L', 'mL',
  'pcs', 'dozen',
  'can', 'bottle',
  'pack', 'box',
  'cup', 'tbsp', 'tsp',
];

const submit = () => {
  form.post('/ingredients-library');
};
</script>

<template>
  <Head title="Add Ingredient" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Add New Ingredient</h1>
        <p class="text-muted-foreground">Add a new ingredient to your inventory library</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
            <CardDescription>Enter the basic details of the ingredient</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="ingredient_name">Ingredient Name *</Label>
                <Input
                  id="ingredient_name"
                  v-model="form.ingredient_name"
                  type="text"
                  placeholder="e.g., Rice, Chicken Breast, Tomato"
                  required
                />
                <div v-if="form.errors.ingredient_name" class="text-sm text-red-600">
                  {{ form.errors.ingredient_name }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="base_unit">Base Unit *</Label>
                <div class="flex space-x-2">
                  <Input
                    id="base_unit"
                    v-model="form.base_unit"
                    type="text"
                    placeholder="e.g., kg, L, pcs"
                    required
                    list="unit-suggestions"
                    class="flex-1"
                  />
                  <datalist id="unit-suggestions">
                    <option v-for="unit in commonUnits" :key="unit" :value="unit">{{ unit }}</option>
                  </datalist>
                </div>
                <div class="text-xs text-muted-foreground">
                  Common units: {{ commonUnits.slice(0, 6).join(', ') }}, etc.
                </div>
                <div v-if="form.errors.base_unit" class="text-sm text-red-600">
                  {{ form.errors.base_unit }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Pricing -->
        <Card>
          <CardHeader>
            <CardTitle>Pricing</CardTitle>
            <CardDescription>Set the cost per unit for this ingredient</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <Label for="cost_per_unit">Cost per Unit (₱) *</Label>
              <Input
                id="cost_per_unit"
                v-model.number="form.cost_per_unit"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                required
              />
              <div class="text-xs text-muted-foreground">
                The cost for one {{ form.base_unit || 'unit' }} of this ingredient
              </div>
              <div v-if="form.errors.cost_per_unit" class="text-sm text-red-600">
                {{ form.errors.cost_per_unit }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Stock Information -->
        <Card>
          <CardHeader>
            <CardTitle>Stock Information</CardTitle>
            <CardDescription>Set initial stock levels and reorder thresholds</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
              <div class="space-y-2">
                <Label for="current_stock">Current Stock</Label>
                <Input
                  id="current_stock"
                  v-model.number="form.current_stock"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0"
                />
                <div class="text-xs text-muted-foreground">
                  Current quantity in {{ form.base_unit || 'units' }}
                </div>
                <div v-if="form.errors.current_stock" class="text-sm text-red-600">
                  {{ form.errors.current_stock }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="reorder_level">Reorder Level</Label>
                <Input
                  id="reorder_level"
                  v-model.number="form.reorder_level"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0"
                />
                <div class="text-xs text-muted-foreground">
                  Alert when stock falls below this level
                </div>
                <div v-if="form.errors.reorder_level" class="text-sm text-red-600">
                  {{ form.errors.reorder_level }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="packages">Packages</Label>
                <Input
                  id="packages"
                  v-model.number="form.packages"
                  type="number"
                  min="0"
                  placeholder="0"
                />
                <div class="text-xs text-muted-foreground">
                  Number of packages/containers
                </div>
                <div v-if="form.errors.packages" class="text-sm text-red-600">
                  {{ form.errors.packages }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Summary -->
        <Card v-if="form.ingredient_name && form.base_unit && form.cost_per_unit > 0">
          <CardHeader>
            <CardTitle>Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Ingredient:</span>
                <span class="font-medium">{{ form.ingredient_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Unit:</span>
                <span class="font-medium">{{ form.base_unit }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Cost per {{ form.base_unit }}:</span>
                <span class="font-medium">₱{{ form.cost_per_unit.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Initial Stock:</span>
                <span class="font-medium">{{ form.current_stock }} {{ form.base_unit }}</span>
              </div>
              <div class="flex justify-between border-t pt-2">
                <span class="text-muted-foreground">Total Value:</span>
                <span class="font-semibold">₱{{ (form.current_stock * form.cost_per_unit).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
          <Button
            type="submit"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Adding...' : 'Add Ingredient' }}
          </Button>

          <Button type="button" variant="outline" onclick="history.back()">
            Cancel
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
