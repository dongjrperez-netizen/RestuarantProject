<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { Plus, Search, MoreVertical, Users, Edit, Trash2, Eye, Calendar, Clock, UserCheck, UserPlus } from 'lucide-vue-next';

interface TableReservation {
  id: number;
  customer_name: string;
  customer_phone: string;
  party_size: number;
  reservation_date: string;
  reservation_time: string;
  duration_minutes: number;
  actual_arrival_time?: string | null;
  dining_start_time?: string | null;
  status: 'pending' | 'confirmed' | 'seated' | 'completed' | 'cancelled' | 'no_show';
  special_requests?: string;
}

interface Table {
  id: number;
  table_number: string;
  table_name: string;
  seats: number;
  status: 'available' | 'occupied' | 'reserved' | 'maintenance';
  description?: string;
  x_position?: number;
  y_position?: number;
  created_at: string;
  updated_at: string;
  current_reservation?: TableReservation;
  next_reservation?: TableReservation;
}

interface Props {
  tables: Table[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
];

const searchQuery = ref('');

const filteredTables = computed(() => {
  if (!searchQuery.value) return props.tables;

  const query = searchQuery.value.toLowerCase();
  return props.tables.filter(table =>
    table.table_number.toLowerCase().includes(query) ||
    table.table_name.toLowerCase().includes(query)
  );
});

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-500';
    case 'occupied':
      return 'bg-red-500';
    case 'reserved':
      return 'bg-yellow-500';
    case 'maintenance':
      return 'bg-gray-500';
    default:
      return 'bg-gray-400';
  }
};

const getStatusText = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const tableStats = computed(() => {
  const stats = {
    total: props.tables.length,
    available: 0,
    occupied: 0,
    reserved: 0,
    maintenance: 0,
  };

  props.tables.forEach(table => {
    stats[table.status as keyof typeof stats]++;
  });

  return stats;
});

const deleteTable = (table: Table) => {
  if (confirm(`Are you sure you want to delete table ${table.table_number}? This action cannot be undone.`)) {
    router.delete(`/pos/tables/${table.id}`, {
      onSuccess: () => {
        // Table deleted successfully
      },
    });
  }
};

const markArrived = (reservation: TableReservation) => {
  if (confirm(`Mark ${reservation.customer_name} as arrived?`)) {
    router.post(`/pos/reservations/${reservation.id}/arrived`, {}, {
      onSuccess: () => {
        // Customer marked as arrived
      },
    });
  }
};

const seatCustomers = (reservation: TableReservation) => {
  if (confirm(`Seat ${reservation.customer_name} and start their dining experience?`)) {
    router.post(`/pos/reservations/${reservation.id}/seat`, {}, {
      onSuccess: () => {
        // Customers seated successfully
      },
    });
  }
};

const confirmReservation = (reservation: TableReservation) => {
  if (confirm(`Confirm reservation for ${reservation.customer_name}?`)) {
    router.post(`/pos/reservations/${reservation.id}/confirm`, {}, {
      onSuccess: () => {
        // Reservation confirmed
      },
    });
  }
};

const completeReservation = (reservation: TableReservation) => {
  if (confirm(`Mark ${reservation.customer_name}'s reservation as complete?`)) {
    router.post(`/pos/reservations/${reservation.id}/complete`, {}, {
      onSuccess: () => {
        // Reservation completed
      },
    });
  }
};

const formatTime = (timeString: string | null | undefined) => {
  if (!timeString) {
    return 'Not set';
  }

  try {
    // Handle time string (HH:MM or HH:MM:SS)
    const time = new Date(`1970-01-01T${timeString}`);
    if (isNaN(time.getTime())) {
      return timeString; // Return original if parsing fails
    }
    return time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  } catch (error) {
    return timeString; // Return original if parsing fails
  }
};

const formatDateTime = (dateTimeString: string | null | undefined) => {
  if (!dateTimeString) {
    return 'Not set';
  }

  try {
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) {
      return 'Invalid date';
    }
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  } catch (error) {
    return 'Invalid date';
  }
};

const formatNextReservation = (dateString: string | null | undefined, timeString: string | null | undefined) => {
  if (!dateString || !timeString) {
    return 'Not set';
  }

  try {
    // Handle different date formats - could be datetime or just date
    let date = new Date(dateString);

    // If that fails, try parsing just the date part
    if (isNaN(date.getTime()) && dateString.includes(' ')) {
      date = new Date(dateString.split(' ')[0]);
    }

    const time = new Date(`1970-01-01T${timeString}`);

    if (isNaN(date.getTime()) || isNaN(time.getTime())) {
      console.log('Date parsing failed:', { dateString, timeString });
      return 'Invalid date';
    }

    const dateFormatted = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    const timeFormatted = time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    return `${dateFormatted} ${timeFormatted}`;
  } catch (error) {
    console.error('Error formatting reservation date:', error, { dateString, timeString });
    return 'Invalid date';
  }
};
</script>

<template>
  <Head title="Tables" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 mx-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Tables</h1>
          <p class="text-muted-foreground">Manage your restaurant tables and seating arrangements</p>
        </div>
        <Link href="/pos/tables/create">
          <Button>
            <Plus class="w-4 h-4 mr-2" />
            Add Table
          </Button>
        </Link>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <Users class="w-5 h-5 text-muted-foreground" />
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Tables</p>
                <p class="text-2xl font-bold">{{ tableStats.total }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Available</p>
                <p class="text-2xl font-bold">{{ tableStats.available }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Occupied</p>
                <p class="text-2xl font-bold">{{ tableStats.occupied }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Reserved</p>
                <p class="text-2xl font-bold">{{ tableStats.reserved }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 rounded-full bg-gray-500"></div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Maintenance</p>
                <p class="text-2xl font-bold">{{ tableStats.maintenance }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Search and Filters -->
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-4">
            <div class="relative flex-1 max-w-sm">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
              <Input
                v-model="searchQuery"
                placeholder="Search tables..."
                class="pl-10"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tables List -->
      <Card>
        <CardHeader>
          <CardTitle>All Tables ({{ filteredTables.length }})</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Table #</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Seats</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Current/Next Reservation</TableHead>
                <TableHead>Reservation Actions</TableHead>
                <TableHead>Description</TableHead>
                <TableHead class="w-20">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="table in filteredTables" :key="table.id">
                <TableCell class="font-medium">{{ table.table_number }}</TableCell>
                <TableCell>{{ table.table_name }}</TableCell>
                <TableCell>
                  <div class="flex items-center space-x-1">
                    <Users class="w-4 h-4 text-muted-foreground" />
                    <span>{{ table.seats }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(table.status)" class="text-white">
                    {{ getStatusText(table.status) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div v-if="table.current_reservation" class="text-xs space-y-1">
                    <div class="font-medium text-red-600">Current: {{ table.current_reservation.customer_name }}</div>
                    <div class="text-muted-foreground flex items-center space-x-1">
                      <Users class="w-3 h-3" />
                      <span>{{ table.current_reservation.party_size }}</span>
                    </div>
                    <div class="text-muted-foreground space-y-0.5">
                      <div class="flex items-center space-x-1">
                        <Clock class="w-3 h-3" />
                        <span>Reserved: {{ formatTime(table.current_reservation.reservation_time) }}</span>
                      </div>
                      <div v-if="table.current_reservation.actual_arrival_time" class="flex items-center space-x-1">
                        <Clock class="w-3 h-3" />
                        <span>Arrived: {{ formatDateTime(table.current_reservation.actual_arrival_time) }}</span>
                      </div>
                      <div v-if="table.current_reservation.dining_start_time" class="flex items-center space-x-1">
                        <Clock class="w-3 h-3" />
                        <span>Started: {{ formatDateTime(table.current_reservation.dining_start_time) }}</span>
                      </div>
                    </div>
                  </div>
                  <div v-else-if="table.next_reservation" class="text-xs space-y-1">
                    <div class="font-medium text-yellow-600">Next: {{ table.next_reservation.customer_name }}</div>
                    <div class="text-muted-foreground flex items-center space-x-1">
                      <Users class="w-3 h-3" />
                      <span>{{ table.next_reservation.party_size }}</span>
                      <Clock class="w-3 h-3 ml-2" />
                      <span>{{ formatNextReservation(table.next_reservation.reservation_date, table.next_reservation.reservation_time) }}</span>
                    </div>
                  </div>
                  <div v-else class="text-xs text-muted-foreground">No reservations</div>
                </TableCell>
                <TableCell>
                  <!-- Reservation Action Buttons -->
                  <div v-if="table.current_reservation" class="flex flex-col space-y-1">
                    <!-- Pending Reservation Actions -->
                    <div v-if="table.current_reservation.status === 'pending'" class="flex flex-col space-y-1">
                      <Button
                        size="sm"
                        variant="outline"
                        @click="confirmReservation(table.current_reservation!)"
                        class="text-xs h-7"
                      >
                        <UserCheck class="w-3 h-3 mr-1" />
                        Confirm
                      </Button>
                    </div>

                    <!-- Confirmed Reservation Actions -->
                    <div v-else-if="table.current_reservation.status === 'confirmed'" class="flex flex-col space-y-1">
                      <Button
                        v-if="!table.current_reservation.actual_arrival_time"
                        size="sm"
                        variant="default"
                        @click="markArrived(table.current_reservation!)"
                        class="text-xs h-7 bg-blue-600 hover:bg-blue-700"
                      >
                        <UserPlus class="w-3 h-3 mr-1" />
                        Mark Arrived
                      </Button>
                      <Button
                        size="sm"
                        variant="default"
                        @click="seatCustomers(table.current_reservation!)"
                        class="text-xs h-7 bg-green-600 hover:bg-green-700"
                      >
                        <Users class="w-3 h-3 mr-1" />
                        Seat Now
                      </Button>
                    </div>

                    <!-- Seated Reservation Actions -->
                    <div v-else-if="table.current_reservation.status === 'seated'" class="flex flex-col space-y-1">
                      <Button
                        size="sm"
                        variant="outline"
                        @click="completeReservation(table.current_reservation!)"
                        class="text-xs h-7"
                      >
                        <Clock class="w-3 h-3 mr-1" />
                        Complete
                      </Button>
                    </div>
                  </div>
                  <div v-else-if="table.next_reservation" class="flex flex-col space-y-1">
                    <!-- Next Reservation Actions -->
                    <div v-if="table.next_reservation.status === 'pending'" class="flex flex-col space-y-1">
                      <Button
                        size="sm"
                        variant="outline"
                        @click="confirmReservation(table.next_reservation!)"
                        class="text-xs h-7"
                      >
                        <UserCheck class="w-3 h-3 mr-1" />
                        Confirm
                      </Button>
                    </div>
                  </div>
                  <div v-else class="text-xs text-muted-foreground">-</div>
                </TableCell>
                <TableCell>
                  <span class="text-sm text-muted-foreground">
                    {{ table.description || 'No description' }}
                  </span>
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="sm">
                        <MoreVertical class="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem asChild>
                        <Link :href="`/pos/tables/${table.id}`">
                          <Eye class="w-4 h-4 mr-2" />
                          View
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link :href="`/pos/reservations/create?table_id=${table.id}`">
                          <Calendar class="w-4 h-4 mr-2" />
                          Reserve Table
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link :href="`/pos/tables/${table.id}/edit`">
                          <Edit class="w-4 h-4 mr-2" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        class="text-destructive"
                        @click="deleteTable(table)"
                      >
                        <Trash2 class="w-4 h-4 mr-2" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="filteredTables.length === 0">
                <TableCell colspan="8" class="text-center py-8">
                  <div class="text-muted-foreground">
                    <Users class="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
                    <div class="text-lg mb-2">No tables found</div>
                    <div class="text-sm">
                      {{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by adding your first table.' }}
                    </div>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>