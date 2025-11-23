<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { Plus, ArrowLeft, Trash2 } from 'lucide-vue-next';

interface TableData {
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  [key: string]: any;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
  { title: 'Create Tables', href: '/pos/tables/create' },
];

const tables = ref<TableData[]>([
  {
    table_number: '',
    table_name: '',
    seats: 4,
    status: 'available',
  }
]);

const form = useForm({
  tables: [] as TableData[]
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

const addTable = () => {
  tables.value.push({
    table_number: '',
    table_name: '',
    seats: 4,
    status: 'available',
  });
};

const removeTable = (index: number) => {
  if (tables.value.length > 1) {
    tables.value.splice(index, 1);
  }
};

const submit = () => {
  // Filter out empty tables and validate
  const validTables = tables.value.filter(table =>
    table.table_number.trim() && table.table_name.trim()
  );

  form.tables = validTables;

  form.post('/pos/tables', {
    onSuccess: () => {
      // Will be redirected by the controller
    },
  });
};
</script>

<template>
  <Head title="Create Tables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Tables</h1>
          <p class="text-muted-foreground">Add multiple tables to your restaurant layout</p>
        </div>
        <Button variant="outline" @click="$inertia.visit('/pos/tables')">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to Tables
        </Button>
      </div>

      <!-- Global error alert -->
      <div
        v-if="Object.keys(form.errors).length"
        class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded"
      >
        <div v-for="(message, field) in form.errors" :key="field">
          {{ message }}
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="flex items-center gap-2">
                  <Plus class="w-5 h-5" />
                  Table Information
                </CardTitle>
                <CardDescription class="mt-1">
                  Add one or more tables in a single transaction
                </CardDescription>
              </div>
              <Button
                type="button"
                @click="addTable"
                variant="outline"
                size="sm"
              >
                <Plus class="w-4 h-4 mr-2" />
                Add Table
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="min-w-[150px]">Table Number *</TableHead>
                    <TableHead class="min-w-[200px]">Table Name *</TableHead>
                    <TableHead class="min-w-[120px]">Seats *</TableHead>
                    <TableHead class="min-w-[140px]">Status *</TableHead>
                    <TableHead class="w-12"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(table, index) in tables" :key="index">
                    <TableCell>
                      <div class="space-y-1">
                        <Input
                          v-model="tables[index].table_number"
                          placeholder="e.g., T1, A-1"
                          class="min-w-[140px]"
                          :class="{ 'border-red-500': (form.errors as any)[`tables.${index}.table_number`] }"
                        />
                        <div
                          v-if="(form.errors as any)[`tables.${index}.table_number`]"
                          class="text-xs text-red-600"
                        >
                          {{ (form.errors as any)[`tables.${index}.table_number`] }}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="space-y-1">
                        <Input
                          v-model="tables[index].table_name"
                          placeholder="e.g., Window Table"
                          class="min-w-[190px]"
                          :class="{ 'border-red-500': (form.errors as any)[`tables.${index}.table_name`] }"
                        />
                        <div
                          v-if="(form.errors as any)[`tables.${index}.table_name`]"
                          class="text-xs text-red-600"
                        >
                          {{ (form.errors as any)[`tables.${index}.table_name`] }}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="space-y-1">
                        <Select v-model="tables[index].seats">
                          <SelectTrigger class="min-w-[110px]">
                            <SelectValue />
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
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="space-y-1">
                        <Select v-model="tables[index].status">
                          <SelectTrigger class="min-w-[130px]">
                            <SelectValue />
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
                      </div>
                    </TableCell>
  
                    <TableCell>
                      <Button
                        type="button"
                        @click="removeTable(index)"
                        variant="ghost"
                        size="sm"
                        :disabled="tables.length === 1"
                        class="h-8 w-8 p-0"
                      >
                        <Trash2 class="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <div v-if="(form.errors as any).tables" class="text-sm text-red-600 mt-4">
              {{ (form.errors as any).tables }}
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
            :disabled="form.processing || tables.length === 0"
          >
            {{ form.processing ? 'Creating...' : `Create ${tables.length} Table${tables.length > 1 ? 's' : ''}` }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
