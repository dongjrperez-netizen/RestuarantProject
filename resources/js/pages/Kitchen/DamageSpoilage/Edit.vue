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
import { computed } from 'vue';
import { AlertTriangle, Package, ArrowLeft, Save } from 'lucide-vue-next';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface DamageSpoilageLog {
  id: number;
  ingredient_id: number;
  type: string;
  quantity: number;
  unit: string;
  reason: string | null;
  notes: string | null;
  incident_date: string;
  estimated_cost: number | null;
}

interface Props {
  log: DamageSpoilageLog;
  ingredients: Ingredient[];
  types: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Damage & Spoilage', href: '/damage-spoilage' },
  { title: 'Edit Report', href: `/damage-spoilage/${props.log.id}/edit` },
];

const form = useForm({
  ingredient_id: props.log.ingredient_id.toString(),
  type: props.log.type,
  quantity: props.log.quantity.toString(),
  unit: props.log.unit,
  reason: props.log.reason || '',
  notes: props.log.notes || '',
  incident_date: props.log.incident_date,
  estimated_cost: props.log.estimated_cost?.toString() || '',
});

// Get unit from selected ingredient
const selectedIngredient = computed(() => {
  if (!form.ingredient_id) return null;
  return props.ingredients.find(i => i.ingredient_id.toString() === form.ingredient_id);
});

// Auto-set unit when ingredient is selected
const onIngredientChange = (ingredientId: string) => {
  form.ingredient_id = ingredientId;
  const ingredient = props.ingredients.find(i => i.ingredient_id.toString() === ingredientId);
  if (ingredient) {
    form.unit = ingredient.base_unit;
  }
};

const submit = () => {
  form.put(`/damage-spoilage/${props.log.id}`, {
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
  <Head title="Edit Damage/Spoilage Report" />

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
            <h1 class="text-3xl font-bold tracking-tight">Edit Damage/Spoilage Report</h1>
            <p class="text-muted-foreground">Update the details of this incident report</p>
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
                  <Input
                    v-model="form.unit"
                    placeholder="Unit"
                    :class="{ 'border-red-500': form.errors.unit }"
                    class="w-24"
                    readonly
                  />
                </div>
                <p v-if="form.errors.quantity" class="text-sm text-red-500">
                  {{ form.errors.quantity }}
                </p>
                <p v-if="form.errors.unit" class="text-sm text-red-500">
                  {{ form.errors.unit }}
                </p>
              </div>

              <!-- Incident Date -->
              <div class="space-y-2">
                <Label for="incident_date">Incident Date *</Label>
                <Input
                  id="incident_date"
                  v-model="form.incident_date"
                  type="date"
                  :max="new Date().toISOString().split('T')[0]"
                  :class="{ 'border-red-500': form.errors.incident_date }"
                />
                <p v-if="form.errors.incident_date" class="text-sm text-red-500">
                  {{ form.errors.incident_date }}
                </p>
              </div>

              <!-- Estimated Cost -->
              <div class="space-y-2 md:col-span-2">
                <Label for="estimated_cost">Estimated Cost (Optional)</Label>
                <Input
                  id="estimated_cost"
                  v-model="form.estimated_cost"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  :class="{ 'border-red-500': form.errors.estimated_cost }"
                  class="max-w-xs"
                />
                <p v-if="form.errors.estimated_cost" class="text-sm text-red-500">
                  {{ form.errors.estimated_cost }}
                </p>
                <p class="text-xs text-muted-foreground">
                  Enter the monetary value of the lost ingredient (optional)
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

            <!-- Additional Notes -->
            <div class="space-y-2">
              <Label for="notes">Additional Notes (Optional)</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                placeholder="Any additional details, prevention measures, or observations..."
                rows="3"
                :class="{ 'border-red-500': form.errors.notes }"
              />
              <p v-if="form.errors.notes" class="text-sm text-red-500">
                {{ form.errors.notes }}
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
            {{ form.processing ? 'Updating...' : 'Update Report' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>