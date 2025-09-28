<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import { Calendar, Clock, Users, ChefHat, AlertCircle } from 'lucide-vue-next';

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: string;
}

interface TimeSlot {
  time: string;
  display_time: string;
  available: boolean;
}

interface Props {
  tables: Table[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
  { title: 'New Reservation', href: '/pos/reservations/create' },
];

const form = useForm({
  table_id: '',
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  party_size: 1,
  reservation_date: '',
  reservation_time: '',
  actual_arrival_time: '',
  duration_minutes: 120,
  special_requests: '',
  notes: '',
});

// Ensure duration is properly set as number
watch(() => form.duration_minutes, (newValue) => {
  if (typeof newValue === 'string') {
    form.duration_minutes = parseInt(newValue) || 120;
  }
});

const availableSlots = ref<TimeSlot[]>([]);
const loadingSlots = ref(false);

const selectedTable = computed(() => {
  if (!form.table_id) return null;
  return props.tables.find(table => table.id.toString() === form.table_id.toString());
});

const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const durationOptions = [
  { value: 60, label: '1 hour' },
  { value: 90, label: '1.5 hours' },
  { value: 120, label: '2 hours' },
  { value: 150, label: '2.5 hours' },
  { value: 180, label: '3 hours' },
  { value: 240, label: '4 hours' },
];

const loadAvailableSlots = async () => {
  if (!form.table_id || !form.reservation_date || !form.duration_minutes) {
    availableSlots.value = [];
    return;
  }

  loadingSlots.value = true;
  try {
    console.log('Loading slots for:', { table_id: form.table_id, date: form.reservation_date, duration: form.duration_minutes });
    const response = await fetch(`/pos/reservations/available-slots?table_id=${form.table_id}&date=${form.reservation_date}&duration=${form.duration_minutes}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin'
    });

    console.log('Response status:', response.status);
    console.log('Response headers:', Object.fromEntries(response.headers));

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Response not ok:', response.status, response.statusText, errorText);
      availableSlots.value = [];
      return;
    }

    const data = await response.json();
    console.log('Received slots data:', data);
    availableSlots.value = data.slots || [];
    console.log('Set availableSlots to:', availableSlots.value);
  } catch (error) {
    console.error('Failed to load available slots:', error);
    availableSlots.value = [];
  } finally {
    loadingSlots.value = false;
  }
};

watch([() => form.table_id, () => form.reservation_date, () => form.duration_minutes], loadAvailableSlots);

const submit = () => {
  form.post('/pos/reservations', {
    onSuccess: () => {
      router.visit('/pos/tables');
    },
  });
};

// Pre-select table if passed in query params
const urlParams = new URLSearchParams(window.location.search);
const tableId = urlParams.get('table_id');
if (tableId) {
  form.table_id = tableId;
}
</script>

<template>
  <Head title="New Reservation" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6 px-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">New Reservation</h1>
          <p class="text-muted-foreground">Create a new table reservation</p>
        </div>
        <Button variant="outline" @click="router.visit('/pos/tables')">
          Cancel
        </Button>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Table Selection -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <ChefHat class="w-5 h-5" />
                <span>Table Information</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <Label for="table_id">Table</Label>
                <Select v-model="form.table_id" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a table" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="table in tables"
                      :key="table.id"
                      :value="table.id.toString()"
                    >
                      {{ table.table_number }} - {{ table.table_name }} ({{ table.seats }} seats)
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.table_id" class="text-sm text-destructive">
                  {{ form.errors.table_id }}
                </div>
              </div>

              <div v-if="selectedTable" class="p-3 bg-muted rounded-lg">
                <div class="text-sm space-y-1">
                  <div class="font-medium">{{ selectedTable.table_name }}</div>
                  <div class="text-muted-foreground flex items-center space-x-4">
                    <span class="flex items-center space-x-1">
                      <Users class="w-4 h-4" />
                      <span>{{ selectedTable.seats }} seats</span>
                    </span>
                    <span class="flex items-center space-x-1">
                      <div class="w-2 h-2 rounded-full bg-green-500"></div>
                      <span>{{ selectedTable.status }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="party_size">Party Size</Label>
                <Input
                  id="party_size"
                  v-model.number="form.party_size"
                  type="number"
                  min="1"
                  max="20"
                  required
                />
                <div v-if="form.errors.party_size" class="text-sm text-destructive">
                  {{ form.errors.party_size }}
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Customer Information -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center space-x-2">
                <Users class="w-5 h-5" />
                <span>Customer Information</span>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <Label for="customer_name">Customer Name</Label>
                <Input
                  id="customer_name"
                  v-model="form.customer_name"
                  type="text"
                  placeholder="Enter customer name"
                  required
                />
                <div v-if="form.errors.customer_name" class="text-sm text-destructive">
                  {{ form.errors.customer_name }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="customer_phone">Phone Number</Label>
                <Input
                  id="customer_phone"
                  v-model="form.customer_phone"
                  type="tel"
                  placeholder="Enter phone number"
                  required
                />
                <div v-if="form.errors.customer_phone" class="text-sm text-destructive">
                  {{ form.errors.customer_phone }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="customer_email">Email (Optional)</Label>
                <Input
                  id="customer_email"
                  v-model="form.customer_email"
                  type="email"
                  placeholder="Enter email address"
                />
                <div v-if="form.errors.customer_email" class="text-sm text-destructive">
                  {{ form.errors.customer_email }}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Date and Time Selection -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Calendar class="w-5 h-5" />
              <span>Reservation Details</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="reservation_date">Date</Label>
                <Input
                  id="reservation_date"
                  v-model="form.reservation_date"
                  type="date"
                  :min="minDate"
                  required
                />
                <div v-if="form.errors.reservation_date" class="text-sm text-destructive">
                  {{ form.errors.reservation_date }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="duration_minutes">Duration</Label>
                <select
                  v-model="form.duration_minutes"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option value="">Select duration</option>
                  <option
                    v-for="option in durationOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <div v-if="form.errors.duration_minutes" class="text-sm text-destructive">
                  {{ form.errors.duration_minutes }}
                </div>
              </div>
            </div>

            <!-- Actual Arrival Time -->
            <div class="space-y-2">
              <Label for="actual_arrival_time">Actual Arrival Time (Optional)</Label>
              <Input
                id="actual_arrival_time"
                v-model="form.actual_arrival_time"
                type="time"
                placeholder="Enter actual arrival time if different from reservation"
              />
              <p class="text-sm text-muted-foreground">
                Leave empty if customer hasn't arrived yet. You can update this later.
              </p>
              <div v-if="form.errors.actual_arrival_time" class="text-sm text-destructive">
                {{ form.errors.actual_arrival_time }}
              </div>
            </div>

            <!-- Time Slots -->
            <div v-if="form.table_id && form.reservation_date && form.duration_minutes" class="space-y-2">
              <Label>Available Time Slots</Label>
              <div v-if="loadingSlots" class="flex items-center space-x-2 text-muted-foreground">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
                <span>Loading available times...</span>
              </div>
              <div v-else-if="availableSlots.length > 0" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-2">
                <Button
                  v-for="slot in availableSlots"
                  :key="slot.time"
                  type="button"
                  :variant="form.reservation_time === slot.time ? 'default' : 'outline'"
                  :disabled="!slot.available"
                  @click="form.reservation_time = slot.time"
                  class="h-auto py-2 px-3"
                  :class="{ 'opacity-50 cursor-not-allowed': !slot.available }"
                >
                  <div class="text-center">
                    <div class="text-sm font-medium">{{ slot.display_time }}</div>
                    <div v-if="!slot.available" class="text-xs text-muted-foreground">Unavailable</div>
                  </div>
                </Button>
              </div>
              <Alert v-else>
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                  No available time slots for the selected date and duration. Please try a different date or shorter duration.
                </AlertDescription>
              </Alert>
              <div v-if="form.errors.reservation_time" class="text-sm text-destructive">
                {{ form.errors.reservation_time }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Additional Information -->
        <Card>
          <CardHeader>
            <CardTitle>Additional Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="special_requests">Special Requests</Label>
              <Textarea
                id="special_requests"
                v-model="form.special_requests"
                placeholder="Any special requests or dietary restrictions..."
                rows="3"
              />
              <div v-if="form.errors.special_requests" class="text-sm text-destructive">
                {{ form.errors.special_requests }}
              </div>
            </div>

            <div class="space-y-2">
              <Label for="notes">Internal Notes</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                placeholder="Internal notes for staff..."
                rows="2"
              />
              <div v-if="form.errors.notes" class="text-sm text-destructive">
                {{ form.errors.notes }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <Button type="button" variant="outline" @click="router.visit('/pos/tables')">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Calendar class="w-4 h-4 mr-2" />
            Create Reservation
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>