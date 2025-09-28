<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import { Users, Clock, AlertTriangle, ShoppingCart, Search } from 'lucide-vue-next';

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

interface Employee {
  employee_id: number;
  firstname: string;
  lastname: string;
  email: string;
  role: {
    role_name: string;
  };
}

interface Props {
  tables: Table[];
  employee: Employee;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const selectedStatus = ref<string | null>(null);

const filteredTables = computed(() => {
  return props.tables.filter(table => {
    const matchesSearch = table.table_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         table.table_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         (table.description && table.description.toLowerCase().includes(searchQuery.value.toLowerCase()));

    const matchesStatus = selectedStatus.value === null || table.status === selectedStatus.value;

    return matchesSearch && matchesStatus;
  });
});

const availableTables = computed(() => filteredTables.value.filter(t => t.status === 'available'));
const occupiedTables = computed(() => filteredTables.value.filter(t => t.status === 'occupied'));
const reservedTables = computed(() => filteredTables.value.filter(t => t.status === 'reserved'));

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-800 border-green-200';
    case 'occupied':
      return 'bg-red-100 text-red-800 border-red-200';
    case 'reserved':
      return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    case 'maintenance':
      return 'bg-gray-100 text-gray-800 border-gray-200';
    default:
      return 'bg-gray-100 text-gray-800 border-gray-200';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'available':
      return 'text-green-600';
    case 'occupied':
      return 'text-red-600';
    case 'reserved':
      return 'text-yellow-600';
    case 'maintenance':
      return 'text-gray-600';
    default:
      return 'text-gray-600';
  }
};

const canTakeOrder = (table: Table) => {
  return table.status === 'available' || table.status === 'occupied' || table.status === 'reserved';
};

const statusFilters = [
  { value: null, label: 'All Tables', count: computed(() => filteredTables.value.length) },
  { value: 'available', label: 'Available', count: computed(() => availableTables.value.length) },
  { value: 'occupied', label: 'Occupied', count: computed(() => occupiedTables.value.length) },
  { value: 'reserved', label: 'Reserved', count: computed(() => reservedTables.value.length) },
];
</script>

<template>
  <Head title="Take Order - Select Table" />

  <WaiterLayout :employee="employee">
    <template #title>Take Order</template>

    <div class="p-4 space-y-6">
      <!-- Header Section -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <ShoppingCart class="h-5 w-5" />
            Select a Table to Take Order
          </CardTitle>
          <CardDescription>
            Choose an available table to start taking a customer's order. You can take orders for available, occupied, or reserved tables.
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4">
          <!-- Search Bar -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <Input
              v-model="searchQuery"
              placeholder="Search tables by name, number, or description..."
              class="pl-10"
            />
          </div>

          <!-- Status Filters -->
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="filter in statusFilters"
              :key="filter.value || 'all'"
              @click="selectedStatus = filter.value"
              :variant="selectedStatus === filter.value ? 'default' : 'outline'"
              size="sm"
              class="text-xs"
            >
              {{ filter.label }} ({{ filter.count.value }})
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Tables Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <Card
          v-for="table in filteredTables"
          :key="table.id"
          class="transition-all hover:shadow-md"
          :class="[
            table.status === 'available' ? 'border-green-200 bg-green-50' :
            table.status === 'occupied' ? 'border-red-200 bg-red-50' :
            table.status === 'reserved' ? 'border-yellow-200 bg-yellow-50' :
            'border-gray-200 bg-gray-50'
          ]"
        >
          <CardContent class="p-4 space-y-4">
            <!-- Table Header -->
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-lg truncate">{{ table.table_name }}</h3>
                <p class="text-sm text-muted-foreground">Table #{{ table.table_number }}</p>
              </div>
              <Badge :class="getStatusColor(table.status)" class="capitalize text-xs ml-2">
                {{ table.status }}
              </Badge>
            </div>

            <!-- Table Info -->
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm">
                <Users :class="getStatusIcon(table.status)" class="h-4 w-4 flex-shrink-0" />
                <span>{{ table.seats }} seats</span>
              </div>
              <div v-if="table.description" class="text-sm text-muted-foreground">
                {{ table.description }}
              </div>
            </div>

            <!-- Action Button -->
            <div class="pt-2 border-t">
              <Link
                v-if="canTakeOrder(table)"
                :href="route('waiter.orders.create', { tableId: table.id })"
                class="w-full"
              >
                <Button class="w-full" :variant="table.status === 'available' ? 'default' : 'outline'">
                  <ShoppingCart class="h-4 w-4 mr-2" />
                  Take Order
                </Button>
              </Link>

              <Button
                v-else
                disabled
                class="w-full"
                variant="outline"
              >
                <AlertTriangle class="h-4 w-4 mr-2" />
                Not Available
              </Button>
            </div>

            <!-- Status Indicator -->
            <div class="text-xs text-center">
              <span v-if="table.status === 'available'" class="text-green-600">
                Ready for new customers
              </span>
              <span v-else-if="table.status === 'occupied'" class="text-red-600">
                Currently occupied - can add more orders
              </span>
              <span v-else-if="table.status === 'reserved'" class="text-yellow-600">
                Reserved - can take orders
              </span>
              <span v-else class="text-gray-600">
                Under maintenance
              </span>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-if="filteredTables.length === 0" class="text-center py-12">
        <AlertTriangle class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-muted-foreground mb-2">
          {{ searchQuery || selectedStatus ? 'No tables match your criteria' : 'No Tables Found' }}
        </h3>
        <p class="text-muted-foreground">
          {{ searchQuery || selectedStatus ? 'Try adjusting your search or filter settings' : 'There are no tables configured for this restaurant yet.' }}
        </p>
        <Button
          v-if="searchQuery || selectedStatus"
          @click="() => { searchQuery = ''; selectedStatus = null; }"
          variant="outline"
          class="mt-4"
        >
          Clear Filters
        </Button>
      </div>
    </div>
  </WaiterLayout>
</template>