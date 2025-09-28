<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Edit, Users, MapPin, Calendar, Info } from 'lucide-vue-next';

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
}

interface Props {
  table: Table;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'POS Management', href: '#' },
  { title: 'Tables', href: '/pos/tables' },
  { title: `Table ${props.table.table_number}`, href: `/pos/tables/${props.table.id}` },
];

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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <Head :title="`Table ${table.table_number}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Table {{ table.table_number }}</h1>
          <p class="text-muted-foreground">{{ table.table_name }}</p>
        </div>
        <div class="flex items-center space-x-2">
          <Button variant="outline" @click="$inertia.visit('/pos/tables')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Tables
          </Button>
          <Link :href="`/pos/tables/${table.id}/edit`">
            <Button>
              <Edit class="w-4 h-4 mr-2" />
              Edit Table
            </Button>
          </Link>
        </div>
      </div>

      <!-- Table Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Info class="w-5 h-5" />
              Basic Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">Table Number:</span>
              <span class="font-semibold">{{ table.table_number }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">Table Name:</span>
              <span class="font-semibold">{{ table.table_name }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">Seats:</span>
              <div class="flex items-center space-x-1">
                <Users class="w-4 h-4 text-muted-foreground" />
                <span class="font-semibold">{{ table.seats }}</span>
              </div>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">Status:</span>
              <Badge :class="getStatusColor(table.status)" class="text-white">
                {{ getStatusText(table.status) }}
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Location Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <MapPin class="w-5 h-5" />
              Position Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">X Position:</span>
              <span class="font-semibold">
                {{ table.x_position !== null ? table.x_position : 'Not set' }}
              </span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-muted-foreground">Y Position:</span>
              <span class="font-semibold">
                {{ table.y_position !== null ? table.y_position : 'Not set' }}
              </span>
            </div>

            <div v-if="table.x_position === null && table.y_position === null"
                 class="text-sm text-muted-foreground">
              <p>Position coordinates are not set for this table. You can set them for future restaurant layout features.</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Description -->
      <Card v-if="table.description">
        <CardHeader>
          <CardTitle>Description</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-muted-foreground">{{ table.description }}</p>
        </CardContent>
      </Card>

      <!-- Timestamps -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Calendar class="w-5 h-5" />
            Timeline
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-muted-foreground">Created:</span>
            <span class="text-sm">{{ formatDate(table.created_at) }}</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-muted-foreground">Last Updated:</span>
            <span class="text-sm">{{ formatDate(table.updated_at) }}</span>
          </div>
        </CardContent>
      </Card>

      <!-- Quick Actions -->
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Button
              variant="outline"
              class="w-full"
              :disabled="table.status === 'available'"
            >
              Mark Available
            </Button>
            <Button
              variant="outline"
              class="w-full"
              :disabled="table.status === 'occupied'"
            >
              Mark Occupied
            </Button>
            <Button
              variant="outline"
              class="w-full"
              :disabled="table.status === 'reserved'"
            >
              Mark Reserved
            </Button>
            <Button
              variant="outline"
              class="w-full"
              :disabled="table.status === 'maintenance'"
            >
              Mark Maintenance
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>