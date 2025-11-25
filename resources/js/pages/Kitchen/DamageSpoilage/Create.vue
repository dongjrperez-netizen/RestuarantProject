<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { AlertTriangle, Package, ArrowLeft, Save, Calendar } from 'lucide-vue-next';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

const unitOptions = [
  // Weight units
  'g', 'gram', 'grams',
  'kg', 'kilogram', 'kilograms',
  'lb', 'pound', 'pounds',
  'oz', 'ounce', 'ounces',
  // Volume units
  'ml', 'milliliter', 'milliliters',
  'l', 'liter', 'liters',
  'cup', 'cups',
  'tbsp', 'tablespoon', 'tablespoons',
  'tsp', 'teaspoon', 'teaspoons',
  // Count units
  'pcs', 'piece', 'pieces', 'item', 'items', 'unit', 'units',
];

interface Props {
  ingredients: Ingredient[];
  types: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Damage & Spoilage', href: '/damage-spoilage' },
  { title: 'Report New', href: '/damage-spoilage/create' },
];

const form = useForm({
  ingredient_id: '',
  type: '',
  quantity: '',
  unit: '',
  reason: '',
  incident_date: new Date().toISOString().split('T')[0], // Today's date
});

// Handle ingredient change (unit is entered manually by user)
const onIngredientChange = (ingredientId: string) => {
  form.ingredient_id = ingredientId;
};

const submit = () => {
  form.post('/damage-spoilage', {
    preserveScroll: true,
  });
};

// Common reasons for damage/spoilage
const commonReasons = {
  damage: [
    'Dropped during handling',
    'Packaging damaged',
    'Equipment malfunction',
    'Temperature fluctuation',
    'Contamination',
    'Over-processing',
    'Storage accident',
  ],
  spoilage: [
    'Expired/past use date',
    'Temperature abuse',
    'Poor storage conditions',
    'Natural spoilage',
    'Contamination',
    'Pest infestation',
    'Power outage',
  ]
};
</script>

<template>
  <Head title="Report Damage/Spoilage" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6 px-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="$inertia.visit('/damage-spoilage')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to List
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Report Damage/Spoilage</h1>
            <p class="text-muted-foreground">Record damaged or spoiled ingredients for inventory tracking</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <AlertTriangle class="w-5 h-5 text-orange-600" />
              Incident Details
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Ingredient Selection -->
              <div class="space-y-2">
                <Label for="ingredient_id">Ingredient *</Label>
                <Select :model-value="form.ingredient_id" @update:model-value="onIngredientChange">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.ingredient_id }">
                    <SelectValue placeholder="Select ingredient" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="ingredient in ingredients"
                      :key="ingredient.ingredient_id"
                      :value="ingredient.ingredient_id.toString()"
                    >
                      {{ ingredient.ingredient_name }} ({{ ingredient.base_unit }})
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.ingredient_id" class="text-sm text-red-500">
                  {{ form.errors.ingredient_id }}
                </p>
              </div>

              <!-- Type Selection -->
              <div class="space-y-2">
                <Label for="type">Type *</Label>
                <Select v-model="form.type">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(label, value) in types"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.type" class="text-sm text-red-500">
                  {{ form.errors.type }}
                </p>
              </div>

              <!-- Quantity -->
              <div class="space-y-2">
                <Label for="quantity">Quantity *</Label>
                <div class="flex gap-2">
                  <Input
                    id="quantity"
                    v-model="form.quantity"
                    type="number"
                    step="0.001"
                    min="0"
                    placeholder="0.000"
                    :class="{ 'border-red-500': form.errors.quantity }"
                    class="flex-1"
                  />
                  <Select v-model="form.unit">
                    <SelectTrigger
                      :class="[{ 'border-red-500': form.errors.unit }, 'w-28']"
                    >
                      <SelectValue placeholder="Unit" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="unit in unitOptions"
                        :key="unit"
                        :value="unit"
                      >
                        {{ unit }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <p v-if="form.errors.quantity" class="text-sm text-red-500">
                  {{ form.errors.quantity }}
                </p>
                <p v-if="form.errors.unit" class="text-sm text-red-500">
                  {{ form.errors.unit }}
                </p>
              </div>

              <!-- Incident Date (Auto - Today) -->
              <div class="space-y-2">
                <Label for="incident_date">Incident Date</Label>
                <div class="flex items-center gap-2 px-3 py-2 bg-muted rounded-md border">
                  <Calendar class="w-4 h-4 text-muted-foreground" />
                  <span class="text-sm font-medium">
                    {{ new Date(form.incident_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                  </span>
                  <span class="ml-auto text-xs text-muted-foreground">(Today)</span>
                </div>
                <p v-if="form.errors.incident_date" class="text-sm text-red-500">
                  {{ form.errors.incident_date }}
                </p>
              </div>

            </div>

            <!-- Reason -->
            <div class="space-y-2">
              <Label for="reason">Reason (Optional)</Label>
              <div class="space-y-2">
                <Textarea
                  id="reason"
                  v-model="form.reason"
                  placeholder="Describe the cause of damage/spoilage..."
                  rows="3"
                  :class="{ 'border-red-500': form.errors.reason }"
                />

                <!-- Quick Reason Selection -->
                <div v-if="form.type && commonReasons[form.type as keyof typeof commonReasons]" class="space-y-2">
                  <p class="text-sm text-muted-foreground">Quick select common reasons:</p>
                  <div class="flex flex-wrap gap-2">
                    <Button
                      v-for="reason in commonReasons[form.type as keyof typeof commonReasons]"
                      :key="reason"
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="form.reason = reason"
                      class="text-xs"
                    >
                      {{ reason }}
                    </Button>
                  </div>
                </div>
              </div>
              <p v-if="form.errors.reason" class="text-sm text-red-500">
                {{ form.errors.reason }}
              </p>
            </div>

          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            @click="$inertia.visit('/damage-spoilage')"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="bg-orange-600 hover:bg-orange-700"
          >
            <Save class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Saving...' : 'Report Incident' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>