<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent } from '@/components/ui/card';
import { AlertTriangle, Package, Save, X, Plus, Trash2, Calendar } from 'lucide-vue-next';

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface Props {
  isOpen: boolean;
  ingredients: Ingredient[];
  types: Record<string, string>;
}

interface Emits {
  (e: 'close'): void;
  (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

interface IngredientEntry {
  ingredient_id: string;
  quantity: string;
  unit: string;
  reason: string;
}

const selectedType = ref('');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const ingredientEntries = ref<IngredientEntry[]>([
  {
    ingredient_id: '',
    quantity: '',
    unit: '',
    reason: '',
  },
]);

const form = useForm<{
  type: string;
  incident_date: string;
  ingredients: Array<{
    ingredient_id: string;
    quantity: string;
    unit: string;
    reason: string;
  }>;
}>({
  type: '',
  incident_date: '',
  ingredients: [],
});

const addIngredientEntry = () => {
  ingredientEntries.value.push({
    ingredient_id: '',
    quantity: '',
    unit: '',
    reason: '',
  });
};

// Auto-populate unit when ingredient is selected
const onIngredientChange = (index: number, ingredientId: string | number | boolean | null | undefined) => {
  if (!ingredientId || typeof ingredientId !== 'string') return;

  const ingredient = props.ingredients.find(ing => ing.ingredient_id.toString() === ingredientId);
  if (ingredient) {
    ingredientEntries.value[index].ingredient_id = ingredientId;
    ingredientEntries.value[index].unit = ingredient.base_unit;
  }
};

const removeIngredientEntry = (index: number) => {
  if (ingredientEntries.value.length > 1) {
    ingredientEntries.value.splice(index, 1);
  }
};

const resetForm = () => {
  selectedType.value = '';
  selectedDate.value = new Date().toISOString().split('T')[0];
  ingredientEntries.value = [
    {
      ingredient_id: '',
      quantity: '',
      unit: '',
      reason: '',
    },
  ];
  form.reset();
};

const submit = async () => {
  // Prepare form data
  form.type = selectedType.value;
  form.incident_date = selectedDate.value;
  form.ingredients = ingredientEntries.value.filter(entry => entry.ingredient_id && entry.quantity);

  if (form.ingredients.length === 0) {
    alert('Please add at least one ingredient');
    return;
  }

  try {
    await form.post('/damage-spoilage', {
      preserveScroll: true,
      onSuccess: () => {
        resetForm();
        emit('success');
        emit('close');
      },
    });
  } catch (error) {
    console.error('Error submitting damage/spoilage report:', error);
  }
};

const closeModal = () => {
  resetForm();
  emit('close');
};

// Reset form when modal opens
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    resetForm();
  }
});

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
  <Dialog :open="isOpen" @update:open="closeModal">
    <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-orange-600" />
          Report Damage/Spoilage
        </DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-6 mt-4">
        <!-- General Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Type Selection -->
          <div class="space-y-2">
            <Label for="type">Type *</Label>
            <Select v-model="selectedType">
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

          <!-- Incident Date (Auto - Today) -->
          <div class="space-y-2">
            <Label for="incident_date">Incident Date</Label>
            <div class="flex items-center gap-2 px-3 py-2 bg-muted rounded-md border">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <span class="text-sm font-medium">
                {{ new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
              </span>
              <span class="ml-auto text-xs text-muted-foreground">(Today)</span>
            </div>
            <p v-if="form.errors.incident_date" class="text-sm text-red-500">
              {{ form.errors.incident_date }}
            </p>
          </div>
        </div>

        <!-- Ingredients Section -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <Label class="text-base font-semibold">Affected Ingredients *</Label>
            <Button
              type="button"
              @click="addIngredientEntry"
              size="sm"
              variant="outline"
              class="gap-2"
            >
              <Plus class="w-4 h-4" />
              Add Ingredient
            </Button>
          </div>

          <div class="space-y-3">
            <Card
              v-for="(entry, index) in ingredientEntries"
              :key="index"
              class="border-2"
            >
              <CardContent class="pt-4">
                <div class="space-y-4">
                  <div class="flex items-start justify-between">
                    <h4 class="text-sm font-medium text-gray-700">Ingredient #{{ index + 1 }}</h4>
                    <Button
                      v-if="ingredientEntries.length > 1"
                      type="button"
                      @click="removeIngredientEntry(index)"
                      size="sm"
                      variant="ghost"
                      class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Ingredient Selection -->
                    <div class="space-y-2 md:col-span-2">
                      <Label>Ingredient *</Label>
                      <Select :model-value="entry.ingredient_id" @update:model-value="(value) => onIngredientChange(index, value)">
                        <SelectTrigger>
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
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-2">
                      <Label>Quantity *</Label>
                      <Input
                        v-model="entry.quantity"
                        type="number"
                        step="0.001"
                        min="0"
                        placeholder="0.000"
                      />
                    </div>

                    <!-- Unit (Auto-populated, read-only) -->
                    <div class="space-y-2">
                      <Label>Unit *</Label>
                      <Input
                        v-model="entry.unit"
                        type="text"
                        readonly
                        disabled
                        placeholder="Auto"
                        class="bg-gray-100 cursor-not-allowed"
                      />
                    </div>
                  </div>

                  <!-- Individual Reason -->
                  <div class="grid grid-cols-1 gap-4 mt-4">
                    <div class="space-y-2 md:col-span-4">
                      <Label>Reason (Optional)</Label>
                      <div class="space-y-2">
                        <Textarea
                          v-model="entry.reason"
                          placeholder="Describe the cause of damage/spoilage for this ingredient..."
                          rows="2"
                        />

                        <!-- Quick Reason Selection -->
                        <div v-if="selectedType && commonReasons[selectedType as keyof typeof commonReasons]" class="space-y-2">
                          <p class="text-xs text-muted-foreground">Quick select:</p>
                          <div class="flex flex-wrap gap-1">
                            <Button
                              v-for="reason in commonReasons[selectedType as keyof typeof commonReasons]"
                              :key="reason"
                              type="button"
                              variant="outline"
                              size="sm"
                              @click="entry.reason = reason"
                              class="text-xs h-7"
                            >
                              {{ reason }}
                            </Button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Bottom Add Ingredient Button -->
          <div class="flex justify-start pt-2">
            <Button
              type="button"
              @click="addIngredientEntry"
              size="sm"
              variant="outline"
              class="gap-2"
            >
              <Plus class="w-4 h-4" />
              Add Another Ingredient
            </Button>
          </div>

          <p v-if="form.errors.ingredients" class="text-sm text-red-500">
            {{ form.errors.ingredients }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-2 pt-4 border-t">
          <Button
            type="button"
            variant="outline"
            @click="closeModal"
          >
            <X class="w-4 h-4 mr-2" />
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="bg-orange-600 hover:bg-orange-700"
          >
            <Save class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Saving...' : `Report ${ingredientEntries.filter(e => e.ingredient_id).length} Incident${ingredientEntries.filter(e => e.ingredient_id).length !== 1 ? 's' : ''}` }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
