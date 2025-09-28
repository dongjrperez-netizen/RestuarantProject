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
import { Edit, ArrowLeft } from 'lucide-vue-next';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  description?: string;
  x_position?: number;
  y_position?: number;
}

interface Props {
  table: Table;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
  { title: `Edit ${props.table.table_number}`, href: `/pos/tables/${props.table.id}/edit` },
];

const form = useForm({
  table_number: props.table.table_number,
  table_name: props.table.table_name,
  seats: props.table.seats,
  status: props.table.status,
  description: props.table.description || '',
  x_position: props.table.x_position,
  y_position: props.table.y_position,
});

const statusOptions = [
  { value: 'available', label: 'Available' },
  { value: 'occupied', label: 'Occupied' },
  { value: 'reserved', label: 'Reserved' },
  { value: 'maintenance', label: 'Maintenance' },
];

const seatOptions = Array.from({ length: 20 }, (_, i) => ({
  value: i + 1,
  label: `${i + 1} seat${i + 1 > 1 ? 's' : ''}`,
}));

const submit = () => {
  form.put(`/pos/tables/${props.table.id}`, {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  });
};
</script>

<template>
  <Head :title="`Edit Table ${table.table_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Edit Table {{ table.table_number }}</h1>
          <p class="text-muted-foreground">Update table information and settings</p>
        </div>
        <Button variant="outline" @click="$inertia.visit('/pos/tables')">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to Tables
        </Button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Edit class="w-5 h-5" />
              Table Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="table_number">Table Number *</Label>
                <Input
                  id="table_number"
                  v-model="form.table_number"
                  placeholder="e.g., T1, A-1, 101"
                  :class="{ 'border-red-500': form.errors.table_number }"
                />
                <p v-if="form.errors.table_number" class="text-sm text-red-500">
                  {{ form.errors.table_number }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="table_name">Table Name *</Label>
                <Input
                  id="table_name"
                  v-model="form.table_name"
                  placeholder="e.g., Window Table, VIP Table"
                  :class="{ 'border-red-500': form.errors.table_name }"
                />
                <p v-if="form.errors.table_name" class="text-sm text-red-500">
                  {{ form.errors.table_name }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="seats">Number of Seats *</Label>
                <Select v-model="form.seats">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.seats }">
                    <SelectValue placeholder="Select number of seats" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in seatOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.seats" class="text-sm text-red-500">
                  {{ form.errors.seats }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="status">Status *</Label>
                <Select v-model="form.status">
                  <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in statusOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.status" class="text-sm text-red-500">
                  {{ form.errors.status }}
                </p>
              </div>
            </div>

            <!-- Position (Optional) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="x_position">X Position (Optional)</Label>
                <Input
                  id="x_position"
                  v-model="form.x_position"
                  type="number"
                  step="0.01"
                  placeholder="X coordinate for layout"
                  :class="{ 'border-red-500': form.errors.x_position }"
                />
                <p v-if="form.errors.x_position" class="text-sm text-red-500">
                  {{ form.errors.x_position }}
                </p>
                <p class="text-xs text-muted-foreground">
                  For future restaurant layout features
                </p>
              </div>

              <div class="space-y-2">
                <Label for="y_position">Y Position (Optional)</Label>
                <Input
                  id="y_position"
                  v-model="form.y_position"
                  type="number"
                  step="0.01"
                  placeholder="Y coordinate for layout"
                  :class="{ 'border-red-500': form.errors.y_position }"
                />
                <p v-if="form.errors.y_position" class="text-sm text-red-500">
                  {{ form.errors.y_position }}
                </p>
                <p class="text-xs text-muted-foreground">
                  For future restaurant layout features
                </p>
              </div>
            </div>

            <!-- Description -->
            <div class="space-y-2">
              <Label for="description">Description (Optional)</Label>
              <Textarea
                id="description"
                v-model="form.description"
                placeholder="Additional details about this table..."
                class="min-h-[80px]"
                :class="{ 'border-red-500': form.errors.description }"
              />
              <p v-if="form.errors.description" class="text-sm text-red-500">
                {{ form.errors.description }}
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <Button type="button" variant="outline" @click="$inertia.visit('/pos/tables')">
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing || !form.table_number || !form.table_name"
          >
            {{ form.processing ? 'Updating...' : 'Update Table' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>